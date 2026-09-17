# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-17T06:27:28+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `1379989` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 370
Exécutés                 : 370
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 370

Valeurs comparées        : 118 281
Exactes                  : 89 089
Dans tolérance           : 16 486
Hors tolérance           : 10 633
Balises manquantes       : 122
Balises supplémentaires  : 1 902
Écarts non numériques    : 49

Conformité               : 89,26 %
```

Sur ces écarts, **3 326 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,07 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 45976 | 44240 | 1352 | 255 | 7 | 122 | 99,16 % |
| Ventilation | 2534 | 1629 | 883 | 22 | 0 | 0 | 99,13 % |
| Apports | 11777 | 10162 | 1034 | 291 | 0 | 290 | 95,07 % |
| Besoin chauffage | 1834 | 14 | 1677 | 139 | 4 | 0 | 92,20 % |
| Besoin ECS | 3518 | 3310 | 193 | 15 | 0 | 0 | 99,57 % |
| Génération chauffage | 7563 | 3083 | 2725 | 1704 | 26 | 25 | 76,79 % |
| Génération ECS | 7802 | 4935 | 1624 | 1214 | 15 | 14 | 84,07 % |
| Auxiliaires | 12580 | 7968 | 2358 | 2254 | 0 | 0 | 82,08 % |
| Froid | 4592 | 4248 | 29 | 251 | 64 | 0 | 93,14 % |
| PV | 2602 | 2587 | 0 | 9 | 6 | 0 | 99,42 % |
| Sorties énergie finale | 2432 | 1276 | 663 | 493 | 0 | 0 | 79,73 % |
| Sorties énergie primaire | 2960 | 1368 | 871 | 694 | 0 | 0 | 75,64 % |
| GES | 4943 | 2320 | 1466 | 1135 | 0 | 0 | 76,59 % |
| Coûts | 4203 | 881 | 1417 | 1905 | 0 | 0 | 54,68 % |
| Confort d'été | 2131 | 585 | 0 | 95 | 0 | 1451 | 27,45 % |
| Autre | 834 | 483 | 194 | 157 | 0 | 0 | 81,18 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61264 | 11028 | 5303 | 22 | 91,09 % |
| appartement_issu_immeuble | 54 | 18664 | 12664 | 2598 | 2996 | 49 | 81,54 % |
| maison_individuelle | 39 | 10872 | 8121 | 1333 | 1339 | 23 | 86,65 % |
| appartement_individuel | 41 | 9619 | 7040 | 1527 | 995 | 28 | 88,69 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 284 | 87920 | 67030 | 12699 | 6540 | 66 | 90,39 % |
| pre_2026 | 86 | 30361 | 22059 | 3787 | 4093 | 56 | 84,89 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 273 | 85108 | 65394 | 12152 | 6017 | 14 | 90,82 % |
| 3cl_tribu_1.4.25.1 | 66 | 23654 | 17191 | 2975 | 3168 | 40 | 85,02 % |
| 3cl_tribu_2024.6.1.0 | 12 | 4587 | 3427 | 601 | 495 | 0 | 87,58 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 674 | 96 | 158 | 0 | 81,91 % |
| inconnu | 3 | 799 | 532 | 197 | 54 | 15 | 90,90 % |
| 3cl-2024.6.1.0 | 3 | 644 | 435 | 81 | 112 | 12 | 79,75 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 186 | 33 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 146 | 1 | 112 | 0 | 56,32 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 143 | 58 | 9 | 0 | 93,93 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 653 | 137 | 2 900,0 % | hors-tol×647 manquante×6 |
| 2 | Génération ECS | `conso_ecs` | 542 | 82 | 1 098,9 % | hors-tol×542 |
| 3 | Génération chauffage | `conso_ch_depensier` | 534 | 138 | 2 900,0 % | hors-tol×528 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 491 | 222 | 882,3 % | hors-tol×491 |
| 5 | Génération ECS | `conso_ecs_depensier` | 465 | 80 | 845,3 % | hors-tol×465 |
| 6 | GES | `emission_ges_5_usages` | 374 | 160 | 19 178,9 % | hors-tol×374 |
| 7 | Apports | `inertie_lourde` | 369 | 369 | 100,0 % | hors-tol×79 suppl×290 |
| 8 | Sorties énergie finale | `conso_5_usages` | 364 | 153 | 19 918,0 % | hors-tol×364 |
| 9 | Coûts | `cout_ecs_depensier` | 359 | 359 | 207,9 % | hors-tol×359 |
| 10 | Coûts | `cout_ch_depensier` | 358 | 358 | 268,0 % | hors-tol×358 |
| 11 | Coûts | `cout_ch` | 332 | 162 | 533,1 % | hors-tol×332 |
| 12 | Confort d'été | `isolation_toiture` | 327 | 327 | 100,0 % | hors-tol×36 suppl×291 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 324 | 324 | 200,0 % | hors-tol×34 suppl×290 |
| 14 | Confort d'été | `protection_solaire_exterieure` | 303 | 303 | 100,0 % | hors-tol×13 suppl×290 |
| 15 | Confort d'été | `aspect_traversant` | 302 | 302 | — | hors-tol×12 suppl×290 |
| 16 | GES | `emission_ges_ch` | 298 | 145 | 810,8 % | hors-tol×298 |
| 17 | Coûts | `cout_ecs` | 291 | 162 | 559,4 % | hors-tol×291 |
| 18 | Confort d'été | `brasseur_air` | 290 | 290 | — | suppl×290 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 215 | 215 | 346,7 % | hors-tol×215 |
| 20 | Génération chauffage | `rendement_generation` | 166 | 126 | 39,1 % | hors-tol×140 suppl×23 manquante×3 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 165 | 165 | 225,0 % | hors-tol×165 |
| 22 | Génération ECS | `rendement_stockage` | 163 | 50 | 40,0 % | hors-tol×151 manquante×12 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 149 | 149 | 519,8 % | hors-tol×149 |
| 24 | GES | `emission_ges_ecs` | 146 | 73 | 616,5 % | hors-tol×146 |
| 25 | GES | `emission_ges_ch_depensier` | 144 | 144 | 154,0 % | hors-tol×144 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 138 | 138 | 519,0 % | hors-tol×138 |
| 27 | Sorties énergie primaire | `ep_conso_ch` | 134 | 134 | 142,9 % | hors-tol×134 |
| 28 | Sorties énergie primaire | `ep_conso_ch_depensier` | 134 | 134 | 142,9 % | hors-tol×134 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 127 | 127 | 242,6 % | hors-tol×127 |
| 30 | Auxiliaires | `cout_auxiliaire_generation_ch` | 115 | 115 | 100,0 % | hors-tol×115 |
| 31 | Auxiliaires | `cout_total_auxiliaire` | 115 | 115 | 3 213,8 % | hors-tol×115 |
| 32 | Auxiliaires | `conso_auxiliaire_generation_ch` | 101 | 101 | 100,0 % | hors-tol×101 |
| 33 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 101 | 101 | 100,0 % | hors-tol×101 |
| 34 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 101 | 101 | 100,0 % | hors-tol×101 |
| 35 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 101 | 101 | 100,0 % | hors-tol×101 |
| 36 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 101 | 101 | 100,0 % | hors-tol×101 |
| 37 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 101 | 101 | 100,0 % | hors-tol×101 |
| 38 | Génération chauffage | `qp0` | 97 | 85 | 205 028,2 % | hors-tol×96 manquante×1 |
| 39 | Génération chauffage | `pn` | 93 | 79 | 1 328,6 % | hors-tol×89 manquante×4 |
| 40 | GES | `emission_ges_5_usages_m2` | 92 | 92 | 152,9 % | hors-tol×92 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 277 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 277 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 277 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 277 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 277 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 277 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 13 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 15 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 19 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 306 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 306 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 146 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 184 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `conso_totale_auxiliaire` | 10 | la référence publie un total auxiliaire de 103.1 kWh, incompatible avec la somme de ses postes (316.8 kWh) |
| `besoin_ch_depensier` | 10 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `conso_ecs` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 154 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 32 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 3 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 142 | 84,29 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 136 | 76,91 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 848 | 125 | 85,26 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 113 | 56,54 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 111 | 63,84 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 100 | 78,59 % |
| `2400E0636882P.xml` | immeuble_collectif | pre_2026 | 415 | 98 | 76,39 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 97 | 62,11 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 90 | 68,20 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2268184I.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2675E0942311V.xml` | immeuble_collectif | post_2026 | 410 | 90 | 78,05 % |
| `2420E4488915M.xml` | appartement_issu_immeuble | pre_2026 | 316 | 89 | 71,84 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 89 | 68,55 % |
| `2675E2152874Y.xml` | appartement_issu_immeuble | post_2026 | 335 | 89 | 73,43 % |
| `2400E0333876N.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333878P.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333880R.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333883U.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333885W.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333888Z.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |
| `2400E0333891C.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |

## Cas totalement conformes

_Aucun._
