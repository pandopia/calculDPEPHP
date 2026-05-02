# calculDPE — guide pour les IAs collaboratrices

## Objectif du projet

Implémenter en PHP la spécification officielle **DPE 3CL-2021** (Diagnostic de Performance Énergétique pour logements existants, Annexe 1 — méthode de calcul, octobre 2021).

Le CLI cible : `bin/calcul-dpe input.xml` → enrichit le XML avec les balises `<donnee_intermediaire>` et `<sortie>` calculées selon la méthode 3CL.

## Chemins clés

| Chemin | Rôle |
|---|---|
| `resources/spec.pdf` | Spec officielle, 147 pages, 18 sections. **Source de vérité.** |
| `resources/ademe_DPE.xsd` | Schéma XML d'entrée (1.6 Mo). 74 enums, 35 tables `tv_*_id`. |
| `resources/specsplitted/` | Spec découpée en `.md` par section (header YAML + verbatim). |
| `resources/tables/` | Tables `tv_*_id` digitalisées en `return [...];` PHP. |
| `resources/XML/input/` | Exemples d'entrée (4 fichiers). |
| `resources/XML/verif/` | Mêmes fichiers avec les balises calculées attendues. |
| `src/` | Code PHP, namespace racine `CalculDpe\\`, organisé par domaine. |
| `tests/EndToEndTest.php` | Harness : input/*.xml → engine → diff vs verif/*.xml (tolérance 1e-3). |
| `bin/process-xml` | Outil legacy : prépare un fichier verif en générant un input épuré. |
| `bin/calcul-dpe` | CLI principal qui exécute le moteur de calcul. |
| `TASKS.md` | Liste des tâches à faire, organisées en phases A→G, avec checkboxes. |

## Workflow de calcul

```
input.xml → XmlSanitizer (purge sortie) → DpeEngine::run()
          → CalculatorPipeline (tri topologique des Calculators)
          → DOM enrichi → save → output.xml
```

Le moteur est **idempotent** : on peut le relancer sur un fichier déjà calculé, le sanitizer purge les balises de sortie d'abord.

## Convention de traçabilité (OBLIGATOIRE)

Toute classe `Calculator` PHP DOIT porter ces tags dans son doc-block :

```php
/**
 * Description courte.
 *
 * @spec-section 3.2.1
 * @spec-pages 13-16
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/01-umur/00-calcul.md
 * @xml-input  enveloppe.mur_collection.mur.donnee_entree.{materiau, epaisseur, …}
 * @xml-output enveloppe.mur_collection.mur.donnee_intermediaire.{umur, umur0}
 * @depends-on \CalculDpe\Enveloppe\Mur\Umur0Calculator
 * @tables tv_umur_id, tv_umur0_id
 */
```

Toute formule importante :
```php
/**
 * §3.2.1 p.13 :
 *   Umur = U0 / (1 + U0 × R_isolant)   si isolation rapportée
 *   sinon Umur = lecture tv_umur_id (matériau, épaisseur)
 *
 * @spec-formula F-3.2.1-a
 */
private function compute(...): float { … }
```

Tout fichier de table dans `resources/tables/` :
```php
<?php
/**
 * @spec-section 3.2.1
 * @spec-pages 13-14
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/01-umur/01-table-umur-connu.md
 * @generated-on 2026-04-29
 */
return [
    1 => ['materiau' => 'mur_brique_pleine', 'epaisseur_min_cm' => 0,  'epaisseur_max_cm' => 22, 'umur' => 2.30], // p.13 ligne 1
    // …
];
```

**Règles d'or** :

1. **Traçabilité** : si tu ne peux pas pointer une formule ou une valeur de table vers un numéro de page de la spec, tu n'as pas le droit de la coder.

2. **Tables exhaustives** : toute table digitalisée doit couvrir **l'intégralité de l'espace d'index** défini par la spec ou par le XSD, pas seulement les valeurs rencontrées dans les fichiers d'exemple. Une table partielle est un bug en attente : elle marche tant que personne n'envoie un XML hors-échantillon, puis crashe en production. Si une table semble trop volumineuse pour être faite en une fois, découpe-la en sous-tâches indépendantes — mais ne la marque jamais comme « terminée » tant qu'il manque une entrée. Les exceptions doivent être annoncées explicitement avec `@status partial — manque X` dans le doc-block et une tâche TASK-Xxx dédiée pour la compléter.

3. **Pas de mapping interne LICIEL** : certaines balises XML d'entrée portent des IDs `tv_*_id` qui résultent de tables internes au logiciel diagnostiqueur (LICIEL, etc.) plutôt que de la spec officielle. Quand l'indexation n'est pas définie par la spec elle-même, **calcule à partir des paramètres directs** disponibles ailleurs dans `<donnee_entree>` (enum_type_vitrage, enum_type_gaz_lame, vitrage_vir, etc.) plutôt que de faire un lookup `tv_*_id`. La table digitalisée doit alors être indexée par ces paramètres, pas par l'ID externe.

## Workflow multi-IA (TASKS.md)

Plusieurs agents IA travaillent en parallèle sur ce repo. Pour éviter les collisions :

1. **Avant de prendre une tâche** dans `TASKS.md`, change la checkbox de `[ ]` à `[~ABC]` (ABC = tes initiales) et commit ce changement seul.
2. **Une seule tâche `[~]` à la fois par agent.**
3. **Ne touche jamais une tâche `[~XYZ]` qui n'est pas la tienne** — même pour "améliorer".
4. Quand tu termines : passe à `[x]`, commit avec message `[TASK-xxx] description courte`.
5. Si tu découvres qu'une tâche est mal spécifiée : ajoute un commentaire `> NOTE-ABC: …` sous la tâche, et ouvre une nouvelle tâche à la fin du fichier plutôt que de modifier la spec d'une tâche existante.
6. **Ne dépasse jamais le scope d'une tâche.** Si tu vois un problème ailleurs, crée une tâche `TASK-Xnn` à la fin, ne corrige pas en passant.

## Architecture PHP (résumé)

- `CalculDpe\Engine\` : `CalculatorInterface`, `CalculationContext`, `DpeEngine`, `CalculatorPipeline` (tri topologique sur `dependencies()`).
- `CalculDpe\Xml\` : `XmlReader`, `XmlWriter`, `NodeAccessor` (helpers `getFloat/getInt/getEnum` + normalisation virgule→point).
- `CalculDpe\Tables\` : `TableRepository` qui charge à la demande les fichiers `resources/tables/**/*.php`.
- `CalculDpe\Common\` : `Period` (PRE_2026 / POST_2026), `Energy`, `Math`.
- `CalculDpe\Enveloppe\{Mur,PlancherBas,PlancherHaut,BaieVitree,Porte,PontThermique}\` : un Calculator par grandeur calculée.
- `CalculDpe\Ventilation\`, `CalculDpe\Apport\`, `CalculDpe\Inertie\`, `CalculDpe\Intermittence\`.
- `CalculDpe\Chauffage\` (avec sous-dossier `Strategy/` pour les 12 cas §9.1 à §9.11), `CalculDpe\Froid\`, `CalculDpe\Ecs\`.
- `CalculDpe\Auxiliaire\`, `CalculDpe\Eclairage\`, `CalculDpe\ProductionElec\`.
- `CalculDpe\Sortie\` : agrégateurs finaux (EF, EP, GES, coût, classes A→G).
- `CalculDpe\Collectif\` : §17 (immeuble, appartement, multi-immeuble, mixte).

## Tolérance des tests E2E

- Défaut : 1e-3 (différence absolue normalisée par la valeur attendue).
- Surcharges par balise dans `tests/tolerances.php` (ex : `'conso_5_usages_m2' => 1` car arrondi entier dans le verif).

## Référence open3cl (à utiliser en complément de la spec)

Le projet open-source **open3cl** (JavaScript/TypeScript) implémente la même méthode 3CL-DPE. Son code source est une référence précieuse pour lever des ambiguïtés de la spec PDF :

- **Dépôt** : `https://github.com/Open3CL/engine`
- **Clone local** : `/tmp/open3cl/` (clone non persistant — re-cloner si besoin : `git clone --depth=1 https://github.com/Open3CL/engine.git /tmp/open3cl`)
- **Fichiers clés** :
  - `src/13.2_generateur_combustion_ch.js` — rendement annuel moyen (profil de charge, QPx, Cdimref)
  - `src/9_chauffage.js` — stratégies chauffage, tauxChargeForGenerator, Cdimref
  - `src/2021_04_13_confort_ete.js` — indicateur confort d'été
  - `src/engine.js` — point d'entrée, ordre des calculs
  - `src/enums.js` — valeurs des enums XSD

**Quand utiliser open3cl** : lorsqu'une formule de la spec est ambiguë, incomplète ou donne des résultats différents du verif. Comparer l'implémentation JS avec notre PHP pour détecter les divergences.

**Écart connu** : la spec textuelle peut omettre des détails d'implémentation présents dans open3cl (ex : Cdimref calculé sur la puissance TOTALE de tous les générateurs d'une installation, puis utilisé avec GV_ratio corrigé par `rdim`).

## Notes utiles

- Le code legacy `src/XmlSanitizer.php` est conservé : il est appelé par `DpeEngine` au début de chaque exécution pour garantir l'idempotence.
- La méthode 3CL distingue **pré-2026** et **post-2026** (coefficients de conversion énergie finale → primaire pour l'électricité). Voir `CalculDpe\Common\Period`. Les 4 exemples couvrent les 2 régimes (2 pré + 2 post).
- L'XSD contient les valeurs textuelles des enums dans `<xs:appinfo>` (pas besoin d'une table externe pour les enums).
- Les tables `tv_*_id` ne contiennent que des IDs entiers dans l'XSD — les valeurs réelles sont dans les tables PDF, à digitaliser dans `resources/tables/`.

## Couverture de tests (OBLIGATOIRE)

**Objectif : 80 % de couverture de code minimum**, mesuré par PHPUnit + Xdebug.

Règles :
1. **Chaque Calculator doit avoir un test unitaire dédié** dans `tests/Unit/{Domaine}/{NomCalculator}Test.php`.
2. Les tests unitaires couvrent les cas nominaux + les branches alternatives (valeurs nulles, méthodes différentes, court-circuits, fallbacks).
3. Les tests E2E (`tests/EndToEndTest.php`) complètent la couverture mais ne la remplacent pas : un Calculator sans test unitaire = tâche incomplète.
4. Les tables PHP (`resources/tables/**/*.php`) ne sont pas testées directement (elles ne contiennent pas de logique), mais leurs valeurs sont validées indirectement par les tests E2E.

Commandes de couverture :
```bash
vendor/bin/phpunit --coverage-text          # rapport terminal
vendor/bin/phpunit --coverage-html coverage/ # rapport HTML dans coverage/
```

Pour atteindre 80 % : penser aux branches `match` avec `default`, aux `if ($x === null) return`, aux cas où les tables ne retournent pas de valeur, etc.

## Commandes courantes

```bash
composer dump-autoload
php -l src/...           # lint syntaxe
vendor/bin/phpunit       # tous les tests
vendor/bin/phpunit --coverage-text          # avec couverture
vendor/bin/phpunit --filter EnveloppeTest
php bin/calcul-dpe resources/XML/input/zone_post2026coefelec_diag2356755.xml
```
