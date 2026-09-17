# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-17T12:32:26+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `742323b` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 376
Exécutés                 : 376
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 376

Valeurs comparées        : 119 973
Exactes                  : 90 967
Dans tolérance           : 17 873
Hors tolérance           : 9 042
Balises manquantes       : 122
Balises supplémentaires  : 1 932
Écarts non numériques    : 37

Conformité               : 90,72 %
```

Sur ces écarts, **3 363 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,52 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 46649 | 44902 | 1363 | 255 | 7 | 122 | 99,18 % |
| Ventilation | 2575 | 1658 | 895 | 22 | 0 | 0 | 99,15 % |
| Apports | 11939 | 10384 | 1049 | 211 | 0 | 295 | 95,76 % |
| Besoin chauffage | 1858 | 14 | 1701 | 139 | 4 | 0 | 92,30 % |
| Besoin ECS | 3566 | 3358 | 193 | 15 | 0 | 0 | 99,58 % |
| Génération chauffage | 7632 | 3247 | 3153 | 1181 | 26 | 25 | 83,86 % |
| Génération ECS | 7890 | 5039 | 1653 | 1169 | 15 | 14 | 84,82 % |
| Auxiliaires | 12784 | 8169 | 2438 | 2177 | 0 | 0 | 82,97 % |
| Froid | 4664 | 4320 | 29 | 251 | 64 | 0 | 93,25 % |
| PV | 2644 | 2629 | 0 | 9 | 6 | 0 | 99,43 % |
| Sorties énergie finale | 2468 | 1343 | 775 | 350 | 0 | 0 | 85,82 % |
| Sorties énergie primaire | 3008 | 1451 | 1034 | 503 | 0 | 0 | 82,61 % |
| GES | 5018 | 2415 | 1746 | 840 | 0 | 0 | 82,92 % |
| Coûts | 4266 | 903 | 1637 | 1726 | 0 | 0 | 59,54 % |
| Confort d'été | 2166 | 618 | 0 | 72 | 0 | 1476 | 28,53 % |
| Autre | 846 | 517 | 207 | 122 | 0 | 0 | 85,58 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 236 | 79126 | 61473 | 11741 | 4384 | 22 | 92,25 % |
| appartement_issu_immeuble | 59 | 20138 | 14016 | 3156 | 2539 | 49 | 85,02 % |
| maison_individuelle | 39 | 10872 | 8178 | 1377 | 1238 | 23 | 87,57 % |
| appartement_individuel | 42 | 9837 | 7300 | 1599 | 881 | 28 | 90,08 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 285 | 88138 | 67484 | 13472 | 5537 | 66 | 91,56 % |
| pre_2026 | 91 | 31835 | 23483 | 4401 | 3505 | 56 | 87,34 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 274 | 85326 | 65843 | 12925 | 5019 | 14 | 92,02 % |
| 3cl_tribu_1.4.25.1 | 68 | 24269 | 17839 | 3437 | 2661 | 40 | 87,42 % |
| 3cl_tribu_2024.6.1.0 | 15 | 5446 | 4170 | 712 | 488 | 0 | 89,40 % |
| 3cl_bbs_V2025.11.1.0 | 7 | 1800 | 961 | 292 | 460 | 37 | 69,34 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 684 | 137 | 107 | 0 | 87,34 % |
| inconnu | 3 | 799 | 536 | 197 | 50 | 15 | 91,40 % |
| 3cl-2024.6.1.0 | 3 | 644 | 440 | 81 | 107 | 12 | 80,53 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 186 | 33 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 164 | 1 | 94 | 0 | 63,22 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 522 | 80 | 104,8 % | hors-tol×522 |
| 2 | Génération chauffage | `conso_ch` | 463 | 90 | 2 900,0 % | hors-tol×457 manquante×6 |
| 3 | Génération ECS | `conso_ecs_depensier` | 447 | 78 | 103,4 % | hors-tol×447 |
| 4 | Coûts | `cout_5_usages` | 409 | 208 | 882,3 % | hors-tol×409 |
| 5 | Génération chauffage | `conso_ch_depensier` | 393 | 92 | 2 900,0 % | hors-tol×387 manquante×6 |
| 6 | Coûts | `cout_ecs_depensier` | 365 | 365 | 122,3 % | hors-tol×365 |
| 7 | Coûts | `cout_ch_depensier` | 363 | 363 | 268,0 % | hors-tol×363 |
| 8 | Confort d'été | `isolation_toiture` | 332 | 332 | 100,0 % | hors-tol×36 suppl×296 |
| 9 | Confort d'été | `protection_solaire_exterieure` | 308 | 308 | 100,0 % | hors-tol×13 suppl×295 |
| 10 | Confort d'été | `aspect_traversant` | 307 | 307 | — | hors-tol×12 suppl×295 |
| 11 | Confort d'été | `enum_indicateur_confort_ete_id` | 306 | 306 | 100,0 % | hors-tol×11 suppl×295 |
| 12 | Apports | `inertie_lourde` | 296 | 296 | — | suppl×295 hors-tol×1 |
| 13 | Confort d'été | `brasseur_air` | 295 | 295 | — | suppl×295 |
| 14 | GES | `emission_ges_5_usages` | 274 | 128 | 19 178,9 % | hors-tol×274 |
| 15 | Coûts | `cout_ecs` | 272 | 152 | 73,4 % | hors-tol×272 |
| 16 | Sorties énergie finale | `conso_5_usages` | 266 | 121 | 19 918,0 % | hors-tol×266 |
| 17 | Coûts | `cout_ch` | 241 | 117 | 534,4 % | hors-tol×241 |
| 18 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 215 | 215 | 352,3 % | hors-tol×215 |
| 19 | GES | `emission_ges_ch` | 194 | 93 | 812,9 % | hors-tol×194 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 166 | 166 | 141,7 % | hors-tol×166 |
| 21 | Génération ECS | `rendement_stockage` | 163 | 50 | 40,0 % | hors-tol×151 manquante×12 |
| 22 | GES | `emission_ges_ecs` | 138 | 69 | 100,0 % | hors-tol×138 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 117 | 117 | 3 213,8 % | hors-tol×117 |
| 24 | Génération chauffage | `rendement_generation` | 117 | 77 | 36,2 % | hors-tol×91 suppl×23 manquante×3 |
| 25 | Auxiliaires | `cout_auxiliaire_generation_ch` | 111 | 111 | 100,0 % | hors-tol×111 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages` | 101 | 101 | 519,8 % | hors-tol×101 |
| 27 | Auxiliaires | `conso_auxiliaire_generation_ch` | 96 | 96 | 100,0 % | hors-tol×96 |
| 28 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 96 | 96 | 100,0 % | hors-tol×96 |
| 29 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 96 | 96 | 100,0 % | hors-tol×96 |
| 30 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 96 | 96 | 100,0 % | hors-tol×96 |
| 31 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 96 | 96 | 100,0 % | hors-tol×96 |
| 32 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 96 | 96 | 100,0 % | hors-tol×96 |
| 33 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 94 | 94 | 519,0 % | hors-tol×94 |
| 34 | GES | `emission_ges_ch_depensier` | 92 | 92 | 154,0 % | hors-tol×92 |
| 35 | Sorties énergie primaire | `ep_conso_ch` | 86 | 86 | 142,9 % | hors-tol×86 |
| 36 | Sorties énergie primaire | `ep_conso_ch_depensier` | 86 | 86 | 142,9 % | hors-tol×86 |
| 37 | Génération chauffage | `qp0` | 85 | 78 | 205 028,2 % | hors-tol×84 manquante×1 |
| 38 | Sorties énergie finale | `conso_5_usages_m2` | 82 | 82 | 242,6 % | hors-tol×82 |
| 39 | Génération chauffage | `pn` | 80 | 71 | 1 328,6 % | hors-tol×76 manquante×4 |
| 40 | Coûts | `cout_eclairage` | 76 | 76 | 83,6 % | hors-tol×76 |

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
| `besoin_ecs_depensier` | 13 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 8800 publié au lieu de 24990 |
| `qp0` | 15 | la référence sérialise QP0 en kW (0.120) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=24000 W est 120 W |
| `cout_fr_depensier` | 19 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 311 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 311 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 147 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 184 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `conso_totale_auxiliaire` | 10 | la référence publie un total auxiliaire de 103.1 kWh, incompatible avec la somme de ses postes (316.8 kWh) |
| `besoin_ch_depensier` | 10 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (7116 vs 50051), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 6 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `conso_ecs` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 227 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 154 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
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
| `2400E0124709Q.xml` | maison_individuelle | pre_2026 | 320 | 84 | 73,75 % |
| `2675E0021756W.xml` | immeuble_collectif | post_2026 | 356 | 84 | 76,40 % |
| `2600E0035103I.xml` | immeuble_collectif | post_2026 | 642 | 83 | 87,07 % |
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 323 | 82 | 74,61 % |
| `2600E0006037K.xml` | appartement_individuel | post_2026 | 250 | 81 | 67,60 % |
| `2600E0025586H.xml` | maison_individuelle | post_2026 | 354 | 81 | 77,12 % |
| `2612E0854137C.xml` | immeuble_collectif | post_2026 | 274 | 80 | 70,80 % |
| `2682E0040013I.xml` | immeuble_collectif | post_2026 | 254 | 80 | 68,50 % |
| `2682E0040066J.xml` | immeuble_collectif | post_2026 | 254 | 80 | 68,50 % |

## Cas totalement conformes

_Aucun._
