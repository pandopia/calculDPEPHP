# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-01T15:28:20+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `ecb51de`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 349
Exécutés                 : 349
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 349

Valeurs comparées        : 112 097
Exactes                  : 84 960
Dans tolérance           : 14 524
Hors tolérance           : 10 718
Balises manquantes       : 69
Balises supplémentaires  : 1 792
Écarts non numériques    : 34

Conformité               : 88,75 %
```

Sur ces écarts, **2 788 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **91,24 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 43910 | 42305 | 1245 | 239 | 7 | 114 | 99,18 % |
| Ventilation | 2391 | 1553 | 830 | 8 | 0 | 0 | 99,67 % |
| Apports | 11234 | 9743 | 943 | 274 | 0 | 274 | 95,12 % |
| Besoin chauffage | 1722 | 14 | 1436 | 268 | 4 | 0 | 84,20 % |
| Besoin ECS | 3335 | 3172 | 157 | 6 | 0 | 0 | 99,82 % |
| Génération chauffage | 7023 | 2813 | 2436 | 1727 | 25 | 22 | 74,74 % |
| Génération ECS | 7428 | 4717 | 972 | 1723 | 5 | 11 | 76,59 % |
| Auxiliaires | 11866 | 7631 | 2237 | 1998 | 0 | 0 | 83,16 % |
| Froid | 4300 | 3996 | 28 | 248 | 28 | 0 | 93,58 % |
| PV | 2449 | 2440 | 0 | 9 | 0 | 0 | 99,63 % |
| Sorties énergie finale | 2285 | 1199 | 613 | 473 | 0 | 0 | 79,30 % |
| Sorties énergie primaire | 2792 | 1315 | 814 | 645 | 0 | 0 | 76,25 % |
| GES | 4649 | 2232 | 1366 | 1035 | 0 | 0 | 77,39 % |
| Coûts | 3951 | 831 | 1273 | 1847 | 0 | 0 | 53,25 % |
| Confort d'été | 2018 | 558 | 0 | 89 | 0 | 1371 | 27,65 % |
| Autre | 744 | 441 | 174 | 129 | 0 | 0 | 82,66 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61227 | 10764 | 5602 | 22 | 90,71 % |
| appartement_issu_immeuble | 38 | 13740 | 9479 | 1318 | 2708 | 1 | 78,36 % |
| maison_individuelle | 36 | 10085 | 7563 | 1120 | 1331 | 18 | 85,79 % |
| appartement_individuel | 39 | 9146 | 6691 | 1322 | 1077 | 28 | 87,24 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 272 | 84783 | 65000 | 12039 | 6194 | 29 | 90,58 % |
| pre_2026 | 77 | 27314 | 19960 | 2485 | 4524 | 40 | 81,94 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 268 | 83793 | 64367 | 11770 | 6125 | 15 | 90,57 % |
| 3cl_tribu_1.4.25.1 | 64 | 23040 | 16811 | 2036 | 3904 | 24 | 81,57 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2711 | 2067 | 247 | 369 | 0 | 85,14 % |
| 3cl-2024.6.1.0 | 3 | 644 | 441 | 81 | 106 | 12 | 80,68 % |
| BBS_Slama_2024.6.1.0 | 2 | 640 | 457 | 87 | 96 | 0 | 84,74 % |
| inconnu | 2 | 522 | 347 | 131 | 33 | 10 | 91,22 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 184 | 34 | 49 | 4 | 77,86 % |
| 3cl_bbs_V2025.11.1.0 | 1 | 255 | 144 | 80 | 26 | 4 | 87,50 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 142 | 58 | 10 | 0 | 93,46 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 732 | 68 | 1 098,9 % | hors-tol×732 |
| 2 | Génération chauffage | `conso_ch` | 692 | 134 | 1 243,1 % | hors-tol×686 manquante×6 |
| 3 | Génération ECS | `conso_ecs_depensier` | 673 | 66 | 845,3 % | hors-tol×673 |
| 4 | Génération chauffage | `conso_ch_depensier` | 559 | 134 | 1 243,1 % | hors-tol×553 manquante×6 |
| 5 | Coûts | `cout_5_usages` | 469 | 213 | 559,4 % | hors-tol×469 |
| 6 | GES | `emission_ges_5_usages` | 353 | 150 | 616,5 % | hors-tol×353 |
| 7 | Apports | `inertie_lourde` | 348 | 348 | 100,0 % | hors-tol×74 suppl×274 |
| 8 | Sorties énergie finale | `conso_5_usages` | 346 | 145 | 616,5 % | hors-tol×346 |
| 9 | Coûts | `cout_ecs_depensier` | 339 | 339 | 204,2 % | hors-tol×339 |
| 10 | Coûts | `cout_ch_depensier` | 338 | 338 | 264,2 % | hors-tol×338 |
| 11 | Coûts | `cout_ch` | 335 | 160 | 533,1 % | hors-tol×335 |
| 12 | Confort d'été | `isolation_toiture` | 309 | 309 | 100,0 % | hors-tol×34 suppl×275 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 306 | 306 | 200,0 % | hors-tol×32 suppl×274 |
| 14 | GES | `emission_ges_ch` | 293 | 138 | 810,8 % | hors-tol×293 |
| 15 | Confort d'été | `protection_solaire_exterieure` | 286 | 286 | 100,0 % | hors-tol×12 suppl×274 |
| 16 | Confort d'été | `aspect_traversant` | 285 | 285 | — | hors-tol×11 suppl×274 |
| 17 | Coûts | `cout_ecs` | 280 | 156 | 559,4 % | hors-tol×280 |
| 18 | Confort d'été | `brasseur_air` | 274 | 274 | — | suppl×274 |
| 19 | Génération ECS | `rendement_stockage` | 272 | 38 | 44,3 % | hors-tol×267 manquante×4 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 200 | 200 | 346,7 % | hors-tol×200 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 151 | 151 | 141,7 % | hors-tol×151 |
| 22 | Génération chauffage | `rendement_generation` | 145 | 114 | 26,9 % | hors-tol×120 suppl×22 manquante×3 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 140 | 140 | 108,5 % | hors-tol×140 |
| 24 | Besoin chauffage | `besoin_ch` | 139 | 51 | 155,6 % | hors-tol×137 manquante×2 |
| 25 | GES | `emission_ges_ch_depensier` | 137 | 137 | 151,3 % | hors-tol×137 |
| 26 | Besoin chauffage | `besoin_ch_depensier` | 133 | 48 | 1 009,4 % | hors-tol×131 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 132 | 132 | 108,4 % | hors-tol×132 |
| 28 | Sorties énergie primaire | `ep_conso_ch` | 132 | 132 | 140,3 % | hors-tol×132 |
| 29 | Sorties énergie primaire | `ep_conso_ch_depensier` | 132 | 132 | 140,3 % | hors-tol×132 |
| 30 | Sorties énergie finale | `conso_5_usages_m2` | 127 | 127 | 143,8 % | hors-tol×127 |
| 31 | Auxiliaires | `cout_total_auxiliaire` | 117 | 117 | 248,6 % | hors-tol×117 |
| 32 | GES | `emission_ges_ecs` | 114 | 57 | 616,5 % | hors-tol×114 |
| 33 | Auxiliaires | `cout_auxiliaire_generation_ch` | 100 | 100 | 100,0 % | hors-tol×100 |
| 34 | Auxiliaires | `conso_auxiliaire_generation_ch` | 88 | 88 | 100,0 % | hors-tol×88 |
| 35 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 88 | 88 | 100,0 % | hors-tol×88 |
| 36 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 88 | 88 | 100,0 % | hors-tol×88 |
| 37 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 88 | 88 | 100,0 % | hors-tol×88 |
| 38 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 88 | 88 | 100,0 % | hors-tol×88 |
| 39 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 88 | 88 | 100,0 % | hors-tol×88 |
| 40 | Génération chauffage | `pn` | 88 | 74 | 1 328,6 % | hors-tol×85 manquante×3 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `cout_fr_depensier` | 18 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 300 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 300 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 141 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 182 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `rendement_stockage` | 233 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `zone_pre2026coefelec_diag1793608.xml` | appartement_issu_immeuble | pre_2026 | 722 | 153 | 78,81 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 848 | 145 | 82,90 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 145 | 75,38 % |
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 142 | 84,29 % |
| `2400E0333876N.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333878P.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333880R.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333885W.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333888Z.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333891C.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333895G.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333896H.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333898J.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333903O.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333904P.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333906R.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333883U.xml` | appartement_issu_immeuble | pre_2026 | 403 | 131 | 67,49 % |
| `2400E0333892D.xml` | appartement_issu_immeuble | pre_2026 | 403 | 131 | 67,49 % |
| `2400E0333901M.xml` | appartement_issu_immeuble | pre_2026 | 403 | 131 | 67,49 % |
| `2600E0035103I.xml` | immeuble_collectif | post_2026 | 642 | 120 | 81,31 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 100 | 78,59 % |
| `2400E0636882P.xml` | immeuble_collectif | pre_2026 | 415 | 100 | 75,90 % |
| `2400E0669732B.xml` | immeuble_collectif | pre_2026 | 890 | 98 | 88,99 % |

## Cas totalement conformes

_Aucun._
