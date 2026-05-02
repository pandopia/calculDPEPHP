<?php

/**
 * Rendement de distribution Rd par type de réseau (§12.2 p.76).
 *
 * Structure : [enum_type_emission_distribution_id => ['non_isole' => Rd, 'isole' => Rd]]
 * Pour les réseaux fluide frigorigène : Rd=1 (spécifié p.76 « sans pertes »).
 *
 * Le type de réseau est encodé dans enum_type_emission_distribution_id :
 *   - pas de réseau       : IDs 1-4, 6-10, 19-23, 40-41, 50     → Rd=1 (toujours)
 *   - réseau aéraulique   : IDs 5                                → 0.80/0.85
 *   - collectif eau HT    : IDs 11,15,24,28,32,36,46             → 0.85/0.87
 *   - collectif eau BT    : IDs 12,16,25,29,33,37,47             → 0.87/0.90
 *   - individuel eau HT   : IDs 13,17,26,30,34,38,48             → 0.88/0.92
 *   - individuel eau BT   : IDs 14,18,27,31,35,39,49             → 0.91/0.95
 *   - fluide frigorigène  : IDs 42-45                            → 1.00/1.00
 *
 * @spec-section 12.2
 * @spec-pages   76
 * @spec-source  resources/specsplitted/12-rendements-installations/02-distribution.md
 * @generated-on 2026-04-29
 */
return [
    // Pas de réseau de distribution → Rd=1
    1  => ['non_isole' => 1.00, 'isole' => 1.00], // convecteur électrique NFC
    2  => ['non_isole' => 1.00, 'isole' => 1.00], // panneau rayonnant NFC
    3  => ['non_isole' => 1.00, 'isole' => 1.00], // radiateur électrique NFC
    4  => ['non_isole' => 1.00, 'isole' => 1.00], // autres émetteurs effet joule
    // Réseau aéraulique → 0.80 / 0.85
    5  => ['non_isole' => 0.80, 'isole' => 0.85], // soufflage réseau aéraulique
    // Pas de réseau (plafond/plancher électriques locaux)
    6  => ['non_isole' => 1.00, 'isole' => 1.00], // plafond rayonnant élec avec régul
    7  => ['non_isole' => 1.00, 'isole' => 1.00], // plafond rayonnant élec sans régul
    8  => ['non_isole' => 1.00, 'isole' => 1.00], // plancher rayonnant élec avec régul
    9  => ['non_isole' => 1.00, 'isole' => 1.00], // plancher rayonnant élec sans régul
    10 => ['non_isole' => 1.00, 'isole' => 1.00], // radiateur élec accumulation
    // Réseau collectif eau chaude HT (≥65°C) → 0.85 / 0.87
    11 => ['non_isole' => 0.85, 'isole' => 0.87], // plancher collectif HT
    // Réseau collectif eau chaude BT (<65°C) → 0.87 / 0.90
    12 => ['non_isole' => 0.87, 'isole' => 0.90], // plancher collectif BT
    // Réseau individuel eau chaude HT (≥65°C) → 0.88 / 0.92
    13 => ['non_isole' => 0.88, 'isole' => 0.92], // plancher individuel HT
    // Réseau individuel eau chaude BT (<65°C) → 0.91 / 0.95
    14 => ['non_isole' => 0.91, 'isole' => 0.95], // plancher individuel BT
    // Réseau collectif eau chaude HT
    15 => ['non_isole' => 0.85, 'isole' => 0.87], // plafond collectif HT
    // Réseau collectif eau chaude BT
    16 => ['non_isole' => 0.87, 'isole' => 0.90], // plafond collectif BT
    // Réseau individuel eau chaude HT
    17 => ['non_isole' => 0.88, 'isole' => 0.92], // plafond individuel HT
    // Réseau individuel eau chaude BT
    18 => ['non_isole' => 0.91, 'isole' => 0.95], // plafond individuel BT
    // Pas de réseau (émetteurs locaux combustion/accumulation)
    19 => ['non_isole' => 1.00, 'isole' => 1.00], // radiateur gaz ventouse
    20 => ['non_isole' => 1.00, 'isole' => 1.00], // poêle charbon
    21 => ['non_isole' => 1.00, 'isole' => 1.00], // poêle bois
    22 => ['non_isole' => 1.00, 'isole' => 1.00], // poêle fioul
    23 => ['non_isole' => 1.00, 'isole' => 1.00], // poêle gpl
    // Réseau collectif eau chaude HT → 0.85 / 0.87
    24 => ['non_isole' => 0.85, 'isole' => 0.87], // radiateur monotube sans robinet collectif HT
    // Réseau collectif eau chaude BT → 0.87 / 0.90
    25 => ['non_isole' => 0.87, 'isole' => 0.90], // radiateur monotube sans robinet collectif BT
    // Réseau individuel eau chaude HT → 0.88 / 0.92
    26 => ['non_isole' => 0.88, 'isole' => 0.92], // radiateur monotube sans robinet individuel HT
    // Réseau individuel eau chaude BT → 0.91 / 0.95
    27 => ['non_isole' => 0.91, 'isole' => 0.95], // radiateur monotube sans robinet individuel BT
    // Réseau collectif eau chaude HT
    28 => ['non_isole' => 0.85, 'isole' => 0.87], // radiateur monotube avec robinet collectif HT
    // Réseau collectif eau chaude BT
    29 => ['non_isole' => 0.87, 'isole' => 0.90], // radiateur monotube avec robinet collectif BT
    // Réseau individuel eau chaude HT
    30 => ['non_isole' => 0.88, 'isole' => 0.92], // radiateur monotube avec robinet individuel HT
    // Réseau individuel eau chaude BT
    31 => ['non_isole' => 0.91, 'isole' => 0.95], // radiateur monotube avec robinet individuel BT
    // Réseau collectif eau chaude HT
    32 => ['non_isole' => 0.85, 'isole' => 0.87], // radiateur bitube sans robinet collectif HT
    // Réseau collectif eau chaude BT
    33 => ['non_isole' => 0.87, 'isole' => 0.90], // radiateur bitube sans robinet collectif BT
    // Réseau individuel eau chaude HT
    34 => ['non_isole' => 0.88, 'isole' => 0.92], // radiateur bitube sans robinet individuel HT
    // Réseau individuel eau chaude BT
    35 => ['non_isole' => 0.91, 'isole' => 0.95], // radiateur bitube sans robinet individuel BT
    // Réseau collectif eau chaude HT
    36 => ['non_isole' => 0.85, 'isole' => 0.87], // radiateur bitube avec robinet collectif HT
    // Réseau collectif eau chaude BT
    37 => ['non_isole' => 0.87, 'isole' => 0.90], // radiateur bitube avec robinet collectif BT
    // Réseau individuel eau chaude HT
    38 => ['non_isole' => 0.88, 'isole' => 0.92], // radiateur bitube avec robinet individuel HT
    // Réseau individuel eau chaude BT
    39 => ['non_isole' => 0.91, 'isole' => 0.95], // radiateur bitube avec robinet individuel BT
    // Pas de réseau
    40 => ['non_isole' => 1.00, 'isole' => 1.00], // convecteur bi-jonction
    41 => ['non_isole' => 1.00, 'isole' => 1.00], // autres équipements
    // Fluide frigorigène → Rd=1 (§12.2 : « sans pertes »)
    42 => ['non_isole' => 1.00, 'isole' => 1.00], // soufflage frigorigène
    43 => ['non_isole' => 1.00, 'isole' => 1.00], // plancher frigorigène
    44 => ['non_isole' => 1.00, 'isole' => 1.00], // plafond frigorigène
    45 => ['non_isole' => 1.00, 'isole' => 1.00], // radiateur frigorigène
    // Réseau collectif eau chaude HT
    46 => ['non_isole' => 0.85, 'isole' => 0.87], // ventiloconvecteur collectif HT
    // Réseau collectif eau chaude BT
    47 => ['non_isole' => 0.87, 'isole' => 0.90], // ventiloconvecteur collectif BT
    // Réseau individuel eau chaude HT
    48 => ['non_isole' => 0.88, 'isole' => 0.92], // ventiloconvecteur individuel HT
    // Réseau individuel eau chaude BT
    49 => ['non_isole' => 0.91, 'isole' => 0.95], // ventiloconvecteur individuel BT
    // Pas de réseau
    50 => ['non_isole' => 1.00, 'isole' => 1.00], // ventiloconvecteur électrique sans réseau
];
