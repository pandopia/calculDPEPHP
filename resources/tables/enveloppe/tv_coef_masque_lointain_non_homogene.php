<?php

/**
 * @spec-section 6.2.2.2
 * @spec-pages   49-50
 * @spec-source  resources/specsplitted/06-apports-gratuits/02-surface-sud-equivalente/06-masques-lointains.md
 * @generated-on 2026-04-30
 *
 * Pourcentage d'ombrage (omb) pour masques lointains non homogènes.
 * fe2 = max(0, 1 - Σ(omb_i / 100)) pour les masques d'une même baie.
 * Indexé par tv_coef_masque_lointain_non_homogene_id → omb (%)
 */
return [
    // 2 secteurs latéraux — Sud ou Nord
    1  => 0,   // secteur latéral, S/N, omb=0%
    2  => 4,   // secteur latéral, S/N, omb=4%
    3  => 13,  // secteur latéral, S/N, omb=13%
    4  => 15,  // secteur latéral, S/N, omb=15%
    // 2 secteurs centraux — Sud ou Nord
    5  => 0,   // secteur central, S/N, omb=0%
    6  => 14,  // secteur central, S/N, omb=14%
    7  => 35,  // secteur central, S/N, omb=35%
    8  => 40,  // secteur central, S/N, omb=40%
    // Secteur latéral vers le sud — Est ou Ouest
    9  => 0,   // secteur latéral vers S, E/O, omb=0%
    10 => 14,  // secteur latéral vers S, E/O, omb=14%
    11 => 27,  // secteur latéral vers S, E/O, omb=27%
    12 => 30,  // secteur latéral vers S, E/O, omb=30%
    // Secteur central vers le sud — Est ou Ouest
    13 => 0,   // secteur central vers S, E/O, omb=0%
    14 => 17,  // secteur central vers S, E/O, omb=17%
    15 => 40,  // secteur central vers S, E/O, omb=40%
    16 => 45,  // secteur central vers S, E/O, omb=45%
    // 2 autres secteurs — Est ou Ouest
    17 => 0,   // 2 autres secteurs, E/O, omb=0%
    18 => 5,   // 2 autres secteurs, E/O, omb=5%
    19 => 17,  // 2 autres secteurs, E/O, omb=17%
    20 => 25,  // 2 autres secteurs, E/O, omb=25%
];
