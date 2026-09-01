<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs\Rendement;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use DOMXPath;

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
 *   En échantillonnage individuel (§17.1.2), Qg,w est ramené au logement
 *   représentatif selon sa surface et celles des logements visités du groupe.
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
                    $qgw  *= $this->sampledIndividualScale($node, $accessor, $context);
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
     * §17.1.2 p.107-108 — caractéristiques du système d'un appartement moyen :
     * la caractéristique observée est ramenée à la surface du logement
     * représentatif par rapport aux surfaces de l'échantillon du groupe.
     *
     * Les installations sont ordonnées comme les groupes du XML. Le nombre de
     * logements visités affecté à chaque groupe suit sa part de surface dans
     * l'immeuble ; le dernier groupe reçoit le reliquat afin de conserver
     * exhaustivement l'échantillon.
     *
     * @spec-formula F-17.1.2-systeme-appartement-moyen
     */
    private function sampledIndividualScale(
        DOMElement $genNode,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        $installation = $this->findParentInstallation($genNode);
        if ($installation === null) {
            return 1.0;
        }

        $method = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $installation) ?? 1;
        $type = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $installation) ?? 1;
        if ($method !== 4 || $type !== 1) {
            return 1.0;
        }

        $logement = $this->findAncestor($installation, 'logement');
        if ($logement === null) {
            return 1.0;
        }

        $representativeSurface = $accessor->getFloatOrNull(
            './caracteristique_generale/surface_habitable_logement',
            $logement,
        );
        if ($representativeSurface === null || $representativeSurface <= 0.0) {
            return 1.0;
        }

        $xpath = new DOMXPath($context->document);
        $visitedNodes = $xpath->query('//dpe_immeuble/logement_visite_collection/logement_visite/surface_habitable_logement');
        if ($visitedNodes === false || $visitedNodes->length === 0) {
            return 1.0;
        }

        $visitedSurfaces = [];
        foreach ($visitedNodes as $visitedNode) {
            $surface = (float)str_replace(',', '.', trim($visitedNode->textContent));
            if ($surface > 0.0) {
                $visitedSurfaces[] = $surface;
            }
        }
        if ($visitedSurfaces === []) {
            return 1.0;
        }

        $installations = [];
        $totalInstallationSurface = 0.0;
        foreach ($logement->getElementsByTagName('installation_ecs') as $candidate) {
            if (!$candidate instanceof DOMElement) {
                continue;
            }
            $candidateMethod = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $candidate) ?? 1;
            $candidateType = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $candidate) ?? 1;
            if ($candidateMethod !== 4 || $candidateType !== 1) {
                continue;
            }
            $surface = $accessor->getFloatOrNull('./donnee_entree/surface_habitable', $candidate) ?? 0.0;
            $installations[] = ['node' => $candidate, 'surface' => max(0.0, $surface)];
            $totalInstallationSurface += max(0.0, $surface);
        }
        if ($installations === [] || $totalInstallationSurface <= 0.0) {
            return 1.0;
        }

        $offset = 0;
        $remainingVisits = count($visitedSurfaces);
        $lastIndex = count($installations) - 1;
        foreach ($installations as $index => $candidate) {
            $count = $index === $lastIndex
                ? $remainingVisits
                : max(1, (int)round(count($visitedSurfaces) * $candidate['surface'] / $totalInstallationSurface));
            $count = min($count, $remainingVisits - max(0, $lastIndex - $index));

            if ($candidate['node']->isSameNode($installation)) {
                $sampleSurface = array_sum(array_slice($visitedSurfaces, $offset, $count));
                return $sampleSurface > 0.0 ? $representativeSurface / $sampleSurface : 1.0;
            }

            $offset += $count;
            $remainingVisits -= $count;
        }

        return 1.0;
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

    private function findAncestor(DOMElement $node, string $tagName): ?DOMElement
    {
        $current = $node->parentNode;
        while ($current !== null) {
            if ($current instanceof DOMElement && $current->nodeName === $tagName) {
                return $current;
            }
            $current = $current->parentNode;
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
