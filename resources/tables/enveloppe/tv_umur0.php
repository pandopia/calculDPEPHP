<?php

declare(strict_types=1);

/**
 * Coefficient de transmission thermique Umur0 du mur non isolé (W/(m².K)),
 * indexé par (enum_materiaux_structure_mur_id, épaisseur en cm).
 *
 * Digitalisation **complète** des tableaux §3.2.1.2 p.14-16. Pour chaque matériau,
 * une suite de plages d'épaisseur croissantes ; la dernière utilise PHP_FLOAT_MAX
 * pour le cas "≥ N cm".
 *
 * Lookup : pour `(materiau, epaisseur)`, on prend la première ligne dont
 * `epaisseur_max_cm >= epaisseur` parmi les lignes du même matériau.
 *
 * Cas spéciaux qui ne suivent pas le format (matériau, épaisseur) :
 *   - matériau 20 (cloison de plâtre)             → 3.33 W/(m².K), traité dans Umur0Calculator
 *   - matériau 1  (inconnu)                       → 2.50 (forfait, traité dans Calculator)
 *   - matériaux 21, 22, 23 (autres)               → 2.50 (forfait, traité dans Calculator)
 *
 * @spec-section 3.2.1.2
 * @spec-pages 14-16
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/01-umur/02-calcul-umur0.md
 * @generated-on 2026-04-29
 */
return [
    // 2 — Pierre de taille / moellons constitué d'un seul matériau (p.14)
    ['materiau' => 2, 'epaisseur_max_cm' => 20.0,           'umur0' => 3.20],
    ['materiau' => 2, 'epaisseur_max_cm' => 25.0,           'umur0' => 2.85],
    ['materiau' => 2, 'epaisseur_max_cm' => 30.0,           'umur0' => 2.65],
    ['materiau' => 2, 'epaisseur_max_cm' => 35.0,           'umur0' => 2.45],
    ['materiau' => 2, 'epaisseur_max_cm' => 40.0,           'umur0' => 2.30],
    ['materiau' => 2, 'epaisseur_max_cm' => 45.0,           'umur0' => 2.15],
    ['materiau' => 2, 'epaisseur_max_cm' => 50.0,           'umur0' => 2.05],
    ['materiau' => 2, 'epaisseur_max_cm' => 55.0,           'umur0' => 1.90],
    ['materiau' => 2, 'epaisseur_max_cm' => 60.0,           'umur0' => 1.80],
    ['materiau' => 2, 'epaisseur_max_cm' => 65.0,           'umur0' => 1.75],
    ['materiau' => 2, 'epaisseur_max_cm' => 70.0,           'umur0' => 1.65],
    ['materiau' => 2, 'epaisseur_max_cm' => 75.0,           'umur0' => 1.55],
    ['materiau' => 2, 'epaisseur_max_cm' => PHP_FLOAT_MAX,  'umur0' => 1.50],

    // 3 — Pierre de taille / moellons avec remplissage tout venant (p.14)
    // (lignes "≤45cm" non répertoriées dans le tableau spec → conservation du Umur0 du matériau seul ci-dessus n'est pas indiquée)
    // La spec donne uniquement à partir de 50cm.
    ['materiau' => 3, 'epaisseur_max_cm' => 50.0,           'umur0' => 1.90],
    ['materiau' => 3, 'epaisseur_max_cm' => 55.0,           'umur0' => 1.75],
    ['materiau' => 3, 'epaisseur_max_cm' => 60.0,           'umur0' => 1.60],
    ['materiau' => 3, 'epaisseur_max_cm' => 65.0,           'umur0' => 1.50],
    ['materiau' => 3, 'epaisseur_max_cm' => 70.0,           'umur0' => 1.45],
    ['materiau' => 3, 'epaisseur_max_cm' => 75.0,           'umur0' => 1.30],
    ['materiau' => 3, 'epaisseur_max_cm' => PHP_FLOAT_MAX,  'umur0' => 1.25],

    // 4 — Murs en pisé ou béton de terre stabilisé (p.14)
    ['materiau' => 4, 'epaisseur_max_cm' => 40.0,           'umur0' => 1.75],
    ['materiau' => 4, 'epaisseur_max_cm' => 45.0,           'umur0' => 1.65],
    ['materiau' => 4, 'epaisseur_max_cm' => 50.0,           'umur0' => 1.55],
    ['materiau' => 4, 'epaisseur_max_cm' => 55.0,           'umur0' => 1.45],
    ['materiau' => 4, 'epaisseur_max_cm' => 60.0,           'umur0' => 1.35],
    ['materiau' => 4, 'epaisseur_max_cm' => 65.0,           'umur0' => 1.25],
    ['materiau' => 4, 'epaisseur_max_cm' => 70.0,           'umur0' => 1.20],
    ['materiau' => 4, 'epaisseur_max_cm' => 75.0,           'umur0' => 1.15],
    ['materiau' => 4, 'epaisseur_max_cm' => PHP_FLOAT_MAX,  'umur0' => 1.10],

    // 5 — Murs en pan de bois sans remplissage tout venant (p.14)
    ['materiau' => 5, 'epaisseur_max_cm' => 8.0,            'umur0' => 3.00],
    ['materiau' => 5, 'epaisseur_max_cm' => 10.0,           'umur0' => 2.70],
    ['materiau' => 5, 'epaisseur_max_cm' => 13.0,           'umur0' => 2.35],
    ['materiau' => 5, 'epaisseur_max_cm' => 18.0,           'umur0' => 1.98],
    ['materiau' => 5, 'epaisseur_max_cm' => 24.0,           'umur0' => 1.65],
    ['materiau' => 5, 'epaisseur_max_cm' => PHP_FLOAT_MAX,  'umur0' => 1.35],

    // 6 — Murs en pan de bois avec remplissage tout venant (p.14)
    // La spec donne une seule valeur 1,7 sans plage explicite — appliquée toutes épaisseurs.
    ['materiau' => 6, 'epaisseur_max_cm' => PHP_FLOAT_MAX,  'umur0' => 1.70],

    // 7 — Murs bois (rondins) (p.14)
    ['materiau' => 7, 'epaisseur_max_cm' => 10.0,           'umur0' => 1.60],
    ['materiau' => 7, 'epaisseur_max_cm' => 15.0,           'umur0' => 1.20],
    ['materiau' => 7, 'epaisseur_max_cm' => 20.0,           'umur0' => 0.95],
    ['materiau' => 7, 'epaisseur_max_cm' => PHP_FLOAT_MAX,  'umur0' => 0.80],

    // 8 — Murs en briques pleines simples (p.14)
    ['materiau' => 8, 'epaisseur_max_cm' => 9.0,            'umur0' => 3.90],
    ['materiau' => 8, 'epaisseur_max_cm' => 12.0,           'umur0' => 3.45],
    ['materiau' => 8, 'epaisseur_max_cm' => 15.0,           'umur0' => 3.05],
    ['materiau' => 8, 'epaisseur_max_cm' => 19.0,           'umur0' => 2.75],
    ['materiau' => 8, 'epaisseur_max_cm' => 23.0,           'umur0' => 2.50],
    ['materiau' => 8, 'epaisseur_max_cm' => 28.0,           'umur0' => 2.25],
    ['materiau' => 8, 'epaisseur_max_cm' => 34.0,           'umur0' => 2.00],
    ['materiau' => 8, 'epaisseur_max_cm' => 45.0,           'umur0' => 1.65],
    ['materiau' => 8, 'epaisseur_max_cm' => 55.0,           'umur0' => 1.45],
    ['materiau' => 8, 'epaisseur_max_cm' => 60.0,           'umur0' => 1.35],
    ['materiau' => 8, 'epaisseur_max_cm' => PHP_FLOAT_MAX,  'umur0' => 1.20],

    // 9 — Murs en briques pleines doubles avec lame d'air (p.14)
    ['materiau' => 9, 'epaisseur_max_cm' => 20.0,           'umur0' => 2.00],
    ['materiau' => 9, 'epaisseur_max_cm' => 25.0,           'umur0' => 1.85],
    ['materiau' => 9, 'epaisseur_max_cm' => 30.0,           'umur0' => 1.65],
    ['materiau' => 9, 'epaisseur_max_cm' => 35.0,           'umur0' => 1.55],
    ['materiau' => 9, 'epaisseur_max_cm' => 45.0,           'umur0' => 1.35],
    ['materiau' => 9, 'epaisseur_max_cm' => 50.0,           'umur0' => 1.25],
    ['materiau' => 9, 'epaisseur_max_cm' => PHP_FLOAT_MAX,  'umur0' => 1.20],

    // 10 — Murs en briques creuses (p.14)
    ['materiau' => 10, 'epaisseur_max_cm' => 15.0,          'umur0' => 2.15],
    ['materiau' => 10, 'epaisseur_max_cm' => 18.0,          'umur0' => 2.05],
    ['materiau' => 10, 'epaisseur_max_cm' => 20.0,          'umur0' => 2.00],
    ['materiau' => 10, 'epaisseur_max_cm' => 23.0,          'umur0' => 1.85],
    ['materiau' => 10, 'epaisseur_max_cm' => 25.0,          'umur0' => 1.70],
    ['materiau' => 10, 'epaisseur_max_cm' => 28.0,          'umur0' => 1.68],
    ['materiau' => 10, 'epaisseur_max_cm' => 33.0,          'umur0' => 1.65],
    ['materiau' => 10, 'epaisseur_max_cm' => 38.0,          'umur0' => 1.55],
    ['materiau' => 10, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 1.40],

    // 11 — Murs en blocs de béton pleins (p.14)
    ['materiau' => 11, 'epaisseur_max_cm' => 20.0,          'umur0' => 2.90],
    ['materiau' => 11, 'epaisseur_max_cm' => 23.0,          'umur0' => 2.75],
    ['materiau' => 11, 'epaisseur_max_cm' => 25.0,          'umur0' => 2.60],
    ['materiau' => 11, 'epaisseur_max_cm' => 28.0,          'umur0' => 2.50],
    ['materiau' => 11, 'epaisseur_max_cm' => 30.0,          'umur0' => 2.40],
    ['materiau' => 11, 'epaisseur_max_cm' => 33.0,          'umur0' => 2.30],
    ['materiau' => 11, 'epaisseur_max_cm' => 35.0,          'umur0' => 2.20],
    ['materiau' => 11, 'epaisseur_max_cm' => 38.0,          'umur0' => 2.10],
    ['materiau' => 11, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 2.05],

    // 12 — Murs en blocs de béton creux (p.15)
    ['materiau' => 12, 'epaisseur_max_cm' => 20.0,          'umur0' => 2.80],
    ['materiau' => 12, 'epaisseur_max_cm' => 23.0,          'umur0' => 2.65],
    ['materiau' => 12, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 2.30],

    // 13 — Murs en béton banché (p.15)
    ['materiau' => 13, 'epaisseur_max_cm' => 20.0,          'umur0' => 2.90],
    ['materiau' => 13, 'epaisseur_max_cm' => 22.5,          'umur0' => 2.75],
    ['materiau' => 13, 'epaisseur_max_cm' => 25.0,          'umur0' => 2.65],
    ['materiau' => 13, 'epaisseur_max_cm' => 28.0,          'umur0' => 2.50],
    ['materiau' => 13, 'epaisseur_max_cm' => 30.0,          'umur0' => 2.40],
    ['materiau' => 13, 'epaisseur_max_cm' => 35.0,          'umur0' => 2.20],
    ['materiau' => 13, 'epaisseur_max_cm' => 40.0,          'umur0' => 2.05],
    ['materiau' => 13, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 1.90],

    // 14 — Murs en béton de mâchefer (p.15)
    ['materiau' => 14, 'epaisseur_max_cm' => 20.0,          'umur0' => 2.75],
    ['materiau' => 14, 'epaisseur_max_cm' => 22.5,          'umur0' => 2.50],
    ['materiau' => 14, 'epaisseur_max_cm' => 25.0,          'umur0' => 2.40],
    ['materiau' => 14, 'epaisseur_max_cm' => 28.0,          'umur0' => 2.25],
    ['materiau' => 14, 'epaisseur_max_cm' => 30.0,          'umur0' => 2.15],
    ['materiau' => 14, 'epaisseur_max_cm' => 35.0,          'umur0' => 1.95],
    ['materiau' => 14, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 1.80],

    // 15 — Brique terre cuite alvéolaire (p.15)
    ['materiau' => 15, 'epaisseur_max_cm' => 30.0,          'umur0' => 0.47],
    ['materiau' => 15, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 0.40],

    // 16 — Béton cellulaire avant 2013 (p.15)
    ['materiau' => 16, 'epaisseur_max_cm' => 15.0,          'umur0' => 0.90],
    ['materiau' => 16, 'epaisseur_max_cm' => 17.5,          'umur0' => 0.79],
    ['materiau' => 16, 'epaisseur_max_cm' => 20.0,          'umur0' => 0.70],
    ['materiau' => 16, 'epaisseur_max_cm' => 22.5,          'umur0' => 0.63],
    ['materiau' => 16, 'epaisseur_max_cm' => 25.0,          'umur0' => 0.57],
    ['materiau' => 16, 'epaisseur_max_cm' => 27.5,          'umur0' => 0.53],
    ['materiau' => 16, 'epaisseur_max_cm' => 30.0,          'umur0' => 0.49],
    ['materiau' => 16, 'epaisseur_max_cm' => 32.5,          'umur0' => 0.45],
    ['materiau' => 16, 'epaisseur_max_cm' => 35.0,          'umur0' => 0.42],
    ['materiau' => 16, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 0.40],

    // 17 — Béton cellulaire à partir de 2013 (p.15)
    ['materiau' => 17, 'epaisseur_max_cm' => 15.0,          'umur0' => 0.69],
    ['materiau' => 17, 'epaisseur_max_cm' => 17.5,          'umur0' => 0.60],
    ['materiau' => 17, 'epaisseur_max_cm' => 20.0,          'umur0' => 0.53],
    ['materiau' => 17, 'epaisseur_max_cm' => 22.5,          'umur0' => 0.48],
    ['materiau' => 17, 'epaisseur_max_cm' => 25.0,          'umur0' => 0.43],
    ['materiau' => 17, 'epaisseur_max_cm' => 27.5,          'umur0' => 0.40],
    ['materiau' => 17, 'epaisseur_max_cm' => 30.0,          'umur0' => 0.36],
    ['materiau' => 17, 'epaisseur_max_cm' => 32.5,          'umur0' => 0.30],
    ['materiau' => 17, 'epaisseur_max_cm' => 35.0,          'umur0' => 0.28],
    ['materiau' => 17, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 0.22],

    // 19 — Murs sandwich béton/isolant/béton (sans isolation rapportée) (p.15)
    ['materiau' => 19, 'epaisseur_max_cm' => 15.0,          'umur0' => 0.90],
    ['materiau' => 19, 'epaisseur_max_cm' => 20.0,          'umur0' => 0.48],
    ['materiau' => 19, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 0.45],

    // 18 — Murs en ossature bois avec isolant en remplissage ≥ 2006 (p.15)
    ['materiau' => 18, 'epaisseur_max_cm' => 10.0,          'umur0' => 0.45],
    ['materiau' => 18, 'epaisseur_max_cm' => 15.0,          'umur0' => 0.35],
    ['materiau' => 18, 'epaisseur_max_cm' => 20.0,          'umur0' => 0.26],
    ['materiau' => 18, 'epaisseur_max_cm' => 25.0,          'umur0' => 0.21],
    ['materiau' => 18, 'epaisseur_max_cm' => 30.0,          'umur0' => 0.17],
    ['materiau' => 18, 'epaisseur_max_cm' => 35.0,          'umur0' => 0.15],
    ['materiau' => 18, 'epaisseur_max_cm' => 40.0,          'umur0' => 0.13],
    ['materiau' => 18, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 0.11],

    // 24 — Murs en ossature bois avec isolant en remplissage 2001-2005 (p.15)
    ['materiau' => 24, 'epaisseur_max_cm' => 10.0,          'umur0' => 0.52],
    ['materiau' => 24, 'epaisseur_max_cm' => 15.0,          'umur0' => 0.41],
    ['materiau' => 24, 'epaisseur_max_cm' => 20.0,          'umur0' => 0.30],
    ['materiau' => 24, 'epaisseur_max_cm' => 25.0,          'umur0' => 0.24],
    ['materiau' => 24, 'epaisseur_max_cm' => 30.0,          'umur0' => 0.20],
    ['materiau' => 24, 'epaisseur_max_cm' => 35.0,          'umur0' => 0.17],
    ['materiau' => 24, 'epaisseur_max_cm' => 40.0,          'umur0' => 0.15],
    ['materiau' => 24, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 0.13],

    // 26 — Murs en ossature bois avec isolant en remplissage <2001 (p.15)
    ['materiau' => 26, 'epaisseur_max_cm' => 10.0,          'umur0' => 0.65],
    ['materiau' => 26, 'epaisseur_max_cm' => 15.0,          'umur0' => 0.45],
    ['materiau' => 26, 'epaisseur_max_cm' => 20.0,          'umur0' => 0.34],
    ['materiau' => 26, 'epaisseur_max_cm' => 25.0,          'umur0' => 0.28],
    ['materiau' => 26, 'epaisseur_max_cm' => 30.0,          'umur0' => 0.23],
    ['materiau' => 26, 'epaisseur_max_cm' => 35.0,          'umur0' => 0.20],
    ['materiau' => 26, 'epaisseur_max_cm' => 40.0,          'umur0' => 0.18],
    ['materiau' => 26, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 0.16],

    // 27 — Murs en ossature bois avec remplissage tout venant (p.16)
    // Une seule valeur 1,7 toute épaisseur dans la spec.
    ['materiau' => 27, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 1.70],

    // 25 — Murs en ossature bois sans remplissage (p.16)
    ['materiau' => 25, 'epaisseur_max_cm' => 8.0,           'umur0' => 3.00],
    ['materiau' => 25, 'epaisseur_max_cm' => 10.0,          'umur0' => 2.70],
    ['materiau' => 25, 'epaisseur_max_cm' => 13.0,          'umur0' => 2.35],
    ['materiau' => 25, 'epaisseur_max_cm' => 18.0,          'umur0' => 1.98],
    ['materiau' => 25, 'epaisseur_max_cm' => 24.0,          'umur0' => 1.65],
    ['materiau' => 25, 'epaisseur_max_cm' => PHP_FLOAT_MAX, 'umur0' => 1.35],
];
