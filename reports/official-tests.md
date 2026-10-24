# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-24T11:41:03+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `f89a6cd`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 694
Exécutés                 : 694
Crash                    : 0
Totalement conformes     : 1
Partiellement conformes  : 693

Valeurs comparées        : 216 983
Exactes                  : 162 858
Dans tolérance           : 32 301
Hors tolérance           : 18 568
Balises manquantes       : 732
Balises supplémentaires  : 2 475
Écarts non numériques    : 49

Conformité               : 89,94 %
Avancement               : 92,27 %
```

**Avancement : 92,27 %.** C'est le chiffre de suivi : la part des valeurs **atteignables** qui sont conformes. Il vaut 100 % quand tout ce qui dépend du moteur est juste.

Il se distingue de la conformité brute (89,94 %) par un point : 5 475 écarts sont imputables à la **référence** et non au moteur — le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels, et les reproduire nous éloignerait de la méthode. Ces écarts sortent du dénominateur de l'avancement, jamais du décompte brut : la mesure ne se maquille pas. Détail des imputations plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 83902 | 80669 | 2119 | 957 | 53 | 104 | 98,67 % |
| Ventilation | 4759 | 3212 | 1437 | 98 | 11 | 1 | 97,69 % |
| Apports | 21406 | 18415 | 2069 | 542 | 0 | 380 | 95,69 % |
| Besoin chauffage | 3458 | 28 | 2743 | 687 | 0 | 0 | 80,13 % |
| Besoin ECS | 6036 | 5444 | 516 | 76 | 0 | 0 | 98,74 % |
| Génération chauffage | 14415 | 6268 | 5590 | 2529 | 14 | 14 | 82,26 % |
| Génération ECS | 12505 | 8129 | 2382 | 1836 | 111 | 47 | 84,05 % |
| Auxiliaires | 23596 | 15171 | 4602 | 3822 | 0 | 1 | 83,80 % |
| Froid | 9096 | 7698 | 439 | 484 | 468 | 7 | 89,46 % |
| PV | 4987 | 4723 | 112 | 134 | 15 | 3 | 96,95 % |
| Sorties énergie finale | 4620 | 2386 | 1362 | 850 | 14 | 8 | 81,13 % |
| Sorties énergie primaire | 5552 | 2513 | 1870 | 1137 | 0 | 1 | 78,94 % |
| GES | 9359 | 4050 | 3360 | 1898 | 21 | 12 | 79,18 % |
| Coûts | 7971 | 1610 | 3325 | 3003 | 21 | 12 | 61,91 % |
| Confort d'été | 3820 | 1658 | 0 | 280 | 0 | 1882 | 43,40 % |
| Autre | 1501 | 884 | 375 | 235 | 4 | 3 | 83,88 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 269 | 93514 | 72170 | 14274 | 5306 | 141 | 92,17 % |
| maison_individuelle | 173 | 52181 | 39505 | 6301 | 5993 | 298 | 87,49 % |
| appartement_issu_immeuble | 117 | 39930 | 28890 | 6702 | 3539 | 102 | 88,88 % |
| appartement_individuel | 135 | 31358 | 22293 | 5024 | 3730 | 191 | 86,74 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 464 | 143551 | 109463 | 21930 | 9886 | 373 | 91,24 % |
| pre_2026 | 230 | 73432 | 53395 | 10371 | 8682 | 359 | 86,57 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 402 | 125451 | 97821 | 19558 | 6341 | 6 | 93,27 % |
| 3cl_tribu_1.4.25.1 | 153 | 48087 | 35423 | 6935 | 5280 | 164 | 87,81 % |
| inconnu | 40 | 12116 | 8006 | 1337 | 2395 | 271 | 76,86 % |
| 3cl_tribu_2024.6.1.0 | 27 | 8966 | 6905 | 1268 | 693 | 4 | 90,88 % |
| 3cl_bbs_V2025.11.1.0 | 26 | 8085 | 4822 | 1306 | 1683 | 151 | 75,55 % |
| BBS_Slama_2024.6.1.0 | 15 | 5207 | 3888 | 972 | 267 | 0 | 93,07 % |
| 3cl-2024.6.1.0 | 14 | 4594 | 3264 | 413 | 786 | 95 | 79,80 % |
| 3cl_bbs_V2024.6.1.0 | 4 | 1310 | 774 | 142 | 348 | 18 | 69,71 % |
| 3cl_BBS_2025.11.1.0 | 3 | 810 | 532 | 185 | 78 | 0 | 88,19 % |
| V 1.4.25.1 | 2 | 432 | 196 | 6 | 215 | 10 | 46,54 % |
| V 2024.6.1.0 | 1 | 294 | 196 | 7 | 85 | 5 | 68,81 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 187 | 33 | 48 | 4 | 78,57 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 173 | 2 | 84 | 0 | 67,05 % |
| V 2025.11.1.0 | 1 | 242 | 158 | 3 | 77 | 4 | 66,26 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 1127 | 215 | 6 900,0 % | hors-tol×1116 suppl×4 manquante×7 |
| 2 | Génération chauffage | `conso_ch_depensier` | 913 | 227 | 6 900,0 % | hors-tol×913 |
| 3 | Génération ECS | `conso_ecs` | 836 | 149 | 817,5 % | hors-tol×825 suppl×4 manquante×7 |
| 4 | Génération ECS | `conso_ecs_depensier` | 704 | 160 | 549,6 % | hors-tol×704 |
| 5 | Coûts | `cout_5_usages` | 686 | 278 | 824,9 % | hors-tol×675 suppl×4 manquante×7 |
| 6 | GES | `emission_ges_5_usages` | 641 | 270 | 25 955,5 % | hors-tol×630 suppl×4 manquante×7 |
| 7 | Coûts | `cout_ch_depensier` | 638 | 638 | 116,2 % | hors-tol×638 |
| 8 | Sorties énergie finale | `conso_5_usages` | 631 | 261 | 1 016 120,1 % | hors-tol×620 suppl×4 manquante×7 |
| 9 | Coûts | `cout_ecs_depensier` | 621 | 621 | 540,5 % | hors-tol×621 |
| 10 | Coûts | `cout_ch` | 516 | 222 | 138,2 % | hors-tol×505 suppl×4 manquante×7 |
| 11 | Confort d'été | `enum_indicateur_confort_ete_id` | 508 | 508 | 200,0 % | hors-tol×132 suppl×376 |
| 12 | GES | `emission_ges_ch` | 496 | 211 | 17 433,3 % | hors-tol×485 suppl×4 manquante×7 |
| 13 | Confort d'été | `protection_solaire_exterieure` | 433 | 433 | 100,0 % | hors-tol×57 suppl×376 |
| 14 | Coûts | `cout_ecs` | 428 | 207 | 799,1 % | hors-tol×417 suppl×4 manquante×7 |
| 15 | Confort d'été | `aspect_traversant` | 424 | 424 | 100,0 % | hors-tol×48 suppl×376 |
| 16 | Confort d'été | `isolation_toiture` | 413 | 413 | 100,0 % | hors-tol×35 suppl×378 |
| 17 | Apports | `inertie_lourde` | 388 | 388 | — | suppl×377 hors-tol×11 |
| 18 | Confort d'été | `brasseur_air` | 384 | 384 | 100,0 % | suppl×376 hors-tol×8 |
| 19 | Besoin chauffage | `besoin_ch_depensier` | 353 | 143 | 1 009,5 % | hors-tol×353 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 337 | 337 | 275,0 % | hors-tol×337 |
| 21 | Besoin chauffage | `besoin_ch` | 334 | 130 | 562,2 % | hors-tol×334 |
| 22 | Génération ECS | `rendement_stockage` | 274 | 120 | 42,8 % | hors-tol×195 manquante×72 suppl×7 |
| 23 | GES | `emission_ges_ecs` | 271 | 134 | 57 267,7 % | hors-tol×260 suppl×4 manquante×7 |
| 24 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 269 | 269 | 346,0 % | hors-tol×269 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 233 | 233 | 268,3 % | hors-tol×233 |
| 26 | Sorties énergie finale | `conso_5_usages_m2` | 225 | 225 | 293,2 % | hors-tol×225 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 225 | 225 | 268,3 % | hors-tol×225 |
| 28 | GES | `emission_ges_ch_depensier` | 216 | 216 | 13 342,0 % | hors-tol×216 |
| 29 | Sorties énergie primaire | `ep_conso_ch_depensier` | 208 | 208 | 98,2 % | hors-tol×208 |
| 30 | Auxiliaires | `cout_total_auxiliaire` | 208 | 208 | 20 278,1 % | hors-tol×208 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 201 | 201 | 92,1 % | hors-tol×201 |
| 32 | Enveloppe | `k` | 195 | 54 | 100,0 % | hors-tol×194 suppl×1 |
| 33 | Enveloppe | `umur0` | 169 | 29 | 140,0 % | hors-tol×169 |
| 34 | Auxiliaires | `cout_auxiliaire_generation_ch` | 165 | 165 | 100,0 % | hors-tol×165 |
| 35 | Auxiliaires | `conso_totale_auxiliaire` | 156 | 156 | 19 463,7 % | hors-tol×156 |
| 36 | Auxiliaires | `ep_conso_totale_auxiliaire` | 156 | 156 | 19 432,2 % | hors-tol×156 |
| 37 | Auxiliaires | `emission_ges_totale_auxiliaire` | 155 | 155 | 19 785,9 % | hors-tol×155 |
| 38 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 149 | 149 | 81,4 % | hors-tol×149 |
| 39 | GES | `emission_ges_ecs_depensier` | 149 | 149 | 42 565,6 % | hors-tol×149 |
| 40 | Coûts | `cout_eclairage` | 147 | 147 | 72,0 % | hors-tol×147 |

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
| `aspect_traversant` | 346 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 346 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 346 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 346 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 346 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 346 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 31 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 16730 publié au lieu de 43884 |
| `apport_interne_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `apport_solaire_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `fraction_apport_gratuit_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `fraction_apport_gratuit_depensier_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `besoin_ecs` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_distribution_ecs_recup` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_distribution_ecs_recup_depensier` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_generateur_ch_recup` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_generateur_ch_recup_depensier` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `qp0` | 54 | la référence sérialise QP0 en kW (1.500) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=125000 W est 1500 W |
| `ep_conso_fr_depensier` | 38 | la référence recopie ep_conso_fr dans ep_conso_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `ep_conso_ecs_depensier` | 98 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 111 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 38 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 101 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 119 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `cout_fr_depensier` | 31 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 438 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 439 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 213 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (3 vs 4 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 240 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (9 vs 20 kWh) |
| `cout_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `emission_ges_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `ep_conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_elec_ac` | 8 | la référence publie une autoconsommation PV nulle malgré Ppv=37727.50 kWh, Sh=2690.00 m² et Cef_5=263910.20 kWh : §16.2 p.104-106 impose aux seuls usages mobiliers une autoconsommation supérieure à 5418.85 kWh (borne conservatrice, sans notre moteur) |
| `taux_autoproduction` | 4 | la référence publie une autoconsommation PV nulle malgré Ppv=37727.50 kWh, Sh=2690.00 m² et Cef_5=263910.20 kWh : §16.2 p.104-106 impose aux seuls usages mobiliers une autoconsommation supérieure à 5418.85 kWh (borne conservatrice, sans notre moteur) |
| `conso_elec_ac_autre_usage` | 4 | la référence publie une autoconsommation PV nulle malgré Ppv=37727.50 kWh, Sh=2690.00 m² et Cef_5=263910.20 kWh : §16.2 p.104-106 impose aux seuls usages mobiliers une autoconsommation supérieure à 5418.85 kWh (borne conservatrice, sans notre moteur) |
| `conso_totale_auxiliaire` | 34 | la référence publie un total auxiliaire de 56.6, incompatible avec la somme de ses postes (272.0) |
| `emission_ges_totale_auxiliaire` | 33 | la référence publie un total auxiliaire de 3.6, incompatible avec la somme de ses postes (17.4) |
| `cout_total_auxiliaire` | 54 | la référence publie un total auxiliaire de 19.7, incompatible avec la somme de ses postes (94.4) |
| `ep_conso_totale_auxiliaire` | 34 | la référence publie un total auxiliaire de 107.5, incompatible avec la somme de ses postes (516.8) |
| `besoin_ch_depensier` | 41 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (13746 vs 98393), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 77 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 51 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `production_ch_solaire` | 9 | la référence publie production_ch_solaire = 745.271 et conso_ch = 2614.984 pour fch = 0.25 : §9.2 p.62 et §9.6 p.65 posent les deux sur le même produit Bch × Σ(part × INT × Ich), d'où Prod / Cch = Fch / (1 − Fch) = 0.3333, quand le fichier publie 0.2850 |
| `k` | 60 | la référence publie k = 0 pour tv_pont_thermique_id = 75, alors qu'elle publie une valeur non nulle pour ce même identifiant ailleurs dans le fichier : un identifiant tabulé ne désigne qu'une valeur |
| `hvent` | 11 | la référence publie hvent = 0 alors que son renouvellement d'air vaut 313.773 pour un hperm de 133.484 : les deux termes ne peuvent pas en sommer un troisième |
| `ep_conso_ecs` | 1 | la référence publie ep_conso_ecs et ep_conso_ecs_depensier permutés : leurs rapports à la consommation valent 1.41000 et 0.70922 en direct, mais 1.00000 de part et d'autre une fois croisés |
| `emission_ges_ecs` | 27 | la référence publie emission_ges_ecs = 972.3 mais sa propre ventilation par énergie en somme 10.3 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `emission_ges_ch` | 33 | la référence publie emission_ges_ch = 5040.5 mais sa propre ventilation par énergie en somme 53.4 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `cout_ch` | 5 | la référence publie cout_ch = 981.0 mais sa propre ventilation par énergie en somme 384.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ch` | 50 | la référence publie conso_ch = 19312.3 mais sa propre ventilation par énergie en somme 19542.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ecs` | 259 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 248 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 200 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `cop` | 4 | la référence déclare une saisie forfaitaire (enum_methode_saisie_carac_sys_id = 1) mais publie cop = 3.07 : §14.2 p.95 ne tabule que 2 ; 2.1 ; 2.2 ; 2.3 ; 2.4 ; 2.5 ; 2.6 ; 2.8 ; 2.9 pour les chauffe-eau thermodynamiques |
| `q4pa_conv` | 2 | la référence déclare une perméabilité mesurée de 1.700 m³/(h.m²) (enum_methode_saisie_q4pa_conv_id = 2) mais publie 1.500, la valeur forfaitaire : §4 p.44 ne l'emploie qu'à défaut de mesure |
| `rendement_generation` | 27 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 5 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `upb` | 7 | la référence déclare un plancher bas isolé (enum_type_isolation_id = 3) mais publie upb = upb0 = 2.000, le coefficient non isolé : §3.2.2 p.20 ne rend upb = upb0 que sur la branche « Isolation ? Non » |
| `umur0` | 121 | la référence déclare un doublage (enum_type_doublage_id = 5, Rdoublage = 0,21 m².K/W) mais publie umur0 = 2.500 : §3.2.1.2 p.16 plafonne alors Umur0 à 1 / (1/3,90 + 0,21) = 2.144, la plus grande valeur tabulée étant 3,90 W/(m².K) |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2612E2658562B.xml` | immeuble_collectif | post_2026 | 700 | 226 | 67,71 % |
| `2580E4039823B.xml` | immeuble_collectif | pre_2026 | 974 | 145 | 85,11 % |
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 140 | 46,56 % |
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 135 | 85,07 % |
| `2501E4041234H.xml` | immeuble_collectif | pre_2026 | 377 | 124 | 67,11 % |
| `2507E4042850R.xml` | immeuble_collectif | pre_2026 | 585 | 124 | 78,80 % |
| `2406E2911962X.xml` | appartement_individuel | pre_2026 | 231 | 119 | 48,48 % |
| `2406E0420767S.xml` | appartement_individuel | pre_2026 | 229 | 118 | 48,47 % |
| `2606E0346427O.xml` | maison_individuelle | post_2026 | 540 | 118 | 78,15 % |
| `2606E1070535W.xml` | immeuble_collectif | post_2026 | 497 | 117 | 76,46 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 116 | 86,29 % |
| `2606E0023430P.xml` | appartement_individuel | post_2026 | 236 | 115 | 51,27 % |
| `2401E2888143P.xml` | maison_individuelle | pre_2026 | 344 | 113 | 67,15 % |
| `2601E0370414Y.xml` | maison_individuelle | post_2026 | 437 | 111 | 74,60 % |
| `2406E0197090T.xml` | appartement_individuel | pre_2026 | 201 | 111 | 44,78 % |
| `2501E0139457Y.xml` | immeuble_collectif | pre_2026 | 421 | 109 | 74,11 % |
| `2601E0089001J.xml` | maison_individuelle | post_2026 | 374 | 108 | 71,12 % |
| `2601E0076852C.xml` | maison_individuelle | post_2026 | 389 | 107 | 72,49 % |
| `2513E4042629K.xml` | immeuble_collectif | pre_2026 | 423 | 107 | 74,70 % |
| `2401E0473146C.xml` | maison_individuelle | pre_2026 | 368 | 105 | 71,47 % |
| `2606E0835323G.xml` | appartement_individuel | post_2026 | 257 | 105 | 59,14 % |
| `2501E0122493M.xml` | immeuble_collectif | pre_2026 | 276 | 105 | 61,96 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 104 | 66,12 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 103 | 66,45 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 102 | 85,91 % |

## Cas totalement conformes

- `2659E2613722G.xml` (appartement_individuel, post_2026) — 218 valeurs
