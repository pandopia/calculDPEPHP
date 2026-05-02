<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe\PlancherBas;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Calcul de Upb_final.
 *
 * Pour les planchers donnant sur extérieur ou local non chauffé (hors sous-sol),
 * Upb_final = Upb (le plancher est traité comme une paroi déperditive classique).
 *
 * Pour les planchers sur **vide sanitaire** (adj=3), **sous-sol non chauffé** (adj=6),
 * **terre-plein** (adj=5) ou **paroi enterrée** (adj=2), un coefficient Ue est calculé
 * via les tableaux §3.2.2.1 (p.18-19) en fonction de di.upb (Uiso) et 2S/P.
 *
 *   - Vide sanitaire / sous-sol non chauffé : tableau unique p.18.
 *   - Terre-plein                            : 2 tableaux selon avant/après 2001 (p.19).
 *
 * Une **interpolation bilinéaire** est utilisée pour les valeurs intermédiaires (et
 * extrapolation linéaire en bordure). Les valeurs hors plage sont clampées au seuil
 * du tableau.
 *
 * @spec-section 3.2.2.1
 * @spec-pages 18-19
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/02-upb/00-calcul.md
 * @xml-input  plancher_bas.donnee_entree.{enum_type_adjacence_id, surface_ue, perimetre_ue} + donnee_intermediaire.upb
 * @xml-output plancher_bas.donnee_intermediaire.upb_final
 * @depends-on \CalculDpePHP\Enveloppe\PlancherBas\UpbCalculator
 * @tables tv_ue_vide_sanitaire, tv_ue_terre_plein
 */
final class UpbFinalCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [UpbCalculator::class];
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
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);

        // di.upb = Uiso (valeur isolée, calculée par UpbCalculator)
        $upb = $accessor->getFloatOrNull('./upb', $intermediaire);
        if ($upb === null) {
            throw new RuntimeException('UpbFinalCalculator : upb absent.');
        }

        $adjacence = $accessor->getIntOrNull('./enum_type_adjacence_id', $entree);

        $upbFinal = match ($adjacence) {
            3, 6    => $this->ueViaTableau('enveloppe/tv_ue_vide_sanitaire', $upb, $entree, $accessor, $context),
            2, 5    => $this->ueTerrePlein($upb, $entree, $accessor, $context),
            default => $upb,
        };

        $accessor->setChildValue($intermediaire, 'upb_final', $upbFinal);
    }

    private function ueTerrePlein(float $upb, DOMElement $entree, NodeAccessor $accessor, CalculationContext $context): float
    {
        $tableRoot = $context->tables->load('enveloppe/tv_ue_terre_plein');
        $isAfter2001 = $context->periodeConstructionId !== null && $context->periodeConstructionId >= 7;
        $table = $isAfter2001 ? $tableRoot['depuis_2001'] : $tableRoot['avant_2001'];

        return $this->bilinearInterpolation($upb, $entree, $accessor, $table);
    }

    /**
     * @param string $tableRel "enveloppe/tv_ue_vide_sanitaire" ou clé d'un sous-arbre déjà extrait
     */
    private function ueViaTableau(string $tableRel, float $upb, DOMElement $entree, NodeAccessor $accessor, CalculationContext $context): float
    {
        $table = $context->tables->load($tableRel);
        return $this->bilinearInterpolation($upb, $entree, $accessor, $table);
    }

    /**
     * Interpolation bilinéaire dans le tableau Ue(Upb, 2S/P).
     *
     * @param array{upb_axis: list<float>, ratios: array<int, list<float>>} $table
     */
    private function bilinearInterpolation(float $upb, DOMElement $entree, NodeAccessor $accessor, array $table): float
    {
        $surface   = $accessor->getFloatOrNull('./surface_ue', $entree);
        $perimetre = $accessor->getFloatOrNull('./perimetre_ue', $entree);
        if ($surface === null || $perimetre === null || $perimetre <= 0) {
            // Données manquantes : on retombe sur Upb (comportement le plus conservateur)
            return $upb;
        }

        $ratio = (2.0 * $surface) / $perimetre;
        // §3.2.2.1 p.18 : "2S/P est arrondi à l'entier le plus proche"
        $ratio = (int)round($ratio);

        $ratiosTable = $table['ratios'];
        $ratiosKeys  = array_keys($ratiosTable);
        sort($ratiosKeys);

        // Encadrement vertical
        [$rLow, $rHigh] = $this->bracket($ratio, $ratiosKeys);

        // Encadrement horizontal sur Upb
        $upbAxis = $table['upb_axis'];
        $idxLow  = $this->bracketIndex($upb, $upbAxis);
        $idxHigh = min($idxLow + 1, count($upbAxis) - 1);

        $valLowLow   = $ratiosTable[$rLow][$idxLow];
        $valLowHigh  = $ratiosTable[$rLow][$idxHigh];
        $valHighLow  = $ratiosTable[$rHigh][$idxLow];
        $valHighHigh = $ratiosTable[$rHigh][$idxHigh];

        // Interpolation horizontale (Upb)
        $upbLow  = $upbAxis[$idxLow];
        $upbHigh = $upbAxis[$idxHigh];
        $tx = ($upbHigh - $upbLow) > 0 ? ($upb - $upbLow) / ($upbHigh - $upbLow) : 0.0;
        $valLow  = $valLowLow  + $tx * ($valLowHigh  - $valLowLow);
        $valHigh = $valHighLow + $tx * ($valHighHigh - $valHighLow);

        // Interpolation verticale (2S/P)
        $ty = ($rHigh - $rLow) > 0 ? ($ratio - $rLow) / ($rHigh - $rLow) : 0.0;
        return $valLow + $ty * ($valHigh - $valLow);
    }

    /**
     * @param list<int> $axis
     * @return array{0: int, 1: int}
     */
    private function bracket(int $value, array $axis): array
    {
        if ($value <= $axis[0]) return [$axis[0], $axis[0]];
        $last = $axis[count($axis) - 1];
        if ($value >= $last) return [$last, $last];
        for ($i = 0; $i < count($axis) - 1; $i++) {
            if ($value >= $axis[$i] && $value <= $axis[$i + 1]) {
                return [$axis[$i], $axis[$i + 1]];
            }
        }
        return [$axis[0], $last];
    }

    /**
     * @param list<float> $axis
     */
    private function bracketIndex(float $value, array $axis): int
    {
        if ($value <= $axis[0]) return 0;
        $last = count($axis) - 1;
        if ($value >= $axis[$last]) return $last;
        for ($i = 0; $i < $last; $i++) {
            if ($value >= $axis[$i] && $value <= $axis[$i + 1]) {
                return $i;
            }
        }
        return 0;
    }
}
