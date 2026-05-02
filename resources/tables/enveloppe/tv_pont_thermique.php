<?php

declare(strict_types=1);

/**
 * Coefficient k linéique des ponts thermiques (W/(m.K)), §3.4.1 à §3.4.5.
 *
 * Digitalisation **complète** des 5 sous-tableaux. Indexée par paramètres directs
 * (type de liaison, type d'isolation des parois adjacentes, type de pose pour les
 * menuiseries, etc.). **N'utilise pas** `tv_pont_thermique_id` qui est un mapping
 * interne LICIEL non documenté par la spec officielle.
 *
 * Convention de codage des isolations :
 *   - 'non_isole'   isolation inconnue ou absente
 *   - 'iti'         intérieure
 *   - 'ite'         extérieure
 *   - 'itr'         répartie
 *   - 'iti_ite'     intérieure + extérieure
 *   - 'iti_itr'     intérieure + répartie
 *   - 'ite_itr'     extérieure + répartie
 *
 * @spec-section 3.4
 * @spec-pages 32-37
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/04-ponts-thermiques/00-overview.md
 * @generated-on 2026-04-29
 */
return [

    // §3.4.1 — Plancher bas / mur (p.35) — k_pb_m_j (table 7 × 4)
    'pb_mur' => [
        // [iso_mur][iso_pb] = k
        'non_isole' => ['non_isole' => 0.39, 'iti' => 0.47, 'ite' => 0.80, 'iti_ite' => 0.47],
        'iti'       => ['non_isole' => 0.31, 'iti' => 0.08, 'ite' => 0.71, 'iti_ite' => 0.08],
        'ite'       => ['non_isole' => 0.49, 'iti' => 0.48, 'ite' => 0.64, 'iti_ite' => 0.48],
        'itr'       => ['non_isole' => 0.35, 'iti' => 0.10, 'ite' => 0.45, 'iti_ite' => 0.10],
        'iti_ite'   => ['non_isole' => 0.31, 'iti' => 0.08, 'ite' => 0.45, 'iti_ite' => 0.08],
        'iti_itr'   => ['non_isole' => 0.31, 'iti' => 0.08, 'ite' => 0.45, 'iti_ite' => 0.08],
        'ite_itr'   => ['non_isole' => 0.35, 'iti' => 0.10, 'ite' => 0.45, 'iti_ite' => 0.10],
    ],

    // §3.4.2 — Plancher intermédiaire / mur (p.35) — k_pi_m_j (uniquement par iso_mur)
    'pi_mur' => [
        'non_isole' => 0.86,
        'iti'       => 0.92,
        'ite'       => 0.13,
        'itr'       => 0.24,
        'iti_ite'   => 0.13,
        'iti_itr'   => 0.24,
        'ite_itr'   => 0.13,
    ],

    // §3.4.3 — Plancher haut / mur (p.36) — k_ph_m_j (table 7 × 4)
    'ph_mur' => [
        'non_isole' => ['non_isole' => 0.30, 'iti' => 0.83, 'ite' => 0.40, 'iti_ite' => 0.40],
        'iti'       => ['non_isole' => 0.27, 'iti' => 0.07, 'ite' => 0.75, 'iti_ite' => 0.07],
        'ite'       => ['non_isole' => 0.55, 'iti' => 0.76, 'ite' => 0.58, 'iti_ite' => 0.58],
        'itr'       => ['non_isole' => 0.40, 'iti' => 0.30, 'ite' => 0.48, 'iti_ite' => 0.30],
        'iti_ite'   => ['non_isole' => 0.27, 'iti' => 0.07, 'ite' => 0.58, 'iti_ite' => 0.07],
        'iti_itr'   => ['non_isole' => 0.27, 'iti' => 0.07, 'ite' => 0.48, 'iti_ite' => 0.07],
        'ite_itr'   => ['non_isole' => 0.40, 'iti' => 0.30, 'ite' => 0.48, 'iti_ite' => 0.30],
    ],

    // §3.4.4 — Refend / mur (p.36) — k_rf_m_j (uniquement par iso_mur)
    'refend_mur' => [
        'non_isole' => 0.73,
        'iti'       => 0.82,
        'ite'       => 0.13,
        'itr'       => 0.20,
        'iti_ite'   => 0.13,
        'iti_itr'   => 0.20,
        'ite_itr'   => 0.13,
    ],

    // §3.4.5 — Menuiserie / mur (p.37) — k_men_m_j (table 12 × 6)
    // Indexation : [iso_mur_avec_retour][type_pose][Lp_cm]
    //   iso_mur_avec_retour : 'non_isole' | '<iso>_avec_retour' | '<iso>_sans_retour' | 'itr' (ITR n'a qu'une valeur unique 0.20 pour pose tunnel uniquement)
    //   type_pose           : 'nu_exterieur' | 'tunnel' | 'nu_interieur'
    //   Lp_cm               : 5 | 10  (largeur dormant arrondie)
    'menuiserie_mur' => [
        'non_isole' => [
            'nu_exterieur' => [5 => 0.43, 10 => 0.29],
            'tunnel'       => [5 => 0.31, 10 => 0.19],
            'nu_interieur' => [5 => 0.38, 10 => 0.25],
        ],
        'iti_avec_retour' => [
            'nu_exterieur' => [5 => 0.22, 10 => 0.18],
            'tunnel'       => [5 => 0.16, 10 => 0.13],
            'nu_interieur' => [5 => 0.00, 10 => 0.00],
        ],
        'iti_sans_retour' => [
            'nu_exterieur' => [5 => 0.43, 10 => 0.29],
            'tunnel'       => [5 => 0.31, 10 => 0.19],
            'nu_interieur' => [5 => 0.00, 10 => 0.00],
        ],
        'ite_avec_retour' => [
            'nu_exterieur' => [5 => 0.00, 10 => 0.00],
            'tunnel'       => [5 => 0.19, 10 => 0.15],
            'nu_interieur' => [5 => 0.25, 10 => 0.20],
        ],
        'ite_sans_retour' => [
            'nu_exterieur' => [5 => 0.00, 10 => 0.00],
            'tunnel'       => [5 => 0.45, 10 => 0.40],
            'nu_interieur' => [5 => 0.90, 10 => 0.80],
        ],
        // ITR : valeur unique 0,20 toutes positions (cf. spec p.37)
        'itr' => [
            'nu_exterieur' => [5 => 0.20, 10 => 0.20],
            'tunnel'       => [5 => 0.20, 10 => 0.20],
            'nu_interieur' => [5 => 0.20, 10 => 0.20],
        ],
        'iti_ite_avec_retour' => [
            'nu_exterieur' => [5 => 0.00, 10 => 0.00],
            'tunnel'       => [5 => 0.16, 10 => 0.13],
            'nu_interieur' => [5 => 0.00, 10 => 0.00],
        ],
        'iti_ite_sans_retour' => [
            'nu_exterieur' => [5 => 0.00, 10 => 0.00],
            'tunnel'       => [5 => 0.31, 10 => 0.19],
            'nu_interieur' => [5 => 0.00, 10 => 0.00],
        ],
        'iti_itr_avec_retour' => [
            'nu_exterieur' => [5 => 0.20, 10 => 0.18],
            'tunnel'       => [5 => 0.16, 10 => 0.13],
            'nu_interieur' => [5 => 0.00, 10 => 0.00],
        ],
        'iti_itr_sans_retour' => [
            'nu_exterieur' => [5 => 0.20, 10 => 0.20],
            'tunnel'       => [5 => 0.20, 10 => 0.19],
            'nu_interieur' => [5 => 0.00, 10 => 0.00],
        ],
        'ite_itr_avec_retour' => [
            'nu_exterieur' => [5 => 0.00, 10 => 0.00],
            'tunnel'       => [5 => 0.19, 10 => 0.15],
            'nu_interieur' => [5 => 0.20, 10 => 0.20],
        ],
        'ite_itr_sans_retour' => [
            'nu_exterieur' => [5 => 0.00, 10 => 0.00],
            'tunnel'       => [5 => 0.20, 10 => 0.20],
            'nu_interieur' => [5 => 0.20, 10 => 0.20],
        ],
    ],
];
