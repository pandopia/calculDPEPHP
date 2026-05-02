<?php

declare(strict_types=1);

/**
 * Coefficients de transparence T des espaces tampons solarisés — §6.3 p.50-52.
 *
 * Indexé par tv_coef_transparence_ets_id (1-21).
 * Combinaisons : 4 types menuiserie × 5 types vitrage + polycarbonate (21 entrées).
 *
 * Menuiseries :
 *   Bois/Bois métal     → ids 1-5
 *   PVC                 → ids 6-10
 *   Métal avec rupture  → ids 11-15
 *   Métal               → ids 16-20
 *   Polycarbonate       → id 21
 *
 * Vitrages (par menuiserie) :
 *   +0 : simple vitrage
 *   +1 : double vitrage
 *   +2 : double vitrage peu émissif
 *   +3 : triple vitrage
 *   +4 : triple vitrage peu émissif
 *
 * @spec-section 6.3
 * @spec-pages   50-51
 * @spec-source  resources/specsplitted/06-apports-gratuits/03-espaces-tampons-solarises.md
 * @generated-on 2026-04-29
 * @status verified-spec — valeurs lues p.50-51 table "Menuiserie/Type de Vitrage/Transparence T"
 */
return [
    // ── Bois / Bois métal ────────────────────────────────────────────────────
    1  => 0.62, // simple vitrage
    2  => 0.55, // double vitrage
    3  => 0.48, // double vitrage peu émissif
    4  => 0.49, // triple vitrage
    5  => 0.44, // triple vitrage peu émissif

    // ── PVC ──────────────────────────────────────────────────────────────────
    6  => 0.50, // simple vitrage
    7  => 0.45, // double vitrage
    8  => 0.39, // double vitrage peu émissif
    9  => 0.40, // triple vitrage
    10 => 0.36, // triple vitrage peu émissif

    // ── Métal avec rupture de pont thermique ─────────────────────────────────
    11 => 0.63, // simple vitrage
    12 => 0.56, // double vitrage
    13 => 0.48, // double vitrage peu émissif
    14 => 0.50, // triple vitrage
    15 => 0.45, // triple vitrage peu émissif

    // ── Métal ────────────────────────────────────────────────────────────────
    16 => 0.64, // simple vitrage
    17 => 0.58, // double vitrage
    18 => 0.50, // double vitrage peu émissif
    19 => 0.52, // triple vitrage
    20 => 0.47, // triple vitrage peu émissif

    // ── Polycarbonate ────────────────────────────────────────────────────────
    21 => 0.40, // §6.3 p.51 "Pour les parois en polycarbonate, on prendra T = 0,4"
];
