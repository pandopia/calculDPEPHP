<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use DOMElement;
use LogicException;

/**
 * Plusieurs émissions pour un même générateur.
 *
 * @spec-section 9.1.3
 * @spec-pages   60
 * @spec-source  resources/specsplitted/09-conso-chauffage/01-installation-seule/03-multi-emissions.md
 * @xml-input    installation_chauffage.*
 * @xml-output   conso_ch
 * @depends-on   \CalculDpePHP\Chauffage\Strategy\InstallationClassique
 * @tables       (aucune)
 *
 * Stub : implémentation à réaliser dans la tâche TASK-E11 (voir TASKS.md).
 */
final class MultiEmissions implements CalculatorInterface
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
        return $node->nodeName === 'installation_chauffage';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        throw new LogicException(self::class . ' not implemented yet — see TASK-E11 in TASKS.md');
    }
}
