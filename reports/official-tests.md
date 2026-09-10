# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-10T19:38:00+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `cd45fe5` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 360
Exécutés                 : 360
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 360

Valeurs comparées        : 114 947
Exactes                  : 86 686
Dans tolérance           : 14 958
Hors tolérance           : 11 309
Balises manquantes       : 107
Balises supplémentaires  : 1 846
Écarts non numériques    : 41

Conformité               : 88,43 %
```

Sur ces écarts, **3 845 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **91,77 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 44847 | 43152 | 1316 | 252 | 7 | 120 | 99,15 % |
| Ventilation | 2466 | 1598 | 860 | 8 | 0 | 0 | 99,68 % |
| Apports | 11485 | 9909 | 985 | 310 | 0 | 281 | 94,85 % |
| Besoin chauffage | 1766 | 14 | 1466 | 282 | 4 | 0 | 83,81 % |
| Besoin ECS | 3412 | 3212 | 187 | 13 | 0 | 0 | 99,62 % |
| Génération chauffage | 7216 | 2887 | 2485 | 1794 | 26 | 24 | 74,45 % |
| Génération ECS | 7562 | 4776 | 992 | 1765 | 14 | 15 | 76,28 % |
| Auxiliaires | 12240 | 7786 | 2289 | 2165 | 0 | 0 | 82,31 % |
| Froid | 4460 | 4128 | 27 | 249 | 56 | 0 | 93,16 % |
| PV | 2526 | 2517 | 0 | 9 | 0 | 0 | 99,64 % |
| Sorties énergie finale | 2362 | 1231 | 634 | 497 | 0 | 0 | 78,96 % |
| Sorties énergie primaire | 2880 | 1332 | 829 | 696 | 0 | 0 | 75,03 % |
| GES | 4803 | 2273 | 1397 | 1115 | 0 | 0 | 76,41 % |
| Coûts | 4083 | 852 | 1315 | 1916 | 0 | 0 | 53,07 % |
| Confort d'été | 2074 | 573 | 0 | 95 | 0 | 1406 | 27,63 % |
| Autre | 765 | 446 | 176 | 143 | 0 | 0 | 81,31 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61227 | 10762 | 5604 | 22 | 90,71 % |
| appartement_issu_immeuble | 45 | 15543 | 10489 | 1539 | 3191 | 34 | 77,16 % |
| maison_individuelle | 39 | 10872 | 8108 | 1304 | 1380 | 23 | 86,26 % |
| appartement_individuel | 40 | 9406 | 6862 | 1353 | 1134 | 28 | 86,97 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 282 | 87373 | 66573 | 12455 | 6697 | 67 | 90,16 % |
| pre_2026 | 78 | 27574 | 20113 | 2503 | 4612 | 40 | 81,79 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 271 | 84561 | 64932 | 11908 | 6179 | 15 | 90,58 % |
| 3cl_tribu_1.4.25.1 | 64 | 23040 | 16818 | 2036 | 3897 | 24 | 81,60 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2711 | 2066 | 265 | 352 | 0 | 85,76 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| inconnu | 3 | 799 | 538 | 197 | 48 | 15 | 91,65 % |
| 3cl-2024.6.1.0 | 3 | 644 | 441 | 81 | 106 | 12 | 80,68 % |
| BBS_Slama_2024.6.1.0 | 2 | 640 | 457 | 87 | 96 | 0 | 84,74 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 185 | 33 | 49 | 4 | 77,86 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 146 | 1 | 112 | 0 | 56,32 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 142 | 58 | 10 | 0 | 93,46 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 758 | 76 | 1 098,9 % | hors-tol×758 |
| 2 | Génération chauffage | `conso_ch` | 706 | 141 | 1 243,1 % | hors-tol×700 manquante×6 |
| 3 | Génération ECS | `conso_ecs_depensier` | 687 | 74 | 845,3 % | hors-tol×687 |
| 4 | Génération chauffage | `conso_ch_depensier` | 580 | 143 | 1 243,1 % | hors-tol×574 manquante×6 |
| 5 | Coûts | `cout_5_usages` | 489 | 222 | 799,5 % | hors-tol×489 |
| 6 | GES | `emission_ges_5_usages` | 371 | 158 | 11 300,5 % | hors-tol×371 |
| 7 | Sorties énergie finale | `conso_5_usages` | 363 | 152 | 1 219,0 % | hors-tol×363 |
| 8 | Apports | `inertie_lourde` | 359 | 359 | 100,0 % | hors-tol×78 suppl×281 |
| 9 | Coûts | `cout_ecs_depensier` | 349 | 349 | 204,2 % | hors-tol×349 |
| 10 | Coûts | `cout_ch_depensier` | 348 | 348 | 268,0 % | hors-tol×348 |
| 11 | Coûts | `cout_ch` | 342 | 167 | 533,1 % | hors-tol×342 |
| 12 | Confort d'été | `isolation_toiture` | 318 | 318 | 100,0 % | hors-tol×36 suppl×282 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 315 | 315 | 200,0 % | hors-tol×34 suppl×281 |
| 14 | GES | `emission_ges_ch` | 306 | 148 | 810,8 % | hors-tol×306 |
| 15 | Coûts | `cout_ecs` | 296 | 164 | 559,4 % | hors-tol×296 |
| 16 | Confort d'été | `protection_solaire_exterieure` | 294 | 294 | 100,0 % | hors-tol×13 suppl×281 |
| 17 | Confort d'été | `aspect_traversant` | 293 | 293 | — | hors-tol×12 suppl×281 |
| 18 | Confort d'été | `brasseur_air` | 281 | 281 | — | suppl×281 |
| 19 | Génération ECS | `rendement_stockage` | 280 | 46 | 44,3 % | hors-tol×269 manquante×10 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 209 | 209 | 346,7 % | hors-tol×209 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 158 | 158 | 225,0 % | hors-tol×158 |
| 22 | Génération chauffage | `rendement_generation` | 152 | 121 | 39,1 % | hors-tol×127 suppl×22 manquante×3 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 149 | 149 | 199,4 % | hors-tol×149 |
| 24 | GES | `emission_ges_ch_depensier` | 147 | 147 | 154,0 % | hors-tol×147 |
| 25 | Besoin chauffage | `besoin_ch` | 143 | 53 | 155,6 % | hors-tol×141 manquante×2 |
| 26 | Besoin chauffage | `besoin_ch_depensier` | 143 | 56 | 1 009,5 % | hors-tol×141 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 142 | 142 | 199,4 % | hors-tol×142 |
| 28 | Sorties énergie primaire | `ep_conso_ch` | 140 | 140 | 142,9 % | hors-tol×140 |
| 29 | Sorties énergie primaire | `ep_conso_ch_depensier` | 140 | 140 | 142,9 % | hors-tol×140 |
| 30 | Sorties énergie finale | `conso_5_usages_m2` | 134 | 134 | 143,8 % | hors-tol×134 |
| 31 | GES | `emission_ges_ecs` | 130 | 65 | 616,5 % | hors-tol×130 |
| 32 | Auxiliaires | `cout_total_auxiliaire` | 125 | 125 | 3 213,8 % | hors-tol×125 |
| 33 | Auxiliaires | `cout_auxiliaire_generation_ch` | 109 | 109 | 100,0 % | hors-tol×109 |
| 34 | Auxiliaires | `conso_auxiliaire_generation_ch` | 96 | 96 | 100,0 % | hors-tol×96 |
| 35 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 96 | 96 | 100,0 % | hors-tol×96 |
| 36 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 96 | 96 | 100,0 % | hors-tol×96 |
| 37 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 96 | 96 | 100,0 % | hors-tol×96 |
| 38 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 96 | 96 | 100,0 % | hors-tol×96 |
| 39 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 96 | 96 | 100,0 % | hors-tol×96 |
| 40 | Génération chauffage | `qp0` | 95 | 83 | 205 028,2 % | hors-tol×94 manquante×1 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 270 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 270 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 270 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 270 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 270 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 270 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 11 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 11 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 18 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 300 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 300 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 143 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 182 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `conso_totale_auxiliaire` | 8 | la référence publie un total auxiliaire de 103.1 kWh, incompatible avec la somme de ses postes (316.8 kWh) |
| `besoin_ch_depensier` | 8 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_ecs` | 469 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 469 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 272 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 31 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 3 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

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
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 113 | 56,54 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 100 | 78,59 % |
| `2400E0636882P.xml` | immeuble_collectif | pre_2026 | 415 | 100 | 75,90 % |

## Cas totalement conformes

_Aucun._
