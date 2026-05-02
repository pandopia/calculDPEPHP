<?php

declare(strict_types=1);

namespace CalculDpePHP\Ventilation;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Valeur conventionnelle de la perméabilité sous 4 Pa — §4 p.39.
 *
 * Algorithme :
 *   1. Si `enum_methode_saisie_q4pa_conv_id` ∈ {2, 3} (mesure < 2 ans ou RSET/RSEE) :
 *      lecture directe de `q4pa_conv_saisi`.
 *   2. Sinon : lookup `tv_q4pa_conv` par `tv_q4pa_conv_id`.
 *
 * @spec-section 4
 * @spec-pages 39
 * @spec-source resources/specsplitted/04-renouvellement-air/00-calcul.md
 * @xml-input  ventilation.donnee_entree.{enum_methode_saisie_q4pa_conv_id, q4pa_conv_saisi, tv_q4pa_conv_id}
 * @xml-output ventilation.donnee_intermediaire.q4pa_conv
 * @depends-on aucun
 * @tables tv_q4pa_conv
 */
final class Q4PaConvCalculator implements CalculatorInterface
{
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
        return $node->nodeName === 'ventilation';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('ventilation sans <donnee_entree>.');
        }

        $methode = $accessor->getIntOrNull('./enum_methode_saisie_q4pa_conv_id', $entree);

        if ($methode === 2 || $methode === 3) {
            $saisi = $accessor->getFloatOrNull('./q4pa_conv_saisi', $entree);
            if ($saisi !== null) {
                $intermediaire = $accessor->ensureDonneeIntermediaire($node);
                $accessor->setChildValue($intermediaire, 'q4pa_conv', $saisi);
                return;
            }
        }

        $tvId = $accessor->getIntOrNull('./tv_q4pa_conv_id', $entree);
        if ($tvId === null) {
            return;
        }

        $table = $context->tables->load('ventilation/tv_q4pa_conv');
        $value = $table[$tvId] ?? null;
        if ($value === null) {
            return;
        }

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'q4pa_conv', (float)$value);
    }
}
