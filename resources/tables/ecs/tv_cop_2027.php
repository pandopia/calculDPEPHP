<?php

declare(strict_types=1);

/**
 * COP des CET et PAC double service installés à partir de 2027.
 * Indexation par les quatre enums ECS officiels 135 à 138, exhaustive.
 * Le COP inclut le stockage, comme pour les générations antérieures (§14.2).
 *
 * Source ADEME : valeur_tables.xlsx, révision 05aabba7 (modèle DPE 2.7,
 * publication du 16/10/2026), feuille scop, cellules A108:J113.
 * SHA-256 : 50d1189e0df8b47aad006a3e525564ae33fab041c3f389d7b407c99fa5546c81.
 * Les zones H1/H2 sont les enums 1 à 7 ; H3 est l'enum 8.
 * Aucun lookup d'ID de table interne au logiciel diagnostiqueur.
 *
 * @spec-section 14.2
 * @spec-pages 95
 * @spec-source resources/specsplitted/14-rendement-ecs-generateurs/02-cet-accumulation.md
 * @spec-source https://gitlab.com/observatoire-dpe/observatoire-dpe/-/blob/05aabba7/modele_donnee/valeur_tables.xlsx
 * @generated-on 2026-10-24
 */
return [
    135 => ['h1h2' => 2.7, 'h3' => 3.0], // air ambiant sur LNC, lignes 108-109
    136 => ['h1h2' => 2.7, 'h3' => 3.0], // air extérieur, lignes 108-109
    137 => ['h1h2' => 3.0, 'h3' => 3.1], // air extrait, lignes 110-111
    138 => ['h1h2' => 2.8, 'h3' => 3.1], // PAC double service, lignes 112-113
];
