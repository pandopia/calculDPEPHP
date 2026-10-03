# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-03T14:23:34+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `dc612ac`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 389
Exécutés                 : 389
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 389

Valeurs comparées        : 123 253
Exactes                  : 93 211
Dans tolérance           : 18 551
Hors tolérance           : 9 330
Balises manquantes       : 144
Balises supplémentaires  : 1 982
Écarts non numériques    : 35

Conformité               : 90,68 %
```

Sur ces écarts, **3 437 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,47 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 47710 | 45900 | 1412 | 256 | 9 | 133 | 99,17 % |
| Ventilation | 2662 | 1723 | 908 | 29 | 1 | 1 | 98,84 % |
| Apports | 12225 | 10609 | 1081 | 235 | 0 | 300 | 95,62 % |
| Besoin chauffage | 1910 | 12 | 1747 | 147 | 4 | 0 | 92,09 % |
| Besoin ECS | 3657 | 3438 | 201 | 18 | 0 | 0 | 99,51 % |
| Génération chauffage | 7850 | 3337 | 3281 | 1182 | 19 | 31 | 84,31 % |
| Génération ECS | 8041 | 5114 | 1681 | 1214 | 16 | 16 | 84,50 % |
| Auxiliaires | 13226 | 8407 | 2557 | 2261 | 0 | 1 | 82,90 % |
| Froid | 4848 | 4464 | 29 | 260 | 88 | 7 | 92,68 % |
| PV | 2738 | 2723 | 0 | 9 | 6 | 0 | 99,45 % |
| Sorties énergie finale | 2557 | 1384 | 803 | 368 | 0 | 2 | 85,53 % |
| Sorties énergie primaire | 3112 | 1489 | 1088 | 514 | 0 | 1 | 82,81 % |
| GES | 5197 | 2477 | 1842 | 860 | 0 | 3 | 83,11 % |
| Coûts | 4419 | 931 | 1707 | 1778 | 0 | 3 | 59,70 % |
| Confort d'été | 2231 | 678 | 0 | 72 | 0 | 1481 | 30,39 % |
| Autre | 870 | 525 | 214 | 127 | 1 | 3 | 84,94 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 237 | 79455 | 61696 | 11832 | 4388 | 26 | 92,27 % |
| appartement_issu_immeuble | 59 | 20138 | 14015 | 3169 | 2527 | 49 | 85,08 % |
| maison_individuelle | 44 | 12255 | 9201 | 1679 | 1287 | 33 | 88,46 % |
| appartement_individuel | 49 | 11405 | 8299 | 1871 | 1128 | 36 | 88,79 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 291 | 89660 | 68592 | 13855 | 5557 | 79 | 91,66 % |
| pre_2026 | 98 | 33593 | 24619 | 4696 | 3773 | 65 | 87,01 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 277 | 86018 | 66339 | 13129 | 5015 | 14 | 92,09 % |
| 3cl_tribu_1.4.25.1 | 69 | 24463 | 17993 | 3559 | 2588 | 33 | 87,85 % |
| 3cl_tribu_2024.6.1.0 | 16 | 5705 | 4379 | 779 | 473 | 0 | 90,16 % |
| inconnu | 7 | 1891 | 1269 | 377 | 166 | 36 | 86,72 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 684 | 137 | 107 | 0 | 87,34 % |
| 3cl-2024.6.1.0 | 3 | 644 | 441 | 81 | 106 | 12 | 80,68 % |
| 3cl_bbs_V2024.6.1.0 | 1 | 329 | 208 | 35 | 74 | 4 | 73,64 % |
| V 2024.6.1.0 | 1 | 297 | 196 | 7 | 86 | 4 | 68,12 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 186 | 33 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 169 | 1 | 89 | 0 | 65,13 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |
| 3cl_tribu_1.4.23.7 | 1 | 213 | 127 | 31 | 55 | 0 | 73,83 % |
| 3cl_tribu_1.4.25 | 1 | 204 | 115 | 32 | 55 | 0 | 71,71 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 543 | 85 | 104,8 % | hors-tol×542 suppl×1 |
| 2 | Génération chauffage | `conso_ch` | 464 | 90 | 2 900,0 % | hors-tol×457 manquante×6 suppl×1 |
| 3 | Génération ECS | `conso_ecs_depensier` | 463 | 84 | 103,4 % | hors-tol×463 |
| 4 | Coûts | `cout_5_usages` | 423 | 217 | 936,3 % | hors-tol×422 suppl×1 |
| 5 | Génération chauffage | `conso_ch_depensier` | 393 | 92 | 2 900,0 % | hors-tol×387 manquante×6 |
| 6 | Coûts | `cout_ecs_depensier` | 376 | 376 | 122,3 % | hors-tol×376 |
| 7 | Coûts | `cout_ch_depensier` | 375 | 375 | 268,0 % | hors-tol×375 |
| 8 | Confort d'été | `enum_indicateur_confort_ete_id` | 328 | 328 | 100,0 % | hors-tol×32 suppl×296 |
| 9 | Confort d'été | `aspect_traversant` | 312 | 312 | — | hors-tol×16 suppl×296 |
| 10 | Confort d'été | `protection_solaire_exterieure` | 312 | 312 | 100,0 % | hors-tol×16 suppl×296 |
| 11 | Confort d'été | `isolation_toiture` | 304 | 304 | 100,0 % | hors-tol×7 suppl×297 |
| 12 | Apports | `inertie_lourde` | 298 | 298 | — | suppl×297 hors-tol×1 |
| 13 | Confort d'été | `brasseur_air` | 297 | 297 | 100,0 % | suppl×296 hors-tol×1 |
| 14 | GES | `emission_ges_5_usages` | 285 | 132 | 20 316,2 % | hors-tol×284 suppl×1 |
| 15 | Coûts | `cout_ecs` | 281 | 157 | 73,4 % | hors-tol×280 suppl×1 |
| 16 | Sorties énergie finale | `conso_5_usages` | 279 | 125 | 1 016 150,4 % | hors-tol×278 suppl×1 |
| 17 | Coûts | `cout_ch` | 246 | 120 | 534,4 % | hors-tol×245 suppl×1 |
| 18 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 223 | 223 | 407,5 % | hors-tol×223 |
| 19 | GES | `emission_ges_ch` | 191 | 89 | 9 092,3 % | hors-tol×190 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 171 | 171 | 141,7 % | hors-tol×171 |
| 21 | Génération ECS | `rendement_stockage` | 167 | 53 | 40,0 % | hors-tol×154 manquante×13 |
| 22 | GES | `emission_ges_ecs` | 149 | 74 | 8 232,9 % | hors-tol×148 suppl×1 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 124 | 124 | 3 352,7 % | hors-tol×124 |
| 24 | Génération chauffage | `rendement_generation` | 118 | 78 | 30,7 % | suppl×24 hors-tol×92 manquante×2 |
| 25 | Auxiliaires | `cout_auxiliaire_generation_ch` | 114 | 114 | 442,0 % | hors-tol×114 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages` | 103 | 103 | 551,8 % | hors-tol×103 |
| 27 | Auxiliaires | `conso_auxiliaire_generation_ch` | 95 | 95 | 457,0 % | hors-tol×95 |
| 28 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 95 | 95 | 457,0 % | hors-tol×95 |
| 29 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 95 | 95 | 457,0 % | hors-tol×95 |
| 30 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 95 | 95 | 457,0 % | hors-tol×95 |
| 31 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 95 | 95 | 551,2 % | hors-tol×95 |
| 32 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 95 | 95 | 457,0 % | hors-tol×95 |
| 33 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 95 | 95 | 457,0 % | hors-tol×95 |
| 34 | Sorties énergie finale | `conso_5_usages_m2` | 88 | 88 | 257,3 % | hors-tol×88 |
| 35 | GES | `emission_ges_ch_depensier` | 88 | 88 | 9 093,8 % | hors-tol×88 |
| 36 | Génération chauffage | `qp0` | 85 | 78 | 205 028,2 % | hors-tol×84 suppl×1 |
| 37 | Sorties énergie primaire | `ep_conso_ch` | 84 | 84 | 100,0 % | hors-tol×84 |
| 38 | Sorties énergie primaire | `ep_conso_ch_depensier` | 84 | 84 | 100,0 % | hors-tol×84 |
| 39 | Auxiliaires | `conso_totale_auxiliaire` | 83 | 83 | 42 072,2 % | hors-tol×83 |
| 40 | Auxiliaires | `emission_ges_totale_auxiliaire` | 83 | 83 | 41 008,1 % | hors-tol×83 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 282 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 282 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 282 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 282 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 282 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 282 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 14 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 16 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 19 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 315 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 315 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 150 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 187 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `emission_ges_totale_auxiliaire` | 11 | la référence publie un total auxiliaire de 6.6, incompatible avec la somme de ses postes (20.3) |
| `cout_total_auxiliaire` | 14 | la référence publie un total auxiliaire de 25.9, incompatible avec la somme de ses postes (79.5) |
| `ep_conso_totale_auxiliaire` | 11 | la référence publie un total auxiliaire de 195.9, incompatible avec la somme de ses postes (601.9) |
| `conso_totale_auxiliaire` | 11 | la référence publie un total auxiliaire de 103.1, incompatible avec la somme de ses postes (316.8) |
| `besoin_ch_depensier` | 12 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 7 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 7 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `k` | 15 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `conso_ecs` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 157 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 27 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 3 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 142 | 84,29 % |
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 140 | 46,56 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 848 | 125 | 85,26 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 111 | 63,84 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 99 | 78,80 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 97 | 62,11 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 94 | 84,04 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 297 | 94 | 68,35 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 90 | 65,38 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 90 | 68,20 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2268184I.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 89 | 68,55 % |
| `2675E2152874Y.xml` | appartement_issu_immeuble | post_2026 | 335 | 89 | 73,43 % |
| `2675E0942311V.xml` | immeuble_collectif | post_2026 | 410 | 88 | 78,54 % |
| `2459E4183923N.xml` | immeuble_collectif | pre_2026 | 329 | 86 | 73,86 % |
| `2400E0124709Q.xml` | maison_individuelle | pre_2026 | 320 | 84 | 73,75 % |
| `2675E0021756W.xml` | immeuble_collectif | post_2026 | 356 | 84 | 76,40 % |
| `2600E0035103I.xml` | immeuble_collectif | post_2026 | 642 | 83 | 87,07 % |
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 323 | 82 | 74,61 % |
| `2600E0025586H.xml` | maison_individuelle | post_2026 | 354 | 81 | 77,12 % |
| `2612E0854137C.xml` | immeuble_collectif | post_2026 | 274 | 80 | 70,80 % |

## Cas totalement conformes

_Aucun._
