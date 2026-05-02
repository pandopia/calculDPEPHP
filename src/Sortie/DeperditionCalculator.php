<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use DOMXPath;

/**
 * Bloc <sortie><deperdition> — totaux finaux.
 *
 * EnveloppeAggregator écrit les 6 déperditions par type de paroi.
 * VentilationAggregator écrit hvent et hperm.
 * Ce calculator ajoute :
 *   deperdition_renouvellement_air = hvent + hperm
 *   deperdition_enveloppe = Σ parois + PT + deperdition_renouvellement_air
 *
 * @spec-section 3
 * @spec-pages   7-37
 * @spec-source  resources/specsplitted/03-enveloppe-deperditions/00-overview-GV.md
 * @xml-input    sortie.deperdition.{hvent, hperm}
 *               context: enveloppe.dp_parois, enveloppe.dp_pont_thermique
 *               context: ventilation.hvent, ventilation.hperm
 * @xml-output   sortie.deperdition.{deperdition_renouvellement_air, deperdition_enveloppe}
 * @depends-on   \CalculDpePHP\Enveloppe\EnveloppeAggregator, \CalculDpePHP\Ventilation\VentilationAggregator
 * @tables       (aucune)
 */
final class DeperditionCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Enveloppe\EnveloppeAggregator',
            '\CalculDpePHP\Ventilation\VentilationAggregator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $xpath    = new DOMXPath($context->document);

        $depNodes = $xpath->query('./sortie/deperdition', $node);
        if ($depNodes === false || $depNodes->length === 0) return;
        /** @var DOMElement $dep */
        $dep = $depNodes->item(0);

        $hvent = (float)($context->get('ventilation.hvent') ?? 0.0);
        $hperm = (float)($context->get('ventilation.hperm') ?? 0.0);
        $dr    = $hvent + $hperm;

        $dpParois = (float)($context->get('enveloppe.dp_parois') ?? 0.0);
        $dpPT     = (float)($context->get('enveloppe.dp_pont_thermique') ?? 0.0);
        $dpEnv    = $dpParois + $dpPT + $dr;

        $accessor->setChildValue($dep, 'deperdition_renouvellement_air', $dr);
        $accessor->setChildValue($dep, 'deperdition_enveloppe', $dpEnv);
    }
}
