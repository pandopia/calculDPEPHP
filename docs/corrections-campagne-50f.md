# Campagne 50f : besoin mensuel et dimensionnement des chaudières mixtes

Le lot du 4 octobre 2026 ajoute 50 bâtiments distincts aux 250 précédents.
Il comprend 588 cibles par mode (538 logements historiques/actuels et 50
bâtiments), soit 1 764 comparaisons par passage. Les sources ont été figées
avant modification, puis rejouées hors ligne avec le moteur local. Baseline :
`4729d992cdca5cf46f7e2b1d078dc60b51543ae6`.

## Besoin mensuel de chauffage

`BesoinChauffageCalculator` bornait le solde annuel à zéro. Lorsque les pertes
récupérables dépassaient le besoin d'un mois, son solde négatif diminuait les
besoins des autres mois. §9.1.1 p.57–59 définit d'abord `Bchj`, puis la somme
annuelle. La borne physique déjà appliquée au besoin doit donc intervenir sur
chaque mois, avant cette somme, pour les deux scénarios de température.

Le calcul utilise les `Fj` de `FCalculator`, répartit les pertes de distribution
et stockage selon `Nrefj`, et conserve les pertes mensuelles de génération avec
leur propre `Dperj`. Les agrégats annuels publiés de pertes restent inchangés,
hormis l'arrondi flottant de la somme. Le calcul collectif simplifié conserve
sa règle sans récupération. La convention préexistante d'unité des pertes de
génération n'est pas modifiée dans cette correction.

- Bâtiment **1533573**, cible **3814130**, ADEME **2524E2077839Z** :
  besoin 713,975 → 742,120 kWh, référence 742,109 ; EP 55 → 56, référence 56.
- Les excédents mensuels du calcul initial totalisaient 28,144 kWh à 19 °C et
  9,462 kWh à 21 °C : leur soustraction annuelle expliquait l'écart.
- Deux tests échouaient avant correction. Ils couvrent distribution, stockage,
  génération, les deux scénarios et le calcul collectif simplifié.

Source : `resources/specsplitted/09-conso-chauffage/01-installation-seule/01-conso.md`.
La lecture mensuelle est également présente dans le clone local d'open3cl
`src/9_besoin_ch.js` ; son bornage n'y concerne que le conventionnel. Ici les
deux scénarios suivent le même principe de non-négativité du besoin mensuel.

## Lien explicite chauffage vers ECS

`ChaudiereDefautCalculator` reconnaissait la clé commune du XSD et le lien
historique ECS → référence chauffage, mais pas le sens chauffage → référence
ECS. En cascade, plusieurs chaudières peuvent désigner le même ECS tandis que
celui-ci ne nomme que la première dans son lien retour. La seconde chaudière
perdait ainsi sa puissance ECS dans `Pdim = max(Pch, Pecs)` (§13.2.2.4 p.91–92).

La clé commune conserve sa priorité. Les deux sens explicites du format
historique sont ensuite reconnus par comparaison de chaînes, y compris quand
une référence contient des guillemets. Une référence absente ne devient pas un
rapprochement implicite avec l'unique ECS. La convention de virtualisation
historique, suivie séparément dans TASK-K41, reste inchangée.

- Bâtiment **432130**, cible **432139**, ADEME **2659E0940714U** : seconde
  chaudière 5 → 24 kW, EP 226 → 259 (référence 259), GES 40 → 47 (référence 47).
- Cible **432147**, ADEME **2659E0941130U** : EP 163 → 172, GES 25 → 27,
  exactement les agrégats publiés.
- Les **16 logements** de ce bâtiment atteignent le seuil de 1 % après
  correction. Le test dédié à deux chaudières et un lien ECS retour unique
  échouait sur la seconde chaudière avant correction.

## Résultats A/B

| Mode | Cibles | Calculées | Mêmes lettres avant/après | EP et GES ≤1 %, mêmes lettres |
|---|---:|---:|---:|---:|
| XML certifié | 588 | 554 | 554 → 554 | 520 → 537 |
| Chaîne archivée | 588 | 521 | 508 → 508 | 71 → 73 |
| Saisie actuelle | 588 | 521 | 508 → 508 | 68 → 68 |

Aucune nouvelle erreur, lettre dégradée ou perte du seuil de 1 % dans les trois
modes. Les deux cibles améliorées de la chaîne archivée concernent 1533573 ;
la saisie actuelle applique sa période propre, ce n'est pas un rejeu historique.

En mode XML certifié, le contrôle numérique strict à 0,1 % passe de **6 484 à
5 738** valeurs hors tolérance (−746). Les 3 549 valeurs manquantes, 2 909
supplémentaires et 26 écarts non numériques restent inchangés. **Aucune cible
n'est intégralement conforme sur tous ses détails** : le bilan des agrégats
n'est pas une certification réglementaire.

Les 34 erreurs du mode certifié sont des XML complets absents, conservés dans
les dénominateurs. Chaîne archivée : 55 sources absentes et 12 cibles nécessitant
un éclatement. Saisie actuelle : 55 liens métier manquants et ces mêmes 12 cibles.

Corpus de contrôle : mêmes **389 cas**, profil strict, 123 142 valeurs.

| Mesure | Avant | Après |
|---|---:|---:|
| Exactes | 93 323 | 93 324 |
| Dans la tolérance | 18 923 | 18 940 |
| Hors tolérance | 8 851 | 8 833 |
| Manquantes / supplémentaires | 145 / 1 871 | 145 / 1 871 |
| Écarts non numériques | 29 | 29 |
| Suspectes de référence | 3 461 | 3 461 |
| Conformité brute | 91,15 % | 91,17 % |

Un cas amélioré : **2600E0019751W**, −18 écarts ; aucun cas dégradé et aucun
plantage. Aucun changement de tolérance, exclusion, `ReferenceDefects` ou table.
Deux fichiers de calcul seulement changent, identifiés par SHA-256 avant/après.

## Reste à qualifier

17 cibles certifiées restent au-delà de 1 %, avec des lettres exactes :

- **419079**, quatre appartements, dont **2559E3128801Y** : besoins ECS publiés
  à l'immeuble alors que le moteur les ramène au logement moyen ; rendement
  0,854926 contre 0,698657. Échelle du format 2.5 à instruire (TASK-K42).
- **431123**, **2659E0496026K** et **2659E0496027L** : la normalisation des
  exports exhaustifs 2.6 s'applique à une référence dont le chauffage reste
  à l'échelle du logement moyen. Le discriminant de format actuel n'est pas
  suffisant sur ces fichiers (TASK-K42). Ne pas lire les sorties attendues
  pour choisir l'algorithme.
- **1531776** et **1531778**, dix appartements, par exemple **2559E3489516P**
  et **2559E3494101Y** : Pn publiée 180 contre 185 kW calculés, problème
  d'arrondi déjà documenté dans TASK-K31, avec basculement d'un entier GES.
- **2682E0504521A** (1540251/3862474) : CET type 3, depuis 2015, H2c, méthode
  forfaitaire ; référence COP=2,8 alors que §14.2 p.95 donne 2,5. Ce défaut de
  référence n'est pas reproduit. Les besoins de froid restent à instruire.

Autres détails : **2559E2601318D** applique la ligne Ue 10 avec 2S/P=10,791,
contre l'arrondi à 11 prescrit p.18 ; conserver la règle réglementaire.
**2559E3812231S** illustre une ambiguïté d'échelle/unité de production ECS
solaire : moteur `Becs_moy × Fecs × 1000`, référence
`Cecs_moy × 6 × Fecs/(1−Fecs) × 1000`, alors que le XSD demande des kWh à
l'immeuble. Traitement séparé dans TASK-K43.

Le lot ne couvre pas les quotas IFC, hétérogène, appartement isolé ni maisons
regroupées dans le vivier disponible : 24 maisons, 4 bâtiments de deux logements,
12 collectifs individuels homogènes et 10 collectifs sans IFC.

## Validation et livrables

- PHPUnit complet : **1 668 tests, 3 567 assertions, zéro échec**, les quatre
  dépréciations préexistantes. Les trois nouveaux tests ont été constatés rouges
  sur le code initial, puis verts après correction.
- Syntaxe PHP et `git diff --check` : OK.
- Couverture non mesurée, faute de Xdebug/PCOV ; l'objectif de 80 % reste à mesurer.
- Numéros ADEME joints aux 1 764 lignes (554 numéros par mode), vérifiés par
  diagnostic exact, bâtiment/cible et concordance XML lorsqu'il les contient.
- Rapports applicatifs locaux :
  `app.pandopia.com/docs/dpe-campaigns/dpe-campaign-50f/rapport.txt`,
  `validation.json`, `results.json`, `references-ademe.csv`, `source-hashes.json`.
- Sources privées : `app.pandopia.com/tmp/dpe-campaign-50f-frozen` ; scripts et
  journaux : `app.pandopia.com/tmp/dpe-convergence-50f`.
- Pas d'écriture métier, pas de publication. Composer applicatif reste v0.1.28 ;
  cette campagne charge directement ce dépôt local.
