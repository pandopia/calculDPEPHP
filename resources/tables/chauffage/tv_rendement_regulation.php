<?php

/**
 * Rendement de régulation Rr par type d'émetteur (§12.3 p.76).
 *
 * Clé : enum_type_emission_distribution_id (1-50).
 * Valeur : Rr (float).
 *
 * Règle par défaut (§12.3 p.76) : Rr = 0.9 pour tout cas non listé.
 *
 * @spec-section 12.3
 * @spec-pages   76
 * @spec-source  resources/specsplitted/12-rendements-installations/03-regulation.md
 * @generated-on 2026-04-29
 */
return [
    // Convecteur et radiateur électrique NFC → Rr=0.99
    1  => 0.99, // convecteur électrique NFC, NF** et NF***
    2  => 0.99, // panneau rayonnant NFC, NF** et NF***
    3  => 0.99, // radiateur électrique NFC, NF** et NF***
    // Autres émetteurs à effet joule → Rr=0.96
    4  => 0.96, // autres émetteurs à effet joule
    // Air soufflé → Rr=0.96
    5  => 0.96, // soufflage d'air chaud avec réseau aéraulique
    // Plafond/plancher électrique avec régulation terminale → Rr=0.98
    6  => 0.98, // plafond rayonnant électrique avec régulation terminale
    // Plafond/plancher électrique sans régulation → Rr=0.96
    7  => 0.96, // plafond rayonnant électrique sans régulation
    // Plancher électrique avec régulation terminale → Rr=0.98
    8  => 0.98, // plancher rayonnant électrique avec régulation terminale
    // Plancher électrique sans régulation → Rr=0.96
    9  => 0.96, // plancher rayonnant électrique sans régulation
    // Radiateur électrique à accumulation → Rr=0.95
    10 => 0.95, // radiateur électrique à accumulation
    // Plancher/plafond chauffant eau collectif → Rr=0.90
    11 => 0.90, // plancher collectif eau HT
    12 => 0.90, // plancher collectif eau BT
    // Plancher/plafond chauffant eau individuel → Rr=0.95
    13 => 0.95, // plancher individuel eau HT
    14 => 0.95, // plancher individuel eau BT
    // Plafond chauffant eau collectif → Rr=0.90
    15 => 0.90, // plafond collectif eau HT
    16 => 0.90, // plafond collectif eau BT
    // Plafond chauffant eau individuel → Rr=0.95
    17 => 0.95, // plafond individuel eau HT
    18 => 0.95, // plafond individuel eau BT
    // Radiateur gaz à ventouse → Rr=0.96
    19 => 0.96, // radiateur gaz à ventouse ou conduit de fumée
    // Poêles (charbon/bois/fioul/gpl) → Rr=0.80
    20 => 0.80, // poêle charbon
    21 => 0.80, // poêle bois
    22 => 0.80, // poêle fioul
    23 => 0.80, // poêle gpl
    // Radiateurs eau sans robinet thermostatique → Rr=0.90
    24 => 0.90, // radiateur monotube sans robinet collectif HT
    25 => 0.90, // radiateur monotube sans robinet collectif BT
    26 => 0.90, // radiateur monotube sans robinet individuel HT
    27 => 0.90, // radiateur monotube sans robinet individuel BT
    // Radiateurs eau avec robinet thermostatique → Rr=0.95
    28 => 0.95, // radiateur monotube avec robinet collectif HT
    29 => 0.95, // radiateur monotube avec robinet collectif BT
    30 => 0.95, // radiateur monotube avec robinet individuel HT
    31 => 0.95, // radiateur monotube avec robinet individuel BT
    // Radiateurs bitube sans robinet → Rr=0.90
    32 => 0.90, // radiateur bitube sans robinet collectif HT
    33 => 0.90, // radiateur bitube sans robinet collectif BT
    34 => 0.90, // radiateur bitube sans robinet individuel HT
    35 => 0.90, // radiateur bitube sans robinet individuel BT
    // Radiateurs bitube avec robinet → Rr=0.95
    36 => 0.95, // radiateur bitube avec robinet collectif HT
    37 => 0.95, // radiateur bitube avec robinet collectif BT
    38 => 0.95, // radiateur bitube avec robinet individuel HT
    39 => 0.95, // radiateur bitube avec robinet individuel BT
    // Convecteur bi-jonction → Rr=0.90 (§12.3)
    40 => 0.90, // convecteur bi-jonction
    // Autres équipements → défaut 0.90
    41 => 0.90, // autres équipements
    // Fluide frigorigène (air soufflé ou assimilé) → Rr=0.96
    42 => 0.96, // soufflage frigorigène
    // Fluide frigorigène plancher/plafond/radiateur → défaut 0.90
    43 => 0.90, // plancher frigorigène
    44 => 0.90, // plafond frigorigène
    45 => 0.90, // radiateur frigorigène
    // Ventiloconvecteurs (air soufflé) → Rr=0.96
    46 => 0.96, // ventiloconvecteur collectif HT
    47 => 0.96, // ventiloconvecteur collectif BT
    48 => 0.96, // ventiloconvecteur individuel HT
    49 => 0.96, // ventiloconvecteur individuel BT
    50 => 0.96, // ventiloconvecteur électrique sans réseau
];
