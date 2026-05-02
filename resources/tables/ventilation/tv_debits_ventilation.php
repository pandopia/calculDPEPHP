<?php

declare(strict_types=1);

/**
 * Débits conventionnels de ventilation et puissances auxiliaires — §4 p.39-40 + §5 p.41-42.
 *
 * Indexé par `enum_type_ventilation_id` (1…38).
 *
 * Colonnes :
 *   qvarep  : débit d'air extrait conventionnel (m³/(h·m²))
 *   qvasouf : débit volumique conventionnel soufflé (m³/(h·m²))
 *   smea    : somme des modules d'entrée d'air sous 20 Pa (m³/(h·m²))
 *   pvent_immeuble : puissance auxiliaire immeuble collectif (W/(m³/h)), 0 si naturel
 *   pvent_maison   : puissance auxiliaire maison individuelle (W-ThC), 0 si naturel
 *
 * Pour immeuble : Pventmoy = pvent_immeuble × Qvarepconv × Sh
 * Pour maison   : Pventmoy = pvent_maison (valeur fixe, en W)
 *
 * Ventilation hybride (§5 p.42) :
 *   Pventmoy = consommation VMC SF autoréglable 2001-2012 × ratio_temps_mécanique
 *   Ratio collectif = 0,167 → pvent_immeuble = 0,46 × 0,167 = 0,07682 W/(m³/h)
 *   Ratio individuel = 0,083 → pvent_maison = 65 × 0,083 = 5,395 W-ThC
 *
 * @spec-section 4, 5
 * @spec-pages 39-42
 * @spec-source resources/specsplitted/04-renouvellement-air/00-calcul.md
 * @spec-source resources/specsplitted/05-auxiliaires-ventilation/00-calcul.md
 * @generated-on 2026-04-29
 */
return [
    // ID => [qvarep, qvasouf, smea, pvent_immeuble, pvent_maison]

    1  => ['qvarep' => 1.20, 'qvasouf' => 1.20, 'smea' => 0.0,  'pvent_immeuble' => 0.0,     'pvent_maison' => 0.0   ], // ventilation par ouverture des fenêtres
    2  => ['qvarep' => 2.23, 'qvasouf' => 0.00, 'smea' => 4.0,  'pvent_immeuble' => 0.0,     'pvent_maison' => 0.0   ], // ventilation par entrées d'air hautes et basses

    // VMC SF Auto-réglable
    3  => ['qvarep' => 1.97, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 65.0  ], // SF Auto < 1982
    4  => ['qvarep' => 1.65, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 65.0  ], // SF Auto 1982-2000
    5  => ['qvarep' => 1.50, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 65.0  ], // SF Auto 2001-2012
    6  => ['qvarep' => 1.32, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.25,    'pvent_maison' => 35.0  ], // SF Auto après 2012

    // VMC SF Hygro A
    7  => ['qvarep' => 1.50, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 50.0  ], // SF Hygro A < 2001
    8  => ['qvarep' => 1.44, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 50.0  ], // SF Hygro A 2001-2012
    9  => ['qvarep' => 1.16, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.25,    'pvent_maison' => 15.0  ], // SF Hygro A après 2012

    // VMC SF Gaz (assimilé SF auto §5 — pas de catégorie distincte dans la table p.41)
    10 => ['qvarep' => 1.59, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 65.0  ], // SF Gaz < 2001
    11 => ['qvarep' => 1.53, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 65.0  ], // SF Gaz 2001-2012
    12 => ['qvarep' => 1.22, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.25,    'pvent_maison' => 35.0  ], // SF Gaz après 2012

    // VMC SF Hygro B
    13 => ['qvarep' => 1.36, 'qvasouf' => 0.00, 'smea' => 1.5,  'pvent_immeuble' => 0.46,    'pvent_maison' => 50.0  ], // SF Hygro B < 2001
    14 => ['qvarep' => 1.24, 'qvasouf' => 0.00, 'smea' => 1.5,  'pvent_immeuble' => 0.46,    'pvent_maison' => 50.0  ], // SF Hygro B 2001-2012
    15 => ['qvarep' => 1.09, 'qvasouf' => 0.00, 'smea' => 1.5,  'pvent_immeuble' => 0.25,    'pvent_maison' => 15.0  ], // SF Hygro B après 2012

    // VMC Basse Pression (mêmes puissances que VMC classiques, §5 p.41)
    16 => ['qvarep' => 1.97, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 65.0  ], // BP Auto
    17 => ['qvarep' => 1.30, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 50.0  ], // BP Hygro A
    18 => ['qvarep' => 1.24, 'qvasouf' => 0.00, 'smea' => 1.5,  'pvent_immeuble' => 0.46,    'pvent_maison' => 50.0  ], // BP Hygro B

    // VMC Double Flux individuelle
    19 => ['qvarep' => 0.60, 'qvasouf' => 0.60, 'smea' => 0.0,  'pvent_immeuble' => 1.10,    'pvent_maison' => 80.0  ], // DF indiv avec échangeur ≤ 2012
    20 => ['qvarep' => 0.26, 'qvasouf' => 0.26, 'smea' => 0.0,  'pvent_immeuble' => 0.60,    'pvent_maison' => 35.0  ], // DF indiv avec échangeur après 2012

    // VMC Double Flux collective
    21 => ['qvarep' => 0.75, 'qvasouf' => 0.75, 'smea' => 0.0,  'pvent_immeuble' => 1.10,    'pvent_maison' => 80.0  ], // DF coll avec échangeur ≤ 2012
    22 => ['qvarep' => 0.46, 'qvasouf' => 0.46, 'smea' => 0.0,  'pvent_immeuble' => 0.60,    'pvent_maison' => 35.0  ], // DF coll avec échangeur après 2012

    // VMC DF sans échangeur
    23 => ['qvarep' => 1.65, 'qvasouf' => 1.65, 'smea' => 0.0,  'pvent_immeuble' => 1.10,    'pvent_maison' => 80.0  ], // DF sans échangeur ≤ 2012
    24 => ['qvarep' => 1.32, 'qvasouf' => 1.32, 'smea' => 0.0,  'pvent_immeuble' => 0.60,    'pvent_maison' => 35.0  ], // DF sans échangeur après 2012

    // Ventilation naturelle par conduit — pas de moteur
    25 => ['qvarep' => 2.23, 'qvasouf' => 0.00, 'smea' => 4.0,  'pvent_immeuble' => 0.0,     'pvent_maison' => 0.0   ],

    // Ventilation hybride (§5 p.42) : Pvent = SF auto 2001-2012 × ratio_temps_méca
    // Collectif ratio=0,167 → 0,46×0,167≈0,07682 W/(m³/h)
    // Individuel ratio=0,083 → 65×0,083≈5,395 W-ThC
    26 => ['qvarep' => 1.52, 'qvasouf' => 0.00, 'smea' => 3.0,  'pvent_immeuble' => 0.07682, 'pvent_maison' => 5.395 ], // Hybride < 2001
    27 => ['qvarep' => 1.33, 'qvasouf' => 0.00, 'smea' => 3.0,  'pvent_immeuble' => 0.07682, 'pvent_maison' => 5.395 ], // Hybride 2001-2012
    28 => ['qvarep' => 1.17, 'qvasouf' => 0.00, 'smea' => 3.0,  'pvent_immeuble' => 0.07682, 'pvent_maison' => 5.395 ], // Hybride après 2012

    // Ventilation hybride avec entrées d'air hygro
    29 => ['qvarep' => 1.52, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.07682, 'pvent_maison' => 5.395 ], // Hybride hygro < 2001
    30 => ['qvarep' => 1.33, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.07682, 'pvent_maison' => 5.395 ], // Hybride hygro 2001-2012
    31 => ['qvarep' => 1.17, 'qvasouf' => 0.00, 'smea' => 2.0,  'pvent_immeuble' => 0.07682, 'pvent_maison' => 5.395 ], // Hybride hygro après 2012

    // Ventilation mécanique sur conduit existant (assimilé SF auto)
    32 => ['qvarep' => 2.24, 'qvasouf' => 0.00, 'smea' => 4.0,  'pvent_immeuble' => 0.46,    'pvent_maison' => 65.0  ], // Sur conduit ≤ 2012
    33 => ['qvarep' => 1.97, 'qvasouf' => 0.00, 'smea' => 4.0,  'pvent_immeuble' => 0.25,    'pvent_maison' => 35.0  ], // Sur conduit après 2012

    // Ventilation naturelle par conduit avec entrées d'air hygro — pas de moteur
    34 => ['qvarep' => 2.23, 'qvasouf' => 0.00, 'smea' => 3.0,  'pvent_immeuble' => 0.0,     'pvent_maison' => 0.0   ],

    // Puits climatique (assimilé DF, §4 p.40 — correction température intégrée dans les débits)
    35 => ['qvarep' => 0.99, 'qvasouf' => 0.99, 'smea' => 0.0,  'pvent_immeuble' => 1.10,    'pvent_maison' => 80.0  ], // Puits climatique sans échangeur ≤ 2012
    36 => ['qvarep' => 0.79, 'qvasouf' => 0.79, 'smea' => 0.0,  'pvent_immeuble' => 0.60,    'pvent_maison' => 35.0  ], // Puits climatique sans échangeur après 2012
    37 => ['qvarep' => 0.36, 'qvasouf' => 0.36, 'smea' => 0.0,  'pvent_immeuble' => 1.10,    'pvent_maison' => 80.0  ], // Puits climatique avec échangeur ≤ 2012
    38 => ['qvarep' => 0.16, 'qvasouf' => 0.16, 'smea' => 0.0,  'pvent_immeuble' => 0.60,    'pvent_maison' => 35.0  ], // Puits climatique avec échangeur après 2012
];
