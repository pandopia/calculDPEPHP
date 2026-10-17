# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-17T10:16:13+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `5ef939e`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 525
Exécutés                 : 525
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 525

Valeurs comparées        : 164 034
Exactes                  : 124 744
Dans tolérance           : 23 214
Hors tolérance           : 13 642
Balises manquantes       : 326
Balises supplémentaires  : 2 060
Écarts non numériques    : 48

Conformité               : 90,20 %
```

Sur ces écarts, **4 477 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,93 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 63565 | 61537 | 1510 | 445 | 19 | 54 | 99,19 % |
| Ventilation | 3594 | 2379 | 1158 | 53 | 3 | 1 | 98,41 % |
| Apports | 16320 | 14175 | 1491 | 326 | 0 | 328 | 95,99 % |
| Besoin chauffage | 2532 | 20 | 2195 | 313 | 4 | 0 | 87,48 % |
| Besoin ECS | 4705 | 4386 | 283 | 36 | 0 | 0 | 99,23 % |
| Génération chauffage | 10386 | 4435 | 4037 | 1882 | 24 | 8 | 81,57 % |
| Génération ECS | 9983 | 6477 | 1927 | 1523 | 40 | 16 | 84,18 % |
| Auxiliaires | 17850 | 11651 | 3282 | 2916 | 0 | 1 | 83,66 % |
| Froid | 6656 | 5960 | 55 | 422 | 212 | 7 | 90,37 % |
| PV | 3726 | 3640 | 10 | 67 | 6 | 3 | 97,96 % |
| Sorties énergie finale | 3475 | 1843 | 1026 | 598 | 4 | 4 | 82,56 % |
| Sorties énergie primaire | 4200 | 1975 | 1375 | 820 | 0 | 1 | 79,76 % |
| GES | 7050 | 3238 | 2386 | 1395 | 6 | 6 | 79,77 % |
| Coûts | 6000 | 1232 | 2221 | 2535 | 6 | 6 | 57,55 % |
| Confort d'été | 2932 | 1148 | 0 | 162 | 0 | 1622 | 39,15 % |
| Autre | 1060 | 648 | 258 | 149 | 2 | 3 | 85,47 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 244 | 82117 | 64115 | 12197 | 4286 | 40 | 92,66 % |
| maison_individuelle | 114 | 33816 | 25678 | 4024 | 3916 | 152 | 87,54 % |
| appartement_issu_immeuble | 79 | 27813 | 19856 | 3996 | 3388 | 71 | 85,52 % |
| appartement_individuel | 88 | 20288 | 15095 | 2997 | 2052 | 63 | 88,79 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 373 | 114743 | 88422 | 16816 | 7681 | 191 | 91,42 % |
| pre_2026 | 152 | 49291 | 36322 | 6398 | 5961 | 135 | 86,40 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 342 | 106239 | 83013 | 15548 | 6138 | 12 | 92,48 % |
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
| V 2025.11.1.0 | 1 | 242 | 158 | 3 | 77 | 4 | 66,26 % |
| V 1.4.25.1 | 1 | 231 | 106 | 4 | 115 | 5 | 47,41 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |
| 3cl_BBS_2024.6.1.0 | 1 | 214 | 130 | 62 | 15 | 0 | 89,30 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 808 | 155 | 6 900,0 % | hors-tol×798 manquante×8 suppl×2 |
| 2 | Génération ECS | `conso_ecs` | 691 | 117 | 104,8 % | hors-tol×687 suppl×2 manquante×2 |
| 3 | Génération chauffage | `conso_ch_depensier` | 653 | 158 | 6 900,0 % | hors-tol×647 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 639 | 309 | 936,3 % | hors-tol×635 suppl×2 manquante×2 |
| 5 | Génération ECS | `conso_ecs_depensier` | 579 | 118 | 103,4 % | hors-tol×579 |
| 6 | Coûts | `cout_ecs_depensier` | 492 | 492 | 104,4 % | hors-tol×492 |
| 7 | Coûts | `cout_ch_depensier` | 485 | 485 | 128,8 % | hors-tol×485 |
| 8 | GES | `emission_ges_5_usages` | 463 | 202 | 21 175,5 % | hors-tol×459 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 445 | 189 | 1 016 120,1 % | hors-tol×441 suppl×2 manquante×2 |
| 10 | Coûts | `cout_ch` | 409 | 182 | 741,6 % | hors-tol×405 suppl×2 manquante×2 |
| 11 | Coûts | `cout_ecs` | 399 | 212 | 65,9 % | hors-tol×395 suppl×2 manquante×2 |
| 12 | Confort d'été | `enum_indicateur_confort_ete_id` | 397 | 397 | 100,0 % | hors-tol×73 suppl×324 |
| 13 | Confort d'été | `aspect_traversant` | 359 | 359 | — | hors-tol×35 suppl×324 |
| 14 | Confort d'été | `protection_solaire_exterieure` | 357 | 357 | 100,0 % | hors-tol×33 suppl×324 |
| 15 | GES | `emission_ges_ch` | 350 | 152 | 15 993,3 % | hors-tol×346 suppl×2 manquante×2 |
| 16 | Confort d'été | `isolation_toiture` | 344 | 344 | 100,0 % | hors-tol×18 suppl×326 |
| 17 | Confort d'été | `brasseur_air` | 327 | 327 | 100,0 % | suppl×324 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 327 | 327 | — | suppl×325 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 263 | 263 | 359,4 % | hors-tol×263 |
| 20 | GES | `emission_ges_ecs` | 217 | 107 | 20 212,9 % | hors-tol×213 suppl×2 manquante×2 |
| 21 | Génération ECS | `rendement_stockage` | 210 | 74 | 40,0 % | hors-tol×182 manquante×28 |
| 22 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 200 | 200 | 141,7 % | hors-tol×200 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 190 | 190 | 20 278,1 % | hors-tol×190 |
| 24 | Sorties énergie primaire | `ep_conso_5_usages` | 163 | 163 | 551,8 % | hors-tol×163 |
| 25 | Besoin chauffage | `besoin_ch_depensier` | 162 | 76 | 1 009,5 % | hors-tol×160 manquante×2 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 156 | 156 | 551,2 % | hors-tol×156 |
| 27 | Besoin chauffage | `besoin_ch` | 155 | 66 | 741,6 % | hors-tol×153 manquante×2 |
| 28 | Sorties énergie finale | `conso_5_usages_m2` | 151 | 151 | 257,3 % | hors-tol×151 |
| 29 | GES | `emission_ges_ch_depensier` | 151 | 151 | 12 237,9 % | hors-tol×151 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 145 | 145 | 149,9 % | hors-tol×145 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 143 | 143 | 101,9 % | hors-tol×143 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 133 | 133 | 100,0 % | hors-tol×133 |
| 33 | Génération chauffage | `rendement_generation` | 131 | 86 | 29,0 % | hors-tol×129 manquante×2 |
| 34 | Coûts | `cout_eclairage` | 123 | 123 | 83,7 % | hors-tol×123 |
| 35 | Auxiliaires | `emission_ges_totale_auxiliaire` | 120 | 120 | 41 008,1 % | hors-tol×120 |
| 36 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 118 | 118 | 71,3 % | hors-tol×118 |
| 37 | Auxiliaires | `conso_totale_auxiliaire` | 117 | 117 | 42 072,2 % | hors-tol×117 |
| 38 | Auxiliaires | `ep_conso_totale_auxiliaire` | 117 | 117 | 42 109,2 % | hors-tol×117 |
| 39 | Auxiliaires | `cout_auxiliaire_ventilation` | 114 | 114 | 131,6 % | hors-tol×114 |
| 40 | Auxiliaires | `conso_auxiliaire_generation_ch` | 110 | 110 | 100,0 % | hors-tol×110 |

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
| `ep_conso_ecs_depensier` | 79 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 103 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 26 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 81 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 109 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
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
| `emission_ges_ecs` | 28 | la référence publie emission_ges_ecs = 972.3 mais sa propre ventilation par énergie en somme 10.3 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `emission_ges_ch` | 33 | la référence publie emission_ges_ch = 5040.5 mais sa propre ventilation par énergie en somme 53.4 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `k` | 19 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `cout_ch` | 2 | la référence publie cout_ch = 1214.0 mais sa propre ventilation par énergie en somme 0.0 sur 1 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ch` | 25 | la référence publie conso_ch = 19312.3 mais sa propre ventilation par énergie en somme 19542.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
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
