<?php

declare(strict_types=1);

/**
 * Facteur solaire Sw des baies vitrées — §6.2.1 p.45-46.
 *
 * Structure : ['sw'][menuiserie][baie][position][vitrage] = float
 *
 * menuiserie  : 'bois', 'pvc', 'metal_rpt', 'metal_sans_rpt'
 * baie        : 'battante', 'coulissante', 'pf_battante', 'pf_coulissante', 'pf_battante_soubassement'
 * position    : 'nu_ext' (enum_type_pose_id=1) | 'nu_int_tunnel' (pose=2 ou 3)
 * vitrage     : 'simple' | 'double' | 'double_vir' | 'triple' | 'triple_vir'
 *
 * Cas spéciaux (§6.2.1 p.45) :
 *   - Polycarbonate (enum_type_baie_id = 3)        : Sw = 0,4
 *   - Brique de verre pleine/creuse (baie_id = 1/2) : Sw = 0,4
 *   → Stockés dans ['special'].
 *
 * Survitrage (enum_type_vitrage_id = 4) : prendre la valeur 'double' (§6.2.1 "du double vitrage équivalent").
 *
 * Colonnes du tableau spec: [simple, double, double_vir, triple, triple_vir]
 *
 * @spec-section 6.2.1
 * @spec-pages 45-46
 * @spec-source resources/specsplitted/06-apports-gratuits/02-surface-sud-equivalente/01-facteur-solaire.md
 * @generated-on 2026-04-29
 */
return [

    'special' => [
        'polycarbonate'     => 0.4,
        'brique_verre'      => 0.4,
    ],

    'sw' => [

        'bois' => [
            'battante' => [
                'nu_ext'         => ['simple' => 0.58, 'double' => 0.52, 'double_vir' => 0.45, 'triple' => 0.46, 'triple_vir' => 0.41],
                'nu_int_tunnel'  => ['simple' => 0.52, 'double' => 0.47, 'double_vir' => 0.40, 'triple' => 0.41, 'triple_vir' => 0.37],
            ],
            'coulissante' => [
                // même valeurs que battante pour bois (§6.2.1 tableau p.45)
                'nu_ext'         => ['simple' => 0.58, 'double' => 0.52, 'double_vir' => 0.45, 'triple' => 0.46, 'triple_vir' => 0.41],
                'nu_int_tunnel'  => ['simple' => 0.52, 'double' => 0.47, 'double_vir' => 0.40, 'triple' => 0.41, 'triple_vir' => 0.37],
            ],
            'pf_battante' => [
                // « Porte-fenêtre battante ou coulissante sans soubassement »
                'nu_ext'         => ['simple' => 0.62, 'double' => 0.55, 'double_vir' => 0.48, 'triple' => 0.49, 'triple_vir' => 0.44],
                'nu_int_tunnel'  => ['simple' => 0.56, 'double' => 0.50, 'double_vir' => 0.43, 'triple' => 0.44, 'triple_vir' => 0.40],
            ],
            'pf_coulissante' => [
                // même valeurs que pf_battante pour bois
                'nu_ext'         => ['simple' => 0.62, 'double' => 0.55, 'double_vir' => 0.48, 'triple' => 0.49, 'triple_vir' => 0.44],
                'nu_int_tunnel'  => ['simple' => 0.56, 'double' => 0.50, 'double_vir' => 0.43, 'triple' => 0.44, 'triple_vir' => 0.40],
            ],
            'pf_battante_soubassement' => [
                'nu_ext'         => ['simple' => 0.53, 'double' => 0.48, 'double_vir' => 0.41, 'triple' => 0.42, 'triple_vir' => 0.38],
                'nu_int_tunnel'  => ['simple' => 0.48, 'double' => 0.43, 'double_vir' => 0.37, 'triple' => 0.38, 'triple_vir' => 0.34],
            ],
        ],

        'pvc' => [
            'battante' => [
                'nu_ext'         => ['simple' => 0.54, 'double' => 0.48, 'double_vir' => 0.42, 'triple' => 0.43, 'triple_vir' => 0.39],
                'nu_int_tunnel'  => ['simple' => 0.49, 'double' => 0.44, 'double_vir' => 0.38, 'triple' => 0.39, 'triple_vir' => 0.35],
            ],
            'coulissante' => [
                'nu_ext'         => ['simple' => 0.60, 'double' => 0.54, 'double_vir' => 0.46, 'triple' => 0.47, 'triple_vir' => 0.43],
                'nu_int_tunnel'  => ['simple' => 0.54, 'double' => 0.48, 'double_vir' => 0.41, 'triple' => 0.43, 'triple_vir' => 0.38],
            ],
            'pf_battante' => [
                // « Porte-fenêtre battante sans soubassement »
                'nu_ext'         => ['simple' => 0.57, 'double' => 0.51, 'double_vir' => 0.44, 'triple' => 0.45, 'triple_vir' => 0.40],
                'nu_int_tunnel'  => ['simple' => 0.51, 'double' => 0.46, 'double_vir' => 0.39, 'triple' => 0.40, 'triple_vir' => 0.36],
            ],
            'pf_coulissante' => [
                'nu_ext'         => ['simple' => 0.64, 'double' => 0.57, 'double_vir' => 0.49, 'triple' => 0.51, 'triple_vir' => 0.45],
                'nu_int_tunnel'  => ['simple' => 0.57, 'double' => 0.51, 'double_vir' => 0.44, 'triple' => 0.45, 'triple_vir' => 0.41],
            ],
            'pf_battante_soubassement' => [
                'nu_ext'         => ['simple' => 0.50, 'double' => 0.45, 'double_vir' => 0.39, 'triple' => 0.40, 'triple_vir' => 0.36],
                'nu_int_tunnel'  => ['simple' => 0.45, 'double' => 0.40, 'double_vir' => 0.35, 'triple' => 0.36, 'triple_vir' => 0.32],
            ],
        ],

        'metal_rpt' => [
            'battante' => [
                'nu_ext'         => ['simple' => 0.59, 'double' => 0.53, 'double_vir' => 0.46, 'triple' => 0.47, 'triple_vir' => 0.42],
                'nu_int_tunnel'  => ['simple' => 0.53, 'double' => 0.48, 'double_vir' => 0.41, 'triple' => 0.42, 'triple_vir' => 0.38],
            ],
            'coulissante' => [
                'nu_ext'         => ['simple' => 0.65, 'double' => 0.58, 'double_vir' => 0.50, 'triple' => 0.52, 'triple_vir' => 0.46],
                'nu_int_tunnel'  => ['simple' => 0.58, 'double' => 0.52, 'double_vir' => 0.45, 'triple' => 0.46, 'triple_vir' => 0.42],
            ],
            'pf_battante' => [
                'nu_ext'         => ['simple' => 0.63, 'double' => 0.56, 'double_vir' => 0.48, 'triple' => 0.50, 'triple_vir' => 0.45],
                'nu_int_tunnel'  => ['simple' => 0.56, 'double' => 0.51, 'double_vir' => 0.44, 'triple' => 0.45, 'triple_vir' => 0.40],
            ],
            'pf_coulissante' => [
                'nu_ext'         => ['simple' => 0.70, 'double' => 0.62, 'double_vir' => 0.54, 'triple' => 0.55, 'triple_vir' => 0.50],
                'nu_int_tunnel'  => ['simple' => 0.63, 'double' => 0.56, 'double_vir' => 0.48, 'triple' => 0.50, 'triple_vir' => 0.45],
            ],
            // pas de pf_battante_soubassement pour menuiserie métal dans la spec → reprise pf_battante
            'pf_battante_soubassement' => [
                'nu_ext'         => ['simple' => 0.63, 'double' => 0.56, 'double_vir' => 0.48, 'triple' => 0.50, 'triple_vir' => 0.45],
                'nu_int_tunnel'  => ['simple' => 0.56, 'double' => 0.51, 'double_vir' => 0.44, 'triple' => 0.45, 'triple_vir' => 0.40],
            ],
        ],

        'metal_sans_rpt' => [
            'battante' => [
                'nu_ext'         => ['simple' => 0.61, 'double' => 0.55, 'double_vir' => 0.48, 'triple' => 0.49, 'triple_vir' => 0.44],
                'nu_int_tunnel'  => ['simple' => 0.55, 'double' => 0.49, 'double_vir' => 0.43, 'triple' => 0.44, 'triple_vir' => 0.40],
            ],
            'coulissante' => [
                'nu_ext'         => ['simple' => 0.67, 'double' => 0.60, 'double_vir' => 0.52, 'triple' => 0.53, 'triple_vir' => 0.48],
                'nu_int_tunnel'  => ['simple' => 0.60, 'double' => 0.54, 'double_vir' => 0.47, 'triple' => 0.48, 'triple_vir' => 0.43],
            ],
            'pf_battante' => [
                'nu_ext'         => ['simple' => 0.64, 'double' => 0.58, 'double_vir' => 0.50, 'triple' => 0.52, 'triple_vir' => 0.47],
                'nu_int_tunnel'  => ['simple' => 0.58, 'double' => 0.52, 'double_vir' => 0.45, 'triple' => 0.46, 'triple_vir' => 0.42],
            ],
            'pf_coulissante' => [
                'nu_ext'         => ['simple' => 0.71, 'double' => 0.64, 'double_vir' => 0.55, 'triple' => 0.56, 'triple_vir' => 0.51],
                'nu_int_tunnel'  => ['simple' => 0.64, 'double' => 0.57, 'double_vir' => 0.49, 'triple' => 0.51, 'triple_vir' => 0.46],
            ],
            // pas de pf_battante_soubassement pour menuiserie métal → reprise pf_battante
            'pf_battante_soubassement' => [
                'nu_ext'         => ['simple' => 0.64, 'double' => 0.58, 'double_vir' => 0.50, 'triple' => 0.52, 'triple_vir' => 0.47],
                'nu_int_tunnel'  => ['simple' => 0.58, 'double' => 0.52, 'double_vir' => 0.45, 'triple' => 0.46, 'triple_vir' => 0.42],
            ],
        ],
    ],
];
