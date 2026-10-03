<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage;

/**
 * Normalisation des enum_type_generateur_ch_id > 97 vers leur générateur
 * « équivalent » 1-97 pour les calculs de rendement (§12.4, §13.2).
 *
 * La spec définit les formules pour les familles de base (gaz, fioul, bois,
 * PAC 1-19) ; les enums additionnels de l'XSD (GPL, charbon, PAC hybrides,
 * bouilleurs granulés…) réutilisent les mêmes lignes de table. Le mapping
 * ci-dessous reproduit les groupes d'ids de la table `generateur_combustion`
 * et `scop` d'open3cl (src/tv.js), où chaque ligne liste explicitement les
 * enums équivalents (ex. rpn/rpint identiques pour 96|138|148|160).
 *
 * @spec-section 12.4, 13.2.2
 * @spec-pages   78-80, 86-92
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/02-valeurs-defaut-gaz-fioul.md
 * @tables       chauffage/tv_generateur_combustion (groupes d'enums par ligne)
 */
final class GenerateurChAlias
{
    /**
     * enum CH > 97 → enum équivalent (groupes des tables generateur_combustion/scop).
     */
    private const ALIAS = [
        // Chaudières charbon (120-126) → chaudières bois bûche de même période
        // (open3cl generateur_combustion ids 28-49 : groupes 55|62|120, 56|63|121, …)
        120 => 55, 121 => 56, 122 => 57, 123 => 58, 124 => 59, 125 => 60, 126 => 61,

        // Chaudières GPL/propane/butane (127-139) → chaudières gaz de même
        // période/technologie (groupes 85|127 … 97|139)
        127 => 85, 128 => 86, 129 => 87,             // classique <1981, 81-85, 86-90
        130 => 88, 131 => 89, 132 => 90,             // standard 91-00, 01-15, >2015
        133 => 91, 134 => 92, 135 => 93,             // basse température
        136 => 94, 137 => 95, 138 => 96, 139 => 97,  // condensation

        // Poêles à bois bouilleur granulés (groupes 70|140, 72|141)
        140 => 70, 141 => 72,

        // PAC hybride : partie PAC (groupes scop 4|143, 5|143|145, …)
        143 => 4,
        145 => 5,  146 => 6,  147 => 7,              // air/eau 08-14, 15-16, >2017
        162 => 9,  163 => 10, 164 => 11,             // eau/eau
        165 => 13, 166 => 14, 167 => 15,             // eau glycolée/eau
        168 => 17, 169 => 18, 170 => 19,             // géothermique

        // PAC hybride : partie chaudière (groupes 96|138|148|160, 97|139|149|161, …)
        144 => 96,                                    // générique (supprimé de l'XSD)
        148 => 96, 149 => 97,                         // gaz condensation 01-15, >2015
        150 => 83, 151 => 84,                         // fioul condensation 96-15, >2015
        152 => 73, 153 => 74,                         // bois granulés 13-19, >2019
        154 => 59, 155 => 60, 156 => 61,              // bois bûche 13-17, 18-19, >2019
        157 => 66, 158 => 67, 159 => 68,              // bois plaquette 13-17, 18-19, >2019
        160 => 96, 161 => 97,                         // GPL condensation 01-15, >2015
    ];

    /**
     * « Autre système à combustion » (113-116) → chaudière équivalente selon
     * l'année d'installation (open3cl 13.2_generateur_combustion_chaudiere.js).
     * Seuils = année plancher → enum équivalent ; sans année → période la plus
     * ancienne (pénalisant).
     */
    private const AUTRE_COMBUSTION = [
        113 => [1948 => 85, 1981 => 86, 1986 => 87, 1991 => 88, 2001 => 89, 2015 => 90], // gaz
        114 => [1948 => 75, 1970 => 76, 1976 => 77, 1981 => 78, 1991 => 79, 2015 => 80], // fioul
        115 => [1948 => 55, 1978 => 56, 1995 => 57, 2004 => 58, 2013 => 59, 2018 => 60, 2019 => 61], // bois
        116 => [1948 => 75, 1970 => 76, 1976 => 77, 1981 => 78, 1991 => 79, 2015 => 80], // autres fossiles → fioul
    ];

    /**
     * « Système collectif par défaut en absence d'information : chaudière fioul
     * pénalisante » (enum 119) — §17.2.1.1 p.129 :
     *
     *   « si aucune information n'est communiquée sur les équipements collectifs,
     *     un calcul par défaut se fera avec une chaudière atmosphérique mixte
     *     standard datant de la construction du bâtiment. L'énergie utilisée par
     *     le système sera du fioul. »
     *
     * L'enum 119 ne décrit donc pas une famille de générateur : il renvoie à la
     * chaudière fioul de la période de construction du bâtiment. Seuils = année
     * plancher → enum fioul équivalent ; sans année connue, la période la plus
     * ancienne, conformément au qualificatif « pénalisante » de l'enum.
     */
    /** Enum du système collectif par défaut en absence d'information (§17.2.1.1). */
    public const COLLECTIF_DEFAUT_ID = 119;

    private const COLLECTIF_DEFAUT_FIOUL = [
        1948 => 75, 1970 => 76, 1976 => 77, 1981 => 78, 1991 => 79, 2015 => 80,
    ];

    /**
     * Première année de chaque période de construction (enum_periode_construction_id),
     * utilisée quand `annee_construction` n'est pas renseignée.
     */
    private const PERIODE_CONSTRUCTION_DEBUT = [
        1 => 1900, 2 => 1948, 3 => 1975, 4 => 1978, 5 => 1983,
        6 => 1989, 7 => 2001, 8 => 2006, 9 => 2013, 10 => 2022,
    ];

    /**
     * Retourne l'enum équivalent 1-97 pour les calculs de rendement,
     * ou l'enum inchangé s'il n'a pas d'alias.
     *
     * @param int|null $anneeInstallation Année d'installation si connue —
     *                 utilisée pour les « autres systèmes à combustion » 113-116.
     * @param int|null $anneeConstruction Année de construction du bâtiment —
     *                 utilisée pour le système collectif par défaut (119).
     */
    public static function normalize(
        ?int $genId,
        ?int $anneeInstallation = null,
        ?int $anneeConstruction = null,
    ): ?int {
        if ($genId === null) {
            return null;
        }
        if ($genId === self::COLLECTIF_DEFAUT_ID) {
            return self::parAnnee(self::COLLECTIF_DEFAUT_FIOUL, $anneeConstruction);
        }
        if (isset(self::AUTRE_COMBUSTION[$genId])) {
            return self::parAnnee(self::AUTRE_COMBUSTION[$genId], $anneeInstallation);
        }
        return self::ALIAS[$genId] ?? $genId;
    }

    /**
     * Lit un barème « année plancher → enum » : sans année connue, la période la
     * plus ancienne, qui est aussi la plus pénalisante.
     *
     * @param array<int,int> $bareme
     */
    private static function parAnnee(array $bareme, ?int $annee): int
    {
        $eq = $bareme[array_key_first($bareme)];
        if ($annee === null) {
            return $eq;
        }
        foreach ($bareme as $seuil => $enum) {
            if ($annee >= $seuil) {
                $eq = $enum;
            }
        }
        return $eq;
    }

    /**
     * Normalise l'enum d'un nœud <generateur_chauffage> en lisant, pour les
     * « autres systèmes » 113-116, l'année d'installation portée par
     * data_complementaires[data-annee-installation] (LICIEL).
     */
    public static function normalizeNode(?int $genId, \DOMElement $genNode): ?int
    {
        $xpath = new \DOMXPath($genNode->ownerDocument);
        $annee = null;
        if ($genId !== null && isset(self::AUTRE_COMBUSTION[$genId])) {
            $nodes = $xpath->query('./donnee_entree/data_complementaires', $genNode);
            if ($nodes !== false && $nodes->length > 0) {
                $dc = $nodes->item(0);
                if ($dc instanceof \DOMElement) {
                    $raw = $dc->getAttribute('data-annee-installation');
                    if ($raw !== '' && is_numeric($raw)) {
                        $annee = (int)$raw;
                    }
                }
            }
        }
        $construction = $genId === self::COLLECTIF_DEFAUT_ID
            ? self::anneeConstruction($xpath)
            : null;

        return self::normalize($genId, $annee, $construction);
    }

    /**
     * Année de construction du bâtiment : la valeur saisie si elle existe, sinon
     * la première année de la période déclarée (enum_periode_construction_id).
     */
    private static function anneeConstruction(\DOMXPath $xpath): ?int
    {
        $annee = $xpath->query('//caracteristique_generale/annee_construction');
        if ($annee !== false && $annee->length > 0) {
            $raw = trim($annee->item(0)?->textContent ?? '');
            if ($raw !== '' && is_numeric($raw)) {
                return (int)$raw;
            }
        }
        $periode = $xpath->query('//caracteristique_generale/enum_periode_construction_id');
        if ($periode !== false && $periode->length > 0) {
            $raw = trim($periode->item(0)?->textContent ?? '');
            if ($raw !== '' && is_numeric($raw)) {
                return self::PERIODE_CONSTRUCTION_DEBUT[(int)$raw] ?? null;
            }
        }
        return null;
    }

    /**
     * Vrai si l'enum est une PAC hybride (partie PAC ou partie chaudière) —
     * répartition forfaitaire du besoin §9.1.4.3.
     */
    public static function isHybride(?int $genId): bool
    {
        return $genId !== null && $genId >= 143 && $genId <= 170;
    }

    /**
     * Vrai si l'enum est la partie PAC d'une PAC hybride.
     */
    public static function isHybridePac(?int $genId): bool
    {
        return $genId !== null
            && in_array($genId, [143, 145, 146, 147, 162, 163, 164, 165, 166, 167, 168, 169, 170], true);
    }

    /**
     * Part forfaitaire du besoin de chauffage couverte par chaque partie d'une
     * PAC hybride — §9.1.4.3 p.62 : H1 80/20, H2 83/17, H3 88/12.
     *
     * @return array{pac: float, chaudiere: float}
     */
    public static function prorataHybride(string $zoneGroupe): array
    {
        return match ($zoneGroupe) {
            'H3'    => ['pac' => 0.88, 'chaudiere' => 0.12],
            'H2'    => ['pac' => 0.83, 'chaudiere' => 0.17],
            default => ['pac' => 0.80, 'chaudiere' => 0.20],
        };
    }
}
