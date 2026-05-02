# resources/tables/

Tables de la spec DPE 3CL-2021 digitalisées en PHP.

Chaque fichier renvoie un `array` indexé par l'identifiant de la table (`tv_*_id`). Les classes `Calculator` y accèdent via `CalculDpe\Tables\TableRepository`.

## Format obligatoire

```php
<?php
/**
 * Description de la table.
 *
 * @spec-section 3.2.1
 * @spec-pages 13-16
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/01-umur/01-table-umur-connu.md
 * @generated-on 2026-04-29
 */
return [
    1 => ['materiau' => 'mur_brique_pleine', 'epaisseur_min_cm' => 0,  'epaisseur_max_cm' => 22, 'umur' => 2.30], // p.13 ligne 1
    2 => ['materiau' => 'mur_brique_pleine', 'epaisseur_min_cm' => 22, 'epaisseur_max_cm' => 35, 'umur' => 1.90], // p.13 ligne 2
    // …
];
```

Règles :

1. **Doc-block obligatoire** avec `@spec-section`, `@spec-pages`, `@spec-source` (chemin du `.md` qui contient la transcription markdown), `@generated-on`.
2. **La clé du tableau racine** = identifiant de la spec (`tv_umur_id` → entier 1..N). Si la spec ne fournit pas d'ID explicite, créer un compteur séquentiel et le documenter.
3. **Une ligne PHP = une ligne du PDF**. Commenter chaque ligne avec `// p.<N> ligne "<intitulé>"` pour tracer.
4. **Les valeurs textuelles** (matériaux, types d'isolation, …) sont en `snake_case` ASCII pour éviter les surprises d'encodage.
5. **Pas de logique** dans les tables : que des données. Toute interpolation, condition ou seuil va dans le `Calculator` correspondant.

## EXIGENCE D'EXHAUSTIVITÉ (impérative)

**Une table publiée ici doit couvrir l'intégralité de l'espace d'index défini par la spec.** Pas de "subset des IDs vus dans les exemples". Pas de "j'ajouterai les manquants plus tard sans le dire". Une table incomplète est un piège : elle marche en dev sur les exemples, puis explose en prod sur le premier XML hors échantillon.

Si la table est trop grosse pour être digitalisée en une seule passe :

- **Soit** la découper en sous-fichiers (`tv_X_partA.php`, `tv_X_partB.php`) avec un fichier d'index `tv_X.php` qui les fusionne, et une tâche TASK-Xxx par partie ;
- **Soit** marquer le fichier `@status partial` avec dans le doc-block la liste **précise** de ce qui manque, et créer une TASK-Xxx visible dans `TASKS.md`. Le `Calculator` qui consomme la table doit alors lever une `RuntimeException` lisible si on lui passe un ID hors couverture, pas retourner silencieusement une valeur par défaut. Cf. les exceptions dans `tv_coef_reduction_deperdition` ou `tv_pont_thermique` pour le pattern attendu.

**Refus de digitalisation** : si l'indexation `tv_*_id` n'est pas définie par la spec officielle (3CL-DPE 2021) mais résulte d'un mapping interne au logiciel diagnostiqueur (LICIEL et consorts), ne pas digitaliser cet ID. À la place, **indexer la table par les paramètres directs** présents dans la donnée d'entrée XML (`enum_type_vitrage_id`, `enum_type_gaz_lame_id`, `vitrage_vir`, `epaisseur_lame`, etc.) et faire le lookup depuis ces paramètres. Le `Calculator` lit alors les paramètres dans `<donnee_entree>` au lieu de l'ID. C'est plus robuste, traçable, et indépendant des conventions tierces.

## Tables multi-axes

Pour les tables 3D ou 4D (orientation × inclinaison × zone × altitude — typiquement §18.2 et §18.5), on utilise des **clés composites** :

```php
return [
    // §18.5 : C1 par zone × orientation × inclinaison
    'H1a|S|45'  => 1.150,
    'H1a|S|0'   => 1.000,
    'H1a|N|45'  => 0.420,
    'H1b|S|45'  => 1.080,
    // …
];
```

L'ordre des composants de la clé est documenté dans le doc-block.

## Annexes 18.x volumineuses

Pour les sollicitations extérieures (T_ext, E_solaire mensuels par zone × altitude), préférer des arrays imbriqués structurés :

```php
return [
    'H1a' => [
        'altitude_lt_400' => [
            'temperature_ext_mensuelle' => [4.2, 5.1, 8.0, /* … 12 valeurs */],
            'ensoleillement_mensuel'    => [/* dépend orientation */],
        ],
        'altitude_400_800' => [/* … */],
        'altitude_gt_800'  => [/* … */],
    ],
    'H1b' => [/* … */],
    // …
];
```

## Arborescence

```
resources/tables/
├── enveloppe/      # tv_umur, tv_umur0, tv_upb, tv_upb0, tv_uph, tv_uph0,
│                   # tv_ug, tv_uw, tv_ujn, tv_deltar, tv_uporte, tv_sw,
│                   # tv_coef_reduction_deperdition (~283 lignes),
│                   # tv_coef_masque_proche, tv_coef_masque_lointain,
│                   # tv_pont_thermique, tv_coef_transparence_ets
├── ventilation/    # tv_debits_ventilation, tv_q4pa_conv
├── chauffage/      # tv_intermittence, tv_rendement_emission,
│                   # tv_rendement_distribution_ch, tv_rendement_regulation,
│                   # tv_rendement_generation, tv_scop
├── froid/          # tv_seer
├── ecs/            # tv_rendement_distribution_ecs, tv_pertes_stockage,
│                   # tv_facteur_couverture_solaire, tv_reseau_chaleur
├── prod_elec/      # tv_coef_orientation_pv
└── reference/      # zones_climatiques, temp_ext_mensuelles,
                    # ensoleillement_mensuel, degres_heures, nref,
                    # inertie_lourde, c1_orientation_inclinaison
```

## Workflow de digitalisation

1. Ouvrir le `.md` de `resources/specsplitted/` correspondant à la table.
2. Reformater la table en pipe-markdown (relecture humaine plus simple).
3. Recopier la table en `return [...];` dans `resources/tables/<dossier>/tv_*.php`.
4. Vérifier le compte de lignes : `count(require 'resources/tables/...')` doit correspondre.
5. Spot-check 5 lignes aléatoires contre le PDF.
6. Commit : `[TASK-Bxx] tv_<nom> digitalisée — N lignes`.
