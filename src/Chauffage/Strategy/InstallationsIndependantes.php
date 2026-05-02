<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use DOMElement;
use LogicException;

/**
 * Plusieurs installations différentes et indépendantes.
 *
 * @spec-section 9.10
 * @spec-pages   66
 * @spec-source  resources/specsplitted/09-conso-chauffage/10-installations-independantes.md
 * @xml-input    installation_chauffage_collection.*
 * @xml-output   conso_ch
 * @depends-on   \CalculDpePHP\Chauffage\Strategy\InstallationClassique
 * @tables       (aucune)
 *
 * Stub : implémentation à réaliser dans la tâche TASK-E21 (voir TASKS.md).
 */
final class InstallationsIndependantes implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return ['\CalculDpePHP\Chauffage\Strategy\InstallationClassique'];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        throw new LogicException(self::class . ' not implemented yet — see TASK-E21 in TASKS.md');
    }
}
