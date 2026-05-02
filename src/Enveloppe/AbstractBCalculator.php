<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Calcul du coefficient b de réduction des déperditions — §3.1 (p.8-12).
 *
 * Algorithme :
 *   1. Si `enum_type_adjacence_id` est un cas direct (extérieur, terre-plein, VS, sous-sol non
 *      chauffé, paroi enterrée, mitoyenneté, occupation discontinue, local non déperditif) :
 *      lecture directe dans `tv_b['cas_directs']`.
 *   2. Si l'adjacence est un espace tampon solarisé (10) : lecture dans `tv_b['veranda']` selon
 *      zone climatique × orientation × isolation lc (déduite de `enum_cfg_isolation_lnc_id`).
 *   3. Sinon : tableau Aiu/Aue × UV,ue (4 sous-tables selon `enum_cfg_isolation_lnc_id`).
 *      - Aiu = `surface_aiu`, Aue = `surface_aue` (lus dans la donnée d'entrée).
 *      - UV,ue = `tv_b['uv_ue'][adjacence]` (table p.9 du PDF).
 *      - Si Aue = 0 → b = 0.
 *
 * @spec-section 3.1
 * @spec-pages 8-12
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/01-coef-reduction-b/00-detail.md
 * @xml-input  <paroi>.donnee_entree.{enum_type_adjacence_id, enum_cfg_isolation_lnc_id, surface_aiu, surface_aue, enum_orientation_id}
 * @xml-output <paroi>.donnee_intermediaire.b
 * @tables tv_b
 */
abstract class AbstractBCalculator implements CalculatorInterface
{
    public function dependencies(): array
    {
        return [];
    }

    /** @return list<string> */
    abstract protected function appliesToTags(): array;

    public function appliesTo(DOMElement $node): bool
    {
        return in_array($node->nodeName, $this->appliesToTags(), true);
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException(sprintf('%s sans <donnee_entree>.', $node->nodeName));
        }

        $b = $this->resolveB($entree, $accessor, $context);

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'b', $b);
    }

    private function resolveB(DOMElement $entree, NodeAccessor $accessor, CalculationContext $context): float
    {
        // Bug-for-bug compat (open3cl behaviour): when tv_coef_reduction_deperdition_id is
        // present in donnee_entree, use it to look up b directly from the LICIEL table.
        $tvCoefId = $accessor->getIntOrNull('./tv_coef_reduction_deperdition_id', $entree);
        if ($tvCoefId !== null) {
            $table = $context->tables->load('enveloppe/tv_coef_reduction_deperdition_id');
            if (isset($table[$tvCoefId])) {
                return (float)$table[$tvCoefId];
            }
        }

        $adjacence = $accessor->getIntOrNull('./enum_type_adjacence_id', $entree);
        $tvB = $context->tables->load('enveloppe/tv_b');

        // 1. Cas directs
        if ($adjacence !== null && isset($tvB['cas_directs'][$adjacence])) {
            return (float)$tvB['cas_directs'][$adjacence];
        }

        // 2. Véranda / espace tampon solarisé
        if ($adjacence === 10) {
            return $this->resolveVeranda($entree, $accessor, $context, $tvB['veranda']);
        }

        // 3. Tableaux Aiu/Aue × UV,ue
        $aiu = $accessor->getFloatOrNull('./surface_aiu', $entree);
        $aue = $accessor->getFloatOrNull('./surface_aue', $entree);
        $cfg = $accessor->getIntOrNull('./enum_cfg_isolation_lnc_id', $entree);

        if ($aue === null || $aue == 0.0) {
            // §3.1 : « Dans le cas où Aue = 0, alors b = 0. »
            return 0.0;
        }
        if ($aiu === null) {
            // Donnée incomplète : on retombe sur b = 1 (cas le plus défavorable)
            return 1.0;
        }
        $uvUe = $tvB['uv_ue'][$adjacence] ?? null;
        if ($uvUe === null || $cfg === null) {
            // Adjacence non couverte par la table d'UV,ue → forfait conservateur
            return 0.95;
        }

        $tableKey = match ($cfg) {
            2       => 'lc_ni_lnc_ni',
            3       => 'lc_ni_lnc_i',
            4       => 'lc_i_lnc_ni',
            5       => 'lc_i_lnc_i',
            1       => 'lc_i_lnc_i',  // « local chauffé non accessible » — choix conservateur
            default => 'lc_i_lnc_ni', // configurations véranda 6-11 ne sont pas couvertes ici
        };

        return $this->lookupTableau(
            $tvB['tableaux']['aiu_aue_axis'],
            $tvB['tableaux']['uv_ue_axis'],
            $tvB['tableaux'][$tableKey],
            $aiu / $aue,
            (float)$uvUe,
        );
    }

    /**
     * @param array<int, float|string> $aiuAueAxis
     * @param list<float> $uvUeAxis
     * @param list<list<float>> $matrix
     */
    private function lookupTableau(array $aiuAueAxis, array $uvUeAxis, array $matrix, float $ratio, float $uvUe): float
    {
        // Index ligne : première borne ≥ ratio
        $row = 0;
        foreach ($aiuAueAxis as $i => $borne) {
            if ($ratio <= (float)$borne) { $row = $i; break; }
            $row = $i;
        }

        // Index colonne : première borne ≥ uvUe
        $col = 0;
        foreach ($uvUeAxis as $j => $borne) {
            if ($uvUe <= $borne) { $col = $j; break; }
            $col = $j;
        }

        return (float)$matrix[$row][$col];
    }

    /**
     * @param array<string, mixed> $tableVeranda
     */
    private function resolveVeranda(DOMElement $entree, NodeAccessor $accessor, CalculationContext $context, array $tableVeranda): float
    {
        $zone = $context->zoneGroupe ?? 'H1';
        $orientation = $accessor->getIntOrNull('./enum_orientation_id', $entree);
        $cfg = $accessor->getIntOrNull('./enum_cfg_isolation_lnc_id', $entree);

        // Orientation : 1=sud, 2=nord, 3=est, 4=ouest, 5=horizontal
        $orientKey = match ($orientation) {
            1       => 'sud',
            2       => 'nord',
            3, 4    => 'est_ouest',
            default => 'sud',
        };

        // Isolation lc : 6,7,8 = lc isolé ; 9,10,11 = lc non isolé
        $isolKey = match ($cfg) {
            6, 7, 8    => 'isole',
            9, 10, 11  => 'non_isole',
            default    => 'isole',
        };

        return (float)($tableVeranda[$zone][$orientKey][$isolKey] ?? 0.5);
    }
}
