<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe\Porte;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Coefficient U_porte — §3.3.4.
 *
 * Selon `enum_methode_saisie_uporte_id` :
 *   1 forfaitaire (lookup tv_uporte par enum_type_porte_id)
 *   2 saisie justifiée (uporte_saisi)
 *   3 RT2012/RE2020 (uporte_saisi)
 *
 * @spec-section 3.3.4
 * @spec-pages 32
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/03-parois-vitrees-portes/04-uporte/00-calcul.md
 * @xml-input  porte.donnee_entree.{enum_methode_saisie_uporte_id, enum_type_porte_id, uporte_saisi}
 * @xml-output porte.donnee_intermediaire.uporte
 * @tables tv_uporte
 */
final class UporteCalculator implements CalculatorInterface
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
        return $node->nodeName === 'porte';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree   = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('porte sans <donnee_entree>.');
        }

        $methode = $accessor->getIntOrNull('./enum_methode_saisie_uporte_id', $entree);
        $type    = $accessor->getIntOrNull('./enum_type_porte_id', $entree);

        $uporte = match ($methode) {
            2, 3    => $accessor->getFloatOrNull('./uporte_saisi', $entree) ?? $this->lookupTable($type, $context),
            default => $this->lookupTable($type, $context), // 1 (forfaitaire) ou null
        };

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'uporte', $uporte);
    }

    private function lookupTable(?int $type, CalculationContext $context): float
    {
        if ($type === null) return 3.5; // forfait conservateur
        $table = $context->tables->load('enveloppe/tv_uporte');
        if (!isset($table[$type])) {
            throw new RuntimeException(sprintf(
                'Uporte introuvable pour enum_type_porte_id=%d. Compléter resources/tables/enveloppe/tv_uporte.php.',
                $type
            ));
        }
        return (float)$table[$type];
    }
}
