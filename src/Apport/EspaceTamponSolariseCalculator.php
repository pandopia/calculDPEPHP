<?php

declare(strict_types=1);

namespace CalculDpe\Apport;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Traitement des espaces tampons solarisés (ETS, vérandas) — §6.3 p.50-52.
 *
 * Pour chaque ETS :
 *   1. Calcule T = coef_transparence_ets depuis tv_coef_transparence_ets_id.
 *   2. Calcule bver depuis enum_cfg_isolation_lnc_id + zone climatique (tv_b['veranda']).
 *   3. Stocke la contribution mensuelle Sse_veranda dans le contexte (apport.sse_ets_mensuel).
 *
 * Formule (§6.3 p.50-51) :
 *   Sse_veranda,j = Ssd,j + Ssind,j × bver
 *   Sst,j  = Σ_k Ak × (0.8×T + 0.024) × C1k,j   (baies ETS↔extérieur, Fek=1)
 *   Ssd,j  = T × Σ_i Ai × Swi × Fei × C1i,j       (baies logement↔ETS)
 *   Ssind,j = Sst,j − Ssd,j
 *
 * @spec-section 6.3
 * @spec-pages   50-52
 * @spec-source  resources/specsplitted/06-apports-gratuits/03-espaces-tampons-solarises.md
 * @xml-input    ets.donnee_entree.{tv_coef_transparence_ets_id, enum_cfg_isolation_lnc_id}
 * @xml-input    ets.baie_ets_collection.baie_ets.donnee_entree.{surface_totale_baie, enum_orientation_id, enum_inclinaison_vitrage_id}
 * @xml-output   ets.donnee_intermediaire.{coef_transparence_ets, bver}
 * @depends-on   \CalculDpe\Apport\SurfaceSudEquivalenteCalculator
 * @tables       apports/tv_coef_transparence_ets, enveloppe/tv_b
 */
final class EspaceTamponSolariseCalculator implements CalculatorInterface
{
    /** enum_cfg_isolation_lnc_id → [orientation_key, isolation_key] pour lookup bver */
    private const CFG_MAP = [
        6  => ['nord',      'isole'],
        7  => ['sud',       'isole'],
        8  => ['est_ouest', 'isole'],
        9  => ['nord',      'non_isole'],
        10 => ['sud',       'non_isole'],
        11 => ['est_ouest', 'non_isole'],
    ];

    private const ORIENT_MAP = [1 => 'sud', 2 => 'nord', 3 => 'est', 4 => 'ouest'];
    private const INCL_MAP   = [1 => 'inf25', 2 => 'pente', 3 => 'sup75', 4 => 'horizontal'];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'ets';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $de       = null;
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                $de = $child;
                break;
            }
        }
        if ($de === null) {
            return;
        }

        $transparenceId = $accessor->getIntOrNull('./tv_coef_transparence_ets_id', $de);
        $cfgId          = $accessor->getIntOrNull('./enum_cfg_isolation_lnc_id', $de);

        $tvT = $context->tables->load('apports/tv_coef_transparence_ets');
        $T   = ($transparenceId !== null) ? (float)($tvT[$transparenceId] ?? 0.5) : 0.5;

        $bver = $this->lookupBver($context, $cfgId);

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'coef_transparence_ets', $T);
        $accessor->setChildValue($intermediaire, 'bver', $bver);

        $this->accumulateEtsSse($node, $accessor, $context, $T, $bver);
    }

    private function lookupBver(CalculationContext $context, ?int $cfgId): float
    {
        if ($cfgId === null || !isset(self::CFG_MAP[$cfgId])) {
            return 1.0;
        }
        $zoneGroupe = $context->zoneGroupe
            ?? CalculationContext::zoneGroupeFromId($context->zoneClimatique);
        if ($zoneGroupe === null) {
            return 1.0;
        }
        [$orientKey, $isolKey] = self::CFG_MAP[$cfgId];
        $tvB = $context->tables->load('enveloppe/tv_b');
        return (float)($tvB['veranda'][$zoneGroupe][$orientKey][$isolKey] ?? 1.0);
    }

    /**
     * Calcule la contribution mensuelle Sse_veranda,j et l'accumule dans le contexte.
     * Utilise uniquement les baies ETS↔extérieur (Sst) — les baies logement↔ETS (Ssd)
     * seraient des baies vitrées déjà comptées dans SurfaceSudEquivalenteCalculator.
     * Par simplification spec §6.3 : Sse_veranda,j ≈ Sst,j × bver.
     */
    private function accumulateEtsSse(
        DOMElement $node,
        NodeAccessor $accessor,
        CalculationContext $context,
        float $T,
        float $bver,
    ): void {
        $zoneId   = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
        $tvC1     = $zoneId !== null ? ($context->tables->load('apports/tv_c1')[$zoneId] ?? null) : null;
        $sseFact  = 0.8 * $T + 0.024; // §6.3 : coefficient Sst = Ak × (0.8T + 0.024) × C1

        $sseMensuel = array_fill(1, 12, 0.0);

        foreach ($node->getElementsByTagName('baie_ets') as $baieEts) {
            $deB = null;
            foreach ($baieEts->childNodes as $ch) {
                if ($ch instanceof DOMElement && $ch->nodeName === 'donnee_entree') {
                    $deB = $ch;
                    break;
                }
            }
            if ($deB === null) {
                continue;
            }
            $surf   = $accessor->getFloatOrNull('./surface_totale_baie', $deB);
            $orient = $accessor->getIntOrNull('./enum_orientation_id', $deB);
            $incl   = $accessor->getIntOrNull('./enum_inclinaison_vitrage_id', $deB);
            if ($surf === null) {
                continue;
            }
            for ($j = 1; $j <= 12; $j++) {
                $c1 = $this->lookupC1($tvC1, $j, $orient, $incl);
                $sseMensuel[$j] += $surf * $sseFact * $c1;
            }
        }

        for ($j = 1; $j <= 12; $j++) {
            $sseMensuel[$j] *= $bver;
        }

        $existing = $context->get('apport.sse_ets_mensuel', array_fill(1, 12, 0.0));
        for ($j = 1; $j <= 12; $j++) {
            $existing[$j] += $sseMensuel[$j];
        }
        $context->set('apport.sse_ets_mensuel', $existing);
    }

    private function lookupC1(?array $zoneTable, int $month, ?int $orientId, ?int $inclId): float
    {
        if ($zoneTable === null) {
            return 1.0;
        }
        $monthRow = $zoneTable[$month] ?? null;
        if ($monthRow === null) {
            return 1.0;
        }
        if ($inclId === 4) {
            return (float)($monthRow['horizontal'] ?? 1.0);
        }
        $orientKey = self::ORIENT_MAP[$orientId ?? 0] ?? 'sud';
        $inclKey   = self::INCL_MAP[$inclId ?? 3] ?? 'sup75';
        return (float)($monthRow[$orientKey][$inclKey] ?? 1.0);
    }
}
