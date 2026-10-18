# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-18T15:23:40+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `65b4224`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 541
Exécutés                 : 541
Crash                    : 0
Totalement conformes     : 1
Partiellement conformes  : 540

Valeurs comparées        : 168 273
Exactes                  : 127 898
Dans tolérance           : 24 500
Hors tolérance           : 13 372
Balises manquantes       : 335
Balises supplémentaires  : 2 132
Écarts non numériques    : 36

Conformité               : 90,57 %
```

Sur ces écarts, **4 621 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,31 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 64996 | 62901 | 1541 | 472 | 20 | 62 | 99,15 % |
| Ventilation | 3706 | 2472 | 1174 | 56 | 3 | 1 | 98,38 % |
| Apports | 16694 | 14460 | 1556 | 340 | 0 | 338 | 95,94 % |
| Besoin chauffage | 2596 | 20 | 2253 | 323 | 0 | 0 | 87,56 % |
| Besoin ECS | 4817 | 4465 | 288 | 64 | 0 | 0 | 98,67 % |
| Génération chauffage | 10678 | 4578 | 4198 | 1884 | 6 | 12 | 82,19 % |
| Génération ECS | 10171 | 6634 | 1944 | 1531 | 46 | 16 | 84,34 % |
| Auxiliaires | 18394 | 11944 | 3537 | 2912 | 0 | 1 | 84,16 % |
| Froid | 6872 | 6182 | 201 | 246 | 236 | 7 | 92,88 % |
| PV | 3838 | 3752 | 10 | 67 | 6 | 3 | 98,02 % |
| Sorties énergie finale | 3587 | 1905 | 1082 | 592 | 4 | 4 | 83,27 % |
| Sorties énergie primaire | 4328 | 2058 | 1431 | 815 | 0 | 1 | 80,61 % |
| GES | 7274 | 3354 | 2547 | 1348 | 6 | 6 | 81,12 % |
| Coûts | 6192 | 1309 | 2470 | 2401 | 6 | 6 | 61,03 % |
| Confort d'été | 3022 | 1178 | 0 | 172 | 0 | 1672 | 38,98 % |
| Autre | 1108 | 686 | 268 | 149 | 2 | 3 | 86,10 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 244 | 82121 | 64173 | 12469 | 3973 | 26 | 93,05 % |
| maison_individuelle | 114 | 33816 | 25706 | 4147 | 3770 | 152 | 87,98 % |
| appartement_issu_immeuble | 95 | 32048 | 22907 | 4828 | 3641 | 102 | 86,29 % |
| appartement_individuel | 88 | 20288 | 15112 | 3056 | 1988 | 55 | 89,16 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 389 | 118978 | 91506 | 17847 | 7711 | 214 | 91,61 % |
| pre_2026 | 152 | 49295 | 36392 | 6653 | 5661 | 121 | 87,05 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 352 | 108854 | 85058 | 16407 | 5797 | 4 | 92,91 % |
| 3cl_tribu_1.4.25.1 | 104 | 34702 | 25927 | 4905 | 3553 | 52 | 88,58 % |
| 3cl_tribu_2024.6.1.0 | 24 | 8088 | 6229 | 1071 | 698 | 1 | 89,99 % |
| inconnu | 23 | 6597 | 4383 | 838 | 1166 | 144 | 78,87 % |
| 3cl_bbs_V2025.11.1.0 | 13 | 3334 | 1884 | 542 | 755 | 70 | 72,48 % |
| 3cl-2024.6.1.0 | 7 | 1738 | 1188 | 167 | 348 | 28 | 77,65 % |
| 3cl_bbs_V2024.6.1.0 | 4 | 1310 | 778 | 139 | 347 | 18 | 69,79 % |
| BBS_Slama_2024.6.1.0 | 4 | 1281 | 972 | 189 | 106 | 0 | 90,35 % |
| V 2024.6.1.0 | 1 | 294 | 196 | 7 | 85 | 5 | 68,81 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 188 | 33 | 47 | 4 | 78,93 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 173 | 2 | 84 | 0 | 67,05 % |
| V 2025.11.1.0 | 1 | 242 | 158 | 3 | 77 | 4 | 66,26 % |
| V 1.4.25.1 | 1 | 231 | 107 | 5 | 113 | 5 | 48,28 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |
| 3cl_BBS_2024.6.1.0 | 1 | 214 | 130 | 61 | 16 | 0 | 88,84 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 798 | 154 | 6 900,0 % | hors-tol×794 suppl×2 manquante×2 |
| 2 | Génération ECS | `conso_ecs` | 692 | 118 | 104,8 % | hors-tol×688 suppl×2 manquante×2 |
| 3 | Génération chauffage | `conso_ch_depensier` | 654 | 160 | 6 900,0 % | hors-tol×654 |
| 4 | Génération ECS | `conso_ecs_depensier` | 586 | 123 | 103,4 % | hors-tol×586 |
| 5 | Coûts | `cout_5_usages` | 532 | 228 | 936,3 % | hors-tol×528 suppl×2 manquante×2 |
| 6 | Coûts | `cout_ecs_depensier` | 505 | 505 | 103,8 % | hors-tol×505 |
| 7 | Coûts | `cout_ch_depensier` | 500 | 500 | 128,8 % | hors-tol×500 |
| 8 | GES | `emission_ges_5_usages` | 443 | 193 | 21 172,3 % | hors-tol×439 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 437 | 188 | 1 016 120,1 % | hors-tol×433 suppl×2 manquante×2 |
| 10 | Confort d'été | `enum_indicateur_confort_ete_id` | 411 | 411 | 200,0 % | hors-tol×77 suppl×334 |
| 11 | Coûts | `cout_ch` | 392 | 173 | 138,2 % | hors-tol×388 suppl×2 manquante×2 |
| 12 | Confort d'été | `aspect_traversant` | 371 | 371 | — | hors-tol×37 suppl×334 |
| 13 | Confort d'été | `protection_solaire_exterieure` | 370 | 370 | 100,0 % | hors-tol×36 suppl×334 |
| 14 | Coûts | `cout_ecs` | 363 | 179 | 65,9 % | hors-tol×359 suppl×2 manquante×2 |
| 15 | Confort d'été | `isolation_toiture` | 355 | 355 | 100,0 % | hors-tol×19 suppl×336 |
| 16 | GES | `emission_ges_ch` | 338 | 146 | 15 993,3 % | hors-tol×334 suppl×2 manquante×2 |
| 17 | Confort d'été | `brasseur_air` | 337 | 337 | 100,0 % | suppl×334 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 337 | 337 | — | suppl×335 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 274 | 274 | 242,1 % | hors-tol×274 |
| 20 | Génération ECS | `rendement_stockage` | 216 | 80 | 40,0 % | hors-tol×182 manquante×34 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 208 | 208 | 141,7 % | hors-tol×208 |
| 22 | GES | `emission_ges_ecs` | 206 | 102 | 20 212,9 % | hors-tol×202 suppl×2 manquante×2 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 186 | 186 | 20 278,1 % | hors-tol×186 |
| 24 | Besoin chauffage | `besoin_ch_depensier` | 166 | 79 | 1 009,5 % | hors-tol×166 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 163 | 163 | 551,8 % | hors-tol×163 |
| 26 | Besoin chauffage | `besoin_ch` | 157 | 67 | 562,2 % | hors-tol×157 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 155 | 155 | 551,2 % | hors-tol×155 |
| 28 | Sorties énergie finale | `conso_5_usages_m2` | 151 | 151 | 257,3 % | hors-tol×151 |
| 29 | GES | `emission_ges_ch_depensier` | 151 | 151 | 12 237,9 % | hors-tol×151 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 145 | 145 | 98,2 % | hors-tol×145 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 140 | 140 | 84,2 % | hors-tol×140 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 129 | 129 | 100,0 % | hors-tol×129 |
| 33 | Génération chauffage | `rendement_generation` | 126 | 82 | 54,1 % | hors-tol×126 |
| 34 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 122 | 122 | 71,3 % | hors-tol×122 |
| 35 | Coûts | `cout_eclairage` | 121 | 121 | 83,7 % | hors-tol×121 |
| 36 | Auxiliaires | `cout_auxiliaire_ventilation` | 118 | 118 | 114,8 % | hors-tol×118 |
| 37 | Auxiliaires | `conso_totale_auxiliaire` | 113 | 113 | 42 072,2 % | hors-tol×113 |
| 38 | Auxiliaires | `emission_ges_totale_auxiliaire` | 113 | 113 | 41 008,1 % | hors-tol×113 |
| 39 | Auxiliaires | `ep_conso_totale_auxiliaire` | 113 | 113 | 42 109,2 % | hors-tol×113 |
| 40 | GES | `emission_ges_ecs_depensier` | 109 | 109 | 15 144,9 % | hors-tol×109 |

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
| `ep_conso_ch_depensier` | 98 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 26 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 81 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 104 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
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
| `emission_ges_totale_auxiliaire` | 21 | la référence publie un total auxiliaire de 3.6, incompatible avec la somme de ses postes (17.4) |
| `cout_total_auxiliaire` | 27 | la référence publie un total auxiliaire de 19.7, incompatible avec la somme de ses postes (94.4) |
| `ep_conso_totale_auxiliaire` | 21 | la référence publie un total auxiliaire de 107.5, incompatible avec la somme de ses postes (516.8) |
| `besoin_ch_depensier` | 25 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (13746 vs 98393), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 35 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 26 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `production_ch_solaire` | 8 | la référence publie production_ch_solaire = 14225.096 et conso_ch = 11968.755 pour fch = 0.28 : §9.2 p.62 et §9.6 p.65 posent les deux sur le même produit Bch × Σ(part × INT × Ich), d'où Prod / Cch = Fch / (1 − Fch) = 0.3889, quand le fichier publie 1.1885 |
| `ep_conso_ecs` | 1 | la référence publie ep_conso_ecs et ep_conso_ecs_depensier permutés : leurs rapports à la consommation valent 1.41000 et 0.70922 en direct, mais 1.00000 de part et d'autre une fois croisés |
| `emission_ges_ecs` | 27 | la référence publie emission_ges_ecs = 972.3 mais sa propre ventilation par énergie en somme 10.3 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `emission_ges_ch` | 33 | la référence publie emission_ges_ch = 5040.5 mais sa propre ventilation par énergie en somme 53.4 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `k` | 19 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `cout_ch` | 2 | la référence publie cout_ch = 1214.0 mais sa propre ventilation par énergie en somme 0.0 sur 1 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ch` | 25 | la référence publie conso_ch = 19312.3 mais sa propre ventilation par énergie en somme 19542.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ecs` | 253 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 248 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 186 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `cop` | 3 | la référence déclare une saisie forfaitaire (enum_methode_saisie_carac_sys_id = 1) mais publie cop = 3.07 : §14.2 p.95 ne tabule que 2 ; 2.1 ; 2.2 ; 2.3 ; 2.4 ; 2.5 ; 2.6 ; 2.8 ; 2.9 pour les chauffe-eau thermodynamiques |
| `q4pa_conv` | 2 | la référence déclare une perméabilité mesurée de 1.700 m³/(h.m²) (enum_methode_saisie_q4pa_conv_id = 2) mais publie 1.500, la valeur forfaitaire : §4 p.44 ne l'emploie qu'à défaut de mesure |
| `rendement_generation` | 25 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 4 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `upb` | 4 | la référence déclare un plancher bas isolé (enum_type_isolation_id = 3) mais publie upb = upb0 = 2.000, le coefficient non isolé : §3.2.2 p.20 ne rend upb = upb0 que sur la branche « Isolation ? Non » |
| `umur0` | 52 | la référence déclare un doublage (enum_type_doublage_id = 5, Rdoublage = 0,21 m².K/W) mais publie umur0 = 2.500 : §3.2.1.2 p.16 plafonne alors Umur0 à 1 / (1/3,90 + 0,21) = 2.144, la plus grande valeur tabulée étant 3,90 W/(m².K) |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 140 | 46,56 % |
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 139 | 84,62 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 122 | 85,58 % |
| `2406E2911962X.xml` | appartement_individuel | pre_2026 | 231 | 119 | 48,48 % |
| `2401E2888143P.xml` | maison_individuelle | pre_2026 | 344 | 113 | 67,15 % |
| `2601E0370414Y.xml` | maison_individuelle | post_2026 | 437 | 111 | 74,60 % |
| `2501E0139457Y.xml` | immeuble_collectif | pre_2026 | 421 | 110 | 73,87 % |
| `2601E0089001J.xml` | maison_individuelle | post_2026 | 374 | 108 | 71,12 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 107 | 85,22 % |
| `2601E0076852C.xml` | maison_individuelle | post_2026 | 389 | 107 | 72,49 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 106 | 65,47 % |
| `2401E0473146C.xml` | maison_individuelle | pre_2026 | 368 | 105 | 71,47 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 105 | 65,80 % |
| `2501E0122493M.xml` | immeuble_collectif | pre_2026 | 276 | 105 | 61,96 % |
| `2601E0869643B.xml` | maison_individuelle | post_2026 | 260 | 101 | 61,15 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |
| `2695E2587385R.xml` | appartement_issu_immeuble | post_2026 | 515 | 99 | 80,78 % |
| `2501E0132767Q.xml` | immeuble_collectif | pre_2026 | 284 | 98 | 65,49 % |
| `2401E0490195V.xml` | appartement_individuel | pre_2026 | 242 | 94 | 61,16 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 93 | 67,14 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 294 | 91 | 69,05 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2268184I.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |

## Cas totalement conformes

- `2659E2613722G.xml` (appartement_individuel, post_2026) — 218 valeurs
