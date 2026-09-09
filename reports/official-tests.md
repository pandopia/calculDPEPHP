# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-09T08:43:36+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `9622e85` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 357
Exécutés                 : 357
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 357

Valeurs comparées        : 114 188
Exactes                  : 86 154
Dans tolérance           : 14 901
Hors tolérance           : 11 150
Balises manquantes       : 107
Balises supplémentaires  : 1 837
Écarts non numériques    : 39

Conformité               : 88,50 %
```

Sur ces écarts, **3 836 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **91,86 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 44585 | 42908 | 1310 | 240 | 7 | 120 | 99,18 % |
| Ventilation | 2446 | 1582 | 856 | 8 | 0 | 0 | 99,67 % |
| Apports | 11415 | 9866 | 979 | 290 | 0 | 280 | 95,01 % |
| Besoin chauffage | 1754 | 14 | 1462 | 274 | 4 | 0 | 84,15 % |
| Besoin ECS | 3391 | 3192 | 187 | 12 | 0 | 0 | 99,65 % |
| Génération chauffage | 7169 | 2872 | 2485 | 1764 | 26 | 22 | 74,72 % |
| Génération ECS | 7528 | 4759 | 991 | 1750 | 14 | 14 | 76,38 % |
| Auxiliaires | 12138 | 7708 | 2280 | 2150 | 0 | 0 | 82,29 % |
| Froid | 4424 | 4092 | 28 | 248 | 56 | 0 | 93,13 % |
| PV | 2505 | 2496 | 0 | 9 | 0 | 0 | 99,64 % |
| Sorties énergie finale | 2343 | 1222 | 630 | 491 | 0 | 0 | 79,04 % |
| Sorties énergie primaire | 2856 | 1324 | 827 | 684 | 0 | 0 | 75,32 % |
| GES | 4764 | 2259 | 1393 | 1094 | 0 | 0 | 76,66 % |
| Coûts | 4050 | 848 | 1297 | 1905 | 0 | 0 | 52,96 % |
| Confort d'été | 2058 | 566 | 0 | 91 | 0 | 1401 | 27,50 % |
| Autre | 762 | 446 | 176 | 140 | 0 | 0 | 81,63 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61227 | 10764 | 5602 | 22 | 90,71 % |
| appartement_issu_immeuble | 44 | 15285 | 10296 | 1530 | 3142 | 34 | 77,15 % |
| maison_individuelle | 38 | 10631 | 7940 | 1285 | 1329 | 23 | 86,47 % |
| appartement_individuel | 39 | 9146 | 6691 | 1322 | 1077 | 28 | 87,24 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 280 | 86874 | 66195 | 12398 | 6643 | 67 | 90,18 % |
| pre_2026 | 77 | 27314 | 19959 | 2503 | 4507 | 40 | 82,01 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 269 | 84062 | 64554 | 11851 | 6125 | 15 | 90,60 % |
| 3cl_tribu_1.4.25.1 | 64 | 23040 | 16811 | 2036 | 3904 | 24 | 81,57 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2711 | 2066 | 265 | 352 | 0 | 85,76 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| inconnu | 3 | 799 | 538 | 197 | 48 | 15 | 91,65 % |
| 3cl-2024.6.1.0 | 3 | 644 | 441 | 81 | 106 | 12 | 80,68 % |
| BBS_Slama_2024.6.1.0 | 2 | 640 | 457 | 87 | 96 | 0 | 84,74 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 184 | 34 | 49 | 4 | 77,86 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 142 | 58 | 10 | 0 | 93,46 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 750 | 74 | 1 098,9 % | hors-tol×750 |
| 2 | Génération chauffage | `conso_ch` | 694 | 138 | 1 243,1 % | hors-tol×688 manquante×6 |
| 3 | Génération ECS | `conso_ecs_depensier` | 681 | 72 | 845,3 % | hors-tol×681 |
| 4 | Génération chauffage | `conso_ch_depensier` | 571 | 140 | 1 243,1 % | hors-tol×565 manquante×6 |
| 5 | Coûts | `cout_5_usages` | 485 | 221 | 799,5 % | hors-tol×485 |
| 6 | GES | `emission_ges_5_usages` | 367 | 157 | 1 189,8 % | hors-tol×367 |
| 7 | Sorties énergie finale | `conso_5_usages` | 359 | 151 | 1 219,0 % | hors-tol×359 |
| 8 | Apports | `inertie_lourde` | 356 | 356 | 100,0 % | hors-tol×76 suppl×280 |
| 9 | Coûts | `cout_ecs_depensier` | 346 | 346 | 204,2 % | hors-tol×346 |
| 10 | Coûts | `cout_ch_depensier` | 345 | 345 | 268,0 % | hors-tol×345 |
| 11 | Coûts | `cout_ch` | 342 | 167 | 533,1 % | hors-tol×342 |
| 12 | Confort d'été | `isolation_toiture` | 315 | 315 | 100,0 % | hors-tol×34 suppl×281 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 313 | 313 | 200,0 % | hors-tol×33 suppl×280 |
| 14 | GES | `emission_ges_ch` | 300 | 145 | 810,8 % | hors-tol×300 |
| 15 | Coûts | `cout_ecs` | 295 | 164 | 559,4 % | hors-tol×295 |
| 16 | Confort d'été | `aspect_traversant` | 292 | 292 | — | hors-tol×12 suppl×280 |
| 17 | Confort d'été | `protection_solaire_exterieure` | 292 | 292 | 100,0 % | hors-tol×12 suppl×280 |
| 18 | Confort d'été | `brasseur_air` | 280 | 280 | — | suppl×280 |
| 19 | Génération ECS | `rendement_stockage` | 279 | 45 | 44,3 % | hors-tol×268 manquante×10 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 207 | 207 | 346,7 % | hors-tol×207 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 157 | 157 | 141,7 % | hors-tol×157 |
| 22 | Génération chauffage | `rendement_generation` | 150 | 119 | 26,9 % | hors-tol×125 suppl×22 manquante×3 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 148 | 148 | 199,4 % | hors-tol×148 |
| 24 | GES | `emission_ges_ch_depensier` | 144 | 144 | 154,0 % | hors-tol×144 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 141 | 141 | 199,4 % | hors-tol×141 |
| 26 | Besoin chauffage | `besoin_ch` | 139 | 51 | 155,6 % | hors-tol×137 manquante×2 |
| 27 | Besoin chauffage | `besoin_ch_depensier` | 139 | 54 | 1 009,4 % | hors-tol×137 manquante×2 |
| 28 | Sorties énergie primaire | `ep_conso_ch` | 137 | 137 | 142,9 % | hors-tol×137 |
| 29 | Sorties énergie primaire | `ep_conso_ch_depensier` | 137 | 137 | 142,9 % | hors-tol×137 |
| 30 | Sorties énergie finale | `conso_5_usages_m2` | 132 | 132 | 143,8 % | hors-tol×132 |
| 31 | GES | `emission_ges_ecs` | 126 | 63 | 616,5 % | hors-tol×126 |
| 32 | Auxiliaires | `cout_total_auxiliaire` | 124 | 124 | 3 213,8 % | hors-tol×124 |
| 33 | Auxiliaires | `cout_auxiliaire_generation_ch` | 107 | 107 | 100,0 % | hors-tol×107 |
| 34 | Auxiliaires | `conso_auxiliaire_generation_ch` | 94 | 94 | 100,0 % | hors-tol×94 |
| 35 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 94 | 94 | 100,0 % | hors-tol×94 |
| 36 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 94 | 94 | 100,0 % | hors-tol×94 |
| 37 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 94 | 94 | 100,0 % | hors-tol×94 |
| 38 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 94 | 94 | 100,0 % | hors-tol×94 |
| 39 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 94 | 94 | 100,0 % | hors-tol×94 |
| 40 | Génération chauffage | `qp0` | 93 | 81 | 205 028,2 % | hors-tol×92 manquante×1 |

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
| `besoin_ecs_depensier` | 11 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 11 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 18 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 300 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 300 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 142 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 182 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `conso_totale_auxiliaire` | 8 | la référence publie un total auxiliaire de 103.1 kWh, incompatible avec la somme de ses postes (316.8 kWh) |
| `besoin_ch_depensier` | 8 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_ecs` | 469 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 469 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 271 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
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
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 100 | 78,59 % |
| `2400E0636882P.xml` | immeuble_collectif | pre_2026 | 415 | 100 | 75,90 % |
| `2400E0669732B.xml` | immeuble_collectif | pre_2026 | 890 | 98 | 88,99 % |

## Cas totalement conformes

_Aucun._
