<?php

declare(strict_types=1);

/**
 * Coefficient Ug (vitrage seul) en W/(m².K) — §3.3.1 p.23-25.
 *
 * Digitalisation **complète** des tableaux de la spec.
 *
 * Indexation : `[type_vitrage][orientation][gaz][traitement][épaisseur_lame]`
 *   - type_vitrage : 'simple', 'double', 'triple', 'survitrage'
 *   - orientation  : 'vertical', 'horizontal' (cf. §3.3 : ≥75° = vertical, <75° = horizontal)
 *   - gaz          : 'air', 'argon'  (« argon ou krypton » dans la spec)
 *   - traitement   : 'standard' (vitrage non traité), 'vir' (vitrage faiblement émissif)
 *   - épaisseur_lame : entier en mm
 *
 * Cas spéciaux non indexés par cette grille :
 *   - 'brique_verre_pleine'   → Uw direct 3.5 (cf. §3.3, traité comme vitrage)
 *   - 'brique_verre_creuse'   → Uw direct 2.0
 *   - 'polycarbonate'         → Uw direct 3.0
 *
 * Pour le **survitrage**, la spec dit : Ug = Ug_double_air + 0.1, lame plafonnée à 20 mm.
 * Implémenté côté Calculator (pas dupliqué dans cette table).
 *
 * Si l'épaisseur de lame n'est pas tabulée, prendre la valeur **directement inférieure**
 * (cf. spec, encadré attention p.24).
 *
 * @spec-section 3.3.1
 * @spec-pages 23-25
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/03-parois-vitrees-portes/01-ug/00-calcul.md
 * @generated-on 2026-04-29
 */
return [

    // §3.3.1 — Simple vitrage et survitrage
    // « Ug = 5.8 quelle que soit l'épaisseur du verre, vitrage vertical ou horizontal »
    'simple' => [
        'vertical'   => 5.80,
        'horizontal' => 5.80,
    ],

    // Double vitrage vertical (p.24 haut)
    'double' => [
        'vertical' => [
            'air' => [
                'standard' => [6 => 3.30, 8 => 3.10, 10 => 2.90, 12 => 2.80, 14 => 2.80, 15 => 2.70, 16 => 2.70, 18 => 2.70, 20 => 2.70],
                'vir'      => [6 => 2.45, 8 => 2.10, 10 => 1.80, 12 => 1.60, 14 => 1.50, 15 => 1.40, 16 => 1.40, 18 => 1.40, 20 => 1.40],
            ],
            'argon' => [
                'standard' => [6 => 3.00, 8 => 2.90, 10 => 2.80, 12 => 2.70, 14 => 2.60, 15 => 2.60, 16 => 2.60, 18 => 2.60, 20 => 2.60],
                'vir'      => [6 => 2.00, 8 => 1.70, 10 => 1.40, 12 => 1.30, 14 => 1.20, 15 => 1.10, 16 => 1.10, 18 => 1.10, 20 => 1.10],
            ],
        ],
        // Double vitrage horizontal (p.24 bas)
        'horizontal' => [
            'air' => [
                'standard' => [6 => 3.70, 8 => 3.40, 10 => 3.20, 12 => 3.10, 14 => 3.10, 15 => 2.90, 16 => 2.90, 18 => 2.90, 20 => 2.90],
                'vir'      => [6 => 2.60, 8 => 2.20, 10 => 1.90, 12 => 1.70, 14 => 1.60, 15 => 1.50, 16 => 1.50, 18 => 1.50, 20 => 1.50],
            ],
            'argon' => [
                'standard' => [6 => 3.30, 8 => 3.20, 10 => 3.10, 12 => 2.90, 14 => 2.80, 15 => 2.80, 16 => 2.80, 18 => 2.80, 20 => 2.80],
                'vir'      => [6 => 2.10, 8 => 1.80, 10 => 1.50, 12 => 1.40, 14 => 1.20, 15 => 1.10, 16 => 1.10, 18 => 1.10, 20 => 1.10],
            ],
        ],
    ],

    // Triple vitrage vertical (p.25 haut)
    'triple' => [
        'vertical' => [
            'air' => [
                'standard' => [6 => 2.30, 8 => 2.10, 10 => 2.00, 12 => 1.90, 14 => 1.80, 15 => 1.80, 16 => 1.80, 18 => 1.70, 20 => 1.70],
                'vir'      => [6 => 1.70, 8 => 1.40, 10 => 1.20, 12 => 1.10, 14 => 1.00, 15 => 0.90, 16 => 0.90, 18 => 0.80, 20 => 0.80],
            ],
            'argon' => [
                'standard' => [6 => 2.10, 8 => 1.90, 10 => 1.80, 12 => 1.80, 14 => 1.70, 15 => 1.70, 16 => 1.70, 18 => 1.60, 20 => 1.60],
                'vir'      => [6 => 1.50, 8 => 1.20, 10 => 1.00, 12 => 0.90, 14 => 0.80, 15 => 0.70, 16 => 0.70, 18 => 0.60, 20 => 0.60],
            ],
        ],
        // Triple vitrage horizontal (p.25 bas)
        'horizontal' => [
            'air' => [
                'standard' => [6 => 2.50, 8 => 2.20, 10 => 2.10, 12 => 2.00, 14 => 1.90, 15 => 1.90, 16 => 1.90, 18 => 1.80, 20 => 1.80],
                'vir'      => [6 => 1.80, 8 => 1.50, 10 => 1.20, 12 => 1.10, 14 => 1.00, 15 => 0.90, 16 => 0.90, 18 => 0.80, 20 => 0.80],
            ],
            'argon' => [
                'standard' => [6 => 2.20, 8 => 2.00, 10 => 1.90, 12 => 1.90, 14 => 1.80, 15 => 1.80, 16 => 1.80, 18 => 1.70, 20 => 1.70],
                'vir'      => [6 => 1.60, 8 => 1.20, 10 => 1.00, 12 => 0.90, 14 => 0.80, 15 => 0.70, 16 => 0.70, 18 => 0.60, 20 => 0.60],
            ],
        ],
    ],

    // Cas direct §3.3 (traités comme parois vitrées) — Uw final, pas Ug
    'special_uw' => [
        'brique_verre_pleine'  => 3.50,
        'brique_verre_creuse'  => 2.00,
        'polycarbonate'        => 3.00,
    ],
];
