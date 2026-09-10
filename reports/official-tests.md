# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-10T19:20:43+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `eaeb72e`_

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

Valeurs comparées        : 114 970
Exactes                  : 86 659
Dans tolérance           : 14 934
Hors tolérance           : 11 384
Balises manquantes       : 107
Balises supplémentaires  : 1 846
Écarts non numériques    : 40

Conformité               : 88,36 %
```

Sur ces écarts, **3 844 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **91,71 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 44880 | 43163 | 1320 | 269 | 7 | 121 | 99,12 % |
| Ventilation | 2467 | 1595 | 864 | 8 | 0 | 0 | 99,68 % |
| Apports | 11489 | 9909 | 986 | 313 | 0 | 281 | 94,83 % |
| Besoin chauffage | 1766 | 14 | 1462 | 286 | 4 | 0 | 83,58 % |
| Besoin ECS | 3412 | 3210 | 187 | 15 | 0 | 0 | 99,56 % |
| Génération chauffage | 7210 | 2886 | 2485 | 1790 | 26 | 23 | 74,49 % |
| Génération ECS | 7561 | 4768 | 991 | 1773 | 14 | 15 | 76,17 % |
| Auxiliaires | 12240 | 7774 | 2286 | 2180 | 0 | 0 | 82,19 % |
| Froid | 4460 | 4128 | 28 | 248 | 56 | 0 | 93,18 % |
| PV | 2526 | 2517 | 0 | 9 | 0 | 0 | 99,64 % |
| Sorties énergie finale | 2360 | 1229 | 631 | 500 | 0 | 0 | 78,81 % |
| Sorties énergie primaire | 2880 | 1329 | 827 | 702 | 0 | 0 | 74,86 % |
| GES | 4800 | 2267 | 1394 | 1121 | 0 | 0 | 76,27 % |
| Coûts | 4080 | 850 | 1297 | 1933 | 0 | 0 | 52,62 % |
| Confort d'été | 2074 | 574 | 0 | 94 | 0 | 1406 | 27,68 % |
| Autre | 765 | 446 | 176 | 143 | 0 | 0 | 81,31 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61228 | 10763 | 5602 | 22 | 90,71 % |
| appartement_issu_immeuble | 45 | 15543 | 10489 | 1539 | 3191 | 34 | 77,16 % |
| maison_individuelle | 39 | 10895 | 8105 | 1309 | 1402 | 23 | 86,10 % |
| appartement_individuel | 40 | 9406 | 6837 | 1323 | 1189 | 28 | 86,39 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 282 | 87396 | 66553 | 12431 | 6765 | 67 | 90,08 % |
| pre_2026 | 78 | 27574 | 20106 | 2503 | 4619 | 40 | 81,76 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 270 | 84320 | 64747 | 11860 | 6174 | 15 | 90,56 % |
| 3cl_tribu_1.4.25.1 | 64 | 23040 | 16811 | 2036 | 3904 | 24 | 81,57 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2711 | 2066 | 265 | 352 | 0 | 85,76 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| inconnu | 3 | 799 | 538 | 197 | 48 | 15 | 91,65 % |
| 3cl-2024.6.1.0 | 3 | 644 | 441 | 81 | 106 | 12 | 80,68 % |
| BBS_Slama_2024.6.1.0 | 2 | 640 | 457 | 87 | 96 | 0 | 84,74 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 185 | 33 | 49 | 4 | 77,86 % |
| Moteur DPE: DPE_2025.11.1.0 | 1 | 264 | 165 | 24 | 73 | 0 | 71,32 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 146 | 1 | 112 | 0 | 56,32 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 142 | 58 | 10 | 0 | 93,46 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 762 | 77 | 1 098,9 % | hors-tol×762 |
| 2 | Génération chauffage | `conso_ch` | 706 | 141 | 1 243,1 % | hors-tol×700 manquante×6 |
| 3 | Génération ECS | `conso_ecs_depensier` | 690 | 75 | 845,3 % | hors-tol×690 |
| 4 | Génération chauffage | `conso_ch_depensier` | 580 | 143 | 1 243,1 % | hors-tol×574 manquante×6 |
| 5 | Coûts | `cout_5_usages` | 492 | 224 | 799,5 % | hors-tol×492 |
| 6 | GES | `emission_ges_5_usages` | 374 | 160 | 11 300,5 % | hors-tol×374 |
| 7 | Sorties énergie finale | `conso_5_usages` | 366 | 154 | 1 219,0 % | hors-tol×366 |
| 8 | Apports | `inertie_lourde` | 359 | 359 | 100,0 % | hors-tol×78 suppl×281 |
| 9 | Coûts | `cout_ecs_depensier` | 349 | 349 | 204,2 % | hors-tol×349 |
| 10 | Coûts | `cout_ch_depensier` | 348 | 348 | 268,0 % | hors-tol×348 |
| 11 | Coûts | `cout_ch` | 348 | 170 | 533,1 % | hors-tol×348 |
| 12 | Confort d'été | `isolation_toiture` | 317 | 317 | 100,0 % | hors-tol×35 suppl×282 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 315 | 315 | 200,0 % | hors-tol×34 suppl×281 |
| 14 | GES | `emission_ges_ch` | 306 | 148 | 810,8 % | hors-tol×306 |
| 15 | Coûts | `cout_ecs` | 301 | 167 | 559,4 % | hors-tol×301 |
| 16 | Confort d'été | `protection_solaire_exterieure` | 294 | 294 | 100,0 % | hors-tol×13 suppl×281 |
| 17 | Confort d'été | `aspect_traversant` | 293 | 293 | — | hors-tol×12 suppl×281 |
| 18 | Confort d'été | `brasseur_air` | 281 | 281 | — | suppl×281 |
| 19 | Génération ECS | `rendement_stockage` | 281 | 47 | 44,3 % | hors-tol×270 manquante×10 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 208 | 208 | 346,7 % | hors-tol×208 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 158 | 158 | 225,0 % | hors-tol×158 |
| 22 | Génération chauffage | `rendement_generation` | 152 | 121 | 39,1 % | hors-tol×126 suppl×23 manquante×3 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 151 | 151 | 199,4 % | hors-tol×151 |
| 24 | GES | `emission_ges_ch_depensier` | 147 | 147 | 154,0 % | hors-tol×147 |
| 25 | Besoin chauffage | `besoin_ch` | 145 | 54 | 155,6 % | hors-tol×143 manquante×2 |
| 26 | Besoin chauffage | `besoin_ch_depensier` | 145 | 57 | 1 009,5 % | hors-tol×143 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 144 | 144 | 199,4 % | hors-tol×144 |
| 28 | Sorties énergie primaire | `ep_conso_ch` | 140 | 140 | 142,9 % | hors-tol×140 |
| 29 | Sorties énergie primaire | `ep_conso_ch_depensier` | 140 | 140 | 142,9 % | hors-tol×140 |
| 30 | Sorties énergie finale | `conso_5_usages_m2` | 134 | 134 | 143,8 % | hors-tol×134 |
| 31 | GES | `emission_ges_ecs` | 132 | 66 | 616,5 % | hors-tol×132 |
| 32 | Auxiliaires | `cout_total_auxiliaire` | 127 | 127 | 3 213,8 % | hors-tol×127 |
| 33 | Auxiliaires | `cout_auxiliaire_generation_ch` | 108 | 108 | 100,0 % | hors-tol×108 |
| 34 | Coûts | `cout_eclairage` | 95 | 95 | 62,8 % | hors-tol×95 |
| 35 | Auxiliaires | `conso_auxiliaire_generation_ch` | 95 | 95 | 100,0 % | hors-tol×95 |
| 36 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 95 | 95 | 100,0 % | hors-tol×95 |
| 37 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 95 | 95 | 100,0 % | hors-tol×95 |
| 38 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 95 | 95 | 100,0 % | hors-tol×95 |
| 39 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 95 | 95 | 100,0 % | hors-tol×95 |
| 40 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 95 | 95 | 100,0 % | hors-tol×95 |

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
| `rendement_generation` | 30 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
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
