<?php

declare(strict_types=1);

namespace CalculDpe\Chauffage\Rendement\Combustion;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement de génération inserts et poêles (§13.1 p.78).
 *
 * Rg est lu directement dans une table indexée par enum_type_generateur_ch_id.
 * Les poêles bouilleurs (IDs 48-49) passent par la voie chaudière bois (ChaudiereDefautCalculator).
 *
 * @spec-section 13.1
 * @spec-pages   78
 * @spec-source  resources/specsplitted/13-rendement-combustion/01-inserts-poeles.md
 * @xml-input    generateur_chauffage.donnee_entree.enum_type_generateur_ch_id
 * @xml-output   generateur_chauffage.donnee_intermediaire.rendement_generation
 * @depends-on   aucun
 * @tables       (aucune — table directement dans le code depuis spec p.78)
 */
final class InsertsPoelesCalculator implements CalculatorInterface
{
    /**
     * §13.1 p.78 — Rg par enum_type_generateur_ch_id
     * Cuisinière/foyer fermé/poêle bûche/insert : IDs 20-43
     * Poêle granulés : IDs 44-46
     * Poêle fioul/GPL/charbon : ID 47
     */
    private const RG_BY_TYPE = [
        // Cuisinière/Foyer fermé/Poêle bûche/Insert avant 1990 → 0.50
        20 => 0.50, 21 => 0.50, 22 => 0.50, 23 => 0.50,
        // 1990-2004 → 0.60
        24 => 0.60, 25 => 0.60, 26 => 0.60, 27 => 0.60,
        // à partir 2005 sans label flamme verte → 0.65
        28 => 0.65, 29 => 0.65, 30 => 0.65, 31 => 0.65,
        // 2005-2006 avec label flamme verte → 0.65
        32 => 0.65, 33 => 0.65, 34 => 0.65, 35 => 0.65,
        // 2007-2017 avec label flamme verte → 0.70
        36 => 0.70, 37 => 0.70, 38 => 0.70, 39 => 0.70,
        // à partir 2018 avec label flamme verte → 0.75
        40 => 0.75, 41 => 0.75, 42 => 0.75, 43 => 0.75,
        // Poêle à granulés avant 2012 ou sans label → 0.80
        44 => 0.80,
        // Poêle à granulés flamme verte 2012-2019 → 0.85
        45 => 0.85,
        // Poêle à granulés flamme verte à partir 2020 → 0.87
        46 => 0.87,
        // Poêle fioul, GPL ou charbon → 0.72
        47 => 0.72,
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
        return $node->nodeName === 'generateur_chauffage';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $genId    = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $node);

        if ($genId === null || !isset(self::RG_BY_TYPE[$genId])) {
            return;
        }

        $rg = self::RG_BY_TYPE[$genId];
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_generation', $rg);
    }
}
