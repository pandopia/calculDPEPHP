# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-01T10:05:42+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `d55c12d` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 225
Exécutés                 : 225
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 225

Valeurs comparées        : 71 563
Exactes                  : 54 198
Dans tolérance           : 8 948
Hors tolérance           : 6 047
Balises manquantes       : 46
Balises supplémentaires  : 2 283
Écarts non numériques    : 41

Conformité               : 88,24 %
```

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 28595 | 27663 | 801 | 80 | 4 | 47 | 99,54 % |
| Ventilation | 1553 | 972 | 580 | 1 | 0 | 0 | 99,94 % |
| Apports | 7752 | 6551 | 685 | 78 | 0 | 438 | 93,34 % |
| Besoin chauffage | 958 | 12 | 872 | 72 | 2 | 0 | 92,28 % |
| Besoin ECS | 1797 | 1749 | 45 | 3 | 0 | 0 | 99,83 % |
| Génération chauffage | 4097 | 1562 | 1432 | 926 | 6 | 171 | 73,08 % |
| Génération ECS | 3918 | 2529 | 540 | 276 | 14 | 559 | 78,33 % |
| Auxiliaires | 7650 | 4492 | 1662 | 1496 | 0 | 0 | 80,44 % |
| Froid | 2735 | 2670 | 0 | 42 | 20 | 3 | 97,62 % |
| PV | 1581 | 1572 | 0 | 9 | 0 | 0 | 99,43 % |
| Sorties énergie finale | 1523 | 815 | 442 | 266 | 0 | 0 | 82,53 % |
| Sorties énergie primaire | 1800 | 859 | 545 | 380 | 0 | 0 | 78,00 % |
| GES | 3072 | 1555 | 894 | 598 | 0 | 0 | 79,72 % |
| Coûts | 2622 | 582 | 321 | 1719 | 0 | 0 | 34,44 % |
| Confort d'été | 1337 | 255 | 0 | 17 | 0 | 1065 | 19,07 % |
| Autre | 573 | 360 | 129 | 84 | 0 | 0 | 85,34 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 206 | 65998 | 50178 | 8365 | 5235 | 26 | 88,43 % |
| appartement_issu_immeuble | 7 | 2478 | 1837 | 238 | 324 | 1 | 83,50 % |
| appartement_individuel | 7 | 1620 | 1082 | 151 | 343 | 14 | 75,78 % |
| maison_individuelle | 5 | 1467 | 1101 | 194 | 145 | 5 | 87,98 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 208 | 65887 | 50066 | 8469 | 5155 | 20 | 88,56 % |
| pre_2026 | 17 | 5676 | 4132 | 479 | 892 | 26 | 80,99 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 207 | 65653 | 49919 | 8410 | 5135 | 16 | 88,57 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2749 | 2045 | 196 | 439 | 0 | 81,31 % |
| 3cl_tribu_1.4.25.1 | 4 | 1345 | 1015 | 109 | 167 | 8 | 83,32 % |
| 3cl-2024.6.1.0 | 3 | 652 | 452 | 83 | 93 | 13 | 81,68 % |
| BBS_Slama_2024.6.1.0 | 2 | 647 | 436 | 58 | 144 | 0 | 76,12 % |
| 3cl_tribu_1.4.25.0 | 1 | 283 | 184 | 33 | 49 | 5 | 76,41 % |
| inconnu | 1 | 234 | 147 | 59 | 20 | 4 | 87,66 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Coûts | `cout_5_usages` | 557 | 225 | 65,9 % | hors-tol×557 |
| 2 | Génération chauffage | `conso_ch` | 357 | 82 | 109,0 % | hors-tol×354 manquante×3 |
| 3 | Coûts | `cout_ch` | 341 | 225 | 100,0 % | hors-tol×341 |
| 4 | Coûts | `cout_ecs` | 341 | 224 | 79,5 % | hors-tol×341 |
| 5 | Génération ECS | `Qgw` | 336 | 225 | — | suppl×336 |
| 6 | Génération chauffage | `conso_ch_depensier` | 288 | 82 | 211,4 % | hors-tol×285 manquante×3 |
| 7 | Apports | `enum_classe_inertie_id` | 225 | 225 | — | suppl×225 |
| 8 | Apports | `inertie_lourde` | 224 | 224 | 100,0 % | suppl×213 hors-tol×11 |
| 9 | Confort d'été | `enum_indicateur_confort_ete_id` | 220 | 220 | 50,0 % | suppl×213 hors-tol×7 |
| 10 | Confort d'été | `isolation_toiture` | 220 | 220 | — | suppl×213 hors-tol×7 |
| 11 | Coûts | `cout_ecs_depensier` | 220 | 220 | 128,0 % | hors-tol×220 |
| 12 | Coûts | `cout_ch_depensier` | 217 | 217 | 109,0 % | hors-tol×217 |
| 13 | Confort d'été | `protection_solaire_exterieure` | 215 | 215 | 100,0 % | suppl×213 hors-tol×2 |
| 14 | Génération ECS | `rendement_stockage` | 214 | 164 | 34,4 % | suppl×203 hors-tol×11 |
| 15 | Confort d'été | `aspect_traversant` | 214 | 214 | — | suppl×213 hors-tol×1 |
| 16 | Confort d'été | `brasseur_air` | 213 | 213 | — | suppl×213 |
| 17 | GES | `emission_ges_5_usages` | 201 | 92 | 100,0 % | hors-tol×201 |
| 18 | Sorties énergie finale | `conso_5_usages` | 194 | 87 | 68,4 % | hors-tol×194 |
| 19 | GES | `emission_ges_ch` | 176 | 88 | 103,2 % | hors-tol×176 |
| 20 | Génération chauffage | `pveil` | 167 | 163 | — | suppl×167 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 148 | 148 | 663,3 % | hors-tol×148 |
| 22 | Génération ECS | `conso_ecs` | 134 | 28 | 25,6 % | hors-tol×134 |
| 23 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 122 | 122 | 137,9 % | hors-tol×122 |
| 24 | Auxiliaires | `cout_total_auxiliaire` | 105 | 105 | 248,6 % | hors-tol×105 |
| 25 | Génération ECS | `conso_ecs_depensier` | 100 | 26 | 19,9 % | hors-tol×100 |
| 26 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 93 | 93 | 138,0 % | hors-tol×93 |
| 27 | GES | `emission_ges_ch_depensier` | 88 | 88 | 100,0 % | hors-tol×88 |
| 28 | Sorties énergie primaire | `ep_conso_5_usages` | 84 | 84 | 50,0 % | hors-tol×84 |
| 29 | Sorties énergie primaire | `ep_conso_ch` | 82 | 82 | 100,0 % | hors-tol×82 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 82 | 82 | 100,0 % | hors-tol×82 |
| 31 | Génération chauffage | `rendement_generation` | 80 | 75 | 16,0 % | hors-tol×76 suppl×4 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 74 | 74 | 236,2 % | hors-tol×74 |
| 33 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 73 | 73 | 50,4 % | hors-tol×73 |
| 34 | Sorties énergie finale | `conso_5_usages_m2` | 72 | 72 | 50,0 % | hors-tol×72 |
| 35 | GES | `emission_ges_ecs` | 64 | 32 | 100,0 % | hors-tol×64 |
| 36 | Génération chauffage | `pn` | 54 | 50 | 90,9 % | hors-tol×54 |
| 37 | Génération chauffage | `qp0` | 54 | 50 | 90,9 % | hors-tol×54 |
| 38 | Auxiliaires | `conso_auxiliaire_generation_ch` | 54 | 54 | 99,8 % | hors-tol×54 |
| 39 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 54 | 54 | 99,8 % | hors-tol×54 |
| 40 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 54 | 54 | 99,8 % | hors-tol×54 |

## Conformité structurelle du XML produit

Balises écrites par le moteur mais absentes du schéma ADEME — un fichier
les contenant serait rejeté par l'observatoire :

| Balise | Cas concernés |
|---|---:|
| `Qgw` | 225 |
| `pveil` | 163 |

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `zone_pre2026coefelec_diag1793608.xml` | appartement_issu_immeuble | pre_2026 | 733 | 164 | 77,63 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 528 | 128 | 75,76 % |
| `2675E0942311V.xml` | immeuble_collectif | post_2026 | 428 | 109 | 74,53 % |
| `2593E3377930D.xml` | appartement_individuel | pre_2026 | 272 | 100 | 63,24 % |
| `2659E1128954U.xml` | immeuble_collectif | post_2026 | 590 | 99 | 83,22 % |
| `2659E1127256M.xml` | immeuble_collectif | post_2026 | 569 | 95 | 83,30 % |
| `2592E0655586O.xml` | appartement_individuel | pre_2026 | 257 | 89 | 65,37 % |
| `2467E3590684Y.xml` | immeuble_collectif | pre_2026 | 469 | 87 | 81,45 % |
| `2659E1127961P.xml` | immeuble_collectif | post_2026 | 548 | 87 | 84,12 % |
| `2675E0021756W.xml` | immeuble_collectif | post_2026 | 360 | 87 | 75,83 % |
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 327 | 87 | 73,39 % |
| `2688E0016745Q.xml` | appartement_issu_immeuble | post_2026 | 425 | 87 | 79,53 % |
| `2612E0854137C.xml` | immeuble_collectif | post_2026 | 278 | 84 | 69,78 % |
| `2659E1259773H.xml` | immeuble_collectif | post_2026 | 302 | 84 | 72,19 % |
| `2675E0069484O.xml` | immeuble_collectif | post_2026 | 299 | 84 | 71,91 % |
| `2682E0040013I.xml` | immeuble_collectif | post_2026 | 258 | 84 | 67,44 % |
| `2682E0040066J.xml` | immeuble_collectif | post_2026 | 258 | 84 | 67,44 % |
| `2682E0040174N.xml` | immeuble_collectif | post_2026 | 258 | 84 | 67,44 % |
| `2682E0040242D.xml` | immeuble_collectif | post_2026 | 258 | 84 | 67,44 % |
| `2594E0486196Q.xml` | immeuble_collectif | pre_2026 | 266 | 83 | 68,80 % |
| `2675E0023041H.xml` | immeuble_collectif | post_2026 | 335 | 82 | 75,52 % |
| `2675E0023980K.xml` | immeuble_collectif | post_2026 | 343 | 82 | 76,09 % |
| `2659E1260921L.xml` | immeuble_collectif | post_2026 | 273 | 81 | 70,33 % |
| `2675E0022293N.xml` | immeuble_collectif | post_2026 | 365 | 81 | 77,81 % |
| `2675E0018503T.xml` | immeuble_collectif | post_2026 | 406 | 80 | 80,30 % |

## Cas totalement conformes

_Aucun._
