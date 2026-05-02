<?php

/**
 * Facteur de couverture solaire par zone climatique (§18.4 p.143).
 *
 * Indexé par zone_id (1=H1a, 2=H1b, 3=H1c, 4=H2a, 5=H2b, 6=H2c, 7=H2d, 8=H3).
 * Chaque entrée contient les 6 colonnes définies §18.4 :
 *   - fch : chauffage seul ou combiné (maison individuelle)
 *   - fecs_maison_gt5 : ECS seule maison > 5 ans
 *   - fecs_maison_le5 : ECS seule maison ≤ 5 ans
 *   - fecs_combi     : chauffage + ECS solaire (maison)
 *   - fecs_collectif_gt5 : ECS seule immeuble collectif > 5 ans
 *   - fecs_collectif_le5 : ECS seule immeuble collectif ≤ 5 ans
 *
 * @spec-section 18.4
 * @spec-pages   143
 * @spec-source  resources/specsplitted/18-annexes/04-facteur-couverture-solaire/00-texte.md
 * @generated-on 2026-04-30
 */
return [
    '1' => ['zone' => 'H1a', 'fch' => 0.25, 'fecs_maison_gt5' => 0.49, 'fecs_maison_le5' => 0.63, 'fecs_combi' => 0.87, 'fecs_collectif_gt5' => 0.26, 'fecs_collectif_le5' => 0.38],  // p.143
    '2' => ['zone' => 'H1b', 'fch' => 0.22, 'fecs_maison_gt5' => 0.50, 'fecs_maison_le5' => 0.64, 'fecs_combi' => 0.88, 'fecs_collectif_gt5' => 0.27, 'fecs_collectif_le5' => 0.40],  // p.143
    '3' => ['zone' => 'H1c', 'fch' => 0.28, 'fecs_maison_gt5' => 0.53, 'fecs_maison_le5' => 0.68, 'fecs_combi' => 0.90, 'fecs_collectif_gt5' => 0.31, 'fecs_collectif_le5' => 0.45],  // p.143
    '4' => ['zone' => 'H2a', 'fch' => 0.34, 'fecs_maison_gt5' => 0.51, 'fecs_maison_le5' => 0.66, 'fecs_combi' => 0.90, 'fecs_collectif_gt5' => 0.28, 'fecs_collectif_le5' => 0.41],  // p.143
    '5' => ['zone' => 'H2b', 'fch' => 0.33, 'fecs_maison_gt5' => 0.54, 'fecs_maison_le5' => 0.69, 'fecs_combi' => 0.91, 'fecs_collectif_gt5' => 0.32, 'fecs_collectif_le5' => 0.46],  // p.143
    '6' => ['zone' => 'H2c', 'fch' => 0.38, 'fecs_maison_gt5' => 0.58, 'fecs_maison_le5' => 0.74, 'fecs_combi' => 0.95, 'fecs_collectif_gt5' => 0.35, 'fecs_collectif_le5' => 0.50],  // p.143
    '7' => ['zone' => 'H2d', 'fch' => 0.39, 'fecs_maison_gt5' => 0.61, 'fecs_maison_le5' => 0.77, 'fecs_combi' => 0.96, 'fecs_collectif_gt5' => 0.38, 'fecs_collectif_le5' => 0.56],  // p.143
    '8' => ['zone' => 'H3',  'fch' => 0.52, 'fecs_maison_gt5' => 0.64, 'fecs_maison_le5' => 0.80, 'fecs_combi' => 0.98, 'fecs_collectif_gt5' => 0.40, 'fecs_collectif_le5' => 0.58],  // p.143
];
