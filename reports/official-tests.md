# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-17T16:37:56+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `89fe7dc` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 541
Exécutés                 : 541
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 541

Valeurs comparées        : 168 269
Exactes                  : 127 768
Dans tolérance           : 23 978
Hors tolérance           : 13 990
Balises manquantes       : 357
Balises supplémentaires  : 2 128
Écarts non numériques    : 48

Conformité               : 90,18 %
```

Sur ces écarts, **4 558 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,89 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 64996 | 62891 | 1537 | 486 | 20 | 62 | 99,13 % |
| Ventilation | 3706 | 2472 | 1174 | 56 | 3 | 1 | 98,38 % |
| Apports | 16694 | 14460 | 1552 | 344 | 0 | 338 | 95,91 % |
| Besoin chauffage | 2596 | 20 | 2239 | 333 | 4 | 0 | 87,02 % |
| Besoin ECS | 4817 | 4463 | 288 | 66 | 0 | 0 | 98,63 % |
| Génération chauffage | 10674 | 4576 | 4169 | 1897 | 24 | 8 | 81,93 % |
| Génération ECS | 10171 | 6622 | 1937 | 1550 | 46 | 16 | 84,15 % |
| Auxiliaires | 18394 | 11933 | 3445 | 3015 | 0 | 1 | 83,60 % |
| Froid | 6872 | 6152 | 55 | 422 | 236 | 7 | 90,32 % |
| PV | 3838 | 3752 | 10 | 67 | 6 | 3 | 98,02 % |
| Sorties énergie finale | 3587 | 1902 | 1067 | 610 | 4 | 4 | 82,77 % |
| Sorties énergie primaire | 4328 | 2046 | 1421 | 831 | 0 | 1 | 80,11 % |
| GES | 7274 | 3338 | 2495 | 1410 | 6 | 6 | 80,19 % |
| Coûts | 6192 | 1277 | 2321 | 2582 | 6 | 6 | 58,11 % |
| Confort d'été | 3022 | 1178 | 0 | 172 | 0 | 1672 | 38,98 % |
| Autre | 1108 | 686 | 268 | 149 | 2 | 3 | 86,10 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 244 | 82117 | 64115 | 12197 | 4286 | 40 | 92,66 % |
| maison_individuelle | 114 | 33816 | 25678 | 4024 | 3916 | 152 | 87,54 % |
| appartement_issu_immeuble | 95 | 32048 | 22880 | 4760 | 3736 | 102 | 85,99 % |
| appartement_individuel | 88 | 20288 | 15095 | 2997 | 2052 | 63 | 88,79 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 389 | 118978 | 91446 | 17580 | 8029 | 222 | 91,34 % |
| pre_2026 | 152 | 49291 | 36322 | 6398 | 5961 | 135 | 86,40 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 352 | 108854 | 85012 | 16184 | 6058 | 12 | 92,67 % |
| 3cl_tribu_1.4.25.1 | 104 | 34698 | 25881 | 4690 | 3792 | 66 | 87,84 % |
| 3cl_tribu_2024.6.1.0 | 24 | 8088 | 6210 | 1044 | 744 | 1 | 89,42 % |
| inconnu | 23 | 6597 | 4369 | 797 | 1220 | 144 | 78,04 % |
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
| 1 | Génération chauffage | `conso_ch` | 816 | 158 | 6 900,0 % | hors-tol×806 manquante×8 suppl×2 |
| 2 | Génération ECS | `conso_ecs` | 703 | 123 | 104,8 % | hors-tol×699 suppl×2 manquante×2 |
| 3 | Génération chauffage | `conso_ch_depensier` | 661 | 161 | 6 900,0 % | hors-tol×655 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 650 | 314 | 936,3 % | hors-tol×646 suppl×2 manquante×2 |
| 5 | Génération ECS | `conso_ecs_depensier` | 591 | 124 | 103,4 % | hors-tol×591 |
| 6 | Coûts | `cout_ecs_depensier` | 506 | 506 | 104,4 % | hors-tol×506 |
| 7 | Coûts | `cout_ch_depensier` | 500 | 500 | 128,8 % | hors-tol×500 |
| 8 | GES | `emission_ges_5_usages` | 469 | 205 | 21 175,5 % | hors-tol×465 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 452 | 192 | 1 016 120,1 % | hors-tol×448 suppl×2 manquante×2 |
| 10 | Confort d'été | `enum_indicateur_confort_ete_id` | 411 | 411 | 200,0 % | hors-tol×77 suppl×334 |
| 11 | Coûts | `cout_ch` | 411 | 183 | 741,6 % | hors-tol×407 suppl×2 manquante×2 |
| 12 | Coûts | `cout_ecs` | 401 | 213 | 65,9 % | hors-tol×397 suppl×2 manquante×2 |
| 13 | Confort d'été | `aspect_traversant` | 371 | 371 | — | hors-tol×37 suppl×334 |
| 14 | Confort d'été | `protection_solaire_exterieure` | 370 | 370 | 100,0 % | hors-tol×36 suppl×334 |
| 15 | Confort d'été | `isolation_toiture` | 355 | 355 | 100,0 % | hors-tol×19 suppl×336 |
| 16 | GES | `emission_ges_ch` | 352 | 153 | 15 993,3 % | hors-tol×348 suppl×2 manquante×2 |
| 17 | Confort d'été | `brasseur_air` | 337 | 337 | 100,0 % | suppl×334 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 337 | 337 | — | suppl×335 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 274 | 274 | 359,4 % | hors-tol×274 |
| 20 | GES | `emission_ges_ecs` | 217 | 107 | 20 212,9 % | hors-tol×213 suppl×2 manquante×2 |
| 21 | Génération ECS | `rendement_stockage` | 216 | 80 | 40,0 % | hors-tol×182 manquante×34 |
| 22 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 208 | 208 | 141,7 % | hors-tol×208 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 195 | 195 | 20 278,1 % | hors-tol×195 |
| 24 | Besoin chauffage | `besoin_ch_depensier` | 172 | 82 | 1 009,5 % | hors-tol×170 manquante×2 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 166 | 166 | 551,8 % | hors-tol×166 |
| 26 | Besoin chauffage | `besoin_ch` | 165 | 72 | 741,6 % | hors-tol×163 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 159 | 159 | 551,2 % | hors-tol×159 |
| 28 | Sorties énergie finale | `conso_5_usages_m2` | 154 | 154 | 257,3 % | hors-tol×154 |
| 29 | GES | `emission_ges_ch_depensier` | 153 | 153 | 12 237,9 % | hors-tol×153 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 147 | 147 | 149,9 % | hors-tol×147 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 144 | 144 | 101,9 % | hors-tol×144 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 138 | 138 | 100,0 % | hors-tol×138 |
| 33 | Génération chauffage | `rendement_generation` | 130 | 85 | 29,0 % | hors-tol×128 manquante×2 |
| 34 | Coûts | `cout_eclairage` | 126 | 126 | 83,7 % | hors-tol×126 |
| 35 | Auxiliaires | `emission_ges_totale_auxiliaire` | 123 | 123 | 41 008,1 % | hors-tol×123 |
| 36 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 122 | 122 | 71,3 % | hors-tol×122 |
| 37 | Auxiliaires | `conso_totale_auxiliaire` | 120 | 120 | 42 072,2 % | hors-tol×120 |
| 38 | Auxiliaires | `ep_conso_totale_auxiliaire` | 120 | 120 | 42 109,2 % | hors-tol×120 |
| 39 | Auxiliaires | `cout_auxiliaire_ventilation` | 118 | 118 | 131,6 % | hors-tol×118 |
| 40 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 115 | 115 | 101,3 % | hors-tol×115 |

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
| `aspect_traversant` | 313 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 313 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 313 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 313 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 313 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 313 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
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
| `ep_conso_ch_depensier` | 100 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 26 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 81 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 106 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `cout_fr_depensier` | 24 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 380 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 380 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 178 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (3 vs 4 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 213 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (9 vs 20 kWh) |
| `cout_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `emission_ges_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `ep_conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_totale_auxiliaire` | 21 | la référence publie un total auxiliaire de 56.6, incompatible avec la somme de ses postes (272.0) |
| `emission_ges_totale_auxiliaire` | 24 | la référence publie un total auxiliaire de 3.6, incompatible avec la somme de ses postes (17.4) |
| `cout_total_auxiliaire` | 29 | la référence publie un total auxiliaire de 19.7, incompatible avec la somme de ses postes (94.4) |
| `ep_conso_totale_auxiliaire` | 21 | la référence publie un total auxiliaire de 107.5, incompatible avec la somme de ses postes (516.8) |
| `besoin_ch_depensier` | 25 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (13746 vs 98393), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 27 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 25 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
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
| `2691E0507798K.xml` | appartement_issu_immeuble | post_2026 | 259 | 102 | 60,62 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |
| `2501E0132767Q.xml` | immeuble_collectif | pre_2026 | 284 | 100 | 64,79 % |
| `2691E0752812A.xml` | appartement_issu_immeuble | post_2026 | 259 | 99 | 61,78 % |
| `2695E2587385R.xml` | appartement_issu_immeuble | post_2026 | 515 | 99 | 80,78 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 463 | 94 | 79,70 % |
| `2401E0490195V.xml` | appartement_individuel | pre_2026 | 242 | 94 | 61,16 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 93 | 67,14 % |

## Cas totalement conformes

_Aucun._
