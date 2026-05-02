<?php

declare(strict_types=1);

/**
 * Rendement de distribution ECS Rd — §11.5 p.73-74.
 *
 * Structure : $table[tv_rendement_distribution_ecs_id] → float (Rd)
 *
 * Individuel (§11.5.1 p.73) :
 *   1 = Production en volume habitable, pièces contiguës                        → 0,93
 *   2 = Production en volume habitable, pièces non contiguës                    → 0,87
 *   3 = Production hors volume habitable                                        → 0,83
 *
 * Collectif (§11.5.2 p.73-74) :
 *   4 = Réseau non isolé, pièces contiguës                                      → 0,28
 *   5 = Réseau non isolé, pièces non contiguës                                  → 0,26
 *   6 = Réseau isolé sans traçage, pièces contiguës                             → 0,55
 *   7 = Réseau isolé sans traçage, pièces non contiguës                         → 0,52
 *   8 = Réseau isolé avec traçage                                               → 0,83
 *
 * @spec-section 11.5
 * @spec-pages   73-74
 * @spec-source  resources/specsplitted/11-conso-ecs/05-rendement-distribution.md
 * @generated-on 2026-04-29
 */
return [
    1 => 0.93, // individuel, vol. habitable, pièces contiguës    p.73
    2 => 0.87, // individuel, vol. habitable, pièces non contiguës p.73
    3 => 0.83, // individuel, hors vol. habitable                  p.73
    4 => 0.28, // collectif, réseau non isolé, contiguës           p.74
    5 => 0.26, // collectif, réseau non isolé, non contiguës       p.74
    6 => 0.55, // collectif, réseau isolé sans traçage, contiguës  p.74
    7 => 0.52, // collectif, réseau isolé sans traçage, non cont.  p.74
    8 => 0.83, // collectif, réseau isolé avec traçage             p.74
];
