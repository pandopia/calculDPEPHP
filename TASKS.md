# TASKS — travaux restant à reprendre

Ce fichier contient uniquement les tâches **encore actionnables**. Une tâche
réalisée immédiatement dans la conversation n'y est pas ajoutée.

À son achèvement, une tâche dont le travail est livré est **supprimée** : le
commit et l'historique Git en portent la trace. Une tâche dont l'enquête est
close sans changement de code — verdict mesuré, variante écartée, convention
d'éditeur constatée — part dans `TASKS_DONE.md`, pour que ses chiffres ne
soient pas réinstruits.

Statuts : `[ ]` à faire ; `[~ABC]` en cours par l'agent ABC.

## Phase A — Documentation de la spécification

### TASK-A01 — Digitalisation MD verbatim → markdown structuré

- [ ] Owner: __  | Phase: A  | Estimation: variable  | Itératif
- Pour chaque `resources/specsplitted/**/*.md` encore au statut `verbatim` :
  reformater formules et tables, vérifier les indices perdus par `pdftotext`,
  passer à `digitalized`, puis faire relire par un second agent (`reviewed`).

## Phase K — Conformité au corpus ADEME

### TASK-K58 — Doublage de mur : un éditeur minoritaire l'ignore dans umur0

- [ ] Owner: __ | Phase: K | Priorité: basse
- `umur0` reste hors tolérance sur **15 cas / 78 balises**, toutes de la même
  forme : la référence publie la valeur tabulée nue plafonnée à 2,5, sans la
  résistance de doublage que le fichier déclare pourtant.
  2601E0076852C, mur en pisé de 50 cm, `enum_type_doublage_id` = 5 : la table
  §3.2.1.2 p.14 donne 1,55 et la référence publie 1,55 ; nous publions
  1 / (1/1,55 + 0,21) = 1,169. Idem pour ses blocs de béton creux de 20 cm,
  2,8 tabulés → référence 2,5 (le seul plafond), nous 1,763.
- **Variante mesurée et rejetée.** Sortir le doublage de `umur0` pour ne
  l'appliquer qu'à la branche sans isolation rapportée — lecture littérale du
  schéma §3.2.1.1 p.13, où `Umur_nu = Min(Umur0 ; 2,5)` et où
  `Umur_doublage` de §3.2.1.2 n'apparaît pas — coûte **+2 611 écarts** :
  13 816 → 16 427, 105 cas dégradés contre 11 améliorés. Ne pas y revenir.
- **C'est une convention d'éditeur.** Sur les fichiers qui déclarent un
  doublage, les **161 fichiers LICIEL** du corpus intègrent la résistance dans
  `umur0` comme nous ; seuls 9 des 18 fichiers du logiciel « 4.1.1 » ne le
  font pas, plus DPEWIN et deux isolés. §3.2.1.2 p.16 écrit « pour l'ensemble
  des parois », ce qui tranche en faveur de l'intégration.
- Piste d'imputation, non implémentée : un `umur0` publié **exactement à 2,5**
  sur un mur déclarant `enum_type_doublage_id` ∈ {4, 5} est impossible sous
  §3.2.1.2 — la plus grande valeur tabulée est 3,9 (briques pleines simples
  ≤ 9 cm, p.15) et 1 / (1/3,9 + 0,21) = 2,14 < 2,5. La preuve tient sur le
  seul fichier de référence ; elle ne couvre en revanche pas les murs non
  plafonnés, comme le pisé à 1,55.

### TASK-K53 — Besoin d'ECS : deux références s'opposent à déclaration égale

- [ ] Owner: __ | Phase: K | Priorité: basse
- **Tranché pour les fichiers qui déclarent l'absence d'échantillonnage.**
  `enum_calcul_echantillonnage_id` = 1 vaut « absence de méthode
  d'échantillonnage » au XSD, et §17.1.2 p.107 n'introduit l'appartement
  « moyen » que pour extrapoler un échantillon. `2559E3128801Y` est passé de
  84,6 % à 96,1 % ; ses quatre écarts restants sont tous imputés à la
  référence.
- **Reste un désaccord entre références, pas une question de méthode.**
  `2633E1486544G` (14 visites, 14 logements, mode 11) et `2688E0016745Q`
  (7 visites, 7 logements, mode 10) déclarent **tous deux** un échantillonnage
  (`enum_calcul_echantillonnage_id` = 2), portent la même configuration —
  installation ECS individuelle, `nombre_logement` = 1, `rdim` = 1,
  `surface_habitable` couvrant l'immeuble — et leurs références se
  contredisent : le premier publie le besoin de l'immeuble, le second celui
  d'un logement, que nous reproduisons.
- Ne pas départager ces deux-là par un garde ad hoc : relâcher le critère sans
  lire la déclaration coûte +43 sur `2688E0016745Q` pour −25 sur
  `2633E1486544G`. Chercher plutôt une donnée d'entrée qui les sépare, ou
  conclure à un défaut de référence sur preuve interne au fichier.
- `2633E1486544G` porte par ailleurs un écart de `pn` (115 000 contre 120 000)
  qui relève de TASK-K31, à traiter séparément.

### TASK-K42 — 2659E2542205P : référence auto-contradictoire sur cinq balises

- [ ] Owner: __  | Phase: K  | Estimation: 1h  | Priorité: basse
- Dans un seul bloc `<sortie>`, la référence publie `apport_et_besoin/besoin_ch`
  = 109 083,6 kWh (échelle immeuble) et `ef_conso/conso_ch` = 15 420,4 kWh
  (échelle appartement). Une consommation inférieure au besoin qu'elle couvre
  suppose un rendement global de 7,07 pour une chaudière gaz : impossible.
- Quatre autres contradictions internes sur le même fichier :
  `besoin_ch_depensier` (18 788) < `besoin_ch` (109 084) ;
  `besoin_ecs_depensier` (10 522) < `besoin_ecs` (33 553) ; les six balises
  d'auxiliaires à zéro pour un `conso_totale_auxiliaire` de 211,7 ;
  `emission_ges_5_usages` de 4 325,4 pour une somme de `sortie_par_energie` de
  57,0. Les deux premières sont déjà imputées par `ReferenceDefects`.
- Le fichier déclare par ailleurs `cle_repartition_ecs` = 0,0454546, soit
  exactement 84/1848, puis répartit l'ECS sur 24,326 — il n'utilise pas sa
  propre clé.
- Décider s'il relève de `CorpusLocator::REFERENCES_INVALIDES` : la preuve tient
  sur le fichier seul, mais plusieurs autres modes 33/34 du corpus présentent la
  même inversion d'échelle. Les examiner ensemble, pas un par un.

### TASK-K35 — 2113E0368523M : conso_ch et référence lacunaire

- [ ] Owner: __  | Phase: K  | Estimation: 3h  | Priorité: basse
- `conso_ch` : référence 7 462,41, moteur 10 162,50 (+36 %). Notre facteur
  d'émission est pourtant le bon — 3 292,65 / 10 162,50 = 0,324, exactement
  celui du `sortie_par_energie` de la référence pour le fioul. C'est la
  consommation elle-même qu'il faut reprendre, pas l'aval.
- **Fait** : la règle générique « un agrégat incompatible avec la somme de sa
  ventilation par énergie » est implémentée et couvre six balises. Elle
  attribue 81 balises de plus à la référence et porte le plafond de 92,92 % à
  92,97 %. Deux formes au corpus : une ventilation valant exactement le double
  de son agrégat (2133E0741159K, lignes dupliquées) et une ventilation
  entièrement nulle sous un agrégat positif (2659E2596108U, 2659E2236157N).
- Son `version_moteur_calcul` est « inconnu » et elle omet beaucoup
  d'intermédiaires que le schéma prévoit — `pn`, `qp0`, `rpn`, `temp_fonc_*`,
  `b` des planchers, `surface_sud_equivalente`, `deperdition_plancher_*` — d'où
  39 balises comptées comme supplémentaires chez nous. Vérifier d'abord si une
  partie relève d'une règle d'omission déjà connue avant de conclure.
- `umur0 = 2,9` attendu contre 2,5 sur trois murs, et `k = 0,365` contre 0,73
  sur trois ponts : non instruits.

### TASK-K41 — Qualifier Pecs dans les exports sans clé mixte commune

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- Le XSD définit `reference_generateur_mixte` comme une clé identique pour les
  deux usages. Ce format applique désormais Pecs(Vs) au générateur collectif
  avant virtualisation, conformément à §13.2.2.4 et §17.2.1.1.
- La compatibilité des exports avec références croisées ou usage mixte implicite
  conserve provisoirement la convention historique `Pecs / ratio_virtualisation`.
  Sa justification reste à qualifier ; la différence de sérialisation ne suffit
  pas, à elle seule, à établir une différence physique de dimensionnement.
- Variante globale écartée : enlever ce facteur aussi des anciens exports donne,
  en A/B isolé sur 389 cas (révision 86a2162), 8 990 → 9 228 valeurs hors tolérance,
  28 → 30 écarts non numériques, 145 manquantes et 1 871 supplémentaires inchangées.
  Six références s'éloignent : 2400E0007293Q, 2206E1733496F, 2230E1068756F,
  2613E2419802A, 2659E2129582M et 2659E2407381B.
- Reprendre depuis les entrées et les textes officiels, sans élargir les tolérances
  ni choisir une formule selon un identifiant de diagnostic ou d'éditeur.

### TASK-K06 — Écarts résiduels de génération ECS

- [ ] Owner: __  | Phase: K  | Estimation: 4h  | Priorité: moyenne
- Acquis : `rendement_stockage` n'est plus écrit sans ballon ; les puissances
  saisies en `donnee_intermediaire` sont préservées et utilisées ; un chauffe-eau
  thermodynamique ne publie plus de `rendement_generation` séparé, son `cop`
  incluant le stockage (XSD).
- Deux questions de sérialisation sont tranchées, ne pas les rouvrir :
  - `rendement_stockage` sans ballon : 10 références l'écrivent à 1, 191 balises
    supplémentaires seraient créées en le publiant. On ne l'écrit pas.
  - les 12 balises `rendement_generation` surnuméraires restantes portent sur
    des générateurs **à combustion**, pas thermodynamiques : question distincte.
- Reste : 45 divergences de `rendement_generation` en ECS sur 509 cas, aux
  rapports dispersés de 0,49 à 1,66. Le regroupement par type de générateur ne
  dégage pas de famille — treize sur des chaudières gaz standard 2001-2015
  (type 49), neuf sur des gaz à condensation 2001-2015 (56), six sur des gaz à
  condensation après 2015 (57), puis une queue d'une ou deux occurrences sur
  dix autres types, tous à combustion.
- Deux fichiers concentrent huit de ces divergences, 2400E0669495Y et
  2400E0669425G : ils déclarent `nombre_appartement = 1` pour un immeuble de
  822 m² desservi par sept installations d'ECS. L'échelle est le suspect, pas
  le rendement — les instruire avant le reste (cf. TASK-K31).
- Objectif : famille « Génération ECS » au-dessus de 92 % en profil strict
  (84,62 % actuellement).

### TASK-K26 — §17.1.2 : répartition des visites entre sous-ensembles ECS

- [ ] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: basse
- **Le constat d'origine est périmé.** `StockageCalculator` rend désormais le
  facteur 1 dès qu'un seul sous-ensemble ECS couvre l'immeuble : §17.1.2 p.107
  n'introduit `Shmoy_système_i` que pour répartir entre sous-ensembles, et
  « la surface de cet appartement ne dépend pas de la taille des appartements
  visités ». Les cas qui portaient la question sont retombés — `2659E2297555Z`
  à 96,9 % (2 écarts), `2657E1989142W` à 96,3 % (3), `2483E3258186E` à 94,8 %.
- **Ce qui reste.** Le découpage `$offset`/`$count` des logements visités entre
  plusieurs sous-ensembles ECS est une heuristique, non une lecture de §17.1.2 :
  elle attribue les visites dans l'ordre du fichier, au prorata des surfaces.
  Le repli par typologie (`enum_typologie_logement_id`) la précède quand les
  groupes concordent. Seul `2657E1981571R` (100 logements, 20 visites, deux
  installations) en dépend encore — 87,6 %, 25 écarts.
- **Cas à part, à ne pas traiter ici** : `2513E1578589Q` (18 logements,
  2 visites) publie des pertes de stockage récupérées valant exactement
  **1/18** des nôtres, soit celles d'un seul ballon sous un besoin de chauffage
  d'immeuble ; l'écart de `besoin_ch` vaut au chiffre près celui des pertes
  (1 725,38 contre 1 725,25). Son `rendement_stockage` est déjà imputé à la
  référence par `ReferenceDefects`.
- Traiter avec TASK-K06, qui porte sur les mêmes grandeurs.

### TASK-K27 — Circulateur d'une installation qui ne couvre qu'une partie du bâti

- [ ] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: basse
- Acquis : `δθdim` vaut 15 °C quand `enum_temp_distribution_ch_id = 1`
  (« absence de réseau de distribution »), valeur absente du tableau §15.2.1
  p.99 et qui retombait sur 7,5 °C. `conso_auxiliaire_distribution_ch` est
  désormais exacte sur `2400E0046693A`, `2600E0035103I`, `2600E0045286Z`,
  `2600E0045287A`, `2600E0046158N`, `2600E0062930P`, et dans la tolérance sur
  `2600E0099076V`, `2662E2307275Y`, `2688E0016745Q`.
- Reste deux cas, tous deux avec un circulateur dimensionné à l'échelle du
  bâtiment alors que l'installation n'en couvre qu'une partie :
  - `2600E0000098Z` : 2542,0 contre 1875,7 attendu (Pcircem 596,8 contre
    440,5 W). Trois installations, une seule aéraulique, couvrant 879,88 des
    1728,98 m².
  - `2600E0081026P` : 161,4 contre 132,9 (Pcircem 37,9 contre 31,2 W). Une
    installation, deux émetteurs (5 aéraulique + 21 poêle) et deux générateurs
    (3 PAC air/air + 39 insert).
- **Fait** : `emetteurParams` retenait le δθdim du **dernier** émetteur lu, là
  où ΔPem et Fcot prennent le pire cas. Il prend désormais le plus faible —
  le plus exigeant, puisque qvem = Pnc / (1,163 × δθdim) — et le résultat ne
  dépend plus de l'ordre d'écriture. Mesure nulle sur 509 cas : aucune
  installation du corpus n'a deux émetteurs hydrauliques de régimes
  différents. Les variantes min et max donnent toutes deux 13 263 écarts.
- Piste écartée : `rat` calculé sur le besoin plutôt que sur la surface. §15.2.1
  dit « ratio du besoin couvert par l'équipement », mais les `besoin_ch`
  publiés par installation donnent exactement les mêmes ratios que les surfaces
  (0,50890 sur `2600E0000098Z`) — aucun effet sur ces deux cas.
- Restent à départager : l'échelle de `Sh` dans `Lem` et `shFactor`, et le
  traitement d'une installation à plusieurs émetteurs. §15.2.1 dit « Sh :
  surface habitable du bâtiment », ce que le code applique déjà : chercher la
  source avant de s'en écarter, et mesurer en A/B.

### TASK-K46 — Contenu CO2 des réseaux : la colonne ACV est confirmée

- [ ] Owner: __ | Phase: K | Priorité: basse
- **Tranché sur dix-neuf réseaux.** Douze DPE d'appartements chauffés en
  réseau urbain ont été tirés de l'open data en choisissant un facteur
  implicite distinct par fichier, pour maximiser le nombre de réseaux. Neuf
  sur dix reproduisent notre table ACV et notre règle de millésime **au
  chiffre près** : 9502C, 9503C, 9507C, 9509C, 9511C, 9514C, 9515C, 9516C et
  9522C, tous à l'arrêté du 25 avril 2026. Avec les neuf réseaux déjà
  vérifiés, cela fait dix-huit accords.
- **9514C, le réseau de l'énoncé, en fait partie** : 2695E2606311P publie
  0,120, exactement notre millésime 2025. L'observation de 0,097 sur
  2595E1189146F portait sur l'arrêté du 5 juillet 2024, et reste isolée. La
  piste « colonne CO2 direct » n'est pas confirmée.
- **Un seul écart neuf** : 9521C (2695E2521479V, arrêté du 25 mai 2026, un
  mois après les autres) implique 0,00276 quand nos quatre millésimes vont de
  0,239 à 0,261. Vérifier si un arrêté correctif de mai 2026 a révisé ce
  réseau avant de conclure. Le cas est au corpus, à 66,5 %.
- Reste de l'ancien décompte : 5703C à l'arrêté du 5 juillet 2024 (référence
  0,068, nos millésimes de 0,092 à 0,187) et 4211C sans date d'arrêté
  (référence 0,109, nos millésimes de 0,048 à 0,075).
- **Les multi-bâtiment ne sont pas concernés** : §17.3 impose d'y calculer les
  GES « à partir des énergies consommées réellement par les générateurs », ce
  que `ReseauChaleurFactorResolver::combustibleMultiBatiment` fait déjà.
  2695E2608627R le confirme avec 0,227, le facteur du gaz naturel ;
  2695E2427686K, de configuration identique, y applique au contraire le
  forfait « autre réseau » de 0,385.

### TASK-K45 — Ponts thermiques des exports 7.x, après correction Ue

- [ ] Owner: __ | Phase: K | Priorité: haute
- Campagne aléatoire 50g : bâtiment 1382945 / **2369E0794198V**. Après
  reconstruction correcte de Ue (0,33648 ; §3.2.2.1 p.18–19), la synthèse perd
  le seuil de 1 % : EP 276,500 → 266,534, référence 276,029. Lettres E/D stables.
  Les 20 appartements associés gagnent le seuil ; exemple **2369E0794746X**.
- Ponts thermiques bâtiment : 327,75 W/K calculés contre 472,35 publiés.
  Liaison mur/plancher intermédiaire : k=0,46 contre 0,86 ; mur/plancher haut :
  0,75 contre 0,40. Les anciens identifiants tabulés sont prioritaires dans
  `KCalculator`, malgré son commentaire annonçant un calcul depuis les parois.
- Reconstituer les liaisons avec leurs références et paramètres physiques,
  sourcer §3.4 p.32–37, distinguer mapping externe et défaut de référence.
  Ne pas ajuster un ID de table pour retrouver un résultat ni rétablir l'ancien
  Upb incorrect qui compensait les écarts. Voir aussi **2378E0434697F**.
- Sources figées et mesures : `docs/corrections-campagne-50g.md`, corpus privé
  `tmp/dpe-campaign-50g-frozen` du dépôt applicatif.

### TASK-K62 — Quatre stubs renvoient à des TASK qui n'existent plus

- [ ] Owner: __ | Phase: K | Priorité: basse
- `InstallationsIndependantes`, `GenerateurBiEnergie`, `MultiEmissions` et
  `DeperditionMurCalculator` lèvent `LogicException` en renvoyant
  respectivement à TASK-E21, E22, E11 et B09, absentes de `TASKS.md` comme de
  `TASKS_DONE.md`. Relevé en archivant les enquêtes closes.
- Déterminer pour chacun si le cas est couvert ailleurs — `MultiGenerateurs`
  et `InstallationClassique` traitent déjà plusieurs des configurations
  visées — puis supprimer le stub ou lui rouvrir une tâche décrivant ce qui
  manque réellement. Aucun cas du corpus ne les atteint aujourd'hui : un
  `LogicException` levé en production serait une régression silencieuse.

## Validation obligatoire

- Toute correction doit être sourcée (spec/XSD/règlement), testée unitairement
  et mesurée en A/B isolé avec `bin/official-test-report` sur le corpus complet.
- Ne jamais élargir une tolérance ni ajouter de comportement propre à un numéro
  ADEME pour faire baisser un compteur.

## TASK-COMPARATIF — Répartitions collectives restant à implémenter

- [ ] Compléter les répartitions réellement hétérogènes chauffage/ECS et valider la précision des projections approximatives sur les exports Pandopia (§17.2). Les associations absentes, les installations homogènes multiples, le froid homogène et la surface photovoltaïque collective sont maintenant calculables ; les écarts restent à résorber. La campagne de 150 bâtiments est documentée dans `docs/dpe-chain-alpha12` du dépôt applicatif : 1 449 cibles calculables, 1 257 mêmes lettres, 252 proches à 1 %. Priorités : 410578, 406024, 426983 et 378102 ; remonter aux premiers intermédiaires divergents sans recopier la référence.
- [ ] Valider une collection réelle avec un coefficient IFC explicitement saisi : le moteur conserve 0,7 par défaut ; l’adaptateur Pandopia utilise une approximation surfacique tracée pour les anciens fichiers sans coefficient.
- [ ] Auditer l’autoconsommation photovoltaïque des appartements générés (§16.2) : vérifier l’échelle des consommations lues dans les générateurs et la conversion primaire/finale, au-delà de la répartition des capteurs. Mesurer en A/B sur des cas collectifs et individuels.
- [ ] Exécuter la couverture PHPUnit avec Xdebug (indisponible dans le runtime utilisé pour cette livraison) et vérifier l’objectif de 80 %.

### TASK-K48 — Entrées des appartements du bâtiment 52041673587983

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- Après correction des caractéristiques chaudière sans TV, les 17 logements
  passent de −16/−17 % EP à +0,3/+2,7 %. Trois derniers étages restent au-delà
  de 1 % ; 2916082 et 2916093 donnent G contre F dans le retour Liciel.
- Envoi Pandopia 769195 du 30/06/2026, sans numéro ADEME et sans XML complets
  propres aux appartements. Qualifier les conversions d’entrées (régulation,
  comble, période d’émetteur) et les clés de besoin avant de corriger §17.2.
  Copier les paramètres du XML bâtiment dégrade plusieurs logements : aucune
  substitution ni tolérance ad hoc. Reproduction et variantes :
  `docs/correction-chaudiere-sans-table-52041673587983.txt` ; instantanés privés
  `tmp/dpe-case-52041673587983` dans le dépôt applicatif.

### TASK-K49 — Écarts des précalculs sur les sites Complété

- [ ] Owner: __ | Phase: K | Priorité: haute
- Audit du mode automatique sur 50 sites `complete` : 104/180 références
  exploitables à 1 % et mêmes lettres, 171/180 mêmes lettres. L'effet isolé
  de b09140d fait perdre le seuil à 18 lignes (1600943 : quatre logements ;
  13804186453804 : 14 lignes), pour un gain. Aucun XML complet ne régresse.
- Qualifier les besoins chauffage et la répartition §17.2 : 1600943 / envoi
  805191, Bch 37 368 contre 39 959 ; 1598148 / 785622, 57 250 contre 51 269 ;
  13804186453804 / 784575, 28 560 contre 29 843. ECS reproduite. Références
  Liciel partielles, sans numéro ADEME. Ne pas rétablir Rg=1 ni ajuster les
  lettres pour compenser ces désaccords d'entrée/calcul en amont.
- Audit détaillé : `docs/audit-sites-complete-2026-10-04.txt` ; sources privées
  dans `tmp/dpe-campaign-tester` du dépôt applicatif (50 auto.json figés).

### TASK-K50 — Export Pandopia des PAC hybrides

- [ ] Owner: __ | Phase: intégration | Priorité: haute
- 4568190766945 / logement 4696172, envoi 805106, XML du 01/10/2026,
  sans ADEME : EP +23,71 %, GES −53,53 % sur la chaîne (lettre B/A vs B/B).
- L'export a deux générateurs chauffage 147/149 partageant la même référence
  et l'énergie 1 ; le type 149 est une chaudière gaz. Il copie aussi 149
  dans l'énumération ECS où il n'existe pas. Le moteur ne sort qu'une énergie.
- Corriger/qualifier d'abord le contrat d'export et les associations :
  `Connecteurs_Liciel` vers les lignes 3427 et 3920 dans l'application.
  Ne pas imposer de facteur gaz à tout générateur électrique dans le moteur.
  Mesurer les branches chauffage/ECS sur des entrées valides et comparer les
  retours archivés. Preuves : même rapport et corpus privé que TASK-K49.

### TASK-K57 — Branches du moteur nouvellement couvertes : écarts restants

- [ ] Owner: __ | Phase: K | Priorité: haute
- Le jeu `ademe-branches-2026-10` (76 cas, 19 branches, manifeste suivi)
  couvre les configurations §9.x, ventilations et générateurs que le corpus
  stratifié n'atteignait pas. Après huit corrections livrées, les branches
  encore basses sont, par écarts par cas :

  | Branche | Conf. | Écarts/cas |
  |---|---:|---:|
  | cfg-releve-chaudiere-bois | 80,2 % | 76,8 |
  | cfg-chauffage-solaire (§9.2) | 79,7 % | 62,2 |
  | cfg-solaire-insert-poele (§9.6) | 83,1 % | 56,5 |
  | puits-climatique-echangeur | 81,5 % | 53,5 |
  | froid-pac-eau-eau | 80,4 % | 53,0 |
  | chaudiere-bois-granules | 81,4 % | 49,2 |
  | ventilation-hybride | 79,8 % | 48,0 |

- **Rpn des chaudières bois récentes : la référence contredit la table.**
  §13.2.2.3 p.90 donne, pour une chaudière bûche ou plaquette >2019 de 20 à
  70 kW, `Rpn = 90 + 2 logPn` et `Rpint = 85 + 2 logPn` — c'est notre lecture.
  2601E0138446C publie 99,0309 et 94,0309, soit les mêmes constantes avec le
  coefficient **6**, celui des lignes antérieures à 2013. Les granulés >2019
  donnent un troisième jeu encore (2400E4039624H et 2400E0180612T suivent
  « 89 + 6 logPn » quand la table dit « 91 + 2 logPn », et les deux cas à
  Pn = 4,828 kW ne suivent ni l'un ni l'autre). Ne rien changer sans un texte.
- `production_ch_solaire` reste faux sur les installations multiples :
  §9.6 p.65 donne Prod = Bch × Fch × Σ facteur_i INT_i Ich_i, exact sur
  2400E1027746V et 2601E0263293X, mais le rapport tombe à 0,85 et 0,52 sur
  les deux installations de 2601E0370414Y.
- `sortie_par_energie` ne porte pas de ligne pour
  `enum_type_energie_id = 12` (« électricité d'origine renouvelable utilisée
  dans le bâtiment ») : 2601E0370414Y en attend une complète.
- `rendement_stockage` = 1 : 27 références le publient, plus de 300 l'omettent
  comme nous. Convention d'éditeur, rien à corriger (cf. TASK-K06).

