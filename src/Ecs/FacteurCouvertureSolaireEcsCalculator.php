<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Facteur de couverture solaire ECS (Fecs) — §18.4 p.143.
 *
 * Le facteur de couverture solaire correspond à la fraction du besoin d'ECS
 * couvert par l'apport solaire thermique. Il est appliqué dans le calcul de
 * la consommation : `conso = besoin × (1 − Fecs) × Iecs` (§14.4 p.95-96).
 *
 * Résolution :
 *   1. Si `fecs_saisi` est présent → utiliser cette valeur directement.
 *   2. Sinon, lookup dans `tv_facteur_couverture_solaire` indexé par
 *      `enum_zone_climatique_id` × `enum_type_installation_solaire_id` ×
 *      type_bâtiment (maison/immeuble — résolu via méthode application DPE).
 *
 * Mapping `enum_type_installation_solaire_id` (XSD) → colonne table :
 *   1 = chauffage solaire seul/combiné      → fch (hors scope ECS)
 *   2 = ECS solaire seule sup 5 ans          → fecs_maison_gt5 / fecs_collectif_gt5
 *   3 = ECS solaire seule inf 5 ans          → fecs_maison_le5 / fecs_collectif_le5
 *   4 = chauffage + ECS solaire              → fecs_combi (maison uniquement)
 *
 * Le type bâtiment est "maison" pour `enum_methode_application_dpe_log_id` ∈ {1,14,18}
 * (et appartement 2,3,4,5,31,32,35,36,37 → traité comme maison côté ECS).
 * Sinon "immeuble".
 *
 * @spec-section 18.4
 * @spec-pages   143
 * @spec-source  resources/specsplitted/18-annexes/04-facteur-couverture-solaire/00-texte.md
 * @xml-input    installation_ecs.donnee_entree.{enum_type_installation_solaire_id, fecs_saisi}
 * @xml-output   installation_ecs.donnee_intermediaire.{fecs, production_ecs_solaire}
 * @depends-on   \CalculDpePHP\Ecs\BesoinEcsCalculator
 * @tables       ecs/tv_facteur_couverture_solaire
 */
final class FacteurCouvertureSolaireEcsCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Ecs\BesoinEcsCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'installation_ecs';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $typeSolaire = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_solaire_id', $node);
        if ($typeSolaire === null || $typeSolaire < 1 || $typeSolaire > 4) {
            return; // Pas de système solaire couplé
        }

        // 1. Valeur saisie
        $fecs = $accessor->getFloatOrNull('./donnee_entree/fecs_saisi', $node);

        if ($fecs === null) {
            // 2. Lookup table
            $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
            if ($zoneId === null) {
                return;
            }

            $table = $context->tables->load('ecs/tv_facteur_couverture_solaire');
            $row   = $table[(string)$zoneId] ?? $table[$zoneId] ?? null;
            if ($row === null) {
                return;
            }

            $isMaison = $this->isMaisonOuAppt($node, $accessor);
            $fecs = $this->resolveFecs($row, $typeSolaire, $isMaison);
        }

        if ($fecs === null || $fecs <= 0.0) {
            return;
        }

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'fecs', $fecs);

        // Production ECS solaire (Wh) = besoin × fecs (avant Iecs)
        $becs = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ecs', $node);
        if ($becs !== null && $becs > 0.0) {
            $accessor->setChildValue($di, 'production_ecs_solaire', $becs * $fecs * 1000.0);
        }
    }

    /**
     * Type bâtiment "maison" inclut maison + appartements individuels (open3cl :
     * th = 'maison' pour ces cas dans le matcher solaire).
     */
    private function isMaisonOuAppt(DOMElement $inst, NodeAccessor $accessor): bool
    {
        $logement = $this->findLogement($inst);
        if ($logement === null) {
            return false;
        }
        $mode = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logement);
        return in_array($mode, [1, 2, 3, 4, 5, 14, 18, 31, 32, 35, 36, 37], true);
    }

    private function findLogement(DOMElement $node): ?DOMElement
    {
        $cur = $node->parentNode;
        while ($cur !== null) {
            if ($cur instanceof DOMElement && $cur->nodeName === 'logement') {
                return $cur;
            }
            $cur = $cur->parentNode;
        }
        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveFecs(array $row, int $typeSolaire, bool $isMaison): ?float
    {
        // typeSolaire = 1 → chauffage solaire (pas d'apport ECS direct)
        if ($typeSolaire === 1) {
            return 0.0;
        }
        if ($typeSolaire === 4) {
            // chauffage + ECS — colonne fecs_combi (maison seulement)
            return $isMaison ? (float)($row['fecs_combi'] ?? 0.0) : (float)($row['fecs_collectif_le5'] ?? 0.0);
        }
        // typeSolaire ∈ {2, 3} → ECS seule (>5 ans ou ≤5 ans)
        if ($isMaison) {
            return $typeSolaire === 2
                ? (float)($row['fecs_maison_gt5'] ?? 0.0)
                : (float)($row['fecs_maison_le5'] ?? 0.0);
        }
        return $typeSolaire === 2
            ? (float)($row['fecs_collectif_gt5'] ?? 0.0)
            : (float)($row['fecs_collectif_le5'] ?? 0.0);
    }
}
