# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-06T15:48:14+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `32806d0`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 391
Exécutés                 : 391
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 391

Valeurs comparées        : 123 718
Exactes                  : 94 191
Dans tolérance           : 18 619
Hors tolérance           : 8 843
Balises manquantes       : 151
Balises supplémentaires  : 1 885
Écarts non numériques    : 29

Conformité               : 91,18 %
```

Sur ces écarts, **3 482 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **94,00 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 47813 | 46644 | 920 | 205 | 10 | 34 | 99,48 % |
| Ventilation | 2676 | 1732 | 913 | 29 | 1 | 1 | 98,84 % |
| Apports | 12271 | 10649 | 1093 | 227 | 0 | 302 | 95,69 % |
| Besoin chauffage | 1918 | 8 | 1779 | 127 | 4 | 0 | 93,17 % |
| Besoin ECS | 3677 | 3454 | 204 | 19 | 0 | 0 | 99,48 % |
| Génération chauffage | 7880 | 3347 | 3381 | 1102 | 19 | 31 | 85,38 % |
| Génération ECS | 8079 | 5159 | 1706 | 1190 | 18 | 6 | 84,97 % |
| Auxiliaires | 13294 | 8424 | 2704 | 2165 | 0 | 1 | 83,71 % |
| Froid | 4876 | 4488 | 30 | 259 | 92 | 7 | 92,66 % |
| PV | 2752 | 2738 | 3 | 5 | 6 | 0 | 99,60 % |
| Sorties énergie finale | 2571 | 1398 | 847 | 324 | 0 | 2 | 87,32 % |
| Sorties énergie primaire | 3128 | 1513 | 1126 | 471 | 0 | 1 | 84,37 % |
| GES | 5225 | 2499 | 1916 | 795 | 0 | 3 | 84,50 % |
| Coûts | 4443 | 928 | 1775 | 1737 | 0 | 3 | 60,84 % |
| Confort d'été | 2242 | 679 | 0 | 72 | 0 | 1491 | 30,29 % |
| Autre | 873 | 531 | 222 | 116 | 1 | 3 | 86,25 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 237 | 79380 | 62140 | 11854 | 3924 | 26 | 92,94 % |
| appartement_issu_immeuble | 61 | 20712 | 14433 | 3248 | 2585 | 55 | 85,12 % |
| maison_individuelle | 44 | 12227 | 9299 | 1641 | 1226 | 36 | 89,15 % |
| appartement_individuel | 49 | 11399 | 8319 | 1876 | 1108 | 34 | 89,05 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 293 | 90157 | 69483 | 13972 | 5105 | 86 | 92,27 % |
| pre_2026 | 98 | 33561 | 24708 | 4647 | 3738 | 65 | 87,21 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 278 | 86256 | 67103 | 13223 | 4472 | 11 | 92,83 % |
| 3cl_tribu_1.4.25.1 | 69 | 24435 | 18061 | 3517 | 2566 | 32 | 88,06 % |
| 3cl_tribu_2024.6.1.0 | 16 | 5703 | 4390 | 775 | 465 | 0 | 90,31 % |
| 3cl_bbs_V2025.11.1.0 | 8 | 2061 | 1080 | 328 | 550 | 43 | 68,05 % |
| inconnu | 7 | 1889 | 1277 | 364 | 167 | 40 | 86,55 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 689 | 134 | 107 | 0 | 87,55 % |
| 3cl-2024.6.1.0 | 3 | 644 | 442 | 81 | 105 | 12 | 80,83 % |
| 3cl_bbs_V2024.6.1.0 | 1 | 329 | 210 | 36 | 72 | 4 | 74,55 % |
| V 2024.6.1.0 | 1 | 295 | 196 | 7 | 85 | 5 | 68,58 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 187 | 32 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 170 | 1 | 88 | 0 | 65,52 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |
| 3cl_tribu_1.4.23.7 | 1 | 213 | 127 | 31 | 55 | 0 | 73,83 % |
| 3cl_tribu_1.4.25 | 1 | 204 | 115 | 32 | 55 | 0 | 71,71 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 533 | 83 | 104,8 % | hors-tol×532 suppl×1 |
| 2 | Génération ECS | `conso_ecs_depensier` | 453 | 82 | 103,4 % | hors-tol×453 |
| 3 | Génération chauffage | `conso_ch` | 427 | 84 | 6 900,0 % | hors-tol×420 manquante×6 suppl×1 |
| 4 | Coûts | `cout_5_usages` | 400 | 213 | 936,3 % | hors-tol×399 suppl×1 |
| 5 | Coûts | `cout_ecs_depensier` | 378 | 378 | 122,3 % | hors-tol×378 |
| 6 | Coûts | `cout_ch_depensier` | 375 | 375 | 268,0 % | hors-tol×375 |
| 7 | Génération chauffage | `conso_ch_depensier` | 370 | 87 | 6 900,0 % | hors-tol×364 manquante×6 |
| 8 | Confort d'été | `enum_indicateur_confort_ete_id` | 330 | 330 | 100,0 % | hors-tol×32 suppl×298 |
| 9 | Confort d'été | `aspect_traversant` | 314 | 314 | — | hors-tol×16 suppl×298 |
| 10 | Confort d'été | `protection_solaire_exterieure` | 314 | 314 | 100,0 % | hors-tol×16 suppl×298 |
| 11 | Confort d'été | `isolation_toiture` | 306 | 306 | 100,0 % | hors-tol×7 suppl×299 |
| 12 | Apports | `inertie_lourde` | 300 | 300 | — | suppl×299 hors-tol×1 |
| 13 | Confort d'été | `brasseur_air` | 299 | 299 | 100,0 % | suppl×298 hors-tol×1 |
| 14 | Coûts | `cout_ecs` | 273 | 154 | 73,4 % | hors-tol×272 suppl×1 |
| 15 | GES | `emission_ges_5_usages` | 256 | 121 | 20 316,2 % | hors-tol×255 suppl×1 |
| 16 | Sorties énergie finale | `conso_5_usages` | 245 | 113 | 1 016 150,4 % | hors-tol×244 suppl×1 |
| 17 | Coûts | `cout_ch` | 234 | 115 | 301,0 % | hors-tol×233 suppl×1 |
| 18 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 224 | 224 | 202,6 % | hors-tol×224 |
| 19 | GES | `emission_ges_ch` | 175 | 81 | 9 092,3 % | hors-tol×174 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 172 | 172 | 141,7 % | hors-tol×172 |
| 21 | Génération ECS | `rendement_stockage` | 167 | 53 | 40,0 % | hors-tol×153 manquante×14 |
| 22 | GES | `emission_ges_ecs` | 145 | 72 | 8 232,9 % | hors-tol×144 suppl×1 |
| 23 | Génération chauffage | `rendement_generation` | 114 | 75 | 30,7 % | suppl×24 hors-tol×88 manquante×2 |
| 24 | Auxiliaires | `cout_total_auxiliaire` | 112 | 112 | 3 352,7 % | hors-tol×112 |
| 25 | Auxiliaires | `cout_auxiliaire_generation_ch` | 106 | 106 | 100,0 % | hors-tol×106 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages` | 91 | 91 | 551,8 % | hors-tol×91 |
| 27 | Auxiliaires | `conso_auxiliaire_generation_ch` | 89 | 89 | 100,0 % | hors-tol×89 |
| 28 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 89 | 89 | 100,0 % | hors-tol×89 |
| 29 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 89 | 89 | 100,0 % | hors-tol×89 |
| 30 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 89 | 89 | 100,0 % | hors-tol×89 |
| 31 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 89 | 89 | 100,0 % | hors-tol×89 |
| 32 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 89 | 89 | 170,4 % | hors-tol×89 |
| 33 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 83 | 83 | 551,2 % | hors-tol×83 |
| 34 | Génération chauffage | `qp0` | 83 | 77 | 205 028,2 % | hors-tol×82 suppl×1 |
| 35 | GES | `emission_ges_ch_depensier` | 81 | 81 | 9 093,8 % | hors-tol×81 |
| 36 | Coûts | `cout_eclairage` | 80 | 80 | 83,7 % | hors-tol×80 |
| 37 | Sorties énergie finale | `conso_5_usages_m2` | 78 | 78 | 257,3 % | hors-tol×78 |
| 38 | Génération chauffage | `pn` | 78 | 70 | 275,0 % | hors-tol×74 manquante×3 suppl×1 |
| 39 | Sorties énergie primaire | `ep_conso_ch` | 77 | 77 | 100,0 % | hors-tol×77 |
| 40 | Sorties énergie primaire | `ep_conso_ch_depensier` | 77 | 77 | 100,0 % | hors-tol×77 |

## Écarts imputables à la référence

Écarts démontrables depuis le fichier de référence seul, sans invoquer
notre calcul. Ils restent comptés dans le taux de conformité — la mesure
brute ne se maquille pas — mais **ne doivent pas être « corrigés »** :
reproduire le défaut d'un logiciel tiers éloignerait le moteur de la méthode.

| Balise | Occurrences | Motif |
|---|---:|---|
| `emission_ges_5_usages` | 2 | les deux consommations ECS de la référence divisent les pertes du ballon individuel (434.970 kWh/an selon §11.6.2) par 18 logements : 24.165 au lieu de 434.970 kWh/an par appartement moyen (§17.1.3.2) ; le bilan énergétique et ses classes héritent de cette sous-estimation |
| `emission_ges_5_usages_m2` | 1 | les deux consommations ECS de la référence divisent les pertes du ballon individuel (434.970 kWh/an selon §11.6.2) par 18 logements : 24.165 au lieu de 434.970 kWh/an par appartement moyen (§17.1.3.2) ; le bilan énergétique et ses classes héritent de cette sous-estimation |
| `classe_bilan_dpe` | 1 | les deux consommations ECS de la référence divisent les pertes du ballon individuel (434.970 kWh/an selon §11.6.2) par 18 logements : 24.165 au lieu de 434.970 kWh/an par appartement moyen (§17.1.3.2) ; le bilan énergétique et ses classes héritent de cette sous-estimation |
| `ep_conso_5_usages` | 1 | les deux consommations ECS de la référence divisent les pertes du ballon individuel (434.970 kWh/an selon §11.6.2) par 18 logements : 24.165 au lieu de 434.970 kWh/an par appartement moyen (§17.1.3.2) ; le bilan énergétique et ses classes héritent de cette sous-estimation |
| `ep_conso_5_usages_m2` | 1 | les deux consommations ECS de la référence divisent les pertes du ballon individuel (434.970 kWh/an selon §11.6.2) par 18 logements : 24.165 au lieu de 434.970 kWh/an par appartement moyen (§17.1.3.2) ; le bilan énergétique et ses classes héritent de cette sous-estimation |
| `aspect_traversant` | 283 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 283 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 283 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 283 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 283 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 283 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 15 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 10522 publié au lieu de 47333 |
| `qp0` | 18 | la référence sérialise QP0 en kW (1.500) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=125000 W est 1500 W |
| `cout_fr_depensier` | 19 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 315 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 315 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 150 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 187 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `cout_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `emission_ges_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `ep_conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `cout_total_auxiliaire` | 15 | la référence publie un total auxiliaire de 73.5, incompatible avec la somme de ses postes (176.3) |
| `ep_conso_totale_auxiliaire` | 12 | la référence publie un total auxiliaire de 402.3, incompatible avec la somme de ses postes (964.7) |
| `conso_totale_auxiliaire` | 12 | la référence publie un total auxiliaire de 211.7, incompatible avec la somme de ses postes (507.7) |
| `emission_ges_totale_auxiliaire` | 12 | la référence publie un total auxiliaire de 13.6, incompatible avec la somme de ses postes (32.5) |
| `besoin_ch_depensier` | 13 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (18788 vs 109084), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 7 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 7 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `k` | 15 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `conso_ecs` | 231 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 230 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 157 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 28 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 4 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 902 | 140 | 84,48 % |
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 140 | 46,56 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 123 | 85,46 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 111 | 63,84 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 722 | 109 | 84,90 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 464 | 96 | 79,31 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 93 | 67,14 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 295 | 92 | 68,81 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 90 | 84,72 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2268184I.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 89 | 65,77 % |
| `2675E2152874Y.xml` | appartement_issu_immeuble | post_2026 | 335 | 89 | 73,43 % |
| `2675E0942311V.xml` | immeuble_collectif | post_2026 | 410 | 86 | 79,02 % |
| `2675E0021756W.xml` | immeuble_collectif | post_2026 | 356 | 84 | 76,40 % |
| `2600E0035103I.xml` | immeuble_collectif | post_2026 | 642 | 83 | 87,07 % |
| `2459E4183923N.xml` | immeuble_collectif | pre_2026 | 329 | 83 | 74,77 % |
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 323 | 82 | 74,61 % |
| `2600E0025586H.xml` | maison_individuelle | post_2026 | 354 | 81 | 77,12 % |
| `2400E0124709Q.xml` | maison_individuelle | pre_2026 | 318 | 80 | 74,84 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 80 | 84,56 % |

## Cas totalement conformes

_Aucun._
