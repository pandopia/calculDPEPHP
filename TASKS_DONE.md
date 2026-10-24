# TASKS_DONE — enquêtes closes

Ce fichier n'est pas un journal des travaux : **le code livré se lit dans
l'historique Git**, et une tâche dont le travail est fait est supprimée de
`TASKS.md`, pas recopiée ici.

Il archive l'autre moitié : les questions **instruites puis closes sans
changement de code**. Un verdict mesuré, une variante écartée avec son coût,
une convention d'éditeur constatée — autant de chiffres qu'il serait coûteux
de réinstruire, et dont l'oubli ferait reprendre une piste déjà payée.

Chaque bloc garde sa forme d'origine. La case `[ ]` n'y signifie plus rien :
ce qui compte est la conclusion et le chiffre qui la porte.

**Avant de rouvrir l'un d'eux**, vérifier que la condition de réouverture
qu'il énonce est réellement remplie — un nouveau cas au corpus, un texte
réglementaire, un second éditeur. Un désaccord avec notre moteur n'en est pas
une.

## Enquêtes closes

### TASK-K06 — Verdicts historiques COP et sérialisation, à conserver

- Mesure historique, pas un nouveau décompte des 670 cas : sur 45 écarts de
  Rg ECS, 43 avaient déjà une caractéristique Pn/Rpn/QP0 divergente. Les deux
  isolés étaient la chaudière électrique (corrigée à 0,97, §12.4.1 p.76) et
  la référence lacunaire K35. Les puissances amont restent en K85/K95.
- Rs sans ballon : 10 références l'écrivaient à 1, mais sa publication aurait
  créé 191 balises supplémentaires. Ne pas le publier sans stockage. Les
  12 Rg supplémentaires alors recensés étaient des générateurs à combustion,
  pas des CET, dont le COP inclut le stockage selon le XSD.
- COP : §14.2 p.95 donne 2,5 sur 2400E0020849A (type 3 H1a), 2,2 sur
  2401E0359228Q (type 2 H1c), 2,3 sur 2601E0981468A (type 12 H1c). Leurs
  références publient respectivement 2,8, 2,5 et 2,6, des valeurs d'autres
  lignes. 2600E0099076V et 2400E4097217K déclarent tous deux H2c, type 6,
  période 3, forfait, mais publient 3,8 et 3,2. Les valeurs 3,8, 3,2 et
  3,07 sont déjà imputées par `copForfaitaireHorsTable()` car absentes de
  toute la table ; ne pas ajouter les autres en refaisant notre calcul.
- Ne rouvrir ces verdicts qu'avec une nouvelle règle ou une entrée permettant
  de départager les références, jamais pour relever le COP forfaitaire.

### TASK-K06 — Sondage ADEME des blocs ECS à deux générateurs (24/10/2026)

- Aucun bloc à plusieurs générateurs ECS parmi les 670 cas du corpus. Le
  filtre public DataFair `type_generateur_n2_ecs_n1:*` indexe 807 DPE,
  tous antérieurs au 19/07/2022 dans la sélection triée par date décroissante ;
  zéro en 2026. Trois XML ont été récupérés par `bin/fetch-dpe`, sans retouche.
- Ils recopient tous `conso_ecs` de l'installation dans chacun de ses deux
  générateurs : 117 558,135673123 kWh sur `2160E0981223R` (gaz/gaz),
  59 821,5344301015 sur `2193E0977090Z` (gaz/CET), 3 104,14284610104 sur
  `2276E1627094N` (gaz/bois). La sortie EF ne reprend qu'une fois ce total.
  Les trois déclarent cfg ECS 1, ratio 1 sur les deux générateurs ; les
  moteurs sont `3CL_BBSSlama_1.0.0.0` et `3CL_BBSSlama_1.1.0.0`.
- Le XSD décrit pourtant Cecs comme la consommation annuelle **du générateur**
  et le ratio comme 0,5 en présence de deux générateurs. Ces copies ne peuvent
  départager systèmes indépendants et cascade/appoint. Ne pas les utiliser
  pour choisir le partage de §11.4 p.73, ni diviser une consommation déjà
  calculée sans reprendre les besoins de Rs, Rg et des auxiliaires.
- Les six fichiers d'investigation (input et référence) sont conservés hors
  du corpus, dans `/tmp/calculdpe-ecs-probes-KAHR3G/`. Aucun ancien cas n'est
  exclu, aucun compteur ni budget des 670 cas n'est modifié par ce sondage.
  SHA256 des références : `2160…` =
  `fe64e8f19df55d9a68de7bcd350dd71d6df3fe9d01bc3a2ba7f39a2ec23db994`,
  `2193…` = `0361325017e44ef725b3f6a12e744749174225759a7cc2d45807a67b9ac932ed`,
  `2276…` = `694540cfc3d52d420ba4a26d7f899a26fd51634d0a259045b3dc3c22118356c8`.
- Rouvrir sur un export cohérent ou une clarification du modèle de cascade
  ECS. Le calcul multi-générateurs reste à instruire en K06 ; aucun correctif
  de calcul ECS n'est livré sur la seule base de ces trois références.

### TASK-K95 — QP0 calculé directement sur Pe (24/10/2026)

- A/B en mémoire sur `0b075a7`, même corpus 670 cas, aucun fichier `src/`
  non commité, bandes de puissance bois corrigées dans les deux variantes.
  Seul QP0 change : `a × QP0(Pn_collectif)` devient `QP0(Pe)`, conformément
  à la lecture littérale du tableau §17.2.1.1 p.112.
- Numériques hors tolérance **17 843 → 18 058** (+215), classes 44 → 52
  (+8), manquantes 688 et supplémentaires 2 355 inchangées. Aucun écart
  résolu, 223 nouveaux ; huit cas dégradés, aucun amélioré. Les hausses de
  budget moteur seraient respectivement +40, +19, +40, +39, +1, +16, +16,
  +36 sur 2400E0007293Q, 2401E0490195V, 2600E1626587G, 2600E1932292D,
  2606E0021556N, 2606E0147011S, 2606E0835323G et 2613E2419802A.
- La variante est retirée, sans relever les budgets. Reprendre avec une
  clarification du calcul des pertes du générateur virtuel ou des exports
  permettant de départager QP0 physique et QP0 équivalent (suite K95).

### TASK-K96 — Condition d'inertie du sélecteur climatique global (24/10/2026)

- A/B isolé en mémoire sur l'arbre `42f819a`, 670 cas, aucun fichier `src/`
  non commité : imposer une classe ADEME 1 ou 2 en plus de
  `batiment_materiaux_anciens=1` porte les numériques hors tolérance de
  **17 843 à 18 291** (+448), les classes de 44 à 46 (+2). Manquantes 688
  et supplémentaires 2 355 inchangées ; aucune tolérance modifiée.
- §18.3 p.136 exprime bien les deux conditions, mais 14 références sur les
  51 à matériaux anciens publient une classe moyenne ou légère. Le sélecteur
  commun actuel et open3cl suivent le drapeau seul. Cette mesure ne permet
  pas d'inventer une qualification différente pour ces seuls fichiers.
- Ne pas rejouer cette variante sans clarification de la sémantique du
  drapeau, des classes publiées ou export corrigé. La suite actionnable est
  conservée en K96 ; aucun changement du sélecteur global n'est livré.

### TASK-K67 — Besoin ancien incompatible avec ses propres apports (24/10/2026)

- `2242E2979513I`, modèle 2.2 du 06/04/2022 : ni erreur d'échelle ni
  rendement de réseau à corriger. Le fichier est bien à l'immeuble (mode 26,
  5 180 m²) ; Rg et tous les rendements d'émission/distribution/régulation
  concordent. Le premier écart est Bch : 97 539,920 contre 117 543,045 kWh.
- GV, Sse, Nadeq et apports annuels sont reproduits. Mais la référence
  publie F=0,639, GV=5 439,117 W/K, zone H1c <400 m (ΣDH19=57 673 °Ch),
  5 836,298 kWh de pertes récupérées, zéro stockage/génération récupéré.
  §6.1 et §9.1.1 imposent
  `Σ max(0, GV×DHj×(1−Fj)/1000−Lrecj) ≥ GV×ΣDH×(1−F) /1000−ΣLrec`.
  Même en majorant F à **0,640**, cette borne vaut **107 092,172 kWh**,
  supérieure de 9 552,252 à son Bch. Il faudrait au moins F=0,670451.
- La pondération annuelle de Fj par GV×DHj est aussi celle d'open3cl
  (`9_besoin_ch.js`, lignes 198–206). La référence recopie en outre 0,639
  dans le scénario dépensier malgré les DH21 et besoins différents.
- Aucun moteur modifié, aucune nouvelle imputation ni exclusion : les
  compteurs bruts restent inchangés. Ne pas abaisser artificiellement le
  besoin pour reproduire ce fichier. Rouvrir sur export corrigé ou donnée
  mensuelle/clarification de la sémantique du F publié permettant de lever
  cette contradiction.

### TASK-K94 — Variante globale de volume G non retenue (24/10/2026)

- A/B isolé sur l'arbre `3868d54`, même corpus 670 cas et un fichier `src/`
  non commité, substitution en mémoire du seul trait : 18 301 → 18 045
  numériques hors tolérance (−256), 44 → 40 classes, 688 manquantes et
  2 355 supplémentaires inchangées. Quinze cas gagnent, quatre perdent.
- Les budgets qui augmenteraient sont `2400E0856203A` (+1, GES/m²),
  `2401E0473146C` (+1, GES/m²), `2601E1280780A` (+1, coût ECS) et
  `2611E1951763L` (+2, coûts auxiliaires). Aucun budget n'est relevé.
- Le sous-périmètre des appartements décrits directement apporte −41
  numériques, aucun nouveau défaut et aucun cas dégradé ; il est livré
  séparément. Ne pas rejouer la généralisation aux maisons sans avoir
  départagé les quatre résidus d'aval (suite actionnable K94).

### TASK-K85 — Variantes de périmètre GV déjà écartées

- Conserver ces chiffres historiques, sans les présenter comme des mesures
  du corpus actuel : abandonner la réduction du GV pour les modes 6/8 a coûté
  **+2 075 écarts sur 541 cas**, 3 cas améliorés contre 82 dégradés, malgré
  un gain local de 29 sur `2400E0669882V`. Pas de généralisation possible.
- Le diviseur « nombre de systèmes » non borné par Nblgt a coûté **+537
  écarts sur 23 cas**. La borne protège le périmètre de l'appartement moyen.
- Un Rg juste ne prouve pas que chaque Pn de la zone est juste : des erreurs
  se compensent. La suite actionnable sur les Pn amont demeure en K85.
- Réouverture de ces variantes uniquement sur clarification du périmètre
  réglementaire ou nouvelle donnée d'entrée départageant ces cas.

### TASK-K93 — Variantes globales de colonne Joule écartées (23/10/2026)

- A/B en mémoire, même arbre `70ca91e`, 646 cas, profil strict, aucun fichier
  `src/` modifié. La réplication sans variante reproduit exactement le rapport
  officiel : 17 247 hors tolérance, 626 manquantes, 2 230 supplémentaires.
- Dès qu'un générateur Joule existe : **17 451**, soit +204 ; même résultat
  en ne considérant que les générateurs liés à la génération principale.
- Joule seulement si tous les générateurs connus sont Joule : **17 347** (+100).
- Majorité surfacique ≥50 %, convention de `open3cl/src/utils.js` : **17 300** (+53).
- Le bug d'ordre est réel, mais ni §3.2 p.12 ni les guides publics consultés
  n'établissent un seuil de majorité pour ce calcul. Rien n'est codé ni aucun
  budget relevé. La suite actionnable demeure dans TASKS.md ; ne pas refaire
  ces quatre variantes sans une donnée ou clarification réglementaire nouvelle.

### TASK-K71 — Froid H1c 400-800 m : la référence ajoute `2,03 × Sse_mai`

- Enquête close sans changement de code, 20 octobre 2026, corpus 646 cas.
  Les cinq références `2401E2888143P`, `2601E0370414Y`, `2601E0734445D`,
  `2601E0869643B`, `2605E1578332M` sont en H1c à 400-800 m.
- Recalculer les Sse mensuelles depuis leurs propres `Sw`, `Fe1`, `Fe2`
  reproduit leur Sse annuelle. Leur apport solaire de froid vaut exactement
  `Σ(Sse_j × E_fr28_j) + 2,03 × Sse_mai` : le quotient du résidu par
  `Sse_mai` vaut respectivement 2,0300000000, 2,0299999989, 2,0300000000,
  2,0299999811 et 2,0300000018. Ce n'est ni septembre ni un facteur global
  de surface ; les décalages de mois de −2 à +2 ne reproduisent pas ces cas.
- §18.2.2 p.130 publie pourtant un **tiret** en mai, H1c, 400-800 m,
  consigne 28 °C. L'annexe consolidée téléchargée sur le site ministériel
  est identique octet pour octet à `resources/spec.pdf`
  (SHA256 `66ffacbb172d045d09bd652d0ada6450b2e5ae289b61ebb6303d78e76c3f4f5a`).
  Contrôle visuel de la page effectué. Open3CL publie également zéro.
- **Ne pas remplacer zéro par 2,03 sans source réglementaire.** Ce nombre
  existe dans d'autres colonnes/consignes ; l'origine exacte de la valeur
  utilisée par l'éditeur n'est pas établie. Rouvrir seulement sur publication
  d'un corrigendum officiel applicable à cette cellule, ou d'une règle
  sourcée expliquant son remplacement. Aucune tolérance ni imputation
  automatique à `ReferenceDefects` n'a été ajoutée.
- Les références sans climatisation `2600E0015965G` et `2600E0020799E`
  restent isolées : 506 autres cas sans climatisation publiaient zéro sur
  le corpus de 541. Ne pas fabriquer un besoin de froid pour les suivre.

### TASK-K88 — Balises manquantes et surnuméraires : presque tout est convention d'éditeur

- [ ] Owner: __ | Phase: K | Priorité: basse
- Le rapport compte **335 balises manquantes** et **2 132 surnuméraires** à
  côté de ses 13 212 écarts de valeur. Les deux postes semblent gros ; ils
  sont presque entièrement hors de notre portée, et ce bloc existe pour que
  personne ne les réinstruise.
- **Surnuméraires : 2 007 sur 2 132 sont le bloc `confort_ete`** — six balises
  sur environ 335 cas. Les références le publient vide, `ReferenceDefects` les
  impute déjà sur la preuve du schéma. Le reste se disperse en quinze balises
  à moins de quinze occurrences.
- **Manquantes : 236 sur 335 sont les quatre `*_auxiliaire_distribution_fr`**,
  sur 59 cas. Soixante références les publient, **toutes à 0,000**, et le XSD
  les déclare `minOccurs="0"` : les omettre est donc conforme, et les publier
  partout échangerait 236 manquantes contre environ 1 900 surnuméraires.
  Aucun discriminant : la climatisation n'en explique que 7 sur 60, et le même
  moteur `3cl_tribu_1.4.25.1` les publie 8 fois et les omet 96 fois.
- **La ligne `sortie_par_energie` d'énergie 12** — « électricité d'origine
  renouvelable utilisée dans le bâtiment » — n'existe que dans **une seule
  référence sur 541**, `2601E0370414Y`, qui y porte l'autoconsommation
  photovoltaïque avec un coût et des émissions nuls. Convention minoritaire :
  l'ajouter créerait des surnuméraires sur les huit autres cas du corpus à
  autoconsommation non nulle.
- Les autres postes sont déjà tranchés ailleurs : `rendement_stockage` à 1
  (TASK-K06), les rubriques de plancher haut (bloc archivé « une seule
  rubrique »).
- **Conclusion** : le gisement actionnable est dans les écarts de **valeur**,
  pas dans la présence des balises. Rouvrir seulement si le XSD rend
  `conso_auxiliaire_distribution_fr` obligatoire dans une version ultérieure.

### TASK-K83 — Pertes de stockage ECS récupérées : notre calcul est exact, la condition est indécidable

- [ ] Owner: __ | Phase: K | Priorité: moyenne
- **Point de départ** : 31 des 33 maisons dont `besoin_ch` est hors tolérance
  l'ont **sous-estimé**, ratios de 0,78 à 0,997. Le biais est directionnel, pas
  du bruit.
- **Premier intermédiaire divergent isolé** : sur 15 d'entre elles, `GV` et
  `fraction_apport_gratuit_ch` sont justes, donc l'écart ne peut venir que du
  terme suivant. En reconstruisant le besoin brut
  `GV × ΣDH19 × (1 − F) / 1000`, la référence en retranche 0,24 à 0,60 % de
  pertes récupérées quand nous en retranchons 2,07 à 3,78 % — six à dix fois
  trop. Le coupable est `pertes_stockage_ecs_recup`, et l'écart le confirme au
  kWh près : 427,43 publiés par nous contre 426,40 d'écart sur
  `2400E0020849A`.
- **Notre calcul n'est pas en cause.** Sur les **67 références au moins qui
  publient une valeur non nulle, nous la reproduisons à 1,0000** — y compris
  163 610,51 et 290 244,82 —, et 18 publient zéro là où nous publions une
  valeur. (Ces deux nombres sont des minorants : le rapport ne détaille pas
  les valeurs reproduites **exactement**, qui ne peuvent que renforcer le
  premier.)
- **Et la condition qui les distingue n'existe pas dans les données.** Testé et
  écarté :
  - `position_volume_chauffe` du générateur en repli de
    `position_volume_chauffe_stockage` (large, puis restreint au stockage
    intégré à la production) : **−9 écarts, mais 9 cas améliorés contre 7
    dégradés**, et les dégradés partaient de 2, 3 et 4 écarts pour monter à
    32, 39 et 44. Mesuré en A/B isolé, 0 crash.
  - Le profil d'entrée ne sépare rien : `2600E1253780N` et `2600E0017120R`
    sont tous deux de type 69, `position_volume_chauffe = 0`, stockage intégré
    — le premier s'améliore, le second se dégrade.
  - Le moteur éditeur non plus : Tribu publie 11 zéros et 20 valeurs, BBS
    Slama 5 et 32.
- Les références se contredisent donc entre elles sur des configurations
  identiques. **Rouvrir si** le corpus livre un cas où une donnée d'entrée
  sépare les deux comportements, ou si §9.1.1 reçoit une précision sur la
  localisation du stockage.

### TASK-K81 — Le coût dépensier des références recopie le conventionnel 4 fois sur 5

- [ ] Owner: __ | Phase: K | Priorité: basse
- `cout_ch_depensier` et `cout_ecs_depensier` sont les deux plus gros postes du
  rapport après les consommations — 500 et 505 écarts pour **autant de cas**,
  soit un par fichier. Ce profil « un par cas » laissait espérer une règle
  systématique à corriger. Il n'y en a pas.
- **Les références recopient le coût conventionnel dans 163 cas sur 196**
  (83 %), alors qu'elles calculent toujours une consommation dépensière
  distincte — `conso_ch_depensier ≠ conso_ch` sur **196 sur 196**. Une
  référence qui publie une consommation dépensière plus forte et un coût
  identique se contredit elle-même.
- **La recopie n'obéit à aucune règle reproductible.** Elle n'est ni propre à
  un moteur (Tribu 85 recopient contre 8 qui calculent, BBS Slama 76 contre
  12), ni à une année (2024 : 87/11 ; 2026 : 76/22), ni à une énergie de
  chauffage (élec 78/10, gaz 39/8), ni à un mode d'application (méthode 1 :
  62/24, méthode 2 : 37/1). La coder reviendrait à copier une observation.
- **Et notre valeur n'a pas de défaut propre** : sur les 33 références qui
  calculent vraiment, notre rapport dépensier/conventionnel colle au leur à
  quelques millièmes — 1,2162 contre 1,2134, 1,2055 contre 1,2044, 1,2366
  contre 1,2376. Les écarts restants viennent du `cout_ch` conventionnel, donc
  de `conso_ch` en amont : c'est là qu'il faut remonter, pas sur le dépensier.
- `ReferenceDefects::valeurDepensiereRecopiee` impute déjà ces écarts à la
  référence. **Rouvrir si** un texte réglementaire définit le coût dépensier,
  ou si un éditeur se met à le calculer systématiquement.

### TASK-K82 — Méthode 26 : le facteur commun était interne, pas dans la référence

- [ ] Owner: __ | Phase: K | Priorité: basse
- **Première conclusion, erronée** : le taux de conformité de `besoin_ch` par
  mode d'application isole la méthode 26 — immeuble à chauffage **et** ECS
  mixtes — avec 0 cas conforme sur 6, mais ses ratios obtenu/attendu sont
  dispersés (1,2051 ; 1,0082 ; 0,9990 ; 0,9760 ; 1,0510 ; 1,0123). J'en avais
  conclu qu'aucun facteur commun n'existait.
- **Le facteur commun existait, et il était chez nous.** Un balayage
  d'invariants internes — `deperdition_renouvellement_air = hvent + hperm` — a
  désigné **exactement ces six fichiers, et eux seuls sur 541**. Nous
  publiions `hvent = 0` pour le mode 26, au titre d'une « convention ADEME ».
- Le travail correspondant est livré (commit « cesser d'imiter le hvent nul »)
  et ce bloc n'est gardé que pour sa leçon : **un profil d'écarts dispersé ne
  prouve pas l'absence de cause commune**. La comparaison à la référence ne
  voyait rien, parce que cinq des six références portent le même défaut ; seul
  un invariant interne pouvait le montrer.
- Le seul écart franc qui subsiste sur ces six fichiers, `2242E2979513I` à
  +20,5 % sur `conso_ch`, reste TASK-K67.

### TASK-K81 — Le coût dépensier des références recopie le conventionnel 4 fois sur 5

- [ ] Owner: __ | Phase: K | Priorité: basse
- `cout_ch_depensier` et `cout_ecs_depensier` sont les deux plus gros postes du
  rapport après les consommations — 500 et 505 écarts pour **autant de cas**,
  soit un par fichier. Ce profil « un par cas » laissait espérer une règle
  systématique à corriger. Il n'y en a pas.
- **Les références recopient le coût conventionnel dans 163 cas sur 196**
  (83 %), alors qu'elles calculent toujours une consommation dépensière
  distincte — `conso_ch_depensier ≠ conso_ch` sur **196 sur 196**. Une
  référence qui publie une consommation dépensière plus forte et un coût
  identique se contredit elle-même.
- **La recopie n'obéit à aucune règle reproductible.** Elle n'est ni propre à
  un moteur (Tribu 85 recopient contre 8 qui calculent, BBS Slama 76 contre
  12), ni à une année (2024 : 87/11 ; 2026 : 76/22), ni à une énergie de
  chauffage (élec 78/10, gaz 39/8), ni à un mode d'application (méthode 1 :
  62/24, méthode 2 : 37/1). La coder reviendrait à copier une observation.
- **Et notre valeur n'a pas de défaut propre** : sur les 33 références qui
  calculent vraiment, notre rapport dépensier/conventionnel colle au leur à
  quelques millièmes — 1,2162 contre 1,2134, 1,2055 contre 1,2044, 1,2366
  contre 1,2376. Les écarts restants viennent du `cout_ch` conventionnel, donc
  de `conso_ch` en amont : c'est là qu'il faut remonter, pas sur le dépensier.
- `ReferenceDefects::valeurDepensiereRecopiee` impute déjà ces écarts à la
  référence. **Rouvrir si** un texte réglementaire définit le coût dépensier,
  ou si un éditeur se met à le calculer systématiquement.

### TASK-K82 — Méthode 26 : zéro conforme sur six, mais aucun facteur commun

- [ ] Owner: __ | Phase: K | Priorité: basse
- Le taux de conformité de `besoin_ch` par mode d'application isole la
  **méthode 26** — immeuble collectif à chauffage **et** ECS mixtes — avec
  0 cas conforme sur 6, quand les méthodes 7, 9 et 13 sont à 100 %.
- Ce profil fait penser à un défaut systématique. Les ratios obtenu/attendu
  disent le contraire : 1,2051, 1,0082, 0,9990, 0,9760, 1,0510, 1,0123. Aucun
  facteur commun, et `2501E0014261S` est à 0,1 % près de la tolérance.
- La méthode 26 est simplement le périmètre le plus difficile — deux
  répartitions mixtes superposées — et ses six cas cumulent des écarts fins
  d'origines différentes. Le seul écart franc, `2242E2979513I` à +20,5 %, est
  déjà TASK-K67.
- **Rouvrir si** le corpus gagne assez de cas de méthode 26 pour qu'un facteur
  commun se dégage.

### TASK-K80bis — Cascade avec priorité : le subordonné assure-t-il 5 % du besoin ?

- [ ] Owner: __ | Phase: K | Priorité: basse
- **Le chantier de TASK-K80 est livré** (trois commits : reconnaissance de la
  cascade, profil de charge par générateur, part du besoin pour les
  auxiliaires). Ce qui est archivé ici est le verdict sur sa **dernière
  question ouverte**, qui ne demande aucun code.
- Nous faisons assurer **tout** le besoin au générateur prioritaire, et rien au
  subordonné. La référence `2406E1688667C` publie 1 118 061,89 et 59 635,56,
  soit 94,94 % / 5,06 %.
- **Les 5 % de la référence ne se dérivent d'aucune lecture du profil.** Trois
  candidats ont été calculés sur le Cdimref du fichier (0,8012, pour 700 kW
  installés et 33 602 W/K) : `Σ(CTchx/Tchx)·pond` donne 8,15 %, `Σ pond·CTchx`
  — l'énergie réellement fournie — 16,16 %, et `Σ pond·Tchx_final` 16,16 %
  également. Aucun ne s'approche de 5,06 %, et aucun Cdimref plausible ne les
  y amène : il faudrait 1,1285, soit un GV de 23 858 W/K quand la référence
  publie elle-même 33 602.
- **La règle appliquée est celle du moteur de référence CSTB.** open3cl la
  documente — « le générateur prioritaire assure la totalité du besoin et son
  taux de charge est calculé avec sa seule Pn » — et la rattache aux autotests
  d'État APP5-0-4, 41 et 43. `getProrataGenerateur` y rend explicitement 0
  pour le secondaire d'une cascade avec priorité.
- Ce que notre modèle reproduit **exactement**, lui : les deux
  `rendement_generation`, 0,9746848 contre 0,974685 et 0,9855410 contre
  0,985541 ; et `cout_auxiliaire_generation_ch`, 235,69867951284 contre
  235,69867951283419. Un modèle qui tombe juste à six et onze décimales sur
  les grandeurs voisines n'est pas un modèle qu'une répartition 95/5 invalide.
- `conso_ch` de l'installation reste à 0,056 % de la référence — dans la
  tolérance stricte. Seules les deux lignes par générateur divergent.
- **Rouvrir si** un second éditeur publie une répartition non nulle sur le
  subordonné d'une cascade avec priorité, ou si un autotest CSTB accessible
  contredit open3cl.

### TASK-K65 — Réseaux de chaleur : colonne ACV confirmée, 39 références contre 1

- [ ] Owner: __ | Phase: K | Priorité: basse
- **Le chantier de cette tâche est livré** (commit « digitaliser les millésimes
  2020 et 2021 ») : millésimes 2020 et 2021 digitalisés, 69 valeurs tronquées
  du millésime 2024 reprises, et choix du millésime par les plages publiées au
  lieu de `max(2022, année − 1)`. Ce qui est archivé ici est le **verdict sur
  sa dernière question ouverte**, qui ne demande aucun code.
- **La colonne est l'ACV, et le corpus le dit à 39 contre 1.** Pour chaque
  référence qui déclare un unique réseau de chaleur, le rapport
  `emission_ges_5_usages / conso_5_usages` de sa ligne
  `sortie_par_energie[enum_type_energie_id=8]` donne le contenu CO2 que la
  référence a réellement appliqué. Sur les 45 fichiers concernés :

  | Colonne lue par la référence | Fichiers |
  |---|---:|
  | « Contenu CO2 ACV » — la nôtre | **39** |
  | « Contenu CO2 » direct | 1 |
  | ni l'une ni l'autre (sorties déjà imputées) | 5 |

  Les 39 concordent **à six décimales** avec le millésime que les plages
  désignent, ce qui valide du même coup la table et la règle de sélection.
- **`5703C` est donc tranché, et c'est la référence.** `2457E3582864U` (DPE du
  12 octobre 2024, `date_arrete_reseau_chaleur` = 2024-07-05, soit le millésime
  2023) implique 0,068. L'annexe donne pour ce réseau et ce millésime 0,068 en
  **contenu direct** et 0,092 en **contenu ACV** : la référence a lu la
  mauvaise colonne de la bonne ligne. Le cas est par ailleurs à 93,3 % avec
  10 écarts, dont 9 déjà imputés.
- **Pas de règle `ReferenceDefects` pour autant.** Elle demanderait de faire
  entrer le `TableRepository` et la sélection de millésime dans `ReferenceDefects`,
  qui ne travaille aujourd'hui que sur le fichier de référence, pour imputer
  4 balises d'un seul fichier. Rouvrir si un second éditeur apparaît dans la
  colonne directe — la mesure ci-dessus se refait en une passe.
- **Les cinq « ni l'une ni l'autre »** sont les `sortie_par_energie` à zéro ou
  à 0,00276 déjà couverts par `agregatIncompatibleAvecSaVentilation` ; rien à
  y chercher de plus.
- Source des contenus : `valeur_tables.xlsx` du dépôt de l'observatoire DPE de
  l'ADEME. **Légifrance n'est plus une voie praticable** : 403 sur les liens de
  téléchargement PDF, protection anti-robot sur les pages LODA. Ne pas y
  repasser du temps.

### TASK-K68 — Tranche tarifaire de l'électricité : les deux assiettes alternatives coûtent plus cher

- [ ] Owner: __ | Phase: K | Priorité: basse
- **Point de départ.** La famille « Coûts » est la plus basse du rapport hors
  confort d'été — 58,53 % en strict, 67,91 % au profil réglementaire. Deux
  balises y sont fausses sur presque tous les cas du corpus avec une seule
  occurrence chacune, signature d'une règle et non d'une dérive :
  `cout_eclairage` sur 125 cas et `cout_auxiliaire_ventilation` sur 117.
- **Le tri utile** est celui qui isole la tarification de l'amont : sur **117
  cas, `cout_eclairage` est hors tolérance alors que `conso_eclairage` ne
  l'est pas**, et 109 pour la ventilation. L'éclairage et les auxiliaires de
  ventilation étant toujours électriques, il ne reste que le prix du kWh.
- **Ce prix se laisse inverser.** Le barème donne `coût = fixe + prix × Cef` :
  de `cout_eclairage / conso_eclairage` on remonte au Cef que la référence a
  réellement retenu pour choisir sa tranche. Sur plusieurs cas, ce Cef vaut
  **exactement** sa ligne `sortie_par_energie[enum_type_energie_id=1]`, c'est-
  à-dire toute son électricité ; nous retenons une assiette plus petite, parce
  que `PrixEnergie` sépare l'électricité en deux abonnements, collectif et
  individuel. `2659E2047646C` est le cas d'école : nos deux paniers valent
  1 035,3 et 209,4 kWh, dont la somme 1 244,7 est au kWh près la ligne
  électricité de la référence — et nos consommations, elles, concordent.
- **Les deux assiettes alternatives ont été mesurées, et elles coûtent plus
  cher.** A/B isolé, 541 cas, 0 plantage, référence 13 603 écarts :

  | Variante | Écarts | `cout_eclairage` | `cout_aux_ventilation` |
  |---|---:|---:|---:|
  | Deux abonnements (en place) | 13 603 | 125 | 117 |
  | Électricité non scindée | 13 823 (**+220**) | 178 | 166 |
  | Tranche individuelle sur toute l'électricité | 14 083 (**+480**) | 178 | 166 |

  Les deux dégradent **aussi le sous-ensemble qu'elles visaient**, de 125 à
  178 : la direction est fausse, pas seulement le dosage. Le découpage par
  abonnement de TASK-K10 reste le meilleur que le corpus soutienne.
- **Ne pas rouvrir par l'aval.** Le reste de la famille suit ses
  consommations : `cout_ch` (401 occurrences) et `cout_ecs` (394) sont les
  ombres de `conso_ch` et `conso_ecs`, premières sources d'écart du rapport,
  et `cout_ch_depensier` comme `cout_ecs_depensier` sont déjà imputés à la
  référence pour 380 d'entre eux. Le travail utile est dans « Génération
  chauffage » et « Auxiliaires », pas dans les coûts.
- Condition de réouverture : un texte qui définisse l'assiette de la tranche
  autrement que « la consommation de l'ensemble de l'immeuble » pour le
  collectif et « celle de l'appartement seul » pour l'individuel — les deux
  phrases de l'annexe tarifaire que `PrixEnergie` cite déjà.

### TASK-K70 — `auxiliaire_distribution_fr` : 69 % des balises manquantes, et la méthode n'en donne pas la formule

- [ ] Owner: __ | Phase: K | Priorité: basse
- **Le constat.** Sur les 341 balises que le rapport compte manquantes,
  **236 sont les quatre déclinaisons d'une seule grandeur** —
  `conso_`, `cout_`, `emission_ges_` et `ep_conso_auxiliaire_distribution_fr`
  — sur 59 cas. Nous ne la produisons nulle part : le moteur ne la connaît
  que dans l'outillage de mesure.
- **La méthode la nomme sans la définir.** §15 p.97 écrit « Pour les
  installations de refroidissement, les consommations des auxiliaires de
  génération sont prises en compte dans le SEER (EER). Seules les
  consommations des auxiliaires de distribution sont donc à comptabiliser :
  `Caux_fr = Caux_dist_fr` ». Mais §15.2 ne compte que trois sous-sections —
  15.2.1 puissance des circulateurs, 15.2.2 chauffage, 15.2.3 ECS. **Il n'y a
  pas de §15.2.4 pour le refroidissement.** Aucune formule, aucune table.
- **Les références le confirment** : les 59 qui publient la balise publient
  **toutes les 59 la valeur 0,000**. Pas une seule valeur non nulle au
  corpus. Il n'y a rien à calculer.
- **La publier coûterait quatre fois plus qu'elle ne rapporte.** 59 des
  541 cas la portent ; l'émettre partout créerait **1 928 balises
  supplémentaires** (482 × 4) pour en combler 236. Et elle ne se conditionne
  pas : 52 des 59 qui la publient n'ont aucune installation de
  climatisation, tandis que 27 cas qui en ont une l'omettent.
- **C'est une convention d'éditeur, et la majoritaire est la nôtre.**

  | Moteur de la référence | Publie | Omet |
  |---|---:|---:|
  | BBS_Slama_* | 0 | 356 |
  | 3cl_tribu_2024.6.1.0 | 0 | 24 |
  | 3cl_tribu_1.4.25.1 | 8 | 96 |
  | 3cl_bbs_V* | 17 | 0 |
  | « inconnu » | 23 | 0 |
  | 3cl-2024.6.1.0 | 7 | 0 |

- Condition de réouverture : un texte réglementaire qui donne la formule de
  `Caux_dist_fr`, ou une référence qui en publie une valeur non nulle. Les
  deux se vérifient en une passe sur le corpus.

### TASK-K73 — Le plafond de 400 kW sur Pn : lecture littérale de §15.1 mesurée et écartée

- [ ] Owner: __ | Phase: K | Priorité: basse
- **La question.** `ChaudiereDefautCalculator` plafonne `pn` à 400 kW pour les
  chaudières gaz et fioul. Or §15.1 p.97 énonce ce plafond **à l'intérieur de
  l'équation des auxiliaires** : « Détermination des puissances par défaut des
  auxiliaires : Paux_g = G + H × Pn (W). **Dans cette équation** : pour les
  chaudières gaz ou fioul : si Pn > 400 kW alors Pn = 400 kW », suivi des
  mêmes bornes à 300 kW pour les générateurs d'air chaud et 70 kW pour les
  chaudières bois. §13.2.2.4, qui définit Pn depuis Pdim, n'énonce **aucun**
  plafond : au-delà de 40 kW, `Pn = (partie entière(Pdim / 5) + 1) × 5`, sans
  borne supérieure. La lecture littérale veut donc un `pn` non plafonné, le
  plafond ne valant que pour Paux_g — que `AuxGenerationCalculator` applique
  déjà de son côté, dans sa table G/H, pour les trois types.
- **Mesurée, et écartée sans appel.** A/B isolé, 541 cas, 0 plantage :
  retirer le plafond de `ChaudiereDefautCalculator` coûte **+1 019 écarts**
  (13 613 → 14 632), **30 cas dégradés et aucun amélioré**. 2678E0469617K
  passe de 9 à 79 écarts, 2675E0124737R de 9 à 77, 2675E0066612C de 9 à 74.
  Le plafond se propage à `rpn`, `rpint` et `qp0`, qui se lisent sur des
  tranches de Pn, et toute la chaîne suit. Les références plafonnent donc bien
  `pn` lui-même. Ne pas y revenir sans un texte qui contredise §15.1.
- **Ce que l'A/B a révélé en passant, et qui reste ouvert :** les 21 écarts de
  `pn` à 370 000 W ne venaient pas du plafond. Ils appartiennent tous au même
  immeuble (série 2400E03338xx) et, **plafond retiré**, nous produisons
  470 000 W quand la référence en publie 370 000. C'est `Pdim` qui est trop
  grand de 27 %, pas sa troncature — à chercher dans `Pch`, donc dans le GV
  ou dans l'échelle retenue pour cet immeuble. Voir TASK-K74.

### TASK-K75 — Dénominateur de `ubat` : les trois variantes du filtre, mesurées et écartées

- [ ] Owner: __ | Phase: K | Priorité: basse
- **La piste.** `qualite_isol_enveloppe` est faux sur 43 cas, et dans **41**
  d'entre eux `ubat` l'est aussi : la classe n'est pas le problème, le seuil
  non plus. Sur 82 `ubat` hors tolérance (542 balises au corpus, soit 85 % de
  conformité), **67 sont au-dessus** de la référence, médiane 1,33, jusqu'à
  ×8. Et sur **54** d'entre eux la `deperdition_enveloppe` — le numérateur —
  est juste. C'est donc le dénominateur, la somme des surfaces.
- **Un cas le dit au centième.** `2600E0019751W`, appartement de 29,38 m² :
  numérateur 11,185 W/K, `ubat` publié 0,1470, donc un dénominateur de
  **76,09 m²**. La somme de **toutes** ses parois sans aucun filtre vaut
  76,06 m² — 11,05 de murs, 29,38 de plancher bas, 29,38 de plancher haut,
  4,45 de baies et 1,80 de porte. Le nôtre écarte les parois à `b = 0` et
  l'adjacence 22, et tombe à 17,3.
- **Et pourtant la règle est mauvaise sur le corpus.** A/B isolé, 541 cas,
  0 plantage, référence 13 613 écarts :

  | Dénominateur | Écarts | `ubat` hors tolérance |
  |---|---:|---:|
  | filtres actuels (`b > 0`, hors adjacence 22) | 13 613 | **82** |
  | sans le filtre `b > 0` | 13 627 | — |
  | sans l'exclusion de l'adjacence 22 | 13 667 | — |
  | sans aucun des deux, parois murs/PB/baies/portes | 13 676 | — |
  | **toutes les parois, plancher haut compris** | 13 665 | **122** |

  La dernière ligne est celle qui reproduit `2600E0019751W` exactement, et
  c'est celle qui dégrade le plus `ubat` : de 82 à **122** hors tolérance.
  Les quatre combinaisons sont donc moins bonnes que celle en place.
- **Ce n'est pas non plus une convention d'éditeur.** Le taux d'échec de
  `ubat` se répartit sur tous les moteurs — 9 % chez BBS_Slama (33 cas sur
  352), 23 % chez 3cl_tribu_1.4.25.1, 71 % chez 3cl-2024.6.1.0 — sans qu'un
  groupe se détache.
- Condition de réouverture : un texte qui définisse la surface déperditive de
  `ubat`. §3 ne la donne pas, et nos seuils de classe viennent eux aussi
  d'open3cl. Sans source, ne pas rejouer ces cinq mesures.

**La rubrique de plancher haut est tranchée aussi, et dans l'autre sens.**
Le schéma prévoit trois rubriques — comble aménagé, toit terrasse, comble
perdu — et nous n'en renseignons qu'une, celle du sous-type qui porte la plus
grande surface déperditive. Le corpus le confirme : sur 542 références, **501
en publient exactement une**, 32 aucune et 9 plusieurs.

Les 50 écarts structurels de ces rubriques se décomposent ainsi : 26 cas où
nous publions une rubrique que la référence omet (et leurs planchers hauts
sont bien déperditifs, `b` valant 1 ou 0,95 — ce n'est donc pas une règle de
`b`), 14 où nous n'en publions aucune, et **4 seulement où nous nous trompons
de sous-type**.

Publier une rubrique **par sous-type présent** a été mesuré : les balises
supplémentaires passent de 30 à **132** pour n'économiser que 10 manquantes,
soit +92 au total. Sur `2401E0166251L`, qui publie à la fois `comble_perdu`
et `comble_amenage` pour ses deux planchers hauts de 23,8 et 22,4 m², nous
retenons le plus grand et c'est la bonne règle 501 fois sur 542.

### TASK-K77 — Confort d'été : la règle en place est la meilleure, et le corpus ne s'accorde pas avec lui-même

- [ ] Owner: __ | Phase: K | Priorité: basse
- **L'indicateur n'est pas dans la méthode.** §10 ne définit ni
  `enum_indicateur_confort_ete_id` ni ses cinq drapeaux : c'est un indicateur
  du rapport, pas du calcul, et notre implémentation vient d'open3cl. Il n'y a
  donc pas de texte à opposer au corpus.
- **Les 77 écarts de l'indicateur sont l'ombre de deux drapeaux.** La règle est
  « insuffisant si `protection_solaire_exterieure` = 0 **ou**
  `isolation_toiture` = 0 » : 54 cas attendent « moyen » et 18 « bon » là où
  nous disons « insuffisant », et ces 72 suivent les 27 écarts de protection
  solaire et les 18 d'isolation de toiture. Corriger l'indicateur sans
  corriger les drapeaux n'aurait aucun sens.
- **Notre règle de protection solaire est la meilleure des trois testées.**
  Elle met 0 dès qu'une baie non-nord déclare « absence de fermeture ». Sur les
  202 références qui publient le drapeau :

  | Règle | Justes | Faux |
  |---|---:|---:|
  | une baie non-nord sans fermeture suffit (en place) | **171** | 31 |
  | seuil à 25 % de la surface non-nord sans fermeture | 165 | 37 |

  La répartition par part de surface explique pourquoi : la tranche 0-25 %
  est **franchement ambiguë** — 29 références y publient 0 et 23 publient 1 —
  alors que les tranches supérieures sont nettes (22 contre 1, 12 contre 1,
  19 contre 2). Aucun seuil ne sépare cette tranche.
- **Le masque proche n'est pas le discriminant.** L'hypothèse qu'un masque
  tiendrait lieu de protection a été vérifiée : sur les 109 fichiers qui ont
  au moins une baie non-nord sans fermeture, **aucun** n'a toutes ces baies
  masquées. L'information n'existe pas dans les entrées.
- **Ce n'est pas non plus une convention d'éditeur.** Le taux de divergence se
  répartit sur tous les moteurs : 22 % chez 3cl_tribu_1.4.25.1 (9 cas sur 40),
  21 % chez BBS_Slama_2025.11.1.0 (8 sur 39), 33 % chez « inconnu », 38 % chez
  3cl_tribu_2024.6.1.0. Les références ne s'accordent pas entre elles.
- Condition de réouverture : le texte de l'arrêté qui définit l'indicateur de
  confort d'été et ses drapeaux. Sans lui, les trois mesures ci-dessus sont ce
  qu'on peut savoir, et la règle en place est celle qui colle le mieux.

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

### TASK-K61 — §17.2.2 : la clé de répartition du chauffage n'est pas dans le fichier

- Longtemps numérotée K41, en collision avec « Qualifier Pecs dans les
  exports sans clé mixte commune », qui garde ce numéro dans `TASKS.md`.
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

### TASK-K60 — Échelle de `apport_et_besoin` : même éditeur minoritaire

- [ ] Owner: __ | Phase: K | Priorité: basse
- Six références publient `sortie/apport_et_besoin/besoin_ch` **plus petit**
  que le besoin de leur unique installation de chauffage, dans un rapport de
  2,9 à 6,6 : `2691E0123392O`, `2691E0752812A`, `2691E0507798K`,
  `2692E1146601U`, `2692E1146857Q`, `2693E0440000W`. Toutes sont en mode 10
  avec `enum_calcul_echantillonnage_id` = 2.
- **Le corpus tranche contre elles.** Sur les 83 références en modes 10 à 13,
  **77 publient la somme des besoins de leurs installations**, ce que nous
  faisons ; les 6 restantes sont ces fichiers-là. Parmi les seuls fichiers qui
  déclarent un échantillonnage, 72 sur 78 nous suivent : l'hypothèse « avec
  échantillonnage, la sortie porte l'appartement » est réfutée.
- **C'est une convention d'éditeur, la même que TASK-K58.** Les six viennent
  du logiciel « 4.1.1 », et les six fichiers de ce logiciel en modes 10 à 13
  divergent ; les 122 fichiers LICIEL de ces modes nous suivent tous.
- Sur `2691E0123392O` le GV (1 184,17) et le besoin de l'installation
  (37 116,08 contre 37 116,66) concordent au chiffre près : seule l'échelle de
  la sortie diffère. Ne rien changer sans un texte ; rouvrir si un second
  éditeur adopte la même lecture.

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

## Tâches closes par suppression, dans cette campagne

Leur contenu est dans l'historique Git ; le commit nommé porte la suppression
du bloc et, le plus souvent, la correction qui l'a justifiée.

| Tâche | Commit | Motif |
|---|---|---|
| TASK-K32 — 2459E4183923N : conso de chauffage et pont thermique | `4ec0cfd` | défaut de référence généralisé au plancher bas isolé publié à son coefficient nu |
| TASK-K59 — 41 réseaux à contenu CO2 nul au millésime 2024 | `2ddba14` | 41 valeurs restaurées depuis l'arrêté du 11 avril 2025 |
| TASK-K51 — doublage d'un mur isolé et dépensier des auxiliaires | `c4b3289` | les deux hypothèses reconfirmées écartées sur 500 cas |
| TASK-K53 — 2633E1486544G : besoin d'ECS couvrant l'immeuble | `1513538`, `6a9134d` | réduit au seul désaccord entre deux références à déclaration égale |
| TASK-K46 — colonne et millésimes manquants des réseaux | `c5be07a` | colonne ACV confirmée sur douze cas neufs |
| TASK-K52 — un générateur décrit pour N logements | `2187ce9`, `89fe7dc` | la requalification des sérialisations exhaustives est retirée, §17.1.4.2 s'applique seul |
| TASK-K42 — échelles des exports exhaustifs chauffage et ECS | `fc41e9b` | les trois fichiers sont à 96 %, le reste est imputé à la référence |
| TASK-H04 — besoin et consommation ECS des fichiers collectifs | `6a9134d` | population réduite à son seul cas, fusionné dans TASK-K53 |
| TASK-K26 — Shmoy_système est une moyenne, pas une somme | `f4a5bcf` | le facteur unitaire est livré ; seul le découpage des visites reste ouvert |
| TASK-K44 — besoin de froid du DPE 2682E0504521A | `4a194f0` | rentré dans la tolérance avec les degrés-heures lus sur Textmoy_clim |
