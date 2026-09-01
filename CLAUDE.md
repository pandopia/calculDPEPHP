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
| `bin/official-test-report` | **Juge de conformité** : compare toutes les balises calculées à la référence, écrit `reports/official-tests.{json,md}`. |
| `bin/fetch-official-corpus` | Construit un jeu de cas stratifié depuis l'open data ADEME, avec manifeste de provenance. |
| `resources/XML/official/` | Jeux de tests documentés (manifestes suivis en git, XML non versionnés). |
| `src/Conformite/` | Outillage de mesure d'écart (extraction, familles, tolérances, rapport). |
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

## Mesure de conformité (phase K) — à lire avant de « corriger » quoi que ce soit

### Pourquoi

Le but du projet n'est pas de faire passer `tests/EndToEndTest.php`, c'est
d'être **conforme à la méthode réglementaire**. Le harness E2E ne peut pas
servir de juge : il ne compare qu'une liste de balises « couvertes », exclut
des balises fichier par fichier (`TAGS_EXCLUDED_BY_FILE`), et son indexation de
chemin ignore les fratries homonymes — sur un logement à 30 murs, un seul
`umur` est réellement vérifié. Un moteur peut donc être vert et faux.

`bin/official-test-report` mesure l'écart réel : **toutes** les balises de
`<donnee_intermediaire>` et `<sortie>`, chemins indexés, aucune exclusion, une
balise attendue mais non produite comptant comme non conforme.

### Statut des jeux officiels CSTB

**Ils ne sont pas diffusés publiquement.** Le règlement d'évaluation les
annonce téléchargeables, mais la seule voie d'accès est la plateforme éditeurs
<https://app.rt-batiment.fr/evaluation_logiciel/>, protégée par
authentification ; aucun miroir public n'existe, et aucun seuil de tolérance
chiffré n'est publié. La mesure se fait donc contre les DPE opposables de
l'observatoire ADEME (Licence Ouverte 2.0), produits par des logiciels évalués
CSTB. Détail des sources : `resources/XML/official/README.md`.

Conséquence pratique : **la référence n'est pas la vérité réglementaire, c'est
la sortie d'un autre logiciel**. Un écart peut venir de nous *ou* de lui. Le
rapport ventile par `version_moteur_calcul` justement pour ça : un écart
concentré sur un seul moteur éditeur est suspect côté référence.

### Comment mesurer

```bash
php bin/official-test-report                          # rapport complet
php bin/official-test-report --famille="Coûts" --top=40
php bin/official-test-report --filter=2657E1981571R
php bin/official-test-report --tolerance=reglementaire # 1 % au lieu de 0,1 %
php bin/official-test-report --list-corpora
```

Sorties : `reports/official-tests.md` (suivi en git, c'est la baseline lisible)
et `reports/official-tests.json` (~10 Mo, non suivi, régénérable).

Profils : `strict` (0,1 %, défaut, sert de juge), `reglementaire` (1 %),
`repo` (reprend `tests/tolerances.php`, pour comparer au harness historique).
**N'élargis jamais une tolérance pour faire baisser un compteur.**

### Règle de mesure d'un gain (impérative)

Plusieurs agents modifient le moteur en parallèle et le corpus s'enrichit :
comparer deux rapports pris à des moments différents ne veut **rien** dire. Un
gain se mesure en A/B isolé, sur le même arbre, en ne changeant que le code
évalué :

```bash
cp src/Mon/Calculator.php /tmp/mine.php
git checkout HEAD -- src/Mon/Calculator.php
php bin/official-test-report --quiet --json=/tmp/before.json --md=/tmp/before.md
cp /tmp/mine.php src/Mon/Calculator.php
php bin/official-test-report --quiet --json=/tmp/after.json --md=/tmp/after.md
```

Puis compare `summary.out_of_tolerance`, `extra`, `missing` et `by_famille`.
Chaque rapport porte la révision git et le nombre de fichiers `src/` non
commités : si ce nombre change entre deux runs, la comparaison est invalide.

### Point de départ et acquis

| Étape | Hors tolérance | Suppl. | Conformité |
|---|---:|---:|---:|
| Baseline — 1er sept. 2026, 224 cas | 9 723 | 2 280 | 83,05 % |
| K02 — tarifs indexés sur la date du DPE | −1 099 | | 88,21 % |
| K03 — balises hors schéma (`Qgw`, `pveil`) | | −337 | 88,57 % |
| K10 — tranche tarifaire par abonnement | −914 | | 90,57 % |
| K04 — taux de charge sur la puissance installée | −20 | | 90,60 % |
| K14 — classe d'inertie hors emplacement | | −229 | 90,89 % |
| K06 — rendement de stockage sans ballon | | −191 | 91,13 % |
| **Actuel — 229 cas** | **4 922** | **1 356** | **91,13 %** |

Sur les écarts restants, **1 873 sont imputables à la référence** : le plafond
réellement atteignable sur ce corpus est de **93,74 %**. Le rapport l'affiche.

Quatre leçons à retenir de ces corrections :

1. **Le barème dépend de la date du DPE** (K02). Les tarifs des énergies sont
   réactualisés par arrêté ; la référence applique celui en vigueur à
   `date_etablissement_dpe`. Table : `resources/tables/reference/tv_prix_energie.php`.
2. **Une grandeur intermédiaire ne passe jamais par le XML** (K03, K14). Le
   moteur écrivait `Qgw`, `pveil` et `enum_classe_inertie_id` dans des
   emplacements que le schéma ne déclare pas — un fichier les contenant serait
   rejeté par l'ADEME. Un canal entre Calculators passe par
   `CalculationContext`. Le rapport contrôle ça en permanence (section
   « Conformité structurelle du XML produit ») : garde-la vide.
3. **La référence a ses propres défauts** (K05, K12). 1 873 écarts viennent de
   fichiers qui contredisent leur propre schéma — coût dépensier recopié du
   coût conventionnel, bloc `<confort_ete>` vide. `ReferenceDefects` les
   détecte et les isole. **Ne les « corrige » pas** : reproduire le défaut d'un
   logiciel tiers éloigne le moteur de la méthode.
4. **Une hypothèse se départage sur le corpus entier, pas sur un cas** (K10,
   K13). Un diviseur qui reproduit exactement un cas peut dégrader l'ensemble.
   Mesure chaque variante en A/B et consigne les chiffres, y compris ceux des
   variantes écartées, pour que personne ne refasse l'expérience.

### Ce qu'on attend d'une correction

- Justifiée par la méthode 3CL, le XSD ADEME, un texte réglementaire, ou une
  incohérence démontrée de notre implémentation — **jamais** par l'observation
  d'un fichier de test.
- **Aucun `if ($dpeId === 'XXXX')`**, aucun comportement spécifique à un cas.
- Remonter au **premier intermédiaire divergent** avant de toucher un agrégat :
  une classe DPE fausse est presque toujours une consommation fausse en amont,
  pas un seuil faux.
- Une tâche `TASK-Kxx` dans `TASKS.md`, un test de non-régression, le gain
  mesuré en A/B, et la suite unitaire verte.

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

php bin/official-test-report                     # rapport de conformité complet
php bin/official-test-report --famille="Coûts" --top=40
```
