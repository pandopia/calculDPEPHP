<?php

declare(strict_types=1);

namespace CalculDpe\Apport;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Surface Sud équivalente — §6.2 p.45.
 *
 * Ssej = Σ_i Ai × Swi × (Fe1i × Fe2i) × C1i,j
 *
 * La valeur stockée dans `<surface_sud_equivalente>` est la somme annuelle :
 * Sse = Σ_j Ssej (sur les 12 mois), ce qui permet de la comparer au verif.
 *
 * Les valeurs mensuelles Ssej sont aussi stockées dans le contexte (clé
 * `apport.sse_mensuel`) pour que FCalculator puisse les lire mois par mois.
 *
 * La surface vitrée des portes n'est pas incluse (§6.2 p.45).
 *
 * Mapping enum_orientation_id → clé C1 :
 *   1=sud, 2=nord, 3=est, 4=ouest, 5→ skipped (horizontal géré par inclinaison)
 *
 * Mapping enum_inclinaison_vitrage_id → clé C1 :
 *   1=inf25, 2=pente, 3=sup75, 4=horizontal
 *
 * @spec-section 6.2
 * @spec-pages   45
 * @spec-source  resources/specsplitted/06-apports-gratuits/02-surface-sud-equivalente/00-overview.md
 * @xml-input    logement.enveloppe.baie_vitree_collection.baie_vitree.donnee_entree.{surface_totale_baie, enum_orientation_id, enum_inclinaison_vitrage_id}
 * @xml-input    logement.enveloppe.baie_vitree_collection.baie_vitree.donnee_intermediaire.{sw, fe1, fe2}
 * @xml-output   context:apport.sse_annuel (annual sum), context:apport.sse_mensuel (monthly array)
 * @depends-on   \CalculDpe\Enveloppe\BaieVitree\SwCalculator, \CalculDpe\Enveloppe\BaieVitree\Fe1Calculator, \CalculDpe\Enveloppe\BaieVitree\Fe2Calculator
 * @tables       apports/tv_c1
 */
final class SurfaceSudEquivalenteCalculator implements CalculatorInterface
{
    /** @var array<int, string> */
    private const ORIENT_KEY = [1 => 'sud', 2 => 'nord', 3 => 'est', 4 => 'ouest'];

    /** @var array<int, string> */
    private const INCL_KEY = [1 => 'inf25', 2 => 'pente', 3 => 'sup75', 4 => 'horizontal'];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            \CalculDpe\Enveloppe\BaieVitree\SwCalculator::class,
            \CalculDpe\Enveloppe\BaieVitree\Fe1Calculator::class,
            \CalculDpe\Enveloppe\BaieVitree\Fe2Calculator::class,
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $zoneId   = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;

        $tvC1 = $zoneId !== null ? ($context->tables->load('apports/tv_c1')[$zoneId] ?? null) : null;

        // Monthly sums Σ_i Ai × Swi × Fei × C1i,j  (j = 1..12)
        /** @var array<int, float> */
        $sseMensuel = array_fill(1, 12, 0.0);

        foreach ($node->getElementsByTagName('baie_vitree') as $baie) {
            if (!$baie instanceof DOMElement) {
                continue;
            }
            $de = null;
            $di = null;
            foreach ($baie->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }
                if ($child->nodeName === 'donnee_entree') {
                    $de = $child;
                } elseif ($child->nodeName === 'donnee_intermediaire') {
                    $di = $child;
                }
            }
            if ($de === null || $di === null) {
                continue;
            }

            $surface = $accessor->getFloatOrNull('./surface_totale_baie', $de);
            $sw      = $accessor->getFloatOrNull('./sw', $di);
            $fe1     = $accessor->getFloatOrNull('./fe1', $di) ?? 1.0;
            $fe2     = $accessor->getFloatOrNull('./fe2', $di) ?? 1.0;

            if ($surface === null || $sw === null || $surface <= 0.0 || $sw <= 0.0) {
                continue;
            }

            $orientId = $accessor->getIntOrNull('./enum_orientation_id', $de);
            $inclId   = $accessor->getIntOrNull('./enum_inclinaison_vitrage_id', $de);
            $base     = $surface * $sw * $fe1 * $fe2;

            for ($j = 1; $j <= 12; $j++) {
                $c1 = $this->lookupC1($tvC1, $j, $orientId, $inclId);
                $sseMensuel[$j] += $base * $c1;
            }
        }

        $context->set('apport.sse_mensuel', $sseMensuel);
        $context->set('apport.sse_annuel', array_sum($sseMensuel));
    }

    /**
     * Lookup C1 for a given zone, month, orientation and inclinaison.
     * Returns 1.0 if table or key not found (neutral factor).
     *
     * @param array<int, mixed>|null $zoneTable C1 table already sliced to zone
     */
    private function lookupC1(?array $zoneTable, int $month, ?int $orientId, ?int $inclId): float
    {
        if ($zoneTable === null) {
            return 1.0;
        }

        $monthRow = $zoneTable[$month] ?? null;
        if ($monthRow === null) {
            return 1.0;
        }

        // Horizontal inclinaison : C1 is orientation-independent
        if ($inclId === 4) {
            return (float)($monthRow['horizontal'] ?? 1.0);
        }

        $orientKey = self::ORIENT_KEY[$orientId ?? 0] ?? null;
        $inclKey   = self::INCL_KEY[$inclId ?? 0] ?? null;

        if ($orientKey === null || $inclKey === null) {
            return 1.0;
        }

        return (float)($monthRow[$orientKey][$inclKey] ?? 1.0);
    }
}
