# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-01T09:50:59+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `39c0ada` — arbre de travail modifié : 10 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 224
Exécutés                 : 224
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 224

Valeurs comparées        : 71 304
Exactes                  : 51 799
Dans tolérance           : 7 418
Hors tolérance           : 9 723
Balises manquantes       : 45
Balises supplémentaires  : 2 280
Écarts non numériques    : 39

Conformité               : 83,05 %
```

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 28501 | 27573 | 798 | 81 | 3 | 46 | 99,54 % |
| Ventilation | 1546 | 964 | 578 | 4 | 0 | 0 | 99,74 % |
| Apports | 7729 | 5562 | 553 | 1177 | 0 | 437 | 79,12 % |
| Besoin chauffage | 954 | 12 | 868 | 72 | 2 | 0 | 92,24 % |
| Besoin ECS | 1790 | 1742 | 45 | 3 | 0 | 0 | 99,83 % |
| Génération chauffage | 4085 | 1551 | 1199 | 1158 | 6 | 171 | 67,32 % |
| Génération ECS | 3905 | 2518 | 539 | 276 | 14 | 558 | 78,28 % |
| Auxiliaires | 7616 | 3474 | 1069 | 3073 | 0 | 0 | 59,65 % |
| Froid | 2723 | 2658 | 0 | 42 | 20 | 3 | 97,61 % |
| PV | 1574 | 1565 | 0 | 9 | 0 | 0 | 99,43 % |
| Sorties énergie finale | 1516 | 799 | 328 | 389 | 0 | 0 | 74,34 % |
| Sorties énergie primaire | 1792 | 841 | 531 | 405 | 0 | 0 | 76,56 % |
| GES | 3058 | 1543 | 775 | 716 | 0 | 0 | 75,80 % |
| Coûts | 2610 | 386 | 6 | 2218 | 0 | 0 | 15,02 % |
| Confort d'été | 1332 | 251 | 0 | 16 | 0 | 1065 | 18,84 % |
| Autre | 573 | 360 | 129 | 84 | 0 | 0 | 85,34 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 206 | 65998 | 48106 | 6927 | 8745 | 26 | 83,13 % |
| appartement_issu_immeuble | 7 | 2478 | 1787 | 217 | 395 | 1 | 80,64 % |
| appartement_individuel | 7 | 1620 | 1053 | 132 | 391 | 14 | 72,83 % |
| maison_individuelle | 4 | 1208 | 853 | 142 | 192 | 4 | 82,10 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 207 | 65628 | 47762 | 6959 | 8716 | 19 | 83,12 % |
| pre_2026 | 17 | 5676 | 4037 | 459 | 1007 | 26 | 78,97 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 206 | 65394 | 47619 | 6913 | 8679 | 15 | 83,13 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2749 | 1995 | 183 | 502 | 0 | 79,03 % |
| 3cl_tribu_1.4.25.1 | 4 | 1345 | 987 | 113 | 191 | 8 | 81,54 % |
| 3cl-2024.6.1.0 | 3 | 652 | 443 | 72 | 113 | 13 | 78,63 % |
| BBS_Slama_2024.6.1.0 | 2 | 647 | 427 | 59 | 152 | 0 | 74,88 % |
| 3cl_tribu_1.4.25.0 | 1 | 283 | 185 | 32 | 49 | 5 | 76,41 % |
| inconnu | 1 | 234 | 143 | 46 | 37 | 4 | 80,43 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Coûts | `cout_5_usages` | 646 | 224 | 55,4 % | hors-tol×646 |
| 2 | Génération chauffage | `conso_ch_depensier` | 520 | 164 | 109,6 % | hors-tol×517 manquante×3 |
| 3 | Coûts | `cout_ch` | 453 | 224 | 100,0 % | hors-tol×453 |
| 4 | Coûts | `cout_ecs` | 451 | 223 | 69,3 % | hors-tol×451 |
| 5 | Génération chauffage | `conso_ch` | 357 | 82 | 109,0 % | hors-tol×354 manquante×3 |
| 6 | Génération ECS | `Qgw` | 335 | 224 | — | suppl×335 |
| 7 | GES | `emission_ges_5_usages` | 316 | 174 | 100,0 % | hors-tol×316 |
| 8 | Sorties énergie finale | `conso_5_usages` | 311 | 170 | 68,4 % | hors-tol×311 |
| 9 | Apports | `enum_classe_inertie_id` | 224 | 224 | — | suppl×224 |
| 10 | Coûts | `cout_ch_depensier` | 224 | 224 | 100,0 % | hors-tol×224 |
| 11 | Coûts | `cout_eclairage` | 224 | 224 | 78,0 % | hors-tol×224 |
| 12 | Apports | `inertie_lourde` | 223 | 223 | 100,0 % | suppl×213 hors-tol×10 |
| 13 | Auxiliaires | `cout_total_auxiliaire` | 221 | 221 | 97,7 % | hors-tol×221 |
| 14 | Apports | `pertes_distribution_ecs_recup_depensier` | 220 | 220 | 99 900,0 % | hors-tol×220 |
| 15 | Confort d'été | `isolation_toiture` | 220 | 220 | — | suppl×213 hors-tol×7 |
| 16 | Coûts | `cout_ecs_depensier` | 220 | 220 | 69,8 % | hors-tol×220 |
| 17 | Confort d'été | `enum_indicateur_confort_ete_id` | 219 | 219 | 50,0 % | suppl×213 hors-tol×6 |
| 18 | Apports | `apport_interne_ch` | 219 | 219 | 99 900,0 % | hors-tol×219 |
| 19 | Apports | `apport_solaire_ch` | 219 | 219 | 104 945,5 % | hors-tol×219 |
| 20 | Apports | `pertes_distribution_ecs_recup` | 219 | 219 | 99 900,0 % | hors-tol×219 |
| 21 | Confort d'été | `protection_solaire_exterieure` | 215 | 215 | 100,0 % | suppl×213 hors-tol×2 |
| 22 | Génération ECS | `rendement_stockage` | 214 | 164 | 34,4 % | suppl×203 hors-tol×11 |
| 23 | Confort d'été | `aspect_traversant` | 214 | 214 | — | suppl×213 hors-tol×1 |
| 24 | Confort d'été | `brasseur_air` | 213 | 213 | — | suppl×213 |
| 25 | Auxiliaires | `cout_auxiliaire_ventilation` | 202 | 202 | 53,8 % | hors-tol×202 |
| 26 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 191 | 191 | 100,0 % | hors-tol×191 |
| 27 | GES | `emission_ges_ch` | 175 | 87 | 103,2 % | hors-tol×175 |
| 28 | Génération chauffage | `pveil` | 167 | 163 | — | suppl×167 |
| 29 | Auxiliaires | `cout_auxiliaire_generation_ch` | 163 | 163 | 166,1 % | hors-tol×163 |
| 30 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 163 | 163 | 280,3 % | hors-tol×163 |
| 31 | Auxiliaires | `conso_totale_auxiliaire` | 151 | 151 | 248,5 % | hors-tol×151 |
| 32 | Auxiliaires | `emission_ges_totale_auxiliaire` | 151 | 151 | 248,4 % | hors-tol×151 |
| 33 | Auxiliaires | `ep_conso_totale_auxiliaire` | 151 | 151 | 248,5 % | hors-tol×151 |
| 34 | Auxiliaires | `cout_auxiliaire_generation_ecs` | 137 | 137 | 99,9 % | hors-tol×137 |
| 35 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 137 | 137 | 99,9 % | hors-tol×137 |
| 36 | Auxiliaires | `emission_ges_auxiliaire_generation_ecs_depensier` | 137 | 137 | 99,9 % | hors-tol×137 |
| 37 | Auxiliaires | `ep_conso_auxiliaire_generation_ecs_depensier` | 137 | 137 | 99,9 % | hors-tol×137 |
| 38 | Auxiliaires | `conso_auxiliaire_generation_ecs` | 136 | 136 | 99,9 % | hors-tol×136 |
| 39 | Auxiliaires | `conso_auxiliaire_generation_ecs_depensier` | 136 | 136 | 99,9 % | hors-tol×136 |
| 40 | Auxiliaires | `emission_ges_auxiliaire_generation_ecs` | 136 | 136 | 99,9 % | hors-tol×136 |

## Conformité structurelle du XML produit

Balises écrites par le moteur mais absentes du schéma ADEME — un fichier
les contenant serait rejeté par l'observatoire :

| Balise | Cas concernés |
|---|---:|
| `Qgw` | 224 |
| `pveil` | 163 |

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `zone_pre2026coefelec_diag1793608.xml` | appartement_issu_immeuble | pre_2026 | 733 | 168 | 77,08 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 528 | 134 | 74,62 % |
| `2675E0942311V.xml` | immeuble_collectif | post_2026 | 428 | 112 | 73,83 % |
| `2659E1128954U.xml` | immeuble_collectif | post_2026 | 590 | 106 | 82,03 % |
| `2593E3377930D.xml` | appartement_individuel | pre_2026 | 272 | 105 | 61,40 % |
| `2659E1127256M.xml` | immeuble_collectif | post_2026 | 569 | 100 | 82,43 % |
| `2592E0655586O.xml` | appartement_individuel | pre_2026 | 257 | 94 | 63,42 % |
| `2675E0023041H.xml` | immeuble_collectif | post_2026 | 335 | 93 | 72,24 % |
| `2675E0023980K.xml` | immeuble_collectif | post_2026 | 343 | 93 | 72,89 % |
| `2467E3590684Y.xml` | immeuble_collectif | pre_2026 | 469 | 92 | 80,38 % |
| `2659E1127961P.xml` | immeuble_collectif | post_2026 | 548 | 92 | 83,21 % |
| `2659E1260921L.xml` | immeuble_collectif | post_2026 | 273 | 92 | 66,30 % |
| `2675E0021756W.xml` | immeuble_collectif | post_2026 | 360 | 92 | 74,44 % |
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 327 | 92 | 71,87 % |
| `2612E0854137C.xml` | immeuble_collectif | post_2026 | 278 | 91 | 67,27 % |
| `2659E1259773H.xml` | immeuble_collectif | post_2026 | 302 | 91 | 69,87 % |
| `2675E0018503T.xml` | immeuble_collectif | post_2026 | 406 | 91 | 77,59 % |
| `2675E0021134Y.xml` | immeuble_collectif | post_2026 | 446 | 91 | 79,60 % |
| `2675E0022293N.xml` | immeuble_collectif | post_2026 | 365 | 91 | 75,07 % |
| `2682E0040013I.xml` | immeuble_collectif | post_2026 | 258 | 91 | 64,73 % |
| `2682E0040066J.xml` | immeuble_collectif | post_2026 | 258 | 91 | 64,73 % |
| `2682E0040174N.xml` | immeuble_collectif | post_2026 | 258 | 91 | 64,73 % |
| `2682E0040242D.xml` | immeuble_collectif | post_2026 | 258 | 91 | 64,73 % |
| `2688E0016745Q.xml` | appartement_issu_immeuble | post_2026 | 425 | 90 | 78,82 % |
| `2675E0069484O.xml` | immeuble_collectif | post_2026 | 299 | 89 | 70,23 % |

## Cas totalement conformes

_Aucun._
