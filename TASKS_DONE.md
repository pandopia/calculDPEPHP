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
