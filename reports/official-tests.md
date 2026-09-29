# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-29T21:24:19+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `727c24c` — arbre de travail modifié : 1 fichier(s) src/ non commités_

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
Exactes                  : 92 657
Dans tolérance           : 18 314
Hors tolérance           : 9 420
Balises manquantes       : 147
Balises supplémentaires  : 1 982
Écarts non numériques    : 37

Conformité               : 90,55 %
```

Sur ces écarts, **3 433 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,35 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 47505 | 45671 | 1407 | 285 | 9 | 133 | 99,10 % |
| Ventilation | 2642 | 1707 | 905 | 28 | 1 | 1 | 98,86 % |
| Apports | 12170 | 10563 | 1072 | 235 | 0 | 300 | 95,60 % |
| Besoin chauffage | 1898 | 12 | 1735 | 147 | 4 | 0 | 92,04 % |
| Besoin ECS | 3638 | 3419 | 201 | 18 | 0 | 0 | 99,51 % |
| Génération chauffage | 7796 | 3308 | 3231 | 1200 | 26 | 31 | 83,88 % |
| Génération ECS | 8014 | 5096 | 1672 | 1214 | 16 | 16 | 84,45 % |
| Auxiliaires | 13124 | 8350 | 2503 | 2270 | 0 | 1 | 82,70 % |
| Froid | 4808 | 4428 | 29 | 260 | 84 | 7 | 92,70 % |
| PV | 2717 | 2702 | 0 | 9 | 6 | 0 | 99,45 % |
| Sorties énergie finale | 2536 | 1373 | 792 | 369 | 0 | 2 | 85,37 % |
| Sorties énergie primaire | 3088 | 1476 | 1070 | 520 | 0 | 1 | 82,45 % |
| GES | 5155 | 2457 | 1808 | 871 | 0 | 3 | 82,74 % |
| Coûts | 4383 | 922 | 1678 | 1780 | 0 | 3 | 59,32 % |
| Confort d'été | 2216 | 648 | 0 | 87 | 0 | 1481 | 29,24 % |
| Autre | 867 | 525 | 211 | 127 | 1 | 3 | 84,89 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 237 | 79455 | 61691 | 11810 | 4415 | 26 | 92,23 % |
| appartement_issu_immeuble | 59 | 20138 | 14016 | 3156 | 2539 | 49 | 85,02 % |
| maison_individuelle | 43 | 12037 | 9029 | 1589 | 1326 | 36 | 87,90 % |
| appartement_individuel | 47 | 10927 | 7921 | 1759 | 1140 | 36 | 88,21 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 289 | 89223 | 68273 | 13724 | 5574 | 75 | 91,60 % |
| pre_2026 | 97 | 33334 | 24384 | 4590 | 3846 | 72 | 86,67 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 276 | 85799 | 66179 | 13046 | 5039 | 14 | 92,04 % |
| 3cl_tribu_1.4.25.1 | 69 | 24463 | 17975 | 3517 | 2639 | 40 | 87,61 % |
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
| 3cl_tribu_1.4.23.7 | 1 | 213 | 125 | 31 | 57 | 0 | 72,90 % |
| 3cl_tribu_1.4.25 | 1 | 204 | 114 | 32 | 56 | 0 | 71,22 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 543 | 85 | 104,8 % | hors-tol×542 suppl×1 |
| 2 | Génération chauffage | `conso_ch` | 472 | 92 | 2 900,0 % | hors-tol×465 manquante×6 suppl×1 |
| 3 | Génération ECS | `conso_ecs_depensier` | 463 | 84 | 103,4 % | hors-tol×463 |
| 4 | Coûts | `cout_5_usages` | 425 | 216 | 882,3 % | hors-tol×424 suppl×1 |
| 5 | Génération chauffage | `conso_ch_depensier` | 399 | 94 | 2 900,0 % | hors-tol×393 manquante×6 |
| 6 | Coûts | `cout_ecs_depensier` | 374 | 374 | 122,3 % | hors-tol×374 |
| 7 | Coûts | `cout_ch_depensier` | 372 | 372 | 268,0 % | hors-tol×372 |
| 8 | Confort d'été | `isolation_toiture` | 339 | 339 | 100,0 % | hors-tol×42 suppl×297 |
| 9 | Confort d'été | `protection_solaire_exterieure` | 312 | 312 | 100,0 % | hors-tol×16 suppl×296 |
| 10 | Confort d'été | `aspect_traversant` | 311 | 311 | — | hors-tol×15 suppl×296 |
| 11 | Confort d'été | `enum_indicateur_confort_ete_id` | 309 | 309 | 200,0 % | hors-tol×13 suppl×296 |
| 12 | Apports | `inertie_lourde` | 298 | 298 | — | suppl×297 hors-tol×1 |
| 13 | Confort d'été | `brasseur_air` | 297 | 297 | 100,0 % | suppl×296 hors-tol×1 |
| 14 | GES | `emission_ges_5_usages` | 288 | 133 | 19 178,9 % | hors-tol×287 suppl×1 |
| 15 | Coûts | `cout_ecs` | 283 | 158 | 73,4 % | hors-tol×282 suppl×1 |
| 16 | Sorties énergie finale | `conso_5_usages` | 281 | 126 | 1 016 150,4 % | hors-tol×280 suppl×1 |
| 17 | Coûts | `cout_ch` | 249 | 121 | 534,4 % | hors-tol×248 suppl×1 |
| 18 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 221 | 221 | 352,3 % | hors-tol×221 |
| 19 | GES | `emission_ges_ch` | 195 | 91 | 9 092,3 % | hors-tol×194 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 170 | 170 | 141,7 % | hors-tol×170 |
| 21 | Génération ECS | `rendement_stockage` | 167 | 53 | 40,0 % | hors-tol×154 manquante×13 |
| 22 | GES | `emission_ges_ecs` | 149 | 74 | 8 232,9 % | hors-tol×148 suppl×1 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 123 | 123 | 3 213,8 % | hors-tol×123 |
| 24 | Génération chauffage | `rendement_generation` | 120 | 80 | 36,2 % | suppl×24 manquante×3 hors-tol×93 |
| 25 | Auxiliaires | `cout_auxiliaire_generation_ch` | 116 | 116 | 100,0 % | hors-tol×116 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages` | 104 | 104 | 519,8 % | hors-tol×104 |
| 27 | Auxiliaires | `conso_auxiliaire_generation_ch` | 98 | 98 | 100,0 % | hors-tol×98 |
| 28 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 98 | 98 | 100,0 % | hors-tol×98 |
| 29 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 98 | 98 | 100,0 % | hors-tol×98 |
| 30 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 98 | 98 | 100,0 % | hors-tol×98 |
| 31 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 98 | 98 | 100,0 % | hors-tol×98 |
| 32 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 98 | 98 | 170,4 % | hors-tol×98 |
| 33 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 96 | 96 | 519,0 % | hors-tol×96 |
| 34 | GES | `emission_ges_ch_depensier` | 90 | 90 | 9 093,8 % | hors-tol×90 |
| 35 | Sorties énergie finale | `conso_5_usages_m2` | 87 | 87 | 242,6 % | hors-tol×87 |
| 36 | Génération chauffage | `qp0` | 87 | 80 | 205 028,2 % | manquante×1 hors-tol×85 suppl×1 |
| 37 | Sorties énergie primaire | `ep_conso_ch` | 86 | 86 | 100,0 % | hors-tol×86 |
| 38 | Sorties énergie primaire | `ep_conso_ch_depensier` | 86 | 86 | 100,0 % | hors-tol×86 |
| 39 | Auxiliaires | `conso_totale_auxiliaire` | 83 | 83 | 39 702,7 % | hors-tol×83 |
| 40 | Auxiliaires | `emission_ges_totale_auxiliaire` | 83 | 83 | 38 698,4 % | hors-tol×83 |

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
| `k` | 15 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
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
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 323 | 82 | 74,61 % |
| `2600E0006037K.xml` | appartement_individuel | post_2026 | 250 | 81 | 67,60 % |
| `2600E0025586H.xml` | maison_individuelle | post_2026 | 354 | 81 | 77,12 % |

## Cas totalement conformes

_Aucun._
