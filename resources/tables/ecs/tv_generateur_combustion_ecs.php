<?php

declare(strict_types=1);

/**
 * Caractéristiques des générateurs ECS à combustion — §14.1 p.93-95.
 *
 * Indexé par enum_type_generateur_ecs_id.
 * Chaque entrée est un tableau ordonné de paliers Pn (ordre croissant pn_max_kw).
 * Le premier palier dont pn_kW ≤ pn_max_kw est utilisé.
 *
 * Champs par palier :
 *   'pn_max_kw' → float (seuil Pn supérieur inclus, INF pour le dernier palier)
 *   'rpn'       → float (fraction 0-1) ou Closure($pn_kW):float
 *   'qp0_pct'   → float (% de Pn) ou null (qp0 à zéro/calculé par formula)
 *   'pveil'     → float W (0 si pas de veilleuse)
 *
 * Note : pour accumulateur gaz (IDs 58-62, 105-109), open3cl surcharge qp0
 * avec `1.5 % × Pn` (§14.1.3) — ce champ est ignoré pour ces types.
 *
 * IDs alias : 110-114 (chauffe-eau gpl/propane/butane) sont traités identiquement
 * aux IDs 63-67 ; 105-109 identiquement aux 58-62.
 *
 * @spec-section 14.1
 * @spec-pages   93-95
 * @spec-source  resources/specsplitted/14-rendement-ecs-generateurs/01-combustion.md
 * @generated-on 2026-04-30
 * @source       open3cl tv.js (IDs 79-93 du tableau generateur_combustion)
 */

// Helpers
$pnFn = static fn (float $coeff, float $add): \Closure =>
    static fn (float $p): float => ($add + $coeff * log10($p)) / 100.0;

$e84_2    = $pnFn(2.0, 84.0);    // (84+2×log10(Pn))/100
$e875_1_5 = $pnFn(1.5, 87.5);   // (87.5+1.5×log10(Pn))/100
$e91_1    = $pnFn(1.0, 91.0);   // (91+log10(Pn))/100
$e91_3    = $pnFn(3.0, 91.0);   // (91+3×log10(Pn))/100
$e94_1    = $pnFn(1.0, 94.0);   // (94+log10(Pn))/100
$e47_6    = $pnFn(6.0, 47.0);   // (47+6×log10(Pn))/100  (bois)
$e57_6    = $pnFn(6.0, 57.0);   // (57+6×log10(Pn))/100  (bois)
$e67_6    = $pnFn(6.0, 67.0);   // (67+6×log10(Pn))/100  (bois)
$e80_2    = $pnFn(2.0, 80.0);   // (80+2×log10(Pn))/100  (bois 2018)
$e89_2    = $pnFn(2.0, 89.0);   // (89+2×log10(Pn))/100  (bois >2019 Pn≤20)
$e90_2    = $pnFn(2.0, 90.0);   // (90+2×log10(Pn))/100  (bois >2019 20<Pn≤70)
$e91_2    = $pnFn(2.0, 91.0);   // (91+2×log10(Pn))/100  (bois granulés >2019 Pn≤20)
$e92_2    = $pnFn(2.0, 92.0);   // (92+2×log10(Pn))/100  (bois granulés >2019 20<Pn≤70)

// qp0 bois (pertes à l'arrêt, valeur absolue en W, % de Pn calculée dynamiquement)
// Pour ces formules, open3cl retourne un pourcentage mais l'applique différemment.
// Pour simplifier, on utilise des qp0_pct null (qp0=0 si non explicitement fourni).

return [
    // ─── Chaudières gaz classique ───────────────────────────────────────────
    // ID 45 : Chaudière gaz classique avant 1981
    45  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.04, 'pveil' => 240.0]],
    92  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.04, 'pveil' => 240.0]],
    // ID 46 : Chaudière gaz classique 1981-1985
    46  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.02, 'pveil' => 150.0]],
    93  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.02, 'pveil' => 150.0]],
    // ID 47 : Chaudière gaz classique 1986-1990
    47  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.015, 'pveil' => 150.0]],
    94  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.015, 'pveil' => 150.0]],
    // ID 48 : Chaudière gaz standard 1991-2000
    48  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.012, 'pveil' => 120.0]],
    95  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.012, 'pveil' => 120.0]],
    // ID 49 : Chaudière gaz standard 2001-2015
    49  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    96  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    // ID 50 : Chaudière gaz standard après 2015
    50  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => null, 'pveil' => 0.0]],
    97  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => null, 'pveil' => 0.0]],
    // ID 51 : Chaudière gaz basse température 1991-2000
    51  => [['pn_max_kw' => INF, 'rpn' => $e875_1_5, 'qp0_pct' => 0.012, 'pveil' => 120.0]],
    98  => [['pn_max_kw' => INF, 'rpn' => $e875_1_5, 'qp0_pct' => 0.012, 'pveil' => 120.0]],
    // ID 52 : Chaudière gaz basse température 2001-2015
    52  => [['pn_max_kw' => INF, 'rpn' => $e875_1_5, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    99  => [['pn_max_kw' => INF, 'rpn' => $e875_1_5, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    // ID 53 : Chaudière gaz basse température après 2015
    53  => [['pn_max_kw' => INF, 'rpn' => $e875_1_5, 'qp0_pct' => null, 'pveil' => 0.0]],
    100 => [['pn_max_kw' => INF, 'rpn' => $e875_1_5, 'qp0_pct' => null, 'pveil' => 0.0]],
    // ID 54 : Chaudière gaz à condensation 1981-1985
    54  => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 150.0]],
    101 => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 150.0]],
    // ID 55 : Chaudière gaz à condensation 1986-2000
    55  => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 120.0]],
    102 => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 120.0]],
    // ID 56 : Chaudière gaz à condensation 2001-2015
    56  => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    103 => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    120 => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    132 => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    // ID 57 : Chaudière gaz à condensation après 2015 (3 paliers Pn)
    57  => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e91_3, 'qp0_pct' => 0.005, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => $e94_1, 'qp0_pct' => 0.003, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.966,  'qp0_pct' => 0.003, 'pveil' => 0.0],
    ],
    104 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e91_3, 'qp0_pct' => 0.005, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => $e94_1, 'qp0_pct' => 0.003, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.966,  'qp0_pct' => 0.003, 'pveil' => 0.0],
    ],
    121 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e91_3, 'qp0_pct' => 0.005, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => $e94_1, 'qp0_pct' => 0.003, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.966,  'qp0_pct' => 0.003, 'pveil' => 0.0],
    ],
    133 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e91_3, 'qp0_pct' => 0.005, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => $e94_1, 'qp0_pct' => 0.003, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.966,  'qp0_pct' => 0.003, 'pveil' => 0.0],
    ],

    // ─── Chaudières fioul ───────────────────────────────────────────────────
    35  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.04,  'pveil' => 0.0]],
    36  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.03,  'pveil' => 0.0]],
    37  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.02,  'pveil' => 0.0]],
    38  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.01,  'pveil' => 0.0]],
    39  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.01,  'pveil' => 0.0]],
    40  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => null,  'pveil' => 0.0]],
    41  => [['pn_max_kw' => INF, 'rpn' => $e875_1_5, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    42  => [['pn_max_kw' => INF, 'rpn' => $e875_1_5, 'qp0_pct' => null, 'pveil' => 0.0]],
    43  => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01,  'pveil' => 0.0]],
    122 => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01,  'pveil' => 0.0]],
    44  => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e91_3, 'qp0_pct' => 0.005, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => $e94_1, 'qp0_pct' => 0.006, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.966,  'qp0_pct' => 0.003, 'pveil' => 0.0],
    ],
    123 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e91_3, 'qp0_pct' => 0.005, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => $e94_1, 'qp0_pct' => 0.006, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.966,  'qp0_pct' => 0.003, 'pveil' => 0.0],
    ],
    // Fioul "système collectif par défaut" (84) maps to chaudière fioul (historique)
    84  => [['pn_max_kw' => INF, 'rpn' => $e84_2, 'qp0_pct' => 0.02,  'pveil' => 0.0]],

    // ─── Chaudières bois ────────────────────────────────────────────────────
    // (rpn bois = fixe pour les paliers >70kW; formule pour Pn≤70)
    15 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    22 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    85 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    16 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    23 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    86 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    13 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    17 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    24 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    87 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    18 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e57_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    25 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e57_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    88 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e57_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    14 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e57_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    19 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e67_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    26 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e67_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    89  => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e67_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    126 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e67_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    129 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e67_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    20 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e80_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    27 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e80_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    90  => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e80_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    127 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e80_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    130 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e80_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    21 => [
        ['pn_max_kw' =>  20.0, 'rpn' => $e89_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' =>  70.0, 'rpn' => $e90_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    28 => [
        ['pn_max_kw' =>  20.0, 'rpn' => $e89_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' =>  70.0, 'rpn' => $e90_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    91  => [
        ['pn_max_kw' =>  20.0, 'rpn' => $e89_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' =>  70.0, 'rpn' => $e90_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    128 => [
        ['pn_max_kw' =>  20.0, 'rpn' => $e89_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' =>  70.0, 'rpn' => $e90_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    131 => [
        ['pn_max_kw' =>  20.0, 'rpn' => $e89_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' =>  70.0, 'rpn' => $e90_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.94,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    // Bois granulés (IDs 29-34)
    29  => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    30  => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    115 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e47_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.58,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    31  => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e57_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.68,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    32  => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e67_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    116 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e67_6, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.78,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    33  => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e80_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    124 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e80_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.84,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    34  => [
        ['pn_max_kw' =>  20.0, 'rpn' => $e91_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' =>  70.0, 'rpn' => $e92_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.96,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.96,   'qp0_pct' => null, 'pveil' => 0.0],
    ],
    125 => [
        ['pn_max_kw' =>  20.0, 'rpn' => $e91_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' =>  70.0, 'rpn' => $e92_2, 'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => 0.96,   'qp0_pct' => null, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.96,   'qp0_pct' => null, 'pveil' => 0.0],
    ],

    // ─── Accumulateur gaz (§14.1.3) ─────────────────────────────────────────
    // qp0 = 1.5% × Pn (override dans CombustionCalculator §14.1.3)
    58  => [['pn_max_kw' => INF, 'rpn' => 0.81, 'qp0_pct' => null, 'pveil' => 200.0]],
    105 => [['pn_max_kw' => INF, 'rpn' => 0.81, 'qp0_pct' => null, 'pveil' => 200.0]],
    59  => [['pn_max_kw' => INF, 'rpn' => 0.84, 'qp0_pct' => null, 'pveil' => 150.0]],
    106 => [['pn_max_kw' => INF, 'rpn' => 0.84, 'qp0_pct' => null, 'pveil' => 150.0]],
    60  => [['pn_max_kw' => INF, 'rpn' => 0.84, 'qp0_pct' => null, 'pveil' => 150.0]],
    107 => [['pn_max_kw' => INF, 'rpn' => 0.84, 'qp0_pct' => null, 'pveil' => 150.0]],
    61  => [['pn_max_kw' => INF, 'rpn' => 0.98, 'qp0_pct' => null, 'pveil' => 0.0]],
    108 => [['pn_max_kw' => INF, 'rpn' => 0.98, 'qp0_pct' => null, 'pveil' => 0.0]],
    62  => [['pn_max_kw' => INF, 'rpn' => 0.98, 'qp0_pct' => null, 'pveil' => 0.0]],
    109 => [['pn_max_kw' => INF, 'rpn' => 0.98, 'qp0_pct' => null, 'pveil' => 0.0]],

    // ─── Chauffe-eau gaz à production instantanée (§14.1.1) ─────────────────
    // Deux paliers Pn : ≤10 kW et >10 kW
    // Chauffe-eau gaz : pn_forfait fixe (LICIEL — non dérivé du GV comme pour les chaudières).
    // Pn≤10 → forfait 10 kW ; Pn>10 → forfait 24 kW (valeur ADEME verif 2457E).
    63  => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.70, 'qp0_pct' => 0.04, 'pveil' => 150.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.70, 'qp0_pct' => 0.04, 'pveil' => 150.0],
    ],
    110 => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.70, 'qp0_pct' => 0.04, 'pveil' => 150.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.70, 'qp0_pct' => 0.04, 'pveil' => 150.0],
    ],
    64  => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.75, 'qp0_pct' => 0.02, 'pveil' => 120.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.75, 'qp0_pct' => 0.02, 'pveil' => 120.0],
    ],
    111 => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.75, 'qp0_pct' => 0.02, 'pveil' => 120.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.75, 'qp0_pct' => 0.02, 'pveil' => 120.0],
    ],
    65  => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.81, 'qp0_pct' => 0.012, 'pveil' => 120.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.82, 'qp0_pct' => 0.012, 'pveil' => 120.0],
    ],
    112 => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.81, 'qp0_pct' => 0.012, 'pveil' => 120.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.82, 'qp0_pct' => 0.012, 'pveil' => 120.0],
    ],
    66  => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.82, 'qp0_pct' => 0.01, 'pveil' => 100.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.84, 'qp0_pct' => 0.01, 'pveil' => 100.0],
    ],
    113 => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.82, 'qp0_pct' => 0.01, 'pveil' => 100.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.84, 'qp0_pct' => 0.01, 'pveil' => 100.0],
    ],
    67  => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.82, 'qp0_pct' => 0.01,  'pveil' => 0.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.84, 'qp0_pct' => 0.006, 'pveil' => 0.0],
    ],
    114 => [
        ['pn_max_kw' => 10.0, 'pn_forfait_kw' => 10.0, 'rpn' => 0.82, 'qp0_pct' => 0.01,  'pveil' => 0.0],
        ['pn_max_kw' => INF,  'pn_forfait_kw' => 24.0, 'rpn' => 0.84, 'qp0_pct' => 0.006, 'pveil' => 0.0],
    ],

    // ─── GPL/propane/butane chaudières (IDs 92-104 en ECS) ──────────────────
    // (identiques aux gaz mais indexés différemment — alias ci-dessus pour 92-100)
    // IDs 101-104 (condensation gpl)
    101 => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 150.0]],
    102 => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 120.0]],
    103 => [['pn_max_kw' => INF, 'rpn' => $e91_1, 'qp0_pct' => 0.01, 'pveil' => 0.0]],
    104 => [
        ['pn_max_kw' =>  70.0, 'rpn' => $e91_3, 'qp0_pct' => 0.005, 'pveil' => 0.0],
        ['pn_max_kw' => 400.0, 'rpn' => $e94_1, 'qp0_pct' => 0.003, 'pveil' => 0.0],
        ['pn_max_kw' => INF,   'rpn' => 0.966,  'qp0_pct' => 0.003, 'pveil' => 0.0],
    ],

    // ─── Charbon (IDs 85-91 en ECS) ─────────────────────────────────────────
    // Mêmes formules que bois bûche pour les périodes correspondantes
    // (simplification conservative §14.1)
];
