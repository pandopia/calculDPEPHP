<?php

declare(strict_types=1);

namespace CalculDpe\Ecs\Rendement;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement de distribution ECS Rd (§11.5 p.73-74).
 *
 * Rd est lu depuis tv_rendement_distribution_ecs selon tv_rendement_distribution_ecs_id.
 *
 * Individuel (§11.5.1) : 0,93 / 0,87 / 0,83
 * Collectif  (§11.5.2) : 0,28 / 0,26 / 0,55 / 0,52 / 0,83
 *
 * @spec-section 11.5
 * @spec-pages   73-74
 * @spec-source  resources/specsplitted/11-conso-ecs/05-rendement-distribution.md
 * @xml-input    installation_ecs.donnee_entree.tv_rendement_distribution_ecs_id
 * @xml-output   installation_ecs.donnee_intermediaire.rendement_distribution
 * @depends-on   aucun
 * @tables       ecs/tv_rendement_distribution_ecs
 */
final class DistributionCalculator implements CalculatorInterface
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
        return $node->nodeName === 'installation_ecs';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $tableId = $accessor->getIntOrNull('./donnee_entree/tv_rendement_distribution_ecs_id', $node);

        $rd = 1.0; // valeur neutre si id absent
        if ($tableId !== null) {
            $tvRd = $context->tables->load('ecs/tv_rendement_distribution_ecs');
            $rd   = (float)($tvRd[$tableId] ?? 1.0);
        }

        $di = $this->ensureDi($context->document, $node);
        $accessor->setChildValue($di, 'rendement_distribution', $rd);

        // Stocker par référence de l'installation pour ConsoEcsCalculator
        $ref = $accessor->getStringOrNull('./donnee_entree/reference', $node) ?? '';
        $context->set('ecs.rendement_distribution.' . $ref, $rd);
        $context->set('ecs.rendement_distribution', $rd); // dernier = convention simple
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
