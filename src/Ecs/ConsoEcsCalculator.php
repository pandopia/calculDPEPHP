<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Consommation ECS Cecs et Cecs_depensier (§11.2 p.72-73).
 *
 *   Cecs = Becs × Iecs    avec Iecs = 1 / (Rs × Rd × Rg)
 *
 * Pour le scénario dépensier, Rs_dep et Rg_dep sont recalculés à partir de
 * Rs_conv et Rg_conv en utilisant la relation linéaire sur 1/Becs.
 *
 * Relation de mise à l'échelle (dérivée des formules §11.6 et §14.1) :
 *   Facteur δ = Becs_conv / Becs_dep  (= 56/79)
 *
 *   Pour Rs : X = N/Rs − 1 (N = 1,08 ou 1,0 selon catégorie)
 *     Rs_dep = N / (1 + X_conv × δ)
 *
 *   Pour Rg (combustion) : Y = 1/Rg − 1/Rpn
 *     Rg_dep = 1 / (1/Rpn + Y_conv × δ)
 *
 * Appliqué à chaque installation_ecs ; écrit dans chaque generateur_ecs
 * de l'installation.
 *
 * @spec-section 11.2
 * @spec-pages   72-73
 * @spec-source  resources/specsplitted/11-conso-ecs/02-conso-ecs.md
 * @xml-input    installation_ecs.donnee_intermediaire.{besoin_ecs, besoin_ecs_depensier, rendement_distribution}
 * @xml-input    generateur_ecs.donnee_intermediaire.{rendement_stockage, rendement_generation, rpn}
 * @xml-output   generateur_ecs.donnee_intermediaire.{conso_ecs, conso_ecs_depensier}
 * @depends-on   \CalculDpePHP\Ecs\BesoinEcsCalculator
 * @depends-on   \CalculDpePHP\Ecs\Rendement\DistributionCalculator
 * @depends-on   \CalculDpePHP\Ecs\Rendement\StockageCalculator
 * @depends-on   \CalculDpePHP\Ecs\Rendement\CombustionCalculator
 * @tables       (aucune)
 */
final class ConsoEcsCalculator implements CalculatorInterface
{
    /** Identifiant enum_type_generateur_ecs_id pour ballon électrique cat C/3* */
    private const GEN_TYPE_ELEC_CAT_C = 71;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Ecs\BesoinEcsCalculator',
            '\CalculDpePHP\Ecs\Rendement\DistributionCalculator',
            '\CalculDpePHP\Ecs\Rendement\StockageCalculator',
            '\CalculDpePHP\Ecs\Rendement\CombustionCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'installation_ecs';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // Données de l'installation
        $becsConv = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ecs',           $node) ?? 0.0;
        $becsDep  = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ecs_depensier', $node) ?? 0.0;
        $rd       = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_distribution', $node) ?? 1.0;

        if ($rd <= 0.0) {
            return;
        }

        $totalConso    = 0.0;
        $totalConsoDep = 0.0;

        foreach ($node->getElementsByTagName('generateur_ecs') as $gen) {
            if (!$gen instanceof DOMElement) {
                continue;
            }

            $rsConv  = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_stockage',   $gen) ?? 1.0;
            $rgConv  = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_generation', $gen) ?? 1.0;
            $rpn     = $accessor->getFloatOrNull('./donnee_intermediaire/rpn',                  $gen);
            $genType = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id',   $gen);

            // Recalcul Rs_dep et Rg_dep selon §11.6 et §14.1
            $rsDep = $this->computeRsDep($rsConv, $becsConv, $becsDep, $genType);
            $rgDep = $this->computeRgDep($rgConv, $rpn, $becsConv, $becsDep);

            $conso    = $becsConv > 0.0 ? $becsConv / ($rsConv * $rd * $rgConv) : 0.0;
            $consoDep = $becsDep  > 0.0 ? $becsDep  / ($rsDep  * $rd * $rgDep)  : 0.0;

            $di = $this->ensureDi($context->document, $gen);
            $accessor->setChildValue($di, 'conso_ecs',           $conso);
            $accessor->setChildValue($di, 'conso_ecs_depensier', $consoDep);

            // Ratio besoin — utilisé par l'agrégateur logement
            if (!$accessor->getFloatOrNull('./donnee_intermediaire/ratio_besoin_ecs', $gen)) {
                $accessor->setChildValue($di, 'ratio_besoin_ecs', 1.0);
            }

            $totalConso    += $conso;
            $totalConsoDep += $consoDep;
        }

        // Aggregate to installation level (mirrors verif structure)
        $instDi = $this->ensureDi($context->document, $node);
        $accessor->setChildValue($instDi, 'conso_ecs',           $totalConso);
        $accessor->setChildValue($instDi, 'conso_ecs_depensier', $totalConsoDep);
    }

    /**
     * Rs_dep = N / (1 + X_conv × δ)
     * où X_conv = N/Rs_conv − 1, δ = Becs_conv / Becs_dep.
     */
    private function computeRsDep(float $rsConv, float $becsConv, float $becsDep, ?int $genType): float
    {
        if ($rsConv >= 1.0 || $becsDep <= 0.0) {
            return 1.0;
        }

        $n      = ($genType === self::GEN_TYPE_ELEC_CAT_C) ? 1.08 : 1.0;
        $xConv  = $n / $rsConv - 1.0;
        $delta  = ($becsConv > 0.0) ? $becsConv / $becsDep : 1.0;
        $denom  = 1.0 + $xConv * $delta;

        return $denom > 0.0 ? $n / $denom : 1.0;
    }

    /**
     * Rg_dep = 1 / (1/Rpn + Y_conv × δ)
     * où Y_conv = 1/Rg_conv − 1/Rpn, δ = Becs_conv / Becs_dep.
     * Pour Rg_conv = 1 (électrique) : Rg_dep = 1.
     */
    private function computeRgDep(float $rgConv, ?float $rpn, float $becsConv, float $becsDep): float
    {
        if ($rgConv >= 1.0 || $rpn === null || $rpn <= 0.0 || $becsDep <= 0.0) {
            return $rgConv;
        }

        $yConv  = 1.0 / $rgConv - 1.0 / $rpn;
        $delta  = ($becsConv > 0.0) ? $becsConv / $becsDep : 1.0;
        $denom  = 1.0 / $rpn + $yConv * $delta;

        return $denom > 0.0 ? 1.0 / $denom : $rgConv;
    }

    private function ensureDi(\DOMDocument $doc, DOMElement $parent): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === 'donnee_intermediaire') {
                return $c;
            }
        }
        $el = $doc->createElement('donnee_intermediaire');
        $parent->appendChild($el);
        return $el;
    }
}
