<?php

declare(strict_types=1);

/**
 * Coefficient Ue (W/(m².K)) pour planchers sur vide sanitaire ou sous-sol non chauffé,
 * en fonction de Upb et 2S/P (rapport surface/périmètre, arrondi à l'entier le plus proche).
 *
 * Selon §3.2.2.1 p.18, l'interpolation linéaire est autorisée pour les valeurs intermédiaires
 * de Upb et 2S/P. Pour les valeurs hors tableau on extrapole linéairement entre les deux
 * valeurs les plus proches.
 *
 * @spec-section 3.2.2.1
 * @spec-pages 18
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/02-upb/00-calcul.md
 * @generated-on 2026-04-29
 *
 * Format :
 *   'upb_axis' => list<float>  (axe horizontal du tableau, valeurs de Upb croissantes)
 *   'ratios'   => array<int, list<float>>  (axe vertical 2S/P → ligne de Ue, dans le même ordre que upb_axis)
 */
return [
    'upb_axis' => [0.31, 0.34, 0.37, 0.41, 0.45, 0.83, 1.43, 3.33],
    'ratios' => [
        // 2S/P =>  Ue par Upb
        3  => [0.26, 0.28, 0.30, 0.33, 0.36, 0.39, 0.42, 0.45],
        4  => [0.25, 0.27, 0.29, 0.31, 0.34, 0.37, 0.40, 0.43],
        5  => [0.25, 0.26, 0.28, 0.30, 0.32, 0.34, 0.36, 0.38],
        6  => [0.24, 0.25, 0.27, 0.29, 0.31, 0.33, 0.35, 0.37],
        7  => [0.23, 0.24, 0.26, 0.28, 0.30, 0.32, 0.34, 0.36],
        8  => [0.22, 0.24, 0.25, 0.27, 0.29, 0.31, 0.33, 0.35],
        9  => [0.22, 0.23, 0.24, 0.26, 0.28, 0.30, 0.32, 0.34],
        10 => [0.21, 0.22, 0.24, 0.25, 0.27, 0.29, 0.31, 0.33],
        12 => [0.20, 0.21, 0.22, 0.24, 0.25, 0.26, 0.27, 0.28],
        14 => [0.19, 0.20, 0.21, 0.23, 0.24, 0.26, 0.27, 0.28],
        16 => [0.18, 0.19, 0.20, 0.21, 0.23, 0.25, 0.27, 0.28],
        18 => [0.18, 0.19, 0.19, 0.20, 0.22, 0.24, 0.26, 0.28],
        20 => [0.17, 0.18, 0.19, 0.20, 0.21, 0.22, 0.23, 0.24],
    ],
];
