<?php

/**
 * Nature des douze lignes de distribution chauffage publiées par l'ADEME.
 *
 * Le type d'émetteur générique 41 est compatible avec toutes ces lignes :
 * son libellé seul ne signifie pas « sans réseau ». L'index est publié par
 * l'ADEME, pas inféré d'un logiciel diagnostiqueur. `emetteur_reseau` adresse
 * un représentant du seul réseau dans tv_rendement_distribution_ch ; il ne
 * requalifie ni l'émission ni les pertes de charge de ses auxiliaires.
 *
 * @spec-section 12.2
 * @spec-pages 76
 * @spec-source resources/specsplitted/12-rendements-installations/02-distribution.md
 * @spec-source https://gitlab.com/observatoire-dpe/observatoire-dpe/-/blob/52792fb9e79e52390ded7d4527a7f7a962fd5ccc/modele_donnee/valeur_tables.xlsx (rendement_distribution_ch A2:E13)
 * @generated-on 2026-10-25
 */
return [
    1 => ['emetteur_reseau' => 41, 'isole' => null], // aucun réseau
    2 => ['emetteur_reseau' => 5, 'isole' => false], // aéraulique
    3 => ['emetteur_reseau' => 11, 'isole' => false], // eau collective HT
    4 => ['emetteur_reseau' => 12, 'isole' => false], // eau collective BT
    5 => ['emetteur_reseau' => 13, 'isole' => false], // eau individuelle HT
    6 => ['emetteur_reseau' => 14, 'isole' => false], // eau individuelle BT
    7 => ['emetteur_reseau' => 5, 'isole' => true],
    8 => ['emetteur_reseau' => 11, 'isole' => true],
    9 => ['emetteur_reseau' => 12, 'isole' => true],
    10 => ['emetteur_reseau' => 13, 'isole' => true],
    11 => ['emetteur_reseau' => 14, 'isole' => true],
    12 => ['emetteur_reseau' => 42, 'isole' => null], // fluide frigorigène
];
