<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Besoin annuel de chauffage Bch et Bch_depensier (§9.1.1 p.57-59).
 *
 * Formule mensuelle :
 *   Bchj (kWh) = BVj × DH19j / 1000 − (Qrec_chauff_j + Qg,w_rec_j + Qgen_rec_j) / 1000
 *
 * Formule annuelle :
 *   Bch = Σ_j Bchj = GV × Σ(DH19j × (1−Fj)) / 1000 − pertes_recup_annuelles
 *       = GV × Σ(DH19j) × (1 − fraction_ch) / 1000 − pertes_recup_annuelles
 *
 * (Identité exacte car fraction_ch = Σ(Fj×DH19j)/Σ(DH19j) par définition)
 *
 * Pertes récupérées :
 *   − pertes_distribution_ecs_recup  : calculées par EcsDistributionRecupCalculator (TASK-E26+)
 *   − pertes_stockage_ecs_recup      : calculées par EcsStockageRecupCalculator
 *   − pertes_generateur_ch_recup     : calculées ici (computePertesGenerateurRecup)
 *   Si non encore calculées, valeur dans contexte = 0.
 *
 * @spec-section 9.1.1
 * @spec-pages   57-59
 * @spec-source  resources/specsplitted/09-conso-chauffage/01-installation-seule/01-conso.md
 * @xml-input    (intermédiaires via contexte : GV, fraction_ch, DH mensuel, pertes_recup)
 * @xml-output   logement.sortie.apport_et_besoin.{besoin_ch, besoin_ch_depensier}
 * @depends-on   \CalculDpePHP\Apport\FCalculator, \CalculDpePHP\Enveloppe\EnveloppeAggregator, \CalculDpePHP\Ventilation\VentilationAggregator
 * @tables       reference/tv_sollicitations
 */
final class BesoinChauffageCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Apport\FCalculator',
            '\CalculDpePHP\Enveloppe\EnveloppeAggregator',
            '\CalculDpePHP\Ventilation\VentilationAggregator',
            '\CalculDpePHP\Ecs\BesoinEcsCalculator',
            '\CalculDpePHP\Ecs\Rendement\StockageCalculator', // writes Qgw to DOM
            '\CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator', // writes qp0/pn
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. GV total (W/K) ──────────────────────────────────────────────────
        $dpParois = (float)$context->get('enveloppe.dp_parois', 0.0);
        $dpPT     = (float)$context->get('enveloppe.dp_pont_thermique', 0.0);
        $hvent    = (float)$context->get('ventilation.hvent', 0.0);
        $hperm    = (float)$context->get('ventilation.hperm', 0.0);
        $gv       = $dpParois + $dpPT + $hvent + $hperm;

        // ── 2. Fractions d'apports gratuits (de FCalculator) ──────────────────
        $fraction19 = (float)$context->get('apport.fraction_ch',          0.0);
        $fraction21 = (float)$context->get('apport.fraction_ch_depensier', 0.0);

        // ── 3. Σ(DH19j) et Σ(DH21j) sur la saison de chauffe ─────────────────
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
        $altId  = $context->classeAltitude  !== null ? (int)$context->classeAltitude  : null;
        $tvS    = ($zoneId !== null && $altId !== null)
            ? ($context->tables->load('reference/tv_sollicitations')[$zoneId][$altId] ?? null)
            : null;

        $sumDH19 = 0.0;
        $sumDH21 = 0.0;
        if ($tvS !== null) {
            for ($j = 1; $j <= 12; $j++) {
                $row = $tvS[$j] ?? null;
                if ($row === null) {
                    continue;
                }
                if (isset($row['DH19']) && $row['DH19'] !== null) {
                    $sumDH19 += (float)$row['DH19'];
                }
                if (isset($row['DH21']) && $row['DH21'] !== null) {
                    $sumDH21 += (float)$row['DH21'];
                }
            }
        }

        // ── 4. Besoin brut avant pertes récupérées (kWh) ──────────────────────
        $bchBrut19 = $gv * $sumDH19 * (1.0 - $fraction19) / 1000.0;
        $bchBrut21 = $gv * $sumDH21 * (1.0 - $fraction21) / 1000.0;

        // ── 5. Pertes récupérées ────────────────────────────────────────────────
        $pertesDistribRecup     = (float)$context->get('ecs.pertes_distribution_recup',     0.0);
        $pertesDistribRecupDep  = (float)$context->get('ecs.pertes_distribution_recup_dep',  0.0);
        $pertesStockageRecup    = $this->computePertesStockageRecup($node, $context, $tvS, false);
        $pertesStockageRecupDep = $this->computePertesStockageRecup($node, $context, $tvS, true);

        $pertesGenRecup         = $this->computePertesGenerateurRecup($context, $tvS, $gv, false);
        $pertesGenRecupDep      = $this->computePertesGenerateurRecup($context, $tvS, $gv, true);
        $context->set('ch.pertes_generateur_recup',     $pertesGenRecup);
        $context->set('ch.pertes_generateur_recup_dep', $pertesGenRecupDep);

        $besoinCh         = max(0.0, $bchBrut19 - $pertesDistribRecup - $pertesStockageRecup    - $pertesGenRecup);
        $besoinChDepensier = max(0.0, $bchBrut21 - $pertesDistribRecupDep - $pertesStockageRecupDep - $pertesGenRecupDep);

        // ── 6. Écriture dans sortie.apport_et_besoin ───────────────────────────
        $sortie         = $accessor->ensureSortie($node);
        $apportEtBesoin = $this->ensureApportEtBesoin($sortie);
        $accessor->setChildValue($apportEtBesoin, 'besoin_ch',          $besoinCh);
        $accessor->setChildValue($apportEtBesoin, 'besoin_ch_depensier', $besoinChDepensier);

        // Stocker pour les calculators de consommation chauffage (§9.x)
        $context->set('chauffage.besoin_ch',          $besoinCh);
        $context->set('chauffage.besoin_ch_depensier', $besoinChDepensier);
        $context->set('chauffage.gv',                 $gv);
        $context->set('ecs.pertes_stockage_recup',    $pertesStockageRecup);
    }

    /**
     * §9.1.1 — Pertes récupérées des générateurs de chauffage à combustion
     * situés en volume chauffé (kWh « échelle LICIEL »).
     *
     * Formule mensuelle (open3cl 9_generateur_ch.js::calc_Qrec_gen_j) :
     *   Qrec_gen_j = 0,48 × Cper × QP0 × Dper_j
     *   Cper  = 0,75 si ventouse, 0,5 sinon
     *   Dper_j (chauffage)   = min(Nref_j ; 1,3 × Bch_hp_j / (0,3 × Pn))
     *   Dper_j (ecs)         = Nref_j × 1790 / 8760
     *   Dper_j (mixte)       = min(Nref_j ; somme des deux)
     *   Bch_hp_j = GV × (1 − Fj) × DH_j   [Wh]
     *
     * Échelle : LICIEL divise le résultat (Wh) par 10⁶ (bug historique reproduit
     * par open3cl via ratio=1000 en bug_for_bug_compat, puis conversion kWh) —
     * vérifié sur 2662E2147774H : 334 913 Wh → sortie 0.33491343…
     * L'impact sur besoin_ch est donc négligeable mais la balise doit être remplie.
     *
     * @spec-source resources/specsplitted/09-conso-chauffage/01-installation-seule/01-conso.md
     */
    private function computePertesGenerateurRecup(
        CalculationContext $context,
        ?array $tvS,
        float $gv,
        bool $depensier
    ): float {
        if ($tvS === null || $gv <= 0.0) {
            return 0.0;
        }

        $accessor = new NodeAccessor($context->document);

        // Générateurs à combustion en volume chauffé avec QP0 calculé
        $gens = [];
        foreach ($context->document->getElementsByTagName('generateur_chauffage') as $gen) {
            $qp0 = $accessor->getFloatOrNull('./donnee_intermediaire/qp0', $gen);
            if ($qp0 === null || $qp0 <= 0.0) {
                continue;
            }
            $posVol = $accessor->getIntOrNull('./donnee_entree/position_volume_chauffe', $gen) ?? 0;
            if ($posVol === 0) {
                continue;
            }
            $genId = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $gen);
            if ($genId !== null && $genId >= 50 && $genId <= 52) {
                continue; // générateurs à air chaud exclus (open3cl)
            }
            $pn = $accessor->getFloatOrNull('./donnee_intermediaire/pn', $gen) ?? 0.0;
            if ($pn <= 0.0) {
                continue;
            }
            $ventouse = $accessor->getIntOrNull('./donnee_entree/presence_ventouse', $gen) ?? 0;
            $usage    = $accessor->getIntOrNull('./donnee_entree/enum_usage_generateur_id', $gen) ?? 1;
            $gens[] = [
                'qp0'   => $qp0,
                'pn'    => $pn,
                'cper'  => $ventouse === 1 ? 0.75 : 0.5,
                'usage' => $usage, // 1=chauffage, 2=ecs, 3=chauffage+ecs
            ];
        }
        if ($gens === []) {
            return 0.0;
        }

        $fjKey   = $depensier ? 'apport.fj_mensuel_dep' : 'apport.fj_mensuel';
        $fj      = (array)$context->get($fjKey, array_fill(1, 12, 0.0));
        $dhKey   = $depensier ? 'DH21' : 'DH19';
        $nrefKey = $depensier ? 'Nref21' : 'Nref19';

        $totalWh = 0.0;
        for ($j = 1; $j <= 12; $j++) {
            $row  = $tvS[$j] ?? null;
            $dhj  = $row !== null ? (float)($row[$dhKey]   ?? 0.0) : 0.0;
            $nref = $row !== null ? (float)($row[$nrefKey] ?? 0.0) : 0.0;
            if ($dhj <= 0.0 || $nref <= 0.0) {
                continue;
            }
            $bchHpJ = $gv * (1.0 - (float)($fj[$j] ?? 0.0)) * $dhj; // Wh

            foreach ($gens as $g) {
                $dper = match ($g['usage']) {
                    2       => $nref * 1790.0 / 8760.0,
                    3       => min($nref, 1.3 * $bchHpJ / (0.3 * $g['pn']) + $nref * 1790.0 / 8760.0),
                    default => min($nref, 1.3 * $bchHpJ / (0.3 * $g['pn'])),
                };
                $totalWh += 0.48 * $g['cper'] * $g['qp0'] * $dper;
            }
        }

        // Échelle LICIEL : Wh / 10⁶ (cf. doc-block)
        return $totalWh / 1e6;
    }

    /**
     * §9.1.1 — Pertes de stockage ECS récupérées pour le chauffage (kWh).
     *
     * Qgw_total_ecs = Σ_instal(0.48 × Σ_gen(Qgw) × rdim / 8760)   [W]
     * pertes = Qgw_total_ecs × Σ_j(Nref19_j or Nref21_j) / 1000   [kWh]
     *
     * Seules les installations individuelles (enum_type_installation_id=1) contribuent.
     * Les générateurs hors volume chauffé (position_volume_chauffe=0
     * ou position_volume_chauffe_stockage=0) sont exclus.
     */
    private function computePertesStockageRecup(
        DOMElement $logement,
        CalculationContext $context,
        ?array $tvS,
        bool $depensier
    ): float {
        if ($tvS === null) {
            return 0.0;
        }

        $accessor = new NodeAccessor($context->document);
        $nrefKey  = $depensier ? 'Nref21' : 'Nref19';

        $sumNref = 0.0;
        for ($j = 1; $j <= 12; $j++) {
            $sumNref += (float)(($tvS[$j] ?? [])[$nrefKey] ?? 0.0);
        }

        $qgwTotalEcs = 0.0;
        $installations = $context->document->getElementsByTagName('installation_ecs');

        foreach ($installations as $install) {
            $typeInstallId = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $install);
            if ($typeInstallId !== 1) {
                continue; // collective → pas de récupération stockage
            }
            $rdim = $accessor->getFloatOrNull('./donnee_entree/rdim', $install) ?? 1.0;

            $qgwInstall = 0.0;
            foreach ($install->getElementsByTagName('generateur_ecs') as $gen) {
                $posVol      = $accessor->getIntOrNull('./donnee_entree/position_volume_chauffe', $gen) ?? 1;
                $posStockage = $accessor->getIntOrNull('./donnee_entree/position_volume_chauffe_stockage', $gen) ?? 1;
                if ($posVol === 0 || $posStockage === 0) {
                    continue;
                }
                $qgwGen = $accessor->getFloatOrNull('./donnee_intermediaire/Qgw', $gen) ?? 0.0;
                $qgwInstall += $qgwGen;
            }

            $qgwTotalEcs += 0.48 * $qgwInstall * $rdim / 8760.0;
        }

        return $qgwTotalEcs * $sumNref / 1000.0;
    }

    private function ensureApportEtBesoin(DOMElement $sortie): DOMElement
    {
        foreach ($sortie->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'apport_et_besoin') {
                return $child;
            }
        }
        $el = $sortie->ownerDocument->createElement('apport_et_besoin');
        $sortie->appendChild($el);
        return $el;
    }
}
