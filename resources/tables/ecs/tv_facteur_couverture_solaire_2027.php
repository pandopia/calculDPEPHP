<?php

declare(strict_types=1);

/**
 * ECS solaire seule (type 5), modèle 2.7 : huit zones × deux bâtiments.
 * Les anciennes colonnes selon l'âge restent dans la table historique.
 *
 * @spec-section 18.4
 * @spec-pages 143
 * @spec-source editeur/public/schemas/DPEv2.7.xsd
 * @spec-source https://gitlab.com/observatoire-dpe/observatoire-dpe/-/blob/05aabba7/modele_donnee/valeur_tables.xlsx (facteur_couverture_solaire A50:H65)
 * @source-sha256 50d1189e0df8b47aad006a3e525564ae33fab041c3f389d7b407c99fa5546c81
 * @generated-on 2026-10-24
 * @status complete — les 16 lignes 49–64 du type solaire 5
 */
return [
    1 => ['maison' => 0.63, 'immeuble' => 0.38], // H1a, 49/57
    2 => ['maison' => 0.64, 'immeuble' => 0.40], // H1b, 50/58
    3 => ['maison' => 0.68, 'immeuble' => 0.45], // H1c, 51/59
    4 => ['maison' => 0.66, 'immeuble' => 0.41], // H2a, 52/60
    5 => ['maison' => 0.69, 'immeuble' => 0.46], // H2b, 53/61
    6 => ['maison' => 0.74, 'immeuble' => 0.50], // H2c, 54/62
    7 => ['maison' => 0.77, 'immeuble' => 0.56], // H2d, 55/63
    8 => ['maison' => 0.80, 'immeuble' => 0.58], // H3, 56/64
];
