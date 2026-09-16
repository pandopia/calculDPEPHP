# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-16T08:49:02+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `3b29f44`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 366
Exécutés                 : 366
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 366

Valeurs comparées        : 116 953
Exactes                  : 88 074
Dans tolérance           : 16 186
Hors tolérance           : 10 647
Balises manquantes       : 123
Balises supplémentaires  : 1 878
Écarts non numériques    : 45

Conformité               : 89,15 %
```

Sur ces écarts, **3 289 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **91,96 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 45498 | 43777 | 1337 | 255 | 7 | 122 | 99,16 % |
| Ventilation | 2506 | 1615 | 869 | 22 | 0 | 0 | 99,12 % |
| Apports | 11663 | 10064 | 1023 | 290 | 0 | 286 | 95,06 % |
| Besoin chauffage | 1806 | 14 | 1653 | 135 | 4 | 0 | 92,30 % |
| Besoin ECS | 3478 | 3270 | 193 | 15 | 0 | 0 | 99,57 % |
| Génération chauffage | 7425 | 2999 | 2634 | 1742 | 26 | 24 | 75,87 % |
| Génération ECS | 7717 | 4867 | 1614 | 1205 | 16 | 15 | 83,98 % |
| Auxiliaires | 12444 | 7878 | 2344 | 2222 | 0 | 0 | 82,14 % |
| Froid | 4540 | 4201 | 28 | 247 | 64 | 0 | 93,15 % |
| PV | 2574 | 2559 | 0 | 9 | 6 | 0 | 99,42 % |
| Sorties énergie finale | 2406 | 1256 | 651 | 499 | 0 | 0 | 79,26 % |
| Sorties énergie primaire | 2928 | 1353 | 851 | 699 | 0 | 0 | 75,27 % |
| GES | 4890 | 2303 | 1432 | 1135 | 0 | 0 | 76,38 % |
| Coûts | 4158 | 868 | 1370 | 1920 | 0 | 0 | 53,82 % |
| Confort d'été | 2107 | 581 | 0 | 95 | 0 | 1431 | 27,57 % |
| Autre | 813 | 469 | 187 | 157 | 0 | 0 | 80,69 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61246 | 10956 | 5391 | 22 | 90,98 % |
| appartement_issu_immeuble | 50 | 17336 | 11679 | 2436 | 2844 | 50 | 81,19 % |
| maison_individuelle | 39 | 10872 | 8118 | 1323 | 1352 | 23 | 86,53 % |
| appartement_individuel | 41 | 9619 | 7031 | 1471 | 1060 | 28 | 88,01 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 284 | 87921 | 66995 | 12621 | 6652 | 67 | 90,26 % |
| pre_2026 | 82 | 29032 | 21079 | 3565 | 3995 | 56 | 84,65 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 273 | 85109 | 65359 | 12074 | 6129 | 15 | 90,69 % |
| 3cl_tribu_1.4.25.1 | 66 | 23654 | 17182 | 2906 | 3244 | 40 | 84,69 % |
| 3cl_tribu_2024.6.1.0 | 9 | 3555 | 2673 | 457 | 383 | 0 | 87,82 % |
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
| 1 | Génération chauffage | `conso_ch` | 673 | 140 | 2 900,0 % | hors-tol×667 manquante×6 |
| 2 | Génération chauffage | `conso_ch_depensier` | 546 | 142 | 2 900,0 % | hors-tol×540 manquante×6 |
| 3 | Génération ECS | `conso_ecs` | 538 | 81 | 1 098,9 % | hors-tol×538 |
| 4 | Coûts | `cout_5_usages` | 492 | 221 | 882,3 % | hors-tol×492 |
| 5 | Génération ECS | `conso_ecs_depensier` | 462 | 79 | 845,3 % | hors-tol×462 |
| 6 | GES | `emission_ges_5_usages` | 373 | 157 | 19 178,9 % | hors-tol×373 |
| 7 | Apports | `inertie_lourde` | 365 | 365 | 100,0 % | hors-tol×79 suppl×286 |
| 8 | Sorties énergie finale | `conso_5_usages` | 365 | 151 | 19 918,0 % | hors-tol×365 |
| 9 | Coûts | `cout_ecs_depensier` | 355 | 355 | 207,9 % | hors-tol×355 |
| 10 | Coûts | `cout_ch_depensier` | 354 | 354 | 268,0 % | hors-tol×354 |
| 11 | Coûts | `cout_ch` | 341 | 166 | 533,1 % | hors-tol×341 |
| 12 | Confort d'été | `isolation_toiture` | 323 | 323 | 100,0 % | hors-tol×36 suppl×287 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 320 | 320 | 200,0 % | hors-tol×34 suppl×286 |
| 14 | GES | `emission_ges_ch` | 305 | 147 | 810,8 % | hors-tol×305 |
| 15 | Coûts | `cout_ecs` | 301 | 166 | 559,4 % | hors-tol×301 |
| 16 | Confort d'été | `protection_solaire_exterieure` | 299 | 299 | 100,0 % | hors-tol×13 suppl×286 |
| 17 | Confort d'été | `aspect_traversant` | 298 | 298 | — | hors-tol×12 suppl×286 |
| 18 | Confort d'été | `brasseur_air` | 286 | 286 | — | suppl×286 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 214 | 214 | 346,7 % | hors-tol×214 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 164 | 164 | 225,0 % | hors-tol×164 |
| 21 | Génération chauffage | `rendement_generation` | 163 | 125 | 39,1 % | hors-tol×138 suppl×22 manquante×3 |
| 22 | Génération ECS | `rendement_stockage` | 163 | 50 | 40,0 % | hors-tol×150 manquante×12 suppl×1 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 147 | 147 | 519,8 % | hors-tol×147 |
| 24 | GES | `emission_ges_ch_depensier` | 146 | 146 | 154,0 % | hors-tol×146 |
| 25 | GES | `emission_ges_ecs` | 140 | 70 | 616,5 % | hors-tol×140 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 139 | 139 | 519,0 % | hors-tol×139 |
| 27 | Sorties énergie primaire | `ep_conso_ch` | 138 | 138 | 142,9 % | hors-tol×138 |
| 28 | Sorties énergie primaire | `ep_conso_ch_depensier` | 138 | 138 | 142,9 % | hors-tol×138 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 132 | 132 | 242,6 % | hors-tol×132 |
| 30 | Auxiliaires | `cout_auxiliaire_generation_ch` | 114 | 114 | 100,0 % | hors-tol×114 |
| 31 | Auxiliaires | `cout_total_auxiliaire` | 114 | 114 | 3 213,8 % | hors-tol×114 |
| 32 | Auxiliaires | `conso_auxiliaire_generation_ch` | 100 | 100 | 100,0 % | hors-tol×100 |
| 33 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 100 | 100 | 100,0 % | hors-tol×100 |
| 34 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 100 | 100 | 100,0 % | hors-tol×100 |
| 35 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 100 | 100 | 100,0 % | hors-tol×100 |
| 36 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 100 | 100 | 100,0 % | hors-tol×100 |
| 37 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 100 | 100 | 100,0 % | hors-tol×100 |
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
| `aspect_traversant` | 273 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 273 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 273 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 273 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 273 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 273 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 13 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 15 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 18 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 302 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 302 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 145 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 183 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `conso_totale_auxiliaire` | 10 | la référence publie un total auxiliaire de 103.1 kWh, incompatible avec la somme de ses postes (316.8 kWh) |
| `besoin_ch_depensier` | 10 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `conso_ecs` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
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
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 113 | 56,54 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 111 | 63,84 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 100 | 78,59 % |
| `2400E0636882P.xml` | immeuble_collectif | pre_2026 | 415 | 100 | 75,90 % |
| `2600E0035103I.xml` | immeuble_collectif | post_2026 | 642 | 97 | 84,89 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 97 | 62,11 % |
| `2513E2308242F.xml` | appartement_issu_immeuble | pre_2026 | 528 | 90 | 82,95 % |
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

## Cas totalement conformes

_Aucun._
