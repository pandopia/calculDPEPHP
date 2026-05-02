<?php

declare(strict_types=1);

/**
 * Zones climatiques par département — §18.1 p.120.
 *
 * Structure : $table[code_dept] → ['zone_id' => int, 'zone' => string, 'nom' => string]
 *
 * Zones : 1=H1a, 2=H1b, 3=H1c, 4=H2a, 5=H2b, 6=H2c, 7=H2d, 8=H3.
 *
 * Note : l'engine lit directement `enum_zone_climatique_id` depuis le XML — cette table
 * est une référence documentaire (permet de retrouver la zone depuis un numéro de département).
 *
 * @spec-section 18.1
 * @spec-pages   120-121
 * @spec-source  resources/specsplitted/18-annexes/01-zones-climatiques/00-texte.md
 * @generated-on 2026-04-30
 * @status complete — 96 entrées (01-95 + 2A + 2B)
 */
return [
    // ── H1a ───────────────────────────────────────────────────────────────────
    '02' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Aisne'],
    '14' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Calvados'],
    '27' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Eure'],
    '28' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Eure-et-Loir'],
    '59' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Nord'],
    '60' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Oise'],
    '61' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Orne'],
    '62' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Pas-de-Calais'],
    '75' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Paris'],
    '76' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Seine-Maritime'],
    '77' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Seine-et-Marne'],
    '78' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Yvelines'],
    '80' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Somme'],
    '91' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Essonne'],
    '92' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Hauts-de-Seine'],
    '93' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Seine-St-Denis'],
    '94' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Val-de-Marne'],
    '95' => ['zone_id' => 1, 'zone' => 'H1a', 'nom' => 'Val-d\'Oise'],

    // ── H1b ───────────────────────────────────────────────────────────────────
    '08' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Ardennes'],
    '10' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Aube'],
    '45' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Loiret'],
    '51' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Marne'],
    '52' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Haute-Marne'],
    '54' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Meurthe-et-Moselle'],
    '55' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Meuse'],
    '57' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Moselle'],
    '58' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Nièvre'],
    '67' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Bas-Rhin'],
    '68' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Haut-Rhin'],
    '70' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Haute-Saône'],
    '88' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Vosges'],
    '89' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Yonne'],
    '90' => ['zone_id' => 2, 'zone' => 'H1b', 'nom' => 'Territoire de Belfort'],

    // ── H1c ───────────────────────────────────────────────────────────────────
    '01' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Ain'],
    '03' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Allier'],
    '05' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Hautes-Alpes'],
    '15' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Cantal'],
    '19' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Corrèze'],
    '21' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Côte-d\'Or'],
    '23' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Creuse'],
    '25' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Doubs'],
    '38' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Isère'],
    '39' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Jura'],
    '42' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Loire'],
    '43' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Haute-Loire'],
    '63' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Puy-de-Dôme'],
    '69' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Rhône'],
    '71' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Saône-et-Loire'],
    '73' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Savoie'],
    '74' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Haute-Savoie'],
    '87' => ['zone_id' => 3, 'zone' => 'H1c', 'nom' => 'Haute-Vienne'],

    // ── H2a ───────────────────────────────────────────────────────────────────
    '22' => ['zone_id' => 4, 'zone' => 'H2a', 'nom' => 'Côtes d\'Armor'],
    '29' => ['zone_id' => 4, 'zone' => 'H2a', 'nom' => 'Finistère'],
    '35' => ['zone_id' => 4, 'zone' => 'H2a', 'nom' => 'Ille-et-Vilaine'],
    '50' => ['zone_id' => 4, 'zone' => 'H2a', 'nom' => 'Manche'],
    '56' => ['zone_id' => 4, 'zone' => 'H2a', 'nom' => 'Morbihan'],

    // ── H2b ───────────────────────────────────────────────────────────────────
    '16' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Charente'],
    '17' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Charente-Maritime'],
    '18' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Cher'],
    '36' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Indre'],
    '37' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Indre-et-Loire'],
    '41' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Loir-et-Cher'],
    '44' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Loire-Atlantique'],
    '49' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Maine-et-Loire'],
    '53' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Mayenne'],
    '72' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Sarthe'],
    '79' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Deux-Sèvres'],
    '85' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Vendée'],
    '86' => ['zone_id' => 5, 'zone' => 'H2b', 'nom' => 'Vienne'],

    // ── H2c ───────────────────────────────────────────────────────────────────
    '09' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Ariège'],
    '12' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Aveyron'],
    '24' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Dordogne'],
    '31' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Haute-Garonne'],
    '32' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Gers'],
    '33' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Gironde'],
    '40' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Landes'],
    '46' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Lot'],
    '47' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Lot-et-Garonne'],
    '64' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Pyrénées-Atlantiques'],
    '65' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Hautes-Pyrénées'],
    '81' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Tarn'],
    '82' => ['zone_id' => 6, 'zone' => 'H2c', 'nom' => 'Tarn-et-Garonne'],

    // ── H2d ───────────────────────────────────────────────────────────────────
    '04' => ['zone_id' => 7, 'zone' => 'H2d', 'nom' => 'Alpes-de-Haute-Provence'],
    '07' => ['zone_id' => 7, 'zone' => 'H2d', 'nom' => 'Ardèche'],
    '26' => ['zone_id' => 7, 'zone' => 'H2d', 'nom' => 'Drôme'],
    '48' => ['zone_id' => 7, 'zone' => 'H2d', 'nom' => 'Lozère'],
    '84' => ['zone_id' => 7, 'zone' => 'H2d', 'nom' => 'Vaucluse'],

    // ── H3 ────────────────────────────────────────────────────────────────────
    '06' => ['zone_id' => 8, 'zone' => 'H3',  'nom' => 'Alpes-Maritimes'],
    '11' => ['zone_id' => 8, 'zone' => 'H3',  'nom' => 'Aude'],
    '13' => ['zone_id' => 8, 'zone' => 'H3',  'nom' => 'Bouches-du-Rhône'],
    '2A' => ['zone_id' => 8, 'zone' => 'H3',  'nom' => 'Corse-du-Sud'],
    '2B' => ['zone_id' => 8, 'zone' => 'H3',  'nom' => 'Haute-Corse'],
    '30' => ['zone_id' => 8, 'zone' => 'H3',  'nom' => 'Gard'],
    '34' => ['zone_id' => 8, 'zone' => 'H3',  'nom' => 'Hérault'],
    '66' => ['zone_id' => 8, 'zone' => 'H3',  'nom' => 'Pyrénées-Orientales'],
    '83' => ['zone_id' => 8, 'zone' => 'H3',  'nom' => 'Var'],
];
