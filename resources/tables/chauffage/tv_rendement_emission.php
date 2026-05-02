<?php

/**
 * Rendement d'émission Re par type d'émetteur (§12.1 p.75-76).
 *
 * Clé : enum_type_emission_distribution_id (1-50).
 * Valeur : Re (float).
 *
 * @spec-section 12.1
 * @spec-pages   75-76
 * @spec-source  resources/specsplitted/12-rendements-installations/01-emission.md
 * @generated-on 2026-04-29
 */
return [
    // --- Émetteurs électriques locaux ---
    1  => 0.95, // convecteur électrique NFC, NF** et NF***
    2  => 0.97, // panneau rayonnant NFC, NF** et NF***
    3  => 0.97, // radiateur électrique NFC, NF** et NF***
    4  => 0.95, // autres émetteurs à effet joule
    // --- Soufflage aéraulique ---
    5  => 0.95, // soufflage d'air chaud avec réseau aéraulique
    // --- Plafonds rayonnants électriques → Re=0.98 ---
    6  => 0.98, // plafond rayonnant électrique avec régulation terminale
    7  => 0.98, // plafond rayonnant électrique sans régulation
    // --- Planchers rayonnants électriques → Re=1 ---
    8  => 1.00, // plancher rayonnant électrique avec régulation terminale
    9  => 1.00, // plancher rayonnant électrique sans régulation
    // --- Radiateur électrique à accumulation ---
    10 => 0.95, // radiateur électrique à accumulation
    // --- Planchers chauffants eau → Re=1 ---
    11 => 1.00, // plancher chauffant réseau collectif eau HT (≥65°C)
    12 => 1.00, // plancher chauffant réseau collectif eau BT (<65°C)
    13 => 1.00, // plancher chauffant réseau individuel eau HT (≥65°C)
    14 => 1.00, // plancher chauffant réseau individuel eau BT (<65°C)
    // --- Plafonds chauffants eau → Re=0.98 ---
    15 => 0.98, // plafond chauffant réseau collectif eau HT (≥65°C)
    16 => 0.98, // plafond chauffant réseau collectif eau BT (<65°C)
    17 => 0.98, // plafond chauffant réseau individuel eau HT (≥65°C)
    18 => 0.98, // plafond chauffant réseau individuel eau BT (<65°C)
    // --- Émetteurs gaz / combustion locaux ---
    19 => 0.95, // radiateur gaz à ventouse ou conduit de fumée
    20 => 0.95, // poêle charbon
    21 => 0.95, // poêle bois
    22 => 0.95, // poêle fioul
    23 => 0.95, // poêle gpl
    // --- Radiateurs eau chaude (mono/bitube, sans robinet, collectif/individuel, HT/BT) ---
    24 => 0.95, // radiateur monotube sans robinet collectif HT
    25 => 0.95, // radiateur monotube sans robinet collectif BT
    26 => 0.95, // radiateur monotube sans robinet individuel HT
    27 => 0.95, // radiateur monotube sans robinet individuel BT
    // --- Radiateurs eau chaude avec robinet thermostatique ---
    28 => 0.95, // radiateur monotube avec robinet collectif HT
    29 => 0.95, // radiateur monotube avec robinet collectif BT
    30 => 0.95, // radiateur monotube avec robinet individuel HT
    31 => 0.95, // radiateur monotube avec robinet individuel BT
    // --- Radiateurs bitube sans robinet ---
    32 => 0.95, // radiateur bitube sans robinet collectif HT
    33 => 0.95, // radiateur bitube sans robinet collectif BT
    34 => 0.95, // radiateur bitube sans robinet individuel HT
    35 => 0.95, // radiateur bitube sans robinet individuel BT
    // --- Radiateurs bitube avec robinet thermostatique ---
    36 => 0.95, // radiateur bitube avec robinet collectif HT
    37 => 0.95, // radiateur bitube avec robinet collectif BT
    38 => 0.95, // radiateur bitube avec robinet individuel HT
    39 => 0.95, // radiateur bitube avec robinet individuel BT
    // --- Autres ---
    40 => 0.95, // convecteur bi-jonction
    41 => 0.95, // autres équipements
    // --- Fluide frigorigène ---
    42 => 0.95, // soufflage d'air frigorigène (air soufflé)
    43 => 1.00, // plancher chauffant frigorigène (détente directe)
    44 => 0.98, // plafond chauffant frigorigène (détente directe)
    45 => 0.95, // radiateur frigorigène (à détente directe)
    // --- Ventiloconvecteurs eau ---
    46 => 0.95, // ventiloconvecteur réseau collectif eau HT
    47 => 0.95, // ventiloconvecteur réseau collectif eau BT
    48 => 0.95, // ventiloconvecteur réseau individuel eau HT
    49 => 0.95, // ventiloconvecteur réseau individuel eau BT
    // --- Ventiloconvecteur électrique ---
    50 => 0.95, // soufflage d'air sans réseau (ventiloconvecteur électrique)
];
