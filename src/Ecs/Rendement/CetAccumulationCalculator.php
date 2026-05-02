<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs\Rendement;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * COP des chauffe-eau thermodynamiques à accumulation (§14.2 p.95).
 *
 * Le COP dépend du type d'installation (air ambiant/extérieur, air extrait, PAC double service)
 * et de la zone climatique (H1/H2 vs H3). Pour ce calculateur, COP = rendement_generation.
 *
 * Formula spec : Iecs = 1 / (Rd × COP)
 *
 * @spec-section 14.2
 * @spec-pages   95
 * @spec-source  resources/specsplitted/14-rendement-ecs-generateurs/02-cet-accumulation.md
 * @xml-input    generateur_ecs.donnee_entree.enum_type_generateur_ecs_id
 * @xml-output   generateur_ecs.donnee_intermediaire.{cop, rendement_generation}
 * @depends-on   aucun
 * @tables       (aucune — valeurs directement de la spec p.95)
 */
final class CetAccumulationCalculator implements CalculatorInterface
{
    /**
     * §14.2 p.95 — COP[zone_groupe][type_id]
     * type: 1-6 = air ambiant/extérieur, 7-9 = air extrait, 10-12 = PAC double service
     * zone H1/H2 et zone H3 ont des valeurs différentes.
     */
    private const COP_H1H2 = [
        // CET sur air extérieur ou ambiant (IDs 1-6)
        1 => 2.0,  2 => 2.2,  3 => 2.5,
        4 => 2.0,  5 => 2.2,  6 => 2.5,
        // CET sur air extrait (IDs 7-9)
        7 => 2.3,  8 => 2.5,  9 => 2.8,
        // PAC double service (IDs 10-12)
        10 => 2.0, 11 => 2.1, 12 => 2.3,
    ];

    private const COP_H3 = [
        // CET sur air extérieur ou ambiant (IDs 1-6)
        1 => 2.3,  2 => 2.5,  3 => 2.8,
        4 => 2.3,  5 => 2.5,  6 => 2.8,
        // CET sur air extrait (IDs 7-9)
        7 => 2.3,  8 => 2.5,  9 => 2.9,
        // PAC double service (IDs 10-12)
        10 => 2.3, 11 => 2.4, 12 => 2.6,
    ];

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
        return $node->nodeName === 'generateur_ecs';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $typeId   = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $node);

        if ($typeId === null || $typeId < 1 || $typeId > 12) {
            return;
        }

        $zone  = CalculationContext::zoneGroupeFromId($context->zoneClimatique);
        $table = ($zone === 'H3') ? self::COP_H3 : self::COP_H1H2;
        $cop   = $table[$typeId];

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'cop',                 $cop);
        $accessor->setChildValue($di, 'rendement_generation', $cop);
    }
}
