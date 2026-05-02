<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement de distribution Rd de l'installation de chauffage (§12.2 p.76).
 *
 * Le type de réseau est encodé dans enum_type_emission_distribution_id.
 * L'état d'isolation est fourni par reseau_distribution_isole (0=non, 1=oui).
 * Pour les fluides frigorigènes et les émetteurs sans réseau : Rd=1.
 *
 * @spec-section 12.2
 * @spec-pages   76
 * @spec-source  resources/specsplitted/12-rendements-installations/02-distribution.md
 * @xml-input    emetteur_chauffage.donnee_entree.{enum_type_emission_distribution_id, reseau_distribution_isole}
 * @xml-output   emetteur_chauffage.donnee_intermediaire.rendement_distribution
 * @depends-on   (aucun)
 * @tables       chauffage/tv_rendement_distribution_ch
 */
final class DistributionCalculator implements CalculatorInterface
{
    private const DEFAULT_RD = 1.00;

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
        $emId  = $accessor->getIntOrNull('./donnee_entree/enum_type_emission_distribution_id', $node);
        $isole = $accessor->getIntOrNull('./donnee_entree/reseau_distribution_isole', $node) === 1;

        $table = $context->tables->load('chauffage/tv_rendement_distribution_ch');
        if ($emId === null || !isset($table[$emId])) {
            $rd = self::DEFAULT_RD;
        } else {
            $row = $table[$emId];
            $rd  = $isole ? (float)$row['isole'] : (float)$row['non_isole'];
        }

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_distribution', $rd);
    }
}
