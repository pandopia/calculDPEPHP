<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement\Combustion;

/**
 * Bande de puissance des chaudières à condensation récentes (§13.2.2 p.88).
 *
 * Pour ces familles, la table de la spec n'est pas indexée par le seul couple
 * type × ancienneté : elle porte trois lignes départagées par la puissance
 * nominale — Pn ≤ 70 kW, 70 < Pn ≤ 400 kW, puis Pn > 400 kW —, chacune avec ses
 * formules de Rpn, Rpint et QP0.
 *
 * `tv_generateur_combustion_id` encode cette bande, mais il est saisi par le
 * logiciel diagnostiqueur avant que Pn ne soit calculé : sur une chaudière
 * collective dimensionnée à 400 kW, le corpus le voit rester sur la ligne des
 * petites puissances. La bande se déduit donc de Pn, conformément au tableau.
 *
 * @spec-section 13.2.2
 * @spec-pages   88
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/02-valeurs-defaut-gaz-fioul.md
 */
final class BandePuissanceCombustion
{
    /**
     * Familles à trois bandes : [id Pn ≤ 70, id 70 < Pn ≤ 400, id Pn > 400].
     *
     * @var list<array{int, int, int}>
     */
    private const FAMILLES = [
        [13, 14, 15],  // chaudière gaz à condensation à partir de 2016
        [25, 26, 27],  // chaudière fioul à condensation après 2015
    ];

    /**
     * Renvoie l'identifiant de la ligne correspondant à la puissance calculée,
     * ou l'identifiant reçu lorsqu'il n'appartient pas à une famille à bandes.
     */
    public static function pourPuissance(int $tvId, float $pnKw): int
    {
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
