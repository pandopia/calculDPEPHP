<?php

declare(strict_types=1);

/**
 * Coefficient de réduction des déperditions b — §3.1 (p.8-12).
 *
 * Trois groupes :
 *   1. **Cas directs** par `enum_type_adjacence_id` (extérieur, terre-plein, vide sanitaire,
 *      sous-sol non chauffé, paroi enterrée, mitoyenneté, occupation discontinue, local non
 *      déperditif…). Ces cas court-circuitent les tableaux Aiu/Aue × UV,ue.
 *   2. **UV,ue** par `enum_type_adjacence_id` (combles, garage, cellier, circulations…) — table p.9.
 *   3. **Tableaux 16 × 4** Aiu/Aue × UV,ue selon `enum_cfg_isolation_lnc_id` (lc isolé/non,
 *      lnc isolé/non) — tables p.10-11.
 *   4. **Vérandas (espaces tampons solarisés)** par zone × orientation × isolation lc — p.11.
 *
 * Cf. `BCalculator` pour la logique de dispatch.
 *
 * @spec-section 3.1
 * @spec-pages 8-12
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/01-coef-reduction-b/00-detail.md
 * @generated-on 2026-04-29
 */
return [

    // ---- 1. Cas directs (clé : enum_type_adjacence_id) ------------------------
    // Source p.8 :
    //   « Pour une paroi enterrée ou donnant sur l'extérieur, ou un plancher sur terre-plein,
    //     vide sanitaire ou sous-sol non chauffé, b = 1. »
    //   « locaux non chauffés non accessibles … b = 0,95 »
    //   « bâtiment ou espace autre que d'habitation (occupation discontinue) … b = 0,2 »
    //   « local non déperditif (local à usage d'habitation chauffé) → b = 0 (paroi non comptée) »
    'cas_directs' => [
        1  => 1.00,  // extérieur
        2  => 1.00,  // paroi enterrée
        3  => 1.00,  // vide sanitaire (b=1 pour la paroi ; le Ue se fait au niveau plancher)
        4  => 0.20,  // bâtiment / local autre que d'habitation (occupation discontinue)
        5  => 1.00,  // terre-plein
        6  => 1.00,  // sous-sol non chauffé (b=1 pour la paroi ; Ue au niveau plancher)
        7  => 0.95,  // locaux non chauffés non accessibles
        22 => 0.00,  // local non déperditif
    ],

    // ---- 2. UV,ue par adjacence (clé : enum_type_adjacence_id), table p.9 ----
    // Cas non listés ici → utilisation des tableaux Aiu/Aue × UV,ue.
    'uv_ue' => [
        // Maison individuelle
        8  => 3.0,    // garage
        9  => 3.0,    // cellier
        11 => 9.0,    // comble fortement ventilé
        12 => 3.0,    // comble faiblement ventilé
        13 => 0.3,    // comble très faiblement ventilé
        // Logement collectif
        14 => 0.0,    // circulation sans ouverture directe sur l'extérieur
        15 => 0.3,    // circulation avec ouverture directe sur l'extérieur
        16 => 3.0,    // circulation avec bouche/gaine de désenfumage
        17 => 0.3,    // hall d'entrée avec dispositif de fermeture automatique
        18 => 3.0,    // hall d'entrée sans dispositif de fermeture automatique
        19 => 3.0,    // garage privé collectif
        20 => 3.0,    // local tertiaire à l'intérieur de l'immeuble
        21 => 3.0,    // autres dépendances
    ],

    // ---- 3. Tableaux 16 × 4 Aiu/Aue × UV,ue ----------------------------------
    // L'axe Aiu/Aue est une suite de bornes supérieures (≤ 0.25 ; 0.25< ≤ 0.50 ; etc.).
    // L'axe UV,ue prend les 4 valeurs canoniques 0.0, 0.3, 3.0, 9.0.
    // Pour les UV,ue intermédiaires on prend le seuil le plus proche supérieur ou égal.
    //
    // 4 sous-tableaux selon enum_cfg_isolation_lnc_id :
    //   2 lc non isolé + lnc non isolé   → 'lc_ni_lnc_ni'
    //   3 lc non isolé + lnc isolé        → 'lc_ni_lnc_i'
    //   4 lc isolé + lnc non isolé        → 'lc_i_lnc_ni'
    //   5 lc isolé + lnc isolé            → 'lc_i_lnc_i'
    //
    // Source p.10-11.
    'tableaux' => [
        'aiu_aue_axis' => [0.25, 0.50, 0.75, 1.00, 1.25, 2.00, 2.50, 3.00, 3.50, 4.00, 6.00, 8.00, 10.00, 25.00, 50.00, PHP_FLOAT_MAX],
        'uv_ue_axis'   => [0.0, 0.3, 3.0, 9.0],
        // Mapping confirmé empiriquement par les exemples (cfg=2 + ratio×UVue → b) :
        // - p.10 tableau gauche = lc_ni_lnc_i  (lc non isolé, lnc isolé)
        // - p.10 tableau droit  = lc_ni_lnc_ni (lc non isolé, lnc non isolé) ← cfg=2
        // L'intuition initiale est trompeuse : avec un lnc isolé, le lnc se réchauffe moins
        // depuis l'extérieur, donc la déperdition est plus défavorable pour le lc.
        'lc_ni_lnc_i' => [
            // p.10 tableau gauche
            [0.95, 0.95, 1.00, 1.00],   // ≤ 0.25
            [0.95, 0.95, 0.95, 1.00],   // ≤ 0.50
            [0.90, 0.95, 0.95, 1.00],   // ≤ 0.75
            [0.85, 0.90, 0.95, 0.95],   // ≤ 1.00
            [0.85, 0.90, 0.90, 0.95],   // ≤ 1.25
            [0.80, 0.80, 0.90, 0.95],   // ≤ 2.00
            [0.75, 0.80, 0.85, 0.90],   // ≤ 2.50
            [0.70, 0.75, 0.85, 0.90],   // ≤ 3.00
            [0.65, 0.75, 0.80, 0.90],   // ≤ 3.50
            [0.65, 0.70, 0.80, 0.90],   // ≤ 4.00
            [0.55, 0.60, 0.70, 0.85],   // ≤ 6.00
            [0.45, 0.55, 0.65, 0.80],   // ≤ 8.00
            [0.40, 0.50, 0.60, 0.75],   // ≤ 10.00
            [0.35, 0.40, 0.50, 0.70],   // ≤ 25.00
            [0.20, 0.25, 0.35, 0.50],   // ≤ 50.00
            [0.10, 0.15, 0.20, 0.30],   // > 50.00
        ],
        'lc_ni_lnc_ni' => [
            // p.10 tableau droit
            [0.80, 0.85, 0.90, 0.95],
            [0.65, 0.75, 0.80, 0.90],
            [0.55, 0.65, 0.75, 0.85],
            [0.50, 0.55, 0.70, 0.80],
            [0.45, 0.50, 0.65, 0.80],
            [0.35, 0.40, 0.50, 0.70],
            [0.30, 0.35, 0.45, 0.65],
            [0.25, 0.30, 0.40, 0.60],
            [0.20, 0.30, 0.40, 0.55],
            [0.20, 0.25, 0.35, 0.50],
            [0.15, 0.20, 0.25, 0.40],
            [0.10, 0.15, 0.20, 0.35],
            [0.10, 0.10, 0.20, 0.30],
            [0.05, 0.10, 0.15, 0.25],
            [0.05, 0.05, 0.05, 0.15],
            [0.00, 0.00, 0.05, 0.05],
        ],
        // NOTE — les deux sous-tables ci-dessous (lc isolé) ne sont pas vérifiées par les
        // exemples actuels (les 4 fichiers verif n'utilisent que cfg=2). Le mapping bas-gauche
        // → lc_i_lnc_i et bas-droit → lc_i_lnc_ni suit la cohérence des écarts de valeurs
        // (b minimal en cfg=5) mais doit être confirmé par un exemple avec cfg ∈ {3,4,5}.
        'lc_i_lnc_i' => [
            // p.11 tableau bas-gauche
            [0.35, 0.50, 0.85, 0.95],
            [0.20, 0.35, 0.70, 0.90],
            [0.15, 0.25, 0.65, 0.85],
            [0.15, 0.20, 0.55, 0.80],
            [0.10, 0.15, 0.50, 0.75],
            [0.05, 0.10, 0.40, 0.65],
            [0.05, 0.10, 0.35, 0.60],
            [0.05, 0.10, 0.30, 0.55],
            [0.05, 0.05, 0.25, 0.50],
            [0.05, 0.05, 0.25, 0.45],
            [0.00, 0.05, 0.20, 0.35],
            [0.00, 0.05, 0.15, 0.30],
            [0.00, 0.05, 0.10, 0.25],
            [0.00, 0.00, 0.10, 0.20],
            [0.00, 0.00, 0.05, 0.10],
            [0.00, 0.00, 0.00, 0.05],
        ],
        'lc_i_lnc_ni' => [
            // p.11 tableau bas-droit
            [0.80, 0.90, 0.95, 1.00],
            [0.65, 0.80, 0.95, 1.00],
            [0.55, 0.70, 0.90, 0.95],
            [0.50, 0.65, 0.90, 0.95],
            [0.45, 0.60, 0.90, 0.95],
            [0.35, 0.45, 0.80, 0.95],
            [0.30, 0.40, 0.80, 0.90],
            [0.25, 0.35, 0.75, 0.90],
            [0.20, 0.35, 0.70, 0.90],
            [0.20, 0.30, 0.70, 0.85],
            [0.15, 0.25, 0.60, 0.80],
            [0.10, 0.20, 0.55, 0.75],
            [0.10, 0.15, 0.45, 0.70],
            [0.05, 0.10, 0.40, 0.65],
            [0.05, 0.05, 0.25, 0.45],
            [0.00, 0.05, 0.10, 0.30],
        ],
    ],

    // ---- 4. Vérandas (espace tampon solarisé) — table bver p.11 -------------
    // Indexé par [zone H1/H2/H3][orientation Nord/EstOuest/Sud][isolation lc Isolé/NonIsolé]
    'veranda' => [
        'H1' => [
            'nord'        => ['isole' => 0.95, 'non_isole' => 0.85],
            'est_ouest'   => ['isole' => 0.63, 'non_isole' => 0.60],
            'sud'         => ['isole' => 0.58, 'non_isole' => 0.55],
        ],
        'H2' => [
            'nord'        => ['isole' => 0.95, 'non_isole' => 0.85],
            'est_ouest'   => ['isole' => 0.60, 'non_isole' => 0.58],
            'sud'         => ['isole' => 0.57, 'non_isole' => 0.55],
        ],
        'H3' => [
            'nord'        => ['isole' => 0.95, 'non_isole' => 0.85],
            'est_ouest'   => ['isole' => 0.53, 'non_isole' => 0.53],
            'sud'         => ['isole' => 0.48, 'non_isole' => 0.55],
        ],
    ],
];
