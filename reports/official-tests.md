# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-01T14:23:20+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `b39ce24` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 229
Exécutés                 : 229
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 229

Valeurs comparées        : 71 683
Exactes                  : 55 079
Dans tolérance           : 10 245
Hors tolérance           : 4 922
Balises manquantes       : 42
Balises supplémentaires  : 1 356
Écarts non numériques    : 39

Conformité               : 91,13 %
```

Sur ces écarts, **1 873 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,74 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 28956 | 28001 | 815 | 77 | 3 | 60 | 99,52 % |
| Ventilation | 1581 | 990 | 590 | 1 | 0 | 0 | 99,94 % |
| Apports | 7619 | 6620 | 706 | 79 | 0 | 214 | 96,15 % |
| Besoin chauffage | 974 | 12 | 900 | 60 | 2 | 0 | 93,63 % |
| Besoin ECS | 1825 | 1770 | 52 | 3 | 0 | 0 | 99,84 % |
| Génération chauffage | 4003 | 1595 | 1565 | 833 | 6 | 4 | 78,94 % |
| Génération ECS | 3413 | 2570 | 556 | 276 | 3 | 8 | 91,59 % |
| Auxiliaires | 7786 | 4569 | 1887 | 1330 | 0 | 0 | 82,92 % |
| Froid | 2788 | 2718 | 0 | 42 | 28 | 0 | 97,49 % |
| PV | 1609 | 1600 | 0 | 9 | 0 | 0 | 99,44 % |
| Sorties énergie finale | 1553 | 839 | 480 | 234 | 0 | 0 | 84,93 % |
| Sorties énergie primaire | 1832 | 881 | 599 | 337 | 0 | 0 | 80,79 % |
| GES | 3131 | 1584 | 988 | 535 | 0 | 0 | 82,15 % |
| Coûts | 2673 | 699 | 972 | 1002 | 0 | 0 | 62,51 % |
| Confort d'été | 1358 | 266 | 0 | 22 | 0 | 1070 | 19,59 % |
| Autre | 582 | 365 | 135 | 82 | 0 | 0 | 85,91 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 206 | 65129 | 50322 | 9312 | 4156 | 14 | 91,27 % |
| appartement_issu_immeuble | 8 | 2715 | 2040 | 306 | 314 | 1 | 86,15 % |
| appartement_individuel | 9 | 2095 | 1405 | 341 | 321 | 18 | 82,98 % |
| maison_individuelle | 6 | 1744 | 1312 | 286 | 131 | 9 | 91,31 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 212 | 66083 | 50916 | 9687 | 4138 | 18 | 91,41 % |
| pre_2026 | 17 | 5600 | 4163 | 558 | 784 | 24 | 84,05 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 209 | 65306 | 50425 | 9478 | 4077 | 4 | 91,43 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2711 | 2064 | 242 | 374 | 0 | 84,84 % |
| 3cl_tribu_1.4.25.1 | 4 | 1326 | 1018 | 118 | 155 | 8 | 85,41 % |
| 3cl-2024.6.1.0 | 3 | 644 | 440 | 81 | 106 | 12 | 80,53 % |
| BBS_Slama_2024.6.1.0 | 2 | 640 | 457 | 83 | 100 | 0 | 84,11 % |
| inconnu | 2 | 522 | 347 | 131 | 33 | 10 | 91,22 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 184 | 34 | 49 | 4 | 77,86 % |
| 3cl_bbs_V2025.11.1.0 | 1 | 255 | 144 | 78 | 28 | 4 | 86,72 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 312 | 72 | 112,6 % | hors-tol×309 manquante×3 |
| 2 | Génération chauffage | `conso_ch_depensier` | 253 | 71 | 211,4 % | hors-tol×250 manquante×3 |
| 3 | Coûts | `cout_5_usages` | 253 | 124 | 70,0 % | hors-tol×253 |
| 4 | Apports | `inertie_lourde` | 228 | 228 | 100,0 % | suppl×214 hors-tol×14 |
| 5 | Confort d'été | `isolation_toiture` | 224 | 224 | — | suppl×214 hors-tol×10 |
| 6 | Confort d'été | `enum_indicateur_confort_ete_id` | 222 | 222 | 50,0 % | suppl×214 hors-tol×8 |
| 7 | Coûts | `cout_ecs_depensier` | 221 | 221 | 59,6 % | hors-tol×221 |
| 8 | Coûts | `cout_ch_depensier` | 219 | 219 | 100,0 % | hors-tol×219 |
| 9 | Confort d'été | `aspect_traversant` | 216 | 216 | — | suppl×214 hors-tol×2 |
| 10 | Confort d'été | `protection_solaire_exterieure` | 216 | 216 | 100,0 % | suppl×214 hors-tol×2 |
| 11 | Confort d'été | `brasseur_air` | 214 | 214 | — | suppl×214 |
| 12 | GES | `emission_ges_5_usages` | 179 | 82 | 100,0 % | hors-tol×179 |
| 13 | Sorties énergie finale | `conso_5_usages` | 172 | 78 | 70,4 % | hors-tol×172 |
| 14 | Coûts | `cout_ch` | 153 | 77 | 100,0 % | hors-tol×153 |
| 15 | GES | `emission_ges_ch` | 152 | 76 | 100,0 % | hors-tol×152 |
| 16 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 151 | 151 | 346,7 % | hors-tol×151 |
| 17 | Coûts | `cout_ecs` | 136 | 80 | 42,8 % | hors-tol×136 |
| 18 | Génération ECS | `conso_ecs` | 134 | 28 | 25,6 % | hors-tol×134 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 124 | 124 | 140,9 % | hors-tol×124 |
| 20 | Génération ECS | `conso_ecs_depensier` | 100 | 26 | 19,9 % | hors-tol×100 |
| 21 | GES | `emission_ges_ch_depensier` | 75 | 75 | 100,0 % | hors-tol×75 |
| 22 | Sorties énergie primaire | `ep_conso_5_usages` | 73 | 73 | 50,0 % | hors-tol×73 |
| 23 | Sorties énergie primaire | `ep_conso_ch` | 70 | 70 | 100,0 % | hors-tol×70 |
| 24 | Sorties énergie primaire | `ep_conso_ch_depensier` | 70 | 70 | 100,0 % | hors-tol×70 |
| 25 | Génération chauffage | `rendement_generation` | 67 | 64 | 24,8 % | hors-tol×63 suppl×4 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 65 | 65 | 50,4 % | hors-tol×65 |
| 27 | GES | `emission_ges_ecs` | 64 | 32 | 100,0 % | hors-tol×64 |
| 28 | Auxiliaires | `cout_auxiliaire_generation_ch` | 64 | 64 | 98,6 % | hors-tol×64 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 62 | 62 | 50,0 % | hors-tol×62 |
| 30 | Auxiliaires | `cout_auxiliaire_distribution_ecs` | 55 | 55 | 100,0 % | hors-tol×55 |
| 31 | Auxiliaires | `conso_auxiliaire_distribution_ecs` | 55 | 55 | 100,0 % | hors-tol×55 |
| 32 | Auxiliaires | `emission_ges_auxiliaire_distribution_ecs` | 55 | 55 | 100,0 % | hors-tol×55 |
| 33 | Auxiliaires | `ep_conso_auxiliaire_distribution_ecs` | 55 | 55 | 100,0 % | hors-tol×55 |
| 34 | Génération chauffage | `pn` | 53 | 49 | 90,9 % | hors-tol×53 |
| 35 | Génération chauffage | `qp0` | 53 | 49 | 98 791,1 % | hors-tol×53 |
| 36 | Auxiliaires | `conso_auxiliaire_generation_ch` | 52 | 52 | 99,8 % | hors-tol×52 |
| 37 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 52 | 52 | 99,8 % | hors-tol×52 |
| 38 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 52 | 52 | 99,8 % | hors-tol×52 |
| 39 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 52 | 52 | 99,8 % | hors-tol×52 |
| 40 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 52 | 52 | 99,8 % | hors-tol×52 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 209 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 209 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 209 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 209 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 209 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 209 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `cout_fr_depensier` | 3 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 184 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 184 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 114 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 134 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `zone_pre2026coefelec_diag1793608.xml` | appartement_issu_immeuble | pre_2026 | 722 | 153 | 78,81 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2675E0942311V.xml` | immeuble_collectif | post_2026 | 410 | 89 | 78,29 % |
| `2688E0016745Q.xml` | appartement_issu_immeuble | post_2026 | 422 | 84 | 80,09 % |
| `2675E0021756W.xml` | immeuble_collectif | post_2026 | 356 | 82 | 76,97 % |
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 323 | 82 | 74,61 % |
| `2612E0854137C.xml` | immeuble_collectif | post_2026 | 274 | 80 | 70,80 % |
| `2682E0040013I.xml` | immeuble_collectif | post_2026 | 254 | 80 | 68,50 % |
| `2682E0040066J.xml` | immeuble_collectif | post_2026 | 254 | 80 | 68,50 % |
| `2682E0040174N.xml` | immeuble_collectif | post_2026 | 254 | 80 | 68,50 % |
| `2682E0040242D.xml` | immeuble_collectif | post_2026 | 254 | 80 | 68,50 % |
| `2594E0486196Q.xml` | immeuble_collectif | pre_2026 | 262 | 79 | 69,85 % |
| `2675E0069484O.xml` | immeuble_collectif | post_2026 | 295 | 79 | 73,22 % |
| `2659E1259773H.xml` | immeuble_collectif | post_2026 | 298 | 76 | 74,50 % |
| `2675E0023041H.xml` | immeuble_collectif | post_2026 | 331 | 76 | 77,04 % |
| `2675E0023980K.xml` | immeuble_collectif | post_2026 | 339 | 76 | 77,58 % |
| `2467E3590684Y.xml` | immeuble_collectif | pre_2026 | 461 | 75 | 83,73 % |
| `2659E1260921L.xml` | immeuble_collectif | post_2026 | 269 | 75 | 72,12 % |
| `2675E0022293N.xml` | immeuble_collectif | post_2026 | 361 | 75 | 79,22 % |
| `2675E0018503T.xml` | immeuble_collectif | post_2026 | 402 | 74 | 81,59 % |
| `2675E0021134Y.xml` | immeuble_collectif | post_2026 | 442 | 74 | 83,26 % |
| `2624E0037134J.xml` | immeuble_collectif | post_2026 | 302 | 72 | 76,16 % |
| `2667E1171687R.xml` | immeuble_collectif | post_2026 | 339 | 71 | 79,06 % |
| `2667E1172425B.xml` | immeuble_collectif | post_2026 | 300 | 71 | 76,33 % |
| `2675E0018468K.xml` | immeuble_collectif | post_2026 | 400 | 68 | 83,00 % |

## Cas totalement conformes

_Aucun._
