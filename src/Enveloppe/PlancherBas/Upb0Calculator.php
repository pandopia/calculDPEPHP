<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe\PlancherBas;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Calcul de Upb0 (plancher bas non isolé) — partie haute de l'arbre §3.2.2.1.
 *
 * Étapes :
 *   1. Détermination de Upb0_brut selon `enum_methode_saisie_u0_id` :
 *      - 1 (type inconnu)            → Upb0_brut = 2.0
 *      - 2 (table forfaitaire)       → lookup tv_upb0 par enum_type_plancher_bas_id
 *      - 3, 4 (saisie directe)       → valeur saisie
 *      - 5 (non saisi car U direct)  → cf. UpbCalculator
 *
 * @spec-section 3.2.2.2
 * @spec-pages 20
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/02-upb/02-calcul-upb0.md
 * @xml-input  plancher_bas.donnee_entree.{enum_methode_saisie_u0_id, enum_type_plancher_bas_id}
 * @xml-output plancher_bas.donnee_intermediaire.upb0
 * @tables tv_upb0
 */
final class Upb0Calculator implements CalculatorInterface
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
        return $node->nodeName === 'plancher_bas';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree   = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('plancher_bas sans <donnee_entree>.');
        }

        $methode = $accessor->getIntOrNull('./enum_methode_saisie_u0_id', $entree);
        $type    = $accessor->getIntOrNull('./enum_type_plancher_bas_id', $entree);

        $upb0 = $this->resolveUpb0($methode, $type, $entree, $accessor, $context);

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'upb0', $upb0);
    }

    private function resolveUpb0(
        ?int $methode,
        ?int $type,
        DOMElement $entree,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        return match ($methode) {
            1       => 2.0,
            2       => $this->lookupTable($type, $context),
            3, 4    => $accessor->getFloatOrNull('./upb0_saisi', $entree) ?? 2.0,
            default => 2.0,
        };
    }

    private function lookupTable(?int $type, CalculationContext $context): float
    {
        if ($type === null) return 2.0;
        $table = $context->tables->load('enveloppe/tv_upb0');
        if (!isset($table[$type])) {
            throw new RuntimeException(sprintf(
                'Upb0 introuvable pour enum_type_plancher_bas_id=%d.', $type
            ));
        }
        return (float)$table[$type];
    }
}
