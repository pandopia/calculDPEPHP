<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\BaieVitree;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Coefficient Ujn (fenêtre + fermeture) — §3.3.3.
 *
 * Algorithme :
 *   1. Si `enum_type_fermeture_id == 1` (absence) → on n'écrit pas Ujn.
 *   2. Si une saisie directe Ujn est présente (`ujn_saisi`) → lecture directe.
 *   3. Sinon : déterminer ΔR depuis `enum_type_fermeture_id` puis lookup tv_ujn par
 *      (Uw, ΔR) avec interpolation linéaire entre les deux Uw tabulés les plus proches.
 *
 * @spec-section 3.3.3
 * @spec-pages 30-31
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/03-parois-vitrees-portes/03-ujn/00-calcul.md
 * @xml-input  baie_vitree.donnee_entree.{enum_type_fermeture_id, ujn_saisi}
 * @xml-output baie_vitree.donnee_intermediaire.ujn
 * @depends-on \CalculDpe\Enveloppe\BaieVitree\UwCalculator
 * @tables tv_ujn
 */
final class UjnCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [UwCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'baie_vitree';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('baie_vitree sans <donnee_entree>.');
        }
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);

        $fermeture = $accessor->getIntOrNull('./enum_type_fermeture_id', $entree);
        if ($fermeture === 1 || $fermeture === null) {
            // Pas de Ujn quand pas de fermeture
            return;
        }

        $direct = $accessor->getFloatOrNull('./ujn_saisi', $entree);
        if ($direct !== null) {
            $accessor->setChildValue($intermediaire, 'ujn', $direct);
            return;
        }

        $uw = $accessor->getFloatOrNull('./uw', $intermediaire);
        if ($uw === null) {
            return;
        }
        $tvUjn = $context->tables->load('enveloppe/tv_ujn');
        $drKey = $tvUjn['fermeture_to_dr'][$fermeture] ?? null;
        if ($drKey === null) {
            return;
        }

        $ujn = $this->interpolateLinear($tvUjn['ujn_par_uw'], $drKey, $uw);
        $accessor->setChildValue($intermediaire, 'ujn', $ujn);
    }

    /**
     * Interpolation linéaire entre les Uw tabulés.
     * Les clés sont des strings (ex '0.8', '2.6') pour préserver les décimales en PHP.
     *
     * @param array<string, array<string, float>> $rows
     */
    private function interpolateLinear(array $rows, string $drKey, float $uw): float
    {
        $uws = [];
        foreach (array_keys($rows) as $k) $uws[] = (float)$k;
        sort($uws);
        if ($uw <= $uws[0])  return (float)$rows[(string)$uws[0]][$drKey];
        $last = $uws[count($uws) - 1];
        if ($uw >= $last)    return (float)$rows[(string)$last][$drKey];
        for ($i = 0; $i < count($uws) - 1; $i++) {
            $a = $uws[$i];
            $b = $uws[$i + 1];
            if ($uw >= $a && $uw <= $b) {
                $vA = (float)$rows[(string)$a][$drKey];
                $vB = (float)$rows[(string)$b][$drKey];
                $t = ($b > $a) ? ($uw - $a) / ($b - $a) : 0.0;
                return $vA + $t * ($vB - $vA);
            }
        }
        return (float)$rows[(string)$uws[0]][$drKey];
    }
}
