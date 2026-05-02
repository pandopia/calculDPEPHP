<?php

declare(strict_types=1);

/**
 * Coefficients de perte Cr (Wh/l/°C/jour) pour ballons d'accumulation électriques — §11.6.2 p.74-75.
 *
 * Structure : $table[tv_pertes_stockage_id] → ['cr' => float, 'cat_c' => bool]
 *   cat_c = true  → Rs = 1,08 / (1 + Qg,w × Rd / Becs)
 *   cat_c = false → Rs = 1    / (1 + Qg,w × Rd / Becs)
 *
 * Organisation : 4 groupes × 4 catégories = 16 entrées
 *   Groupes (tranche de volume) :
 *     IDs  1- 4 : volume ≤ 100 L
 *     IDs  5- 8 : 100 L < volume ≤ 200 L
 *     IDs  9-12 : 200 L < volume ≤ 300 L
 *     IDs 13-16 : volume > 300 L
 *   Catégories dans chaque groupe (même ordre que la table spec) :
 *     +0 : horizontal
 *     +1 : autres ou inconnue (vertical)
 *     +2 : vertical catégorie B ou 2 étoiles
 *     +3 : vertical catégorie C ou 3 étoiles  ← Rs avec 1,08
 *
 * @spec-section 11.6.2
 * @spec-pages   74-75
 * @spec-source  resources/specsplitted/11-conso-ecs/06-rendement-stockage.md
 * @generated-on 2026-04-29
 */
return [
    // ── ≤ 100 L ────────────────────────────────────────────────────────────
    1  => ['cr' => 0.39, 'cat_c' => false], // horizontal ≤100             p.75
    2  => ['cr' => 0.32, 'cat_c' => false], // autres/inconnue ≤100         p.75
    3  => ['cr' => 0.27, 'cat_c' => false], // vertical cat B/2* ≤100       p.75
    4  => ['cr' => 0.25, 'cat_c' => true],  // vertical cat C/3* ≤100       p.75

    // ── 100 < volume ≤ 200 L ───────────────────────────────────────────────
    5  => ['cr' => 0.33, 'cat_c' => false], // horizontal 100-200           p.75
    6  => ['cr' => 0.23, 'cat_c' => false], // autres/inconnue 100-200      p.75
    7  => ['cr' => 0.22, 'cat_c' => false], // vertical cat B/2* 100-200    p.75
    8  => ['cr' => 0.20, 'cat_c' => true],  // vertical cat C/3* 100-200    p.75

    // ── 200 < volume ≤ 300 L ───────────────────────────────────────────────
    9  => ['cr' => 0.30, 'cat_c' => false], // horizontal 200-300           p.75
    10 => ['cr' => 0.22, 'cat_c' => false], // autres/inconnue 200-300      p.75
    11 => ['cr' => 0.20, 'cat_c' => false], // vertical cat B/2* 200-300    p.75
    12 => ['cr' => 0.18, 'cat_c' => true],  // vertical cat C/3* 200-300    p.75

    // ── volume > 300 L ─────────────────────────────────────────────────────
    13 => ['cr' => 0.30, 'cat_c' => false], // horizontal >300              p.75
    14 => ['cr' => 0.22, 'cat_c' => false], // autres/inconnue >300         p.75
    15 => ['cr' => 0.18, 'cat_c' => false], // vertical cat B/2* >300       p.75
    16 => ['cr' => 0.16, 'cat_c' => true],  // vertical cat C/3* >300       p.75
];
