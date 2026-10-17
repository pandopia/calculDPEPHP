# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-17T08:49:12+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `d2c52ee`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 500
Exécutés                 : 500
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 500

Valeurs comparées        : 155 570
Exactes                  : 118 062
Dans tolérance           : 22 276
Hors tolérance           : 12 877
Balises manquantes       : 322
Balises supplémentaires  : 1 988
Écarts non numériques    : 45

Conformité               : 90,21 %
```

Sur ces écarts, **4 204 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,91 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 59987 | 58123 | 1362 | 429 | 19 | 54 | 99,16 % |
| Ventilation | 3422 | 2274 | 1094 | 50 | 3 | 1 | 98,42 % |
| Apports | 15446 | 13395 | 1422 | 313 | 0 | 316 | 95,93 % |
| Besoin chauffage | 2412 | 16 | 2107 | 285 | 4 | 0 | 88,02 % |
| Besoin ECS | 4462 | 4149 | 281 | 32 | 0 | 0 | 99,28 % |
| Génération chauffage | 9940 | 4234 | 3880 | 1794 | 24 | 8 | 81,63 % |
| Génération ECS | 9438 | 6186 | 1847 | 1349 | 40 | 16 | 85,11 % |
| Auxiliaires | 17000 | 10964 | 3215 | 2820 | 0 | 1 | 83,41 % |
| Froid | 6348 | 5669 | 55 | 409 | 208 | 7 | 90,17 % |
| PV | 3551 | 3465 | 10 | 67 | 6 | 3 | 97,86 % |
| Sorties énergie finale | 3312 | 1754 | 992 | 558 | 4 | 4 | 82,91 % |
| Sorties énergie primaire | 4000 | 1889 | 1320 | 763 | 0 | 1 | 80,23 % |
| GES | 6718 | 3087 | 2292 | 1309 | 6 | 6 | 80,07 % |
| Coûts | 5718 | 1150 | 2153 | 2403 | 6 | 6 | 57,76 % |
| Confort d'été | 2795 | 1086 | 0 | 147 | 0 | 1562 | 38,86 % |
| Autre | 1021 | 621 | 246 | 149 | 2 | 3 | 84,92 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 244 | 82117 | 64115 | 12197 | 4286 | 40 | 92,66 % |
| maison_individuelle | 113 | 33571 | 25481 | 4016 | 3876 | 152 | 87,57 % |
| appartement_issu_immeuble | 67 | 22409 | 15573 | 3454 | 2882 | 71 | 84,65 % |
| appartement_individuel | 76 | 17473 | 12893 | 2609 | 1833 | 59 | 88,34 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 349 | 106532 | 81912 | 15920 | 6949 | 187 | 91,53 % |
| pre_2026 | 151 | 49038 | 36150 | 6356 | 5928 | 135 | 86,41 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 319 | 98270 | 76661 | 14655 | 5483 | 12 | 92,62 % |
| 3cl_tribu_1.4.25.1 | 104 | 34698 | 25881 | 4690 | 3792 | 66 | 87,84 % |
| 3cl_tribu_2024.6.1.0 | 23 | 7835 | 6038 | 1002 | 711 | 1 | 89,59 % |
| inconnu | 17 | 4977 | 3344 | 669 | 792 | 113 | 80,36 % |
| 3cl_bbs_V2025.11.1.0 | 13 | 3334 | 1884 | 539 | 758 | 70 | 72,39 % |
| 3cl-2024.6.1.0 | 7 | 1738 | 1185 | 167 | 349 | 28 | 77,48 % |
| 3cl_bbs_V2024.6.1.0 | 4 | 1310 | 778 | 139 | 347 | 18 | 69,79 % |
| BBS_Slama_2024.6.1.0 | 4 | 1281 | 972 | 178 | 117 | 0 | 89,49 % |
| V 2024.6.1.0 | 1 | 294 | 196 | 7 | 85 | 5 | 68,81 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 187 | 32 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 173 | 2 | 84 | 0 | 67,05 % |
| V 1.4.25.1 | 1 | 231 | 106 | 4 | 115 | 5 | 47,41 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |
| 3cl_BBS_2024.6.1.0 | 1 | 214 | 130 | 62 | 15 | 0 | 89,30 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 763 | 147 | 6 900,0 % | hors-tol×753 manquante×8 suppl×2 |
| 2 | Génération chauffage | `conso_ch_depensier` | 616 | 150 | 6 900,0 % | hors-tol×610 manquante×6 |
| 3 | Génération ECS | `conso_ecs` | 614 | 108 | 104,8 % | hors-tol×610 suppl×2 manquante×2 |
| 4 | Coûts | `cout_5_usages` | 589 | 284 | 936,3 % | hors-tol×585 suppl×2 manquante×2 |
| 5 | Génération ECS | `conso_ecs_depensier` | 512 | 109 | 103,4 % | hors-tol×512 |
| 6 | Coûts | `cout_ecs_depensier` | 476 | 476 | 104,4 % | hors-tol×476 |
| 7 | Coûts | `cout_ch_depensier` | 472 | 472 | 128,8 % | hors-tol×472 |
| 8 | GES | `emission_ges_5_usages` | 435 | 189 | 21 175,5 % | hors-tol×431 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 417 | 176 | 1 016 120,1 % | hors-tol×413 suppl×2 manquante×2 |
| 10 | Coûts | `cout_ch` | 389 | 172 | 741,6 % | hors-tol×385 suppl×2 manquante×2 |
| 11 | Confort d'été | `enum_indicateur_confort_ete_id` | 379 | 379 | 100,0 % | hors-tol×67 suppl×312 |
| 12 | Coûts | `cout_ecs` | 372 | 198 | 65,9 % | hors-tol×368 suppl×2 manquante×2 |
| 13 | Confort d'été | `protection_solaire_exterieure` | 344 | 344 | 100,0 % | hors-tol×32 suppl×312 |
| 14 | Confort d'été | `aspect_traversant` | 341 | 341 | — | hors-tol×29 suppl×312 |
| 15 | GES | `emission_ges_ch` | 333 | 144 | 15 993,3 % | hors-tol×329 suppl×2 manquante×2 |
| 16 | Confort d'été | `isolation_toiture` | 330 | 330 | 100,0 % | hors-tol×16 suppl×314 |
| 17 | Confort d'été | `brasseur_air` | 315 | 315 | 100,0 % | suppl×312 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 315 | 315 | — | suppl×313 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 260 | 260 | 359,4 % | hors-tol×260 |
| 20 | GES | `emission_ges_ecs` | 198 | 98 | 20 212,9 % | hors-tol×194 suppl×2 manquante×2 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 197 | 197 | 141,7 % | hors-tol×197 |
| 22 | Génération ECS | `rendement_stockage` | 186 | 70 | 40,0 % | hors-tol×158 manquante×28 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 172 | 172 | 20 278,1 % | hors-tol×172 |
| 24 | Sorties énergie primaire | `ep_conso_5_usages` | 151 | 151 | 551,8 % | hors-tol×151 |
| 25 | Besoin chauffage | `besoin_ch_depensier` | 148 | 71 | 1 009,5 % | hors-tol×146 manquante×2 |
| 26 | GES | `emission_ges_ch_depensier` | 143 | 143 | 12 237,9 % | hors-tol×143 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 143 | 143 | 551,2 % | hors-tol×143 |
| 28 | Besoin chauffage | `besoin_ch` | 141 | 61 | 741,6 % | hors-tol×139 manquante×2 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 139 | 139 | 257,3 % | hors-tol×139 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 138 | 138 | 149,9 % | hors-tol×138 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 136 | 136 | 101,9 % | hors-tol×136 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 133 | 133 | 100,0 % | hors-tol×133 |
| 33 | Génération chauffage | `rendement_generation` | 125 | 83 | 29,0 % | hors-tol×123 manquante×2 |
| 34 | Coûts | `cout_eclairage` | 117 | 117 | 83,7 % | hors-tol×117 |
| 35 | Auxiliaires | `emission_ges_totale_auxiliaire` | 114 | 114 | 41 008,1 % | hors-tol×114 |
| 36 | Auxiliaires | `conso_totale_auxiliaire` | 111 | 111 | 42 072,2 % | hors-tol×111 |
| 37 | Auxiliaires | `ep_conso_totale_auxiliaire` | 111 | 111 | 42 109,2 % | hors-tol×111 |
| 38 | Auxiliaires | `conso_auxiliaire_generation_ch` | 110 | 110 | 100,0 % | hors-tol×110 |
| 39 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 110 | 110 | 101,3 % | hors-tol×110 |
| 40 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 110 | 110 | 4 460,4 % | hors-tol×110 |

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
| `aspect_traversant` | 291 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 291 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 291 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 291 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 291 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 291 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 23 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 16730 publié au lieu de 43884 |
| `apport_interne_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `apport_solaire_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `fraction_apport_gratuit_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `fraction_apport_gratuit_depensier_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `besoin_ecs` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_distribution_ecs_recup` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_distribution_ecs_recup_depensier` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_generateur_ch_recup` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_generateur_ch_recup_depensier` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `qp0` | 24 | la référence sérialise QP0 en kW (1.500) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=125000 W est 1500 W |
| `ep_conso_fr_depensier` | 25 | la référence recopie ep_conso_fr dans ep_conso_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `ep_conso_ecs_depensier` | 71 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 97 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (151119 vs 195947 kWh) |
| `emission_ges_fr_depensier` | 25 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 73 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 102 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (151119 vs 195947 kWh) |
| `cout_fr_depensier` | 23 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 371 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 371 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 170 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 205 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `cout_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `emission_ges_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `ep_conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_totale_auxiliaire` | 21 | la référence publie un total auxiliaire de 56.6, incompatible avec la somme de ses postes (272.0) |
| `emission_ges_totale_auxiliaire` | 24 | la référence publie un total auxiliaire de 3.6, incompatible avec la somme de ses postes (17.4) |
| `cout_total_auxiliaire` | 29 | la référence publie un total auxiliaire de 19.7, incompatible avec la somme de ses postes (94.4) |
| `ep_conso_totale_auxiliaire` | 21 | la référence publie un total auxiliaire de 107.5, incompatible avec la somme de ses postes (516.8) |
| `besoin_ch_depensier` | 25 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (13746 vs 98393), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 23 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 22 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `k` | 19 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `conso_ecs` | 231 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 230 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 162 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 25 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 4 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `upb` | 3 | la référence déclare un plancher bas isolé (résistance 3.50 m².K/W saisie et justifiée) mais publie upb = upb0 = 2.000, le coefficient non isolé : §3.2.2 p.20 impose upb = 1 / (1/upb0 + R) = 0.250 |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 140 | 46,56 % |
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 902 | 138 | 84,70 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 123 | 85,46 % |
| `2406E2911962X.xml` | appartement_individuel | pre_2026 | 231 | 121 | 47,62 % |
| `2401E2888143P.xml` | maison_individuelle | pre_2026 | 344 | 116 | 66,28 % |
| `2601E0370414Y.xml` | maison_individuelle | post_2026 | 437 | 112 | 74,37 % |
| `2601E0076852C.xml` | maison_individuelle | post_2026 | 389 | 109 | 71,98 % |
| `2501E0139457Y.xml` | immeuble_collectif | pre_2026 | 421 | 109 | 74,11 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 722 | 106 | 85,32 % |
| `2601E0089001J.xml` | maison_individuelle | post_2026 | 374 | 106 | 71,66 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 106 | 65,47 % |
| `2501E0122493M.xml` | immeuble_collectif | pre_2026 | 276 | 106 | 61,59 % |
| `2401E0473146C.xml` | maison_individuelle | pre_2026 | 368 | 105 | 71,47 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 105 | 65,80 % |
| `2601E0869643B.xml` | maison_individuelle | post_2026 | 260 | 102 | 60,77 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |
| `2501E0132767Q.xml` | immeuble_collectif | pre_2026 | 284 | 100 | 64,79 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 463 | 94 | 79,70 % |
| `2401E0490195V.xml` | appartement_individuel | pre_2026 | 242 | 94 | 61,16 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 93 | 67,14 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 294 | 91 | 69,05 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2268184I.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |

## Cas totalement conformes

_Aucun._
