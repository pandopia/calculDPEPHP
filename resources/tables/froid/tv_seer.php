<?php

/**
 * SEER par zone climatique et période d'installation — §10.3 p.69.
 *
 * Structure : $table[tv_seer_id] → ['seer' => float, 'eer' => float]
 *
 * tv_seer_id 1-6 :
 *   1 = Zone H1/H2, avant 2008  (EER direct, pas SEER × 0.95)
 *   2 = Zone H1/H2, 2008-2014
 *   3 = Zone H1/H2, à partir de 2015
 *   4 = Zone H3,    avant 2008  (EER direct)
 *   5 = Zone H3,    2008-2014
 *   6 = Zone H3,    à partir de 2015
 *
 * Note §10.3 p.69 : pour «avant 2008», la valeur est EER directement (*EER note de bas de page).
 * EER = 0,95 × SEER pour les installations 2008+.
 *
 * @spec-section 10.3
 * @spec-pages   69
 * @spec-source  resources/specsplitted/10-conso-froid/03-consommations.md
 * @generated-on 2026-04-29
 */
return [
    1 => ['seer' => 3.60, 'eer' => 3.60],                // H1/H2 avant 2008 (EER direct) p.69
    2 => ['seer' => 6.50, 'eer' => 0.95 * 6.50],         // H1/H2 2008-2014
    3 => ['seer' => 6.70, 'eer' => 0.95 * 6.70],         // H1/H2 à partir 2015
    4 => ['seer' => 3.25, 'eer' => 3.25],                // H3 avant 2008 (EER direct) p.69
    5 => ['seer' => 5.70, 'eer' => 0.95 * 5.70],         // H3 2008-2014
    6 => ['seer' => 7.50, 'eer' => 0.95 * 7.50],         // H3 à partir 2015
];
