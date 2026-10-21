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
 * @spec-pages   76-78, 80
 * @spec-source  resources/specsplitted/12-rendements-installations/04-generation-non-combustion.md
 * @xml-input    generateur_chauffage.donnee_entree.{enum_type_generateur_ch_id, scop}
 * @xml-output   generateur_chauffage.donnee_intermediaire.{rendement_generation, scop}
 * @see          \CalculDpePHP\Chauffage\Rendement\RendementGenerateur
 * @depends-on   (aucun)
 * @tables       (aucune — valeurs SCOP intégrées d'après §12.4.2)
 */
final class GenerationNonCombustionCalculator implements CalculatorInterface
{
    /** IDs effet joule direct → Rg = 1.0  (§12.4.1) */
    private const JOULE_DIRECT = [98, 99, 100, 101, 102, 103, 104, 105];

    /** IDs chaudière électrique / réseau de chaleur → Rg = 0.97  (§12.4.1) */
    /**
     * 106 chaudière électrique, 107-112 réseaux de chaleur,
     * 142 réseau de chaleur non répertorié ou inconnu,
     * 171 chaudière(s) charbon multi-bâtiment modélisée comme un réseau de chaleur.
     */
    private const RESEAU_IDS = [106, 107, 108, 109, 110, 111, 112, 142, 171];

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

    /**
     * `enum_type_emission_distribution_id` des planchers et plafonds chauffants :
     * 6-9 rayonnants électriques, 11-18 sur réseau d'eau chaude.
     */
    private const EMETTEURS_PLANCHER_PLAFOND = [6, 7, 8, 9, 11, 12, 13, 14, 15, 16, 17, 18];

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

        // PAC hybride partie PAC (143, 145-147, 162-170) → SCOP de la PAC équivalente
        $genId = \CalculDpePHP\Chauffage\GenerateurChAlias::isHybridePac($genId)
            ? \CalculDpePHP\Chauffage\GenerateurChAlias::normalize($genId)
            : $genId;

        if ($genId >= self::PAC_RANGE[0] && $genId <= self::PAC_RANGE[1]) {
            $scop = $this->resolvePacScop($node, $accessor, $context, $genId);
            $di   = $accessor->ensureDonneeIntermediaire($node);
            // XSD : `scop` est publié « en remplacement du Rg pour les
            // générateurs thermodynamique », et `rendement_generation` vaut
            // « pour tous les générateurs sauf thermodynamique ». Une PAC ne
            // publie donc que son SCOP ; RendementGenerateur::lire() le relit
            // là où Ich a besoin du rendement de génération.
            $accessor->setChildValue($di, 'scop', $scop);
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

        // §12.4 enum_methode_saisie_carac_sys_id = 6 : « scop saisi » justifié par
        // documentation technique. LICIEL stocke alors la valeur dans la fiche
        // technique catégorie 7 (chauffage), description "SCOP / COP: <valeur>".
        $methode = $accessor->getIntOrNull('./donnee_entree/enum_methode_saisie_carac_sys_id', $node);
        if ($methode === 6) {
            // §12.4.2 p.80 : un SCOP réel connu et justifié prime sur le
            // forfait. Le XSD le sérialise en donnee_intermediaire ;
            // OutputPurger préserve précisément cette saisie en méthode 6.
            // La fiche technique globale peut décrire une autre PAC.
            $scopSaisi = $accessor->getFloatOrNull('./donnee_intermediaire/scop', $node);
            if ($scopSaisi !== null && $scopSaisi > 0.0) {
                return $scopSaisi;
            }
            $scopFiche = $this->readScopFromFicheTechnique($node, 7);
            if ($scopFiche !== null && $scopFiche > 0.0) {
                return $scopFiche;
            }
        }

        $row       = self::SCOP_TABLE[$genId] ?? null;
        if ($row === null) {
            return 2.2;
        }

        $zoneId    = $this->resolveZone($context, $accessor);
        $zoneKey   = ($zoneId === self::ZONE_H3_ID) ? 'h3' : 'h1h2';
        $emType    = $this->typeEmetteur($node, $accessor, $context);

        return (float)($row[$zoneKey][$emType] ?? $row[$zoneKey]['autres'] ?? 2.2);
    }

    /**
     * §12.4.2 p.80 : le SCAP par défaut d'une PAC se lit sur deux colonnes,
     * « Autres » et « Planchers / Plafonds ». La colonne se déduit des
     * émetteurs de l'installation, `enum_type_emission_distribution_id` 6 à 9
     * (rayonnants électriques) et 11 à 18 (planchers et plafonds sur réseau
     * d'eau chaude) désignant un plancher ou un plafond chauffant.
     *
     * Le contexte garde la priorité quand un calculateur amont l'a renseigné.
     */
    private function typeEmetteur(
        DOMElement $node,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): string {
        $depuisContexte = $context->get('installation.emetteur_type');
        if (is_string($depuisContexte) && $depuisContexte !== '') {
            return $depuisContexte;
        }

        $installation = $node->parentNode?->parentNode;
        if (!$installation instanceof DOMElement || $installation->nodeName !== 'installation_chauffage') {
            return 'autres';
        }
        foreach ($installation->getElementsByTagName('emetteur_chauffage') as $emetteur) {
            if (!$emetteur instanceof DOMElement) {
                continue;
            }
            $type = $accessor->getIntOrNull('./donnee_entree/enum_type_emission_distribution_id', $emetteur);
            if ($type !== null && in_array($type, self::EMETTEURS_PLANCHER_PLAFOND, true)) {
                return 'plancher_plafond';
            }
        }

        return 'autres';
    }

    /**
     * Lit la valeur SCOP/COP saisie dans la fiche technique de la catégorie donnée
     * (7 = chauffage, 8 = ECS). Cherche une sous-fiche dont la description contient
     * "SCOP" ou "COP". Retourne null si introuvable / illisible.
     */
    private function readScopFromFicheTechnique(DOMElement $node, int $category): ?float
    {
        $doc = $node->ownerDocument;
        if ($doc === null) {
            return null;
        }
        $xpath = new \DOMXPath($doc);
        // Cible toutes les sous-fiches de la catégorie demandée dont la description
        // mentionne SCOP ou COP.
        $expr = sprintf(
            '//fiche_technique[enum_categorie_fiche_technique_id="%d"]/sous_fiche_technique_collection/sous_fiche_technique[contains(translate(description, "scop", "SCOP"), "SCOP") or contains(translate(description, "cop", "COP"), "COP")]/valeur',
            $category
        );
        $nodes = @$xpath->query($expr);
        if ($nodes === false) {
            return null;
        }
        foreach ($nodes as $n) {
            $raw = trim(str_replace(',', '.', (string)$n->textContent));
            if ($raw === '' || !is_numeric($raw)) {
                continue;
            }
            $v = (float)$raw;
            if ($v > 0.0) {
                return $v;
            }
        }
        return null;
    }

    private function resolveZone(CalculationContext $context, NodeAccessor $accessor): int
    {
        $zoneId = $context->get('logement.zone_climatique_id');
        if ($zoneId !== null) {
            return (int)$zoneId;
        }
        // La zone climatique est publiée sous <meteo> par le schéma ADEME ;
        // `caracteristique_generale` ne la porte que dans d'anciens exports.
        // Ne lire que ce second emplacement faisait retomber sur H1 — et donc
        // sur la colonne SCOP H1/H2 — tous les fichiers au schéma courant.
        $id = $context->zoneClimatique !== null
            ? (int)$context->zoneClimatique
            : ($accessor->getIntOrNull('//meteo/enum_zone_climatique_id')
                ?? $accessor->getIntOrNull('//caracteristique_generale/enum_zone_climatique_id'));
        $context->set('logement.zone_climatique_id', $id);
        return (int)($id ?? 1);
    }
}
