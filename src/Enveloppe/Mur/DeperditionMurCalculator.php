<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe\Mur;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use DOMElement;
use LogicException;

/**
 * Déperdition d'un mur : DPmur = b × Umur × Surface.
 *
 * @spec-section 3
 * @spec-pages   7
 * @spec-source  resources/specsplitted/03-enveloppe-deperditions/00-overview-GV.md
 * @xml-input    mur.donnee_entree.surface_paroi_opaque + donnee_intermediaire.{b, umur}
 * @xml-output   (agrégat)
 * @depends-on   \CalculDpePHP\Enveloppe\Mur\BCalculator, \CalculDpePHP\Enveloppe\Mur\UmurCalculator
 * @tables       (aucune)
 *
 * Stub : implémentation à réaliser dans la tâche TASK-B09 (voir TASKS.md).
 */
final class DeperditionMurCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return ['\CalculDpePHP\Enveloppe\Mur\BCalculator', '\CalculDpePHP\Enveloppe\Mur\UmurCalculator'];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'mur';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        throw new LogicException(self::class . ' not implemented yet — see TASK-B09 in TASKS.md');
    }
}
