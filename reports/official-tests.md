# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-07T13:55:03+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `19873a8` — arbre de travail modifié : 2 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 354
Exécutés                 : 354
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 354

Valeurs comparées        : 113 413
Exactes                  : 85 672
Dans tolérance           : 14 702
Hors tolérance           : 11 075
Balises manquantes       : 97
Balises supplémentaires  : 1 830
Écarts non numériques    : 37

Conformité               : 88,50 %
```

Sur ces écarts, **3 824 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **91,87 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 44337 | 42686 | 1286 | 239 | 7 | 119 | 99,18 % |
| Ventilation | 2426 | 1568 | 850 | 8 | 0 | 0 | 99,67 % |
| Apports | 11358 | 9828 | 966 | 285 | 0 | 279 | 95,03 % |
| Besoin chauffage | 1742 | 14 | 1451 | 273 | 4 | 0 | 84,10 % |
| Besoin ECS | 3370 | 3178 | 181 | 11 | 0 | 0 | 99,67 % |
| Génération chauffage | 7108 | 2843 | 2444 | 1773 | 26 | 22 | 74,38 % |
| Génération ECS | 7491 | 4733 | 985 | 1747 | 12 | 14 | 76,33 % |
| Auxiliaires | 12036 | 7676 | 2258 | 2102 | 0 | 0 | 82,54 % |
| Froid | 4380 | 4056 | 28 | 248 | 48 | 0 | 93,24 % |
| PV | 2484 | 2475 | 0 | 9 | 0 | 0 | 99,64 % |
| Sorties énergie finale | 2320 | 1210 | 618 | 492 | 0 | 0 | 78,79 % |
| Sorties énergie primaire | 2832 | 1318 | 819 | 675 | 0 | 0 | 75,46 % |
| GES | 4719 | 2246 | 1366 | 1090 | 0 | 0 | 76,54 % |
| Coûts | 4011 | 841 | 1275 | 1895 | 0 | 0 | 52,75 % |
| Confort d'été | 2043 | 558 | 0 | 89 | 0 | 1396 | 27,31 % |
| Autre | 756 | 442 | 175 | 139 | 0 | 0 | 81,61 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61227 | 10764 | 5602 | 22 | 90,71 % |
| appartement_issu_immeuble | 43 | 15056 | 10191 | 1496 | 3065 | 29 | 77,40 % |
| maison_individuelle | 36 | 10085 | 7563 | 1120 | 1331 | 18 | 85,79 % |
| appartement_individuel | 39 | 9146 | 6691 | 1322 | 1077 | 28 | 87,24 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 277 | 86099 | 65712 | 12217 | 6551 | 57 | 90,22 % |
| pre_2026 | 77 | 27314 | 19960 | 2485 | 4524 | 40 | 81,94 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 268 | 83793 | 64367 | 11770 | 6125 | 15 | 90,57 % |
| 3cl_tribu_1.4.25.1 | 64 | 23040 | 16811 | 2036 | 3904 | 24 | 81,57 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2711 | 2067 | 247 | 369 | 0 | 85,14 % |
| 3cl_bbs_V2025.11.1.0 | 6 | 1571 | 856 | 258 | 383 | 32 | 70,64 % |
| 3cl-2024.6.1.0 | 3 | 644 | 441 | 81 | 106 | 12 | 80,68 % |
| BBS_Slama_2024.6.1.0 | 2 | 640 | 457 | 87 | 96 | 0 | 84,74 % |
| inconnu | 2 | 522 | 347 | 131 | 33 | 10 | 91,22 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 184 | 34 | 49 | 4 | 77,86 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 142 | 58 | 10 | 0 | 93,46 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 748 | 73 | 1 098,9 % | hors-tol×748 |
| 2 | Génération chauffage | `conso_ch` | 706 | 139 | 1 243,1 % | hors-tol×700 manquante×6 |
| 3 | Génération ECS | `conso_ecs_depensier` | 680 | 71 | 845,3 % | hors-tol×680 |
| 4 | Génération chauffage | `conso_ch_depensier` | 572 | 139 | 1 243,1 % | hors-tol×566 manquante×6 |
| 5 | Coûts | `cout_5_usages` | 484 | 218 | 799,5 % | hors-tol×484 |
| 6 | GES | `emission_ges_5_usages` | 368 | 155 | 1 189,8 % | hors-tol×368 |
| 7 | Sorties énergie finale | `conso_5_usages` | 361 | 150 | 1 219,0 % | hors-tol×361 |
| 8 | Apports | `inertie_lourde` | 353 | 353 | 100,0 % | hors-tol×74 suppl×279 |
| 9 | Coûts | `cout_ch` | 345 | 165 | 533,1 % | hors-tol×345 |
| 10 | Coûts | `cout_ecs_depensier` | 344 | 344 | 204,2 % | hors-tol×344 |
| 11 | Coûts | `cout_ch_depensier` | 343 | 343 | 264,2 % | hors-tol×343 |
| 12 | Confort d'été | `isolation_toiture` | 314 | 314 | 100,0 % | hors-tol×34 suppl×280 |
| 13 | Confort d'été | `enum_indicateur_confort_ete_id` | 311 | 311 | 200,0 % | hors-tol×32 suppl×279 |
| 14 | GES | `emission_ges_ch` | 303 | 143 | 810,8 % | hors-tol×303 |
| 15 | Confort d'été | `protection_solaire_exterieure` | 291 | 291 | 100,0 % | hors-tol×12 suppl×279 |
| 16 | Coûts | `cout_ecs` | 290 | 161 | 559,4 % | hors-tol×290 |
| 17 | Confort d'été | `aspect_traversant` | 290 | 290 | — | hors-tol×11 suppl×279 |
| 18 | Confort d'été | `brasseur_air` | 279 | 279 | — | suppl×279 |
| 19 | Génération ECS | `rendement_stockage` | 277 | 43 | 44,3 % | hors-tol×268 manquante×8 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 204 | 204 | 346,7 % | hors-tol×204 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 155 | 155 | 141,7 % | hors-tol×155 |
| 22 | Génération chauffage | `rendement_generation` | 149 | 118 | 26,9 % | hors-tol×124 suppl×22 manquante×3 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 145 | 145 | 199,4 % | hors-tol×145 |
| 24 | GES | `emission_ges_ch_depensier` | 142 | 142 | 151,3 % | hors-tol×142 |
| 25 | Besoin chauffage | `besoin_ch` | 139 | 51 | 155,6 % | hors-tol×137 manquante×2 |
| 26 | Besoin chauffage | `besoin_ch_depensier` | 138 | 53 | 1 009,4 % | hors-tol×136 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 137 | 137 | 199,4 % | hors-tol×137 |
| 28 | Sorties énergie primaire | `ep_conso_ch` | 137 | 137 | 140,3 % | hors-tol×137 |
| 29 | Sorties énergie primaire | `ep_conso_ch_depensier` | 137 | 137 | 140,3 % | hors-tol×137 |
| 30 | Sorties énergie finale | `conso_5_usages_m2` | 131 | 131 | 143,8 % | hors-tol×131 |
| 31 | GES | `emission_ges_ecs` | 124 | 62 | 616,5 % | hors-tol×124 |
| 32 | Auxiliaires | `cout_total_auxiliaire` | 122 | 122 | 3 213,8 % | hors-tol×122 |
| 33 | Auxiliaires | `cout_auxiliaire_generation_ch` | 104 | 104 | 100,0 % | hors-tol×104 |
| 34 | Auxiliaires | `conso_auxiliaire_generation_ch` | 92 | 92 | 100,0 % | hors-tol×92 |
| 35 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 92 | 92 | 100,0 % | hors-tol×92 |
| 36 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 92 | 92 | 100,0 % | hors-tol×92 |
| 37 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 92 | 92 | 100,0 % | hors-tol×92 |
| 38 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 92 | 92 | 100,0 % | hors-tol×92 |
| 39 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 92 | 92 | 100,0 % | hors-tol×92 |
| 40 | Génération chauffage | `pn` | 92 | 78 | 1 328,6 % | hors-tol×88 manquante×4 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 269 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 10 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 9 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 18 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 300 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 300 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 141 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 182 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `conso_totale_auxiliaire` | 7 | la référence publie un total auxiliaire de 103.1 kWh, incompatible avec la somme de ses postes (316.8 kWh) |
| `besoin_ch_depensier` | 7 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_ecs` | 466 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 466 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 271 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 30 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 3 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `zone_pre2026coefelec_diag1793608.xml` | appartement_issu_immeuble | pre_2026 | 722 | 153 | 78,81 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 848 | 145 | 82,90 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 145 | 75,38 % |
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 142 | 84,29 % |
| `2400E0333876N.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333878P.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333880R.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333885W.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333888Z.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333891C.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333895G.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333896H.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333898J.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333903O.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333904P.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333906R.xml` | appartement_issu_immeuble | pre_2026 | 403 | 133 | 67,00 % |
| `2400E0333883U.xml` | appartement_issu_immeuble | pre_2026 | 403 | 131 | 67,49 % |
| `2400E0333892D.xml` | appartement_issu_immeuble | pre_2026 | 403 | 131 | 67,49 % |
| `2400E0333901M.xml` | appartement_issu_immeuble | pre_2026 | 403 | 131 | 67,49 % |
| `2600E0035103I.xml` | immeuble_collectif | post_2026 | 642 | 120 | 81,31 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 100 | 78,59 % |
| `2400E0636882P.xml` | immeuble_collectif | pre_2026 | 415 | 100 | 75,90 % |
| `2400E0669732B.xml` | immeuble_collectif | pre_2026 | 890 | 98 | 88,99 % |

## Cas totalement conformes

_Aucun._
