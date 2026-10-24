<?php

declare(strict_types=1);

/**
 * Régulation prédictive, modèle 2.7 : les 40 lignes ADEME 189 à 228.
 * Indexation par critères physiques, pas par identifiant logiciel.
 * Maison : chauffage → inertie → émetteur. Individuel : chauffage → émetteur.
 * Collectif : régulation → comptage → émetteur. La régulation pièce par
 * pièce est obligatoire dans les deux premières configurations du classeur.
 *
 * @spec-section 8
 * @spec-pages 55-57
 * @spec-source editeur/public/schemas/DPEv2.7.xsd
 * @spec-source https://gitlab.com/observatoire-dpe/observatoire-dpe/-/blob/05aabba7/modele_donnee/valeur_tables.xlsx (intermittence A190:P229)
 * @source-sha256 50d1189e0df8b47aad006a3e525564ae33fab041c3f389d7b407c99fa5546c81
 * @generated-on 2026-10-24
 * @status complete — lignes 189–228, toutes les configurations publiées
 */
return [
    'maison' => [
        'divise' => [
            'legere_moyenne' => ['air_souffle' => 0.73, 'radiateur' => 0.73, 'plafond' => 0.73, 'plancher' => 0.82], // 189–192
            'lourde' => ['air_souffle' => 0.76, 'radiateur' => 0.76, 'plafond' => 0.76, 'plancher' => 0.84], // 197–200
        ],
        'central' => [
            'legere_moyenne' => ['air_souffle' => 0.75, 'radiateur' => 0.78, 'plafond' => 0.78, 'plancher' => 0.82], // 193–196
            'lourde' => ['air_souffle' => 0.78, 'radiateur' => 0.81, 'plafond' => 0.81, 'plancher' => 0.84], // 201–204
        ],
    ],
    'collectif_individuel' => [
        'divise' => ['air_souffle' => 0.82, 'radiateur' => 0.82, 'plafond' => 0.82, 'plancher' => 0.86], // 205–208
        'central' => ['air_souffle' => 0.83, 'radiateur' => 0.85, 'plafond' => 0.85, 'plancher' => 0.86], // 209–212
    ],
    'collectif_collectif' => [
        'avec' => [
            'absent' => ['air_souffle' => 0.94, 'radiateur' => 0.96, 'plafond' => 0.96, 'plancher' => 0.98], // 213–216
            'present' => ['air_souffle' => 0.86, 'radiateur' => 0.88, 'plafond' => 0.88, 'plancher' => 0.90], // 217–220
        ],
        'sans' => [
            'absent' => ['air_souffle' => 0.96, 'radiateur' => 0.98, 'plafond' => 0.98, 'plancher' => 1.00], // 221–224
            'present' => ['air_souffle' => 0.88, 'radiateur' => 0.90, 'plafond' => 0.90, 'plancher' => 0.92], // 225–228
        ],
    ],
];
