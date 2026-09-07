# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-07T13:42:40+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `f8439b9` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 353
Exécutés                 : 353
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 353

Valeurs comparées        : 113 155
Exactes                  : 85 545
Dans tolérance           : 14 665
Hors tolérance           : 11 003
Balises manquantes       : 87
Balises supplémentaires  : 1 820
Écarts non numériques    : 35

Conformité               : 88,56 %
```

Sur ces écarts, **3 742 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **91,87 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 44260 | 42621 | 1275 | 239 | 7 | 118 | 99,18 % |
| Ventilation | 2419 | 1564 | 847 | 8 | 0 | 0 | 99,67 % |
| Apports | 11334 | 9812 | 961 | 283 | 0 | 278 | 95,05 % |
| Besoin chauffage | 1738 | 14 | 1448 | 272 | 4 | 0 | 84,12 % |
| Besoin ECS | 3363 | 3175 | 178 | 10 | 0 | 0 | 99,70 % |
| Génération chauffage | 7090 | 2837 | 2444 | 1761 | 26 | 22 | 74,49 % |
| Génération ECS | 7476 | 4730 | 981 | 1748 | 6 | 11 | 76,39 % |
| Auxiliaires | 12002 | 7671 | 2253 | 2078 | 0 | 0 | 82,69 % |
| Froid | 4364 | 4044 | 28 | 248 | 44 | 0 | 93,31 % |
| PV | 2477 | 2468 | 0 | 9 | 0 | 0 | 99,64 % |
| Sorties énergie finale | 2313 | 1208 | 616 | 489 | 0 | 0 | 78,86 % |
| Sorties énergie primaire | 2824 | 1318 | 818 | 669 | 0 | 0 | 75,64 % |
| GES | 4705 | 2244 | 1366 | 1079 | 0 | 0 | 76,73 % |
| Coûts | 3999 | 839 | 1275 | 1885 | 0 | 0 | 52,86 % |
| Confort d'été | 2038 | 558 | 0 | 89 | 0 | 1391 | 27,38 % |
| Autre | 753 | 442 | 175 | 136 | 0 | 0 | 81,94 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61227 | 10764 | 5602 | 22 | 90,71 % |
| appartement_issu_immeuble | 42 | 14798 | 10064 | 1459 | 2993 | 19 | 77,65 % |
| maison_individuelle | 36 | 10085 | 7563 | 1120 | 1331 | 18 | 85,79 % |
| appartement_individuel | 39 | 9146 | 6691 | 1322 | 1077 | 28 | 87,24 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 276 | 85841 | 65585 | 12180 | 6479 | 47 | 90,30 % |
| pre_2026 | 77 | 27314 | 19960 | 2485 | 4524 | 40 | 81,94 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 268 | 83793 | 64367 | 11770 | 6125 | 15 | 90,57 % |
| 3cl_tribu_1.4.25.1 | 64 | 23040 | 16811 | 2036 | 3904 | 24 | 81,57 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2711 | 2067 | 247 | 369 | 0 | 85,14 % |
| 3cl_bbs_V2025.11.1.0 | 5 | 1313 | 729 | 221 | 311 | 22 | 72,08 % |
| 3cl-2024.6.1.0 | 3 | 644 | 441 | 81 | 106 | 12 | 80,68 % |
| BBS_Slama_2024.6.1.0 | 2 | 640 | 457 | 87 | 96 | 0 | 84,74 % |
| inconnu | 2 | 522 | 347 | 131 | 33 | 10 | 91,22 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 184 | 34 | 49 | 4 | 77,86 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 142 | 58 | 10 | 0 | 93,46 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 744 | 72 | 1 098,9 % | hors-tol×744 |
| 2 | Génération chauffage | `conso_ch` | 702 | 138 | 1 243,1 % | hors-tol×696 manquante×6 |
| 3 | Génération ECS | `conso_ecs_depensier` | 681 | 70 | 845,3 % | hors-tol×681 |
| 4 | Génération chauffage | `conso_ch_depensier` | 569 | 138 | 1 243,1 % | hors-tol×563 manquante×6 |
| 5 | Coûts | `cout_5_usages` | 481 | 217 | 799,5 % | hors-tol×481 |
| 6 | GES | `emission_ges_5_usages` | 365 | 154 | 1 189,8 % | hors-tol×365 |
| 7 | Sorties énergie finale | `conso_5_usages` | 358 | 149 | 1 219,0 % | hors-tol×358 |
| 8 | Apports | `inertie_lourde` | 352 | 352 | 100,0 % | hors-tol×74 suppl×278 |
| 9 | Coûts | `cout_ecs_depensier` | 343 | 343 | 204,2 % | hors-tol×343 |
| 10 | Coûts | `cout_ch` | 343 | 164 | 533,1 % | hors-tol×343 |
| 11 | Coûts | `cout_ch_depensier` | 342 | 342 | 264,2 % | hors-tol×342 |
| 12 | Confort d'été | `isolation_toiture` | 313 | 313 | 100,0 % | hors-tol×34 suppl×279 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 310 | 310 | 200,0 % | hors-tol×32 suppl×278 |
| 14 | GES | `emission_ges_ch` | 301 | 142 | 810,8 % | hors-tol×301 |
| 15 | Confort d'été | `protection_solaire_exterieure` | 290 | 290 | 100,0 % | hors-tol×12 suppl×278 |
| 16 | Confort d'été | `aspect_traversant` | 289 | 289 | — | hors-tol×11 suppl×278 |
| 17 | Coûts | `cout_ecs` | 288 | 160 | 559,4 % | hors-tol×288 |
| 18 | Confort d'été | `brasseur_air` | 278 | 278 | — | suppl×278 |
| 19 | Génération ECS | `rendement_stockage` | 276 | 42 | 44,3 % | hors-tol×270 manquante×5 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 203 | 203 | 346,7 % | hors-tol×203 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 154 | 154 | 141,7 % | hors-tol×154 |
| 22 | Génération chauffage | `rendement_generation` | 148 | 117 | 26,9 % | hors-tol×123 suppl×22 manquante×3 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 144 | 144 | 199,4 % | hors-tol×144 |
| 24 | GES | `emission_ges_ch_depensier` | 141 | 141 | 151,3 % | hors-tol×141 |
| 25 | Besoin chauffage | `besoin_ch` | 139 | 51 | 155,6 % | hors-tol×137 manquante×2 |
| 26 | Besoin chauffage | `besoin_ch_depensier` | 137 | 52 | 1 009,4 % | hors-tol×135 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 136 | 136 | 199,4 % | hors-tol×136 |
| 28 | Sorties énergie primaire | `ep_conso_ch` | 136 | 136 | 140,3 % | hors-tol×136 |
| 29 | Sorties énergie primaire | `ep_conso_ch_depensier` | 136 | 136 | 140,3 % | hors-tol×136 |
| 30 | Sorties énergie finale | `conso_5_usages_m2` | 131 | 131 | 143,8 % | hors-tol×131 |
| 31 | GES | `emission_ges_ecs` | 122 | 61 | 616,5 % | hors-tol×122 |
| 32 | Auxiliaires | `cout_total_auxiliaire` | 121 | 121 | 3 213,8 % | hors-tol×121 |
| 33 | Auxiliaires | `cout_auxiliaire_generation_ch` | 103 | 103 | 100,0 % | hors-tol×103 |
| 34 | Auxiliaires | `conso_auxiliaire_generation_ch` | 91 | 91 | 100,0 % | hors-tol×91 |
| 35 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 91 | 91 | 100,0 % | hors-tol×91 |
| 36 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 91 | 91 | 100,0 % | hors-tol×91 |
| 37 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 91 | 91 | 100,0 % | hors-tol×91 |
| 38 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 91 | 91 | 100,0 % | hors-tol×91 |
| 39 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 91 | 91 | 100,0 % | hors-tol×91 |
| 40 | Génération chauffage | `pn` | 91 | 77 | 1 328,6 % | hors-tol×87 manquante×4 |

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
| `besoin_ecs_depensier` | 9 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `cout_fr_depensier` | 18 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 300 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 300 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 141 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 182 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `conso_totale_auxiliaire` | 6 | la référence publie un total auxiliaire de 103.1 kWh, incompatible avec la somme de ses postes (316.8 kWh) |
| `besoin_ch_depensier` | 6 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_ecs` | 466 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 466 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 234 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |

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
