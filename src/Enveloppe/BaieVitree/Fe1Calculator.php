<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\BaieVitree;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Facteur d'ensoleillement Fe1 (masques proches : balcon, loggia, paroi latérale) — §6.2.2.1.
 *
 * Lookup `tv_coef_masque_proche_id`.
 *
 * @spec-section 6.2.2.1
 * @spec-pages 48-49
 * @spec-source resources/specsplitted/06-apports-gratuits/02-surface-sud-equivalente/04-masques-proches.md
 * @xml-input  baie_vitree.donnee_entree.tv_coef_masque_proche_id
 * @xml-output baie_vitree.donnee_intermediaire.fe1
 * @tables tv_coef_masque_proche
 */
final class Fe1Calculator implements CalculatorInterface
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
        return $node->nodeName === 'baie_vitree';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree   = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('baie_vitree sans <donnee_entree>.');
        }

        $tvId = $accessor->getIntOrNull('./tv_coef_masque_proche_id', $entree);
        $fe1 = 1.0;
        if ($tvId !== null) {
            $table = $context->tables->load('enveloppe/tv_coef_masque_proche');
            if (isset($table[$tvId])) {
                $fe1 = (float)$table[$tvId];
            }
        }

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'fe1', $fe1);
    }
}
