<?php

declare(strict_types=1);

namespace CalculDpe\Chauffage;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
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
 *   − pertes_generateur_ch_recup     : calculées par RendementCombustionCalculator
 *   Si non encore calculées, valeur dans contexte = 0.
 *
 * @spec-section 9.1.1
 * @spec-pages   57-59
 * @spec-source  resources/specsplitted/09-conso-chauffage/01-installation-seule/01-conso.md
 * @xml-input    (intermédiaires via contexte : GV, fraction_ch, DH mensuel, pertes_recup)
 * @xml-output   logement.sortie.apport_et_besoin.{besoin_ch, besoin_ch_depensier}
 * @depends-on   \CalculDpe\Apport\FCalculator, \CalculDpe\Enveloppe\EnveloppeAggregator, \CalculDpe\Ventilation\VentilationAggregator
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
            '\CalculDpe\Apport\FCalculator',
            '\CalculDpe\Enveloppe\EnveloppeAggregator',
            '\CalculDpe\Ventilation\VentilationAggregator',
            '\CalculDpe\Ecs\BesoinEcsCalculator',
            '\CalculDpe\Ecs\Rendement\StockageCalculator', // writes Qgw to DOM
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
        $pertesGenRecup         = (float)$context->get('ch.pertes_generateur_recup',         0.0);
        $pertesGenRecupDep      = (float)$context->get('ch.pertes_generateur_recup_dep',     0.0);

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
