<?php

declare(strict_types=1);

/**
 * Coefficient Fe1 des masques proches — §6.2.2.1 p.47-49.
 * Indexé par `tv_coef_masque_proche_id` (1…19).
 *
 * Mapping (déterminé par l'ordre habituel des tables LICIEL) :
 *
 * §6.2.2.1.1 Fond de balcon / loggia (IDs 1–12)
 *   Orientations : Nord (1–4), Sud (5–8), Est/Ouest (9–12)
 *   Avancées par bloc : l<1 · 1≤l<2 · 2≤l<3 · 3≤l
 *
 * §6.2.2.1.2 Baie sous balcon ou auvent (IDs 13–16)
 *   l<1=0,8 · 1≤l<2=0,6 · 2≤l<3=0,5 · l≥3=0,4
 *
 * §6.2.2.1.3 Paroi latérale (IDs 17–18)
 *   17 = retour ne fait pas obstacle au Sud → 0,7
 *   18 = retour fait obstacle au Sud        → 0,5
 *
 * ID 19 : pas de masque proche → Fe1 = 1,0
 *
 * @spec-section 6.2.2.1
 * @spec-pages 47-49
 * @spec-source resources/specsplitted/06-apports-gratuits/02-surface-sud-equivalente/04-masques-proches.md
 * @generated-on 2026-04-29
 */
return [
    // §6.2.2.1.1 Fond balcon/loggia — Nord
    1  => 0.40,  // Nord, l < 1 m
    2  => 0.30,  // Nord, 1 m ≤ l < 2 m
    3  => 0.20,  // Nord, 2 m ≤ l < 3 m
    4  => 0.10,  // Nord, l ≥ 3 m

    // §6.2.2.1.1 Fond balcon/loggia — Sud
    5  => 0.50,  // Sud, l < 1 m
    6  => 0.40,  // Sud, 1 m ≤ l < 2 m
    7  => 0.30,  // Sud, 2 m ≤ l < 3 m
    8  => 0.20,  // Sud, l ≥ 3 m

    // §6.2.2.1.1 Fond balcon/loggia — Est ou Ouest
    9  => 0.45,  // Est/Ouest, l < 1 m
    10 => 0.35,  // Est/Ouest, 1 m ≤ l < 2 m
    11 => 0.25,  // Est/Ouest, 2 m ≤ l < 3 m
    12 => 0.15,  // Est/Ouest, l ≥ 3 m

    // §6.2.2.1.2 Baie sous balcon ou auvent (toutes orientations)
    13 => 0.80,  // l < 1 m
    14 => 0.60,  // 1 m ≤ l < 2 m
    15 => 0.50,  // 2 m ≤ l < 3 m
    16 => 0.40,  // l ≥ 3 m

    // §6.2.2.1.3 Paroi latérale
    17 => 0.70,  // le retour ne fait pas obstacle au Sud
    18 => 0.50,  // le retour fait obstacle au Sud

    // Aucun masque proche
    19 => 1.00,
];
