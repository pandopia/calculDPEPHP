# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-29T21:12:28+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `f10fe9e` — arbre de travail modifié : 2 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 386
Exécutés                 : 386
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 386

Valeurs comparées        : 122 557
Exactes                  : 92 652
Dans tolérance           : 18 201
Hors tolérance           : 9 538
Balises manquantes       : 147
Balises supplémentaires  : 1 982
Écarts non numériques    : 37

Conformité               : 90,45 %
```

Sur ces écarts, **3 418 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,24 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 47505 | 45671 | 1407 | 285 | 9 | 133 | 99,10 % |
| Ventilation | 2642 | 1707 | 905 | 28 | 1 | 1 | 98,86 % |
| Apports | 12170 | 10564 | 1070 | 236 | 0 | 300 | 95,60 % |
| Besoin chauffage | 1898 | 14 | 1733 | 147 | 4 | 0 | 92,04 % |
| Besoin ECS | 3638 | 3419 | 201 | 18 | 0 | 0 | 99,51 % |
| Génération chauffage | 7796 | 3308 | 3194 | 1237 | 26 | 31 | 83,40 % |
| Génération ECS | 8014 | 5095 | 1664 | 1223 | 16 | 16 | 84,34 % |
| Auxiliaires | 13124 | 8348 | 2478 | 2297 | 0 | 1 | 82,49 % |
| Froid | 4808 | 4428 | 29 | 260 | 84 | 7 | 92,70 % |
| PV | 2717 | 2702 | 0 | 9 | 6 | 0 | 99,45 % |
| Sorties énergie finale | 2536 | 1372 | 790 | 372 | 0 | 2 | 85,25 % |
| Sorties énergie primaire | 3088 | 1474 | 1060 | 532 | 0 | 1 | 82,06 % |
| GES | 5155 | 2455 | 1794 | 887 | 0 | 3 | 82,42 % |
| Coûts | 4383 | 922 | 1669 | 1789 | 0 | 3 | 59,11 % |
| Confort d'été | 2216 | 648 | 0 | 87 | 0 | 1481 | 29,24 % |
| Autre | 867 | 525 | 207 | 131 | 1 | 3 | 84,43 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 237 | 79455 | 61691 | 11810 | 4415 | 26 | 92,23 % |
| appartement_issu_immeuble | 59 | 20138 | 14016 | 3156 | 2539 | 49 | 85,02 % |
| maison_individuelle | 43 | 12037 | 9029 | 1589 | 1326 | 36 | 87,90 % |
| appartement_individuel | 47 | 10927 | 7916 | 1646 | 1258 | 36 | 87,13 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 289 | 89223 | 68273 | 13717 | 5581 | 75 | 91,60 % |
| pre_2026 | 97 | 33334 | 24379 | 4484 | 3957 | 72 | 86,34 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 276 | 85799 | 66179 | 13039 | 5046 | 14 | 92,03 % |
| 3cl_tribu_1.4.25.1 | 69 | 24463 | 17970 | 3461 | 2700 | 40 | 87,36 % |
| 3cl_tribu_2024.6.1.0 | 15 | 5446 | 4173 | 715 | 484 | 0 | 89,51 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| inconnu | 6 | 1673 | 1108 | 329 | 161 | 32 | 85,59 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 684 | 137 | 107 | 0 | 87,34 % |
| 3cl-2024.6.1.0 | 3 | 644 | 440 | 81 | 107 | 12 | 80,53 % |
| 3cl_bbs_V2024.6.1.0 | 1 | 329 | 208 | 35 | 74 | 4 | 73,64 % |
| V 2024.6.1.0 | 1 | 297 | 196 | 7 | 86 | 4 | 68,12 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 186 | 33 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 164 | 1 | 94 | 0 | 63,22 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |
| 3cl_tribu_1.4.23.7 | 1 | 213 | 125 | 6 | 82 | 0 | 61,21 % |
| 3cl_tribu_1.4.25 | 1 | 204 | 114 | 7 | 81 | 0 | 59,02 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 547 | 86 | 104,8 % | hors-tol×546 suppl×1 |
| 2 | Génération chauffage | `conso_ch` | 484 | 95 | 2 900,0 % | hors-tol×477 manquante×6 suppl×1 |
| 3 | Génération ECS | `conso_ecs_depensier` | 466 | 85 | 103,4 % | hors-tol×466 |
| 4 | Coûts | `cout_5_usages` | 428 | 216 | 882,3 % | hors-tol×427 suppl×1 |
| 5 | Génération chauffage | `conso_ch_depensier` | 408 | 97 | 2 900,0 % | hors-tol×402 manquante×6 |
| 6 | Coûts | `cout_ecs_depensier` | 374 | 374 | 122,3 % | hors-tol×374 |
| 7 | Coûts | `cout_ch_depensier` | 372 | 372 | 268,0 % | hors-tol×372 |
| 8 | Confort d'été | `isolation_toiture` | 339 | 339 | 100,0 % | hors-tol×42 suppl×297 |
| 9 | Confort d'été | `protection_solaire_exterieure` | 312 | 312 | 100,0 % | hors-tol×16 suppl×296 |
| 10 | Confort d'été | `aspect_traversant` | 311 | 311 | — | hors-tol×15 suppl×296 |
| 11 | Confort d'été | `enum_indicateur_confort_ete_id` | 309 | 309 | 200,0 % | hors-tol×13 suppl×296 |
| 12 | Apports | `inertie_lourde` | 298 | 298 | — | suppl×297 hors-tol×1 |
| 13 | Confort d'été | `brasseur_air` | 297 | 297 | 100,0 % | suppl×296 hors-tol×1 |
| 14 | GES | `emission_ges_5_usages` | 290 | 134 | 19 178,9 % | hors-tol×289 suppl×1 |
| 15 | Coûts | `cout_ecs` | 285 | 159 | 73,4 % | hors-tol×284 suppl×1 |
| 16 | Sorties énergie finale | `conso_5_usages` | 283 | 127 | 1 016 150,4 % | hors-tol×282 suppl×1 |
| 17 | Coûts | `cout_ch` | 253 | 123 | 534,4 % | hors-tol×252 suppl×1 |
| 18 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 221 | 221 | 352,3 % | hors-tol×221 |
| 19 | GES | `emission_ges_ch` | 201 | 94 | 9 092,3 % | hors-tol×200 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 170 | 170 | 141,7 % | hors-tol×170 |
| 21 | Génération ECS | `rendement_stockage` | 167 | 53 | 40,0 % | hors-tol×154 manquante×13 |
| 22 | GES | `emission_ges_ecs` | 151 | 75 | 8 232,9 % | hors-tol×150 suppl×1 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 123 | 123 | 3 213,8 % | hors-tol×123 |
| 24 | Génération chauffage | `rendement_generation` | 123 | 83 | 36,2 % | hors-tol×96 suppl×24 manquante×3 |
| 25 | Auxiliaires | `cout_auxiliaire_generation_ch` | 117 | 117 | 100,0 % | hors-tol×117 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages` | 106 | 106 | 519,8 % | hors-tol×106 |
| 27 | Auxiliaires | `conso_auxiliaire_generation_ch` | 101 | 101 | 100,0 % | hors-tol×101 |
| 28 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 101 | 101 | 100,0 % | hors-tol×101 |
| 29 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 101 | 101 | 100,0 % | hors-tol×101 |
| 30 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 101 | 101 | 100,0 % | hors-tol×101 |
| 31 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 101 | 101 | 100,0 % | hors-tol×101 |
| 32 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 101 | 101 | 170,4 % | hors-tol×101 |
| 33 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 98 | 98 | 519,0 % | hors-tol×98 |
| 34 | GES | `emission_ges_ch_depensier` | 93 | 93 | 9 093,8 % | hors-tol×93 |
| 35 | Génération chauffage | `qp0` | 90 | 83 | 205 028,2 % | hors-tol×88 manquante×1 suppl×1 |
| 36 | Sorties énergie primaire | `ep_conso_ch` | 89 | 89 | 100,0 % | hors-tol×89 |
| 37 | Sorties énergie primaire | `ep_conso_ch_depensier` | 89 | 89 | 100,0 % | hors-tol×89 |
| 38 | Sorties énergie finale | `conso_5_usages_m2` | 88 | 88 | 242,6 % | hors-tol×88 |
| 39 | Génération chauffage | `pn` | 85 | 76 | 1 328,6 % | hors-tol×80 manquante×4 suppl×1 |
| 40 | Auxiliaires | `conso_totale_auxiliaire` | 83 | 83 | 39 702,7 % | hors-tol×83 |

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
| `cout_ecs_depensier` | 314 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 314 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 150 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 186 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `emission_ges_totale_auxiliaire` | 11 | la référence publie un total auxiliaire de 6.6, incompatible avec la somme de ses postes (20.3) |
| `cout_total_auxiliaire` | 14 | la référence publie un total auxiliaire de 25.9, incompatible avec la somme de ses postes (79.5) |
| `ep_conso_totale_auxiliaire` | 11 | la référence publie un total auxiliaire de 195.9, incompatible avec la somme de ses postes (601.9) |
| `conso_totale_auxiliaire` | 11 | la référence publie un total auxiliaire de 103.1, incompatible avec la somme de ses postes (316.8) |
| `besoin_ch_depensier` | 12 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `conso_ecs` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 157 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 28 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 3 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 142 | 84,29 % |
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 142 | 45,80 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 848 | 125 | 85,26 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 111 | 63,84 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 99 | 78,80 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 97 | 62,11 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 95 | 63,46 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 94 | 84,04 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 297 | 94 | 68,35 % |
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
| `2206E1733496F.xml` | appartement_individuel | pre_2026 | 204 | 83 | 59,31 % |
| `2230E1068756F.xml` | appartement_individuel | pre_2026 | 213 | 82 | 61,50 % |
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 323 | 82 | 74,61 % |

## Cas totalement conformes

_Aucun._
