<?php

declare(strict_types=1);

/**
 * Table forfaitaire Upb_tab utilisée quand l'isolation du plancher bas est inconnue
 * (méthode de saisie U id 7 ou 8 — table forfaitaire selon année / énergie / zone).
 *
 * Indexation : zoneGroupe (H1/H2/H3) → energie (joule/autres) → periode_isolation_id (1..10).
 *
 * Les périodes 1 et 2 (avant 1948 ; 1948-1974) sont regroupées en "≤74 ou inconnu".
 * Les périodes 9 et 10 (2013-2021 ; après 2021) sont regroupées en "≥13".
 *
 * @spec-section 3.2.2.1
 * @spec-pages 17
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/02-upb/00-calcul.md
 * @generated-on 2026-04-29
 */
return [
    'H1' => [
        'joule' => [
            1  => 2.00,  2  => 2.00,         // ≤74 ou inconnu
            3  => 0.90,                       // 75-77
            4  => 0.80,                       // 78-82
            5  => 0.55,                       // 83-88
            6  => 0.55,                       // 89-00
            7  => 0.30,                       // 01-05
            8  => 0.27,                       // 06-12
            9  => 0.23, 10 => 0.23,           // ≥13
        ],
        'autres' => [
            1  => 2.00,  2  => 2.00,
            3  => 0.90,
            4  => 0.90,
            5  => 0.80,
            6  => 0.50,
            7  => 0.30,
            8  => 0.27,
            9  => 0.23, 10 => 0.23,
        ],
    ],
    'H2' => [
        'joule' => [
            1  => 2.00,  2  => 2.00,
            3  => 0.95,
            4  => 0.84,
            5  => 0.58,
            6  => 0.58,
            7  => 0.30,
            8  => 0.27,
            9  => 0.23, 10 => 0.23,
        ],
        'autres' => [
            1  => 2.00,  2  => 2.00,
            3  => 0.95,
            4  => 0.95,
            5  => 0.74,
            6  => 0.63,
            7  => 0.30,
            8  => 0.27,
            9  => 0.23, 10 => 0.23,
        ],
    ],
    'H3' => [
        'joule' => [
            1  => 2.00,  2  => 2.00,
            3  => 1.00,
            4  => 0.89,
            5  => 0.78,
            6  => 0.50,
            7  => 0.47,
            8  => 0.40,
            9  => 0.25, 10 => 0.25,
        ],
        'autres' => [
            1  => 2.00,  2  => 2.00,
            3  => 1.00,
            4  => 0.89,
            5  => 0.78,
            6  => 0.56,
            7  => 0.47,
            8  => 0.40,
            9  => 0.25, 10 => 0.25,
        ],
    ],
];
