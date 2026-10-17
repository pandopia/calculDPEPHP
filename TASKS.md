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

- [ ] Owner: __  | Phase: H  | Estimation: 4h  | Dépend : TASK-H10
- Sur plusieurs immeubles, les valeurs divergent d'un facteur proche du nombre
  d'appartements. Distinguer calcul immeuble, appartement échantillonné et
  appartement généré depuis l'immeuble selon le §17.
- Examiner séparément les pertes de distribution ECS parfois sérialisées en Wh.
- Cibles : `src/Ecs/BesoinEcsCalculator.php`, `src/Collectif/`.

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

### TASK-K52 — Un générateur décrit pour N logements : trois références, deux verdicts

- [ ] Owner: __  | Phase: K  | Estimation: 4h  | Priorité: basse
- Instruit à fond sur `2659E2595696Y` (mode 10, 78,57 %). **Trois correctifs
  écrits, mesurés, puis retirés** : ils gagnent 36 écarts sur le corpus sans en
  dégrader un seul, mais contredisaient `exhaustif-117` et `exhaustif-8`.
- **L'élément qui départage est acquis.** Ces deux fixtures ne sont pas des
  références : le test qui les porte s'appelle
  `testLeCalculRetrouvePuissanceBesoinEtEtiquettesSansSortiesDeReference`, et
  leur XML ne contient aucun bloc `<sortie>`. Les valeurs attendues — pn
  400 000, EP 123, GES 25 — sont **notre propre sortie**, figée comme test de
  caractérisation. 2659E2595696Y est, lui, une référence ADEME publiée.
  Une référence réelle l'emporte sur un instantané de notre comportement :
  les trois correctifs sont à reprendre, et les deux fixtures à ré-étalonner.
- **Ne pas reconstruire les correctifs à l'aveugle.** Une tentative fondée sur
  le seul couple « une installation unique dont `surface_chauffee` couvre
  l'immeuble, `rdim` = 1 » coûte **+236 écarts** : le garde doit reprendre
  toutes les conditions de `ExhaustiveInstallationNormalizer` —
  `enum_calcul_echantillonnage_id` = 1, tous les logements visités, surfaces
  visitées sommant à celle de l'immeuble, version de moteur — et ne s'appliquer
  qu'aux installations que ce normaliseur ramène de la méthode 4 à la 1.
- Cinq cas neufs de la même sérialisation ont été tirés de l'open data
  (2695E2370845F, 2695E2370333N, 2695E2369399P, 2695E2369252Y, 2695E2369069X,
  de 9 à 33 logements) : tous sont à chauffage **électrique**, donc sans profil
  de charge ni `pn`, et ne départagent pas la question. Chercher des cas à
  générateur à combustion.
- La configuration : mode 10, installation `enum_type_installation_id` = 1
  (individuelle), `enum_methode_calcul_conso_id` = 4 ramené à 1 par
  `ExhaustiveInstallationNormalizer`, `rdim` = 1, `nombre_logement_echantillon`
  = 1, et `surface_chauffee` égale à `surface_habitable_immeuble`. Une seule
  description pour N logements.
- **Ce que demande 2659E2595696Y** (2 logements, tous deux visités) : N = 2
  générateurs. Vérifié à six décimales sur trois grandeurs indépendantes —
  `rendement_generation` passe de 0,92300 à **0,87167636743401** contre
  0,871677 publié dès que le profil de charge voit GV/2 ;
  `pertes_generateur_ch_recup` vaut exactement le double du nôtre ; et le
  circulateur de §15.2.1 suit le même facteur. Le cas passe de 78,57 % à
  **94,64 %**, 42 écarts à 6.
- **Ce que demandent `exhaustif-117` et `exhaustif-8`** : N = 1, la description
  agrégée vaut pour un seul système. Mêmes mode, type, méthode, `rdim` et
  `nombre_logement_echantillon`, mais 117 et 8 logements. Avec les correctifs,
  leur `ep_conso_5_usages_m2` passe de 123 à 128 et de 143 à 144.
- Aucun discriminant trouvé : ni le mode, ni le type d'installation, ni la
  méthode de calcul, ni `nombre_logement_echantillon`, ni la surface. Le seul
  écart visible est le nombre de logements (2 contre 117 et 8) et la puissance
  par logement (12 kW contre 3,4 et 6,9), mais Pn est **calculé** par le moteur
  depuis GV : il ne peut pas servir de discriminant sans circularité.
- Les 6 écarts résiduels de 2659E2595696Y après correctifs : 4 sur le
  `conso_ch` publié à l'installation et au générateur, que la référence écrit
  par système quand nous écrivons le total du groupe. Ce n'est pas un défaut :
  **120 installations du corpus sur 281** à générateurs tous à combustion
  publient un `conso_ch` inférieur à leur propre `besoin_ch`, soit un rendement
  supérieur à 1. C'est une convention du format, pas une erreur isolée.

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
- Reste : 54 divergences de `rendement_generation` en ECS réparties sur 46 cas,
  aux rapports ref/nous dispersés (0,60 à 2,05) sans famille dominante. Isoler
  les causes par configuration, sans règle globale déduite d'un seul fichier.
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

### TASK-K42 — Échelles des exports exhaustifs chauffage et ECS

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- Campagne 50f : `2659E0496026K` et `2659E0496027L` (bâtiment 431123),
  XML 2.6 LICIEL 319/BBS 2025.11, deux logements tous visités, une installation
  individuelle à `rdim=1` couvrant la surface totale. Le normalisateur la traite
  comme agrégée ; la référence publie pourtant la consommation de chauffage du
  logement moyen (4 414,285 contre 8 518,618 kWh sur le second cas).
- Requalifier le garde de `ExhaustiveInstallationNormalizer` depuis les seules
  entrées et §17 ; pas de branche fondée sur un résultat attendu ou un numéro.
- Cas lié ECS : `2559E3128801Y` (419079/419083), format 2.5, quatre logements.
  Référence besoin ECS = 4 926,122 kWh, moteur = 1 231,530, rendement 0,854926
  contre 0,698657. Distinguer groupe entier et appartement moyen avec TASK-K06.
- Preuves et corpus figé : `docs/corrections-campagne-50f.md` et rapport
  applicatif `docs/dpe-campaigns/dpe-campaign-50f/`.

### TASK-K46 — Contenu CO2 des réseaux : colonne et millésimes manquants

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- **Notre table et notre règle de millésime sont justes sur 9 réseaux sur
  12** vérifiés contre le facteur que les références impliquent
  (`emission_ges_ch / conso_ch` de la ligne énergie 8) : 6921C, 7501C sur deux
  arrêtés, 0106C, 5905C, 8201C, 5901C, 5703C en 2026, 2406C, 8401C.
- Trois désaccords, de trois natures différentes :
  - **0106C** (2675E0015989B, arrêté 2025-04-11) : la référence publie 0,040,
    nous 0,000 — c'est notre table qui a perdu la cellule, voir ci-dessous.
  - **5703C** (2457E3582864U, arrêté 2024-07-05) : référence 0,068, aucune de
    nos quatre années (0,153 / 0,092 / 0,187 / 0,174) n'en approche. C'est la
    piste de la colonne **CO2 direct** contre **CO2 ACV**, à trancher avec le
    texte : l'arrêté du 5 juillet 2024 publie 0,097 en direct et 0,118 en ACV
    pour 9514C, et la référence de 2595E1189146F retient 0,097.
  - **4211C** (2242E2979513I, sans `date_arrete_reseau_chaleur`) : référence
    0,109, nos quatre millésimes vont de 0,048 à 0,075.
- Ne pas remplacer un identifiant isolément ni inférer une colonne depuis les
  résultats attendus : établir d'abord laquelle des deux colonnes l'arrêté DPE
  désigne, puis mesurer sur tous les réseaux du corpus.

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
- Campagne 50f, bâtiment 1540251 / logement 3862474 : besoin de froid
  conventionnel 1 117,727 contre 1 106,248 kWh ; dépensier 3 324,268 contre
  2 628,132. Remonter aux sollicitations et apports mensuels de §10 avant
  de toucher au rendement ou à la consommation finale.
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


