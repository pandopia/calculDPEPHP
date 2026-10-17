# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-17T21:23:43+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `acae837` — arbre de travail modifié : 1 fichier(s) src/ non commités_

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
Exactes                  : 127 824
Dans tolérance           : 24 260
Hors tolérance           : 13 663
Balises manquantes       : 357
Balises supplémentaires  : 2 128
Écarts non numériques    : 37

Conformité               : 90,38 %
```

Sur ces écarts, **4 562 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,09 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 64996 | 62891 | 1537 | 486 | 20 | 62 | 99,13 % |
| Ventilation | 3706 | 2472 | 1174 | 56 | 3 | 1 | 98,38 % |
| Apports | 16694 | 14460 | 1552 | 344 | 0 | 338 | 95,91 % |
| Besoin chauffage | 2596 | 20 | 2247 | 325 | 4 | 0 | 87,33 % |
| Besoin ECS | 4817 | 4465 | 288 | 64 | 0 | 0 | 98,67 % |
| Génération chauffage | 10674 | 4576 | 4185 | 1881 | 24 | 8 | 82,08 % |
| Génération ECS | 10171 | 6629 | 1938 | 1542 | 46 | 16 | 84,23 % |
| Auxiliaires | 18394 | 11933 | 3532 | 2928 | 0 | 1 | 84,08 % |
| Froid | 6872 | 6172 | 180 | 277 | 236 | 7 | 92,43 % |
| PV | 3838 | 3752 | 10 | 67 | 6 | 3 | 98,02 % |
| Sorties énergie finale | 3587 | 1904 | 1077 | 598 | 4 | 4 | 83,11 % |
| Sorties énergie primaire | 4328 | 2056 | 1426 | 822 | 0 | 1 | 80,45 % |
| GES | 7274 | 3353 | 2510 | 1385 | 6 | 6 | 80,60 % |
| Coûts | 6192 | 1277 | 2336 | 2567 | 6 | 6 | 58,35 % |
| Confort d'été | 3022 | 1178 | 0 | 172 | 0 | 1672 | 38,98 % |
| Autre | 1108 | 686 | 268 | 149 | 2 | 3 | 86,10 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 244 | 82117 | 64132 | 12354 | 4113 | 40 | 92,87 % |
| maison_individuelle | 114 | 33816 | 25697 | 4097 | 3832 | 152 | 87,81 % |
| appartement_issu_immeuble | 95 | 32048 | 22897 | 4770 | 3709 | 102 | 86,07 % |
| appartement_individuel | 88 | 20288 | 15098 | 3039 | 2009 | 63 | 89,01 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 389 | 118978 | 91461 | 17704 | 7891 | 222 | 91,45 % |
| pre_2026 | 152 | 49291 | 36363 | 6556 | 5772 | 135 | 86,81 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 352 | 108854 | 85026 | 16307 | 5921 | 12 | 92,79 % |
| 3cl_tribu_1.4.25.1 | 104 | 34698 | 25901 | 4812 | 3658 | 66 | 88,25 % |
| 3cl_tribu_2024.6.1.0 | 24 | 8088 | 6229 | 1069 | 700 | 1 | 89,97 % |
| inconnu | 23 | 6597 | 4370 | 798 | 1219 | 144 | 78,07 % |
| 3cl_bbs_V2025.11.1.0 | 13 | 3334 | 1884 | 539 | 758 | 70 | 72,39 % |
| 3cl-2024.6.1.0 | 7 | 1738 | 1187 | 167 | 349 | 28 | 77,59 % |
| 3cl_bbs_V2024.6.1.0 | 4 | 1310 | 778 | 139 | 347 | 18 | 69,79 % |
| BBS_Slama_2024.6.1.0 | 4 | 1281 | 972 | 189 | 106 | 0 | 90,35 % |
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
| 1 | Génération chauffage | `conso_ch` | 807 | 156 | 6 900,0 % | hors-tol×797 manquante×8 suppl×2 |
| 2 | Génération ECS | `conso_ecs` | 699 | 122 | 104,8 % | hors-tol×695 suppl×2 manquante×2 |
| 3 | Génération chauffage | `conso_ch_depensier` | 654 | 160 | 6 900,0 % | hors-tol×648 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 645 | 312 | 936,3 % | hors-tol×641 suppl×2 manquante×2 |
| 5 | Génération ECS | `conso_ecs_depensier` | 588 | 123 | 103,4 % | hors-tol×588 |
| 6 | Coûts | `cout_ecs_depensier` | 506 | 506 | 103,8 % | hors-tol×506 |
| 7 | Coûts | `cout_ch_depensier` | 500 | 500 | 128,8 % | hors-tol×500 |
| 8 | GES | `emission_ges_5_usages` | 459 | 203 | 21 172,3 % | hors-tol×455 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 442 | 190 | 1 016 120,1 % | hors-tol×438 suppl×2 manquante×2 |
| 10 | Confort d'été | `enum_indicateur_confort_ete_id` | 411 | 411 | 200,0 % | hors-tol×77 suppl×334 |
| 11 | Coûts | `cout_ch` | 405 | 180 | 318,9 % | hors-tol×401 suppl×2 manquante×2 |
| 12 | Coûts | `cout_ecs` | 398 | 212 | 65,9 % | hors-tol×394 suppl×2 manquante×2 |
| 13 | Confort d'été | `aspect_traversant` | 371 | 371 | — | hors-tol×37 suppl×334 |
| 14 | Confort d'été | `protection_solaire_exterieure` | 370 | 370 | 100,0 % | hors-tol×36 suppl×334 |
| 15 | Confort d'été | `isolation_toiture` | 355 | 355 | 100,0 % | hors-tol×19 suppl×336 |
| 16 | GES | `emission_ges_ch` | 348 | 151 | 15 993,3 % | hors-tol×344 suppl×2 manquante×2 |
| 17 | Confort d'été | `brasseur_air` | 337 | 337 | 100,0 % | suppl×334 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 337 | 337 | — | suppl×335 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 274 | 274 | 359,4 % | hors-tol×274 |
| 20 | Génération ECS | `rendement_stockage` | 216 | 80 | 40,0 % | hors-tol×182 manquante×34 |
| 21 | GES | `emission_ges_ecs` | 215 | 106 | 20 212,9 % | hors-tol×211 suppl×2 manquante×2 |
| 22 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 208 | 208 | 141,7 % | hors-tol×208 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 189 | 189 | 20 278,1 % | hors-tol×189 |
| 24 | Besoin chauffage | `besoin_ch_depensier` | 168 | 79 | 1 009,5 % | hors-tol×166 manquante×2 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 163 | 163 | 551,8 % | hors-tol×163 |
| 26 | Besoin chauffage | `besoin_ch` | 161 | 69 | 562,2 % | hors-tol×159 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 157 | 157 | 551,2 % | hors-tol×157 |
| 28 | Sorties énergie finale | `conso_5_usages_m2` | 152 | 152 | 257,3 % | hors-tol×152 |
| 29 | GES | `emission_ges_ch_depensier` | 152 | 152 | 12 237,9 % | hors-tol×152 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 146 | 146 | 100,0 % | hors-tol×146 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 143 | 143 | 100,0 % | hors-tol×143 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 130 | 130 | 100,0 % | hors-tol×130 |
| 33 | Génération chauffage | `rendement_generation` | 130 | 85 | 29,0 % | hors-tol×128 manquante×2 |
| 34 | Coûts | `cout_eclairage` | 125 | 125 | 83,7 % | hors-tol×125 |
| 35 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 122 | 122 | 71,3 % | hors-tol×122 |
| 36 | Auxiliaires | `cout_auxiliaire_ventilation` | 117 | 117 | 131,6 % | hors-tol×117 |
| 37 | Auxiliaires | `emission_ges_totale_auxiliaire` | 117 | 117 | 41 008,1 % | hors-tol×117 |
| 38 | Auxiliaires | `conso_totale_auxiliaire` | 114 | 114 | 42 072,2 % | hors-tol×114 |
| 39 | Auxiliaires | `ep_conso_totale_auxiliaire` | 114 | 114 | 42 109,2 % | hors-tol×114 |
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
| `ep_conso_ecs_depensier` | 78 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 99 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 26 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 80 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 105 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
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
| `production_ch_solaire` | 8 | la référence publie production_ch_solaire = 14225.096 et conso_ch = 11968.755 pour fch = 0.28 : §9.2 p.62 et §9.6 p.65 posent les deux sur le même produit Bch × Σ(part × INT × Ich), d'où Prod / Cch = Fch / (1 − Fch) = 0.3889, quand le fichier publie 1.1885 |
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
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 902 | 139 | 84,59 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 122 | 85,58 % |
| `2406E2911962X.xml` | appartement_individuel | pre_2026 | 231 | 121 | 47,62 % |
| `2401E2888143P.xml` | maison_individuelle | pre_2026 | 344 | 114 | 66,86 % |
| `2601E0370414Y.xml` | maison_individuelle | post_2026 | 437 | 112 | 74,37 % |
| `2501E0139457Y.xml` | immeuble_collectif | pre_2026 | 421 | 109 | 74,11 % |
| `2601E0076852C.xml` | maison_individuelle | post_2026 | 389 | 107 | 72,49 % |
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
| `2401E0490195V.xml` | appartement_individuel | pre_2026 | 242 | 94 | 61,16 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 93 | 67,14 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 294 | 91 | 69,05 % |

## Cas totalement conformes

_Aucun._
