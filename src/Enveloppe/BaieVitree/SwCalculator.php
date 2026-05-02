<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\BaieVitree;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Facteur solaire Sw du vitrage — §6.2.1 p.45-46.
 *
 * Algorithme :
 *   1. Si `<sw_1>` est présent (saisie directe) → lecture directe.
 *   2. Si `enum_type_baie_id` ∈ {1, 2} (brique de verre) ou `enum_type_materiaux_menuiserie_id` = 2
 *      ou `enum_type_baie_id` = 3 (polycarbonate) → Sw = 0,4.
 *   3. Sinon : lookup `tv_sw` par
 *      (menuiserie_key, baie_key, position_key, vitrage_key).
 *
 * position_key :
 *   'nu_ext'        si `enum_type_pose_id` = 1
 *   'nu_int_tunnel' si `enum_type_pose_id` = 2 ou 3 (nu intérieur / tunnel)
 *
 * vitrage_key :
 *   'simple'     enum_type_vitrage_id = 1
 *   'double'     id = 2, vitrage_vir = 0 (ou survitrage id = 4)
 *   'double_vir' id = 2, vitrage_vir = 1
 *   'triple'     id = 3, vitrage_vir = 0
 *   'triple_vir' id = 3, vitrage_vir = 1
 *
 * @spec-section 6.2.1
 * @spec-pages 45-46
 * @spec-source resources/specsplitted/06-apports-gratuits/02-surface-sud-equivalente/01-facteur-solaire.md
 * @xml-input  baie_vitree.donnee_entree.{sw_1, enum_type_baie_id, enum_type_materiaux_menuiserie_id,
 *             enum_type_vitrage_id, vitrage_vir, enum_type_pose_id}
 * @xml-output baie_vitree.donnee_intermediaire.sw
 * @tables tv_sw
 */
final class SwCalculator implements CalculatorInterface
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
        return $node->nodeName === 'baie_vitree';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree   = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('baie_vitree sans <donnee_entree>.');
        }

        $sw = $this->resolveSw($entree, $accessor, $context);
        if ($sw === null) {
            return;
        }

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'sw', $sw);
    }

    private function resolveSw(DOMElement $entree, NodeAccessor $accessor, CalculationContext $context): ?float
    {
        // 1. Saisie directe
        $direct = $accessor->getFloatOrNull('./sw_1', $entree);
        if ($direct !== null) {
            return $direct;
        }

        $tvSw    = $context->tables->load('enveloppe/tv_sw');
        $typeBaie = $accessor->getIntOrNull('./enum_type_baie_id', $entree);
        $typeMenu = $accessor->getIntOrNull('./enum_type_materiaux_menuiserie_id', $entree);

        // 2. Cas spéciaux : brique de verre (baie_id 1/2 ou menu_id 1) ou polycarbonate (baie_id 3 ou menu_id 2)
        if (in_array($typeBaie, [1, 2], true) || $typeMenu === 1) {
            return (float)$tvSw['special']['brique_verre'];
        }
        if ($typeBaie === 3 || $typeMenu === 2) {
            return (float)$tvSw['special']['polycarbonate'];
        }

        // 3. Lookup table
        $menuKey = $this->menuiserieKey($typeMenu);
        $baieKey = $this->baieKey($typeBaie);
        $poseKey = $this->poseKey($accessor->getIntOrNull('./enum_type_pose_id', $entree));
        $vitrageKey = $this->vitrageKey(
            $accessor->getIntOrNull('./enum_type_vitrage_id', $entree),
            $accessor->getIntOrNull('./vitrage_vir', $entree)
        );

        if ($menuKey === null || $baieKey === null || $poseKey === null || $vitrageKey === null) {
            return null;
        }

        return (float)($tvSw['sw'][$menuKey][$baieKey][$poseKey][$vitrageKey] ?? null);
    }

    private function menuiserieKey(?int $enumId): ?string
    {
        return match ($enumId) {
            3, 4    => 'bois',
            5       => 'pvc',
            6       => 'metal_rpt',
            7       => 'metal_sans_rpt',
            default => null,
        };
    }

    private function baieKey(?int $enumId): ?string
    {
        return match ($enumId) {
            4       => 'battante',
            5       => 'coulissante',
            6       => 'pf_coulissante',
            7       => 'pf_battante',
            8       => 'pf_battante_soubassement',
            default => null,
        };
    }

    private function poseKey(?int $enumId): ?string
    {
        return match ($enumId) {
            1       => 'nu_ext',
            2, 3    => 'nu_int_tunnel',
            default => 'nu_ext', // défaut si absent
        };
    }

    private function vitrageKey(?int $vitrageId, ?int $vir): ?string
    {
        return match (true) {
            $vitrageId === 1                         => 'simple',
            $vitrageId === 2 && $vir !== 1           => 'double',
            $vitrageId === 2 && $vir === 1           => 'double_vir',
            $vitrageId === 3 && $vir !== 1           => 'triple',
            $vitrageId === 3 && $vir === 1           => 'triple_vir',
            $vitrageId === 4                         => 'double',   // survitrage → double équivalent
            default                                  => null,
        };
    }
}
