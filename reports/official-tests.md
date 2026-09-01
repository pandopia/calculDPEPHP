# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-09-01T10:12:35+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `859b390` — arbre de travail modifié : 7 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 226
Exécutés                 : 226
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 226

Valeurs comparées        : 71 470
Exactes                  : 54 376
Dans tolérance           : 8 975
Hors tolérance           : 6 086
Balises manquantes       : 45
Balises supplémentaires  : 1 949
Écarts non numériques    : 39

Conformité               : 88,64 %
```

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 28664 | 27753 | 800 | 62 | 3 | 46 | 99,61 % |
| Ventilation | 1560 | 978 | 581 | 1 | 0 | 0 | 99,94 % |
| Apports | 7775 | 6569 | 691 | 76 | 0 | 439 | 93,38 % |
| Besoin chauffage | 962 | 12 | 888 | 60 | 2 | 0 | 93,56 % |
| Besoin ECS | 1804 | 1756 | 45 | 3 | 0 | 0 | 99,83 % |
| Génération chauffage | 4116 | 1568 | 1432 | 938 | 6 | 172 | 72,89 % |
| Génération ECS | 3594 | 2532 | 540 | 284 | 14 | 224 | 85,48 % |
| Auxiliaires | 7684 | 4500 | 1662 | 1522 | 0 | 0 | 80,19 % |
| Froid | 2747 | 2682 | 0 | 42 | 20 | 3 | 97,63 % |
| PV | 1588 | 1579 | 0 | 9 | 0 | 0 | 99,43 % |
| Sorties énergie finale | 1530 | 818 | 442 | 270 | 0 | 0 | 82,35 % |
| Sorties énergie primaire | 1808 | 864 | 546 | 383 | 0 | 0 | 77,99 % |
| GES | 3086 | 1562 | 897 | 603 | 0 | 0 | 79,68 % |
| Coûts | 2634 | 585 | 322 | 1727 | 0 | 0 | 34,43 % |
| Confort d'été | 1342 | 258 | 0 | 19 | 0 | 1065 | 19,23 % |
| Autre | 576 | 360 | 129 | 87 | 0 | 0 | 84,90 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 206 | 65692 | 50178 | 8365 | 5235 | 26 | 88,84 % |
| appartement_issu_immeuble | 7 | 2460 | 1837 | 238 | 324 | 1 | 84,11 % |
| appartement_individuel | 8 | 1857 | 1251 | 167 | 399 | 14 | 76,03 % |
| maison_individuelle | 5 | 1461 | 1110 | 205 | 128 | 4 | 89,70 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 209 | 65835 | 50221 | 8479 | 5234 | 19 | 88,88 % |
| pre_2026 | 17 | 5635 | 4155 | 496 | 852 | 26 | 82,29 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 208 | 65602 | 50074 | 8420 | 5214 | 15 | 88,88 % |
| 3cl_tribu_2024.6.1.0 | 7 | 2724 | 2061 | 210 | 409 | 0 | 83,16 % |
| 3cl_tribu_1.4.25.1 | 4 | 1335 | 1015 | 109 | 167 | 8 | 83,94 % |
| 3cl-2024.6.1.0 | 3 | 649 | 440 | 80 | 106 | 13 | 79,75 % |
| BBS_Slama_2024.6.1.0 | 2 | 645 | 455 | 64 | 121 | 0 | 80,22 % |
| 3cl_tribu_1.4.25.0 | 1 | 282 | 184 | 33 | 49 | 5 | 76,68 % |
| inconnu | 1 | 233 | 147 | 59 | 20 | 4 | 88,03 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Coûts | `cout_5_usages` | 559 | 226 | 65,9 % | hors-tol×559 |
| 2 | Génération chauffage | `conso_ch` | 361 | 83 | 109,0 % | hors-tol×358 manquante×3 |
| 3 | Coûts | `cout_ch` | 343 | 226 | 100,1 % | hors-tol×343 |
| 4 | Coûts | `cout_ecs` | 343 | 225 | 79,5 % | hors-tol×343 |
| 5 | Génération chauffage | `conso_ch_depensier` | 291 | 83 | 211,4 % | hors-tol×288 manquante×3 |
| 6 | Apports | `enum_classe_inertie_id` | 226 | 226 | — | suppl×226 |
| 7 | Apports | `inertie_lourde` | 225 | 225 | 100,0 % | suppl×213 hors-tol×12 |
| 8 | Confort d'été | `isolation_toiture` | 221 | 221 | — | suppl×213 hors-tol×8 |
| 9 | Coûts | `cout_ecs_depensier` | 221 | 221 | 128,0 % | hors-tol×221 |
| 10 | Confort d'été | `enum_indicateur_confort_ete_id` | 220 | 220 | 50,0 % | suppl×213 hors-tol×7 |
| 11 | Coûts | `cout_ch_depensier` | 218 | 218 | 109,0 % | hors-tol×218 |
| 12 | Génération ECS | `rendement_stockage` | 215 | 165 | 34,4 % | suppl×204 hors-tol×11 |
| 13 | Confort d'été | `aspect_traversant` | 215 | 215 | — | suppl×213 hors-tol×2 |
| 14 | Confort d'été | `protection_solaire_exterieure` | 215 | 215 | 100,0 % | suppl×213 hors-tol×2 |
| 15 | Confort d'été | `brasseur_air` | 213 | 213 | — | suppl×213 |
| 16 | GES | `emission_ges_5_usages` | 203 | 92 | 100,0 % | hors-tol×203 |
| 17 | Sorties énergie finale | `conso_5_usages` | 197 | 88 | 68,4 % | hors-tol×197 |
| 18 | GES | `emission_ges_ch` | 177 | 88 | 103,2 % | hors-tol×177 |
| 19 | Génération chauffage | `pveilleuse` | 168 | 164 | — | suppl×168 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 149 | 149 | 663,3 % | hors-tol×149 |
| 21 | Génération ECS | `conso_ecs` | 138 | 29 | 25,6 % | hors-tol×138 |
| 22 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 123 | 123 | 137,9 % | hors-tol×123 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 106 | 106 | 248,6 % | hors-tol×106 |
| 24 | Génération ECS | `conso_ecs_depensier` | 103 | 27 | 19,9 % | hors-tol×103 |
| 25 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 94 | 94 | 138,0 % | hors-tol×94 |
| 26 | GES | `emission_ges_ch_depensier` | 88 | 88 | 100,0 % | hors-tol×88 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages` | 84 | 84 | 50,0 % | hors-tol×84 |
| 28 | Sorties énergie primaire | `ep_conso_ch` | 83 | 83 | 100,0 % | hors-tol×83 |
| 29 | Sorties énergie primaire | `ep_conso_ch_depensier` | 83 | 83 | 100,0 % | hors-tol×83 |
| 30 | Génération chauffage | `rendement_generation` | 81 | 76 | 16,0 % | hors-tol×77 suppl×4 |
| 31 | Auxiliaires | `cout_auxiliaire_generation_ch` | 75 | 75 | 236,2 % | hors-tol×75 |
| 32 | Sorties énergie finale | `conso_5_usages_m2` | 73 | 73 | 50,0 % | hors-tol×73 |
| 33 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 72 | 72 | 50,4 % | hors-tol×72 |
| 34 | GES | `emission_ges_ecs` | 66 | 33 | 100,0 % | hors-tol×66 |
| 35 | Génération chauffage | `pn` | 55 | 51 | 90,9 % | hors-tol×55 |
| 36 | Génération chauffage | `qp0` | 55 | 51 | 90,9 % | hors-tol×55 |
| 37 | Auxiliaires | `conso_auxiliaire_generation_ch` | 55 | 55 | 99,8 % | hors-tol×55 |
| 38 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 55 | 55 | 99,8 % | hors-tol×55 |
| 39 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 55 | 55 | 99,8 % | hors-tol×55 |
| 40 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 55 | 55 | 99,8 % | hors-tol×55 |

## Conformité structurelle du XML produit

Aucune balise produite hors du vocabulaire de `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `zone_pre2026coefelec_diag1793608.xml` | appartement_issu_immeuble | pre_2026 | 723 | 154 | 78,70 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 525 | 125 | 76,19 % |
| `2675E0942311V.xml` | immeuble_collectif | post_2026 | 413 | 94 | 77,24 % |
| `2659E2129582M.xml` | appartement_individuel | post_2026 | 244 | 86 | 64,75 % |
| `2675E0021756W.xml` | immeuble_collectif | post_2026 | 359 | 86 | 76,04 % |
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 326 | 86 | 73,62 % |
| `2688E0016745Q.xml` | appartement_issu_immeuble | post_2026 | 424 | 86 | 79,72 % |
| `2467E3590684Y.xml` | immeuble_collectif | pre_2026 | 465 | 83 | 82,15 % |
| `2612E0854137C.xml` | immeuble_collectif | post_2026 | 277 | 83 | 70,04 % |
| `2659E1259773H.xml` | immeuble_collectif | post_2026 | 301 | 83 | 72,43 % |
| `2675E0069484O.xml` | immeuble_collectif | post_2026 | 298 | 83 | 72,15 % |
| `2682E0040013I.xml` | immeuble_collectif | post_2026 | 257 | 83 | 67,70 % |
| `2682E0040066J.xml` | immeuble_collectif | post_2026 | 257 | 83 | 67,70 % |
| `2682E0040174N.xml` | immeuble_collectif | post_2026 | 257 | 83 | 67,70 % |
| `2682E0040242D.xml` | immeuble_collectif | post_2026 | 257 | 83 | 67,70 % |
| `2594E0486196Q.xml` | immeuble_collectif | pre_2026 | 265 | 82 | 69,06 % |
| `2675E0023041H.xml` | immeuble_collectif | post_2026 | 334 | 81 | 75,75 % |
| `2675E0023980K.xml` | immeuble_collectif | post_2026 | 342 | 81 | 76,32 % |
| `2659E1260921L.xml` | immeuble_collectif | post_2026 | 272 | 80 | 70,59 % |
| `2675E0022293N.xml` | immeuble_collectif | post_2026 | 364 | 80 | 78,02 % |
| `2675E0018503T.xml` | immeuble_collectif | post_2026 | 405 | 79 | 80,49 % |
| `2675E0021134Y.xml` | immeuble_collectif | post_2026 | 445 | 79 | 82,25 % |
| `2624E0037134J.xml` | immeuble_collectif | post_2026 | 305 | 77 | 74,75 % |
| `2667E1172425B.xml` | immeuble_collectif | post_2026 | 303 | 77 | 74,59 % |
| `2667E1171687R.xml` | immeuble_collectif | post_2026 | 342 | 76 | 77,78 % |

## Cas totalement conformes

_Aucun._
