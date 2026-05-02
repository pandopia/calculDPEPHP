<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement de génération hors combustion (§12.4 p.76-78).
 *
 * Couvre trois catégories :
 *   1. Effet joule direct (IDs 98-105)  → Rg = 1.0
 *   2. Chaudière électrique (ID 106)    → Rg = 0.97
 *   3. Réseau de chaleur (IDs 107-112)  → Rg = 0.97
 *   4. PAC (IDs 1-19)                   → Rg = SCOP (table §12.4.2)
 *
 * Pour la PAC : si scop est fourni dans donnee_entree, il est prioritaire.
 * Sinon, le SCOP par défaut est lu dans la table selon (type_pac, zone, emetteur_type).
 * L'emetteur_type (plancher/plafond ou autres) est lu depuis le contexte
 * (clé `installation.emetteur_type`, écrite par EmissionCalculator si besoin).
 * En l'absence d'information : « autres » (SCOP le plus conservateur).
 *
 * Les générateurs à combustion (IDs 20-97, 113-116) sont ignorés ici
 * — leur Rg est produit par RendementAnnuelMoyenCalculator.
 *
 * @spec-section 12.4
 * @spec-pages   76-78
 * @spec-source  resources/specsplitted/12-rendements-installations/04-generation-non-combustion.md
 * @xml-input    generateur_chauffage.donnee_entree.{enum_type_generateur_ch_id, scop}
 * @xml-output   generateur_chauffage.donnee_intermediaire.{rendement_generation, scop}
 * @depends-on   (aucun)
 * @tables       (aucune — valeurs SCOP intégrées d'après §12.4.2)
 */
final class GenerationNonCombustionCalculator implements CalculatorInterface
{
    /** IDs effet joule direct → Rg = 1.0  (§12.4.1) */
    private const JOULE_DIRECT = [98, 99, 100, 101, 102, 103, 104, 105];

    /** IDs chaudière électrique / réseau de chaleur → Rg = 0.97  (§12.4.1) */
    private const RESEAU_IDS = [106, 107, 108, 109, 110, 111, 112];

    /** IDs PAC (1-19) — SCOP en fonction de la zone et du type */
    private const PAC_RANGE = [1, 19];

    /**
     * SCOP par défaut §12.4.2.
     * Structure : [id_gen => ['h1h2' => ['autres' => scop, 'plancher_plafond' => scop],
     *                         'h3'   => ['autres' => scop, 'plancher_plafond' => scop]]]
     * PAC Air/Air n'a pas de variante plancher/plafond (colonne unique).
     */
    private const SCOP_TABLE = [
        // PAC Air/Air
        1  => ['h1h2' => ['autres' => 2.2, 'plancher_plafond' => 2.2], 'h3' => ['autres' => 2.4, 'plancher_plafond' => 2.4]],
        2  => ['h1h2' => ['autres' => 2.3, 'plancher_plafond' => 2.3], 'h3' => ['autres' => 2.6, 'plancher_plafond' => 2.6]],
        3  => ['h1h2' => ['autres' => 3.0, 'plancher_plafond' => 3.0], 'h3' => ['autres' => 3.3, 'plancher_plafond' => 3.3]],
        // PAC Air/Eau
        4  => ['h1h2' => ['autres' => 2.2, 'plancher_plafond' => 2.4], 'h3' => ['autres' => 2.5, 'plancher_plafond' => 2.9]],
        5  => ['h1h2' => ['autres' => 2.4, 'plancher_plafond' => 2.6], 'h3' => ['autres' => 2.8, 'plancher_plafond' => 3.1]],
        6  => ['h1h2' => ['autres' => 2.6, 'plancher_plafond' => 2.9], 'h3' => ['autres' => 3.0, 'plancher_plafond' => 3.5]],
        7  => ['h1h2' => ['autres' => 2.8, 'plancher_plafond' => 3.2], 'h3' => ['autres' => 3.2, 'plancher_plafond' => 3.8]],
        // PAC Eau/Eau
        8  => ['h1h2' => ['autres' => 2.2, 'plancher_plafond' => 2.4], 'h3' => ['autres' => 2.5, 'plancher_plafond' => 2.9]],
        9  => ['h1h2' => ['autres' => 2.4, 'plancher_plafond' => 2.6], 'h3' => ['autres' => 2.8, 'plancher_plafond' => 3.1]],
        10 => ['h1h2' => ['autres' => 2.7, 'plancher_plafond' => 3.0], 'h3' => ['autres' => 3.1, 'plancher_plafond' => 3.6]],
        11 => ['h1h2' => ['autres' => 3.0, 'plancher_plafond' => 3.3], 'h3' => ['autres' => 3.5, 'plancher_plafond' => 4.0]],
        // PAC Eau glycolée/Eau
        12 => ['h1h2' => ['autres' => 2.2, 'plancher_plafond' => 2.4], 'h3' => ['autres' => 2.5, 'plancher_plafond' => 2.9]],
        13 => ['h1h2' => ['autres' => 2.4, 'plancher_plafond' => 2.6], 'h3' => ['autres' => 2.8, 'plancher_plafond' => 3.1]],
        14 => ['h1h2' => ['autres' => 2.7, 'plancher_plafond' => 3.0], 'h3' => ['autres' => 3.1, 'plancher_plafond' => 3.6]],
        15 => ['h1h2' => ['autres' => 3.0, 'plancher_plafond' => 3.3], 'h3' => ['autres' => 3.5, 'plancher_plafond' => 4.0]],
        // PAC Géothermie
        16 => ['h1h2' => ['autres' => 2.2, 'plancher_plafond' => 2.4], 'h3' => ['autres' => 2.5, 'plancher_plafond' => 2.9]],
        17 => ['h1h2' => ['autres' => 2.4, 'plancher_plafond' => 2.6], 'h3' => ['autres' => 2.8, 'plancher_plafond' => 3.1]],
        18 => ['h1h2' => ['autres' => 2.7, 'plancher_plafond' => 3.0], 'h3' => ['autres' => 3.1, 'plancher_plafond' => 3.6]],
        19 => ['h1h2' => ['autres' => 3.0, 'plancher_plafond' => 3.3], 'h3' => ['autres' => 3.5, 'plancher_plafond' => 4.0]],
    ];

    /** enum_zone_climatique_id valant H3 */
    private const ZONE_H3_ID = 8;

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
        return $node->nodeName === 'generateur_chauffage';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $genId    = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $node);

        if ($genId === null) {
            return;
        }

        if (in_array($genId, self::JOULE_DIRECT, true)) {
            $this->writeRg($node, $accessor, $context, 1.0);
            return;
        }

        if (in_array($genId, self::RESEAU_IDS, true)) {
            $this->writeRg($node, $accessor, $context, 0.97);
            return;
        }

        if ($genId >= self::PAC_RANGE[0] && $genId <= self::PAC_RANGE[1]) {
            $scop = $this->resolvePacScop($node, $accessor, $context, $genId);
            $di   = $accessor->ensureDonneeIntermediaire($node);
            $accessor->setChildValue($di, 'scop', $scop);
            $accessor->setChildValue($di, 'rendement_generation', $scop);
            return;
        }

        // Combustion et autres : rendement_generation non écrit ici
    }

    private function writeRg(DOMElement $node, NodeAccessor $accessor, CalculationContext $context, float $rg): void
    {
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_generation', $rg);
    }

    private function resolvePacScop(
        DOMElement $node,
        NodeAccessor $accessor,
        CalculationContext $context,
        int $genId,
    ): float {
        // SCOP saisi par l'utilisateur dans donnee_entree
        $scopInput = $accessor->getFloatOrNull('./donnee_entree/scop', $node);
        if ($scopInput !== null && $scopInput > 0.0) {
            return $scopInput;
        }

        $row       = self::SCOP_TABLE[$genId] ?? null;
        if ($row === null) {
            return 2.2;
        }

        $zoneId    = $this->resolveZone($context, $accessor);
        $zoneKey   = ($zoneId === self::ZONE_H3_ID) ? 'h3' : 'h1h2';
        $emType    = $context->get('installation.emetteur_type', 'autres');

        return (float)($row[$zoneKey][$emType] ?? $row[$zoneKey]['autres'] ?? 2.2);
    }

    private function resolveZone(CalculationContext $context, NodeAccessor $accessor): int
    {
        $zoneId = $context->get('logement.zone_climatique_id');
        if ($zoneId !== null) {
            return (int)$zoneId;
        }
        $id = $accessor->getIntOrNull('//caracteristique_generale/enum_zone_climatique_id');
        $context->set('logement.zone_climatique_id', $id);
        return (int)($id ?? 1);
    }
}
