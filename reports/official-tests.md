# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-17T09:41:44+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `c4b3289`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 505
Exécutés                 : 505
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 505

Valeurs comparées        : 158 042
Exactes                  : 120 018
Dans tolérance           : 22 611
Hors tolérance           : 13 027
Balises manquantes       : 322
Balises supplémentaires  : 2 018
Écarts non numériques    : 46

Conformité               : 90,25 %
```

Sur ces écarts, **4 244 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,93 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 61195 | 59264 | 1428 | 430 | 19 | 54 | 99,18 % |
| Ventilation | 3457 | 2292 | 1111 | 50 | 3 | 1 | 98,44 % |
| Apports | 15708 | 13635 | 1438 | 314 | 0 | 321 | 95,96 % |
| Besoin chauffage | 2452 | 16 | 2135 | 297 | 4 | 0 | 87,72 % |
| Besoin ECS | 4525 | 4210 | 283 | 32 | 0 | 0 | 99,29 % |
| Génération chauffage | 10132 | 4324 | 3939 | 1837 | 24 | 8 | 81,55 % |
| Génération ECS | 9599 | 6279 | 1887 | 1377 | 40 | 16 | 85,07 % |
| Auxiliaires | 17170 | 11087 | 3245 | 2837 | 0 | 1 | 83,47 % |
| Froid | 6408 | 5729 | 55 | 409 | 208 | 7 | 90,26 % |
| PV | 3586 | 3500 | 10 | 67 | 6 | 3 | 97,88 % |
| Sorties énergie finale | 3343 | 1771 | 1002 | 562 | 4 | 4 | 82,95 % |
| Sorties énergie primaire | 4040 | 1905 | 1336 | 770 | 0 | 1 | 80,22 % |
| GES | 6782 | 3114 | 2320 | 1318 | 6 | 6 | 80,12 % |
| Coûts | 5772 | 1162 | 2167 | 2431 | 6 | 6 | 57,67 % |
| Confort d'été | 2825 | 1091 | 0 | 147 | 0 | 1587 | 38,62 % |
| Autre | 1048 | 639 | 255 | 149 | 2 | 3 | 85,31 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 244 | 82117 | 64115 | 12197 | 4286 | 40 | 92,66 % |
| maison_individuelle | 113 | 33571 | 25481 | 4016 | 3876 | 152 | 87,57 % |
| appartement_issu_immeuble | 72 | 24881 | 17529 | 3789 | 3032 | 71 | 85,43 % |
| appartement_individuel | 76 | 17473 | 12893 | 2609 | 1833 | 59 | 88,34 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 354 | 109004 | 83868 | 16255 | 7099 | 187 | 91,56 % |
| pre_2026 | 151 | 49038 | 36150 | 6356 | 5928 | 135 | 86,41 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 324 | 100742 | 78617 | 14990 | 5633 | 12 | 92,62 % |
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
| 1 | Génération chauffage | `conso_ch` | 783 | 149 | 6 900,0 % | hors-tol×773 manquante×8 suppl×2 |
| 2 | Génération chauffage | `conso_ch_depensier` | 635 | 152 | 6 900,0 % | hors-tol×629 manquante×6 |
| 3 | Génération ECS | `conso_ecs` | 626 | 109 | 104,8 % | hors-tol×622 suppl×2 manquante×2 |
| 4 | Coûts | `cout_5_usages` | 599 | 289 | 936,3 % | hors-tol×595 suppl×2 manquante×2 |
| 5 | Génération ECS | `conso_ecs_depensier` | 523 | 110 | 103,4 % | hors-tol×523 |
| 6 | Coûts | `cout_ecs_depensier` | 480 | 480 | 104,4 % | hors-tol×480 |
| 7 | Coûts | `cout_ch_depensier` | 476 | 476 | 128,8 % | hors-tol×476 |
| 8 | GES | `emission_ges_5_usages` | 438 | 191 | 21 175,5 % | hors-tol×434 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 420 | 178 | 1 016 120,1 % | hors-tol×416 suppl×2 manquante×2 |
| 10 | Coûts | `cout_ch` | 393 | 174 | 741,6 % | hors-tol×389 suppl×2 manquante×2 |
| 11 | Confort d'été | `enum_indicateur_confort_ete_id` | 384 | 384 | 100,0 % | hors-tol×67 suppl×317 |
| 12 | Coûts | `cout_ecs` | 376 | 200 | 65,9 % | hors-tol×372 suppl×2 manquante×2 |
| 13 | Confort d'été | `protection_solaire_exterieure` | 349 | 349 | 100,0 % | hors-tol×32 suppl×317 |
| 14 | Confort d'été | `aspect_traversant` | 346 | 346 | — | hors-tol×29 suppl×317 |
| 15 | Confort d'été | `isolation_toiture` | 335 | 335 | 100,0 % | hors-tol×16 suppl×319 |
| 16 | GES | `emission_ges_ch` | 335 | 145 | 15 993,3 % | hors-tol×331 suppl×2 manquante×2 |
| 17 | Confort d'été | `brasseur_air` | 320 | 320 | 100,0 % | suppl×317 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 320 | 320 | — | suppl×318 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 260 | 260 | 359,4 % | hors-tol×260 |
| 20 | GES | `emission_ges_ecs` | 200 | 99 | 20 212,9 % | hors-tol×196 suppl×2 manquante×2 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 197 | 197 | 141,7 % | hors-tol×197 |
| 22 | Génération ECS | `rendement_stockage` | 191 | 71 | 40,0 % | hors-tol×163 manquante×28 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 177 | 177 | 20 278,1 % | hors-tol×177 |
| 24 | Besoin chauffage | `besoin_ch_depensier` | 154 | 72 | 1 009,5 % | hors-tol×152 manquante×2 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 152 | 152 | 551,8 % | hors-tol×152 |
| 26 | Besoin chauffage | `besoin_ch` | 147 | 62 | 741,6 % | hors-tol×145 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 145 | 145 | 551,2 % | hors-tol×145 |
| 28 | GES | `emission_ges_ch_depensier` | 144 | 144 | 12 237,9 % | hors-tol×144 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 140 | 140 | 257,3 % | hors-tol×140 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 139 | 139 | 149,9 % | hors-tol×139 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 137 | 137 | 101,9 % | hors-tol×137 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 133 | 133 | 100,0 % | hors-tol×133 |
| 33 | Génération chauffage | `rendement_generation` | 129 | 84 | 29,0 % | hors-tol×127 manquante×2 |
| 34 | Coûts | `cout_eclairage` | 119 | 119 | 83,7 % | hors-tol×119 |
| 35 | Auxiliaires | `emission_ges_totale_auxiliaire` | 115 | 115 | 41 008,1 % | hors-tol×115 |
| 36 | Auxiliaires | `conso_totale_auxiliaire` | 112 | 112 | 42 072,2 % | hors-tol×112 |
| 37 | Auxiliaires | `ep_conso_totale_auxiliaire` | 112 | 112 | 42 109,2 % | hors-tol×112 |
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
| `aspect_traversant` | 296 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 296 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 296 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 296 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 296 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 296 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
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
| `ep_conso_ecs_depensier` | 72 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 98 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 25 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 74 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 103 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
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
| `rendement_stockage` | 167 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
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
