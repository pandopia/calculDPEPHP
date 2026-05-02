<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\BaieVitree;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Coefficient U_menuiserie de la baie : Ujn si une fermeture est présente, sinon Uw.
 *
 * Cette balise sert ensuite à calculer la déperdition par les baies vitrées
 * (DPbaie = b × U_menuiserie × surface).
 *
 * @spec-section 3.3
 * @spec-pages 22
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/03-parois-vitrees-portes/00-overview.md
 * @xml-input  baie_vitree.donnee_intermediaire.{uw, ujn}
 * @xml-output baie_vitree.donnee_intermediaire.u_menuiserie
 * @depends-on \CalculDpe\Enveloppe\BaieVitree\UjnCalculator
 */
final class UMenuiserieCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [UjnCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'baie_vitree';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);

        $ujn = $accessor->getFloatOrNull('./ujn', $intermediaire);
        $uw  = $accessor->getFloatOrNull('./uw', $intermediaire);
        $u   = $ujn ?? $uw;

        if ($u === null) {
            throw new RuntimeException('UMenuiserieCalculator : ni ujn ni uw disponibles.');
        }

        $accessor->setChildValue($intermediaire, 'u_menuiserie', $u);
    }
}
