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
 *   1. `<uw_saisi>` justifié : performance du complexe complet.
 *      Double fenêtre : Uw = 1 / (1/Uw1 + 1/Uw2 + 0,07).
 *      Sinon `<uw_1>` est la performance de la fenêtre simple.
 *   2. Cas spéciaux (paroi en brique de verre / polycarbonate) — `enum_type_baie_id` 1/2/3 :
 *      Uw forfaitaire (3.5 / 2.0 / 3.0 selon §3.3 et `tv_ug.special_uw`).
 *   3. Sinon : lookup tv_uw par (type_menuiserie, Ug, type_baie) avec **interpolation linéaire
 *      entre les deux Ug tabulés les plus proches** (§3.3.2 introduction).
 *
 * @spec-section 3.3.2
 * @spec-pages 26-30
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/03-parois-vitrees-portes/02-uw/00-calcul.md
 * @xml-input  baie_vitree.donnee_entree.{enum_type_baie_id, enum_type_materiaux_menuiserie_id, uw_saisi, uw_1, uw_2, double_fenetre}
 * @xml-output baie_vitree.donnee_intermediaire.uw
 * @xml-input  baie_vitree.baie_vitree_double_fenetre.donnee_entree (caractéristiques de la seconde fenêtre)
 * @xml-output baie_vitree.baie_vitree_double_fenetre.donnee_intermediaire.uw
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
        return $node->nodeName === 'baie_vitree'
            || ($node->nodeName === 'baie_vitree_double_fenetre'
                && !in_array($node->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'nil'), ['true', '1'], true)
                && $node->getElementsByTagName('donnee_entree')->length > 0);
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
        // Saisie justifiée du complexe complet, et non de sa première fenêtre.
        $direct = $accessor->getFloatOrNull('./uw_saisi', $entree);
        if ($direct !== null) {
            return $direct;
        }
        if ($accessor->getIntOrNull('./double_fenetre', $entree) === 1) {
            $u1 = $this->singleWindowUw($entree, $intermediaire, $accessor, $context);
            $u2 = $accessor->getFloatOrNull('./uw_2', $entree);
            if ($u2 === null) {
                // Le XSD ADEME caractérise aussi la seconde fenêtre dans
                // son propre sous-bloc. Ug y est déjà calculé par dépendance.
                $second = (new \DOMXPath($context->document))->query('../baie_vitree_double_fenetre/donnee_entree', $entree)?->item(0);
                if ($second instanceof DOMElement && $second->parentNode instanceof DOMElement) {
                    $u2 = $this->resolveUw($second, $accessor->ensureDonneeIntermediaire($second->parentNode), $accessor, $context);
                }
            }
            return $this->doubleWindowUw($u1, $u2);
        }

        return $this->singleWindowUw($entree, $intermediaire, $accessor, $context);
    }

    private function singleWindowUw(DOMElement $entree, DOMElement $intermediaire, NodeAccessor $accessor, CalculationContext $context): ?float
    {
        $direct = $accessor->getFloatOrNull('./uw_1', $entree);
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

    /**
     * §3.3.2 p.30 : Uw = 1 / (1/Uw1 + 1/Uw2 + 0,07).
     * Chaque fenêtre du complexe doit être caractérisée séparément.
     *
     * @spec-formula F-3.3.2-double-fenetre
     */
    private function doubleWindowUw(?float $u1, ?float $u2): float
    {
        if ($u1 === null || $u2 === null || !is_finite($u1) || !is_finite($u2) || $u1 <= 0.0 || $u2 <= 0.0) {
            throw new RuntimeException('Uw de double fenêtre : les deux coefficients doivent être caractérisés et strictement positifs (§3.3.2 p.30).');
        }

        return 1.0 / (1.0 / $u1 + 1.0 / $u2 + 0.07);
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
