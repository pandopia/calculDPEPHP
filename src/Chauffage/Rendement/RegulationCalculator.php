<?php

declare(strict_types=1);

namespace CalculDpe\Chauffage\Rendement;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement de régulation Rr de l'installation de chauffage (§12.3 p.76).
 *
 * Lookup dans tv_rendement_regulation via enum_type_emission_distribution_id.
 * Défaut (§12.3) : Rr = 0.9 pour tout cas non listé.
 *
 * @spec-section 12.3
 * @spec-pages   76
 * @spec-source  resources/specsplitted/12-rendements-installations/03-regulation.md
 * @xml-input    emetteur_chauffage.donnee_entree.enum_type_emission_distribution_id
 * @xml-output   emetteur_chauffage.donnee_intermediaire.rendement_regulation
 * @depends-on   (aucun)
 * @tables       chauffage/tv_rendement_regulation
 */
final class RegulationCalculator implements CalculatorInterface
{
    private const DEFAULT_RR = 0.90;

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
        return $node->nodeName === 'emetteur_chauffage';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $emId = $accessor->getIntOrNull('./donnee_entree/enum_type_emission_distribution_id', $node);

        $table = $context->tables->load('chauffage/tv_rendement_regulation');
        $rr = ($emId !== null && isset($table[$emId])) ? (float)$table[$emId] : self::DEFAULT_RR;

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_regulation', $rr);
    }
}
