<?php

declare(strict_types=1);

namespace CalculDpePHP\Ventilation;

use CalculDpePHP\Common\IntermediateEnergyUnit;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Consommation annuelle d'auxiliaires de ventilation — §5 p.41.
 *
 * Formule :
 *   Caux = 8760 × Pventmoy / 1000   [kWhef/an]
 *
 * @spec-section 5
 * @spec-pages 41
 * @spec-source resources/specsplitted/05-auxiliaires-ventilation/00-calcul.md
 * @xml-input  ventilation.donnee_intermediaire.pvent_moy
 * @xml-output ventilation.donnee_intermediaire.conso_auxiliaire_ventilation
 * @depends-on \CalculDpePHP\Ventilation\PventMoyCalculator
 * @tables (aucune)
 */
final class ConsoAuxiliaireVentilationCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [PventMoyCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'ventilation';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('ventilation sans <donnee_entree>.');
        }
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);

        $pventMoy = $accessor->getFloatOrNull('./pvent_moy', $intermediaire) ?? 0.0;
        $caux = 8760.0 * $pventMoy / 1000.0;
        if (IntermediateEnergyUnit::isNativeAdeme($context->document)) {
            // Le format natif conserve des sentinelles dans la ventilation,
            // mais la sortie globale doit recevoir la consommation calculée.
            $context->set(
                'ventilation.caux_reel',
                (float)$context->get('ventilation.caux_reel', 0.0) + $caux,
            );
            $accessor->setChildValue($intermediaire, 'pvent_moy', 0.0);
            $accessor->setChildValue($intermediaire, 'conso_auxiliaire_ventilation', 1.0);
            return;
        }
        // LICIEL arrondit la copie donnee_intermediaire à l'entier (la valeur
        // pleine précision va dans sortie/ef_conso via VentilationAggregator,
        // qui recalcule depuis pvent_moy).
        $accessor->setChildValue($intermediaire, 'conso_auxiliaire_ventilation', round($caux));
    }
}
