# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-17T06:37:53+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `6472b2d`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 373
Exécutés                 : 373
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 373

Valeurs comparées        : 119 089
Exactes                  : 89 830
Dans tolérance           : 16 534
Hors tolérance           : 10 640
Balises manquantes       : 122
Balises supplémentaires  : 1 914
Écarts non numériques    : 49

Conformité               : 89,31 %
```

Sur ces écarts, **3 344 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,12 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 46309 | 44568 | 1357 | 255 | 7 | 122 | 99,17 % |
| Ventilation | 2555 | 1645 | 888 | 22 | 0 | 0 | 99,14 % |
| Apports | 11857 | 10309 | 1041 | 215 | 0 | 292 | 95,72 % |
| Besoin chauffage | 1846 | 14 | 1681 | 147 | 4 | 0 | 91,82 % |
| Besoin ECS | 3539 | 3331 | 193 | 15 | 0 | 0 | 99,58 % |
| Génération chauffage | 7596 | 3095 | 2732 | 1718 | 26 | 25 | 76,71 % |
| Génération ECS | 7835 | 4951 | 1625 | 1230 | 15 | 14 | 83,93 % |
| Auxiliaires | 12682 | 8058 | 2364 | 2260 | 0 | 0 | 82,18 % |
| Froid | 4628 | 4284 | 29 | 251 | 64 | 0 | 93,19 % |
| PV | 2623 | 2608 | 0 | 9 | 6 | 0 | 99,43 % |
| Sorties énergie finale | 2447 | 1282 | 666 | 499 | 0 | 0 | 79,61 % |
| Sorties énergie primaire | 2984 | 1376 | 875 | 706 | 0 | 0 | 75,44 % |
| GES | 4976 | 2330 | 1472 | 1152 | 0 | 0 | 76,41 % |
| Coûts | 4230 | 881 | 1417 | 1932 | 0 | 0 | 54,33 % |
| Confort d'été | 2148 | 615 | 0 | 72 | 0 | 1461 | 28,63 % |
| Autre | 834 | 483 | 194 | 157 | 0 | 0 | 81,18 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61264 | 11028 | 5303 | 22 | 91,09 % |
| appartement_issu_immeuble | 56 | 19254 | 13123 | 2620 | 3093 | 49 | 81,53 % |
| maison_individuelle | 39 | 10872 | 8167 | 1333 | 1293 | 23 | 87,07 % |
| appartement_individuel | 42 | 9837 | 7276 | 1553 | 951 | 28 | 89,37 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 285 | 88138 | 67263 | 12725 | 6499 | 66 | 90,46 % |
| pre_2026 | 88 | 30951 | 22567 | 3809 | 4141 | 56 | 84,98 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 274 | 85326 | 65622 | 12178 | 5981 | 14 | 90,89 % |
| 3cl_tribu_1.4.25.1 | 67 | 23980 | 17486 | 2989 | 3179 | 40 | 85,15 % |
| 3cl_tribu_2024.6.1.0 | 13 | 4851 | 3631 | 609 | 541 | 0 | 87,17 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 677 | 96 | 155 | 0 | 82,23 % |
| inconnu | 3 | 799 | 536 | 197 | 50 | 15 | 91,40 % |
| 3cl-2024.6.1.0 | 3 | 644 | 440 | 81 | 107 | 12 | 80,53 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 186 | 33 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 147 | 1 | 111 | 0 | 56,70 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 661 | 139 | 2 900,0 % | hors-tol×655 manquante×6 |
| 2 | Génération ECS | `conso_ecs` | 550 | 84 | 1 098,9 % | hors-tol×550 |
| 3 | Génération chauffage | `conso_ch_depensier` | 540 | 140 | 2 900,0 % | hors-tol×534 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 497 | 225 | 882,3 % | hors-tol×497 |
| 5 | Génération ECS | `conso_ecs_depensier` | 471 | 82 | 845,3 % | hors-tol×471 |
| 6 | GES | `emission_ges_5_usages` | 378 | 162 | 19 178,9 % | hors-tol×378 |
| 7 | Sorties énergie finale | `conso_5_usages` | 368 | 155 | 19 918,0 % | hors-tol×368 |
| 8 | Coûts | `cout_ecs_depensier` | 362 | 362 | 207,9 % | hors-tol×362 |
| 9 | Coûts | `cout_ch_depensier` | 361 | 361 | 268,0 % | hors-tol×361 |
| 10 | Coûts | `cout_ch` | 338 | 165 | 533,1 % | hors-tol×338 |
| 11 | Confort d'été | `isolation_toiture` | 329 | 329 | 100,0 % | hors-tol×36 suppl×293 |
| 12 | Confort d'été | `protection_solaire_exterieure` | 305 | 305 | 100,0 % | hors-tol×13 suppl×292 |
| 13 | Confort d'été | `aspect_traversant` | 304 | 304 | — | hors-tol×12 suppl×292 |
| 14 | Confort d'été | `enum_indicateur_confort_ete_id` | 303 | 303 | 100,0 % | hors-tol×11 suppl×292 |
| 15 | GES | `emission_ges_ch` | 302 | 147 | 810,8 % | hors-tol×302 |
| 16 | Coûts | `cout_ecs` | 297 | 165 | 559,4 % | hors-tol×297 |
| 17 | Apports | `inertie_lourde` | 293 | 293 | — | suppl×292 hors-tol×1 |
| 18 | Confort d'été | `brasseur_air` | 292 | 292 | — | suppl×292 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 215 | 215 | 346,7 % | hors-tol×215 |
| 20 | Génération chauffage | `rendement_generation` | 166 | 126 | 39,1 % | hors-tol×140 suppl×23 manquante×3 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 165 | 165 | 225,0 % | hors-tol×165 |
| 22 | Génération ECS | `rendement_stockage` | 165 | 52 | 40,5 % | hors-tol×153 manquante×12 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 151 | 151 | 519,8 % | hors-tol×151 |
| 24 | GES | `emission_ges_ecs` | 150 | 75 | 616,5 % | hors-tol×150 |
| 25 | GES | `emission_ges_ch_depensier` | 146 | 146 | 154,0 % | hors-tol×146 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 140 | 140 | 519,0 % | hors-tol×140 |
| 27 | Sorties énergie primaire | `ep_conso_ch` | 136 | 136 | 142,9 % | hors-tol×136 |
| 28 | Sorties énergie primaire | `ep_conso_ch_depensier` | 136 | 136 | 142,9 % | hors-tol×136 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 129 | 129 | 242,6 % | hors-tol×129 |
| 30 | Auxiliaires | `cout_total_auxiliaire` | 118 | 118 | 3 213,8 % | hors-tol×118 |
| 31 | Auxiliaires | `cout_auxiliaire_generation_ch` | 115 | 115 | 100,0 % | hors-tol×115 |
| 32 | Auxiliaires | `conso_auxiliaire_generation_ch` | 101 | 101 | 100,0 % | hors-tol×101 |
| 33 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 101 | 101 | 100,0 % | hors-tol×101 |
| 34 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 101 | 101 | 100,0 % | hors-tol×101 |
| 35 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 101 | 101 | 100,0 % | hors-tol×101 |
| 36 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 101 | 101 | 100,0 % | hors-tol×101 |
| 37 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 101 | 101 | 100,0 % | hors-tol×101 |
| 38 | Génération chauffage | `qp0` | 97 | 85 | 205 028,2 % | hors-tol×96 manquante×1 |
| 39 | GES | `emission_ges_5_usages_m2` | 93 | 93 | 152,9 % | hors-tol×93 |
| 40 | Génération chauffage | `pn` | 93 | 79 | 1 328,6 % | hors-tol×89 manquante×4 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 279 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 279 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 279 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 279 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 279 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 279 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 13 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 15 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 19 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 308 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 308 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 146 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 184 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `conso_totale_auxiliaire` | 10 | la référence publie un total auxiliaire de 103.1 kWh, incompatible avec la somme de ses postes (316.8 kWh) |
| `besoin_ch_depensier` | 10 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `conso_ecs` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 156 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
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
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 112 | 56,92 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 111 | 63,84 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 99 | 78,80 % |
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
