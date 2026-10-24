<?php

declare(strict_types=1);

/**
 * SCOP des neuf nouveaux types PAC CH installés à partir de 2027.
 * Indexation directe par enum_type_generateur_ch_id, exhaustive pour les
 * PAC 172-176 et leurs parties hybrides 178-181 (pas les chaudières bois).
 *
 * ADEME valeur_tables.xlsx, révision 05aabba7 du 16/10/2026, feuille scop
 * A90:J107. SHA-256 :
 * 50d1189e0df8b47aad006a3e525564ae33fab041c3f389d7b407c99fa5546c81.
 * La colonne « plancher_plafond » couvre les enums d'émission 11-18, 41,
 * 46-49 : planchers/plafonds, ventilo-convecteurs à eau, murs chauffants.
 * Les autres émetteurs suivent « autres ». Zones H1/H2 = 1-7 ; H3 = 8.
 *
 * @spec-section 12.4.2
 * @spec-pages 77-78
 * @spec-source resources/specsplitted/12-rendements-installations/04-generation-non-combustion.md
 * @spec-source https://gitlab.com/observatoire-dpe/observatoire-dpe/-/blob/05aabba7/modele_donnee/valeur_tables.xlsx
 * @generated-on 2026-10-24
 */
return [
    172 => ['h1h2' => ['autres' => 3.2, 'plancher_plafond' => 3.2], 'h3' => ['autres' => 3.4, 'plancher_plafond' => 3.4]], // air/air, lignes 90-91
    173 => ['h1h2' => ['autres' => 2.9, 'plancher_plafond' => 3.4], 'h3' => ['autres' => 3.4, 'plancher_plafond' => 4.2]], // air/eau, lignes 92-95
    174 => ['h1h2' => ['autres' => 3.1, 'plancher_plafond' => 3.5], 'h3' => ['autres' => 3.7, 'plancher_plafond' => 4.4]], // eau/eau, lignes 96-99
    175 => ['h1h2' => ['autres' => 3.1, 'plancher_plafond' => 3.5], 'h3' => ['autres' => 3.7, 'plancher_plafond' => 4.4]], // eau glycolée/eau, lignes 100-103
    176 => ['h1h2' => ['autres' => 3.1, 'plancher_plafond' => 3.5], 'h3' => ['autres' => 3.7, 'plancher_plafond' => 4.4]], // géothermie, lignes 104-107
    178 => ['h1h2' => ['autres' => 2.9, 'plancher_plafond' => 3.4], 'h3' => ['autres' => 3.4, 'plancher_plafond' => 4.2]], // hybride air/eau
    179 => ['h1h2' => ['autres' => 3.1, 'plancher_plafond' => 3.5], 'h3' => ['autres' => 3.7, 'plancher_plafond' => 4.4]], // hybride eau/eau
    180 => ['h1h2' => ['autres' => 3.1, 'plancher_plafond' => 3.5], 'h3' => ['autres' => 3.7, 'plancher_plafond' => 4.4]], // hybride eau glycolée/eau
    181 => ['h1h2' => ['autres' => 3.1, 'plancher_plafond' => 3.5], 'h3' => ['autres' => 3.7, 'plancher_plafond' => 4.4]], // hybride géothermie
];
