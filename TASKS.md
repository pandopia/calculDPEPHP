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

### TASK-H04 — Besoin et consommation ECS des fichiers collectifs

- [ ] Owner: __  | Phase: H  | Priorité: basse
- **Mesuré sur 513 cas** : sur les 783 divergences de `besoin_ecs` et
  `conso_ecs`, **deux seulement** suivent un facteur égal au nombre
  d'appartements — `2559E3128801Y` (×4) et `2633E1486544G` (×14). Les 781
  autres sont des écarts ordinaires, de rapport proche de 1.
- Ces deux fichiers sont exactement la configuration de TASK-K53, à traiter
  avec elle : installation ECS individuelle, `nombre_logement` = 1,
  `rdim` = 1, surface couvrant l'immeuble, tous les logements visités.
- La distinction immeuble / appartement échantillonné / appartement généré
  demandée par l'énoncé est donc en place partout ailleurs.
- Les pertes de distribution ECS sérialisées en Wh relèvent de TASK-H03,
  close : la convention est propre au format du fichier.

### TASK-H23 — Ponts thermiques des parois en adjacence 22 : convention d'éditeur

- [ ] Owner: __ | Phase: K | Priorité: basse
- §3.4 p.32 ne néglige que « les ponts thermiques des parois au niveau des
  circulations communes » (adjacences 14 à 18). Nous y ajoutons l'adjacence
  22, « local non déperditif (local à usage d'habitation chauffé) », au motif
  qu'une paroi qui ne déperdit pas n'est pas une limite de l'enveloppe.
- open3cl ne tranche pas non plus : il relit le k du DPE et note que les
  moteurs certifiés divergent — « 3cl-2024.6.1.0 exporte k = 0, Tribu 1.4.25.1
  et BBS 2024.6.1.0 conservent la valeur forfaitaire ».
- Mesuré en A/B isolé sur 500 cas : retirer l'adjacence 22 du veto vaut
  **+73 écarts** — 2501E0122493M y gagne 16, mais 2618E2138973C en perd 48 et
  2517E0227225G 41. La convention actuelle est conservée.
- Sur 2501E0122493M, cet écart explique à lui seul toute la chaîne :
  `deperdition_pont_thermique` 211,7 contre 231,5, donc
  `deperdition_enveloppe` −2,0 % et `besoin_ch` −2,4 %.

## Phase K — Conformité au corpus ADEME

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
- Le partage se lit aussi par éditeur, et il est net : LICIEL omet la balise
  sur 432 fichiers sur 433, les trois autres moteurs la publient à 0 sur
  leurs 48. Ce n'est donc pas une règle de méthode.
- Rouvrir si une référence publiant une valeur non nulle apparaît au corpus, ou
  avec un texte réglementaire donnant la formule.

### TASK-K41 — §17.2.2 : la clé de répartition du chauffage n'est pas dans le fichier

- [ ] Owner: __  | Phase: K  | Estimation: 4h  | Priorité: basse
- Périmètre `appartement_issu_immeuble` : 61 cas, 1 902 écarts moteur, 31,2 par
  cas — le plus faible du corpus. Instruit à fond ; **quatre pistes mesurées,
  aucune retenue**. Ne pas les reprendre sans élément nouveau.
- **`coef_ifc` n'est pas un manque.** §17.2.2.2.2 p.118 écrit
  `Cch_ap = (1 − coef_IFC) × Shap/Sh × Cch + coef_IFC × Clé_ap × Cch`, et 27
  fichiers le déclarent — mais `cle_repartition_ch` **porte déjà le mélange**.
  Sur `2657E1981571R`, le seul cas à `coef_ifc` = 0,7, la référence applique
  `cle_repartition_ch` = 0,0126619 tel quel ; appliquer la formule donnerait
  0,0121153. Les 26 autres sont à `coef_ifc` = 1, où la formule se réduit à
  `Clé_ap`. L'implémenter reviendrait à compter l'individualisation deux fois.
- **La clé que la référence applique n'est pas reconstructible.** Sur les 12 cas
  à écart de `sortie/ef_conso/conso_ch`, la clé implicite ne correspond ni au
  prorata de surface, ni à 1/`nombre_appartement`, ni au prorata de Nadeq —
  aucune des trois sur aucun des 12. Les neuf cas de mode 33/34 déclarent tous
  `cle_repartition_ch` = 1,0, donc aucune clé exploitable. §17.2.2.2.2 fait
  dépendre `Clé_ap` du besoin de chauffage **de l'appartement**, calculé lors du
  DPE immeuble et jamais sérialisé : l'enveloppe décrite est celle de l'immeuble.
- **`pertes_distribution_ecs_recup` : règle exacte mais inapplicable.** La
  référence multiplie le Tau de §15.2.3 par
  `Σ surface_habitable(installations ECS) / surface_habitable_immeuble` —
  vérifié **exactement sur 20 observations réparties sur 4 bâtiments**
  (Sh 2 064, 5 688, 348,3 et 269,7). Mesuré : 7 426 → 7 463 écarts moteur
  (+37) en le restreignant aux installations individuelles, 7 483 (+57) sans
  cette restriction. Sur `2400E0669882V` le correctif remet les deux balises de
  pertes en tolérance et fait sortir `besoin_ch` (0 → 7) et `conso_ch` (6 → 15) :
  **la référence publie une valeur de pertes qu'elle n'utilise pas dans son
  propre `besoin_ch`**, et c'est la nôtre qui est cohérente avec son besoin.
- Reste exploitable : le gros du périmètre est l'ECS (450 écarts), dont 35 cas
  sur 44 ont aussi `pertes_stockage_ecs_recup` divergent — c'est TASK-K26, pas
  §17.2.2.
- Cas supplémentaire du même mal, instruit et laissé : `2675E2540542G` (mode 10,
  78,66 %) — `pn` publié à 18 kW contre 5 kW chez nous parce que Pdim est
  calculé à une autre échelle, et des clés d'auxiliaires au rapport exact de
  1,125.

### TASK-K51 — Protection solaire extérieure : les moteurs ne s'accordent pas

- [ ] Owner: __ | Phase: K | Priorité: basse
- `protection_solaire_exterieure` : la règle « une seule baie non-nord sans
  fermeture suffit », qui est celle d'open3cl, donne 86 bonnes réponses sur
  105 références remplissant le bloc.
- Aucun seuil de surface ne sépare les 19 désaccords : les références qui
  répondent 1 malgré une baie sans fermeture vont de 0,23 m² (2659E2512272I)
  à 3,42 m² couvrant **100 %** du vitrage non-nord (2400E0038579Y), quand
  d'autres répondent 0 dès 0,35 m² (2400E0046693A) ou 3,9 % du vitrage
  (2400E0023332N). Cinq références répondent même 0 sans aucune baie non-nord
  à protéger.
- Variante mesurée et écartée : « au moins une baie avec fermeture » ne donne
  que 65 bonnes réponses. Ne pas chercher de seuil, et traiter avec TASK-K38,
  qui porte sur le même bloc.

### TASK-K53 — Besoin d'ECS d'une installation individuelle couvrant l'immeuble

- [ ] Owner: __ | Phase: K | Priorité: basse
- **Correction d'un constat trop rapide.** Les cinq cas tirés de l'open data
  pour départager cette question (2695E2478847D, 2695E2587385R, 2695E2369069X,
  2695E2107621F, 2695E1554794Q) sont tous **échantillonnés** — 5 visites pour
  12 logements, 15 pour 98, 4 pour 40… — et ne disent donc rien de la
  configuration contestée, qui est celle d'un immeuble **entièrement visité**.
- Parmi les immeubles dont tous les logements sont visités, la référence se
  partage **2 contre 1** :
  - besoin de l'immeuble : `2633E1486544G` (14 visites, 14 logements, mode 11)
    et `2559E3128801Y` (4 visites, 4 logements, mode 10) ;
  - besoin d'un logement : `2688E0016745Q` (7 visites, 7 logements, mode 10).
  Les trois ont la même configuration : installation ECS individuelle,
  `nombre_logement` = 1, `rdim` = 1, `surface_habitable` couvrant l'immeuble.
- Le garde « tous les logements visités → besoin de l'immeuble » reste écarté,
  et pour une raison plus forte qu'avant : mesuré sur 513 cas il coûte **+28
  écarts**, et il **dégrade 2559E3128801Y de 7** alors que c'est l'un des deux
  cas qu'il vise. Le facteur 4 de ce fichier n'est donc pas une simple
  question de division par le nombre de logements.
- Les cinq cas échantillonnés confirment en revanche notre traitement pour
  **leur** configuration, et rejoignent le corpus à ce titre.
- Même famille que TASK-H04, dont il ne reste que ces deux fichiers.

### TASK-K52 — Sérialisation exhaustive : reste la consommation par système

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- **Deux des trois correctifs sont livrés.** §17.1.4.2 p.110 calcule tout à
  l'échelle de l'appartement « moyen » dans un DPE d'immeuble à chauffage
  individuel : une sérialisation exhaustive — description unique couvrant
  l'immeuble, que `ExhaustiveInstallationNormalizer` ramène de la méthode 4 à
  la 1 — en relève. Son profil de charge voit désormais GV / Nblgt, et sa
  puissance se dimensionne au même périmètre, comme le commentaire de
  `RendementAnnuelMoyenCalculator` l'exigeait déjà.
  `rendement_generation` de 2659E2595696Y tombe sur 0,87167636743401 contre
  0,871677 publié.
- **Reste le troisième** : la référence publie `conso_ch` **par système**,
  nous le total du groupe — 6 514,126 contre 13 028,472 sur 2659E2595696Y,
  exactement le double. Même constat sur les deux 2659E04960…
- Population exacte : **trois fichiers** du corpus déclenchent le
  normaliseur — 2659E2595696Y, 2659E0496026K et 2659E0496027L. Vérifié en le
  passant sur les 541 entrées.
- `exhaustif-117` et `exhaustif-8` ont été ré-étalonnés : leurs valeurs sont
  notre propre sortie, pas une référence, et le test le dit désormais. Leurs
  chaudières passent de 400 et 55 kW à 24 kW — une chaudière domestique par
  logement, ce que décrit la sérialisation — et leurs étiquettes restent C/C.
- La sérialisation exhaustive est **rare** : sur vingt-huit immeubles tirés de
  l'open data dont l'installation couvre toute la surface, aucun n'a
  `enum_calcul_echantillonnage_id` = 1. Quand un fichier veut N systèmes, il
  le déclare d'ordinaire par `rdim` = N (2693E0440000W, 2692E1146857Q).

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
- **`aspect_traversant` n'est pas reproductible non plus.** Sur les 188
  références qui remplissent le bloc, 29 le contredisent, toutes dans le même
  sens : la référence dit 0 là où les baies couvrent deux orientations ou
  plus. Aucune règle géométrique ne sépare les deux groupes — les six paires
  d'orientations possibles apparaissent des deux côtés, et les orientations
  opposées ne tranchent pas davantage. Deux variantes mesurées :
  `plusieurs_facade_exposee` de la ventilation ne discrimine pas (23 cas
  `facades=1` à 0 contre 54 à 1), et les orientations des **murs** extérieurs
  font pire que celles des baies (40 erreurs contre 29). La règle d'open3cl,
  qui est la nôtre, reste la meilleure disponible.
- Décompte des désaccords réels, hors blocs vides ou absents :
  `enum_indicateur_confort_ete_id` 67, `protection_solaire_exterieure` 32,
  `aspect_traversant` 29, `isolation_toiture` 16, `brasseur_air` 3,
  `inertie_lourde` 2 — sur 188 blocs remplis.

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

- [~CLA] Owner: __  | Phase: K  | Estimation: 2h  | Priorité: basse
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
    `tv_q4pa_conv[3]`. La référence ignore sa propre mesure — **imputée**
    depuis par `ReferenceDefects`, qui couvre aussi la méthode 3.
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

### TASK-K42 — Échelles des exports exhaustifs chauffage et ECS

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- **Chiffres périmés, à ne pas reprendre tels quels.** Les trois cas nommés
  sont désormais au corpus. Sur `2659E0496026K` la référence publie
  `conso_ch` = 4 414,285 et nous 4 259,309, soit **3,5 %** d'écart et non le
  facteur 2 annoncé : la consommation est déjà publiée par système. Le résidu
  tient au rendement de génération, 0,996572 contre 1,032846.
- **Piste mesurée et retirée.** Faire consulter
  `ChauffageInstallationMultiplicity` pour les installations que
  `ExhaustiveInstallationNormalizer` ramène de la méthode 4 à la 1 — plomberie
  par le contexte, garde sur le chemin du nœud — est **sans effet** : le
  comportement attendu est déjà obtenu par une autre voie.
- **Avertissement de mesure.** Une première version de cette plomberie laissait
  `$context` hors portée dans EfConso et EpConso : 37 cas plantaient, et un cas
  planté ne compte aucun écart. Le rapport affichait alors −1 350 écarts, pur
  artefact. Toujours vérifier `cases_crashed` avant de lire un gain.
- Requalifier le garde de `ExhaustiveInstallationNormalizer` depuis les seules
  entrées et §17 ; pas de branche fondée sur un résultat attendu ou un numéro.
- Cas lié ECS : `2559E3128801Y` (419079/419083), format 2.5, quatre logements.
  Référence besoin ECS = 4 926,122 kWh, moteur = 1 231,530, rendement 0,854926
  contre 0,698657. Distinguer groupe entier et appartement moyen avec TASK-K06.
- Preuves et corpus figé : `docs/corrections-campagne-50f.md` et rapport
  applicatif `docs/dpe-campaigns/dpe-campaign-50f/`.

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

### TASK-K44 — Besoin de froid du DPE 2682E0504521A

- [ ] Owner: __ | Phase: K | Priorité: basse
- `2682E0504521A` est désormais au corpus (83,67 %). Besoin de froid
  conventionnel 1 117,727 contre 1 106,248 kWh (−1,0 %) ; dépensier
  3 324,268 contre 2 628,132 (−21 %). Le rapport dépensier/conventionnel vaut
  2,974 chez la référence et 2,376 chez nous.
- **Les sollicitations ne sont pas en cause.** Nref26 et DH28 des huit zones à
  l'altitude ≤ 400 m ont été recoupés un à un avec §18.2-18.3 p.121-143 :
  80 valeurs sur 80 conformes. La table porte désormais ce `@status verified`.
  La divergence est donc dans la formule de §10 — Rbthj, futj — pas dans les
  données climatiques.
- Fausse piste écartée : les 91 `Text` à zéro de `tv_sollicitations_froid`
  reprennent la convention de `text_mensuel_base_appoint`, où un zéro signale
  un mois hors saison de **chauffe**. La colonne n'est lue par aucun
  calculateur.
- Ne pas confondre avec le COP ECS du même fichier : le XML déclare un CET
  extérieur/ambiant de 2015, H2c et saisie forfaitaire ; §14.2 p.95 impose 2,5,
  la référence publie 2,8. Le moteur conserve ici la valeur réglementaire.

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

### TASK-K54 — Tranche tarifaire du scénario dépensier : deux lectures d'éditeurs

- [ ] Owner: __ | Phase: K | Priorité: basse
- Les coûts dépensiers sont tarifés chez nous sur leur propre panier : la
  tranche de l'annexe tarifaire est choisie sur la consommation dépensière.
  LICIEL applique au dépensier le prix unitaire établi sur le conventionnel,
  à la sixième décimale près (vérifié sur 2659E2632857F, 2659E2632938I,
  2659E2625548C, 2655E2140736I : `cout_ch_depensier` et `cout_ecs_depensier`
  y sont tous deux courts du même rapport, 0,987 à 0,957).
- Mesuré en A/B isolé sur 408 cas : partager la tranche vaut −69 écarts, mais
  le gain est **entièrement** porté par les 47 fichiers LICIEL du corpus, et
  les 10 fichiers des trois autres éditeurs (4.1.1, DPEWIN, EXPERTEC) y
  régressent tous. Égalité entre moteurs, pas majorité.
- L'annexe tarifaire tranche plutôt dans l'autre sens — « les frais annuels
  sont établis à partir de la formule de la plage de consommation
  correspondante » — et c'est la lecture en place. Ne rien changer sans un
  texte qui vise explicitement le scénario dépensier.
- Implémentation mesurée : `PrixEnergie::tarifer()` avec un second panier
  servant d'assiette aux tranches, appelé depuis `CoutCalculator`.

### TASK-K56 — Ligne E/F de QP0 : une référence contredit son propre moteur

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- 2662E2642251Q (maison, mode 1) : `qp0` 334,999 attendu contre 237,812.
  C'est l'unique racine du fichier — forcer la ligne « absence de
  ventilateur » le fait passer de 83,21 % à 99,27 %, 46 écarts à 2.
- Le générateur déclare `presence_ventouse = 1` et
  `presence_regulation_combustion = 1`, et la référence applique pourtant
  E = 2,5 / F = −0,8. Le XSD décrit `presence_ventouse` comme « présence d'un
  système de ventouse **ou avec un ventilateur** pour l'évacuation des
  fumées » : la ligne « présence de ventilateur » de §13.2.2 p.88 est la
  lecture littérale, et c'est la nôtre.
- Le même moteur (BBS_Slama_2025.11.1.0, LICIEL) applique E = 1,75 à trois
  générateurs de description strictement identique — type 90 ou 93, ventouse
  et régulation à 1, Pn 24 kW (2659E0944890K, 2669E0334575D, 2682E0333661M).
  Rien dans le fichier ne les distingue, sinon le périmètre du DPE : les
  trois sont des DPE immeuble (mode 6), le nôtre une maison.
- Variante mesurée en A/B isolé sur 409 cas : exiger en plus un périmètre
  collectif vaut −44 écarts, portés **en totalité** par ce seul fichier,
  sans aucune dégradation. Écartée : le périmètre du DPE ne dit rien de la
  présence d'un ventilateur dans le circuit de combustion, et les quatre
  observations qui la soutiennent viennent d'un seul moteur.
- À reprendre si le corpus livre un DPE de maison dont la référence applique
  E = 1,75 avec ventouse et régulation, ou l'inverse en immeuble.

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


