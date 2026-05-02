<?php

declare(strict_types=1);

/**
 * Valeur conventionnelle de la perméabilité sous 4 Pa — §4 p.39.
 *
 * Indexé par `tv_q4pa_conv_id` (1…12).
 *
 * IDs 1-4  : Appartement / Immeuble (selon période de construction)
 * IDs 5-9  : Maison individuelle
 * IDs 10-12 : Cas spéciaux avec isolation ou menuiseries avec joints
 *
 * Tableau spec p.39 :
 *
 *   Appartement/Immeuble                         Maison
 *   Avant 1948 | 1948-1974 | 1975-2012 | >2012 | Avant 1948 | 1948-1974 | 1975-2005 | 2006-2012 | >2012
 *       4,6    |    2,0    |    1,5    |  1,0  |    3,3     |    2,2    |    1,9    |    1,3    |  0,6
 *
 * Cas spéciaux (§4 p.39-40) :
 *   - Bâtiments/logements avant 1948 avec isolation ≥50% des surfaces murs/plafond :
 *       Q4Paconv/m² = 2,0 (immeuble) — ID 10
 *   - Bâtiments/logements 1948-1974 avec isolation ≥50% des surfaces murs/plafond :
 *       Q4Paconv/m² = 1,9 (immeuble) — ID 11
 *   - Bâtiments/logements avant 1948 avec menuiseries avec joints (>50% des surfaces) :
 *       Q4Paconv/m² = 2,5 (immeuble) — ID 12
 *
 * @spec-section 4
 * @spec-pages 39
 * @spec-source resources/specsplitted/04-renouvellement-air/00-calcul.md
 * @generated-on 2026-04-29
 */
return [
    // Appartement / Immeuble
    1  => 4.6,   // Avant 1948
    2  => 2.0,   // 1948 - 1974
    3  => 1.5,   // 1975 - 2012
    4  => 1.0,   // > 2012

    // Maison individuelle
    5  => 3.3,   // Avant 1948
    6  => 2.2,   // 1948 - 1974
    7  => 1.9,   // 1975 - 2005
    8  => 1.3,   // 2006 - 2012
    9  => 0.6,   // > 2012

    // Cas spéciaux immeuble/logement avec isolation ou joints
    10 => 2.0,   // Avant 1948 avec isolation murs/plafond ≥ 50 %
    11 => 1.9,   // 1948-1974 avec isolation murs/plafond ≥ 50 %
    12 => 2.5,   // Avant 1948 avec menuiseries avec joints sur > 50 % des surfaces
];
