<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe\PlancherHaut;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Calcul de Uph0 (plancher haut non isolé) — §3.2.3.2.
 *
 * Selon `enum_methode_saisie_u0_id` :
 *   1 type inconnu              → Uph0 = 2.5
 *   2 table forfaitaire         → lookup tv_uph0 par enum_type_plancher_haut_id
 *   3, 4 saisie directe         → valeur saisie
 *
 * @spec-section 3.2.3.2
 * @spec-pages 22
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/03-uph/02-calcul-uph0.md
 * @xml-input  plancher_haut.donnee_entree.{enum_methode_saisie_u0_id, enum_type_plancher_haut_id}
 * @xml-output plancher_haut.donnee_intermediaire.uph0
 * @tables tv_uph0
 */
final class Uph0Calculator implements CalculatorInterface
{
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
        return $node->nodeName === 'plancher_haut';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree   = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('plancher_haut sans <donnee_entree>.');
        }

        $methode = $accessor->getIntOrNull('./enum_methode_saisie_u0_id', $entree);
        $type    = $accessor->getIntOrNull('./enum_type_plancher_haut_id', $entree);

        $uph0 = match ($methode) {
            1       => 2.5,
            2       => $this->lookupTable($type, $context),
            3, 4    => $accessor->getFloatOrNull('./uph0_saisi', $entree) ?? 2.5,
            default => 2.5,
        };

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'uph0', $uph0);
    }

    private function lookupTable(?int $type, CalculationContext $context): float
    {
        if ($type === null) return 2.5;
        $table = $context->tables->load('enveloppe/tv_uph0');
        if (!isset($table[$type])) {
            throw new RuntimeException(sprintf(
                'Uph0 introuvable pour enum_type_plancher_haut_id=%d.', $type
            ));
        }
        return (float)$table[$type];
    }
}
