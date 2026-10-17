<?php

declare(strict_types=1);

/**
 * Température extérieure moyenne pendant les périodes de climatisation
 * (°C), par consigne, zone climatique, classe d'altitude et mois.
 *
 * §10.2 p.68 pose le dénominateur du ratio de bilan thermique de froid comme
 * `GV × (Textmoy_clim_j − Tint) × Nref_j` et nomme explicitement cette
 * grandeur : « Textmoy_clim_j : Température extérieure moyenne sur le mois j
 * pendant les périodes de climatisation (°C) ». Les tableaux « Textmoy_clim
 * (°C) », donnés pour Tcons = 26 °C et Tcons = 28 °C, sont la source.
 *
 * Structure : $table[consigne][zone_id][altitude_id][mois] → °C, ou null hors
 * saison de refroidissement (tiret dans la spec).
 *
 * zone_id : 1=H1a, 2=H1b, 3=H1c, 4=H2a, 5=H2b, 6=H2c, 7=H2d, 8=H3
 * altitude_id : 1=≤400m, 2=400-800m, 3=≥800m
 *
 * **Défauts de l'annexe, relevés sur le PDF lui-même** (`pdftotext -layout`
 * sur `resources/spec.pdf`, p.121-143) :
 *  - §18.2.2 (400-800 m) publie pour Tcons = 28 °C exactement le tableau de
 *    Tcons = 26 °C, et son tableau DH28 reprend mot pour mot celui de
 *    §18.2.1 (≤ 400 m). Les deux consignes y sont donc indiscernables.
 *  - à ≤ 400 m, la colonne DH26 de §18.2.1 vaut, au dixième près, le nombre
 *    d'heures Nref26 — par exemple H1a en juillet, Nref26 = 153 et
 *    DH26 = 152,8 — ce qui contredit Textmoy_clim 26 °C = 28,3 °C pour le
 *    même mois : (28,3 − 26) × 153 = 351,9. La colonne DH28 est, elle,
 *    cohérente avec Textmoy_clim 28 °C. C'est la raison d'être de cette
 *    table : §10.2 nomme Textmoy_clim, pas DH.
 *
 * @spec-section 10.2, 18.2
 * @spec-pages   68, 121-143
 * @spec-source  resources/specsplitted/18-annexes/02-sollicitations-exterieures/
 * @generated-on 2026-10-17
 */

return [
    26 => [
        // H1a
        1 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 26.8, 6 => 28.2, 7 => 28.3, 8 => 28.4, 9 => 28.2, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 28.4, 7 => 28.8, 8 => 28.9, 9 => null, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H1b
        2 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 27.3, 6 => 28.3, 7 => 28.9, 8 => 28.9, 9 => 27.3, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.8, 7 => 29.0, 8 => 29.4, 9 => null, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 27.8, 7 => 27.0, 8 => 27.4, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H1c
        3 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 27.7, 6 => 28.9, 7 => 28.8, 8 => 29.0, 9 => 27.4, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 29.3, 6 => 29.5, 7 => 29.2, 8 => 29.4, 9 => null, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 27.3, 6 => 27.5, 7 => 27.2, 8 => 27.4, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H2a
        4 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 28.6, 7 => 28.6, 8 => 28.5, 9 => 28.2, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.5, 7 => 29.1, 8 => 29.0, 9 => 28.7, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H2b
        5 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 27.5, 6 => 28.7, 7 => 28.4, 8 => 28.6, 9 => 28.7, 10 => 27.6, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.6, 7 => 29.3, 8 => 29.6, 9 => 29.9, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H2c
        6 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 27.5, 6 => 28.6, 7 => 29.2, 8 => 30.0, 9 => 29.2, 10 => 28.4, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 28.5, 6 => 30.4, 7 => 29.9, 8 => 31.6, 9 => 31.3, 10 => 28.7, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 26.5, 6 => 28.4, 7 => 27.9, 8 => 29.6, 9 => 29.3, 10 => 26.7, 11 => null, 12 => null],
        ],
        // H2d
        7 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 27.4, 6 => 30.2, 7 => 30.5, 8 => 29.4, 9 => 28.3, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 30.6, 7 => 30.8, 8 => 30.2, 9 => 28.7, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 28.6, 7 => 28.8, 8 => 28.2, 9 => 26.7, 10 => null, 11 => null, 12 => null],
        ],
        // H3
        8 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 27.7, 6 => 28.7, 7 => 29.2, 8 => 28.6, 9 => 28.1, 10 => 26.1, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 28.4, 6 => 29.5, 7 => 29.5, 8 => 29.0, 9 => 28.5, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 26.4, 6 => 27.5, 7 => 27.5, 8 => 27.0, 9 => 26.5, 10 => null, 11 => null, 12 => null],
        ],
    ],
    28 => [
        // H1a
        1 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.2, 7 => 29.7, 8 => 29.8, 9 => 29.3, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 28.4, 7 => 28.8, 8 => 28.9, 9 => null, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H1b
        2 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 28.5, 6 => 29.8, 7 => 29.8, 8 => 30.0, 9 => 28.2, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.8, 7 => 29.0, 8 => 29.4, 9 => null, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.2, 7 => 28.2, 8 => 28.6, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H1c
        3 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 30.5, 6 => 30.0, 7 => 30.2, 8 => 30.2, 9 => 28.9, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 29.3, 6 => 29.5, 7 => 29.2, 8 => 29.4, 9 => null, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 28.4, 6 => 28.7, 7 => 28.4, 8 => 28.9, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H2a
        4 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.6, 7 => 29.8, 8 => 29.8, 9 => 29.6, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.5, 7 => 29.1, 8 => 29.0, 9 => 28.7, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H2b
        5 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 29.2, 6 => 30.4, 7 => 30.5, 8 => 29.9, 9 => 30.9, 10 => 28.4, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.6, 7 => 29.3, 8 => 29.6, 9 => 29.9, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => null, 7 => null, 8 => null, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H2c
        6 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 29.9, 6 => 30.2, 7 => 30.5, 8 => 31.8, 9 => 32.1, 10 => 29.8, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 28.5, 6 => 30.4, 7 => 29.9, 8 => 31.6, 9 => 31.3, 10 => 28.7, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 29.6, 7 => 29.2, 8 => 31.2, 9 => 30.1, 10 => null, 11 => null, 12 => null],
        ],
        // H2d
        7 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 0.0, 6 => 31.3, 7 => 31.7, 8 => 30.8, 9 => 29.5, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 30.6, 7 => 30.8, 8 => 30.2, 9 => 28.7, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 30.2, 7 => 29.9, 8 => 29.4, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
        // H3
        8 => [
            // altitude ≤ 400 m (§18.2.1 p.121-130)
            1 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 29.1, 6 => 30.2, 7 => 30.2, 8 => 29.7, 9 => 29.1, 10 => null, 11 => null, 12 => null],
            // altitude 400 à 800 m (§18.2.2 p.131-140)
            2 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => 28.4, 6 => 29.5, 7 => 29.5, 8 => 29.0, 9 => 28.5, 10 => null, 11 => null, 12 => null],
            // altitude ≥ 800 m (§18.2.3 p.141-143)
            3 => [1 => null, 2 => null, 3 => null, 4 => null, 5 => null, 6 => 28.8, 7 => 28.9, 8 => 28.6, 9 => null, 10 => null, 11 => null, 12 => null],
        ],
    ],
];
