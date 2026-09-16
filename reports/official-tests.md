# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-16T08:30:28+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `a078bdd` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 363
Exécutés                 : 363
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 363

Valeurs comparées        : 115 811
Exactes                  : 87 325
Dans tolérance           : 16 085
Hors tolérance           : 10 394
Balises manquantes       : 107
Balises supplémentaires  : 1 858
Écarts non numériques    : 42

Conformité               : 89,29 %
```

Sur ces écarts, **3 265 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,11 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 45129 | 43426 | 1324 | 252 | 7 | 120 | 99,16 % |
| Ventilation | 2485 | 1609 | 864 | 12 | 0 | 0 | 99,52 % |
| Apports | 11550 | 9975 | 1014 | 278 | 0 | 283 | 95,14 % |
| Besoin chauffage | 1780 | 14 | 1617 | 145 | 4 | 0 | 91,63 % |
| Besoin ECS | 3443 | 3243 | 187 | 13 | 0 | 0 | 99,62 % |
| Génération chauffage | 7273 | 2914 | 2630 | 1679 | 26 | 24 | 76,23 % |
| Génération ECS | 7633 | 4809 | 1596 | 1199 | 14 | 15 | 83,91 % |
| Auxiliaires | 12342 | 7851 | 2344 | 2147 | 0 | 0 | 82,60 % |
| Froid | 4496 | 4165 | 28 | 247 | 56 | 0 | 93,26 % |
| PV | 2547 | 2538 | 0 | 9 | 0 | 0 | 99,65 % |
| Sorties énergie finale | 2385 | 1249 | 651 | 485 | 0 | 0 | 79,66 % |
| Sorties énergie primaire | 2904 | 1349 | 851 | 681 | 0 | 0 | 75,76 % |
| GES | 4848 | 2291 | 1432 | 1106 | 0 | 0 | 76,79 % |
| Coûts | 4122 | 861 | 1370 | 1891 | 0 | 0 | 54,12 % |
| Confort d'été | 2091 | 580 | 0 | 95 | 0 | 1416 | 27,74 % |
| Autre | 783 | 451 | 177 | 155 | 0 | 0 | 80,20 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61242 | 10941 | 5410 | 22 | 90,95 % |
| appartement_issu_immeuble | 47 | 16194 | 10934 | 2350 | 2572 | 34 | 81,79 % |
| maison_individuelle | 39 | 10872 | 8118 | 1323 | 1352 | 23 | 86,53 % |
| appartement_individuel | 41 | 9619 | 7031 | 1471 | 1060 | 28 | 88,01 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 284 | 87921 | 66991 | 12606 | 6671 | 67 | 90,24 % |
| pre_2026 | 79 | 27890 | 20334 | 3479 | 3723 | 40 | 85,14 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 273 | 85109 | 65355 | 12059 | 6148 | 15 | 90,67 % |
| 3cl_tribu_1.4.25.1 | 64 | 23040 | 16831 | 2864 | 3056 | 24 | 85,24 % |
| 3cl_tribu_2024.6.1.0 | 8 | 3027 | 2279 | 413 | 299 | 0 | 88,70 % |
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
| 1 | Génération chauffage | `conso_ch` | 647 | 137 | 2 900,0 % | hors-tol×641 manquante×6 |
| 2 | Génération ECS | `conso_ecs` | 534 | 79 | 1 098,9 % | hors-tol×534 |
| 3 | Génération chauffage | `conso_ch_depensier` | 523 | 139 | 2 900,0 % | hors-tol×517 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 483 | 218 | 799,5 % | hors-tol×483 |
| 5 | Génération ECS | `conso_ecs_depensier` | 460 | 77 | 845,3 % | hors-tol×460 |
| 6 | GES | `emission_ges_5_usages` | 364 | 154 | 11 300,5 % | hors-tol×364 |
| 7 | Apports | `inertie_lourde` | 362 | 362 | 100,0 % | hors-tol×79 suppl×283 |
| 8 | Sorties énergie finale | `conso_5_usages` | 356 | 148 | 1 219,0 % | hors-tol×356 |
| 9 | Coûts | `cout_ecs_depensier` | 352 | 352 | 207,9 % | hors-tol×352 |
| 10 | Coûts | `cout_ch_depensier` | 351 | 351 | 268,0 % | hors-tol×351 |
| 11 | Coûts | `cout_ch` | 335 | 163 | 533,1 % | hors-tol×335 |
| 12 | Confort d'été | `isolation_toiture` | 320 | 320 | 100,0 % | hors-tol×36 suppl×284 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 317 | 317 | 200,0 % | hors-tol×34 suppl×283 |
| 14 | GES | `emission_ges_ch` | 299 | 144 | 810,8 % | hors-tol×299 |
| 15 | Confort d'été | `protection_solaire_exterieure` | 296 | 296 | 100,0 % | hors-tol×13 suppl×283 |
| 16 | Coûts | `cout_ecs` | 295 | 163 | 559,4 % | hors-tol×295 |
| 17 | Confort d'été | `aspect_traversant` | 295 | 295 | — | hors-tol×12 suppl×283 |
| 18 | Confort d'été | `brasseur_air` | 283 | 283 | — | suppl×283 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 211 | 211 | 346,7 % | hors-tol×211 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 161 | 161 | 225,0 % | hors-tol×161 |
| 21 | Génération ECS | `rendement_stockage` | 161 | 48 | 40,0 % | hors-tol×150 manquante×10 suppl×1 |
| 22 | Génération chauffage | `rendement_generation` | 153 | 122 | 39,1 % | hors-tol×128 suppl×22 manquante×3 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 144 | 144 | 199,4 % | hors-tol×144 |
| 24 | GES | `emission_ges_ch_depensier` | 143 | 143 | 154,0 % | hors-tol×143 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 136 | 136 | 199,4 % | hors-tol×136 |
| 26 | GES | `emission_ges_ecs` | 136 | 68 | 616,5 % | hors-tol×136 |
| 27 | Sorties énergie primaire | `ep_conso_ch` | 135 | 135 | 142,9 % | hors-tol×135 |
| 28 | Sorties énergie primaire | `ep_conso_ch_depensier` | 135 | 135 | 142,9 % | hors-tol×135 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 129 | 129 | 143,8 % | hors-tol×129 |
| 30 | Auxiliaires | `cout_auxiliaire_generation_ch` | 111 | 111 | 100,0 % | hors-tol×111 |
| 31 | Auxiliaires | `cout_total_auxiliaire` | 111 | 111 | 3 213,8 % | hors-tol×111 |
| 32 | Auxiliaires | `conso_auxiliaire_generation_ch` | 97 | 97 | 100,0 % | hors-tol×97 |
| 33 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 97 | 97 | 100,0 % | hors-tol×97 |
| 34 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 97 | 97 | 100,0 % | hors-tol×97 |
| 35 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 97 | 97 | 100,0 % | hors-tol×97 |
| 36 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 97 | 97 | 100,0 % | hors-tol×97 |
| 37 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 97 | 97 | 100,0 % | hors-tol×97 |
| 38 | Génération chauffage | `qp0` | 95 | 83 | 205 028,2 % | hors-tol×94 manquante×1 |
| 39 | Génération chauffage | `pn` | 93 | 79 | 1 328,6 % | hors-tol×89 manquante×4 |
| 40 | GES | `emission_ges_5_usages_m2` | 90 | 90 | 152,9 % | hors-tol×90 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 272 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 272 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 272 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 272 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 272 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 272 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 11 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 11 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 18 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 301 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 301 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 144 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
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
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 145 | 75,38 % |
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 142 | 84,29 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 848 | 125 | 85,26 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2600E0035103I.xml` | immeuble_collectif | post_2026 | 642 | 116 | 81,93 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 113 | 56,54 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 100 | 78,59 % |
| `2400E0636882P.xml` | immeuble_collectif | pre_2026 | 415 | 100 | 75,90 % |
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
| `2400E0333892D.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |

## Cas totalement conformes

_Aucun._
