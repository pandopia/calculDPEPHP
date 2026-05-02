<?php

declare(strict_types=1);

namespace CalculDpe\Ventilation;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Coefficient Hvent de déperdition par renouvellement d'air — §4 p.38.
 *
 * Formule :
 *   Hvent = 0,34 × Qvarepconv × Sh
 *
 * Qvarepconv vient de `tv_debits_ventilation` via `enum_type_ventilation_id`.
 * Sh = `surface_ventile` de la donnée d'entrée.
 *
 * @spec-section 4
 * @spec-pages 38-40
 * @spec-source resources/specsplitted/04-renouvellement-air/00-calcul.md
 * @xml-input  ventilation.donnee_entree.{enum_type_ventilation_id, surface_ventile}
 * @xml-output ventilation.donnee_intermediaire.hvent
 * @depends-on aucun
 * @tables tv_debits_ventilation
 */
final class HventCalculator implements CalculatorInterface
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

        $typeId = $accessor->getIntOrNull('./enum_type_ventilation_id', $entree);
        $sh = $accessor->getFloatOrNull('./surface_ventile', $entree);
        if ($typeId === null || $sh === null) {
            return;
        }

        $debits = $context->tables->load('ventilation/tv_debits_ventilation');
        $row = $debits[$typeId] ?? null;
        if ($row === null) {
            return;
        }

        $hvent = 0.34 * (float)$row['qvarep'] * $sh;

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'hvent', $hvent);
    }
}
