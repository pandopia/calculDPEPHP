<?php

declare(strict_types=1);

/**
 * Coefficient de transmission thermique Upb0 du plancher bas non isolé, en W/(m².K).
 *
 * La spec §3.2.2.2 (p.20) donne des valeurs forfaitaires par type de plancher bas
 * (illustrations sans dimensions). On les indexe par `enum_type_plancher_bas_id`.
 *
 * Mapping (vérifié vs open3cl tv.js — source de vérité pour l'association type→Upb0) :
 *   1  inconnu                                   → 2.0   (forfait conservateur)
 *   2  plancher avec/sans remplissage            → 1.45
 *   3  plancher entre solives métalliques        → 1.45
 *   4  plancher entre solives bois               → 1.1
 *   5  plancher bois sur solives métalliques     → 1.6
 *   6  bardeaux et remplissage                   → 1.1
 *   7  voutains sur solives métalliques          → 1.75
 *   8  voutains en briques ou moellons           → 0.8
 *   9  dalle béton                               → 2.0
 *   10 plancher bois sur solives bois            → 1.6
 *   11 plancher lourd entrevous TC, poutrelles   → 2.0
 *   12 plancher à entrevous isolant              → 0.45
 *   13 autre type non répertorié                 → 2.0
 *
 * @spec-section 3.2.2.2
 * @spec-pages 20
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/02-upb/02-calcul-upb0.md
 * @generated-on 2026-04-29
 *
 * Format : enum_type_plancher_bas_id => umur0 (W/(m².K))
 */
return [
    1  => 2.00,  // inconnu
    2  => 1.45,  // plancher avec/sans remplissage
    3  => 1.45,  // plancher entre solives métalliques
    4  => 1.10,  // plancher entre solives bois
    5  => 1.60,  // plancher bois sur solives métalliques
    6  => 1.10,  // bardeaux et remplissage
    7  => 1.75,  // voutains sur solives métalliques
    8  => 0.80,  // voutains en briques ou moellons
    9  => 2.00,  // dalle béton
    10 => 1.60,  // plancher bois sur solives bois
    11 => 2.00,  // plancher lourd type entrevous terre-cuite, poutrelles béton
    12 => 0.45,  // plancher à entrevous isolant
    13 => 2.00,  // autre type non répertorié
];
