<?php

declare(strict_types=1);

/**
 * Coefficients ki — §16.2 p.104, indexés par paramètres physiques XSD.
 *
 * $table[enum_orientation_pv_id][enum_inclinaison_pv_id]
 * Orientations : 1 Est, 2 Sud-Est, 3 Sud, 4 Sud-Ouest, 5 Ouest.
 * Inclinaisons : 1 ≤15°, 2 ]15°;45°], 3 ]45°;75°], 4 >75°.
 * Les 20 combinaisons du tableau réglementaire sont couvertes.
 * Aucun identifiant tv_* propre au logiciel émetteur n'est interprété.
 *
 * @spec-section 16.2
 * @spec-pages 104
 * @spec-source resources/specsplitted/16-eclairage-prod-elec/02-prod-electricite.md
 */
return [
    1 => [1 => 1.00, 2 => 0.96, 3 => 0.83, 4 => 0.59],
    2 => [1 => 1.00, 2 => 1.03, 3 => 0.94, 4 => 0.71],
    3 => [1 => 1.00, 2 => 1.07, 3 => 0.97, 4 => 0.73],
    4 => [1 => 1.00, 2 => 1.03, 3 => 0.94, 4 => 0.71],
    5 => [1 => 1.00, 2 => 0.96, 3 => 0.83, 4 => 0.59],
];
