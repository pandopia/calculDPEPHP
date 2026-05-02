<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe\BaieVitree;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Coefficient Uw (fenêtre complète) — §3.3.2.
 *
 * Algorithme :
 *   1. Si `enum_methode_saisie_perf_vitrage_id` correspond à une saisie directe Uw
 *      (méthodes 5, 8, 11, 14) ou si `<uw_1>` est présent dans la donnée d'entrée :
 *      lecture directe.
 *   2. Cas spéciaux (paroi en brique de verre / polycarbonate) — `enum_type_baie_id` 1/2/3 :
 *      Uw forfaitaire (3.5 / 2.0 / 3.0 selon §3.3 et `tv_ug.special_uw`).
 *   3. Sinon : lookup tv_uw par (type_menuiserie, Ug, type_baie) avec **interpolation linéaire
 *      entre les deux Ug tabulés les plus proches** (§3.3.2 introduction).
 *   4. Double-fenêtre (`double_fenetre == 1`) : on suppose que `<uw_1>` (saisi) est utilisé
 *      via la voie 1. Sinon Uw_1 et Uw_2 doivent être disponibles individuellement (non
 *      géré pour l'instant — voir TODO).
 *
 * @spec-section 3.3.2
 * @spec-pages 26-29
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/03-parois-vitrees-portes/02-uw/00-calcul.md
 * @xml-input  baie_vitree.donnee_entree.{enum_type_baie_id, enum_type_materiaux_menuiserie_id, uw_saisi, uw_1, double_fenetre}
 * @xml-output baie_vitree.donnee_intermediaire.uw
 * @depends-on \CalculDpePHP\Enveloppe\BaieVitree\UgCalculator
 * @tables tv_uw, tv_ug.special_uw
 */
final class UwCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [UgCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'baie_vitree';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('baie_vitree sans <donnee_entree>.');
        }
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);

        $uw = $this->resolveUw($entree, $intermediaire, $accessor, $context);
        if ($uw === null) {
            return;
        }
        $accessor->setChildValue($intermediaire, 'uw', $uw);
    }

    private function resolveUw(DOMElement $entree, DOMElement $intermediaire, NodeAccessor $accessor, CalculationContext $context): ?float
    {
        // 1. Saisie directe (méthodes 5/8/11/14) ou Uw_1 pré-calculé par le diagnostiqueur
        $direct = $accessor->getFloatOrNull('./uw_saisi', $entree)
                ?? $accessor->getFloatOrNull('./uw_1', $entree);
        if ($direct !== null) {
            return $direct;
        }

        // 2. Cas spéciaux : brique de verre / polycarbonate
        $typeBaie = $accessor->getIntOrNull('./enum_type_baie_id', $entree);
        $tvUg = $context->tables->load('enveloppe/tv_ug');
        $specialKey = match ($typeBaie) {
            1       => 'brique_verre_pleine',
            2       => 'brique_verre_creuse',
            3       => 'polycarbonate',
            default => null,
        };
        if ($specialKey !== null) {
            return (float)$tvUg['special_uw'][$specialKey];
        }

        // 3. Lookup table par (type_menuiserie, Ug, type_baie)
        $ug = $accessor->getFloatOrNull('./ug', $intermediaire);
        if ($ug === null) {
            return null;
        }
        $menuKey = $this->menuiserieKey($accessor->getIntOrNull('./enum_type_materiaux_menuiserie_id', $entree));
        $baieKey = $this->baieKey($typeBaie);

        if ($menuKey === null || $baieKey === null) {
            return null;
        }

        $tvUw = $context->tables->load('enveloppe/tv_uw');
        $rows = $tvUw[$menuKey] ?? null;
        if ($rows === null) {
            throw new RuntimeException(sprintf('tv_uw : menuiserie « %s » manquante', $menuKey));
        }

        return $this->interpolateLinear($rows, $baieKey, $ug);
    }

    private function menuiserieKey(?int $enumId): ?string
    {
        return match ($enumId) {
            3, 4    => 'bois',             // bois (3) et bois/métal (4) → §3.3.2 « les mixtes prennent le bois »
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

    /**
     * Interpolation linéaire entre les Ug tabulés.
     * Les clés sont des strings (ex '0.5', '2.6') pour préserver les décimales en PHP.
     *
     * @param array<string, array<string, float>> $rows
     */
    private function interpolateLinear(array $rows, string $baieKey, float $ug): float
    {
        $ugs = [];
        foreach (array_keys($rows) as $k) $ugs[] = (float)$k;
        sort($ugs);

        if ($ug <= $ugs[0]) {
            return (float)$rows[(string)$ugs[0]][$baieKey];
        }
        $last = $ugs[count($ugs) - 1];
        if ($ug >= $last) {
            return (float)$rows[(string)$last][$baieKey];
        }
        for ($i = 0; $i < count($ugs) - 1; $i++) {
            $a = $ugs[$i];
            $b = $ugs[$i + 1];
            if ($ug >= $a && $ug <= $b) {
                $vA = (float)$rows[(string)$a][$baieKey];
                $vB = (float)$rows[(string)$b][$baieKey];
                $t = ($b > $a) ? ($ug - $a) / ($b - $a) : 0.0;
                return $vA + $t * ($vB - $vA);
            }
        }
        return (float)$rows[(string)$ugs[0]][$baieKey];
    }
}
