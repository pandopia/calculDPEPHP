<?php

declare(strict_types=1);

namespace CalculDpePHP\Intermittence;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Coefficient d'intermittence I0 par émetteur — §8 p.55-57.
 *
 * Formule : INT = I0 / (1 + 0.1 × (G - 1))   avec G = GV / (Hsp × Sh).
 * Seul I0 est calculé ici ; le facteur G/INT est appliqué dans BesoinChauffage.
 *
 * Détermine le type de bâtiment à partir de `enum_methode_application_dpe_log_id` :
 *   1, 14, 18, 25 → 'maison'
 *   IDs "collectif collectif" détectés via enum_equipement_intermittence_id ∈ {6,7}
 *   tous les autres → 'collectif_individuel'
 *
 * Mapping enum_type_emission_distribution_id → catégorie :
 *   air_souffle : 5, 42, 46-50
 *   plafond     : 6, 7, 15-18, 44
 *   plancher    : 8, 9, 11-14, 43
 *   radiateur   : tout le reste
 *
 * @spec-section 8
 * @spec-pages   55-57
 * @spec-source  resources/specsplitted/08-intermittence/00-calcul.md
 * @xml-input    emetteur_chauffage.donnee_entree.{enum_type_chauffage_id, enum_type_regulation_id,
 *               enum_equipement_intermittence_id, enum_type_emission_distribution_id}
 * @xml-input    logement.caracteristique_generale.enum_methode_application_dpe_log_id
 * @xml-output   emetteur_chauffage.donnee_intermediaire.i0
 * @depends-on   \CalculDpePHP\Inertie\InertieCalculator
 * @tables       chauffage/tv_intermittence
 */
final class IntermittenceCalculator implements CalculatorInterface
{
    /** enum_type_emission_distribution_id → catégorie */
    private const AIR_SOUFFLE = [5, 42, 46, 47, 48, 49, 50];
    private const PLAFOND     = [6, 7, 15, 16, 17, 18, 44];
    private const PLANCHER    = [8, 9, 11, 12, 13, 14, 43];

    /** enum_methode_application_dpe_log_id → maison individuelle */
    private const MAISON_IDS = [1, 14, 18, 25];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [\CalculDpePHP\Inertie\InertieCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'emetteur_chauffage';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $de = null;
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                $de = $child;
                break;
            }
        }
        if ($de === null) {
            return;
        }

        $equipementId  = $accessor->getIntOrNull('./enum_equipement_intermittence_id', $de);
        $chauffageId   = $accessor->getIntOrNull('./enum_type_chauffage_id', $de);
        $regulationId  = $accessor->getIntOrNull('./enum_type_regulation_id', $de);
        $emetteurId    = $accessor->getIntOrNull('./enum_type_emission_distribution_id', $de);

        $batimentType  = $this->batimentType($context, $equipementId);
        $chauffageType = $chauffageId === 1 ? 'divise' : 'central';
        $regulation    = $regulationId === 2 ? 'avec' : 'sans';
        $emetteur      = $this->emetteurCategory($emetteurId);
        $inertieKey    = $this->inertieKey($context->get('inertie.classe_id', 1));

        $table = $context->tables->load('chauffage/tv_intermittence');

        $i0 = $this->lookup($table, $batimentType, $chauffageType, $regulation, $emetteur, $inertieKey, $equipementId, $context);

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'i0', $i0);
    }

    private function lookup(
        array $table,
        string $batimentType,
        string $chauffageType,
        string $regulation,
        string $emetteur,
        string $inertieKey,
        ?int $equipementId,
        CalculationContext $context,
    ): float {
        if ($batimentType === 'collectif_collectif') {
            $comptage = $this->comptageKey($context);
            $row = $table[$batimentType][$chauffageType][$regulation][$emetteur][$comptage] ?? null;
            $key = $equipementId;
            if ($row === null || $key === null) {
                return 1.0;
            }
            return (float)($row[$key] ?? $row[1] ?? 1.0);
        }

        $emetteurTable = $table[$batimentType][$chauffageType][$regulation][$emetteur] ?? null;
        if ($emetteurTable === null || $equipementId === null) {
            return 1.0;
        }
        // maison has inertie sub-key; collectif_individuel goes directly to equipementId
        $row = isset($emetteurTable[$inertieKey]) ? $emetteurTable[$inertieKey] : $emetteurTable;
        return (float)($row[$equipementId] ?? $row[1] ?? 1.0);
    }

    private function batimentType(CalculationContext $context, ?int $equipementId): string
    {
        if ($equipementId !== null && in_array($equipementId, [6, 7], true)) {
            return 'collectif_collectif';
        }

        $methodeId = $context->get('logement.methode_application_dpe_log_id');
        if ($methodeId === null) {
            $accessor = new NodeAccessor($context->document);
            $methodeId = $accessor->getIntOrNull('//caracteristique_generale/enum_methode_application_dpe_log_id');
            $context->set('logement.methode_application_dpe_log_id', $methodeId);
        }

        return in_array($methodeId, self::MAISON_IDS, true) ? 'maison' : 'collectif_individuel';
    }

    private function emetteurCategory(?int $id): string
    {
        if ($id === null) {
            return 'radiateur';
        }
        if (in_array($id, self::AIR_SOUFFLE, true)) {
            return 'air_souffle';
        }
        if (in_array($id, self::PLAFOND, true)) {
            return 'plafond';
        }
        if (in_array($id, self::PLANCHER, true)) {
            return 'plancher';
        }
        return 'radiateur';
    }

    private function inertieKey(mixed $classeId): string
    {
        // Mapping XSD : 1=très lourde, 2=lourde, 3=moyenne, 4=légère.
        return ((int)$classeId <= 2) ? 'lourde' : 'legere_moyenne';
    }

    /**
     * Détermine la présence d'un comptage individuel.
     * Détection via fiche_technique[categorie=7]/sous_fiche_technique[description~="comptage"].valeur=1.
     * Voir open3cl 9_emetteur_ch.js (`ficheTechniqueComptage`).
     */
    private function comptageKey(CalculationContext $context): string
    {
        $cached = $context->get('logement.comptage_individuel');
        if ($cached !== null) {
            return (string)$cached;
        }

        $key = 'absent';
        $xpath = new \DOMXPath($context->document);
        $nodes = $xpath->query(
            '//fiche_technique[enum_categorie_fiche_technique_id="7"]'
            . '//sous_fiche_technique[contains(translate(description, "PRÉSENCEC", "présencec"), "comptage")]/valeur'
        );
        if ($nodes !== false) {
            foreach ($nodes as $n) {
                $val = trim($n->textContent);
                if ($val === '1' || strcasecmp($val, 'présence') === 0 || strcasecmp($val, 'present') === 0 || strcasecmp($val, 'présent') === 0) {
                    $key = 'present';
                    break;
                }
            }
        }
        $context->set('logement.comptage_individuel', $key);
        return $key;
    }
}
