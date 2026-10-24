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
 * Seul I0 est calculé ici ; le facteur G/INT est appliqué par les stratégies CH.
 *
 * Détermine le type de bâtiment à partir de `enum_methode_application_dpe_log_id` :
 *   1, 14, 18 → 'maison' ; 25 décrit un appartement RE2020
 *   Une maison raccordée à un réseau collectif reste une maison avec
 *   chauffage individuel central (§8 p.56), pas un immeuble collectif.
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
 * @spec-source  editeur/public/schemas/DPEv2.7.xsd
 * @spec-source  https://gitlab.com/observatoire-dpe/observatoire-dpe/-/blob/05aabba7/modele_donnee/valeur_tables.xlsx (intermittence A190:P229)
 * @xml-input    emetteur_chauffage.donnee_entree.{enum_type_chauffage_id, enum_type_regulation_id,
 *               enum_equipement_intermittence_id, enum_type_emission_distribution_id}
 * @xml-input    logement.caracteristique_generale.enum_methode_application_dpe_log_id
 * @xml-output   emetteur_chauffage.donnee_intermediaire.i0
 * @depends-on   \CalculDpePHP\Inertie\InertieCalculator
 * @tables       chauffage/tv_intermittence, chauffage/tv_intermittence_predictive
 */
final class IntermittenceCalculator implements CalculatorInterface
{
    /** enum_type_emission_distribution_id → catégorie */
    private const AIR_SOUFFLE = [5, 42, 46, 47, 48, 49, 50];
    private const PLAFOND     = [6, 7, 15, 16, 17, 18, 44];
    private const PLANCHER    = [8, 9, 11, 12, 13, 14, 43];

    /** enum_methode_application_dpe_log_id → maison individuelle */
    private const MAISON_IDS = [1, 14, 18];

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

        $batimentType  = $this->batimentType($node, $context, $equipementId, $accessor);
        $installation = $node->parentNode?->parentNode;
        $typeInstallation = $installation instanceof DOMElement
            ? $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $installation)
            : null;
        // §8 p.56 : le raccordement d'une maison à un réseau collectif est
        // traité comme un chauffage individuel central, même si la saisie
        // de l'émetteur le décrit comme divisé.
        $chauffageType = $batimentType === 'maison'
            && in_array($typeInstallation, self::INSTALLATION_COLLECTIVE_IDS, true)
            ? 'central'
            : ($chauffageId === 1 ? 'divise' : 'central');
        $regulation    = $regulationId === 2 ? 'avec' : 'sans';
        $emetteur      = $this->emetteurCategory($emetteurId);
        $inertieKey    = $this->inertieKey($context->get('inertie.classe_id', 1));

        $table = $context->tables->load('chauffage/tv_intermittence');

        $i0 = $equipementId === 8
            ? $this->predictiveI0($batimentType, $chauffageType, $regulation, $emetteur, $inertieKey, $context, $de, $accessor)
            : $this->lookup($table, $batimentType, $chauffageType, $regulation, $emetteur, $inertieKey, $equipementId, $context, $de, $accessor);

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'i0', $i0);
    }

    /**
     * XSD 2.7, équipement 8 ; table ADEME intermittence, lignes 189–228.
     * Aucun repli sur l'équipement « absent » si la combinaison n'est pas publiée.
     *
     * @spec-formula F-8-intermittence-predictive
     */
    private function predictiveI0(string $batiment, string $chauffage, string $regulation, string $emetteur, string $inertie, CalculationContext $context, DOMElement $entree, NodeAccessor $accessor): float
    {
        self::assertPredictiveInput($entree->parentNode, $accessor);
        $table = $context->tables->load('chauffage/tv_intermittence_predictive');
        $value = match ($batiment) {
            'maison' => $regulation === 'avec' ? ($table[$batiment][$chauffage][$inertie][$emetteur] ?? null) : null,
            'collectif_individuel' => $regulation === 'avec' ? ($table[$batiment][$chauffage][$emetteur] ?? null) : null,
            'collectif_collectif' => $chauffage === 'central' ? ($table[$batiment][$regulation][$this->comptageKey($context, $entree, $accessor)][$emetteur] ?? null) : null,
            default => null,
        };
        if ($value === null) {
            throw new \CalculDpePHP\Engine\UnsupportedDpeModelException('Combinaison de régulation prédictive absente des lignes ADEME 189–228 : ' . "$batiment/$chauffage/$regulation/$emetteur");
        }
        return (float)$value;
    }

    /** Prévalidation commune au moteur (avant purge) et au calcul isolé. */
    public static function assertPredictiveInput(DOMElement $node, NodeAccessor $accessor): void
    {
        $version = $accessor->getStringOrNull('//administratif/enum_version_id');
        if ($version === null || version_compare($version, '2.7', '<')) {
            throw new \CalculDpePHP\Engine\UnsupportedDpeModelException('La régulation prédictive requiert le modèle DPE 2.7.');
        }
        $installation = $node->parentNode?->parentNode;
        $type = $installation instanceof DOMElement ? $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $installation) : null;
        if ($type === 4) {
            throw new \CalculDpePHP\Engine\UnsupportedDpeModelException('Régulation prédictive et installation mixte collective-individuelle : périmètre non déterminé, voir TASK-K63.');
        }
        $chauffage = $accessor->getIntOrNull('./donnee_entree/enum_type_chauffage_id', $node);
        $regulation = $accessor->getIntOrNull('./donnee_entree/enum_type_regulation_id', $node);
        $emetteur = $accessor->getIntOrNull('./donnee_entree/enum_type_emission_distribution_id', $node);
        $mode = $accessor->getIntOrNull('//caracteristique_generale/enum_methode_application_dpe_log_id');
        $collectif = !in_array($mode, self::MAISON_IDS, true) && in_array($type, self::INSTALLATION_COLLECTIVE_IDS, true);
        $central = $chauffage === 2 || (in_array($mode, self::MAISON_IDS, true) && in_array($type, self::INSTALLATION_COLLECTIVE_IDS, true));
        if (!in_array($chauffage, [1, 2], true) || !in_array($regulation, [1, 2], true)
            || $emetteur === null || $emetteur < 1 || $emetteur > 50
            || ($central && in_array($emetteur, [19, 20, 21, 22, 23], true))
            || ($collectif ? !$central : $regulation !== 2)) {
            throw new \CalculDpePHP\Engine\UnsupportedDpeModelException('enum_equipement_intermittence_id=8 : combinaison de régulation prédictive absente des lignes ADEME 189–228, voir TASK-K63.');
        }
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
        DOMElement $entree,
        NodeAccessor $accessor,
    ): float {
        if ($batimentType === 'collectif_collectif') {
            $comptage = $this->comptageKey($context, $entree, $accessor);
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

    /**
     * `enum_type_installation_id` dont le chauffage est entièrement collectif :
     * 2 « installation collective » et 3 « collective multi-bâtiment modélisée
     * comme un réseau de chaleur ». La valeur 1 est individuelle ; la 4,
     * « hybride collective-individuelle », mêle les deux et se tranche alors
     * émetteur par émetteur sur `enum_equipement_intermittence_id`.
     */
    private const INSTALLATION_COLLECTIVE_IDS = [2, 3];

    private function batimentType(
        DOMElement $node,
        CalculationContext $context,
        ?int $equipementId,
        NodeAccessor $accessor,
    ): string
    {
        $methodeId = $context->get('logement.methode_application_dpe_log_id');
        if ($methodeId === null) {
            $methodeId = $accessor->getIntOrNull('//caracteristique_generale/enum_methode_application_dpe_log_id');
            $context->set('logement.methode_application_dpe_log_id', $methodeId);
        }
        // §8 p.56 : une fourniture collective ne change pas le type de bâti.
        if (in_array($methodeId, self::MAISON_IDS, true)) {
            return 'maison';
        }

        // §8 p.57 : la table « immeuble collectif / chauffage collectif » est
        // déterminée par l'installation commune, même lorsque l'équipement
        // d'intermittence est codé « absent » (ID 1) dans l'export ADEME.
        //
        // `enum_type_installation_id` 3, « collective multi-bâtiment modélisée
        // comme un réseau de chaleur », y ouvre droit au même titre que 2 : la
        // table officielle range les deux sous « immeuble collectif chauffage
        // collectif ». La 4, hybride, se tranche émetteur par émetteur.
        $installation = $node->parentNode?->parentNode;
        if ($installation instanceof DOMElement
            && in_array(
                $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $installation),
                self::INSTALLATION_COLLECTIVE_IDS,
                true,
            )
        ) {
            return 'collectif_collectif';
        }

        if ($equipementId !== null && in_array($equipementId, [6, 7], true)) {
            return 'collectif_collectif';
        }

        return 'collectif_individuel';
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
    private function comptageKey(CalculationContext $context, DOMElement $entree, NodeAccessor $accessor): string
    {
        // L'identifiant tabulé est une donnée d'entrée qui inclut le comptage.
        // Les XML historiques peuvent omettre la fiche technique. Valider les
        // autres critères évite d'utiliser une ligne devenue incohérente après
        // modification de l'émetteur. La résolution reste propre à cet émetteur.
        $tvId = $accessor->getIntOrNull('./tv_intermittence_id', $entree);
        $table = $context->tables->load('chauffage/tv_intermittence_comptage');
        $row = $tvId !== null ? ($table[$tvId] ?? null) : null;
        if ($row !== null) {
            $matches = true;
            foreach (['enum_type_chauffage_id', 'enum_equipement_intermittence_id', 'enum_type_regulation_id', 'enum_type_emission_distribution_id'] as $field) {
                $value = $accessor->getIntOrNull('./' . $field, $entree);
                $matches = $matches && $value !== null && in_array($value, $row[$field], true);
            }
            if ($matches) { return $row['comptage']; }
        }

        $cached = $context->get('logement.comptage_individuel');
        if ($cached !== null) {
            return (string)$cached;
        }

        $xpath = new \DOMXPath($context->document);
        $mode = (int)$xpath->evaluate('string(//caracteristique_generale/enum_methode_application_dpe_log_id)');
        // Dans les méthodes mixtes, la part collective est nécessairement
        // individualisée pour être répartie avec ratio_virtualisation (§17.2).
        $key = in_array($mode, [26, 27, 28, 31, 32, 33, 34, 35, 38], true)
            ? 'present'
            : 'absent';
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
        return $key;
    }
}
