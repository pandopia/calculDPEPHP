<?php

declare(strict_types=1);

namespace CalculDpe\Sortie;

use CalculDpe\Common\Period;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc <sortie><ep_conso> : énergie primaire (×coef énergie). Calcule la classe énergie A→G.
 *
 * Coefficients EF→EP (§1 p.6) :
 *   - Électricité pré-2026  : ×2.3
 *   - Électricité post-2026 : ×1.9
 *   - Gaz, fioul, autres    : ×1.0
 *
 * Auxiliaires et éclairage : toujours électricité.
 * Classe énergie (provisoire) : sera mise à jour par EmissionGesCalculator avec
 *   classe_bilan_dpe = WORST(classe_energie, classe_ges).
 *
 * @spec-section  1
 * @spec-pages    6
 * @spec-source   resources/specsplitted/00-meta/01-methode-conventionnelle.md
 * @xml-input     sortie.ef_conso.{conso_ch, conso_ecs, conso_eclairage, conso_fr, conso_auxiliaire_*}
 *                installation_chauffage.generateur_chauffage_collection.generateur_chauffage.donnee_entree.enum_type_energie_id
 *                installation_ecs.generateur_ecs_collection.generateur_ecs.donnee_entree.enum_type_energie_id
 * @xml-output    sortie.ep_conso.{ep_conso_ch, ep_conso_ecs, ep_conso_eclairage, ep_conso_fr,
 *                    ep_conso_auxiliaire_*, ep_conso_totale_auxiliaire, ep_conso_5_usages,
 *                    ep_conso_5_usages_m2, classe_bilan_dpe}
 * @depends-on    \CalculDpe\Sortie\EfConsoCalculator
 * @tables        (aucune)
 */
final class EpConsoCalculator implements CalculatorInterface
{
    private const EP_ELEC_PRE2026  = 2.3;
    private const EP_ELEC_POST2026 = 1.9;
    private const EP_OTHER         = 1.0;

    /** Seuils de classe énergie 2021 (kWhEP/m².an) — A≤70, B≤110, C≤180, D≤250, E≤330, F≤420, G>420 */
    private const ENERGY_THRESHOLDS = [
        'A' => 70, 'B' => 110, 'C' => 180, 'D' => 250, 'E' => 330, 'F' => 420,
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return ['\CalculDpe\Sortie\EfConsoCalculator'];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Paramètres du bâtiment ─────────────────────────────────────────
        $shLogement = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node);
        $shImmeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node) ?? 0.0;
        $nbreAppt   = $accessor->getFloatOrNull('./caracteristique_generale/nombre_appartement', $node) ?? 1.0;
        $isZone     = $shLogement !== null;
        $surface    = $isZone ? (float)$shLogement : $shImmeuble;

        $epElec = ($context->period === Period::POST_2026) ? self::EP_ELEC_POST2026 : self::EP_ELEC_PRE2026;

        // ── 2. Chauffage EP ──────────────────────────────────────────────────
        [$epChTotal, $epChDepTotal, $cleRepartitionCh] =
            $this->aggregateChEp($accessor, $node, $nbreAppt, $epElec);

        $epConsoChEf    = $isZone ? $epChTotal * $cleRepartitionCh : $epChTotal;
        // EP dépensier chauffage = EP conventionnel (la méthode 3CL utilise toujours
        // le scénario conventionnel pour le calcul d'énergie primaire — le scénario
        // dépensier ne s'applique qu'aux coûts et émissions GES).
        $epConsoChDepEf = $epConsoChEf;

        // ── 3. ECS EP ────────────────────────────────────────────────────────
        [$epEcsTotal, , $cleRepartitionEcs] =
            $this->aggregateEcsEp($accessor, $node, $nbreAppt, $epElec);

        $epConsoEcsEf    = $isZone ? $epEcsTotal * $cleRepartitionEcs : $epEcsTotal;
        // EP dépensier ECS = EP conventionnel (même convention que chauffage).
        $epConsoEcsDepEf = $epConsoEcsEf;

        // ── 4. Lecture ef_conso depuis le DOM ────────────────────────────────
        $sortie  = $accessor->ensureSortie($node);
        $efConso = $this->ensureChild($context->document, $sortie, 'ef_conso');
        $epConso = $this->ensureChild($context->document, $sortie, 'ep_conso');

        $efConsoFr    = $accessor->getFloatOrNull('./conso_fr',           $efConso) ?? 0.0;
        $efConsoFrDep = $accessor->getFloatOrNull('./conso_fr_depensier', $efConso) ?? 0.0;
        $efConsoEcl   = $accessor->getFloatOrNull('./conso_eclairage',    $efConso) ?? 0.0;

        // Froid : énergie électrique (COP déjà intégré dans ConsoFroidCalculator)
        $epConsoFr    = $efConsoFr    * $epElec;
        $epConsoFrDep = $efConsoFrDep * $epElec;

        // Éclairage : toujours électricité
        $epConsoEcl = $efConsoEcl * $epElec;

        // ── 5. Auxiliaires (toujours électricité) ────────────────────────────
        $cauxGenCh     = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ch',               $efConso) ?? 0.0;
        $cauxGenChDep  = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ch_depensier',      $efConso) ?? 0.0;
        $cauxDistCh    = $accessor->getFloatOrNull('./conso_auxiliaire_distribution_ch',             $efConso) ?? 0.0;
        $cauxGenEcs    = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ecs',              $efConso) ?? 0.0;
        $cauxGenEcsDep = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ecs_depensier',    $efConso) ?? 0.0;
        $cauxDistEcs   = $accessor->getFloatOrNull('./conso_auxiliaire_distribution_ecs',            $efConso) ?? 0.0;
        $cauxVent      = $accessor->getFloatOrNull('./conso_auxiliaire_ventilation',                 $efConso) ?? 0.0;

        $epCauxGenCh     = $cauxGenCh  * $epElec;
        // §15.1 convention: EP dépensier uses nominal EF (not depensier EF)
        $epCauxGenChDep  = $cauxGenCh  * $epElec;
        $epCauxDistCh    = $cauxDistCh * $epElec;
        $epCauxGenEcs    = $cauxGenEcs    * $epElec;
        // §15.1 convention: EP dépensier uses nominal EF (not depensier EF)
        $epCauxGenEcsDep = $cauxGenEcs * $epElec;
        $epCauxDistEcs   = $cauxDistEcs   * $epElec;
        $epCauxVent      = $cauxVent      * $epElec;
        $epCauxTotal     = $epCauxGenCh + $epCauxDistCh + $epCauxGenEcs + $epCauxDistEcs + $epCauxVent;

        // ── 6. Agrégat 5 usages EP ───────────────────────────────────────────
        $ep5   = $epConsoChEf + $epConsoEcsEf + $epConsoFr + $epConsoEcl + $epCauxTotal;
        $ep5m2 = $surface > 0.0 ? (int)floor($ep5 / $surface) : 0;

        // Classe énergie provisoire (EmissionGesCalculator mettra à jour classe_bilan_dpe)
        $classeEnergie = $this->classeEnergetique($ep5m2);

        // ── 7. Écriture dans sortie/ep_conso ─────────────────────────────────
        $accessor->setChildValue($epConso, 'ep_conso_ch',                                  $epConsoChEf);
        $accessor->setChildValue($epConso, 'ep_conso_ch_depensier',                        $epConsoChDepEf);
        $accessor->setChildValue($epConso, 'ep_conso_ecs',                                 $epConsoEcsEf);
        $accessor->setChildValue($epConso, 'ep_conso_ecs_depensier',                       $epConsoEcsDepEf);
        $accessor->setChildValue($epConso, 'ep_conso_eclairage',                           $epConsoEcl);
        $accessor->setChildValue($epConso, 'ep_conso_auxiliaire_generation_ch',            $epCauxGenCh);
        $accessor->setChildValue($epConso, 'ep_conso_auxiliaire_generation_ch_depensier',  $epCauxGenChDep);
        $accessor->setChildValue($epConso, 'ep_conso_auxiliaire_distribution_ch',          $epCauxDistCh);
        $accessor->setChildValue($epConso, 'ep_conso_auxiliaire_generation_ecs',           $epCauxGenEcs);
        $accessor->setChildValue($epConso, 'ep_conso_auxiliaire_generation_ecs_depensier', $epCauxGenEcsDep);
        $accessor->setChildValue($epConso, 'ep_conso_auxiliaire_distribution_ecs',         $epCauxDistEcs);
        $accessor->setChildValue($epConso, 'ep_conso_auxiliaire_ventilation',              $epCauxVent);
        $accessor->setChildValue($epConso, 'ep_conso_totale_auxiliaire',                   $epCauxTotal);
        $accessor->setChildValue($epConso, 'ep_conso_fr',                                  $epConsoFr);
        $accessor->setChildValue($epConso, 'ep_conso_fr_depensier',                        $epConsoFrDep);
        $accessor->setChildValue($epConso, 'ep_conso_5_usages',                            $ep5);
        $accessor->setChildValue($epConso, 'ep_conso_5_usages_m2',                         $ep5m2);
        $accessor->setChildValue($epConso, 'classe_bilan_dpe',                             $classeEnergie);
    }

    /**
     * Agrège les consommations de chauffage en énergie primaire.
     *
     * @return array{float, float, float} [ep_total, ep_total_dep, cle_repartition_ch]
     */
    private function aggregateChEp(
        NodeAccessor $accessor,
        DOMElement $logement,
        float $nbreAppt,
        float $epElec,
    ): array {
        $collection = $this->getChild($logement, 'installation_chauffage_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        $sumEchantillon = 0.0;
        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_chauffage') {
                continue;
            }
            $ne = $accessor->getFloatOrNull('./donnee_entree/nombre_logement_echantillon', $install) ?? 0.0;
            $sumEchantillon += $ne;
        }
        if ($sumEchantillon <= 0.0) {
            $sumEchantillon = 1.0;
        }

        $totalEp    = 0.0;
        $totalEpDep = 0.0;
        $cleRepartition = 1.0;

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_chauffage') {
                continue;
            }

            $rdimEff  = $this->computeRdimChauffage($accessor, $install, $nbreAppt, $sumEchantillon);
            $conso    = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ch',           $install) ?? 0.0;
            $consoDep = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ch_depensier', $install) ?? 0.0;

            $energyId = $this->firstGeneratorEnergyType($accessor, $install, 'generateur_chauffage');
            $epCoeff  = $this->epCoeffForEnergyType($energyId, $epElec);

            $totalEp    += $conso    * $rdimEff * $epCoeff;
            $totalEpDep += $consoDep * $rdimEff * $epCoeff;

            $cle = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ch', $install);
            if ($cle !== null && $cle > 0.0) {
                $cleRepartition = $cle;
            }
        }

        return [$totalEp, $totalEpDep, $cleRepartition];
    }

    /**
     * Agrège les consommations ECS en énergie primaire.
     *
     * @return array{float, float, float} [ep_total, ep_total_dep, cle_repartition_ecs]
     */
    private function aggregateEcsEp(
        NodeAccessor $accessor,
        DOMElement $logement,
        float $nbreAppt,
        float $epElec,
    ): array {
        $collection = $this->getChild($logement, 'installation_ecs_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        $sumLogement = 0.0;
        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_ecs') {
                continue;
            }
            $ne = $accessor->getFloatOrNull('./donnee_entree/nombre_logement', $install) ?? 0.0;
            $sumLogement += $ne;
        }
        if ($sumLogement <= 0.0) {
            $sumLogement = 1.0;
        }

        $totalEp    = 0.0;
        $totalEpDep = 0.0;
        $cleRepartition = 1.0;

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_ecs') {
                continue;
            }

            $rdimEff = $this->computeRdimEcs($accessor, $install, $nbreAppt, $sumLogement);

            $installEp    = 0.0;
            $installEpDep = 0.0;
            foreach ($install->getElementsByTagName('generateur_ecs') as $gen) {
                if (!$gen instanceof DOMElement) {
                    continue;
                }
                $conso    = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ecs',           $gen) ?? 0.0;
                $consoDep = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ecs_depensier', $gen) ?? 0.0;

                $energyId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen) ?? 2;
                $epCoeff  = $this->epCoeffForEnergyType($energyId, $epElec);

                $installEp    += $conso    * $epCoeff;
                $installEpDep += $consoDep * $epCoeff;
            }

            $totalEp    += $installEp    * $rdimEff;
            $totalEpDep += $installEpDep * $rdimEff;

            $cle = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ecs', $install);
            if ($cle !== null && $cle > 0.0) {
                $cleRepartition = $cle;
            }
        }

        return [$totalEp, $totalEpDep, $cleRepartition];
    }

    private function firstGeneratorEnergyType(
        NodeAccessor $accessor,
        DOMElement $install,
        string $generatorTag,
    ): int {
        foreach ($install->getElementsByTagName($generatorTag) as $gen) {
            if (!$gen instanceof DOMElement) {
                continue;
            }
            $id = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen);
            if ($id !== null) {
                return $id;
            }
        }
        return 2; // default: gaz
    }

    private function epCoeffForEnergyType(int $energyTypeId, float $epElec): float
    {
        return $energyTypeId === 1 ? $epElec : self::EP_OTHER;
    }

    private function classeEnergetique(int $ep5m2): string
    {
        foreach (self::ENERGY_THRESHOLDS as $classe => $threshold) {
            if ($ep5m2 <= $threshold) {
                return $classe;
            }
        }
        return 'G';
    }

    private function computeRdimChauffage(
        NodeAccessor $accessor,
        DOMElement $install,
        float $nbreAppt,
        float $sumEchantillon,
    ): float {
        $methode     = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $install) ?? 1;
        $typeInstall = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id',    $install) ?? 1;
        $rdim        = $accessor->getFloatOrNull('./donnee_entree/rdim',                       $install) ?? 1.0;
        $ratioVirt   = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',       $install) ?? 1.0;

        if ($methode === 1) {
            $rdimEff = $rdim;
        } elseif ($typeInstall === 1) {
            $rdimEff = $nbreAppt * $ratioVirt / $sumEchantillon;
        } else {
            $rdimEff = $rdim;
        }

        return max(1e-9, $rdimEff);
    }

    private function computeRdimEcs(
        NodeAccessor $accessor,
        DOMElement $install,
        float $nbreAppt,
        float $sumLogement,
    ): float {
        $methode     = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $install) ?? 1;
        $typeInstall = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id',    $install) ?? 1;
        $rdim        = $accessor->getFloatOrNull('./donnee_entree/rdim',                       $install) ?? 1.0;
        $ratioVirt   = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',       $install) ?? 1.0;

        if ($methode === 1) {
            $rdimEff = $rdim;
        } elseif ($typeInstall === 1) {
            $rdimEff = $nbreAppt * $ratioVirt / $sumLogement;
        } else {
            $rdimEff = $rdim;
        }

        return max(1e-9, $rdimEff);
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
