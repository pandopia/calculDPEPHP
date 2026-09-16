# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-16T08:21:26+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `578f631` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 362
Exécutés                 : 362
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 362

Valeurs comparées        : 115 495
Exactes                  : 87 092
Dans tolérance           : 15 807
Hors tolérance           : 10 597
Balises manquantes       : 107
Balises supplémentaires  : 1 852
Écarts non numériques    : 40

Conformité               : 89,09 %
```

Sur ces écarts, **3 256 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **91,91 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 45024 | 43324 | 1321 | 252 | 7 | 120 | 99,16 % |
| Ventilation | 2479 | 1604 | 863 | 12 | 0 | 0 | 99,52 % |
| Apports | 11528 | 9942 | 990 | 314 | 0 | 282 | 94,83 % |
| Besoin chauffage | 1776 | 14 | 1546 | 212 | 4 | 0 | 87,84 % |
| Besoin ECS | 3430 | 3230 | 187 | 13 | 0 | 0 | 99,62 % |
| Génération chauffage | 7260 | 2908 | 2538 | 1764 | 26 | 24 | 75,01 % |
| Génération ECS | 7600 | 4799 | 1596 | 1176 | 14 | 15 | 84,14 % |
| Auxiliaires | 12308 | 7833 | 2342 | 2133 | 0 | 0 | 82,67 % |
| Froid | 4484 | 4152 | 28 | 248 | 56 | 0 | 93,22 % |
| PV | 2540 | 2531 | 0 | 9 | 0 | 0 | 99,65 % |
| Sorties énergie finale | 2376 | 1240 | 640 | 496 | 0 | 0 | 79,12 % |
| Sorties énergie primaire | 2896 | 1343 | 834 | 697 | 0 | 0 | 75,17 % |
| GES | 4831 | 2286 | 1407 | 1120 | 0 | 0 | 76,44 % |
| Coûts | 4107 | 856 | 1338 | 1913 | 0 | 0 | 53,42 % |
| Confort d'été | 2085 | 579 | 0 | 95 | 0 | 1411 | 27,77 % |
| Autre | 771 | 451 | 177 | 143 | 0 | 0 | 81,45 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61232 | 10763 | 5598 | 22 | 90,72 % |
| appartement_issu_immeuble | 46 | 15878 | 10725 | 2327 | 2496 | 34 | 81,96 % |
| maison_individuelle | 39 | 10872 | 8116 | 1319 | 1358 | 23 | 86,47 % |
| appartement_individuel | 41 | 9619 | 7019 | 1398 | 1145 | 28 | 87,13 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 284 | 87921 | 66979 | 12513 | 6776 | 67 | 90,12 % |
| pre_2026 | 78 | 27574 | 20113 | 3294 | 3821 | 40 | 84,65 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 273 | 85109 | 65343 | 11966 | 6253 | 15 | 90,54 % |
| 3cl_tribu_1.4.25.1 | 64 | 23040 | 16821 | 2735 | 3195 | 24 | 84,64 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2711 | 2068 | 357 | 258 | 0 | 89,22 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| inconnu | 3 | 799 | 532 | 197 | 54 | 15 | 90,90 % |
| 3cl-2024.6.1.0 | 3 | 644 | 435 | 81 | 112 | 12 | 79,75 % |
| BBS_Slama_2024.6.1.0 | 2 | 640 | 457 | 87 | 96 | 0 | 84,74 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 186 | 33 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 146 | 1 | 112 | 0 | 56,32 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 143 | 58 | 9 | 0 | 93,93 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 691 | 141 | 1 243,1 % | hors-tol×685 manquante×6 |
| 2 | Génération chauffage | `conso_ch_depensier` | 564 | 143 | 1 243,1 % | hors-tol×558 manquante×6 |
| 3 | Génération ECS | `conso_ecs` | 524 | 78 | 1 098,9 % | hors-tol×524 |
| 4 | Coûts | `cout_5_usages` | 490 | 222 | 799,5 % | hors-tol×490 |
| 5 | Génération ECS | `conso_ecs_depensier` | 451 | 76 | 845,3 % | hors-tol×451 |
| 6 | GES | `emission_ges_5_usages` | 370 | 158 | 11 300,5 % | hors-tol×370 |
| 7 | Sorties énergie finale | `conso_5_usages` | 363 | 152 | 1 219,0 % | hors-tol×363 |
| 8 | Apports | `inertie_lourde` | 361 | 361 | 100,0 % | hors-tol×79 suppl×282 |
| 9 | Coûts | `cout_ecs_depensier` | 351 | 351 | 204,2 % | hors-tol×351 |
| 10 | Coûts | `cout_ch_depensier` | 350 | 350 | 268,0 % | hors-tol×350 |
| 11 | Coûts | `cout_ch` | 345 | 168 | 533,1 % | hors-tol×345 |
| 12 | Confort d'été | `isolation_toiture` | 319 | 319 | 100,0 % | hors-tol×36 suppl×283 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 316 | 316 | 200,0 % | hors-tol×34 suppl×282 |
| 14 | GES | `emission_ges_ch` | 307 | 148 | 810,8 % | hors-tol×307 |
| 15 | Coûts | `cout_ecs` | 299 | 165 | 559,4 % | hors-tol×299 |
| 16 | Confort d'été | `protection_solaire_exterieure` | 295 | 295 | 100,0 % | hors-tol×13 suppl×282 |
| 17 | Confort d'été | `aspect_traversant` | 294 | 294 | — | hors-tol×12 suppl×282 |
| 18 | Confort d'été | `brasseur_air` | 282 | 282 | — | suppl×282 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 211 | 211 | 346,7 % | hors-tol×211 |
| 20 | Génération ECS | `rendement_stockage` | 161 | 48 | 40,0 % | hors-tol×150 manquante×10 suppl×1 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 160 | 160 | 225,0 % | hors-tol×160 |
| 22 | Génération chauffage | `rendement_generation` | 153 | 122 | 39,1 % | hors-tol×128 suppl×22 manquante×3 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 148 | 148 | 199,4 % | hors-tol×148 |
| 24 | GES | `emission_ges_ch_depensier` | 147 | 147 | 154,0 % | hors-tol×147 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 140 | 140 | 199,4 % | hors-tol×140 |
| 26 | Sorties énergie primaire | `ep_conso_ch` | 140 | 140 | 142,9 % | hors-tol×140 |
| 27 | Sorties énergie primaire | `ep_conso_ch_depensier` | 140 | 140 | 142,9 % | hors-tol×140 |
| 28 | GES | `emission_ges_ecs` | 134 | 67 | 616,5 % | hors-tol×134 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 133 | 133 | 143,8 % | hors-tol×133 |
| 30 | Besoin chauffage | `besoin_ch_depensier` | 111 | 44 | 1 009,5 % | hors-tol×109 manquante×2 |
| 31 | Auxiliaires | `cout_auxiliaire_generation_ch` | 111 | 111 | 100,0 % | hors-tol×111 |
| 32 | Auxiliaires | `cout_total_auxiliaire` | 111 | 111 | 3 213,8 % | hors-tol×111 |
| 33 | Besoin chauffage | `besoin_ch` | 105 | 38 | 155,6 % | hors-tol×103 manquante×2 |
| 34 | Auxiliaires | `conso_auxiliaire_generation_ch` | 97 | 97 | 100,0 % | hors-tol×97 |
| 35 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 97 | 97 | 100,0 % | hors-tol×97 |
| 36 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 97 | 97 | 100,0 % | hors-tol×97 |
| 37 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 97 | 97 | 100,0 % | hors-tol×97 |
| 38 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 97 | 97 | 100,0 % | hors-tol×97 |
| 39 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 97 | 97 | 100,0 % | hors-tol×97 |
| 40 | Génération chauffage | `qp0` | 95 | 83 | 205 028,2 % | hors-tol×94 manquante×1 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 271 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 271 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 271 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 271 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 271 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 271 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 11 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 11 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 18 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 300 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 300 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 143 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 182 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `conso_totale_auxiliaire` | 8 | la référence publie un total auxiliaire de 103.1 kWh, incompatible avec la somme de ses postes (316.8 kWh) |
| `besoin_ch_depensier` | 8 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `conso_ecs` | 225 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 225 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 153 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 31 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 3 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 848 | 145 | 82,90 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 145 | 75,38 % |
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 142 | 84,29 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2600E0035103I.xml` | immeuble_collectif | post_2026 | 642 | 116 | 81,93 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 113 | 56,54 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 100 | 78,59 % |
| `2400E0636882P.xml` | immeuble_collectif | pre_2026 | 415 | 100 | 75,90 % |
| `2400E0669732B.xml` | immeuble_collectif | pre_2026 | 890 | 98 | 88,99 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 97 | 62,11 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 90 | 68,20 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2268184I.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2675E0942311V.xml` | immeuble_collectif | post_2026 | 410 | 90 | 78,05 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 89 | 68,55 % |
| `2675E2152874Y.xml` | appartement_issu_immeuble | post_2026 | 335 | 89 | 73,43 % |
| `2400E0333876N.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333878P.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333880R.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333883U.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333885W.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333888Z.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333891C.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333892D.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |

## Cas totalement conformes

_Aucun._
