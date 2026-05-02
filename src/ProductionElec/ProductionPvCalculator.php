<?php

declare(strict_types=1);

namespace CalculDpePHP\ProductionElec;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Production photovoltaïque et électricité autoconsommée (§16.2 p.103-105).
 *
 * Si `presence_production_pv ≠ 1` : aucun calcul.
 * Si `presence_production_pv = 1` :
 *   1. Ppv = Σ_panneaux ki × Scapteur × r × Σ_mois(Epv_j) × C   [kWh/an]
 *   2. Celec_tot = conso_5_usages_elec + (Cum + Ccom_ecl) × Sh
 *   3. Tapl = Σ taplpi × Celec_tot_i / Celec_tot
 *   4. Tcv = Ppv / Celec_tot
 *   5. Tap = 1 / (1/Tcv + 1/Tapl)
 *   6. Celec_ac = Celec_tot × Tap
 *
 * Les consommations ef_conso et ep_conso de la sortie sont réduites de l'autoconsommation
 * après ce calcul, avant que EmissionGesCalculator ne calcule les classes DPE.
 *
 * @spec-section 16.2
 * @spec-pages   103-105
 * @spec-source  resources/specsplitted/16-eclairage-prod-elec/02-prod-electricite.md
 * @xml-input    logement.production_elec_enr.donnee_entree.{presence_production_pv, panneaux_pv_collection}
 * @xml-input    logement.sortie.ef_conso.{conso_ch, conso_ecs, conso_fr, conso_eclairage, conso_totale_auxiliaire}
 * @xml-input    logement.sortie.sortie_par_energie_collection (énergie électricité id=1)
 * @xml-output   logement.production_elec_enr.donnee_intermediaire.{production_pv, conso_elec_ac, taux_autoproduction}
 * @xml-output   logement.sortie.ef_conso.{conso_ch, conso_ecs, …} (modifiés -Celec_ac_i)
 * @xml-output   logement.sortie.ep_conso.{ep_conso_ch, ep_conso_ecs, …} (modifiés)
 * @depends-on   \CalculDpePHP\Sortie\EpConsoCalculator, \CalculDpePHP\Sortie\SortieParEnergieAggregator
 * @tables       prod_elec/e_pv, prod_elec/tv_coef_orientation_pv
 */
final class ProductionPvCalculator implements CalculatorInterface
{
    private const RENDEMENT_MODULE  = 0.17; // §16.2 p.103 : r = 17%
    private const COEF_PERTE        = 0.86; // §16.2 p.103 : C = 0,86
    private const SURFACE_PAR_MODULE = 1.6; // §16.2 p.103 : surface forfaitaire 1,6 m²/module

    /** Taplp_i — §16.2 p.105 (valeurs par usage) */
    private const TAPLPI = [
        'chauffage'              => 0.02,
        'ecs'                    => 0.05,
        'refroidissement'        => 0.25,
        'eclairage'              => 0.05,
        'auxiliaire_ventilation' => 0.50,
        'auxiliaire_distribution'=> 0.10,
        'autres'                 => 0.45,
    ];

    /** Cum (kWh_ef/m²/an) — usages mobiliers §16.2 p.105 */
    private const CUM_MAISON     = 29.0;
    private const CUM_COLLECTIF  = 27.0;

    /** Ccom_ecl (kWh_ef/m²/an) — éclairage parties communes §16.2 p.105 */
    private const CCOM_ECL_MAISON    = 0.0;
    private const CCOM_ECL_COLLECTIF = 1.1;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Sortie\EpConsoCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Trouver le nœud production_elec_enr ─────────────────────────
        $pvNode = null;
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'production_elec_enr') {
                $pvNode = $child;
                break;
            }
        }

        if ($pvNode === null) {
            return;
        }

        $presence = $accessor->getIntOrNull('./donnee_entree/presence_production_pv', $pvNode) ?? 0;
        if ($presence !== 1) {
            return;
        }

        // ── 2. Zone climatique ──────────────────────────────────────────────
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
        if ($zoneId === null) {
            return;
        }

        $ePvTable     = $context->tables->load('prod_elec/e_pv');
        $coefOrTable  = $context->tables->load('prod_elec/tv_coef_orientation_pv');
        $ePvByMonth   = $ePvTable[$zoneId] ?? null;

        if ($ePvByMonth === null) {
            return;
        }

        // ── 3. Surface habitable ────────────────────────────────────────────
        $sh = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node) ?? 0.0;
        if ($sh <= 0.0) {
            // Fallback sur immeuble
            $sh = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node) ?? 0.0;
        }

        // ── 4. Type de bâtiment (maison / collectif) ───────────────────────
        $nAppart    = $accessor->getIntOrNull('./caracteristique_generale/nombre_appartement', $node);
        $isCollectif = ($nAppart !== null && $nAppart > 0);
        $cum        = $isCollectif ? self::CUM_COLLECTIF  : self::CUM_MAISON;
        $ccomEcl    = $isCollectif ? self::CCOM_ECL_COLLECTIF : self::CCOM_ECL_MAISON;

        // ── 5. Production PV totale Ppv (kWh/an) ───────────────────────────
        $ppv = $this->computePpv($pvNode, $accessor, $ePvByMonth, $coefOrTable);

        // ── 6. Lire les consommations électriques depuis la sortie ──────────
        $sortie = null;
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'sortie') {
                $sortie = $child;
                break;
            }
        }
        if ($sortie === null) {
            return;
        }

        [$consoElecCh, $consoElecEcs] = $this->readConsoElecFromGenerators($node, $accessor);

        $efNode   = $this->getChild($sortie, 'ef_conso');
        $consoFr  = $efNode ? ($accessor->getFloatOrNull('./conso_fr',                $efNode) ?? 0.0) : 0.0;
        $consoEcl = $efNode ? ($accessor->getFloatOrNull('./conso_eclairage',          $efNode) ?? 0.0) : 0.0;
        $consoAux = $efNode ? ($accessor->getFloatOrNull('./conso_totale_auxiliaire',  $efNode) ?? 0.0) : 0.0;
        $consoAuxGenCh  = $efNode ? ($accessor->getFloatOrNull('./conso_auxiliaire_generation_ch',  $efNode) ?? 0.0) : 0.0;
        $consoAuxGenEcs = $efNode ? ($accessor->getFloatOrNull('./conso_auxiliaire_generation_ecs', $efNode) ?? 0.0) : 0.0;
        $consoAuxDistCh = $efNode ? ($accessor->getFloatOrNull('./conso_auxiliaire_distribution_ch', $efNode) ?? 0.0) : 0.0;
        $consoAuxDistEcs = $efNode ? ($accessor->getFloatOrNull('./conso_auxiliaire_distribution_ecs', $efNode) ?? 0.0) : 0.0;
        $consoAuxVent   = $efNode ? ($accessor->getFloatOrNull('./conso_auxiliaire_ventilation', $efNode) ?? 0.0) : 0.0;

        // ── 7. Celec_tot — §16.2 p.105 ─────────────────────────────────────
        $consoElec5    = $consoElecCh + $consoElecEcs; // conso_5_usages pour électricité
        // Ajouter éclairage + fr + aux (qui sont toujours électriques)
        $consoElec5 += $consoFr + $consoEcl + $consoAux;
        $celecAu       = ($cum + $ccomEcl) * $sh;
        $celecTot      = $consoElec5 + $celecAu;

        if ($celecTot <= 0.0 || $ppv <= 0.0) {
            $this->writePvResult($pvNode, $accessor, $context, $ppv, 0.0, 0.0);
            return;
        }

        // ── 8. Tapl — pondération par usage ────────────────────────────────
        $taplWeighted = 0.0;
        $taplWeighted += self::TAPLPI['chauffage']               * $consoElecCh;
        $taplWeighted += self::TAPLPI['chauffage']               * $consoAuxGenCh;
        $taplWeighted += self::TAPLPI['ecs']                     * $consoElecEcs;
        $taplWeighted += self::TAPLPI['ecs']                     * $consoAuxGenEcs;
        $taplWeighted += self::TAPLPI['refroidissement']         * $consoFr;
        $taplWeighted += self::TAPLPI['eclairage']               * $consoEcl;
        $taplWeighted += self::TAPLPI['auxiliaire_ventilation']  * $consoAuxVent;
        $taplWeighted += self::TAPLPI['auxiliaire_distribution'] * $consoAuxDistCh;
        $taplWeighted += self::TAPLPI['auxiliaire_distribution'] * $consoAuxDistEcs;
        $taplWeighted += self::TAPLPI['autres']                  * $celecAu;
        $tapl = $taplWeighted / $celecTot;

        // ── 9. Tcv et Tap ──────────────────────────────────────────────────
        $tcv = $ppv / $celecTot;
        $tap = ($tcv <= 0.0 || $tapl <= 0.0) ? 0.0 : 1.0 / (1.0 / $tcv + 1.0 / $tapl);

        // ── 10. Celec_ac par usage ──────────────────────────────────────────
        $celecAc = $celecTot * $tap;

        $acCh        = $tapl > 0.0 ? $celecAc * (self::TAPLPI['chauffage'] * $consoElecCh / $celecTot) / $tapl : 0.0;
        $acAuxGenCh  = $tapl > 0.0 ? $celecAc * (self::TAPLPI['chauffage'] * $consoAuxGenCh / $celecTot) / $tapl : 0.0;
        $acEcs       = $tapl > 0.0 ? $celecAc * (self::TAPLPI['ecs'] * $consoElecEcs / $celecTot) / $tapl : 0.0;
        $acAuxGenEcs = $tapl > 0.0 ? $celecAc * (self::TAPLPI['ecs'] * $consoAuxGenEcs / $celecTot) / $tapl : 0.0;
        $acFr        = $tapl > 0.0 ? $celecAc * (self::TAPLPI['refroidissement'] * $consoFr / $celecTot) / $tapl : 0.0;
        $acEcl       = $tapl > 0.0 ? $celecAc * (self::TAPLPI['eclairage'] * $consoEcl / $celecTot) / $tapl : 0.0;
        $acAuxVent   = $tapl > 0.0 ? $celecAc * (self::TAPLPI['auxiliaire_ventilation'] * $consoAuxVent / $celecTot) / $tapl : 0.0;
        $acAuxDistCh = $tapl > 0.0 ? $celecAc * (self::TAPLPI['auxiliaire_distribution'] * $consoAuxDistCh / $celecTot) / $tapl : 0.0;
        $acAuxDistEcs = $tapl > 0.0 ? $celecAc * (self::TAPLPI['auxiliaire_distribution'] * $consoAuxDistEcs / $celecTot) / $tapl : 0.0;

        $acAux = $acAuxGenCh + $acAuxGenEcs + $acAuxDistCh + $acAuxDistEcs + $acAuxVent;

        // ── 11. Écriture dans production_elec_enr/donnee_intermediaire ───────
        $this->writePvResult($pvNode, $accessor, $context, $ppv, $celecAc, $tap);

        // ── 12. Stocker dans le contexte pour ProductionElectriciteCalculator ──
        $context->set('pv.production_pv',      $ppv);
        $context->set('pv.conso_elec_ac',      $celecAc);
        $context->set('pv.taux_autoproduction', $tap);
        $context->set('pv.ac_ch',              $acCh + $acAuxGenCh);
        $context->set('pv.ac_ecs',             $acEcs + $acAuxGenEcs);
        $context->set('pv.ac_fr',              $acFr);
        $context->set('pv.ac_eclairage',       $acEcl);
        $context->set('pv.ac_auxiliaire',      $acAux);

        // ── 13. Réduire ef_conso dans le sortie (autoconsommation déduite) ──
        if ($efNode !== null && $sh > 0.0) {
            $this->reduceEfConso($efNode, $accessor, $acCh, $acEcs, $acFr, $acEcl, $acAux, $sh);
        }

        // ── 14. Réduire ep_conso dans le sortie ────────────────────────────
        $epNode = $this->getChild($sortie, 'ep_conso');
        $coefEp = $context->period === \CalculDpePHP\Common\Period::POST_2026 ? 2.3 : 1.9;
        if ($epNode !== null && $sh > 0.0) {
            $this->reduceEpConso($epNode, $accessor, $acCh, $acEcs, $acFr, $acEcl, $acAux, $coefEp, $sh);
        }
    }

    /**
     * §16.2 p.103 : Ppv = Σ_panneaux Σ_mois ki × Scapteur × r × Epv_j × C
     *
     * @param array<int, float> $ePvByMonth
     * @param array<int, array{ki: float}> $coefOrTable
     */
    private function computePpv(
        DOMElement $pvNode,
        NodeAccessor $accessor,
        array $ePvByMonth,
        array $coefOrTable
    ): float {
        $ppv = 0.0;

        foreach ($pvNode->childNodes as $coll) {
            if (!$coll instanceof DOMElement || $coll->nodeName !== 'panneaux_pv_collection') {
                continue;
            }
            foreach ($coll->childNodes as $panneau) {
                if (!$panneau instanceof DOMElement || $panneau->nodeName !== 'panneaux_pv') {
                    continue;
                }

                // Coefficient ki depuis tv_coef_orientation_pv_id ou orientation/inclinaison
                $tvId    = $accessor->getIntOrNull('./tv_coef_orientation_pv_id', $panneau);
                $ki      = $tvId !== null ? (float)(($coefOrTable[$tvId] ?? [])['ki'] ?? 1.0) : 1.0;

                // Surface : surface_totale_capteurs OU nombre_module × 1,6
                $surface = $accessor->getFloatOrNull('./surface_totale_capteurs', $panneau);
                if ($surface === null || $surface <= 0.0) {
                    $nbModule = $accessor->getIntOrNull('./nombre_module', $panneau) ?? 0;
                    $surface  = $nbModule * self::SURFACE_PAR_MODULE;
                }

                if ($surface <= 0.0 || $ki <= 0.0) {
                    continue;
                }

                // Σ_mois Epv_j
                $sumEpv = 0.0;
                for ($mois = 1; $mois <= 12; $mois++) {
                    $sumEpv += $ePvByMonth[$mois] ?? 0.0;
                }

                $ppv += $ki * $surface * self::RENDEMENT_MODULE * $sumEpv * self::COEF_PERTE;
            }
        }

        return $ppv;
    }

    /**
     * Lit conso_ch et conso_ecs pour l'électricité (energie_id=1 ou 12) depuis les générateurs.
     * Lit directement depuis installation_chauffage/generateur_chauffage et installation_ecs/generateur_ecs.
     *
     * @return array{float, float} [conso_ch_elec, conso_ecs_elec]
     */
    private function readConsoElecFromGenerators(DOMElement $logement, NodeAccessor $accessor): array
    {
        $consoChElec  = 0.0;
        $consoEcsElec = 0.0;

        foreach ($logement->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            // Générateurs de chauffage
            if ($child->nodeName === 'installation_chauffage_collection') {
                foreach ($child->getElementsByTagName('generateur_chauffage') as $gen) {
                    if (!$gen instanceof DOMElement) {
                        continue;
                    }
                    $eId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen);
                    if ($eId === 1 || $eId === 12) {
                        $consoChElec += $accessor->getFloatOrNull('./donnee_intermediaire/conso_ch', $gen) ?? 0.0;
                    }
                }
            }
            // Générateurs ECS
            if ($child->nodeName === 'installation_ecs_collection') {
                foreach ($child->getElementsByTagName('generateur_ecs') as $gen) {
                    if (!$gen instanceof DOMElement) {
                        continue;
                    }
                    $eId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen);
                    if ($eId === 1 || $eId === 12) {
                        $consoEcsElec += $accessor->getFloatOrNull('./donnee_intermediaire/conso_ecs', $gen) ?? 0.0;
                    }
                }
            }
        }

        return [$consoChElec, $consoEcsElec];
    }

    /**
     * Réduit les valeurs ef_conso de l'autoconsommation PV.
     * §16.2 p.105 : Celec_ac_i déduite de chaque usage électrique.
     */
    private function reduceEfConso(
        DOMElement $efNode,
        NodeAccessor $accessor,
        float $acCh, float $acEcs, float $acFr, float $acEcl, float $acAux,
        float $sh
    ): void {
        $fields = [
            'conso_ch'                  => $acCh,
            'conso_ecs'                 => $acEcs,
            'conso_fr'                  => $acFr,
            'conso_eclairage'           => $acEcl,
            'conso_totale_auxiliaire'   => $acAux,
        ];

        foreach ($fields as $field => $reduction) {
            if ($reduction <= 0.0) {
                continue;
            }
            $current = $accessor->getFloatOrNull('./' . $field, $efNode);
            if ($current !== null) {
                $accessor->setChildValue($efNode, $field, max(0.0, $current - $reduction));
            }
        }

        // Mettre à jour conso_5_usages et conso_5_usages_m2
        $totalReduction = $acCh + $acEcs + $acFr + $acEcl + $acAux;
        $conso5 = $accessor->getFloatOrNull('./conso_5_usages', $efNode);
        if ($conso5 !== null) {
            $newConso5 = max(0.0, $conso5 - $totalReduction);
            $accessor->setChildValue($efNode, 'conso_5_usages',    $newConso5);
            $accessor->setChildValue($efNode, 'conso_5_usages_m2', $sh > 0.0 ? floor($newConso5 / $sh) : 0.0);
        }
    }

    /**
     * Réduit les valeurs ep_conso de l'autoconsommation PV.
     * §16.2 p.105 : EP réduit de coef_ep × Celec_ac_i.
     */
    private function reduceEpConso(
        DOMElement $epNode,
        NodeAccessor $accessor,
        float $acCh, float $acEcs, float $acFr, float $acEcl, float $acAux,
        float $coefEp,
        float $sh
    ): void {
        $fields = [
            'ep_conso_ch'                 => $acCh,
            'ep_conso_ecs'                => $acEcs,
            'ep_conso_fr'                 => $acFr,
            'ep_conso_eclairage'          => $acEcl,
            'ep_conso_totale_auxiliaire'  => $acAux,
        ];

        foreach ($fields as $field => $reduction) {
            if ($reduction <= 0.0) {
                continue;
            }
            $current = $accessor->getFloatOrNull('./' . $field, $epNode);
            if ($current !== null) {
                $accessor->setChildValue($epNode, $field, max(0.0, $current - $coefEp * $reduction));
            }
        }

        // Mettre à jour ep_conso_5_usages et ep_conso_5_usages_m2
        $totalReductionEp = $coefEp * ($acCh + $acEcs + $acFr + $acEcl + $acAux);
        $ep5 = $accessor->getFloatOrNull('./ep_conso_5_usages', $epNode);
        if ($ep5 !== null) {
            $newEp5 = max(0.0, $ep5 - $totalReductionEp);
            $accessor->setChildValue($epNode, 'ep_conso_5_usages',    $newEp5);
            $accessor->setChildValue($epNode, 'ep_conso_5_usages_m2', $sh > 0.0 ? floor($newEp5 / $sh) : 0.0);
        }
    }

    private function writePvResult(
        DOMElement $pvNode,
        NodeAccessor $accessor,
        CalculationContext $context,
        float $ppv,
        float $celecAc,
        float $tap
    ): void {
        $di = $this->ensureChild($context->document, $pvNode, 'donnee_intermediaire');
        $accessor->setChildValue($di, 'production_pv',       $ppv);
        $accessor->setChildValue($di, 'conso_elec_ac',       $celecAc);
        $accessor->setChildValue($di, 'taux_autoproduction', $tap);
    }

    private function ensureChild(\DOMDocument $doc, DOMElement $parent, string $tag): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $tag) {
                return $c;
            }
        }
        $el = $doc->createElement($tag);
        $parent->appendChild($el);
        return $el;
    }

    private function getChild(DOMElement $parent, string $tag): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $tag) {
                return $child;
            }
        }
        return null;
    }
}
