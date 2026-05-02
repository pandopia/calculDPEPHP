<?php

declare(strict_types=1);

/**
 * Coefficient de transmission thermique Uph0 du plancher haut non isolé, en W/(m².K).
 *
 * Vérifié vs open3cl tv.js (source de vérité pour l'association enum→Uph0) :
 *   1  inconnu                                  → 2.5
 *   2  plafond avec/sans remplissage            → 1.45
 *   3  plafond entre solives métalliques        → 1.45
 *   4  plafond entre solives bois               → 1.2
 *   5  plafond bois sur solives métalliques     → 2.5
 *   6  plafond bois sous solives métalliques    → 2.5
 *   7  bardeaux et remplissage                  → 1.2
 *   8  dalle béton                              → 2.5
 *   9  plafond bois sur solives bois            → 2.0
 *   10 plafond bois sous solives bois           → 2.3
 *   11 entrevous TC, poutrelles béton           → 2.5
 *   12 combles aménagés sous rampant            → 2.5
 *   13 toiture en chaume                        → 0.24
 *   14 plafond en plaque de plâtre              → 2.5
 *   15 autre type non répertorié                → 2.5
 *   16 toitures en bac acier (=combles)         → 2.5
 *
 * @spec-section 3.2.3.2
 * @spec-pages 22
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/03-uph/02-calcul-uph0.md
 * @generated-on 2026-04-30
 */
return [
    1  => 2.50,  // inconnu
    2  => 1.45,  // plafond avec ou sans remplissage
    3  => 1.45,  // plafond entre solives métalliques avec ou sans remplissage
    4  => 1.20,  // plafond entre solives bois avec ou sans remplissage
    5  => 2.50,  // plafond bois sur solives métalliques
    6  => 2.50,  // plafond bois sous solives métalliques
    7  => 1.20,  // bardeaux et remplissage
    8  => 2.50,  // dalle béton
    9  => 2.00,  // plafond bois sur solives bois
    10 => 2.30,  // plafond bois sous solives bois
    11 => 2.50,  // plafond lourd type entrevous terre-cuite, poutrelles béton
    12 => 2.50,  // combles aménagés sous rampant
    13 => 0.24,  // toiture en chaume
    14 => 2.50,  // plafond en plaque de plâtre
    15 => 2.50,  // autre type non répertorié
    16 => 2.50,  // toitures en bac acier (traité comme combles)
];
