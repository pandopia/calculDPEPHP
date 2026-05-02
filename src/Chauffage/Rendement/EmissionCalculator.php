<?php

declare(strict_types=1);

namespace CalculDpe\Chauffage\Rendement;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement d'émission Re de l'installation de chauffage (§12.1 p.75-76).
 *
 * Lookup de Re dans tv_rendement_emission via enum_type_emission_distribution_id.
 * Défaut : Re = 0.95 (catégorie « autres équipements »).
 *
 * @spec-section 12.1
 * @spec-pages   75-76
 * @spec-source  resources/specsplitted/12-rendements-installations/01-emission.md
 * @xml-input    emetteur_chauffage.donnee_entree.enum_type_emission_distribution_id
 * @xml-output   emetteur_chauffage.donnee_intermediaire.rendement_emission
 * @depends-on   (aucun)
 * @tables       chauffage/tv_rendement_emission
 */
final class EmissionCalculator implements CalculatorInterface
{
    private const DEFAULT_RE = 0.95;

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

        $table = $context->tables->load('chauffage/tv_rendement_emission');
        $re = ($emId !== null && isset($table[$emId])) ? (float)$table[$emId] : self::DEFAULT_RE;

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_emission', $re);
    }
}
