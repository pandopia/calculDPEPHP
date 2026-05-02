<?php

declare(strict_types=1);

/**
 * Table des coefficients d'intermittence I0 — §8 p.55-57.
 *
 * Structure : batiment_type → chauffage_type → regulation → emetteur → inertie → equipement → I0.
 *
 * batiment_type :
 *   'maison'     DPE maison individuelle (enum_methode_application_dpe_log_id = 1, 14, 18)
 *   'collectif_individuel'  Immeuble avec chauffage individuel (enum 2-5, 10-13, ...)
 *   'collectif_collectif'   Immeuble avec chauffage collectif (enum_equipement_intermittence_id ∈ {6,7})
 *
 * chauffage_type : 'divise' (enum_type_chauffage_id=1) | 'central' (=2)
 *
 * regulation : 'avec' (enum_type_regulation_id=2) | 'sans' (=1)
 *
 * emetteur : 'air_souffle' | 'radiateur' | 'plafond' | 'plancher'
 *   Mapping enum_type_emission_distribution_id :
 *     air_souffle : 5, 42, 46, 47, 48, 49, 50
 *     plafond     : 6, 7, 15, 16, 17, 18, 44
 *     plancher    : 8, 9, 11, 12, 13, 14, 43
 *     radiateur   : tous les autres (1-4, 10, 19-41, 45)
 *
 * inertie : 'legere_moyenne' (enum_classe_inertie_id 1-2) | 'lourde' (3-4)
 *
 * Pour maison/collectif_individuel :
 *   equipement : 1=absent, 2=central_sans, 3=central_avec, 4=ppp_avec, 5=ppp_detection
 *   null pour les combinaisons non prévues par la spec (→ retourner valeur avec enum 1 par défaut)
 *
 * Pour collectif_collectif :
 *   comptage : 'absent' | 'present'
 *   equipement : 1=absent, 6=central_collectif, 7=central_collectif_detection
 *   Le champ comptage est déterminé par l'installation (présence d'un compteur individuel d'énergie).
 *
 * null = combinaison non définie par la spec (ex: plancher chauffant + par pièce + détection).
 *
 * @spec-section 8
 * @spec-pages 55-57
 * @spec-source resources/specsplitted/08-intermittence/00-calcul.md
 * @generated-on 2026-04-29
 * @status verified-spec — valeurs lues depuis les 3 tables p.55-57 ; vérifiées sur bat_pre2026 (i0=0.86) et bat_post2026 (i0=1.01)
 */
return [
    // ── Table 1 : maisons individuelles (chauffage individuel) ─────────────────
    'maison' => [
        'divise' => [
            'avec' => [
                'air_souffle'  => ['legere_moyenne' => [1 => 0.84, 2 => 0.83, 3 => 0.81, 4 => 0.77, 5 => 0.75], 'lourde' => [1 => 0.86, 2 => 0.85, 3 => 0.83, 4 => 0.80, 5 => 0.78]],
                'radiateur'    => ['legere_moyenne' => [1 => 0.84, 2 => 0.83, 3 => 0.81, 4 => 0.77, 5 => 0.75], 'lourde' => [1 => 0.86, 2 => 0.85, 3 => 0.83, 4 => 0.80, 5 => 0.78]],
                'plafond'      => ['legere_moyenne' => [1 => 0.84, 2 => 0.83, 3 => 0.81, 4 => 0.77, 5 => 0.75], 'lourde' => [1 => 0.86, 2 => 0.85, 3 => 0.83, 4 => 0.80, 5 => 0.78]],
                'plancher'     => ['legere_moyenne' => [1 => 0.90, 2 => 0.89, 3 => 0.88, 4 => 0.86, 5 => null ], 'lourde' => [1 => 0.92, 2 => 0.91, 3 => 0.90, 4 => 0.88, 5 => null ]],
            ],
            'sans' => [
                // Pour chauffage divisé, spec ne précise pas de ligne "sans régulation" distincte → mêmes valeurs qu'avec
                'air_souffle'  => ['legere_moyenne' => [1 => 0.84, 2 => 0.83, 3 => 0.81, 4 => null,  5 => null ], 'lourde' => [1 => 0.86, 2 => 0.85, 3 => 0.83, 4 => null,  5 => null ]],
                'radiateur'    => ['legere_moyenne' => [1 => 0.84, 2 => 0.83, 3 => 0.81, 4 => null,  5 => null ], 'lourde' => [1 => 0.86, 2 => 0.85, 3 => 0.83, 4 => null,  5 => null ]],
                'plafond'      => ['legere_moyenne' => [1 => 0.84, 2 => 0.83, 3 => 0.81, 4 => null,  5 => null ], 'lourde' => [1 => 0.86, 2 => 0.85, 3 => 0.83, 4 => null,  5 => null ]],
                'plancher'     => ['legere_moyenne' => [1 => 0.90, 2 => 0.89, 3 => 0.88, 4 => null,  5 => null ], 'lourde' => [1 => 0.92, 2 => 0.91, 3 => 0.90, 4 => null,  5 => null ]],
            ],
        ],
        'central' => [
            'avec' => [
                'air_souffle'  => ['legere_moyenne' => [1 => 0.86, 2 => 0.85, 3 => 0.83, 4 => 0.79, 5 => 0.77], 'lourde' => [1 => 0.88, 2 => 0.87, 3 => 0.85, 4 => 0.82, 5 => 0.80]],
                'radiateur'    => ['legere_moyenne' => [1 => 0.88, 2 => 0.87, 3 => 0.85, 4 => 0.82, 5 => 0.80], 'lourde' => [1 => 0.90, 2 => 0.89, 3 => 0.87, 4 => 0.85, 5 => 0.82]],
                'plafond'      => ['legere_moyenne' => [1 => 0.88, 2 => 0.87, 3 => 0.85, 4 => 0.82, 5 => 0.80], 'lourde' => [1 => 0.90, 2 => 0.89, 3 => 0.87, 4 => 0.85, 5 => 0.82]],
                'plancher'     => ['legere_moyenne' => [1 => 0.90, 2 => 0.89, 3 => 0.88, 4 => 0.86, 5 => null ], 'lourde' => [1 => 0.92, 2 => 0.91, 3 => 0.90, 4 => 0.88, 5 => null ]],
            ],
            'sans' => [
                'air_souffle'  => ['legere_moyenne' => [1 => 0.90, 2 => 0.89, 3 => 0.87, 4 => null,  5 => null ], 'lourde' => [1 => 0.91, 2 => 0.91, 3 => 0.89, 4 => null,  5 => null ]],
                'radiateur'    => ['legere_moyenne' => [1 => 0.91, 2 => 0.90, 3 => 0.88, 4 => null,  5 => null ], 'lourde' => [1 => 0.93, 2 => 0.92, 3 => 0.90, 4 => null,  5 => null ]],
                'plafond'      => ['legere_moyenne' => [1 => 0.91, 2 => 0.90, 3 => 0.88, 4 => null,  5 => null ], 'lourde' => [1 => 0.93, 2 => 0.92, 3 => 0.90, 4 => null,  5 => null ]],
                'plancher'     => ['legere_moyenne' => [1 => 0.92, 2 => 0.91, 3 => 0.90, 4 => null,  5 => null ], 'lourde' => [1 => 0.94, 2 => 0.93, 3 => 0.92, 4 => null,  5 => null ]],
            ],
        ],
    ],

    // ── Table 2 : immeubles collectifs avec chauffage individuel ───────────────
    'collectif_individuel' => [
        'divise' => [
            'avec' => [
                'air_souffle'  => [1 => 0.90, 2 => 0.89, 3 => 0.88, 4 => 0.86, 5 => 0.83],
                'radiateur'    => [1 => 0.90, 2 => 0.89, 3 => 0.88, 4 => 0.86, 5 => 0.83],
                'plafond'      => [1 => 0.90, 2 => 0.89, 3 => 0.88, 4 => 0.86, 5 => 0.83],
                'plancher'     => [1 => 0.95, 2 => 0.94, 3 => 0.93, 4 => 0.91, 5 => null ],
            ],
            'sans' => [
                'air_souffle'  => [1 => 0.90, 2 => 0.89, 3 => 0.88, 4 => null,  5 => null ],
                'radiateur'    => [1 => 0.90, 2 => 0.89, 3 => 0.88, 4 => null,  5 => null ],
                'plafond'      => [1 => 0.90, 2 => 0.89, 3 => 0.88, 4 => null,  5 => null ],
                'plancher'     => [1 => 0.95, 2 => 0.94, 3 => 0.93, 4 => null,  5 => null ],
            ],
        ],
        'central' => [
            'avec' => [
                'air_souffle'  => [1 => 0.91, 2 => 0.90, 3 => 0.89, 4 => 0.87, 5 => 0.84],
                'radiateur'    => [1 => 0.93, 2 => 0.92, 3 => 0.91, 4 => 0.89, 5 => 0.86],
                'plafond'      => [1 => 0.93, 2 => 0.92, 3 => 0.91, 4 => 0.89, 5 => 0.86],
                'plancher'     => [1 => 0.95, 2 => 0.94, 3 => 0.93, 4 => 0.91, 5 => null ],
            ],
            'sans' => [
                'air_souffle'  => [1 => 0.95, 2 => 0.94, 3 => 0.93, 4 => null,  5 => null ],
                'radiateur'    => [1 => 0.96, 2 => 0.95, 3 => 0.94, 4 => null,  5 => null ],
                'plafond'      => [1 => 0.96, 2 => 0.95, 3 => 0.94, 4 => null,  5 => null ],
                'plancher'     => [1 => 0.97, 2 => 0.96, 3 => 0.95, 4 => null,  5 => null ],
            ],
        ],
    ],

    // ── Table 3 : immeubles collectifs avec chauffage collectif ───────────────
    // Clés d'équipement : 1=absent, 6=central_collectif, 7=central_collectif+détection
    // Comptage : 'absent' | 'present'
    'collectif_collectif' => [
        'central' => [
            'avec' => [
                'air_souffle'  => ['absent' => [1 => 1.01, 6 => 0.99, 7 => 0.96], 'present' => [1 => 0.93, 6 => 0.91, 7 => 0.88]],
                'radiateur'    => ['absent' => [1 => 1.03, 6 => 1.01, 7 => 0.98], 'present' => [1 => 0.95, 6 => 0.93, 7 => 0.90]],
                'plafond'      => ['absent' => [1 => 1.03, 6 => 1.01, 7 => 0.98], 'present' => [1 => 0.95, 6 => 0.93, 7 => 0.90]],
                'plancher'     => ['absent' => [1 => 1.05, 6 => 1.03, 7 => null ], 'present' => [1 => 0.97, 6 => 0.95, 7 => null ]],
            ],
            'sans' => [
                'air_souffle'  => ['absent' => [1 => 1.03, 6 => 1.01, 7 => null ], 'present' => [1 => 0.95, 6 => 0.93, 7 => null ]],
                'radiateur'    => ['absent' => [1 => 1.05, 6 => 1.03, 7 => null ], 'present' => [1 => 0.97, 6 => 0.95, 7 => null ]],
                'plafond'      => ['absent' => [1 => 1.05, 6 => 1.03, 7 => null ], 'present' => [1 => 0.97, 6 => 0.95, 7 => null ]],
                'plancher'     => ['absent' => [1 => 1.07, 6 => 1.05, 7 => null ], 'present' => [1 => 0.99, 6 => 0.97, 7 => null ]],
            ],
        ],
    ],
];
