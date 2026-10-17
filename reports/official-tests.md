# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-17T10:04:06+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `642d370`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 513
Exécutés                 : 513
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 513

Valeurs comparées        : 161 219
Exactes                  : 122 542
Dans tolérance           : 22 826
Hors tolérance           : 13 423
Balises manquantes       : 322
Balises supplémentaires  : 2 060
Écarts non numériques    : 46

Conformité               : 90,17 %
```

Sur ces écarts, **4 461 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,94 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 62688 | 60708 | 1476 | 431 | 19 | 54 | 99,20 % |
| Ventilation | 3513 | 2329 | 1130 | 50 | 3 | 1 | 98,46 % |
| Apports | 16080 | 13968 | 1463 | 321 | 0 | 328 | 95,96 % |
| Besoin chauffage | 2484 | 20 | 2151 | 309 | 4 | 0 | 87,40 % |
| Besoin ECS | 4621 | 4304 | 283 | 34 | 0 | 0 | 99,26 % |
| Génération chauffage | 10241 | 4375 | 3960 | 1874 | 24 | 8 | 81,39 % |
| Génération ECS | 9846 | 6376 | 1917 | 1497 | 40 | 16 | 84,23 % |
| Auxiliaires | 17442 | 11302 | 3272 | 2867 | 0 | 1 | 83,56 % |
| Froid | 6508 | 5816 | 55 | 422 | 208 | 7 | 90,21 % |
| PV | 3642 | 3556 | 10 | 67 | 6 | 3 | 97,91 % |
| Sorties énergie finale | 3389 | 1791 | 1005 | 585 | 4 | 4 | 82,50 % |
| Sorties énergie primaire | 4104 | 1928 | 1343 | 804 | 0 | 1 | 79,70 % |
| GES | 6879 | 3151 | 2332 | 1366 | 6 | 6 | 79,71 % |
| Coûts | 5853 | 1171 | 2171 | 2499 | 6 | 6 | 57,10 % |
| Confort d'été | 2872 | 1102 | 0 | 148 | 0 | 1622 | 38,37 % |
| Autre | 1057 | 645 | 258 | 149 | 2 | 3 | 85,43 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 244 | 82117 | 64115 | 12197 | 4286 | 40 | 92,66 % |
| maison_individuelle | 114 | 33816 | 25678 | 4024 | 3916 | 152 | 87,54 % |
| appartement_issu_immeuble | 79 | 27813 | 19856 | 3996 | 3388 | 71 | 85,52 % |
| appartement_individuel | 76 | 17473 | 12893 | 2609 | 1833 | 59 | 88,34 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 361 | 111928 | 86220 | 16428 | 7462 | 187 | 91,41 % |
| pre_2026 | 152 | 49291 | 36322 | 6398 | 5961 | 135 | 86,40 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 331 | 103666 | 80969 | 15163 | 5996 | 12 | 92,44 % |
| 3cl_tribu_1.4.25.1 | 104 | 34698 | 25881 | 4690 | 3792 | 66 | 87,84 % |
| 3cl_tribu_2024.6.1.0 | 24 | 8088 | 6210 | 1044 | 744 | 1 | 89,42 % |
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
| 1 | Génération chauffage | `conso_ch` | 803 | 154 | 6 900,0 % | hors-tol×793 manquante×8 suppl×2 |
| 2 | Génération ECS | `conso_ecs` | 678 | 114 | 104,8 % | hors-tol×674 suppl×2 manquante×2 |
| 3 | Génération chauffage | `conso_ch_depensier` | 650 | 157 | 6 900,0 % | hors-tol×644 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 617 | 297 | 936,3 % | hors-tol×613 suppl×2 manquante×2 |
| 5 | Génération ECS | `conso_ecs_depensier` | 570 | 115 | 103,4 % | hors-tol×570 |
| 6 | Coûts | `cout_ecs_depensier` | 488 | 488 | 104,4 % | hors-tol×488 |
| 7 | Coûts | `cout_ch_depensier` | 484 | 484 | 128,8 % | hors-tol×484 |
| 8 | GES | `emission_ges_5_usages` | 454 | 198 | 21 175,5 % | hors-tol×450 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 436 | 185 | 1 016 120,1 % | hors-tol×432 suppl×2 manquante×2 |
| 10 | Coûts | `cout_ch` | 407 | 181 | 741,6 % | hors-tol×403 suppl×2 manquante×2 |
| 11 | Coûts | `cout_ecs` | 392 | 208 | 65,9 % | hors-tol×388 suppl×2 manquante×2 |
| 12 | Confort d'été | `enum_indicateur_confort_ete_id` | 391 | 391 | 100,0 % | hors-tol×67 suppl×324 |
| 13 | Confort d'été | `protection_solaire_exterieure` | 356 | 356 | 100,0 % | hors-tol×32 suppl×324 |
| 14 | Confort d'été | `aspect_traversant` | 354 | 354 | — | hors-tol×30 suppl×324 |
| 15 | GES | `emission_ges_ch` | 345 | 150 | 15 993,3 % | hors-tol×341 suppl×2 manquante×2 |
| 16 | Confort d'été | `isolation_toiture` | 342 | 342 | 100,0 % | hors-tol×16 suppl×326 |
| 17 | Confort d'été | `brasseur_air` | 327 | 327 | 100,0 % | suppl×324 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 327 | 327 | — | suppl×325 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 263 | 263 | 359,4 % | hors-tol×263 |
| 20 | GES | `emission_ges_ecs` | 210 | 104 | 20 212,9 % | hors-tol×206 suppl×2 manquante×2 |
| 21 | Génération ECS | `rendement_stockage` | 210 | 74 | 40,0 % | hors-tol×182 manquante×28 |
| 22 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 200 | 200 | 141,7 % | hors-tol×200 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 183 | 183 | 20 278,1 % | hors-tol×183 |
| 24 | Besoin chauffage | `besoin_ch_depensier` | 160 | 75 | 1 009,5 % | hors-tol×158 manquante×2 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 159 | 159 | 551,8 % | hors-tol×159 |
| 26 | Besoin chauffage | `besoin_ch` | 153 | 65 | 741,6 % | hors-tol×151 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 152 | 152 | 551,2 % | hors-tol×152 |
| 28 | GES | `emission_ges_ch_depensier` | 149 | 149 | 12 237,9 % | hors-tol×149 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 147 | 147 | 257,3 % | hors-tol×147 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 144 | 144 | 149,9 % | hors-tol×144 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 142 | 142 | 101,9 % | hors-tol×142 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 133 | 133 | 100,0 % | hors-tol×133 |
| 33 | Génération chauffage | `rendement_generation` | 131 | 86 | 29,0 % | hors-tol×129 manquante×2 |
| 34 | Coûts | `cout_eclairage` | 123 | 123 | 83,7 % | hors-tol×123 |
| 35 | Auxiliaires | `emission_ges_totale_auxiliaire` | 117 | 117 | 41 008,1 % | hors-tol×117 |
| 36 | Auxiliaires | `conso_totale_auxiliaire` | 114 | 114 | 42 072,2 % | hors-tol×114 |
| 37 | Auxiliaires | `ep_conso_totale_auxiliaire` | 114 | 114 | 42 109,2 % | hors-tol×114 |
| 38 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 112 | 112 | 71,3 % | hors-tol×112 |
| 39 | Auxiliaires | `conso_auxiliaire_generation_ch` | 110 | 110 | 100,0 % | hors-tol×110 |
| 40 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 110 | 110 | 101,3 % | hors-tol×110 |

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
| `aspect_traversant` | 303 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 303 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 303 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 303 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 303 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 303 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
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
| `ep_conso_fr_depensier` | 26 | la référence recopie ep_conso_fr dans ep_conso_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `ep_conso_ecs_depensier` | 77 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 103 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 26 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 79 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 108 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `cout_fr_depensier` | 24 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 375 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 375 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 173 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 208 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
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
| `emission_ges_ecs` | 25 | la référence publie emission_ges_ecs = 327.6 mais sa propre ventilation par énergie en somme 0.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `emission_ges_ch` | 30 | la référence publie emission_ges_ch = 244.6 mais sa propre ventilation par énergie en somme 0.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `k` | 19 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `cout_ch` | 2 | la référence publie cout_ch = 1214.0 mais sa propre ventilation par énergie en somme 0.0 sur 1 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ch` | 20 | la référence publie conso_ch = 13741.0 mais sa propre ventilation par énergie en somme 27482.0 sur 3 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ecs` | 253 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 248 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 186 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `q4pa_conv` | 2 | la référence déclare une perméabilité mesurée de 1.700 m³/(h.m²) (enum_methode_saisie_q4pa_conv_id = 2) mais publie 1.500, la valeur forfaitaire : §4 p.44 ne l'emploie qu'à défaut de mesure |
| `rendement_generation` | 25 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 4 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `upb` | 4 | la référence déclare un plancher bas isolé (enum_type_isolation_id = 3) mais publie upb = upb0 = 2.000, le coefficient non isolé : §3.2.2 p.20 ne rend upb = upb0 que sur la branche « Isolation ? Non » |

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
| `2695E2587385R.xml` | appartement_issu_immeuble | post_2026 | 515 | 99 | 80,78 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 463 | 94 | 79,70 % |
| `2401E0490195V.xml` | appartement_individuel | pre_2026 | 242 | 94 | 61,16 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 93 | 67,14 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 294 | 91 | 69,05 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |

## Cas totalement conformes

_Aucun._
