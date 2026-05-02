<?php

declare(strict_types=1);

namespace CalculDpe\Chauffage\Rendement\Combustion;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
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
 * @depends-on   \CalculDpe\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator
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
        $genId    = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $node);

        // Seules les chaudières gaz/fioul (55-97) sont couvertes
        if ($genId === null || $genId < 55 || $genId > 97) {
            return;
        }

        $regulation = $accessor->getIntOrNull('./donnee_entree/presence_regulation_combustion', $node) === 1;

        // Type de chaudière
        $boilerType = $this->boilerType($genId);

        // Température de distribution de l'émetteur lié
        $tempDistId = $this->resolveEmetteurTempDist($node, $accessor);
        // enum_temp_distribution_ch_id : 1=absent→basse, 2=basse, 3=moyenne, 4=haute
        // On remplace "absent" (1) par "basse" (2) pour les tables Tfonc
        $tempKey = max(2, $tempDistId ?? 3); // défaut: moyenne

        // Période des émetteurs
        $anneeBat = $accessor->getIntOrNull('//caracteristique_generale/annee_construction', $node) ?? 2000;
        $anneeEm  = $this->resolveAnneEmetteur($node, $accessor, $anneeBat);
        $periodeEm = $this->periodeEmetteur($anneeEm);

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

    /** Lit enum_temp_distribution_ch_id depuis le premier emetteur de l'installation parente. */
    private function resolveEmetteurTempDist(DOMElement $node, NodeAccessor $accessor): ?int
    {
        // Remonte : generateur → generateur_collection → installation_chauffage
        $parent = $node->parentNode?->parentNode;
        if (!$parent instanceof DOMElement) {
            return null;
        }
        return $accessor->getIntOrNull(
            './emetteur_chauffage_collection/emetteur_chauffage/donnee_entree/enum_temp_distribution_ch_id',
            $parent,
        );
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
