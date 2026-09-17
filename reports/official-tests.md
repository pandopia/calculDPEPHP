# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-17T07:33:15+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `aaf7625` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 375
Exécutés                 : 375
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 375

Valeurs comparées        : 119 684
Exactes                  : 90 461
Dans tolérance           : 16 724
Hors tolérance           : 10 412
Balises manquantes       : 122
Balises supplémentaires  : 1 926
Écarts non numériques    : 39

Conformité               : 89,56 %
```

Sur ces écarts, **3 361 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,36 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 46524 | 44780 | 1360 | 255 | 7 | 122 | 99,17 % |
| Ventilation | 2568 | 1656 | 890 | 22 | 0 | 0 | 99,14 % |
| Apports | 11907 | 10354 | 1046 | 213 | 0 | 294 | 95,74 % |
| Besoin chauffage | 1854 | 14 | 1689 | 147 | 4 | 0 | 91,86 % |
| Besoin ECS | 3559 | 3351 | 193 | 15 | 0 | 0 | 99,58 % |
| Génération chauffage | 7621 | 3131 | 2752 | 1687 | 26 | 25 | 77,19 % |
| Génération ECS | 7879 | 5015 | 1650 | 1185 | 15 | 14 | 84,59 % |
| Auxiliaires | 12750 | 8137 | 2434 | 2179 | 0 | 0 | 82,91 % |
| Froid | 4652 | 4308 | 29 | 251 | 64 | 0 | 93,23 % |
| PV | 2637 | 2622 | 0 | 9 | 6 | 0 | 99,43 % |
| Sorties énergie finale | 2463 | 1294 | 673 | 496 | 0 | 0 | 79,86 % |
| Sorties énergie primaire | 3000 | 1394 | 882 | 702 | 0 | 0 | 75,87 % |
| GES | 5007 | 2374 | 1497 | 1119 | 0 | 0 | 77,31 % |
| Coûts | 4257 | 897 | 1422 | 1938 | 0 | 0 | 54,47 % |
| Confort d'été | 2160 | 617 | 0 | 72 | 0 | 1471 | 28,56 % |
| Autre | 846 | 517 | 207 | 122 | 0 | 0 | 85,58 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61332 | 11107 | 5158 | 22 | 91,28 % |
| appartement_issu_immeuble | 58 | 19849 | 13670 | 2731 | 3026 | 49 | 82,39 % |
| maison_individuelle | 39 | 10872 | 8167 | 1333 | 1293 | 23 | 87,07 % |
| appartement_individuel | 42 | 9837 | 7292 | 1553 | 935 | 28 | 89,53 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 285 | 88138 | 67324 | 12802 | 6365 | 66 | 90,62 % |
| pre_2026 | 90 | 31546 | 23137 | 3922 | 4047 | 56 | 85,53 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 274 | 85326 | 65683 | 12255 | 5847 | 14 | 91,05 % |
| 3cl_tribu_1.4.25.1 | 67 | 23980 | 17500 | 2999 | 3155 | 40 | 85,25 % |
| 3cl_tribu_2024.6.1.0 | 15 | 5446 | 4170 | 712 | 488 | 0 | 89,40 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 677 | 96 | 155 | 0 | 82,23 % |
| inconnu | 3 | 799 | 536 | 197 | 50 | 15 | 91,40 % |
| 3cl-2024.6.1.0 | 3 | 644 | 440 | 81 | 107 | 12 | 80,53 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 186 | 33 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 164 | 1 | 94 | 0 | 63,22 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 663 | 140 | 2 900,0 % | hors-tol×657 manquante×6 |
| 2 | Génération chauffage | `conso_ch_depensier` | 542 | 141 | 2 900,0 % | hors-tol×536 manquante×6 |
| 3 | Génération ECS | `conso_ecs` | 530 | 82 | 104,8 % | hors-tol×530 |
| 4 | Coûts | `cout_5_usages` | 501 | 227 | 882,3 % | hors-tol×501 |
| 5 | Génération ECS | `conso_ecs_depensier` | 453 | 80 | 103,4 % | hors-tol×453 |
| 6 | GES | `emission_ges_5_usages` | 373 | 161 | 19 178,9 % | hors-tol×373 |
| 7 | Sorties énergie finale | `conso_5_usages` | 366 | 154 | 19 918,0 % | hors-tol×366 |
| 8 | Coûts | `cout_ecs_depensier` | 364 | 364 | 122,3 % | hors-tol×364 |
| 9 | Coûts | `cout_ch_depensier` | 363 | 363 | 268,0 % | hors-tol×363 |
| 10 | Coûts | `cout_ch` | 338 | 165 | 533,1 % | hors-tol×338 |
| 11 | Confort d'été | `isolation_toiture` | 331 | 331 | 100,0 % | hors-tol×36 suppl×295 |
| 12 | Confort d'été | `protection_solaire_exterieure` | 307 | 307 | 100,0 % | hors-tol×13 suppl×294 |
| 13 | Confort d'été | `aspect_traversant` | 306 | 306 | — | hors-tol×12 suppl×294 |
| 14 | Confort d'été | `enum_indicateur_confort_ete_id` | 305 | 305 | 100,0 % | hors-tol×11 suppl×294 |
| 15 | Coûts | `cout_ecs` | 295 | 164 | 73,4 % | hors-tol×295 |
| 16 | Apports | `inertie_lourde` | 295 | 295 | — | suppl×294 hors-tol×1 |
| 17 | GES | `emission_ges_ch` | 294 | 143 | 810,8 % | hors-tol×294 |
| 18 | Confort d'été | `brasseur_air` | 294 | 294 | — | suppl×294 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 215 | 215 | 352,3 % | hors-tol×215 |
| 20 | Génération chauffage | `rendement_generation` | 166 | 126 | 36,2 % | hors-tol×140 suppl×23 manquante×3 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 166 | 166 | 141,7 % | hors-tol×166 |
| 22 | Génération ECS | `rendement_stockage` | 165 | 52 | 40,5 % | hors-tol×153 manquante×12 |
| 23 | Sorties énergie primaire | `ep_conso_5_usages` | 150 | 150 | 519,8 % | hors-tol×150 |
| 24 | GES | `emission_ges_ch_depensier` | 142 | 142 | 154,0 % | hors-tol×142 |
| 25 | GES | `emission_ges_ecs` | 142 | 71 | 100,0 % | hors-tol×142 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 140 | 140 | 519,0 % | hors-tol×140 |
| 27 | Sorties énergie primaire | `ep_conso_ch` | 136 | 136 | 142,9 % | hors-tol×136 |
| 28 | Sorties énergie primaire | `ep_conso_ch_depensier` | 136 | 136 | 142,9 % | hors-tol×136 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 128 | 128 | 242,6 % | hors-tol×128 |
| 30 | Auxiliaires | `cout_total_auxiliaire` | 118 | 118 | 3 213,8 % | hors-tol×118 |
| 31 | Auxiliaires | `cout_auxiliaire_generation_ch` | 111 | 111 | 100,0 % | hors-tol×111 |
| 32 | Auxiliaires | `conso_auxiliaire_generation_ch` | 96 | 96 | 100,0 % | hors-tol×96 |
| 33 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 96 | 96 | 100,0 % | hors-tol×96 |
| 34 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 96 | 96 | 100,0 % | hors-tol×96 |
| 35 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 96 | 96 | 100,0 % | hors-tol×96 |
| 36 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 96 | 96 | 100,0 % | hors-tol×96 |
| 37 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 96 | 96 | 100,0 % | hors-tol×96 |
| 38 | GES | `emission_ges_5_usages_m2` | 88 | 88 | 142,9 % | hors-tol×88 |
| 39 | Génération chauffage | `qp0` | 85 | 78 | 205 028,2 % | hors-tol×84 manquante×1 |
| 40 | Génération chauffage | `pn` | 80 | 71 | 1 328,6 % | hors-tol×76 manquante×4 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `aspect_traversant` | 281 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 281 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 281 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 281 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 281 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 281 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 13 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 15 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 19 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 310 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 310 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 147 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
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
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 848 | 125 | 85,26 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 118 | 77,22 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 111 | 84,67 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 111 | 63,84 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 467 | 99 | 78,80 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 99 | 83,19 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 97 | 62,11 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 95 | 63,46 % |
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
| `2400E0333895G.xml` | appartement_issu_immeuble | pre_2026 | 403 | 86 | 78,66 % |

## Cas totalement conformes

_Aucun._
