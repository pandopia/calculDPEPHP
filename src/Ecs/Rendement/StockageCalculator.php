<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs\Rendement;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement de stockage ECS Rs (§11.6 p.74-75).
 *
 * S'il n'y a pas de stockage (Vs = 0) : Qg,w = 0 → Rs = 1
 *
 * Ballons électriques (§11.6.2) :
 *   Qg,w = 8592 × (45/24) × Vs × Cr
 *   Cr lu dans tv_pertes_stockage (tableau indexé par tv_pertes_stockage_id)
 *   Rs = 1,08 / (1 + Qg,w × Rd / Becs)  pour cat C ou 3*
 *   Rs = 1    / (1 + Qg,w × Rd / Becs)  pour les autres ballons électriques
 *
 * Autres ballons (§11.6.1) :
 *   Qg,w = 67662 × Vs^0,55
 *   Rs = 1 / (1 + Qg,w × Rd / Becs)
 *
 * Ne s'applique pas aux CET (chauffe-eau thermodynamiques) — traités en §14.2.
 *
 * @spec-section 11.6
 * @spec-pages   74-75
 * @spec-source  resources/specsplitted/11-conso-ecs/06-rendement-stockage.md
 * @xml-input    generateur_ecs.donnee_entree.{volume_stockage, enum_type_energie_id, enum_type_generateur_ecs_id, tv_pertes_stockage_id}
 * @xml-output   generateur_ecs.donnee_intermediaire.rendement_stockage
 * @depends-on   \CalculDpePHP\Ecs\BesoinEcsCalculator
 * @depends-on   \CalculDpePHP\Ecs\Rendement\DistributionCalculator
 * @tables       ecs/tv_pertes_stockage
 */
final class StockageCalculator implements CalculatorInterface
{
    /** Constante annuelle (358 jours × 24 h) × 45°C — §11.6.2 */
    private const FACTEUR_ELEC = 8592.0 * 45.0 / 24.0;

    /** Identifiants CET — §14.2 traite leur Rs différemment */
    private const CET_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Ecs\BesoinEcsCalculator',
            '\CalculDpePHP\Ecs\Rendement\DistributionCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'generateur_ecs';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $vs        = $accessor->getFloatOrNull('./donnee_entree/volume_stockage', $node) ?? 0.0;
        $energieId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $node);
        $typeGenId = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $node);
        $isCet     = ($typeGenId !== null && in_array($typeGenId, self::CET_IDS, true));

        $rs  = 1.0;
        $qgw = 0.0;

        if ($vs > 0.0) {
            // §11.6.2 ne s'applique qu'aux « ballons électriques à accumulation » (enum 68-71).
            // Les CET utilisent la formule générique §11.6.1 (Qgw = 67662 × Vs^0.55), comme
            // open3cl `14_generateur_ecs.js:calc_Qgw`. Leur Rs réel est porté par le COP (§14.2).
            $isBallonElec = in_array($typeGenId, [68, 69, 70, 71], true);
            $rd           = $this->resolveRd($node, $accessor, $context);
            $becsWh       = $this->resolveBecsWh($node, $accessor, $context);

            if ($becsWh > 0.0) {
                if ($isBallonElec) {
                    $qgw   = $this->qgwElectrique($vs, $node, $accessor, $context);
                    $catC  = $this->isCatCVertical($node, $accessor, $context);
                    $denom = 1.0 + $qgw * $rd / $becsWh;
                    $rs    = ($catC ? 1.08 : 1.0) / $denom;
                } else {
                    $qgw   = 67662.0 * ($vs ** 0.55);
                    $denom = 1.0 + $qgw * $rd / $becsWh;
                    $rs    = 1.0 / $denom;
                }
            }
        }

        $di = $this->ensureDi($context->document, $node);
        // CET : rendement_stockage géré par CetAccumulationCalculator (= COP).
        // On écrit Qgw seulement pour permettre la récupération des pertes (§9.1.1).
        if (!$isCet) {
            $accessor->setChildValue($di, 'rendement_stockage', $rs);
        }
        $accessor->setChildValue($di, 'Qgw', $qgw);

        $ref = $accessor->getStringOrNull('./donnee_entree/reference', $node) ?? '';
        $context->set('ecs.rendement_stockage.' . $ref, $rs);
    }

    /**
     * Qg,w pour ballon électrique : 8592 × (45/24) × Vs × Cr — §11.6.2 p.74.
     */
    private function qgwElectrique(float $vs, DOMElement $node, NodeAccessor $accessor, CalculationContext $context): float
    {
        $tvId = $accessor->getIntOrNull('./donnee_entree/tv_pertes_stockage_id', $node);
        $cr   = 0.25; // par défaut : cat C ≤100

        if ($tvId !== null) {
            $table = $context->tables->load('ecs/tv_pertes_stockage');
            $cr    = (float)(($table[$tvId] ?? [])['cr'] ?? $cr);
        }

        return self::FACTEUR_ELEC * $vs * $cr;
    }

    /**
     * Catégorie C ou 3 étoiles vertical → Rs = 1,08/… sinon 1/….
     * Déterminé par tv_pertes_stockage_id (flag cat_c) ou enum_type_generateur_ecs_id.
     */
    private function isCatCVertical(DOMElement $node, NodeAccessor $accessor, CalculationContext $context): bool
    {
        $typeGenId = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $node);
        if ($typeGenId === 71) {
            return true;
        }

        $tvId = $accessor->getIntOrNull('./donnee_entree/tv_pertes_stockage_id', $node);
        if ($tvId !== null) {
            $table = $context->tables->load('ecs/tv_pertes_stockage');
            return (bool)(($table[$tvId] ?? [])['cat_c'] ?? false);
        }

        return false;
    }

    /**
     * Rd depuis donnee_intermediaire de l'installation parente.
     */
    private function resolveRd(DOMElement $genNode, NodeAccessor $accessor, CalculationContext $context): float
    {
        $inst = $this->findParentInstallation($genNode);
        if ($inst !== null) {
            $rd = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_distribution', $inst);
            if ($rd !== null) {
                return $rd;
            }
        }
        return (float)$context->get('ecs.rendement_distribution', 1.0);
    }

    /**
     * Becs (Wh) depuis donnee_intermediaire de l'installation parente.
     */
    private function resolveBecsWh(DOMElement $genNode, NodeAccessor $accessor, CalculationContext $context): float
    {
        $inst = $this->findParentInstallation($genNode);
        if ($inst !== null) {
            $becs = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ecs', $inst);
            if ($becs !== null) {
                return $becs * 1000.0; // kWh → Wh
            }
        }
        return (float)$context->get('ecs.besoin_ecs', 0.0) * 1000.0;
    }

    private function findParentInstallation(DOMElement $node): ?DOMElement
    {
        $cur = $node->parentNode;
        while ($cur !== null) {
            if ($cur instanceof DOMElement && $cur->nodeName === 'installation_ecs') {
                return $cur;
            }
            $cur = $cur->parentNode;
        }
        return null;
    }

    private function ensureDi(\DOMDocument $doc, DOMElement $parent): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === 'donnee_intermediaire') {
                return $c;
            }
        }
        $el = $doc->createElement('donnee_intermediaire');
        $parent->appendChild($el);
        return $el;
    }
}
