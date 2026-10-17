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

### TASK-A01 — Digitalisation des `specsplitted` : ce qui reste vraiment

- [ ] Owner: __  | Phase: A  | Estimation: variable  | Itératif
- **Le retard est apparent, pas réel.** 100 des 103 fichiers de
  `resources/specsplitted/` portent encore `status: "verbatim"`, mais la
  couverture du moteur, elle, est complète : les **119 sections du sommaire**
  sont toutes revendiquées par au moins un `@spec-section`, et **142 des 147
  pages** de l'annexe sont citées par un `@spec-pages` — les cinq restantes
  sont le sommaire lui-même (p.2-3 et 5). Mesuré, pas supposé.
- **Les listes « tables à transcrire » sont périmées.** Sur les 30 `tv_*_id`
  encore cochés `[ ]` dans les markdown, **25 ont un fichier dans
  `resources/tables/`** depuis longtemps, deux de plus existent sous un autre
  nom (`tv_upb_tab`, `tv_uph_tab`), et les trois derniers sont implémentés en
  dur : `tv_deltar_id` dans la clé `fermeture_to_dr` de `tv_ujn.php`
  (exhaustive, ids 2 à 8 du XSD), `tv_scop_id` et
  `tv_rendement_generation_id` en constantes de
  `GenerationNonCombustionCalculator`. **Zéro table réellement en attente.**
  Cocher les cases, ou retirer les listes.
- Sous-tâche repérable : `tv_scop_id` et `tv_rendement_generation_id` sont les
  deux seules tables de la méthode qui vivent en `private const` plutôt que
  dans `resources/tables/`. Les y déplacer alignerait la convention, à coût
  nul pour le calcul — mais c'est un déplacement, pas une correction : mesurer
  en A/B pour le prouver.
- **Ce qui reste donc** est le travail de forme annoncé par les en-têtes :
  formules en LaTeX, tables en pipe-markdown, indices et exposants perdus par
  `pdftotext` à rétablir, puis `status` → `digitalized` et relecture par un
  second agent → `reviewed`. Le moteur n'en dépend pas : il est tracé au PDF
  directement. C'est de la relisibilité, à traiter section par section.
- Priorité de relecture suggérée, là où une coquille d'extraction coûterait le
  plus cher : §18.2-18.3 (sollicitations, déjà deux défauts d'annexe trouvés),
  §13.2.2 (valeurs par défaut des chaudières, trois régimes d'arrondi en
  litige dans TASK-K31) et §3.2.1.2 (tables Umur0, 14 colonnes serrées).
- **Garde en place** : `tests/Unit/Tracabilite/AnnotationsSpecTest.php`
  vérifie désormais que toute page citée tient dans les 147 de l'annexe, que
  tout `@spec-source` en `resources/` désigne un fichier existant, et que
  toute section découpée est revendiquée par le moteur. Il a attrapé §18.5,
  dont la table C1 annonçait `@spec-pages 144` pour quatre pages et pointait
  un `00-calcul.md` jamais créé.

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
- **Imputation livrée.** `doublageDeMurDeclareMaisIgnore()` signale tout
  `umur0` publié au-dessus de `1 / (1/3,90 + 0,21) = 2,144` sur un mur qui
  déclare `enum_type_doublage_id` ∈ {4, 5} et une saisie tabulée : la plus
  grande valeur de §3.2.1.2 étant 3,90 (briques pleines simples ≤ 9 cm, p.15),
  aucune autre origine n'est possible. **52 écarts imputés**, 2 663 → 2 715.
  La règle ne vaut pas pour `Rdoublage` = 0,10, où le plafond de 2,5 reste
  atteignable (2,806), ni pour les murs sous la borne — le pisé à 1,55 du
  même fichier reste non démontrable.
- Reste donc à départager, si un texte le permet un jour : les murs dont la
  référence publie la valeur tabulée nue **sous** 2,144.

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
- **La racine est en amont, pas dans la génération ECS.** Sur les 45
  divergences de `rendement_generation` en ECS, **43 s'accompagnent d'un
  `rpn`, `rpint`, `qp0` ou `pn` faux sur le même fichier** : le résidu de
  cette tâche est celui des caractéristiques de chaudière, donc TASK-K31 et
  TASK-K56, pas un problème de génération. Ne pas chercher de famille par
  type de générateur, c'était la fausse piste.
- Des deux écarts isolés, l'un est corrigé — §12.4.1 p.76 donne Rg = 0,97 aux
  chaudières électriques et l'ECS leur donnait 1 — et l'autre appartient à la
  référence lacunaire de TASK-K35.
- Deux fichiers concentrent huit de ces divergences, 2400E0669495Y et
  2400E0669425G : ils déclarent `nombre_appartement = 1` pour un immeuble de
  822 m² desservi par sept installations d'ECS. L'échelle est le suspect, pas
  le rendement — les instruire avant le reste (cf. TASK-K31).
- **COP des chauffe-eau thermodynamiques : tranché, c'est la référence.**
  Sur les neuf cas en écart, notre valeur est **à chaque fois** celle de la
  table §14.2 p.95 lue sur la bonne ligne, et la référence est au-dessus :
  2400E0020849A (type 3 « CET air ambiant 2015-2026 », H1a → 2,5) publie 2,8 ;
  2401E0359228Q (type 2, 2010-2014, H1c → 2,2) publie 2,5 ; 2601E0981468A
  (type 12 « PAC double service », H1c → 2,3) publie 2,6, la valeur H3.
  **Deux fichiers aux entrées identiques se contredisent** : 2600E0099076V et
  2400E4097217K déclarent tous deux zone H2c, type 6, période 3 et saisie
  forfaitaire, pour des COP de 3,8 et 3,2.
- `copForfaitaireHorsTable()` impute les trois dont la valeur n'existe nulle
  part dans §14.2 — 3,8, 3,2 et 3,07 sous `enum_methode_saisie_carac_sys_id`
  = 1. Les six autres sont des valeurs du tableau lues sur la mauvaise ligne :
  les départager demanderait de refaire le calcul, pas de lire le fichier.
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

### TASK-K65 — Réseaux de chaleur : deux millésimes manquent, et le choix est approché

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- `arrete_reseau_chaleur.json`, copié de l'amont ADEME dans
  `editeur/public/schemas/`, donne la correspondance **officielle** : six
  arrêtés, chacun avec sa plage d'application et son lien Légifrance.

  | Millésime | Arrêté | En vigueur |
  |---|---|---|
  | 2020 | 2020-10-12 | 2020-10-17 → 2022-01-18 |
  | 2021 | 2021-10-21 | 2022-01-18 → 2023-04-23 |
  | 2022 | 2023-03-16 | 2023-03-16 → 2024-09-20 |
  | 2023 | 2024-07-05 | 2024-07-05 → 2025-07-01 |
  | 2024 | 2025-04-11 | 2025-04-11 → 2026-09-01 |
  | 2025 | 2026-04-25 | 2026-05-10 → … |

- **`tv_reseau_chaleur` ne porte que 2022 à 2025.** Les millésimes 2020 et
  2021 manquent, alors que la méthode 3CL s'applique depuis le 1er juillet
  2021 : tout DPE antérieur au 16 mars 2023 relève de l'un des deux.
- **Le choix du millésime est anachronique pour ces DPE.**
  `ReseauChaleurFactorResolver` prend `max(2022, année(date) − 1)` : un DPE du
  6 avril 2022 se voit appliquer l'arrêté du **16 mars 2023**, publié après
  lui. C'est la faute que TASK-K02 a corrigée pour les tarifs d'énergie.
  `2242E2979513I` en est le cas : sa référence implique 0,109 pour `4211C`
  quand nos millésimes 2022 et 2023 donnent 0,075.
- La règle `année − 1` ne vaut d'ailleurs pas pour les deux plus anciens :
  l'arrêté 2021-10-21 porte le millésime **2021**, pas 2020. Remplacer
  l'approximation par les plages du JSON, qui sont la donnée officielle.
- Ordre de travail : digitaliser d'abord les deux millésimes depuis leurs
  liens Légifrance (colonne « Contenu CO2 ACV »), **puis** basculer sur les
  plages — faire l'inverse ferait tomber les DPE anciens sur le forfait
  « autre réseau » de 0,385, pire que le plafonnement actuel.
- Reste indépendant de ce chantier : `5703C` au millésime 2023 (arrêté du
  5 juillet 2024), où `2457E3582864U` implique 0,068 quand nous lisons 0,092.
  Vérifier la ligne dans l'arrêté avant de conclure.

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

### TASK-K63 — Modèle DPE 2.7 : EPBD, nouvelles technologies, pef élec

- [ ] Owner: __ | Phase: K | Priorité: haute
- L'ADEME a publié le **2026-10-16** `DPEv2.7.xsd` (V10.0.0), copié dans
  `editeur/public/schemas/` avec `enums.json`, `CHANGELOG.md` et consorts
  (`SOURCE.md` porte la date de synchronisation). `enum_version_id` = 2.7 vaut
  « changement de pef elec, epbd et nouvelles techno ». Le moteur ne connaît
  que 2.6 ; `schema-registry.ts` de l'éditeur s'arrête aussi à 2.6, et
  `xsd-compiler.ts` annonce un inventaire « DPEv1 à DPEv2.6 » à revérifier.
- **Deux blocs de sortie deviennent obligatoires** et nous ne les produisons
  pas — ils compteraient en balises manquantes sur tout fichier 2.7 :
  `logement/sortie/production_enr` (neuf balises) et
  `logement/sortie/mention_a0`.
- **Le texte est identifié** : arrêté du 11 juin 2026 « portant diverses
  dispositions d'adaptation au droit de l'Union européenne sur le diagnostic
  de performance énergétique », `JORFTEXT000054673312`.
  - `mention_a0` — son annexe 1 point 3 : étiquette **A ou B**, énergies
    « exclusivement des énergies renouvelables sur site ou à proximité, de
    l'électricité, ou de l'énergie provenant d'un réseau de chaleur ou de
    froid considéré comme efficace » (art. L. 711-4 du code de l'énergie), et
    **aucune installation de chauffage ou d'ECS pouvant utiliser des énergies
    fossiles** sur site ou à proximité. Le logigramme d'attribution n'est pas
    dans le texte HTML.
  - `production_enr` — son annexe 2 point 1.2, une sous-section par
    composante : 1.2.1 `Prod_enr_PAC` (variables `Cch_pac_j`, `SCOPj` ; les
    PAC hybrides ont une expression distincte par groupe de zones H1, H2,
    H3), 1.2.2 `Prod_enr_thermodynamique` (`Cecsj`, `COPj`), 1.2.3 `Ppv`
    (renvoi à l'annexe 1 de l'arrêté du 31 mars 2021), 1.2.4
    `Prod_enr_sol_therm` (`Bchj`, `INTj`, `Fch`, `Ichj`, `Becsj`, `Fecs`,
    `Iecsj` — le `Fch` du §18.4 que nous tabulons déjà), 1.2.5
    `Prod_enr_bois` = « la consommation de bois pour le chauffage », 1.2.6
    `Prod_enr_rcfu` depuis les taux d'ENR et de récupération de l'annexe 7 de
    l'arrêté du 15 septembre 2006, 1.2.7 `Prod_PAC_géo` (mêmes variables que
    la PAC).
  - L'article 1 points 9.b et 9.c définissent les deux indicateurs : l'ENR
    produite sur site en kWh et par source, puis son rapport à la
    consommation d'énergie finale totale — « la consommation d'énergie à
    considérer contient l'autoconsommation ». Aucun seuil n'est fixé.
- **Bloquant pour coder** : les formules de 1.2.1, 1.2.2, 1.2.4 et 1.2.7 sont
  des **images** dans la page Légifrance, remplacées par « Vous pouvez
  consulter l'intégralité du texte avec ses images ». Elles ne sont que dans
  l'extrait PDF du Journal officiel, que Légifrance refuse en téléchargement
  direct (HTTP 403). Les récupérer avant d'écrire une ligne : les formes
  attendues — `Cch_pac × (SCOP − 1)` pour la chaleur ambiante, par exemple —
  sont plausibles mais **ne sont pas sourcées**, et la règle d'or n° 1
  l'interdit.
- **Nos tables deviennent incomplètes** — règle d'or n° 2. Le millésime ajoute
  13 `enum_type_generateur_ch_id` (172 à 184) et 12
  `enum_type_generateur_ecs_id` (135 à 146) : PAC et CET à partir de 2027,
  chaudières bois granulés à condensation et sans condensation après 2024,
  ballons électriques selon la présence de commande intelligente. S'y
  ajoutent `enum_bouclage_reseau_ecs_id` = 4 (module thermique d'appartement),
  `enum_equipement_intermittence_id` = 8 (régulation prédictive),
  `enum_periode_installation_ecs_thermo_id` = 4, et
  `enum_type_installation_solaire_id` = 5 « ECS solaire seule », les
  modalités 2 et 3 n'étant **plus permises**.
- **Nouvelles grandeurs à calculer** : récupération de chaleur sur eaux grises
  (`presence_rceg`, `fech_saisi`, `ftrans_saisi` → `fecs_rceg`,
  `besoin_ecs_rceg`), commande intelligente des ballons (`scf_saisi` → `scf`),
  part du besoin couverte par une PAC hybride
  (`couverture_pac_hybride_saisi`). Toutes relèvent des « évolutions 2026 de
  la méthode 3CL-DPE » : **chercher l'arrêté avant de coder**, le XSD ne donne
  que les emplacements.
- Entrées EPBD bloquantes à lire et à conserver : `flexibilite_reseau`
  (`caracteristique_generale`), `chauffage_duree_vie_restante`,
  `clim_duree_vie_restante`. `identifiant_reseau_chaleur` est désormais
  restreint à `[0-9]{4}C` pour chauffage/ECS et `[0-9]{4}F` pour un réseau de
  **froid** urbain, nouvellement déclarable en climatisation —
  `ReseauChaleurFactorResolver` n'en tient pas compte.
- Le changement de pef électricité est la même mécanique que `Common\Period`
  (PRE_2026 / POST_2026) : vérifier s'il faut un troisième régime, daté.
- Méthode : aucun fichier 2.7 n'étant encore au corpus, commencer par le
  schéma et le `CHANGELOG.md` amont, puis guetter l'open data. Mesurer en A/B
  isolé comme d'habitude — un millésime ne doit pas dégrader les 2.6.

### TASK-K64 — Audit énergétique réglementaire : explorer, puis câbler le moteur

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- **L'audit réutilise notre moteur tel quel, c'est son intérêt.** Le `README.md`
  amont désigne `modele_commun_DPE_audit_reg.xsd` comme la source de vérité
  dont les schémas DPE **et** audit sont générés. `audit_v2.6.xsd` (V6.0.0 —
  2026-10-16) est copié dans `editeur/public/schemas/`.
- Structure : `<audit><logement_collection>` de blocs `<logement>` au modèle
  3CL, chacun étiqueté dans sa `caracteristique_generale` par
  `enum_scenario_id` (0 état initial, 1 multi-étapes principal, 2 une étape
  principal, 3 à 5 complémentaires, 6 et 7 copro), `enum_etape_id` (0 état
  initial, 1 première, 2 finale, 3 à 5 intermédiaires) et `nom_scenario`.
  Autrement dit : **N états du même logement, chacun un calcul 3CL**.
- Mesure de l'écart au modèle DPE : 644 éléments distincts contre 562, soit
  **153 propres à l'audit** — et ce ne sont presque pas des grandeurs
  physiques. Ce sont les travaux (`etape_travaux`,
  `enum_lot_travaux_audit_id` : murs, planchers bas, toiture, menuiseries,
  chauffage, ECS, froid, ventilation, ENR, autre ; `enum_type_travaux_id`),
  les coûts (`cout_min`, `cout_max`, `cout_cumule`), les aides
  (`aide_financiere_nationale`, `aide_financiere_locale`), les dérogations
  (technique, économique, ventilation), l'auditeur, et la famille des gains
  (`ef_conso_5_usages_m2_gain`, `_gain_cumule`, `_gain_relatif`, idem EP et
  GES).
- **À explorer avant toute ligne de code** :
  1. Dresser l'inventaire des 153 éléments en trois tas — dérivés
     arithmétiques de nos sorties, données saisies par l'auditeur, règles
     réglementaires. Seul le troisième tas est du travail de méthode.
  2. Lire l'arrêté du 4 mai 2022 (audit énergétique réglementaire) et ses
     modificatifs : lots obligatoires par scénario, classes à atteindre,
     conditions de dérogation. Le XSD ne donne que les emplacements, jamais
     les règles — même discipline que pour le 3CL.
  3. Vérifier ce que l'état initial partage avec un DPE : `enum_version_dpe_id`
     dit quelle version de calcul l'audit emploie, et les champs EPBD de
     TASK-K63 y sont bloquants **pour l'état initial seulement**.
  4. Chercher un corpus : l'observatoire publie les audits comme les DPE
     (numéro ADEME en `[2-3][0-9]{2}[0-9A-B][ETN][0-9]{7}[A-Z]`), à confirmer.
     Sans référence, pas de mesure de conformité possible.
- **Câblage pressenti dans le moteur**, à valider par l'exploration :
  `DpeEngine::calculateDocument()` s'applique à un `<logement>` et est déjà
  **idempotent** — exactement ce qu'il faut pour l'enchaîner sur chaque état.
  Le travail porte sur l'enveloppe : un `AuditEngine` qui itère la collection,
  un garde `assertCalculable` étendu au modèle audit, et les gains calculés
  **après** coup à partir des sorties, jamais pendant. Ne pas dupliquer un
  Calculator : si un état a besoin d'une règle particulière, c'est que la
  règle est mal placée.
- Hors périmètre, à ne pas confondre : le « DPE projeté » commercial n'est pas
  un modèle du XSD (`enum_modele_dpe_id` ne connaît que 3CL existant, neuf
  RT2012, neuf RE2020, tertiaire 2006) et ne porte pas de numéro ADEME. Le
  DPE neuf, lui, **embarque** le résultat de l'étude réglementaire (`rset`
  pour la RT2012, `rsee` pour la RE2020) : il n'y a rien à y recalculer, et
  implémenter Th-BCE serait un autre projet.

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
- **`production_ch_solaire` : tranché, c'est la référence.** §9.2 p.62 et
  §9.6 p.65 écrivent la production et la consommation sur le même produit
  `Bch × Σ(part_i × INT_i × Ich_i)` : tout s'élimine sauf
  `Prod / Cch = Fch / (1 − Fch)`. Sur les dix installations du corpus qui
  publient les trois balises, **huit violent ce rapport**, de 0,52 à 3,32 fois
  — et les deux qui le respectent sont exactement celles que nous
  reproduisons. `ReferenceDefects` les impute désormais sur cette preuve,
  interne au fichier.
- `sortie_par_energie` ne porte pas de ligne pour
  `enum_type_energie_id = 12` (« électricité d'origine renouvelable utilisée
  dans le bâtiment ») : 2601E0370414Y en attend une complète.
- `rendement_stockage` = 1 : 27 références le publient, plus de 300 l'omettent
  comme nous. Convention d'éditeur, rien à corriger (cf. TASK-K06).

