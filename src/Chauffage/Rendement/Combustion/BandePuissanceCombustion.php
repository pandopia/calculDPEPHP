<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement\Combustion;

/**
 * Bande de puissance des chaudières (§13.2.2 p.88-90).
 *
 * Pour ces familles, la table de la spec n'est pas indexée par le seul couple
 * type × ancienneté : elle porte trois lignes départagées par la puissance
 * nominale — 70 et 400 kW, avec un seuil de 20 kW supplémentaire pour le
 * bois après 2019 —, chacune avec ses formules de Rpn, Rpint et QP0.
 *
 * `tv_generateur_combustion_id` encode cette bande, mais il est saisi par le
 * logiciel diagnostiqueur avant que Pn ne soit calculé : sur une chaudière
 * collective dimensionnée à 400 kW, le corpus le voit rester sur la ligne des
 * petites puissances. La bande se déduit donc de Pn, conformément au tableau.
 *
 * @spec-section 13.2.2
 * @spec-pages   88-90
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/02-valeurs-defaut-gaz-fioul.md
 */
final class BandePuissanceCombustion
{
    /**
     * Retrouve la ligne digitalisée depuis les caractéristiques physiques.
     *
     * §13.2.2 p.86–90 : les enums CH 50–97 décrivent intégralement la
     * technologie et l'époque. L'index renvoyé adresse notre table de
     * formules, jamais une correspondance inférée d'un ID externe absent.
     * Les aliases sont normalisés par GenerateurChAlias avant cet appel.
     * Le domaine 50–97 est exhaustif ; les poêles directs relèvent de §13.1.
     */
    public static function depuisType(int $type, float $pnKw): ?int
    {
        if ($type >= 50 && $type <= 52) {
            // §13.2.2.1 p.87 : QP0 est figé à sa valeur de 300 kW au-delà.
            return 69 + 2 * ($type - 50) + ($pnKw > 300.0 ? 1 : 0);
        }
        if ($type === 53 || $type === 54) {
            // §13.2.2.2 p.88 : la seconde valeur s'applique dès Pn≥5 kW.
            return 75 + 2 * ($type - 53) + ($pnKw >= 5.0 ? 1 : 0);
        }
        $first = match (true) {
            $type >= 55 && $type <= 61 => 28 + 3 * ($type - 55), // bûches
            $type >= 62 && $type <= 68 => 28 + 3 * ($type - 62), // plaquettes
            $type >= 69 && $type <= 74 => 50 + 3 * ($type - 69), // granulés
            $type >= 75 && $type <= 84 => $type - 59, // fioul
            $type >= 85 && $type <= 97 => $type - 84, // gaz
            default => null,
        };
        return $first === null ? null : self::pourPuissance($first, $pnKw);
    }

    /**
     * Familles à trois bandes : [id Pn ≤ 70, id 70 < Pn ≤ 400, id Pn > 400].
     *
     * @var list<array{int, int, int}>
     */
    private const FAMILLES = [
        [13, 14, 15],  // chaudière gaz à condensation à partir de 2016
        [25, 26, 27],  // chaudière fioul à condensation après 2015
        [28, 29, 30],  // bois bûches/plaquettes avant 1978 — p.89
        [31, 32, 33],  // bois bûches/plaquettes 1978-1994 — p.89
        [34, 35, 36],  // bois bûches/plaquettes 1995-2003 — p.89
        [37, 38, 39],  // bois bûches/plaquettes 2004-2012 — p.89
        [40, 41, 42],  // bois bûches/plaquettes 2013-2017 — p.89
        [43, 44, 45],  // bois bûches/plaquettes 2018-2019 — p.89
        [50, 51, 52],  // granulés avant 1978 — p.89
        [53, 54, 55],  // granulés 1978-1994 — p.89
        [56, 57, 58],  // granulés 1995-2003 — p.89
        [59, 60, 61],  // granulés 2004-2012 — p.89-90
        [62, 63, 64],  // granulés 2013-2019 — p.90
    ];

    /** @var list<array{int, int, int, int}> Seuils 20, 70 et 400 kW. */
    private const FAMILLES_QUATRE_BANDES = [
        [46, 47, 48, 49], // bois bûches/plaquettes après 2019 — p.89
        [65, 66, 67, 68], // granulés après 2019 — p.90
        [94, 95, 96, 97], // granulés condensation après 2024 — ADEME 05aabba7, lignes 95-98
    ];

    /**
     * Renvoie l'identifiant de la ligne correspondant à la puissance calculée,
     * ou l'identifiant reçu lorsqu'il n'appartient pas à une famille à bandes.
     */
    public static function pourPuissance(int $tvId, float $pnKw): int
    {
        foreach (self::FAMILLES_QUATRE_BANDES as $bandes) {
            if (!in_array($tvId, $bandes, true)) {
                continue;
            }
            if ($pnKw <= 20.0) {
                return $bandes[0];
            }
            if ($pnKw <= 70.0) {
                return $bandes[1];
            }
            return $pnKw <= 400.0 ? $bandes[2] : $bandes[3];
        }
        foreach (self::FAMILLES as $bandes) {
            if (!in_array($tvId, $bandes, true)) {
                continue;
            }
            if ($pnKw <= 70.0) {
                return $bandes[0];
            }

            return $pnKw <= 400.0 ? $bandes[1] : $bandes[2];
        }

        return $tvId;
    }
}
