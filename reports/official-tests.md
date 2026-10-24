# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-24T22:56:45+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `63c806e`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 750
Exécutés                 : 750
Crash                    : 0
Totalement conformes     : 1
Partiellement conformes  : 749

Valeurs comparées        : 234 575
Exactes                  : 176 677
Dans tolérance           : 34 355
Hors tolérance           : 20 297
Balises manquantes       : 715
Balises supplémentaires  : 2 476
Écarts non numériques    : 55

Conformité               : 89,96 %
Avancement               : 92,18 %
```

**Avancement : 92,18 %.** C'est le chiffre de suivi : la part des valeurs **atteignables** qui sont conformes. Il vaut 100 % quand tout ce qui dépend du moteur est juste.

Il se distingue de la conformité brute (89,96 %) par un point : 5 632 écarts sont imputables à la **référence** et non au moteur — le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels, et les reproduire nous éloignerait de la méthode. Ces écarts sortent du dénominateur de l'avancement, jamais du décompte brut : la mesure ne se maquille pas. Détail des imputations plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 91154 | 87712 | 2298 | 993 | 44 | 107 | 98,74 % |
| Ventilation | 5117 | 3465 | 1541 | 98 | 11 | 2 | 97,83 % |
| Apports | 23176 | 19970 | 2242 | 577 | 0 | 387 | 95,84 % |
| Besoin chauffage | 3736 | 32 | 2951 | 753 | 0 | 0 | 79,84 % |
| Besoin ECS | 6456 | 5849 | 527 | 80 | 0 | 0 | 98,76 % |
| Génération chauffage | 15513 | 6710 | 5945 | 2840 | 11 | 7 | 81,58 % |
| Génération ECS | 13249 | 8695 | 2447 | 1971 | 103 | 33 | 84,10 % |
| Auxiliaires | 25500 | 16533 | 4909 | 4057 | 0 | 1 | 84,09 % |
| Froid | 9824 | 8341 | 463 | 517 | 496 | 7 | 89,62 % |
| PV | 5379 | 5115 | 112 | 134 | 15 | 3 | 97,17 % |
| Sorties énergie finale | 5008 | 2586 | 1444 | 968 | 8 | 2 | 80,47 % |
| Sorties énergie primaire | 6000 | 2718 | 1969 | 1278 | 0 | 1 | 78,12 % |
| GES | 10137 | 4403 | 3563 | 2135 | 12 | 3 | 78,58 % |
| Coûts | 8637 | 1739 | 3553 | 3330 | 12 | 3 | 61,27 % |
| Confort d'été | 4107 | 1868 | 0 | 322 | 0 | 1917 | 45,48 % |
| Autre | 1582 | 941 | 391 | 244 | 3 | 3 | 84,20 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 272 | 95541 | 73898 | 14439 | 5444 | 131 | 92,20 % |
| maison_individuelle | 204 | 61510 | 46706 | 7393 | 7058 | 292 | 87,66 % |
| appartement_issu_immeuble | 121 | 41674 | 30371 | 6741 | 3739 | 102 | 88,80 % |
| appartement_individuel | 153 | 35850 | 25702 | 5782 | 4056 | 190 | 87,45 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 516 | 160219 | 122562 | 23699 | 11660 | 371 | 91,00 % |
| pre_2026 | 234 | 74356 | 54115 | 10656 | 8637 | 344 | 86,84 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 447 | 139991 | 109368 | 21028 | 7821 | 5 | 92,85 % |
| 3cl_tribu_1.4.25.1 | 153 | 48081 | 35431 | 7100 | 5108 | 163 | 88,18 % |
| inconnu | 45 | 13711 | 9188 | 1583 | 2593 | 257 | 78,30 % |
| 3cl_tribu_2024.6.1.0 | 29 | 9438 | 7274 | 1318 | 754 | 2 | 90,76 % |
| 3cl_bbs_V2025.11.1.0 | 26 | 8085 | 4825 | 1307 | 1679 | 151 | 75,60 % |
| BBS_Slama_2024.6.1.0 | 17 | 5674 | 4222 | 1039 | 333 | 0 | 92,44 % |
| 3cl-2024.6.1.0 | 14 | 4584 | 3266 | 415 | 792 | 85 | 80,06 % |
| 3cl_bbs_V2024.6.1.0 | 4 | 1310 | 774 | 142 | 348 | 18 | 69,71 % |
| 3cl_BBS_2025.11.1.0 | 3 | 810 | 533 | 184 | 78 | 0 | 88,19 % |
| V 2025.11.1.0 | 3 | 776 | 531 | 57 | 171 | 15 | 75,48 % |
| V 1.4.25.1 | 2 | 432 | 196 | 6 | 215 | 10 | 46,54 % |
| V 2024.6.1.0 | 1 | 294 | 196 | 7 | 85 | 5 | 68,81 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 187 | 33 | 48 | 4 | 78,57 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 173 | 2 | 84 | 0 | 67,05 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 1246 | 240 | 6 900,0 % | hors-tol×1241 manquante×4 suppl×1 |
| 2 | Génération chauffage | `conso_ch_depensier` | 1026 | 256 | 6 900,0 % | hors-tol×1026 |
| 3 | Génération ECS | `conso_ecs` | 895 | 165 | 817,5 % | hors-tol×890 manquante×4 suppl×1 |
| 4 | Coûts | `cout_5_usages` | 774 | 317 | 824,9 % | hors-tol×769 manquante×4 suppl×1 |
| 5 | Génération ECS | `conso_ecs_depensier` | 752 | 176 | 549,6 % | hors-tol×752 |
| 6 | GES | `emission_ges_5_usages` | 722 | 306 | 42 920,8 % | hors-tol×717 manquante×4 suppl×1 |
| 7 | Sorties énergie finale | `conso_5_usages` | 714 | 296 | 1 051 647,2 % | hors-tol×709 manquante×4 suppl×1 |
| 8 | Coûts | `cout_ch_depensier` | 688 | 688 | 116,2 % | hors-tol×688 |
| 9 | Coûts | `cout_ecs_depensier` | 673 | 673 | 540,5 % | hors-tol×673 |
| 10 | Coûts | `cout_ch` | 572 | 248 | 150,0 % | hors-tol×567 manquante×4 suppl×1 |
| 11 | GES | `emission_ges_ch` | 550 | 236 | 42 920,8 % | hors-tol×545 manquante×4 suppl×1 |
| 12 | Confort d'été | `enum_indicateur_confort_ete_id` | 531 | 531 | 200,0 % | hors-tol×148 suppl×383 |
| 13 | Coûts | `cout_ecs` | 472 | 230 | 799,1 % | hors-tol×467 manquante×4 suppl×1 |
| 14 | Confort d'été | `protection_solaire_exterieure` | 451 | 451 | 100,0 % | hors-tol×68 suppl×383 |
| 15 | Confort d'été | `aspect_traversant` | 438 | 438 | 100,0 % | hors-tol×55 suppl×383 |
| 16 | Confort d'été | `isolation_toiture` | 427 | 427 | 100,0 % | hors-tol×42 suppl×385 |
| 17 | Apports | `inertie_lourde` | 395 | 395 | — | suppl×384 hors-tol×11 |
| 18 | Confort d'été | `brasseur_air` | 392 | 392 | 100,0 % | suppl×383 hors-tol×9 |
| 19 | Besoin chauffage | `besoin_ch_depensier` | 386 | 156 | 1 009,5 % | hors-tol×386 |
| 20 | Besoin chauffage | `besoin_ch` | 367 | 143 | 562,2 % | hors-tol×367 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 364 | 364 | 275,0 % | hors-tol×364 |
| 22 | GES | `emission_ges_ecs` | 299 | 151 | 57 267,7 % | hors-tol×294 manquante×4 suppl×1 |
| 23 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 273 | 273 | 346,0 % | hors-tol×273 |
| 24 | Génération ECS | `rendement_stockage` | 270 | 116 | 42,8 % | hors-tol×196 manquante×73 suppl×1 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 263 | 263 | 268,3 % | hors-tol×263 |
| 26 | Sorties énergie finale | `conso_5_usages_m2` | 254 | 254 | 293,2 % | hors-tol×254 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 254 | 254 | 268,3 % | hors-tol×254 |
| 28 | GES | `emission_ges_ch_depensier` | 242 | 242 | 13 342,0 % | hors-tol×242 |
| 29 | Auxiliaires | `cout_total_auxiliaire` | 237 | 237 | 20 278,1 % | hors-tol×237 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 234 | 234 | 98,2 % | hors-tol×234 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 225 | 225 | 92,1 % | hors-tol×225 |
| 32 | Enveloppe | `k` | 200 | 56 | 100,0 % | hors-tol×199 suppl×1 |
| 33 | Auxiliaires | `cout_auxiliaire_generation_ch` | 184 | 184 | 97,6 % | hors-tol×184 |
| 34 | Enveloppe | `umur0` | 177 | 32 | 140,0 % | hors-tol×177 |
| 35 | Auxiliaires | `conso_totale_auxiliaire` | 170 | 170 | 19 463,7 % | hors-tol×170 |
| 36 | Auxiliaires | `ep_conso_totale_auxiliaire` | 170 | 170 | 19 432,2 % | hors-tol×170 |
| 37 | Auxiliaires | `emission_ges_totale_auxiliaire` | 169 | 169 | 19 785,9 % | hors-tol×169 |
| 38 | Coûts | `cout_eclairage` | 166 | 166 | 72,0 % | hors-tol×166 |
| 39 | GES | `emission_ges_ecs_depensier` | 165 | 165 | 42 565,6 % | hors-tol×165 |
| 40 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 158 | 158 | 100,0 % | hors-tol×158 |

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
| `aspect_traversant` | 353 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 353 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 353 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 353 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 353 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 353 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
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
| `ep_conso_fr_depensier` | 40 | la référence recopie ep_conso_fr dans ep_conso_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `ep_conso_ecs_depensier` | 112 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 136 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 40 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 115 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 144 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `cout_fr_depensier` | 33 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 444 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 445 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
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
| `conso_auxiliaire_ventilation` | 82 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 53 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `production_ch_solaire` | 9 | la référence publie production_ch_solaire = 745.271 et conso_ch = 2614.984 pour fch = 0.25 : §9.2 p.62 et §9.6 p.65 posent les deux sur le même produit Bch × Σ(part × INT × Ich), d'où Prod / Cch = Fch / (1 − Fch) = 0.3333, quand le fichier publie 0.2850 |
| `k` | 62 | la référence publie k = 0 pour tv_pont_thermique_id = 75, alors qu'elle publie une valeur non nulle pour ce même identifiant ailleurs dans le fichier : un identifiant tabulé ne désigne qu'une valeur |
| `hvent` | 11 | la référence publie hvent = 0 alors que son renouvellement d'air vaut 313.773 pour un hperm de 133.484 : les deux termes ne peuvent pas en sommer un troisième |
| `ep_conso_ecs` | 1 | la référence publie ep_conso_ecs et ep_conso_ecs_depensier permutés : leurs rapports à la consommation valent 1.41000 et 0.70922 en direct, mais 1.00000 de part et d'autre une fois croisés |
| `emission_ges_ecs` | 30 | la référence publie emission_ges_ecs = 972.3 mais sa propre ventilation par énergie en somme 10.3 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `emission_ges_ch` | 37 | la référence publie emission_ges_ch = 5040.5 mais sa propre ventilation par énergie en somme 53.4 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `cout_ch` | 5 | la référence publie cout_ch = 981.0 mais sa propre ventilation par énergie en somme 384.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ch` | 47 | la référence publie conso_ch = 19312.3 mais sa propre ventilation par énergie en somme 19542.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ecs` | 254 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 248 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 200 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `cop` | 4 | la référence déclare une saisie forfaitaire (enum_methode_saisie_carac_sys_id = 1) mais publie cop = 3.07 : §14.2 p.95 ne tabule que 2 ; 2.1 ; 2.2 ; 2.3 ; 2.4 ; 2.5 ; 2.6 ; 2.8 ; 2.9 pour les chauffe-eau thermodynamiques |
| `q4pa_conv` | 2 | la référence déclare une perméabilité mesurée de 1.700 m³/(h.m²) (enum_methode_saisie_q4pa_conv_id = 2) mais publie 1.500, la valeur forfaitaire : §4 p.44 ne l'emploie qu'à défaut de mesure |
| `rendement_generation` | 34 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 5 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `upb` | 7 | la référence déclare un plancher bas isolé (enum_type_isolation_id = 3) mais publie upb = upb0 = 2.000, le coefficient non isolé : §3.2.2 p.20 ne rend upb = upb0 que sur la branche « Isolation ? Non » |
| `umur0` | 125 | la référence déclare un doublage (enum_type_doublage_id = 5, Rdoublage = 0,21 m².K/W) mais publie umur0 = 2.500 : §3.2.1.2 p.16 plafonne alors Umur0 à 1 / (1/3,90 + 0,21) = 2.144, la plus grande valeur tabulée étant 3,90 W/(m².K) |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2612E2658562B.xml` | immeuble_collectif | post_2026 | 700 | 226 | 67,71 % |
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 263 | 137 | 47,91 % |
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 902 | 133 | 85,25 % |
| `2580E4039823B.xml` | immeuble_collectif | pre_2026 | 964 | 132 | 86,31 % |
| `2501E4041234H.xml` | immeuble_collectif | pre_2026 | 377 | 124 | 67,11 % |
| `2507E4042850R.xml` | immeuble_collectif | pre_2026 | 585 | 122 | 79,15 % |
| `2406E2911962X.xml` | appartement_individuel | pre_2026 | 231 | 119 | 48,48 % |
| `2406E0420767S.xml` | appartement_individuel | pre_2026 | 229 | 118 | 48,47 % |
| `2606E0346427O.xml` | maison_individuelle | post_2026 | 540 | 118 | 78,15 % |
| `2606E1070535W.xml` | immeuble_collectif | post_2026 | 497 | 117 | 76,46 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 116 | 86,29 % |
| `2401E2888143P.xml` | maison_individuelle | pre_2026 | 344 | 113 | 67,15 % |
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
| `2406E0661311K.xml` | appartement_individuel | pre_2026 | 271 | 102 | 62,36 % |
| `2513E4042633O.xml` | immeuble_collectif | pre_2026 | 405 | 102 | 74,81 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |

## Cas totalement conformes

- `2659E2613722G.xml` (appartement_individuel, post_2026) — 218 valeurs
