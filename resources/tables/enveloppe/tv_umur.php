<?php

declare(strict_types=1);

/**
 * Table forfaitaire Umur_tab — §3.2.1.1 p.13.
 *
 * Utilisée quand l'isolation du mur est inconnue (méthode 2) ou connue seulement
 * par année (méthodes 7, 8). Umur final = min(Umur_nu, Umur_tab).
 *
 * Indexation : zoneGroupe (H1/H2/H3) → energie (joule/autres) → periode_id (1..10).
 *
 * Périodes groupées :
 *   1+2 → ≤74 ou inconnu
 *   9+10 → ≥13
 *
 * Règle "méthode 8" (année construction inconnue ou ≤74) :
 *   Si enum_periode_construction_id ≤ 2 → utiliser la période 3 (75-77) comme année
 *   d'isolation conventionnelle. Sinon → utiliser enum_periode_construction_id.
 *   Cette règle est appliquée dans UmurCalculator, pas dans cette table.
 *
 * Note : pour la période 83-88, les colonnes joule/autres sont identiques pour H1
 * (les réglementations RT82 ne distinguaient pas EJ/autres en zone H1). Pour H2/H3,
 * l'effet joule avait des exigences plus strictes dès RT82.
 *
 * @spec-section 3.2.1.1
 * @spec-pages 13
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/01-umur/00-calcul.md
 * @generated-on 2026-04-29
 * @status verified-spec — valeurs lues depuis le tableau p.13 ; non validées sur exemples
 *         (les 4 fichiers de test n'utilisent pas les méthodes 2/7/8 pour les murs).
 */
return [
    'H1' => [
        'joule' => [
            1 => 2.50,  2 => 2.50,          // ≤74 ou inconnu
            3 => 1.00,                       // 75-77
            4 => 0.80,                       // 78-82
            5 => 0.70,                       // 83-88
            6 => 0.45,                       // 89-00
            7 => 0.40,                       // 01-05
            8 => 0.36,                       // 06-12
            9 => 0.23, 10 => 0.23,           // ≥13
        ],
        'autres' => [
            1 => 2.50,  2 => 2.50,
            3 => 1.00,
            4 => 1.00,                       // RT74 autres : exigence moins stricte qu'effet joule
            5 => 0.80,                       // RT82 autres H1
            6 => 0.50,
            7 => 0.40,
            8 => 0.36,
            9 => 0.23, 10 => 0.23,
        ],
    ],
    'H2' => [
        'joule' => [
            1 => 2.50,  2 => 2.50,
            3 => 1.05,
            4 => 0.84,
            5 => 0.74,                       // H2 RT82 : EJ plus strict
            6 => 0.47,
            7 => 0.40,
            8 => 0.36,
            9 => 0.23, 10 => 0.23,
        ],
        'autres' => [
            1 => 2.50,  2 => 2.50,
            3 => 1.05,
            4 => 1.05,                       // RT74 autres : exigence moins stricte qu'effet joule
            5 => 0.84,                       // RT82 autres H2
            6 => 0.53,
            7 => 0.40,
            8 => 0.36,
            9 => 0.23, 10 => 0.23,
        ],
    ],
    'H3' => [
        'joule' => [
            1 => 2.50,  2 => 2.50,
            3 => 1.11,
            4 => 0.89,
            5 => 0.78,                       // H3 RT82 : EJ plus strict
            6 => 0.50,
            7 => 0.47,
            8 => 0.40,
            9 => 0.25, 10 => 0.25,
        ],
        'autres' => [
            1 => 2.50,  2 => 2.50,
            3 => 1.11,
            4 => 1.11,                       // RT74 autres : exigence moins stricte qu'effet joule
            5 => 0.89,                       // RT82 autres H3
            6 => 0.56,
            7 => 0.47,
            8 => 0.40,
            9 => 0.25, 10 => 0.25,
        ],
    ],
];
