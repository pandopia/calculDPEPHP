<?php

declare(strict_types=1);

/**
 * Coefficients ki d'orientation des panneaux photovoltaïques — §16.2 p.104.
 *
 * Structure : $table[tv_coef_orientation_pv_id] → ['ki' => float, 'orientation_id' => int, 'inclinaison_id' => int]
 *
 * Orientation (enum_orientation_pv_id) :
 *   1=Est  2=Sud-Est  3=Sud  4=Sud-Ouest  5=Ouest
 *
 * Inclinaison (enum_inclinaison_pv_id) :
 *   1=≤15°  2=15-45°  3=45-75°  4=>75°
 *
 * Indexation : IDs 1-20, organisés par orientation (5) × inclinaison (4).
 *   ID = (orientation_id - 1) × 4 + inclinaison_id
 *
 * @spec-section 16.2
 * @spec-pages   103-104
 * @spec-source  resources/specsplitted/16-eclairage-prod-elec/02-prod-electricite.md
 * @generated-on 2026-04-30
 */
return [
    // ── Est (orientation_id=1) ─────────────────────────────────────────────
    1  => ['ki' => 1.00, 'orientation_id' => 1, 'inclinaison_id' => 1], // Est, ≤15°    p.104
    2  => ['ki' => 0.96, 'orientation_id' => 1, 'inclinaison_id' => 2], // Est, 15-45°  p.104
    3  => ['ki' => 0.83, 'orientation_id' => 1, 'inclinaison_id' => 3], // Est, 45-75°  p.104
    4  => ['ki' => 0.59, 'orientation_id' => 1, 'inclinaison_id' => 4], // Est, >75°    p.104

    // ── Sud-Est (orientation_id=2) ─────────────────────────────────────────
    5  => ['ki' => 1.00, 'orientation_id' => 2, 'inclinaison_id' => 1], // Sud-Est, ≤15°    p.104
    6  => ['ki' => 1.03, 'orientation_id' => 2, 'inclinaison_id' => 2], // Sud-Est, 15-45°  p.104
    7  => ['ki' => 0.94, 'orientation_id' => 2, 'inclinaison_id' => 3], // Sud-Est, 45-75°  p.104
    8  => ['ki' => 0.71, 'orientation_id' => 2, 'inclinaison_id' => 4], // Sud-Est, >75°    p.104

    // ── Sud (orientation_id=3) ─────────────────────────────────────────────
    9  => ['ki' => 1.00, 'orientation_id' => 3, 'inclinaison_id' => 1], // Sud, ≤15°    p.104
    10 => ['ki' => 1.07, 'orientation_id' => 3, 'inclinaison_id' => 2], // Sud, 15-45°  p.104
    11 => ['ki' => 0.97, 'orientation_id' => 3, 'inclinaison_id' => 3], // Sud, 45-75°  p.104
    12 => ['ki' => 0.73, 'orientation_id' => 3, 'inclinaison_id' => 4], // Sud, >75°    p.104

    // ── Sud-Ouest (orientation_id=4) ──────────────────────────────────────
    13 => ['ki' => 1.00, 'orientation_id' => 4, 'inclinaison_id' => 1], // Sud-Ouest, ≤15°    p.104
    14 => ['ki' => 1.03, 'orientation_id' => 4, 'inclinaison_id' => 2], // Sud-Ouest, 15-45°  p.104
    15 => ['ki' => 0.94, 'orientation_id' => 4, 'inclinaison_id' => 3], // Sud-Ouest, 45-75°  p.104
    16 => ['ki' => 0.71, 'orientation_id' => 4, 'inclinaison_id' => 4], // Sud-Ouest, >75°    p.104

    // ── Ouest (orientation_id=5) ───────────────────────────────────────────
    17 => ['ki' => 1.00, 'orientation_id' => 5, 'inclinaison_id' => 1], // Ouest, ≤15°    p.104
    18 => ['ki' => 0.96, 'orientation_id' => 5, 'inclinaison_id' => 2], // Ouest, 15-45°  p.104
    19 => ['ki' => 0.83, 'orientation_id' => 5, 'inclinaison_id' => 3], // Ouest, 45-75°  p.104
    20 => ['ki' => 0.59, 'orientation_id' => 5, 'inclinaison_id' => 4], // Ouest, >75°    p.104
];
