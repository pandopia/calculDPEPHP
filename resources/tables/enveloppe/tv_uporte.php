<?php

declare(strict_types=1);

/**
 * Coefficient U_porte en W/(m².K), par enum_type_porte_id (16 valeurs).
 *
 * Digitalisation **complète** de la table §3.3.4 p.32 :
 *
 *   | type bois/PVC simple   | opaque pleine             → 3.5  | <30% vitrage simple → 4.0   | 30-60% vit. simple → 4.5  | double vitrage → 3.3 |
 *   | métal simple           | opaque pleine             → 5.8  | vitrage simple      → 5.8   | <30% dbl vitrage   → 5.5  | 30-60% dbl vitrage → 4.8 |
 *   | toute menuiserie       | opaque pleine isolée      → 1.5  | porte avec sas      → 1.5   | dbl vitrage isolé  → 1.5  |
 *
 * La spec ne fournit pas explicitement la valeur des cellules "porte PVC" — on les
 * aligne sur "porte bois" (la table indique « Porte simple en bois ou PVC » sur la même ligne).
 *
 * Le type 16 (« autre porte ») n'est pas dans la spec : on prend la valeur conservatrice 3.5
 * et on lève dans le Calculator si une autre interprétation est attendue.
 *
 * @spec-section 3.3.4
 * @spec-pages 32
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/03-parois-vitrees-portes/04-uporte/00-calcul.md
 * @generated-on 2026-04-29
 *
 * Format : enum_type_porte_id => Uporte (W/(m².K))
 */
return [
    1  => 3.50, // bois  — opaque pleine
    2  => 4.00, // bois  — <30% vitrage simple
    3  => 4.50, // bois  — 30-60% vitrage simple
    4  => 3.30, // bois  — double vitrage
    5  => 3.50, // PVC   — opaque pleine
    6  => 4.00, // PVC   — <30% vitrage simple
    7  => 4.50, // PVC   — 30-60% vitrage simple
    8  => 3.30, // PVC   — double vitrage
    9  => 5.80, // métal — opaque pleine
    10 => 5.80, // métal — vitrage simple
    11 => 5.50, // métal — <30% double vitrage
    12 => 4.80, // métal — 30-60% double vitrage
    13 => 1.50, // toute menuiserie — opaque pleine isolée
    14 => 1.50, // toute menuiserie — porte précédée d'un SAS
    15 => 1.50, // toute menuiserie — isolée avec double vitrage
    16 => 3.50, // « autre type de porte » — fallback conservateur (cf. doc-block)
];
