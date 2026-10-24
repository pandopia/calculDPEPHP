<?php

declare(strict_types=1);

/**
 * Transparence T des espaces tampons solarisés — §6.3 p.51–52.
 *
 * Valeurs physiques relues dans le PDF. Index public ADEME : feuille
 * coef_transparence_ets A2:G22 du classeur officiel, et non ordre du PDF.
 * La ligne polycarbonate vient en premier (ID 1), avant les quatre familles
 * de cinq vitrages. Aucun paramètre vitrage/menuiserie direct n'est présent
 * dans donnee_entree de l'ETS : son ID est celui de cette table publiée.
 * Il ne s'agit pas d'une correspondance interne à un diagnostiqueur.
 *
 * @spec-section 6.3
 * @spec-pages 51-52
 * @spec-source resources/specsplitted/06-apports-gratuits/03-espaces-tampons-solarises.md
 * @spec-source https://gitlab.com/observatoire-dpe/observatoire-dpe/-/blob/52792fb9e79e52390ded7d4527a7f7a962fd5ccc/modele_donnee/valeur_tables.xlsx (coef_transparence_ets A2:G22)
 * @generated-on 2026-10-25
 * @status verified-spec — 21 valeurs et toutes les correspondances publiques ADEME
 */
return [
    1 => 0.40, // Polycarbonate — p.52, classeur ligne 2

    // Bois / Bois-métal — p.51, classeur lignes 3–7
    2 => 0.62, // simple
    3 => 0.55, // double
    4 => 0.48, // double peu émissif
    5 => 0.49, // triple
    6 => 0.44, // triple peu émissif

    // PVC — p.51–52, classeur lignes 8–12
    7 => 0.50,
    8 => 0.45,
    9 => 0.39,
    10 => 0.40,
    11 => 0.36,

    // Métal avec rupture — p.52, classeur lignes 13–17
    12 => 0.63,
    13 => 0.56,
    14 => 0.48,
    15 => 0.50,
    16 => 0.45,

    // Métal sans rupture — p.52, classeur lignes 18–22
    17 => 0.64,
    18 => 0.58,
    19 => 0.50,
    20 => 0.52,
    21 => 0.47,
];
