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
- Cas mesuré : `2676E2269868T` (`<dpe version="8.0.2">`, moteur « Moteur DPE:
  DPE_2025.11.1.0 ») publie `besoin_ecs`, `apport_interne_ch`,
  `pertes_distribution_ecs_recup{,_depensier}` et `pertes_stockage_ecs_recup`
  exactement ×1 000 par rapport à notre sortie, alors que le XSD documente ces
  cinq balises en kWh — donc défaut de référence à déclarer dans
  `ReferenceDefects`, pas une convention à reproduire. `IntermediateEnergyUnit`
  reproduit aujourd'hui le ×1 000 pour les versions `0.1.0` et `9.x` : trancher
  les deux sens dans la même tâche, la version `8.0.2` n'ayant qu'un seul cas au
  corpus, ce qui ne suffit pas à étendre la liste de versions.

### TASK-H04 — Besoin et consommation ECS des fichiers collectifs

- [ ] Owner: __  | Phase: H  | Estimation: 4h  | Dépend : TASK-H10
- Sur plusieurs immeubles, les valeurs divergent d'un facteur proche du nombre
  d'appartements. Distinguer calcul immeuble, appartement échantillonné et
  appartement généré depuis l'immeuble selon le §17.
- Examiner séparément les pertes de distribution ECS parfois sérialisées en Wh.
- Cibles : `src/Ecs/BesoinEcsCalculator.php`, `src/Collectif/`.

### TASK-H06 — Puissance de ventilation VMC SF Hygro B post-2012

- [ ] Owner: __  | Phase: H  | Estimation: 1h
- Plusieurs références anciennes attendent `pvent_moy=0` contre 15 W pour
  `enum_type_ventilation_id=15`, `ventilation_post_2012=1`.
- Vérifier la table officielle et open3cl avant de décider si la référence ou
  `resources/tables/ventilation/tv_pvent_moy.php` est fautive.

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

### TASK-K04 — Écarts résiduels de chauffage

- [ ] Owner: __  | Phase: K  | Estimation: 8h  | Priorité: haute
- Premier amont à traiter : `pn`, puis `qp0`, températures de fonctionnement,
  `rpn`, `rendement_generation` et seulement ensuite `conso_ch`.
- Acquis à ne pas refaire :
  - la formule §13.2.2.4 de `Pch` est déjà implémentée ;
  - grosses chaudières collectives : cluster 2400E03338xx, référence 370 kW
    contre 405 kW, sans grandeur publiée expliquant encore 370 kW ;
  - petites chaudières : le choix murale ≥2006 (5/10/13 kW) contre sur sol ou
    ancienne (minimum 18 kW) n'est pas dérivable du XSD standard ; seul
    `data_complementaires/@data-chaudiere-murale` le porte parfois ;
  - installations à plusieurs générateurs : répartir Pch par surface desservie
    conformément au §13.2.2.4 ;
  - le taux de charge utilise déjà la puissance installée totale.
- Traiter les trois groupes séparément, mesurer chaque hypothèse en A/B complet
  et classer non reproductibles les sorties dépendant de données cachées.

### TASK-K06 — Écarts résiduels de génération ECS

- [ ] Owner: __  | Phase: K  | Estimation: 5h  | Priorité: moyenne
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
- Traiter avec TASK-K06 et TASK-H04, qui portent sur les mêmes grandeurs.

## Validation obligatoire

- Toute correction doit être sourcée (spec/XSD/règlement), testée unitairement
  et mesurée en A/B isolé avec `bin/official-test-report` sur le corpus complet.
- Ne jamais élargir une tolérance ni ajouter de comportement propre à un numéro
  ADEME pour faire baisser un compteur.
