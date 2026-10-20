# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-20T12:39:08+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `ae4a475`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 646
Exécutés                 : 646
Crash                    : 0
Totalement conformes     : 1
Partiellement conformes  : 645

Valeurs comparées        : 198 192
Exactes                  : 149 350
Dans tolérance           : 28 518
Hors tolérance           : 17 422
Balises manquantes       : 626
Balises supplémentaires  : 2 230
Écarts non numériques    : 46

Conformité               : 89,75 %
Avancement               : 92,10 %
```

**Avancement : 92,10 %.** C'est le chiffre de suivi : la part des valeurs **atteignables** qui sont conformes. Il vaut 100 % quand tout ce qui dépend du moteur est juste.

Il se distingue de la conformité brute (89,75 %) par un point : 5 067 écarts sont imputables à la **référence** et non au moteur — le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels, et les reproduire nous éloignerait de la méthode. Ces écarts sortent du dénominateur de l'avancement, jamais du décompte brut : la mesure ne se maquille pas. Détail des imputations plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 76037 | 73131 | 1920 | 835 | 51 | 100 | 98,70 % |
| Ventilation | 4429 | 3004 | 1324 | 95 | 5 | 1 | 97,72 % |
| Apports | 19607 | 16886 | 1891 | 486 | 0 | 344 | 95,77 % |
| Besoin chauffage | 3118 | 28 | 2498 | 592 | 0 | 0 | 81,01 % |
| Besoin ECS | 5558 | 5088 | 400 | 70 | 0 | 0 | 98,74 % |
| Génération chauffage | 12731 | 5464 | 4833 | 2411 | 10 | 13 | 80,88 % |
| Génération ECS | 11416 | 7468 | 2083 | 1755 | 79 | 31 | 83,66 % |
| Auxiliaires | 21964 | 14400 | 4070 | 3493 | 0 | 1 | 84,09 % |
| Froid | 8428 | 7196 | 365 | 436 | 424 | 7 | 89,71 % |
| PV | 4603 | 4451 | 21 | 113 | 15 | 3 | 97,15 % |
| Sorties énergie finale | 4280 | 2224 | 1203 | 837 | 10 | 6 | 80,07 % |
| Sorties énergie primaire | 5168 | 2368 | 1650 | 1121 | 0 | 1 | 77,75 % |
| GES | 8681 | 3813 | 2976 | 1850 | 15 | 9 | 78,21 % |
| Coûts | 7389 | 1504 | 2992 | 2869 | 15 | 9 | 60,85 % |
| Confort d'été | 3551 | 1579 | 0 | 270 | 0 | 1702 | 44,47 % |
| Autre | 1232 | 746 | 292 | 189 | 2 | 3 | 84,25 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 249 | 83986 | 65551 | 12922 | 3965 | 41 | 93,16 % |
| maison_individuelle | 169 | 50971 | 38579 | 5907 | 6117 | 294 | 86,99 % |
| appartement_issu_immeuble | 97 | 32826 | 23631 | 5163 | 3353 | 102 | 87,46 % |
| appartement_individuel | 131 | 30409 | 21589 | 4526 | 3987 | 189 | 85,51 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 442 | 134492 | 102822 | 19929 | 9601 | 356 | 90,97 % |
| pre_2026 | 204 | 63700 | 46528 | 8589 | 7821 | 270 | 86,25 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 382 | 117461 | 91863 | 17795 | 6183 | 4 | 93,05 % |
| 3cl_tribu_1.4.25.1 | 150 | 47401 | 34919 | 6567 | 5471 | 163 | 87,25 % |
| inconnu | 39 | 11416 | 7567 | 1173 | 2314 | 261 | 76,30 % |
| 3cl_tribu_2024.6.1.0 | 24 | 8088 | 6236 | 1156 | 606 | 1 | 91,12 % |
| 3cl_bbs_V2025.11.1.0 | 18 | 4825 | 2824 | 798 | 1016 | 95 | 74,79 % |
| 3cl-2024.6.1.0 | 12 | 3243 | 2241 | 354 | 576 | 61 | 79,72 % |
| 3cl_bbs_V2024.6.1.0 | 4 | 1310 | 774 | 140 | 350 | 18 | 69,56 % |
| BBS_Slama_2024.6.1.0 | 4 | 1281 | 972 | 189 | 106 | 0 | 90,35 % |
| 3cl_BBS_2025.11.1.0 | 3 | 810 | 531 | 161 | 103 | 0 | 85,12 % |
| V 1.4.25.1 | 2 | 432 | 196 | 6 | 215 | 10 | 46,54 % |
| V 2024.6.1.0 | 1 | 294 | 196 | 7 | 85 | 5 | 68,81 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 187 | 33 | 48 | 4 | 78,57 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 173 | 2 | 84 | 0 | 67,05 % |
| V 2025.11.1.0 | 1 | 242 | 158 | 3 | 77 | 4 | 66,26 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 1078 | 210 | 6 900,0 % | hors-tol×1070 suppl×3 manquante×5 |
| 2 | Génération chauffage | `conso_ch_depensier` | 854 | 219 | 6 900,0 % | hors-tol×854 |
| 3 | Génération ECS | `conso_ecs` | 805 | 147 | 817,5 % | hors-tol×797 suppl×3 manquante×5 |
| 4 | Génération ECS | `conso_ecs_depensier` | 673 | 155 | 549,6 % | hors-tol×673 |
| 5 | Coûts | `cout_5_usages` | 655 | 265 | 824,9 % | hors-tol×647 suppl×3 manquante×5 |
| 6 | GES | `emission_ges_5_usages` | 626 | 263 | 25 908,1 % | hors-tol×618 suppl×3 manquante×5 |
| 7 | Sorties énergie finale | `conso_5_usages` | 621 | 256 | 1 016 120,1 % | hors-tol×613 suppl×3 manquante×5 |
| 8 | Coûts | `cout_ch_depensier` | 597 | 597 | 116,2 % | hors-tol×597 |
| 9 | Coûts | `cout_ecs_depensier` | 590 | 590 | 540,5 % | hors-tol×590 |
| 10 | Coûts | `cout_ch` | 496 | 212 | 138,2 % | hors-tol×488 suppl×3 manquante×5 |
| 11 | GES | `emission_ges_ch` | 483 | 206 | 15 993,3 % | hors-tol×475 suppl×3 manquante×5 |
| 12 | Confort d'été | `enum_indicateur_confort_ete_id` | 467 | 467 | 200,0 % | hors-tol×127 suppl×340 |
| 13 | Coûts | `cout_ecs` | 411 | 201 | 799,1 % | hors-tol×403 suppl×3 manquante×5 |
| 14 | Confort d'été | `protection_solaire_exterieure` | 394 | 394 | 100,0 % | hors-tol×54 suppl×340 |
| 15 | Confort d'été | `aspect_traversant` | 387 | 387 | 100,0 % | hors-tol×47 suppl×340 |
| 16 | Confort d'été | `isolation_toiture` | 376 | 376 | 100,0 % | hors-tol×34 suppl×342 |
| 17 | Confort d'été | `brasseur_air` | 348 | 348 | 100,0 % | suppl×340 hors-tol×8 |
| 18 | Apports | `inertie_lourde` | 348 | 348 | — | suppl×341 hors-tol×7 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 313 | 313 | 242,1 % | hors-tol×313 |
| 20 | Besoin chauffage | `besoin_ch_depensier` | 302 | 132 | 1 009,5 % | hors-tol×302 |
| 21 | Besoin chauffage | `besoin_ch` | 290 | 118 | 562,2 % | hors-tol×290 |
| 22 | GES | `emission_ges_ecs` | 264 | 131 | 57 267,7 % | hors-tol×256 suppl×3 manquante×5 |
| 23 | Génération ECS | `rendement_stockage` | 243 | 106 | 42,8 % | hors-tol×191 manquante×51 suppl×1 |
| 24 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 239 | 239 | 141,7 % | hors-tol×239 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 231 | 231 | 267,7 % | hors-tol×231 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 223 | 223 | 267,3 % | hors-tol×223 |
| 27 | Sorties énergie finale | `conso_5_usages_m2` | 219 | 219 | 292,1 % | hors-tol×219 |
| 28 | GES | `emission_ges_ch_depensier` | 211 | 211 | 12 237,9 % | hors-tol×211 |
| 29 | Sorties énergie primaire | `ep_conso_ch_depensier` | 203 | 203 | 98,2 % | hors-tol×203 |
| 30 | Sorties énergie primaire | `ep_conso_ch` | 197 | 197 | 93,5 % | hors-tol×197 |
| 31 | Auxiliaires | `cout_total_auxiliaire` | 196 | 196 | 20 278,1 % | hors-tol×196 |
| 32 | Enveloppe | `k` | 160 | 44 | 100,0 % | hors-tol×159 suppl×1 |
| 33 | Auxiliaires | `cout_auxiliaire_generation_ch` | 151 | 151 | 100,0 % | hors-tol×151 |
| 34 | Auxiliaires | `conso_totale_auxiliaire` | 149 | 149 | 19 463,7 % | hors-tol×149 |
| 35 | Auxiliaires | `emission_ges_totale_auxiliaire` | 149 | 149 | 19 785,9 % | hors-tol×149 |
| 36 | Auxiliaires | `ep_conso_totale_auxiliaire` | 149 | 149 | 19 432,2 % | hors-tol×149 |
| 37 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 148 | 148 | 77,5 % | hors-tol×148 |
| 38 | Enveloppe | `umur0` | 147 | 28 | 140,0 % | hors-tol×147 |
| 39 | Coûts | `cout_eclairage` | 144 | 144 | 72,0 % | hors-tol×144 |
| 40 | GES | `emission_ges_ecs_depensier` | 143 | 143 | 42 565,6 % | hors-tol×143 |

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
| `aspect_traversant` | 317 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 317 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 317 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 317 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 317 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 317 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 27 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 16730 publié au lieu de 43884 |
| `apport_interne_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `apport_solaire_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `fraction_apport_gratuit_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `fraction_apport_gratuit_depensier_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `besoin_ecs` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_distribution_ecs_recup` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_distribution_ecs_recup_depensier` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_generateur_ch_recup` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_generateur_ch_recup_depensier` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `qp0` | 27 | la référence sérialise QP0 en kW (1.500) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=125000 W est 1500 W |
| `ep_conso_fr_depensier` | 34 | la référence recopie ep_conso_fr dans ep_conso_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `ep_conso_ecs_depensier` | 92 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 112 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 34 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 95 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 120 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `cout_fr_depensier` | 30 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 421 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 422 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 200 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (3 vs 4 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 233 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (9 vs 20 kWh) |
| `cout_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `emission_ges_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `ep_conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_totale_auxiliaire` | 25 | la référence publie un total auxiliaire de 56.6, incompatible avec la somme de ses postes (272.0) |
| `emission_ges_totale_auxiliaire` | 25 | la référence publie un total auxiliaire de 3.6, incompatible avec la somme de ses postes (17.4) |
| `cout_total_auxiliaire` | 34 | la référence publie un total auxiliaire de 19.7, incompatible avec la somme de ses postes (94.4) |
| `ep_conso_totale_auxiliaire` | 25 | la référence publie un total auxiliaire de 107.5, incompatible avec la somme de ses postes (516.8) |
| `besoin_ch_depensier` | 31 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (13746 vs 98393), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 73 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 50 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `production_ch_solaire` | 9 | la référence publie production_ch_solaire = 745.271 et conso_ch = 2614.984 pour fch = 0.25 : §9.2 p.62 et §9.6 p.65 posent les deux sur le même produit Bch × Σ(part × INT × Ich), d'où Prod / Cch = Fch / (1 − Fch) = 0.3333, quand le fichier publie 0.2850 |
| `k` | 54 | la référence publie k = 0 pour tv_pont_thermique_id = 75, alors qu'elle publie une valeur non nulle pour ce même identifiant ailleurs dans le fichier : un identifiant tabulé ne désigne qu'une valeur |
| `hvent` | 11 | la référence publie hvent = 0 alors que son renouvellement d'air vaut 313.773 pour un hperm de 133.484 : les deux termes ne peuvent pas en sommer un troisième |
| `ep_conso_ecs` | 1 | la référence publie ep_conso_ecs et ep_conso_ecs_depensier permutés : leurs rapports à la consommation valent 1.41000 et 0.70922 en direct, mais 1.00000 de part et d'autre une fois croisés |
| `emission_ges_ecs` | 27 | la référence publie emission_ges_ecs = 972.3 mais sa propre ventilation par énergie en somme 10.3 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `emission_ges_ch` | 33 | la référence publie emission_ges_ch = 5040.5 mais sa propre ventilation par énergie en somme 53.4 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `cout_ch` | 5 | la référence publie cout_ch = 981.0 mais sa propre ventilation par énergie en somme 384.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ch` | 32 | la référence publie conso_ch = 19312.3 mais sa propre ventilation par énergie en somme 19542.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ecs` | 256 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 248 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 195 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `cop` | 4 | la référence déclare une saisie forfaitaire (enum_methode_saisie_carac_sys_id = 1) mais publie cop = 3.07 : §14.2 p.95 ne tabule que 2 ; 2.1 ; 2.2 ; 2.3 ; 2.4 ; 2.5 ; 2.6 ; 2.8 ; 2.9 pour les chauffe-eau thermodynamiques |
| `q4pa_conv` | 2 | la référence déclare une perméabilité mesurée de 1.700 m³/(h.m²) (enum_methode_saisie_q4pa_conv_id = 2) mais publie 1.500, la valeur forfaitaire : §4 p.44 ne l'emploie qu'à défaut de mesure |
| `rendement_generation` | 29 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 4 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `upb` | 7 | la référence déclare un plancher bas isolé (enum_type_isolation_id = 3) mais publie upb = upb0 = 2.000, le coefficient non isolé : §3.2.2 p.20 ne rend upb = upb0 que sur la branche « Isolation ? Non » |
| `umur0` | 99 | la référence déclare un doublage (enum_type_doublage_id = 5, Rdoublage = 0,21 m².K/W) mais publie umur0 = 2.500 : §3.2.1.2 p.16 plafonne alors Umur0 à 1 / (1/3,90 + 0,21) = 2.144, la plus grande valeur tabulée étant 3,90 W/(m².K) |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 140 | 46,56 % |
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 904 | 139 | 84,62 % |
| `2606E0835323G.xml` | appartement_individuel | post_2026 | 257 | 123 | 52,14 % |
| `2606E0021556N.xml` | appartement_individuel | post_2026 | 255 | 120 | 52,94 % |
| `2606E0346427O.xml` | maison_individuelle | post_2026 | 540 | 120 | 77,78 % |
| `2406E2911962X.xml` | appartement_individuel | pre_2026 | 231 | 119 | 48,48 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 118 | 86,05 % |
| `2406E0420767S.xml` | appartement_individuel | pre_2026 | 229 | 118 | 48,47 % |
| `2606E1070535W.xml` | immeuble_collectif | post_2026 | 497 | 117 | 76,46 % |
| `2606E0023430P.xml` | appartement_individuel | post_2026 | 236 | 115 | 51,27 % |
| `2401E2888143P.xml` | maison_individuelle | pre_2026 | 344 | 113 | 67,15 % |
| `2601E0370414Y.xml` | maison_individuelle | post_2026 | 437 | 111 | 74,60 % |
| `2406E0197090T.xml` | appartement_individuel | pre_2026 | 201 | 111 | 44,78 % |
| `2501E0139457Y.xml` | immeuble_collectif | pre_2026 | 421 | 111 | 73,63 % |
| `2601E0089001J.xml` | maison_individuelle | post_2026 | 374 | 108 | 71,12 % |
| `2601E0076852C.xml` | maison_individuelle | post_2026 | 389 | 107 | 72,49 % |
| `2406E0400266F.xml` | appartement_individuel | pre_2026 | 239 | 107 | 55,23 % |
| `2401E0473146C.xml` | maison_individuelle | pre_2026 | 368 | 105 | 71,47 % |
| `2501E0122493M.xml` | immeuble_collectif | pre_2026 | 276 | 105 | 61,96 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 104 | 66,12 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 103 | 66,45 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 102 | 85,91 % |
| `2406E0661311K.xml` | appartement_individuel | pre_2026 | 271 | 102 | 62,36 % |
| `2601E0869643B.xml` | maison_individuelle | post_2026 | 260 | 101 | 61,15 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |

## Cas totalement conformes

- `2659E2613722G.xml` (appartement_individuel, post_2026) — 218 valeurs
