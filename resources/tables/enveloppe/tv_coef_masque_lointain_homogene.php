<?php

/**
 * @spec-section 6.2.2.2
 * @spec-pages   49-50
 * @spec-source  resources/specsplitted/06-apports-gratuits/02-surface-sud-equivalente/06-masques-lointains.md
 * @generated-on 2026-04-30
 *
 * Coefficient Fe2 pour masques lointains homogènes.
 * Indexé par tv_coef_masque_lointain_homogene_id → fe2
 */
return [
    1  => 1.0,   // Nord, hauteur_alpha < 15°
    2  => 0.82,  // Nord, 15 ≤ alpha < 30°
    3  => 0.5,   // Nord, 30 ≤ alpha < 60°
    4  => 0.3,   // Nord, 60 ≤ alpha < 90°
    5  => 1.0,   // Sud, hauteur_alpha < 15°
    6  => 0.8,   // Sud, 15 ≤ alpha < 30°
    7  => 0.3,   // Sud, 30 ≤ alpha < 60°
    8  => 0.1,   // Sud, 60 ≤ alpha < 90°
    9  => 1.0,   // Est/Ouest, hauteur_alpha < 15°
    10 => 0.77,  // Est/Ouest, 15 ≤ alpha < 30°
    11 => 0.4,   // Est/Ouest, 30 ≤ alpha < 60°
    12 => 0.2,   // Est/Ouest, 60 ≤ alpha < 90°
];
