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

### TASK-K66 — §17.1.4.2 : « diviser par Nblgt » ou proratiser par la surface ?

- [ ] Owner: __ | Phase: K | Priorité: basse
- §17.1.4.2 p.110 écrit, pour un DPE immeuble à chauffage individuel, que la
  consommation se calcule « sur la base d'un appartement « moyen », à partir
  du besoin de chauffage de l'appartement « moyen » (obtenu en multipliant le
  besoin de chauffage de l'immeuble Bch par le rapport de la surface
  habitable de l'appartement « moyen » à celle de l'immeuble, **ce qui revient
  à diviser le besoin de chauffage Bch de l'immeuble par le nombre de
  logements de l'immeuble Nblgt**) », puis à multiplier par `Nblgt_syst_i`.
- Pour la **consommation**, les deux lectures coïncident : avec
  `Nblgt_syst_i = Sh_i / Shmoy` de §17.1.2, on a
  `(Bch / Nblgt) × Nblgt_syst_i = Bch × Sh_i / Sh_immeuble`. Vérifié sur
  2400E0669882V, dont la référence donne exactement 166,3 kWh/m² sur ses six
  installations.
- Elles divergent en revanche pour le **profil de charge** du générateur, qui
  voit un GV et non un besoin. `resolveGvBuilding` prend aujourd'hui
  `ChauffageInstallationMultiplicity::directShareOrNull()`, soit
  `Sh_i / Sh_immeuble`, et non `1 / Nblgt`.
- **Variante mesurée et écartée** : forcer `1 / Nblgt` coûte **+122 écarts**
  (13 654 → 13 776), sept cas dégradés et aucun amélioré — 2682E0040013I et
  2682E0040066J à +27 chacun, 2612E0854137C à +25. La part surfacique est
  celle que le corpus soutient. Ne pas y revenir sans un élément nouveau.
- Reste ouvert : sur 2400E0669882V le rendement de génération vaut 0,8655
  attendu contre 0,8010, et la direction de l'écart irait dans le sens d'un
  GV plus grand. Chercher ailleurs que dans ce partage — le profil de charge
  de §13.2.2.1 lui-même, ou `Cdimref`.

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

### TASK-K69 — §9.8 collectif base + appoint : les deux résidus après branchement

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- La stratégie est désormais branchée et le garde `Textj <= 0` corrigé.
  `besoin_ch` est **exact** sur les deux cas du corpus, et la répartition
  base/appoint de `2600E0006037K` est juste à la proportion près (97,8 % /
  2,2 %, comme sa référence). Restent deux choses, distinctes.
- **1. Pn absent quand la base n'est pas une chaudière.** `2478E0866009E`
  porte deux générateurs électriques (102 plancher rayonnant, 100 radiateur) :
  `ChaudiereDefautCalculator` ne les dimensionne pas, `pn` reste vide, donc
  `Pe = 0` et §9.8 retombe sur la convention de 50 %. Sa référence répartit
  82 % / 18 %. Une recherche de la puissance émise qui reproduirait ce partage
  donne **Pe ≈ 12,3 kW pour 1 762 m²**, soit 7 W/m² — trop faible pour un
  dimensionnement, donc la convention de 50 % n'est pas simplement mal
  calibrée : c'est la source de Pn qu'il faut trouver pour un générateur
  électrique. §9.8 ne la donne pas ; chercher du côté de §13.2.2.4 (Pdim) et
  de open3cl avant d'écrire quoi que ce soit.
- **2. Les six tabulations vides d'altitude 3 : tranché, elles sont vides dans
  l'annexe.** §18.2.3 et §18.3.3 ne publient aucune donnée pour H1a, H2a et
  H2b au-dessus de 800 m. Ce n'était pas un trou de transcription. Les deux
  tables ont été recoupées une à une avec §18.2.1-18.2.3 (`ilpa = 0`) et
  §18.3.1-18.3.3 (`ilpa = 1`) : 576 valeurs chacune, une seule corrigée. Rien
  à reprendre de ce côté.
- Résidu mesuré de `2600E0006037K` après correction : `conso_ch` à ×1,12, qui
  suit son `rendement_generation` de chaudière gaz (0,8141 contre 0,8342) —
  c'est TASK-K31, pas §9.8.

### TASK-K71 — Froid : `apport_solaire_fr` à un facteur 1,057 constant

- [ ] Owner: __ | Phase: K | Priorité: basse
- **Ce qui est fait** : les deux colonnes `Nref` de l'altitude 400-800 m sont
  reprises de §18.2.2, et `apport_interne_fr` devient exact — à douze chiffres
  sur `2605E1578332M`. Les 1 728 valeurs de `tv_sollicitations_froid` et les
  576 de `tv_textmoy_clim` sont désormais recoupées une à une avec l'annexe,
  sans écart : **l'écart restant n'est pas dans les tables.**
- **Ce qui reste** : `apport_solaire_fr` est en dessous de la référence d'un
  facteur remarquablement stable — 1,0576, 1,0563, 1,0582 et 1,0570 sur
  quatre fichiers indépendants, et 1,0910 sur un cinquième.
- **La piste du mois de septembre est morte, ne pas la reprendre.** L'idée
  était que la référence retienne les mois où l'annexe publie un `DH28` non
  nul en regard d'un `Nref28` tiret (H1c 400-800 m en septembre, par exemple).
  Mais `E_fr_28` y vaut **0** : inclure ce mois n'ajoute rien aux apports
  solaires. Pour H1c à cette altitude, seuls les mois 6, 7 et 8 portent un
  `E_fr_28` non nul, et nous les retenons tous les trois.
- **Chercher donc dans `ssej`**, la surface sud équivalente mensuelle, et non
  dans le choix des mois. `apport_solaire_ch` est pourtant exact sur ces mêmes
  fichiers — mais il ne porte que sur les mois de chauffe. Regarder ce que
  `sseMensuel` et `sseEtsMensuel` valent en juin, juillet et août, quand les
  masques et les facteurs d'orientation d'été diffèrent de ceux d'hiver.
- **Les trois cas où nous publions zéro ne sont pas un défaut.** Deux —
  `2600E0015965G` et `2600E0020799E` — n'ont aucun bloc `<climatisation>` et
  leur référence publie quand même un besoin de froid. Ils sont **seuls de
  leur espèce** : sur les 541 cas, 506 sans climatisation publient un
  `besoin_fr` nul comme nous, et deux ne le font pas. Le troisième,
  `2600E0019751W`, attend 0,03. Ne rien changer pour 2 cas contre 506.

### TASK-K72 — `2605E1578332M` : un besoin de froid qui contredit ses propres apports

- [ ] Owner: __ | Phase: K | Priorité: basse
- Ce fichier publie `apport_interne_fr` = 71,738679571428563, que nous
  reproduisons désormais à **douze chiffres** : sa valeur est celle que donne
  la somme des `Nref28` de §18.2.2 pour H1c à 400-800 m, soit 116 heures.
- Mais son `besoin_fr` de 381,23 est celui qu'on obtient avec les **anciennes**
  valeurs, fausses, de 35 heures ; avec les apports qu'il publie lui-même, le
  besoin vaut 435,71. La référence a donc calculé son besoin avec un jeu de
  sollicitations et publié ses apports avec un autre.
- Son budget de non-régression est passé de 18 à 23 pour cette raison, et
  c'est le seul relâché de la campagne — les dix autres cas touchés ont vu le
  leur se resserrer. Le relâchement est volontaire et documenté ici.
- À instruire avec TASK-K71 : si la saison retenue pour les apports solaires
  explique les 5,39 % restants, ce cas se referme peut-être tout seul. Sinon,
  c'est un candidat à `ReferenceDefects` — la preuve est interne au fichier
  (ses apports publiés ne reproduisent pas son besoin publié) mais elle
  demande de rejouer §10.2, ce qui sort du cadre de l'outil.

### TASK-K74 — Série 2400E03338xx : un `pn` que la référence ne tire pas de son propre GV

- [ ] Owner: __ | Phase: K | Priorité: basse
- Vingt et un cas publient `pn` = 370 000 W quand nous publions 400 000. Ils
  appartiennent tous au **même immeuble**, la série `2400E03338xx`, mode 7
  (immeuble collectif, chauffage collectif), H1a à 400-800 m, 2 064 m².
- **Trois choses sont établies, et elles ferment les pistes évidentes.**
  1. Le plafond n'est pas en cause : retiré, nous produisons 470 000 W
     (cf. TASK-K73 dans `TASKS_DONE.md`, qui mesure aussi son retrait à
     +1 019 écarts).
  2. **Le GV est identique au nôtre à la dixième décimale** —
     `deperdition_enveloppe` = 10 938,545 615 99 W/K des deux côtés, et
     chacun de ses sept postes concorde. L'enveloppe est hors de cause.
  3. Notre valeur est l'application **littérale** de §13.2.2.4 avec le Tbase
     de §18.1 : `Pch = 1,2 × 10 938,55 × (19 − (−11,5)) / (1000 × 0,95³)`
     = 466,95 kW, d'où `Pn = (⌊466,95/5⌋ + 1) × 5` = 470 kW, plafonné à 400.
- **La référence ne tire pas son 370 kW de son propre GV.** Il faudrait
  GV = 8 667 W/K, soit **79,2 %** de celui qu'elle publie. Aucune
  décomposition du GV publié ne donne ce chiffre : sans les ponts thermiques
  on tombe à 280 kW, sans le renouvellement d'air à 385, sans `hperm` à 455.
- **Et notre chaîne est bonne ailleurs** : sur les **733 balises `pn`** des
  328 références qui en publient, **588 sont exactes** et 12 de plus dans la
  tolérance, soit 81,9 % de conformité. Les 126 écarts ne forment pas une
  famille — leur rapport va de 0,27 à 8,54 — et 19 d'entre eux sont ce seul
  immeuble.
- Donc : soit un élément d'entrée propre à cet immeuble nous échappe, soit sa
  référence a dimensionné autrement. **Ne pas toucher à la formule** : 588
  valeurs exactes la soutiennent. Reprendre par les entrées de ce bâtiment,
  ou conclure au défaut de référence si rien ne les distingue.

### TASK-K76 — Rendement de génération d'un radiateur à gaz : 1,11, et la méthode ne le définit pas

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- `tv_generateur_combustion` est désormais complète — 93 lignes sans trou — et
  l'ID **76** manquait alors qu'il est au corpus : `2400E0669425G` et
  `2400E0669495Y` déclarent un radiateur à gaz d'avant 2006, et nous sortions
  sans `pn` ni `rpn` pour ce générateur. Les deux le publient maintenant
  exactement (12 595,7 W et 19 144,8 W ; Rpn = 0,73).
- **Mais leur `rendement_generation` sort à 1,11**, impossible pour une
  combustion, contre 0,720139 attendu — la même valeur sur les deux fichiers,
  donc fonction du seul Rpn et non de Pn.
- **La cause est structurelle.** §13.2.2.2 p.88 donne aux radiateurs à gaz une
  loi de pertes qui leur est propre — `QPx = 1,04 × (100 − RPn)/RPn × Pn × x` —
  et **ne tabule ni Rpint ni QP0**, les deux grandeurs que l'interpolation de
  `RendementAnnuelMoyenCalculator` consomme. La table leur écrit donc
  `rpint = 0`, faute de quoi ce calculateur ne produit rien du tout, et c'est
  ce zéro inventé qui donne 1,11. `qp0 = 0` est en revanche correct : la loi
  ci-dessus s'annule à l'arrêt. §15.1 p.97 isole d'ailleurs les radiateurs gaz
  dans sa propre table G/H.
- **Trois sorties ont été mesurées, toutes moins bonnes que celle retenue**
  (A/B isolé, 541 cas, 0 plantage, 89,1155 % de valeurs conformes au départ) :

  | Variante | Conformes | Effet |
  |---|---:|---|
  | `rpint = 0` (retenue) | 89,1162 % | 6 balises manquantes résolues, `rendement_generation` à 1,11 |
  | `rpint` non publié quand la méthode ne le tabule pas | 89,1143 % | `rendement_generation` manquant |
  | idem, `qp0` explicitement à 0 | 89,1143 % | identique : c'est `rpint` qui commande |
  | radiateurs exclus de `RendementAnnuelMoyenCalculator` | 89,1122 % | `rendement_generation` manquant sur les deux |

  Le budget de `2400E0669425G` passe de 92 à 93 pour cette raison, et c'est le
  seul relâchement : le fichier publie désormais deux balises justes de plus.
- **Ce qu'il faut trouver** : la loi de Rg propre aux radiateurs à gaz.
  0,720139 pour Rpn = 0,73 fait un rapport de 0,986492, et
  `1/0,720139 − 1/0,73 = 0,018758` — à rapprocher de `1,04 × 27/73 = 0,384658`,
  dont il vaut 4,876 %. Ne rien coder tant que la forme n'est pas sourcée :
  §13.2.2.2 ne donne que QPx, et l'assemblage vers Rg est ailleurs.

### TASK-K67 — 2242E2979513I : conso_ch à +20 % une fois le facteur CO2 juste

- [ ] Owner: __ | Phase: K | Priorité: basse
- Le facteur d'émission de ce DPE est désormais exact — ses trois lignes
  `sortie_par_energie` donnent 0,109000 à six décimales, et nous l'appliquons
  (cf. le bloc TASK-K65 de `TASKS_DONE.md`). Sa branche ECS en devient juste au
  millième : 22 459,878 calculés contre 22 459,880 publiés.
- **Ce qui reste est en amont, sur la consommation de chauffage** : 160 558 kWh
  calculés contre 133 765 publiés, soit +20,0 %. Le besoin, les rendements et
  l'échelle sont à reprendre dans cet ordre, pas les agrégats d'aval.
- Le fichier est un DPE du 6 avril 2022 au modèle 2.2 — le plus ancien du
  corpus. Vérifier d'abord si son échelle (immeuble ou logement) est celle que
  nous lui prêtons : c'est le suspect habituel d'un écart de +20 % franc.
- Ses balises `emission_ges_ecs` et `ep_conso_ecs` sont publiées permutées avec
  leurs dépensières, et déjà imputées à ce titre. Ne pas les instruire.

### TASK-K85 — Cdimref : le périmètre du GV reste à trancher pour 25 générateurs

- [ ] Owner: __ | Phase: K | Priorité: haute
- **Acquis, et livré** : quand §17.1.4.2 ramène le GV à l'appartement moyen,
  Cdimref se calcule sur la chaudière **moyenne**, pas sur le générateur
  courant. Les références donnent un Cdimref commun à tous les générateurs
  d'un même bâtiment — 2,6834 et 2,6848 sur `2513E2308028Z`, rapport 0,9995.
  Gain mesuré : −99 écarts.
- **Reste 25 générateurs** dont `pn`, `rpn`, `rpint`, `qp0` et les températures
  de fonctionnement sont **exacts** et le `rendement_generation` faux : 15 en
  méthode 6, 5 en méthode 1, 2 en méthode 2, 2 en méthode 10, 1 en méthode 3.
  Les quinze de méthode 6 sont tous **sous-estimés**, de 2 à 11 %.
- **Un Cdimref implicite reconstruit exactement.** Sur `2400E0669882V`
  (méthode 6, 8 appartements, 6 installations individuelles, GV 1 047,603,
  zone 2 altitude 1 donc Tbase −9,5), la résolution à rebours du rendement
  publié donne **1,6077** pour sa chaudière à condensation. Or
  48 000 / (1 047,603 × 28,5) = **1,608** : la référence utilise le **GV
  complet et la puissance totale**, sans rien ramener. Exact à 0,02 %.
- **Mais la règle ne peut pas être généralisée.** Ne plus ramener le GV pour
  les méthodes 6 et 8 — les immeubles, par opposition aux appartements
  générés 10 et 12 — a été mesuré en A/B isolé, 541 cas, 0 crash :
  **+2 075 écarts**, 3 cas améliorés contre 82 dégradés, dont douze passant
  de 10 à 37-39 écarts. Le cas témoin, lui, gagne 29 écarts (53 → 24).
- Ce qui distingue `2400E0669882V` des autres méthode 6 reste à trouver. Deux
  observations à creuser : seules **2 de ses 6 installations** portent un
  générateur à combustion (les autres sont électriques, type 99), et ses
  installations ne couvrent donc pas tous les logements. Les pistes
  « diviseur = nombre de systèmes » et « part des logements équipés » ont été
  calculées à la main et ne reproduisent pas 1,6077.
- Méthode qui marche, à reprendre : résoudre le Cdimref à rebours depuis le
  rendement publié, puis le comparer aux combinaisons (GV complet, GV/nblgt,
  GV/nb systèmes) × (Pn générateur, Pn totale, Pn moyenne).
- **Ce qui ne marchera pas** : chercher un facteur commun dans les ratios. Ils
  sont dispersés de 0,914 à 1,005, sans aucune valeur ronde, et se concentrent
  sur quelques fichiers — `2400E0575636Z` en porte six à lui seul,
  `2600E0035103I` quatre. Les gains de cette campagne sont tous venus de
  **rapports exacts** (0,9995 pour le Cdimref commun, 0,75 pour la surface de
  module, 0,9072 répété pour la bi-jonction) ; leur absence ici signale des
  résidus de second ordre, pas une formule manquante.

### TASK-K86 — Appartements générés de méthode 33/34 : une clé de répartition que rien ne publie

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- Quatre cas — `2659E2542205P` (94 écarts), `2659E2596084W` (58),
  `2659E2277989L` (60), `2659E2596108U` (57) — ont leur `conso_ch`
  **d'installation juste** et leur `conso_ch` de `ef_conso` faux : seul le
  passage à l'échelle du logement diverge. Sur `2659E2542205P`, l'installation
  publie 159 258,72 contre 159 362,16 (0,065 %, conforme) et `ef_conso`
  7 239,03 contre 15 420,40.
- **Le facteur implicite de la référence n'est ni la part surfacique ni
  1/Nblgt**, et il n'est pas constant :

  | Cas | facteur réf. | sh_log / sh_imm | 1/Nblgt | rapport |
  |---|---:|---:|---:|---:|
  | 2659E2542205P | 0,096763 | 0,045455 | 0,038462 | 2,13 |
  | 2659E2596084W | 0,058069 | 0,037850 | 0,035714 | 1,53 |
  | 2659E2277989L | 0,080142 | 0,067172 | 0,071429 | 1,19 |
  | 2659E2596108U | 0,028544 | 0,034065 | 0,035714 | 0,84 |

- Les quatre déclarent `cle_repartition_ch = 1` et `rdim = 1` ; leur
  installation est collective (type 2 ou 3). La surface implicite qu'on en
  déduit — 178,8 m² pour un logement de 84 m², 37,7 pour un de 45 — ne
  correspond à aucune donnée du fichier.
- **Rouvrir si** un export de ce type publie enfin une clé exploitable, ou si
  §17.2 précise la répartition d'une installation collective vers un
  appartement généré en chauffage mixte.

### TASK-K87 — cfg 9 : la branche d'appoint bois sur-estimée de 24 %

- [ ] Owner: __ | Phase: K | Priorité: basse
- `2601E0060401J` (47 écarts) porte une installation `cfg = 9` — chaudière en
  relève de PAC **avec insert ou poêle en appoint** — à trois générateurs
  répartis sur deux branches par `enum_lien_generateur_emetteur_id`.
- **Les deux branches ne divergent pas de la même façon**, ce qui localise le
  défaut : les deux générateurs de la branche 1 (types 7 et 106) sont à
  ×1,0329 de la référence, le générateur de la branche 2 (type 43, insert) à
  **×1,2412**. Un écart propre à une branche est un écart de **part**, pas de
  rendement — ceux-ci sont identiques des deux côtés (0,97 et 0,75).
- Part de la branche 2 : 0,4934 chez nous contre 0,4477 dans la référence.
  Ni 0,25 ni 0,1, donc ce n'est pas un facteur §9.x lu tel quel ; et
  reconstruire l'intermittence à rebours donne 1,067 pour notre branche 2,
  une valeur impossible — le produit `part × INT` est donc mal réparti.
- Commencer par vérifier ce que `ChaudiereReleve` attribue à chaque branche,
  et avec quelle intermittence, plutôt que les rendements.

### TASK-K89 — Les références tarifent la ventilation dans une autre assiette que le reste

- [ ] Owner: __ | Phase: K | Priorité: haute
- **35 cas ont leur `conso_ch` juste et leur `cout_ch` faux**, tous
  sous-estimés de 1 à 9 %. Le défaut est donc dans la tarification, pas en
  amont — et il est isolable au prix unitaire près.
- Sur `2600E0281136D` (méthode 10, 24 appartements, installations toutes
  **individuelles**), **toutes les consommations sont justes** et **tous les
  coûts faux** :

  | | Référence | Nous |
  |---|---:|---:|
  | `cout_ch` / `conso_ch` | 0,25335 | 0,24820 |
  | `cout_ecs` / `conso_ecs` | 0,25335 | 0,24820 |
  | `cout_auxiliaire_ventilation` / `conso` | **0,20950** | 0,24820 |

- **Nous appliquons un prix unique, la référence en applique deux** : le même
  au chauffage et à l'ECS, un plus bas de 15 % à la ventilation. Un prix plus
  bas signifie une assiette plus large, donc un abonnement distinct — l'inverse
  exact de notre convention, qui rattache la ventilation à l'abonnement
  individuel.
- **Mais ce n'est pas l'abonnement d'immeuble entier** : le rapport des
  surfaces vaut ici 45, ce qui donnerait un prix bien plus bas que 0,2095. Il
  manque le périmètre exact de cette seconde assiette.
- `surface_ventile > surface_habitable_logement` ne sépare pas les deux
  groupes : 35 % des 100 cas à `cout_auxiliaire_ventilation` faux l'ont, contre
  26 % des 74 cas justes.
- **Les deux assiettes sont résolues**, en inversant le barème du 1er juillet
  2024 (électricité : 0,18954 + 158/C entre 1 000 et 2 500 kWh ; 0,19726 +
  119/C entre 5 000 et 15 000) :
  - 0,25335 €/kWh ⇒ assiette de **2 475 kWh**, soit exactement le total du
    logement **moins** sa ventilation (2 691 − 216) ;
  - 0,20950 €/kWh ⇒ assiette de **9 722 kWh**, soit la ventilation multipliée
    par le rapport des surfaces (215,845 × 45,06 = 9 726).
  La référence tarife donc bien la ventilation sur l'**abonnement
  d'immeuble**, mécanisme que `PrixEnergie` sait déjà appliquer.
- **Mais la règle n'est pas généralisable en l'état.** Trois variantes mesurées
  en A/B isolé, 541 cas, 0 crash :
  - ventilation toujours collective : **+1 018 écarts**, 55 cas améliorés
    contre 299 dégradés ;
  - collective si `surface_ventile > surface_habitable_logement` : **−25**,
    mais 31 améliorés contre **30 dégradés** ;
  - la même, restreinte aux DPE non-immeuble : identique au précédent, la
    série `2400E03338xx` étant en méthode 11.
  Le gain net est positif mais l'équilibre est trop fragile pour livrer une
  règle inférée : il manque le critère qui sépare vraiment les deux groupes.
- Chercher ce critère dans le bloc `ventilation` lui-même —
  `enum_type_ventilation_id` collectif, présence d'un caisson commun — plutôt
  que dans les surfaces.

### TASK-K90 — `ef_conso` retient une seule clé de répartition pour toutes les installations

- [ ] Owner: __ | Phase: K | Priorité: haute
- `EfConsoCalculator::aggregateChauffage` accumule les consommations de toutes
  les installations, puis multiplie le total par **la dernière**
  `cle_repartition_ch` lue — la boucle écrase la précédente à chaque tour.
- Sur `2601E0158771V` (méthode 2, deux installations de clés **0,7866** et
  **0,2134**, qui somment à 1), `ef_conso` publie 49,087 là où
  `sortie_par_energie` — qui applique la clé installation par installation —
  publie 123,152. Soit le total brut de 230,02 ramené à la seule clé 0,2134.
  Même défaut sur `2601E0304014C`, où l'écart atteint un facteur 14.
- Les deux cas viennent du jeu `ademe-emetteurs-2026-10` : la configuration
  n'existait pas dans le corpus avant lui.
- **La correction évidente est fausse** : pondérer chaque installation par sa
  propre clé a été mesuré en A/B isolé, 645 cas, 0 crash — **+411 écarts, 75
  cas dégradés, aucun amélioré**. La raison est que les installations sans clé
  déclarée se mêlent à celles qui en portent une : aujourd'hui la clé trouvée
  s'applique à tout le monde, et c'est ce que la plupart des références
  attendent.
- Chercher d'abord ce que la référence fait quand **une partie seulement** des
  installations déclare une clé, puis traiter le cas où elles somment à 1 —
  qui est le seul où le défaut se voit.
- Les deux fichiers sont les seules entrées de
  `CoherenceInterneTest::DETTE_CONNUE`.

### TASK-K84 — Réseau de chaleur multi-bâtiment : 0,75 ou 0,90, le corpus ne tranche plus

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- §14.3 p.95 distingue l'installation « isolée » (Rs × Rg = 0,9) de celle qui
  ne l'est pas (0,75). Le XSD ne porte ce qualificatif que sur les réseaux
  urbains 72/73 ; pour les « chaudière(s) … multi bâtiment modélisée(s) comme
  un réseau de chaleur » (74-77, 134), `ReseauChaleurCalculator` le reconstruit
  depuis les positions déclarées : générateur **et** ballon hors volume
  chauffé ⇒ 0,75.
- **La règle était unanime sur trente occurrences ; elle est désormais à deux
  contre deux**, et le seul discriminant est la version du moteur éditeur :
  0,75 sur `2501E1812848D` (3cl-2024.6.1.0) et `2513E0192986F`
  (3cl_tribu_2024.6.1.0), 0,90 sur `2695E2427686K` et `2695E2608627R`
  (BBS_Slama_2025.11.1.0). Les entrées sont identiques — type 76, stockage
  hors volume chauffé, usage 3, saisie 1.
- **Mesuré** : abandonner la règle (0,90 pour tout multi-bâtiment) donne
  **exactement 0 écart de différence** en A/B isolé sur 541 cas, 0 crash —
  deux cas gagnent 26 et 19 écarts, deux autres en perdent 25 et 20. Le
  compteur ne peut donc pas arbitrer.
- Le facteur est 1,2 exactement (0,90 / 0,75) et se propage à tout l'ECS :
  sur `2695E2427686K`, `conso_ecs` vaut 3 548,33 contre 2 956,95 publiés,
  pour un `besoin_ecs` identique à la onzième décimale.
- **Trancher sur le texte, pas sur le corpus** : chercher si l'arrêté ou une
  note CSTB qualifie l'isolation d'une sous-station multi-bâtiment, ou si la
  version 2025.11 des moteurs a acté un changement. Rouvrir aussi si un
  cinquième fichier en configuration « hors volume chauffé » arrive.

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
- **Poids actuel sur le corpus public** : 79 écarts sur `k` répartis sur
  18 cas, plus 19 sur `deperdition_pont_thermique`. L'invariant
  `deperdition_pont_thermique = Σ l × k` est vérifié sur les 541 cas : le
  défaut est bien dans `k`, pas dans l'agrégation.
- **47 des 79 écarts ont la même forme** : la référence publie `k = 0` et nous
  une valeur tabulée. Ils se concentrent sur **9 fichiers**. L'un d'eux,
  `2401E0166251L`, annule ses 13 liaisons et publie
  `deperdition_pont_thermique = 0` — il n'y a rien à en tirer. Les 8 autres
  n'en annulent qu'une partie.
- **L'adjacence des parois n'est pas le critère**, bien qu'elle sépare
  proprement les exemples pris un à un (`tv_id = 1` annulé avec un plancher en
  adjacence 7, conservé en adjacence 1). Trois variantes mesurées en A/B
  isolé, 541 cas, 0 crash, toutes désastreuses :
  - toute adjacence non extérieure, liaisons 1 et 3 : **+15 581 écarts**,
    3 cas améliorés contre 378 dégradés ;
  - ajout des seules adjacences 7 et 12 : **+3 118**, 2 contre 86 ;
  - ajout de la seule adjacence 7 : **+1 854**, 2 contre 61.
  Les planchers sur terre-plein, vide sanitaire et sous comble ont bien leurs
  ponts thermiques. Ne pas reprendre ces trois pistes.
- Ni `tv_pont_thermique_id` ni `enum_type_liaison_id` ne séparent les deux
  groupes : l'identifiant 1 apparaît 12 fois annulé et 3 fois conservé, le 29
  sept fois et dix-neuf fois, le 85 dix fois et trois fois.
- **Le corpus élargi à 645 cas fait apparaître le groupe symétrique** : sur
  159 écarts de `k`, 93 ont la référence à zéro et **33 nous ont à zéro** avec
  une référence non nulle. Ces 33 sont à 96 % sur les liaisons 1 et 3 — celles
  que `isNegligibleByAdjacency` traite. Le garde annule donc parfois à tort.
- **Mais le resserrer coûte davantage qu'il ne rapporte.** Deux variantes
  mesurées en A/B isolé, 645 cas, 0 crash :
  - retrait complet du garde : **+104 écarts**, 4 cas améliorés contre 8
    dégradés, dont `2618E2138973C` qui passe de 24 à 74 ;
  - garde restreint aux seuls planchers bordant la circulation, le mur ne
    déclenchant plus l'annulation : **+61**, 3 améliorés contre 6 dégradés.
  Le garde actuel reste donc le meilleur des trois. Les cas où un **mur** en
  circulation justifie l'annulation sont bien réels.
- Le défaut pèse maintenant **159 écarts de `k` sur 44 cas**, plus 44 sur
  `deperdition_pont_thermique`, et il explique **21 des 31 GV faux** du jeu
  `ademe-emetteurs-2026-10`.
- **Vingt de ces références se contredisent, et sont désormais imputées.** Un
  même `tv_pont_thermique_id` y vaut à la fois 0 et la valeur de table ; or un
  identifiant tabulé ne désigne qu'une valeur. `ReferenceDefects` les relève
  sans recalcul — +35 écarts imputés. Les treize autres fichiers qui annulent
  n'ont qu'une occurrence de l'identifiant concerné : rien à démontrer, ils
  restent à notre charge.
- **open3cl n'est pas un oracle ici, et c'est la découverte qui ferme la
  piste.** Son `3.4_pont_thermique.js` ouvre par `if (pt_di.k === 0) return 0;`
  — il **recopie** le `k` de la `donnee_intermediaire` du fichier d'entrée au
  lieu de le calculer, et son propre commentaire parle de `bug_for_bug_compat`.
  Il reproduit donc les zéros des éditeurs sans les expliquer.
- Nos entrées, elles, sont purgées : `OutputPurger` ne préserve les valeurs de
  `donnee_intermediaire` que sur `generateur_chauffage`, `generateur_ecs` et
  `climatisation`, et **aucun des 12 869 ponts thermiques du corpus ne porte de
  `k` en entrée**. C'est correct — les 93 cas sont tous déclarés
  `enum_methode_saisie_pont_thermique_id = 1`, donc forfaitaires, donc calculés
  depuis la table, et `k_saisi` n'existe que pour les méthodes 2 et 3.
- **Conséquence** : ces zéros ne sont pas reproductibles par le calcul, et
  chercher la règle qui les produirait est sans objet. Ne rouvrir que si un
  export publie un `k` nul avec une méthode de saisie 2 ou 3, ou si le texte
  introduit une annulation que §3.4 ne mentionne pas.

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
- **Déjà fait, et à ne pas refaire** : l'éditeur gère les deux millésimes —
  `DPEv2.7.xsd` est au registre, 2.7 est proposé en tête à la création, 2.6
  reste disponible, et un test paramétré couvre les deux formats de bout en
  bout. Le moteur, lui, **refuse explicitement** un générateur de type 172-184
  ou 135-146 au lieu de le calculer avec un rendement implicite de 1 : sans ce
  garde, le fichier produit était silencieusement faux et rien ne le signalait.
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
- **Inventaire fait.** Contre `DPEv2.7.xsd` (589 éléments), `audit_v2.6.xsd`
  en compte 644, dont **126 propres à l'audit** — le chiffre de 153 était
  mesuré contre le modèle 2.6. Les quatre tas :

  | Tas | Nombre | Nature |
  |---|---:|---|
  | Saisie de l'auditeur et présentation | **68** | identité et qualification, dates, descriptifs, observations, pathologies, recommandations, coûts `cout_min`/`cout_max`, aides, caractéristiques de travaux par technique (`pac_air_eau`, `isolation_mur_ite`, `menuiseries_double_vitrage`…) |
  | Dérivés arithmétiques de nos sorties | **32** | les `*_m2`, les quatre familles de gains (`_gain`, `_gain_cumule`, `_gain_relatif`, `_gain_cumule_relatif`) en EF, EP et GES, `facture_gain`, les `cout_cumule*`, `ubat_base`, `qualite_isol` |
  | Règles réglementaires | **12** | `enum_scenario_id`, `enum_etape_id`, `enum_lot_travaux_audit_id`, `enum_type_travaux_id`, les trois `enum_derogation_*_id`, `enum_etat_composant_id`, `enum_etat_ventilation_id`, `enum_modele_audit_id`, `enum_version_audit_id`, `enum_version_dpe_id` |
  | Conteneurs de structure | 14 | `audit`, `logement_collection`, `etape_travaux`, les `*_collection` |

  **Seuls les 12 du troisième tas sont du travail de méthode**, et aucun n'est
  une grandeur physique : ce sont des règles d'éligibilité et de contenu de
  scénario, à lire dans l'arrêté du 4 mai 2022.
- **Le modèle de calcul est bien partagé, vérifié dans l'autre sens.** 71
  éléments du DPE 2.7 sont absents de l'audit, et **aucun n'est une entrée ni
  un intermédiaire de la méthode 3CL** : ce sont l'identité du
  diagnostiqueur (12), les recommandations et packs de travaux du DPE —
  l'audit a ses propres `etape_travaux` —, les consommations relevées sur
  factures, le tertiaire et le neuf (`rsee`, `rset`, `shon`, `surface_utile`,
  `enum_modele_dpe_id`), et l'enveloppe documentaire du DPE lui-même.
- **Quatre points de câblage précis, et ils tiennent à une date.**
  `date_etablissement_dpe` n'existe pas dans le modèle audit, qui porte
  `date_etablissement_audit`. Or quatre composants du moteur la lisent pour
  choisir un barème daté : `PrixEnergie` (tarifs), `CoutCalculator`,
  `SeuilsClasses` (seuils d'étiquette) et `ReseauChaleurFactorResolver`
  (millésime d'arrêté). S'y ajoute `DpeEngine`, qui lit `enum_version_id` —
  l'audit a `enum_version_dpe_id`, qui dit « quelle version du calcul DPE est
  utilisée ». Ces cinq lectures sont le vrai périmètre du branchement, pas
  les Calculators.
- **À explorer ensuite** :
  1. Lire l'arrêté du 4 mai 2022 (audit énergétique réglementaire) et ses
     modificatifs : lots obligatoires par scénario, classes à atteindre,
     conditions de dérogation. Le XSD ne donne que les emplacements, jamais
     les règles — même discipline que pour le 3CL.
  2. Vérifier ce que l'état initial partage avec un DPE : `enum_version_dpe_id`
     dit quelle version de calcul l'audit emploie, et les champs EPBD de
     TASK-K63 y sont bloquants **pour l'état initial seulement**.
  3. Chercher un corpus : l'observatoire publie les audits comme les DPE
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

