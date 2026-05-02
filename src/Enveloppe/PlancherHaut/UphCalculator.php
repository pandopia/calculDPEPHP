<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe\PlancherHaut;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Calcul de Uph (plancher haut, isolation incluse) — §3.2.3.1.
 *
 * Selon `enum_methode_saisie_u_id` :
 *   1  non isolé                                  → Uph = Uph0
 *   2  isolation inconnue (table forfaitaire)     → Uph = min(Uph0, Uph_tab)
 *   3,4  épaisseur saisie                         → Uph = 1 / (1/Uph0 + e/0,04)
 *   5,6  R isolant saisie                         → Uph = 1 / (1/Uph0 + R)
 *   7,8  table forfaitaire (année)                → Uph = min(Uph0, Uph_tab)
 *   9,10 saisie directe U                          → Uph = u_saisi
 *
 * Le sous-tableau Uph_tab est choisi entre "combles" et "terrasse" selon
 * `enum_type_adjacence_id` :
 *   - {11, 12, 13} (combles) → 'combles'
 *   - {1 (toiture-terrasse), 4, 7, autres locaux non chauffés} → 'terrasse'
 *
 * @spec-section 3.2.3.1
 * @spec-pages 21
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/03-uph/00-calcul.md
 * @xml-input  plancher_haut.donnee_entree.{enum_methode_saisie_u_id, enum_type_isolation_id, enum_type_adjacence_id, epaisseur_isolation, resistance_isolation, enum_periode_isolation_id}
 * @xml-output plancher_haut.donnee_intermediaire.uph
 * @depends-on \CalculDpePHP\Enveloppe\PlancherHaut\Uph0Calculator
 * @tables tv_uph_tab
 */
final class UphCalculator implements CalculatorInterface
{
    private const LAMBDA_ISOLANT = 0.04;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [Uph0Calculator::class];
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
        $intermediaire = $accessor->ensureDonneeIntermediaire($node);

        $uph0 = $accessor->getFloatOrNull('./uph0', $intermediaire);
        if ($uph0 === null) {
            throw new RuntimeException('UphCalculator : uph0 absent.');
        }

        $methodeU      = $accessor->getIntOrNull('./enum_methode_saisie_u_id', $entree);
        $typeIsolation = $accessor->getIntOrNull('./enum_type_isolation_id', $entree);

        $uph = $this->computeUph($uph0, $methodeU, $typeIsolation, $entree, $accessor, $context);
        $accessor->setChildValue($intermediaire, 'uph', $uph);
    }

    private function computeUph(
        float $uph0,
        ?int $methode,
        ?int $typeIsolation,
        DOMElement $entree,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        if ($methode === 1 || $typeIsolation === 2) {
            return $uph0;
        }

        return match ($methode) {
            3, 4    => $this->byEpaisseur($uph0, $accessor->getFloatOrNull('./epaisseur_isolation', $entree)),
            5, 6    => $this->byResistance($uph0, $accessor->getFloatOrNull('./resistance_isolation', $entree)),
            9, 10   => $accessor->getFloatOrNull('./u_saisi', $entree) ?? $uph0,
            2, 7, 8 => $this->lookupUphTab($uph0, $entree, $accessor, $context),
            default => $uph0,
        };
    }

    /** @spec-formula F-3.2.3-épaisseur */
    private function byEpaisseur(float $uph0, ?float $epaisseurCm): float
    {
        if ($epaisseurCm === null || $epaisseurCm <= 0) return $uph0;
        $e = $epaisseurCm / 100.0;
        return 1.0 / (1.0 / $uph0 + $e / self::LAMBDA_ISOLANT);
    }

    /** @spec-formula F-3.2.3-resistance */
    private function byResistance(float $uph0, ?float $r): float
    {
        if ($r === null || $r <= 0) return $uph0;
        return 1.0 / (1.0 / $uph0 + $r);
    }

    private function lookupUphTab(
        float $uph0,
        DOMElement $entree,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        $zone = $context->zoneGroupe;
        $energie = $context->energieChauffagePrincipale;
        $periode = $accessor->getIntOrNull('./enum_periode_isolation_id', $entree)
            ?? $context->periodeConstructionId;

        if ($zone === null || $energie === null || $periode === null) {
            return $uph0;
        }

        $config = $this->configFromAdjacence($accessor->getIntOrNull('./enum_type_adjacence_id', $entree));
        $table  = $context->tables->load('enveloppe/tv_uph_tab');
        $value  = $table[$config][$zone][$energie][$periode] ?? null;
        if ($value === null) {
            return $uph0;
        }
        return min($uph0, (float)$value);
    }

    /**
     * Sélectionne la sous-table 'combles' ou 'terrasse' selon l'adjacence (§3.2.3.1 p.21).
     * Par défaut : 'terrasse' (cas conservateur cf. note "local au-dessus est non chauffé").
     */
    private function configFromAdjacence(?int $adjacence): string
    {
        return match ($adjacence) {
            11, 12, 13 => 'combles',
            default    => 'terrasse',
        };
    }
}
