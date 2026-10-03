# TASKS — travaux restant à reprendre

Ce fichier contient uniquement les tâches non terminées ou différées. Une tâche
réalisée immédiatement dans la conversation n'y est pas ajoutée. À son achèvement,
une tâche existante est supprimée au lieu d'être conservée avec `[x]`.

Statuts : `[ ]` à faire ; `[~ABC]` en cours par l'agent ABC.

## Phase A — Documentation de la spécification

### TASK-A01 — Digitalisation MD verbatim → markdown structuré

- [ ] Owner: __  | Phase: A  | Estimation: variable  | Itératif
- Pour chaque `resources/specsplitted/**/*.md` encore au statut `verbatim` :
  reformater formules et tables, vérifier les indices perdus par `pdftotext`,
  passer à `digitalized`, puis faire relire par un second agent (`reviewed`).

## Phase H — Corrections issues des diagnostics ADEME

### TASK-H03 — Apports internes : qualifier les références sérialisées en Wh

- [ ] Owner: __  | Phase: H  | Estimation: 2h
- Certains exports anciens publient `apport_interne_ch`, `apport_solaire_ch` et
  leurs équivalents froid avec un facteur exactement ×1 000.
- Vérifier la cohérence interne et le moteur éditeur avant toute modification :
  le moteur reste en kWh ; une référence en Wh doit être classée comme défaut de
  référence, jamais reproduite dans les calculs.
- Précédent tranché : `2676E2269868T` publiait `besoin_ecs` deux fois, en kWh
  et en Wh, sur un logement à une seule installation ECS. Référence démontrée
  fausse, retirée du corpus (`CorpusLocator::REFERENCES_INVALIDES`) plutôt que
  reproduite. `IntermediateEnergyUnit` reproduit encore le ×1 000 pour les
  versions `0.1.0` et `9.x` : c'est ce sens-là qui reste à trancher, en
  vérifiant d'abord si ces fichiers se contredisent de la même façon.

### TASK-H04 — Besoin et consommation ECS des fichiers collectifs

- [ ] Owner: __  | Phase: H  | Estimation: 4h  | Dépend : TASK-H10
- Sur plusieurs immeubles, les valeurs divergent d'un facteur proche du nombre
  d'appartements. Distinguer calcul immeuble, appartement échantillonné et
  appartement généré depuis l'immeuble selon le §17.
- Examiner séparément les pertes de distribution ECS parfois sérialisées en Wh.
- Cibles : `src/Ecs/BesoinEcsCalculator.php`, `src/Collectif/`.

### TASK-H08 — Upb des planchers bas en dalle béton

- [ ] Owner: __  | Phase: H  | Estimation: 2h
- Cas historiques : `2242E2979513I` attend 2,0 contre 0,241 ;
  `2392E1620721B` attend 2,0 contre 0,8.
- Vérifier le sens de `enum_type_isolation_id` et la lecture de
  `resistance_isolation` dans `UpbCalculator` à partir de la spec, sans calage
  sur ces seules références.

### TASK-H10 — DPE immeuble collectif, méthode 26

- [ ] Owner: __  | Phase: H  | Estimation: 4h
- Reprendre les règles §17 pour `enum_methode_application_dpe_log_id=26` :
  échelle immeuble, chauffage/ECS, ventilation et valeurs nulles.
- Ne pas appliquer mécaniquement un facteur `nombre_appartement` : séparer les
  différents périmètres et utiliser les données publiées à la bonne échelle.
- Cibles : `src/Collectif/` et calculators concernés.

## Phase K — Conformité au corpus ADEME

### TASK-K32 — 2459E4183923N : consommation de chauffage et pont thermique

- [ ] Owner: __  | Phase: K  | Estimation: 3h  | Priorité: basse
- Reste de l'instruction du cas, après la correction de `besoin_ecs`. Le cas est
  passé de 98 à 76 écarts imputables au moteur.
- `conso_ch` : référence 118 781, moteur 110 056, alors que `besoin_ch` ne
  diverge que de +0,8 % et que les quatre rendements publiés (émission 0,95,
  distribution 0,87, régulation 0,95, génération 0,84) sont reproduits. Le
  rapport `besoin_ch / conso_ch` de la référence vaut 0,6809, contre 0,6595
  pour le produit de ses propres rendements : la référence ne reproduit pas sa
  propre consommation. Chercher le facteur manquant avant de toucher au moteur.
- `k` du pont thermique n° 2 : la référence publie 0,460, soit la valeur
  tabulée `tv_pont_thermique_id = 30` (0,92) déjà multipliée par
  `pourcentage_valeur_pont_thermique = 0,5`. Sur les 54 fichiers du corpus
  portant un pourcentage ≠ 1, **50 publient au contraire le `k` brut et
  appliquent le pourcentage à la déperdition** — notre convention. Et le total
  publié par ce fichier, 243,326, ne correspond ni à Σ(l·k) = 254,601 ni à
  Σ(l·k·pct) = 206,512 : il se contredit lui-même. Ne pas aligner le moteur
  dessus.
- `i0` : 4 écarts seulement sur 377 cas, de sens opposés (0,95 attendu pour 1,03
  calculé sur un fichier, l'inverse sur un autre). Queue contradictoire, pas de
  règle à en tirer.
- `upb = 2,0` contre 0,5 : même motif que TASK-H08.
- Apports et pertes récupérées sérialisés ×1 000 : cf. TASK-H03.

### TASK-K33 — Coût d'une installation de chauffage bi-énergie

- [ ] Owner: __  | Phase: K  | Estimation: 4h  | Priorité: moyenne
- `CoutCalculator::primaryEnergieId` retient l'énergie du **premier** générateur
  pour tarifer toute l'installation, comme le faisaient `EpConsoCalculator` et
  `EmissionGesCalculator` avant d'être corrigés. Sur les configurations §9.3
  à §9.5 — poêle bois plus convecteur électrique — la part électrique est donc
  tarifée au prix du bois.
- La correction n'est pas le simple portage de la pondération : le barème a des
  **tranches d'abonnement** indexées sur la consommation annuelle par énergie
  (cf. K10). Il faut tarifer chaque énergie séparément, à partir des
  consommations par générateur, puis sommer — pas moyenner un prix.
- Mesure de l'écart restant sur 2662E2454031K : `cout_ch` 1 439 contre 1 723
  attendu, `cout_ch_depensier` 1 739 contre 2 067. Les consommations par énergie
  (`sortie_par_energie_collection`) sont désormais exactes, l'entrée est donc
  disponible.
- Le garde posé sur la pondération EP/GES vaut ici aussi : n'utiliser les
  consommations par générateur que lorsqu'elles répartissent celle de
  l'installation.

### TASK-K34 — Étendre la répartition par branche à §9.3 et §9.5

- [ ] Owner: __  | Phase: K  | Estimation: 3h  | Priorité: moyenne
- `InsertElecSdb` (§9.4) répartit désormais la consommation entre l'émetteur de
  base et celui de salle de bains via `enum_lien_generateur_emetteur_id`, grâce
  à `StrategieComputeTrait::computeAndWriteParLien`. `InsertPoeleAppoint` (§9.3,
  liens 1 et 2) et `AppointInsertElecSdb` (§9.5, liens 1, 2 et 3) lisent encore
  les parts sur le rang de l'installation et écrivent la même consommation sur
  chaque générateur.
- Le corpus compte une douzaine de fichiers en cfg 3 et 5, dont
  2600E0080950R, 2600E0105050P, 2600E0083964P et 2400E0086646R.
- Reprendre le garde de §9.4 : la répartition ne vaut que si l'installation est
  la seule du logement, sans quoi le besoin est double-compté.
- Gain attendu en prime : la pondération EP/GES par générateur ne s'applique
  qu'aux installations dont les consommations par générateur se répartissent, ce
  que cette tâche rendrait vrai pour ces fichiers.

### TASK-K39 — Auxiliaires de génération : répartir le besoin entre générateurs

- [ ] Owner: __  | Phase: K  | Estimation: 4h  | Priorité: moyenne
- §15.1.1 p.97 : « Bch_g : besoin annuel d'énergie **assuré par le générateur** »,
  et « dans les cas où le générateur n'assure pas 100 % du besoin, seule la part
  du besoin qu'il couvre est prise en compte ».
- `AuxGenerationCalculator` passe le `besoin_ch` **entier** de l'installation à
  **chaque** générateur, puis somme : une installation à deux générateurs compte
  donc le besoin deux fois.
- Démontré sur `2457E3640397P` (hors corpus, tiré de l'open data) : chaudière
  fioul 78 et chaudière charbon 120, toutes deux Pn = 60 kW, besoin_ch =
  51 685,61. La référence publie 99,9255 kWh, soit exactement
  116 W x 51 685,61 / 60 000 — la valeur d'**un seul** générateur, ou de deux se
  partageant le besoin, ce qui revient au même à Pn égal. Nous publions le
  double.
- Le partage n'est pas toujours 50/50 : sur `2457E3724590U` (gaz 97 à 32 kW +
  charbon 120 à 40 kW) un partage égal donne 57,428 contre 57,2428 publiés.
  Chercher la clé réelle — probablement la part déjà calculée par les stratégies
  de §9, que les générateurs publient en `conso_ch`.
- Faible rendement sur le corpus actuel : 8 cas seulement, dont 2 au rapport
  exact de 2. Mesurer avant de livrer.

### TASK-K40 — Auxiliaires de distribution de refroidissement : balise jamais produite

- [ ] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: basse
- §15 p.97 annonce `Caux_fr = Caux_dist_fr`, mais **aucun paragraphe ne donne la
  formule** : §15.2.1 ne tabule que le mode chaud, et §15.2.2/15.2.3 ne traitent
  que le chauffage et l'ECS. open3cl ne la calcule pas davantage.
- Nous ne produisons donc ni `conso_auxiliaire_distribution_fr` ni ses trois
  dérivées (coût, GES, énergie primaire), ce qui vaut 88 balises manquantes sur
  22 cas.
- **Aucune référence du corpus ne publie de valeur non nulle** : sur 390
  fichiers, 22 écrivent un zéro — tous sans installation de froid — et 367
  omettent la balise, y compris les 20 qui ont du froid. Il n'y a donc rien à
  valider contre le corpus.
- La sérialisation suit le format du fichier : les versions 8.x et 9.x
  l'écrivent (11 sur 11), la version 2 jamais (1 sur 364), la 0.1.0 11 fois sur
  14. Publier un zéro sous ce seul garde de format gagnerait environ 72 écarts
  nets, au prix d'un second garde de format inféré, pour une balise que la
  méthode ne définit pas. Non retenu en l'état.
- Rouvrir si une référence publiant une valeur non nulle apparaît au corpus, ou
  avec un texte réglementaire donnant la formule.

### TASK-K35 — 2113E0368523M : conso_ch et référence lacunaire

- [ ] Owner: __  | Phase: K  | Estimation: 3h  | Priorité: basse
- `conso_ch` : référence 7 462,41, moteur 10 162,50 (+36 %). Notre facteur
  d'émission est pourtant le bon — 3 292,65 / 10 162,50 = 0,324, exactement
  celui du `sortie_par_energie` de la référence pour le fioul. C'est la
  consommation elle-même qu'il faut reprendre, pas l'aval.
- La référence se contredit sur les émissions : son agrégat
  `sortie/emission_ges/emission_ges_ch` vaut 589,53 quand son propre
  `sortie_par_energie[enum_type_energie_id=3]/emission_ges_ch` vaut 2 417,82.
  Idem côté ECS, 309,54 contre 1 080,99. Candidat à une règle `ReferenceDefects`
  générique : *un agrégat d'émissions incompatible avec la somme de sa
  ventilation par énergie*.
- Son `version_moteur_calcul` est « inconnu » et elle omet beaucoup
  d'intermédiaires que le schéma prévoit — `pn`, `qp0`, `rpn`, `temp_fonc_*`,
  `b` des planchers, `surface_sud_equivalente`, `deperdition_plancher_*` — d'où
  39 balises comptées comme supplémentaires chez nous. Vérifier d'abord si une
  partie relève d'une règle d'omission déjà connue avant de conclure.
- `umur0 = 2,9` attendu contre 2,5 sur trois murs, et `k = 0,365` contre 0,73
  sur trois ponts : non instruits.

### TASK-K37 — Auxiliaires de distribution d'ECS d'un réseau à traceur chauffant

- [ ] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: moyenne
- §15.2 p.99 : « aux consommations d'auxiliaires du générateur, il faut ajouter
  celles éventuelles du bouclage **ou du traçage** de l'ECS ». Le XSD distingue
  1 « réseau d'ecs non bouclé », 2 « réseau d'ecs bouclé » et 3 « réseau d'ecs
  avec présence d'un traceur chauffant ».
- `AuxDistributionCalculator::…` ne calcule rien hors de la valeur 2. Sur
  2659E2407381B — installation collective, `enum_bouclage_reseau_ecs_id = 3`,
  réseau isolé — la référence publie `conso_auxiliaire_distribution_ecs` =
  151,19 kWh et nous 0. En découlent `conso_totale_auxiliaire` (721,69 contre
  570,49), son coût, ses émissions et son énergie primaire, soit une dizaine
  d'écarts.
- **Élargir le garde à la valeur 3 ne suffit pas** : mesuré, l'effet est
  strictement nul, le calcul rendant toujours 0 une fois le garde passé.
  Chercher le second bloqueur avant de toucher au garde — vérifier d'abord
  `ecs.besoin_ecs_mensuel`, que la méthode consomme et qui pourrait être vide
  pour cette configuration.
- Un traceur chauffant n'est pas un circulateur : vérifier aussi que la formule
  de §15.2.3, écrite pour une boucle, s'applique telle quelle — la spec les cite
  ensemble sans donner deux formules.

### TASK-K38 — L'isolation de la toiture conditionne-t-elle l'indicateur de confort d'été ?

- [ ] Owner: __  | Phase: K  | Estimation: 3h  | Priorité: basse
- `ConfortEteCalculator` déclare le confort « insuffisant » dès que
  `protection_solaire_exterieure = 0` **ou** `isolation_toiture = 0`, règle
  reprise d'open3cl (`2021_04_13_confort_ete.js`). Aucun texte de la méthode ni
  du XSD ne l'établit : le XSD documente les cinq critères un par un, jamais
  leur agrégation.
- **Les références se contredisent.** 2583E2717522L et 2659E2047646C publient
  `isolation_toiture = 0` avec `enum_indicateur_confort_ete_id = 3` (« bon ») ;
  2662E2147774H publie 0 avec l'indicateur à 1 (« insuffisant ») pour une
  protection solaire et un aspect traversant identiques.
- Mesuré : retirer `isolation_toiture` du veto donne 8 054 -> 8 040 écarts
  moteur (-14), mais 23 cas améliorés contre **9 dégradés**. Le corpus ne
  départage pas, et aucune source ne tranche : non retenu.
- Rouvrir avec le texte de l'arrêté (annexe « confort d'été »), qui seul peut
  donner la table d'agrégation des cinq critères.

### TASK-K31 — Pn des chaudières : trois régimes d'arrondi inconciliables

- [ ] Owner: __  | Phase: K  | Estimation: 4h  | Priorité: basse
- Sortie de TASK-K04. Notre `Pdim` est juste : sur tous les cas litigieux, le GV
  que nous calculons égale au bit près le `deperdition_enveloppe` publié par la
  référence, et Tbase est conforme à la table §18.1. Le désaccord porte
  uniquement sur le passage `Pdim → Pn`, où la spec §13.2.2.4 p.92 impose
  `(partie entière(Pdim/5) + 1) × 5` au-delà de 40 kW.
- Sur 81 générateurs mesurés, la référence suit trois régimes incompatibles, au
  même `version_moteur_calcul` (`BBS_Slama_2025.11.1.0`), même mode
  d'application et même zone climatique :
  - 15 suivent la règle de la spec ;
  - 19 arrondissent à l'inférieur (`floor(Pdim/5) × 5`) ;
  - 11 appliquent en plus un facteur ≈ 0,85 à `Pdim` avant d'arrondir.
- Aucun facteur multiplicatif unique ne réconcilie l'ensemble : le meilleur
  (0,981) n'explique que 39 cas sur 81. « Arrondi au plus proche » en explique
  23. Écarté faute de source et de cohérence.
- Reste documenté à part : le cluster 2400E03338xx (`Pdim` 465,6 pour une
  référence à 370 kW, ratio 0,7946, 18 écarts de `pn`) et 2400E0669425G /
  2400E0669495Y, qui déclarent `nombre_appartement = 1` pour un immeuble de
  822 m² desservi par sept installations d'ECS.
- Ne rien changer sans une source : la table de la spec est explicite et notre
  lecture est celle qui colle aux 15 cas conformes.

### TASK-K06 — Écarts résiduels de génération ECS

- [~CLA] Owner: CLA  | Phase: K  | Estimation: 5h  | Priorité: moyenne
- Acquis : `rendement_stockage` n'est plus écrit sans ballon ; les puissances
  saisies en `donnee_intermediaire` sont préservées et utilisées.
- Reste : environ 33 divergences de `rendement_generation`, 33 de
  `rendement_stockage` et quelques COP, avec plusieurs installations ECS
  hétérogènes. Isoler les causes par configuration, sans règle globale déduite
  d'un seul fichier.
- Objectif : famille « Génération ECS » au-dessus de 92 % en profil strict.

### TASK-K26 — §17.1.2 : Shmoy_système est une moyenne, pas une somme

- [ ] Owner: __  | Phase: K  | Estimation: 4h  | Priorité: moyenne
- `StockageCalculator::sampledIndividualAdjustment` divise la surface de
  l'appartement « moyen » par la **somme** des surfaces des logements visités du
  groupe. Le §17.1.2 p.107 définit
  `Shmoy_système_i = Σ_j Sh_système_i,appartement_j / Nblgt_système_i`, soit la
  **moyenne** : avec la somme, le facteur décroît avec la taille de
  l'échantillon (20 visites → ÷19), ce qui est dimensionnellement faux.
- La référence est pourtant contradictoire d'un cas à l'autre, à moteur
  identique (`BBS_Slama_2025.11.1.0`). Facteur d'échelle déduit de son
  `rendement_stockage` (script de sondage : reconstruire `scale` depuis
  `Rs = k / (1 + Qgw·scale·Rd/Becs)`) :
  - `2688E0016745Q` (méthode 10, 1 installation) : `scale_ref = 1,0000`
    exactement, ce que la **moyenne** reproduit au chiffre près ;
  - `2659E2297555Z` (méthode 10, 1 installation) : `scale_ref = 1,0000`
    exactement, que ni la somme (0,0526) ni la moyenne (1,0526) ne donnent ;
  - `2657E1981571R` / `2657E1989142W` (méthode 11, 2 installations) : la
    **somme** reproduit la référence à 0,004–0,09 %, la moyenne s'en écarte de
    20 à 31 %.
- A/B mesuré sur le corpus complet (360 cas, révision `eaeb72e`) : passer à la
  moyenne donne +15 exactes et +7 dans-tolérance, mais fait basculer 98 valeurs
  de dans-tolérance à hors-tolérance sur les deux cas méthode 11 — conformité
  88,36 % → 88,30 %. **Correction non retenue en l'état**, la variable cachée
  restant l'affectation des logements visités aux sous-ensembles ECS quand une
  installation ne couvre pas tout l'immeuble (le découpage `$offset`/`$count`
  actuel est une hypothèse).
- Enjeu : sur `2659E2297555Z`, ce seul facteur explique la totalité des écarts
  du fichier — `rendement_stockage` (0,979 contre 0,710), donc `conso_ecs`
  (−27,5 %), donc `pertes_stockage_ecs_recup` et `besoin_ch` (+10,3 %, l'écart
  de besoin vaut exactement celui des pertes récupérées), puis les coûts
  électriques (−2,0 %, simple effet du terme fixe d'abonnement réparti sur un
  Cef plus faible), les GES, l'EP et les étiquettes.
- Quatrième sens observé, `2483E3258186E` (méthode 10, ballon de 175 L, 25
  logements) : la référence y reproduit **exactement** le Qg,w non ajusté de
  §11.6.2 (Rs = 0,629875 au chiffre près), donc un facteur 1 — l'inverse de
  `2513E1578589Q`, de même méthode et de même configuration.
- Variante mesurée et écartée : supprimer complètement l'ajustement
  d'échantillonnage donne +264 exactes mais **+1 452 hors tolérance**
  (89,33 % → 88,13 %). L'ajustement reste nécessaire sur le corpus.
- Troisième sens observé, `2513E1578589Q` (méthode 10, ballon électrique de
  100 L cat. B, 18 logements) : la référence implique un Qg,w de 24 kWh/an là
  où §11.6.2 en donne 435 pour ce ballon, et nous 217 via l'ajustement
  d'échantillonnage. Ses pertes récupérées à l'immeuble valent exactement celles
  d'**un seul** ballon alors que l'immeuble en compte 18. Aucune des trois
  lectures ne se recoupe ; la valeur publiée y est de surcroît physiquement
  impossible.
- Traiter avec TASK-K06 et TASK-H04, qui portent sur les mêmes grandeurs.

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
    (3 PAC air/air + 39 insert) ; `emetteurParams` retient le pire ΔPem et le
    δθdim du dernier émetteur lu.
- Piste écartée : `rat` calculé sur le besoin plutôt que sur la surface. §15.2.1
  dit « ratio du besoin couvert par l'équipement », mais les `besoin_ch`
  publiés par installation donnent exactement les mêmes ratios que les surfaces
  (0,50890 sur `2600E0000098Z`) — aucun effet sur ces deux cas.
- Restent à départager : l'échelle de `Sh` dans `Lem` et `shFactor`, et le
  traitement d'une installation à plusieurs émetteurs. §15.2.1 dit « Sh :
  surface habitable du bâtiment », ce que le code applique déjà : chercher la
  source avant de s'en écarter, et mesurer en A/B.

### TASK-K29 — Écarts non reproductibles des appartements 2313E359… (méthode 33)

- [ ] Owner: __  | Phase: K  | Estimation: 3h  | Priorité: basse
- Deux cas au corpus, `2313E3593911Y` et `2313E3593866F` : deux appartements du
  même immeuble, même diagnostiqueur, même `3cl_tribu_1.4.25.1`. Leurs 103
  balises en écart sont identiques ; le second ajoute seulement
  `classe_emission_ges`, conséquence directe de l'intensité GES.
- Écarts résiduels tous instruits et
  renvoient à la référence plutôt qu'au moteur. Consigné pour éviter de les
  réinstruire :
  - `q4pa_conv` : le fichier déclare `enum_methode_saisie_q4pa_conv_id = 2`
    (« mesure d'étanchéité à l'air de moins de deux ans ») et
    `q4pa_conv_saisi = 1,700`, mais publie 1,500, soit le forfait
    `tv_q4pa_conv[3]`. La référence ignore sa propre mesure.
  - `sortie/deperdition/hvent = 0` alors que sa propre
    `ventilation/donnee_intermediaire/hvent = 1573,568` égale la nôtre au
    chiffre près. Ce n'est pas une convention de méthode 33 : les trois autres
    cas de cette méthode au corpus publient un `hvent` non nul.
  - `k` des ponts thermiques : la référence multiplie `k` par
    `pourcentage_valeur_pont_thermique` (0,92 → 0,46). Appliquer ce facteur à
    `k` corrigerait 7 comparaisons au corpus et en casserait environ 177 ;
    notre `deperdition_pont_thermique` est d'ailleurs exacte sur ce cas.
  - Les apports et pertes récupérées sont sérialisés ×1 000 : cf. TASK-H03.
  - `upb = 2,0` contre 0,25 : même motif que TASK-H08.
- Ne rouvrir qu'avec de nouveaux cas de méthode 33 au corpus.

### TASK-K30 — Réseau de chaleur ECS multi-bâtiment : 0,9 ou 0,75 ?

- [ ] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: basse
- §14.3 p.95 remplace Rs × Rg par le rendement d'échange de la sous-station :
  0,9 si l'installation est isolée, 0,75 sinon. Le XSD ne porte ce qualificatif
  que sur les réseaux urbains 72/73 ; les types 74-77 et 134, « chaudière(s) …
  multi bâtiment modélisée comme un réseau de chaleur », n'en ont pas.
- **La référence se contredit sur des entrées identiques.** Trois cas de type 76
  au corpus, tous `enum_type_installation_id = 2` :

  | Cas | `reseau_distribution_isole` | bouclage | stockage | volume | Rs×Rg publié |
  |---|---|---|---|---|---|
  | `2513E0566835A` | 0 | 1 | 1 | 0 | 0,90 |
  | `2673E0034873H` | 1 | 2 | 3 | 1500 | 0,90 |
  | `2513E0192986F` | 1 | 2 | 3 | 1000 | **0,75** |

  Les deux derniers ne diffèrent que par le volume du ballon, que §14.3 ne fait
  pas intervenir. Aucune discriminante n'est dérivable des données publiées.
- 0,90 est retenu : il reproduit deux cas sur trois et mesure mieux sur le
  corpus complet (exactes 88 846 contre 88 834, hors tolérance 10 696 contre
  10 710 avec 0,75). `reseau_distribution_isole` a été écarté comme
  discriminante — il décrit le réseau de distribution interne, et vaut 0 sur un
  cas à 0,90.
- Ne rouvrir qu'avec de nouveaux cas de types 74-77 ou 134 au corpus.

## Validation obligatoire

- Toute correction doit être sourcée (spec/XSD/règlement), testée unitairement
  et mesurée en A/B isolé avec `bin/official-test-report` sur le corpus complet.
- Ne jamais élargir une tolérance ni ajouter de comportement propre à un numéro
  ADEME pour faire baisser un compteur.
