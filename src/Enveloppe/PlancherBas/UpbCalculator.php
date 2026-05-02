<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe\PlancherBas;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Calcul de Upb (plancher bas, U isolé) — §3.2.2.1 p.17.
 *
 * Selon `enum_methode_saisie_u_id` :
 *   1  non isolé                                  → Upb = Upb0
 *   2  isolation inconnue (table forfaitaire)     → Upb = min(Upb0, Upb_tab)
 *   3,4  épaisseur saisie                         → Upb = 1 / (1/Upb0 + e/0,042)
 *   5,6  R isolant saisie                         → Upb = 1 / (1/Upb0 + R)
 *   7,8  table forfaitaire (année)                → Upb = min(Upb0, Upb_tab)
 *   9,10 saisie directe U                          → Upb = u_saisi
 *
 * Convention open3cl : upb = Uiso (valeur isolée), upb_final = après table d'adjacence
 * (calculé par UpbFinalCalculator).
 *
 * @spec-section 3.2.2.1
 * @spec-pages 17
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/02-upb/00-calcul.md
 * @xml-input  plancher_bas.donnee_entree.{enum_methode_saisie_u_id, enum_type_isolation_id, epaisseur_isolation, resistance_isolation, enum_periode_isolation_id}
 * @xml-input  plancher_bas.donnee_intermediaire.upb0
 * @xml-output plancher_bas.donnee_intermediaire.upb
 * @depends-on \CalculDpePHP\Enveloppe\PlancherBas\Upb0Calculator
 * @tables tv_upb_tab
 */
final class UpbCalculator implements CalculatorInterface
{
    /** Conductivité thermique conventionnelle de l'isolant des planchers bas : λ = 0,042 W/(m.K). */
    private const LAMBDA_ISOLANT = 0.042;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [Upb0Calculator::class];
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

        $upb0 = $accessor->getFloatOrNull('./upb0', $intermediaire);
        if ($upb0 === null) {
            throw new RuntimeException('UpbCalculator : upb0 absent (Upb0Calculator non exécuté ?).');
        }

        $methodeU      = $accessor->getIntOrNull('./enum_methode_saisie_u_id', $entree);
        $typeIsolation = $accessor->getIntOrNull('./enum_type_isolation_id', $entree);

        $upb = $this->computeUpb($upb0, $methodeU, $typeIsolation, $entree, $accessor, $context);
        $accessor->setChildValue($intermediaire, 'upb', $upb);
    }

    private function computeUpb(
        float $upb0,
        ?int $methode,
        ?int $typeIsolation,
        DOMElement $entree,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        if ($methode === 1 || $typeIsolation === 2) {
            return $upb0;
        }

        return match ($methode) {
            3, 4 => $this->byEpaisseur($upb0, $accessor->getFloatOrNull('./epaisseur_isolation', $entree)),
            5, 6 => $this->byResistance($upb0, $accessor->getFloatOrNull('./resistance_isolation', $entree)),
            9, 10 => $accessor->getFloatOrNull('./u_saisi', $entree) ?? $upb0,
            2, 7, 8 => $this->lookupUpbTab($upb0, $entree, $accessor, $context),
            default => $upb0,
        };
    }

    /**
     * Upb = 1 / (1/Upb0 + e/λ), avec e en mètres et λ = 0.042 W/(m.K).
     *
     * @spec-formula F-3.2.2-épaisseur
     */
    private function byEpaisseur(float $upb0, ?float $epaisseurCm): float
    {
        if ($epaisseurCm === null || $epaisseurCm <= 0) return $upb0;
        $e = $epaisseurCm / 100.0;
        return 1.0 / (1.0 / $upb0 + $e / self::LAMBDA_ISOLANT);
    }

    /**
     * Upb = 1 / (1/Upb0 + R).
     *
     * @spec-formula F-3.2.2-resistance
     */
    private function byResistance(float $upb0, ?float $r): float
    {
        if ($r === null || $r <= 0) return $upb0;
        return 1.0 / (1.0 / $upb0 + $r);
    }

    /**
     * Lookup Upb_tab par zone climatique × énergie chauffage × période d'isolation.
     * Upb final = min(Upb0, Upb_tab).
     */
    private function lookupUpbTab(
        float $upb0,
        DOMElement $entree,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        $zone     = $context->zoneGroupe;
        $energie  = $context->energieChauffagePrincipale;
        $periodeIsolation = $accessor->getIntOrNull('./enum_periode_isolation_id', $entree)
            ?? $context->periodeConstructionId;

        if ($zone === null || $energie === null || $periodeIsolation === null) {
            return $upb0;
        }

        $table = $context->tables->load('enveloppe/tv_upb_tab');
        $upbTab = $table[$zone][$energie][$periodeIsolation] ?? null;
        if ($upbTab === null) {
            return $upb0;
        }
        return min($upb0, (float)$upbTab);
    }
}
