# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-28T08:47:32+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `e8a0b93` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 380
Exécutés                 : 380
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 380

Valeurs comparées        : 121 172
Exactes                  : 91 831
Dans tolérance           : 18 113
Hors tolérance           : 9 118
Balises manquantes       : 135
Balises supplémentaires  : 1 941
Écarts non numériques    : 34

Conformité               : 90,73 %
```

Sur ces écarts, **3 405 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,54 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 47116 | 45328 | 1396 | 260 | 7 | 125 | 99,17 % |
| Ventilation | 2602 | 1679 | 901 | 22 | 0 | 0 | 99,15 % |
| Apports | 12054 | 10478 | 1059 | 221 | 0 | 296 | 95,71 % |
| Besoin chauffage | 1874 | 14 | 1713 | 143 | 4 | 0 | 92,16 % |
| Besoin ECS | 3596 | 3379 | 201 | 16 | 0 | 0 | 99,56 % |
| Génération chauffage | 7704 | 3280 | 3180 | 1193 | 26 | 25 | 83,85 % |
| Génération ECS | 7945 | 5078 | 1664 | 1173 | 16 | 14 | 84,86 % |
| Auxiliaires | 12920 | 8245 | 2474 | 2201 | 0 | 0 | 82,96 % |
| Froid | 4724 | 4368 | 29 | 251 | 76 | 0 | 93,08 % |
| PV | 2675 | 2660 | 0 | 9 | 6 | 0 | 99,44 % |
| Sorties énergie finale | 2496 | 1355 | 788 | 353 | 0 | 0 | 85,86 % |
| Sorties énergie primaire | 3040 | 1464 | 1055 | 501 | 0 | 0 | 82,86 % |
| GES | 5074 | 2438 | 1787 | 835 | 0 | 0 | 83,27 % |
| Coûts | 4314 | 911 | 1659 | 1744 | 0 | 0 | 59,57 % |
| Confort d'été | 2186 | 631 | 0 | 74 | 0 | 1481 | 28,87 % |
| Autre | 852 | 523 | 207 | 122 | 0 | 0 | 85,68 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 237 | 79455 | 61682 | 11776 | 4458 | 26 | 92,18 % |
| appartement_issu_immeuble | 59 | 20138 | 14016 | 3156 | 2539 | 49 | 85,02 % |
| maison_individuelle | 42 | 11742 | 8833 | 1582 | 1240 | 32 | 88,38 % |
| appartement_individuel | 42 | 9837 | 7300 | 1599 | 881 | 28 | 90,08 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 288 | 89008 | 68137 | 13674 | 5543 | 75 | 91,62 % |
| pre_2026 | 92 | 32164 | 23694 | 4439 | 3575 | 60 | 87,22 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 275 | 85584 | 66043 | 12996 | 5008 | 14 | 92,06 % |
| 3cl_tribu_1.4.25.1 | 68 | 24269 | 17839 | 3437 | 2661 | 40 | 87,42 % |
| 3cl_tribu_2024.6.1.0 | 15 | 5446 | 4173 | 715 | 484 | 0 | 89,51 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| inconnu | 5 | 1411 | 989 | 328 | 67 | 24 | 93,01 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 684 | 137 | 107 | 0 | 87,34 % |
| 3cl-2024.6.1.0 | 3 | 644 | 440 | 81 | 107 | 12 | 80,53 % |
| 3cl_bbs_V2024.6.1.0 | 1 | 329 | 208 | 35 | 74 | 4 | 73,64 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 186 | 33 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 164 | 1 | 94 | 0 | 63,22 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 522 | 80 | 104,8 % | hors-tol×522 |
| 2 | Génération chauffage | `conso_ch` | 467 | 91 | 2 900,0 % | hors-tol×461 manquante×6 |
| 3 | Génération ECS | `conso_ecs_depensier` | 448 | 79 | 103,4 % | hors-tol×448 |
| 4 | Coûts | `cout_5_usages` | 413 | 211 | 882,3 % | hors-tol×413 |
| 5 | Génération chauffage | `conso_ch_depensier` | 396 | 93 | 2 900,0 % | hors-tol×390 manquante×6 |
| 6 | Coûts | `cout_ecs_depensier` | 368 | 368 | 122,3 % | hors-tol×368 |
| 7 | Coûts | `cout_ch_depensier` | 366 | 366 | 268,0 % | hors-tol×366 |
| 8 | Confort d'été | `isolation_toiture` | 334 | 334 | 100,0 % | hors-tol×37 suppl×297 |
| 9 | Confort d'été | `aspect_traversant` | 309 | 309 | — | hors-tol×13 suppl×296 |
| 10 | Confort d'été | `protection_solaire_exterieure` | 309 | 309 | 100,0 % | hors-tol×13 suppl×296 |
| 11 | Confort d'été | `enum_indicateur_confort_ete_id` | 307 | 307 | 100,0 % | hors-tol×11 suppl×296 |
| 12 | Apports | `inertie_lourde` | 297 | 297 | — | suppl×296 hors-tol×1 |
| 13 | Confort d'été | `brasseur_air` | 296 | 296 | — | suppl×296 |
| 14 | GES | `emission_ges_5_usages` | 274 | 128 | 19 178,9 % | hors-tol×274 |
| 15 | Coûts | `cout_ecs` | 274 | 154 | 73,4 % | hors-tol×274 |
| 16 | Sorties énergie finale | `conso_5_usages` | 268 | 122 | 19 918,0 % | hors-tol×268 |
| 17 | Coûts | `cout_ch` | 244 | 119 | 534,4 % | hors-tol×244 |
| 18 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 217 | 217 | 352,3 % | hors-tol×217 |
| 19 | GES | `emission_ges_ch` | 192 | 90 | 812,9 % | hors-tol×192 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 167 | 167 | 141,7 % | hors-tol×167 |
| 21 | Génération ECS | `rendement_stockage` | 166 | 52 | 40,0 % | hors-tol×153 manquante×13 |
| 22 | GES | `emission_ges_ecs` | 138 | 69 | 100,0 % | hors-tol×138 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 119 | 119 | 3 213,8 % | hors-tol×119 |
| 24 | Génération chauffage | `rendement_generation` | 118 | 78 | 36,2 % | hors-tol×92 suppl×23 manquante×3 |
| 25 | Auxiliaires | `cout_auxiliaire_generation_ch` | 113 | 113 | 100,0 % | hors-tol×113 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages` | 101 | 101 | 519,8 % | hors-tol×101 |
| 27 | Auxiliaires | `conso_auxiliaire_generation_ch` | 97 | 97 | 100,0 % | hors-tol×97 |
| 28 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 97 | 97 | 100,0 % | hors-tol×97 |
| 29 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 97 | 97 | 100,0 % | hors-tol×97 |
| 30 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 97 | 97 | 100,0 % | hors-tol×97 |
| 31 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 97 | 97 | 100,0 % | hors-tol×97 |
| 32 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 97 | 97 | 100,0 % | hors-tol×97 |
| 33 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 93 | 93 | 519,0 % | hors-tol×93 |
| 34 | GES | `emission_ges_ch_depensier` | 89 | 89 | 100,0 % | hors-tol×89 |
| 35 | Génération chauffage | `qp0` | 86 | 79 | 205 028,2 % | hors-tol×85 manquante×1 |
| 36 | Sorties énergie primaire | `ep_conso_ch` | 85 | 85 | 100,0 % | hors-tol×85 |
| 37 | Sorties énergie primaire | `ep_conso_ch_depensier` | 85 | 85 | 100,0 % | hors-tol×85 |
| 38 | Sorties énergie finale | `conso_5_usages_m2` | 83 | 83 | 242,6 % | hors-tol×83 |
| 39 | Génération chauffage | `pn` | 81 | 72 | 1 328,6 % | hors-tol×77 manquante×4 |
| 40 | Coûts | `cout_eclairage` | 79 | 79 | 83,6 % | hors-tol×79 |

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
| `cout_ecs_depensier` | 311 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 311 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 148 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 184 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `emission_ges_totale_auxiliaire` | 11 | la référence publie un total auxiliaire de 6.6, incompatible avec la somme de ses postes (20.3) |
| `cout_total_auxiliaire` | 12 | la référence publie un total auxiliaire de 25.9, incompatible avec la somme de ses postes (79.5) |
| `ep_conso_totale_auxiliaire` | 11 | la référence publie un total auxiliaire de 195.9, incompatible avec la somme de ses postes (601.9) |
| `conso_totale_auxiliaire` | 11 | la référence publie un total auxiliaire de 103.1, incompatible avec la somme de ses postes (316.8) |
| `besoin_ch_depensier` | 12 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `conso_ecs` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 156 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 28 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 3 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 142 | 84,29 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 848 | 125 | 85,26 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 111 | 63,84 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 99 | 78,80 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 97 | 62,11 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 95 | 63,46 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 94 | 84,04 % |
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
| `2612E0854137C.xml` | immeuble_collectif | post_2026 | 274 | 80 | 70,80 % |
| `2682E0040013I.xml` | immeuble_collectif | post_2026 | 254 | 80 | 68,50 % |

## Cas totalement conformes

_Aucun._
