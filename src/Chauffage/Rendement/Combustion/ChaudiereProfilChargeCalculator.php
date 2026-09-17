<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement\Combustion;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Températures de fonctionnement (Tfonc_100, Tfonc_30) des chaudières — §13.2.1.5/13.2.1.6 p.81-85.
 *
 * Ces températures dépendent du type de chaudière (condensation/BT/standard), du type
 * d'émetteur (basse/moyenne/haute température) et de l'année d'installation des émetteurs.
 *
 * Le type d'émetteur est lu depuis le premier emetteur_chauffage de l'installation
 * parente via XPath `../../emetteur_chauffage_collection/emetteur_chauffage`.
 *
 * @spec-section 13.2.1
 * @spec-pages   81-85
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/01-profil-charge.md
 * @xml-input    generateur_chauffage.donnee_entree.{enum_type_generateur_ch_id, presence_regulation_combustion}
 * @xml-input    emetteur_chauffage.donnee_entree.{enum_temp_distribution_ch_id, annee_installation_emetteur}
 * @xml-output   generateur_chauffage.donnee_intermediaire.{temp_fonc_100, temp_fonc_30}
 * @depends-on   \CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator
 * @tables       (aucune — tables inline §13.2.1.5 p.81-85)
 */
final class ChaudiereProfilChargeCalculator implements CalculatorInterface
{
    // --- Catégories de chaudière déterminées par enum_type_generateur_ch_id ---
    /** Chaudières gaz à condensation */
    private const COND_GAZ_IDS = [94, 95, 96, 97];
    /** Chaudières fioul à condensation */
    private const COND_FIOUL_IDS = [83, 84];
    /** Chaudières gaz basse température */
    private const BT_GAZ_IDS = [91, 92, 93];
    /** Chaudières fioul basse température */
    private const BT_FIOUL_IDS = [81, 82];

    // --- Tables Tfonc_100 (§13.2.1.5 p.81) ---
    // Format : [type_boiler => [période_émetteur => [temp_dist => Tfonc_100]]]
    //   type_boiler : 'condensation' | 'bt' | 'standard'
    //   periode     : 'avant_1981' | '1981_2000' | 'apres_2000'
    //   temp_dist   : 1=basse | 2..3=moyenne | 4=haute
    //                (enum_temp_distribution_ch_id : 1=absent,2=basse,3=moyenne,4=haute)
    private const TFONC_100 = [
        'condensation' => [
            'avant_1981'  => [1 => 60, 2 => 60, 3 => 80, 4 => 80],
            '1981_2000'   => [1 => 35, 2 => 35, 3 => 70, 4 => 70],
            'apres_2000'  => [1 => 35, 2 => 35, 3 => 60, 4 => 70],
        ],
        'bt' => [
            'avant_1981'  => [1 => 60, 2 => 60, 3 => 80, 4 => 80],
            '1981_2000'   => [1 => 35, 2 => 35, 3 => 70, 4 => 70],
            'apres_2000'  => [1 => 35, 2 => 35, 3 => 60, 4 => 70],
        ],
        'standard' => [
            'avant_1981'  => [1 => 60, 2 => 60, 3 => 80, 4 => 80],
            '1981_2000'   => [1 => 35, 2 => 35, 3 => 70, 4 => 70],
            'apres_2000'  => [1 => 35, 2 => 35, 3 => 60, 4 => 70],
        ],
    ];

    // --- Tables Tfonc_30 (§13.2.1.5 p.82-85) ---
    // condensation, avec régulation
    private const TFONC_30_COND_AVEC = [
        'avant_1981'  => [1 => 32.0, 2 => 32.0, 3 => 38.0, 4 => 38.0],
        '1981_2000'   => [1 => 24.5, 2 => 24.5, 3 => 35.0, 4 => 35.0],
        'apres_2000'  => [1 => 24.5, 2 => 24.5, 3 => 32.0, 4 => 35.0],
    ];
    // condensation, sans régulation
    private const TFONC_30_COND_SANS = [
        'avant_1981'  => [1 => 32.0, 2 => 32.0, 3 => 38.0, 4 => 38.0],
        '1981_2000'   => [1 => 24.5, 2 => 24.5, 3 => 35.0, 4 => 35.0],
        'apres_2000'  => [1 => 24.5, 2 => 24.5, 3 => 32.0, 4 => 35.0],
    ];
    // basse température, avec régulation
    private const TFONC_30_BT_AVEC = [
        'avant_1981'  => [1 => 42.5, 2 => 42.5, 3 => 48.5, 4 => 48.5],
        '1981_2000'   => [1 => 35.0, 2 => 35.0, 3 => 45.5, 4 => 45.5],
        'apres_2000'  => [1 => 35.0, 2 => 35.0, 3 => 42.5, 4 => 45.5],
    ];
    // basse température, sans régulation (même valeurs — Tfonc_30 est basé sur Tfonc_100)
    private const TFONC_30_BT_SANS = [
        'avant_1981'  => [1 => 42.5, 2 => 42.5, 3 => 48.5, 4 => 48.5],
        '1981_2000'   => [1 => 35.0, 2 => 35.0, 3 => 45.5, 4 => 45.5],
        'apres_2000'  => [1 => 35.0, 2 => 35.0, 3 => 42.5, 4 => 45.5],
    ];
    // standard avant 1990, avec ou sans régulation
    private const TFONC_30_STD_AVANT90 = [
        'avant_1981'  => [1 => 53.0, 2 => 53.0, 3 => 59.0, 4 => 59.0],
        '1981_2000'   => [1 => 50.0, 2 => 50.0, 3 => 56.0, 4 => 56.0],
        'apres_2000'  => [1 => 50.0, 2 => 50.0, 3 => 53.0, 4 => 56.0],
    ];
    // standard depuis 1991
    private const TFONC_30_STD_DEPUIS91 = [
        'avant_1981'  => [1 => 49.5, 2 => 49.5, 3 => 55.5, 4 => 55.5],
        '1981_2000'   => [1 => 45.0, 2 => 45.0, 3 => 52.5, 4 => 52.5],
        'apres_2000'  => [1 => 45.0, 2 => 45.0, 3 => 49.5, 4 => 52.5],
    ];

    /** Chaudières gaz classiques avant 1990 (standard "avant 1990") */
    private const CLASSIQUE_AVANT90 = [85, 86, 87, 75, 76, 77, 78];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [ChaudiereDefautCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'generateur_chauffage';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        // Normalisation GPL/charbon/hybride-chaudière → générateur équivalent (§13.2.1.5)
        $genId    = \CalculDpePHP\Chauffage\GenerateurChAlias::normalizeNode(
            $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $node),
            $node,
        );

        // Seules les chaudières gaz/fioul (55-97) sont couvertes
        if ($genId === null || $genId < 55 || $genId > 97) {
            return;
        }

        $regulation = $accessor->getIntOrNull('./donnee_entree/presence_regulation_combustion', $node) === 1;

        // Type de chaudière
        $boilerType = $this->boilerType($genId);

        // Ligne du tableau Tfonc, lue sur la nature des émetteurs desservis
        $tempKey = $this->resolveLigneTfonc($node, $accessor);

        // Période des émetteurs : on lit d'abord l'enum dédié, puis on retombe sur l'année
        $periodeEm = $this->resolvePeriodeEmetteur($node, $accessor);

        [$tfonc100, $tfonc30] = $this->computeTemps($boilerType, $periodeEm, $tempKey, $regulation, $genId);

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'temp_fonc_100', $tfonc100);
        $accessor->setChildValue($di, 'temp_fonc_30',  $tfonc30);
    }

    private function boilerType(int $genId): string
    {
        if (in_array($genId, self::COND_GAZ_IDS, true) || in_array($genId, self::COND_FIOUL_IDS, true)) {
            return 'condensation';
        }
        if (in_array($genId, self::BT_GAZ_IDS, true) || in_array($genId, self::BT_FIOUL_IDS, true)) {
            return 'bt';
        }
        return 'standard';
    }

    /**
     * Planchers et plafonds chauffants sur réseau d'eau chaude basse ou moyenne
     * température, et leurs équivalents à détente directe : ligne « Basse /
     * Plancher ou plafond basse température ».
     */
    private const EMETTEURS_BASSE = [12, 14, 16, 18, 43, 44];

    /**
     * Radiateurs et ventilo-convecteurs sur réseau d'eau chaude basse ou
     * moyenne température (< 65 °C) : ligne « Moyenne / Radiateur à chaleur
     * douce ». Leurs homologues sur réseau haute température (≥ 65 °C) tombent
     * dans « Autres émetteurs ».
     */
    private const EMETTEURS_MOYENNE = [25, 27, 29, 31, 33, 35, 37, 39, 45, 47, 49];

    /**
     * Ligne du tableau Tfonc (§13.2.1.5 p.81), indexée comme les tables
     * ci-dessus : 2 = basse, 3 = moyenne, 4 = haute.
     *
     * Le tableau de la spec porte sur « Température de distribution / Type
     * d'émetteur » : la ligne basse est celle des planchers et plafonds basse
     * température, la ligne moyenne celle des radiateurs à chaleur douce, et
     * tout le reste relève d'« Autres émetteurs ». Lire le seul
     * enum_temp_distribution_ch_id classait en basse un radiateur bitube posé
     * sur un réseau à moins de 65 °C, qui est un radiateur à chaleur douce.
     *
     * « Si un système de génération alimente des réseaux de distribution de
     * températures différentes, la température de fonctionnement est prise
     * égale à la température maximale » : tous les émetteurs de l'installation
     * sont donc parcourus et le maximum retenu.
     */
    private function resolveLigneTfonc(DOMElement $node, NodeAccessor $accessor): int
    {
        // Remonte : generateur → generateur_collection → installation_chauffage
        $parent = $node->parentNode?->parentNode;
        if (!$parent instanceof DOMElement) {
            return 3;
        }

        $doc = $node->ownerDocument;
        if ($doc === null) {
            return 3;
        }
        $emetteurs = (new \DOMXPath($doc))
            ->query('./emetteur_chauffage_collection/emetteur_chauffage', $parent);
        if ($emetteurs === false || $emetteurs->length === 0) {
            return 3;
        }

        $ligne = null;
        foreach ($emetteurs as $emetteur) {
            if (!$emetteur instanceof DOMElement) {
                continue;
            }
            $type = $accessor->getIntOrNull('./donnee_entree/enum_type_emission_distribution_id', $emetteur);
            if ($type !== null) {
                $courante = match (true) {
                    in_array($type, self::EMETTEURS_BASSE, true)   => 2,
                    in_array($type, self::EMETTEURS_MOYENNE, true) => 3,
                    default                                        => 4,
                };
            } else {
                // Repli : enum_temp_distribution_ch_id (1=absent, 2=basse, 3=moyenne, 4=haute)
                $courante = max(2, $accessor->getIntOrNull('./donnee_entree/enum_temp_distribution_ch_id', $emetteur) ?? 3);
            }
            $ligne = $ligne === null ? $courante : max($ligne, $courante);
        }

        return $ligne ?? 3;
    }

    /**
     * Résout la période d'installation des émetteurs.
     * Lit en priorité `enum_periode_installation_emetteur_id` (1=avant 1981, 2=1981-2000, 3=après 2000),
     * sinon retombe sur `annee_installation_emetteur`. Les exports ADEME où
     * les deux champs sont absents utilisent conventionnellement la période
     * récente ; l'année de construction ne sert de repli que lorsque le bloc
     * émetteur est lui-même indisponible.
     */
    private function resolvePeriodeEmetteur(DOMElement $node, NodeAccessor $accessor): string
    {
        $parent = $node->parentNode?->parentNode;
        if ($parent instanceof DOMElement) {
            $periodeId = $accessor->getIntOrNull(
                './emetteur_chauffage_collection/emetteur_chauffage/donnee_entree/enum_periode_installation_emetteur_id',
                $parent,
            );
            if ($periodeId !== null) {
                return match ($periodeId) {
                    1 => 'avant_1981',
                    2 => '1981_2000',
                    default => 'apres_2000',
                };
            }

            $anneeEmetteur = $accessor->getIntOrNull(
                './emetteur_chauffage_collection/emetteur_chauffage/donnee_entree/annee_installation_emetteur',
                $parent,
            );
            if ($anneeEmetteur !== null) {
                return $this->periodeEmetteur($anneeEmetteur);
            }

            // Compatibilité avec la valeur par défaut des exports ADEME 2.6
            // lorsque l'enum de période optionnel n'est pas sérialisé.
            return 'apres_2000';
        }

        $anneeBat = $accessor->getIntOrNull('//caracteristique_generale/annee_construction', $node) ?? 2000;
        $anneeEm  = $this->resolveAnneEmetteur($node, $accessor, $anneeBat);
        return $this->periodeEmetteur($anneeEm);
    }

    /** Lit l'année d'installation des émetteurs depuis le premier emetteur de l'installation parente. */
    private function resolveAnneEmetteur(DOMElement $node, NodeAccessor $accessor, int $fallback): int
    {
        $parent = $node->parentNode?->parentNode;
        if (!$parent instanceof DOMElement) {
            return $fallback;
        }
        $annee = $accessor->getIntOrNull(
            './emetteur_chauffage_collection/emetteur_chauffage/donnee_entree/annee_installation_emetteur',
            $parent,
        );
        return $annee ?? $fallback;
    }

    private function periodeEmetteur(int $annee): string
    {
        if ($annee < 1981) {
            return 'avant_1981';
        }
        if ($annee <= 2000) {
            return '1981_2000';
        }
        return 'apres_2000';
    }

    /** @return array{float, float} [Tfonc_100, Tfonc_30] */
    private function computeTemps(
        string $boilerType,
        string $periodeEm,
        int $tempKey,
        bool $regulation,
        int $genId,
    ): array {
        $tfonc100 = (float)(self::TFONC_100[$boilerType][$periodeEm][$tempKey] ?? 70.0);

        $tfonc30 = match ($boilerType) {
            'condensation' => $regulation
                ? (float)(self::TFONC_30_COND_AVEC[$periodeEm][$tempKey] ?? 35.0)
                : (float)(self::TFONC_30_COND_SANS[$periodeEm][$tempKey] ?? 35.0),
            'bt' => $regulation
                ? (float)(self::TFONC_30_BT_AVEC[$periodeEm][$tempKey] ?? 45.5)
                : (float)(self::TFONC_30_BT_SANS[$periodeEm][$tempKey] ?? 45.5),
            default => in_array($genId, self::CLASSIQUE_AVANT90, true)
                ? (float)(self::TFONC_30_STD_AVANT90[$periodeEm][$tempKey] ?? 56.0)
                : (float)(self::TFONC_30_STD_DEPUIS91[$periodeEm][$tempKey] ?? 52.5),
        };

        return [$tfonc100, $tfonc30];
    }
}
