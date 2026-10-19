# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-19T07:37:55+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `d93bfd3`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 577
Exécutés                 : 577
Crash                    : 0
Totalement conformes     : 1
Partiellement conformes  : 576

Valeurs comparées        : 179 030
Exactes                  : 136 007
Dans tolérance           : 25 949
Hors tolérance           : 14 499
Balises manquantes       : 382
Balises supplémentaires  : 2 160
Écarts non numériques    : 33

Conformité               : 90,46 %
Avancement               : 92,95 %
```

**Avancement : 92,95 %.** C'est le chiffre de suivi : la part des valeurs **atteignables** qui sont conformes. Il vaut 100 % quand tout ce qui dépend du moteur est juste.

Il se distingue de la conformité brute (90,46 %) par un point : 4 785 écarts sont imputables à la **référence** et non au moteur — le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels, et les reproduire nous éloignerait de la méthode. Ces écarts sortent du dénominateur de l'avancement, jamais du décompte brut : la mesure ne se maquille pas. Détail des imputations plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 69212 | 66936 | 1675 | 509 | 29 | 63 | 99,13 % |
| Ventilation | 3958 | 2658 | 1228 | 68 | 3 | 1 | 98,18 % |
| Apports | 17784 | 15399 | 1673 | 370 | 0 | 342 | 96,00 % |
| Besoin chauffage | 2774 | 20 | 2359 | 395 | 0 | 0 | 85,76 % |
| Besoin ECS | 5073 | 4705 | 304 | 64 | 0 | 0 | 98,74 % |
| Génération chauffage | 11400 | 4898 | 4499 | 1985 | 6 | 12 | 82,43 % |
| Génération ECS | 10601 | 6946 | 1993 | 1591 | 52 | 19 | 84,32 % |
| Auxiliaires | 19618 | 12764 | 3740 | 3113 | 0 | 1 | 84,13 % |
| Froid | 7360 | 6565 | 233 | 287 | 268 | 7 | 92,36 % |
| PV | 4102 | 3984 | 16 | 93 | 6 | 3 | 97,51 % |
| Sorties énergie finale | 3823 | 2016 | 1134 | 665 | 4 | 4 | 82,40 % |
| Sorties énergie primaire | 4616 | 2180 | 1513 | 902 | 0 | 1 | 80,00 % |
| GES | 7754 | 3540 | 2701 | 1488 | 6 | 6 | 80,49 % |
| Coûts | 6600 | 1377 | 2604 | 2607 | 6 | 6 | 60,32 % |
| Confort d'été | 3205 | 1310 | 0 | 203 | 0 | 1692 | 40,87 % |
| Autre | 1150 | 709 | 277 | 159 | 2 | 3 | 85,74 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 248 | 83489 | 65187 | 12685 | 4084 | 34 | 93,00 % |
| maison_individuelle | 133 | 39959 | 30442 | 4791 | 4500 | 181 | 87,88 % |
| appartement_issu_immeuble | 96 | 32349 | 23142 | 4978 | 3556 | 102 | 86,67 % |
| appartement_individuel | 100 | 23233 | 17236 | 3495 | 2359 | 65 | 88,85 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 407 | 124503 | 95730 | 18543 | 8266 | 239 | 91,48 % |
| pre_2026 | 170 | 54527 | 40277 | 7406 | 6233 | 143 | 87,18 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 366 | 113153 | 88405 | 16967 | 6170 | 4 | 92,82 % |
| 3cl_tribu_1.4.25.1 | 122 | 39934 | 29816 | 5584 | 4195 | 74 | 88,38 % |
| 3cl_tribu_2024.6.1.0 | 24 | 8088 | 6230 | 1145 | 623 | 1 | 90,91 % |
| inconnu | 26 | 7503 | 5024 | 923 | 1318 | 165 | 78,99 % |
| 3cl_bbs_V2025.11.1.0 | 14 | 3654 | 2120 | 593 | 785 | 74 | 73,96 % |
| 3cl-2024.6.1.0 | 7 | 1738 | 1188 | 167 | 348 | 28 | 77,65 % |
| 3cl_bbs_V2024.6.1.0 | 4 | 1310 | 774 | 139 | 351 | 18 | 69,48 % |
| BBS_Slama_2024.6.1.0 | 4 | 1281 | 972 | 189 | 106 | 0 | 90,35 % |
| V 2024.6.1.0 | 1 | 294 | 196 | 7 | 85 | 5 | 68,81 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 187 | 33 | 48 | 4 | 78,57 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 173 | 2 | 84 | 0 | 67,05 % |
| V 2025.11.1.0 | 1 | 242 | 158 | 3 | 77 | 4 | 66,26 % |
| V 1.4.25.1 | 1 | 231 | 107 | 5 | 113 | 5 | 48,28 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |
| 3cl_BBS_2024.6.1.0 | 1 | 214 | 130 | 61 | 16 | 0 | 88,84 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 856 | 168 | 6 900,0 % | hors-tol×852 suppl×2 manquante×2 |
| 2 | Génération ECS | `conso_ecs` | 718 | 124 | 104,8 % | hors-tol×714 suppl×2 manquante×2 |
| 3 | Génération chauffage | `conso_ch_depensier` | 704 | 176 | 6 900,0 % | hors-tol×704 |
| 4 | Génération ECS | `conso_ecs_depensier` | 611 | 133 | 103,4 % | hors-tol×611 |
| 5 | Coûts | `cout_5_usages` | 594 | 252 | 226,0 % | hors-tol×590 suppl×2 manquante×2 |
| 6 | Coûts | `cout_ecs_depensier` | 535 | 535 | 103,8 % | hors-tol×535 |
| 7 | Coûts | `cout_ch_depensier` | 532 | 532 | 128,8 % | hors-tol×532 |
| 8 | GES | `emission_ges_5_usages` | 498 | 215 | 21 172,3 % | hors-tol×494 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 492 | 209 | 1 016 120,1 % | hors-tol×488 suppl×2 manquante×2 |
| 10 | Coûts | `cout_ch` | 438 | 192 | 138,2 % | hors-tol×434 suppl×2 manquante×2 |
| 11 | Confort d'été | `enum_indicateur_confort_ete_id` | 431 | 431 | 200,0 % | hors-tol×93 suppl×338 |
| 12 | Coûts | `cout_ecs` | 387 | 191 | 65,9 % | hors-tol×383 suppl×2 manquante×2 |
| 13 | GES | `emission_ges_ch` | 380 | 164 | 15 993,3 % | hors-tol×376 suppl×2 manquante×2 |
| 14 | Confort d'été | `protection_solaire_exterieure` | 380 | 380 | 100,0 % | hors-tol×42 suppl×338 |
| 15 | Confort d'été | `aspect_traversant` | 376 | 376 | — | hors-tol×38 suppl×338 |
| 16 | Confort d'été | `isolation_toiture` | 366 | 366 | 100,0 % | hors-tol×26 suppl×340 |
| 17 | Confort d'été | `brasseur_air` | 342 | 342 | 100,0 % | suppl×338 hors-tol×4 |
| 18 | Apports | `inertie_lourde` | 342 | 342 | — | suppl×339 hors-tol×3 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 288 | 288 | 242,1 % | hors-tol×288 |
| 20 | Génération ECS | `rendement_stockage` | 220 | 84 | 40,0 % | hors-tol×183 manquante×37 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 219 | 219 | 141,7 % | hors-tol×219 |
| 22 | GES | `emission_ges_ecs` | 218 | 108 | 20 212,9 % | hors-tol×214 suppl×2 manquante×2 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 204 | 204 | 20 278,1 % | hors-tol×204 |
| 24 | Besoin chauffage | `besoin_ch_depensier` | 202 | 92 | 1 009,5 % | hors-tol×202 |
| 25 | Besoin chauffage | `besoin_ch` | 193 | 80 | 562,2 % | hors-tol×193 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages` | 184 | 184 | 43,1 % | hors-tol×184 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 178 | 178 | 43,0 % | hors-tol×178 |
| 28 | Sorties énergie finale | `conso_5_usages_m2` | 172 | 172 | 40,1 % | hors-tol×172 |
| 29 | GES | `emission_ges_ch_depensier` | 169 | 169 | 12 237,9 % | hors-tol×169 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 161 | 161 | 98,2 % | hors-tol×161 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 156 | 156 | 84,2 % | hors-tol×156 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 139 | 139 | 100,0 % | hors-tol×139 |
| 33 | Auxiliaires | `cout_auxiliaire_ventilation` | 136 | 136 | 112,0 % | hors-tol×136 |
| 34 | Auxiliaires | `cout_auxiliaire_distribution_ch` | 134 | 134 | 71,3 % | hors-tol×134 |
| 35 | Coûts | `cout_eclairage` | 133 | 133 | 72,0 % | hors-tol×133 |
| 36 | Auxiliaires | `conso_totale_auxiliaire` | 123 | 123 | 19 463,7 % | hors-tol×123 |
| 37 | Auxiliaires | `emission_ges_totale_auxiliaire` | 123 | 123 | 19 785,9 % | hors-tol×123 |
| 38 | Auxiliaires | `ep_conso_totale_auxiliaire` | 123 | 123 | 19 432,2 % | hors-tol×123 |
| 39 | GES | `emission_ges_ecs_depensier` | 119 | 119 | 15 144,9 % | hors-tol×119 |
| 40 | Sorties énergie primaire | `ep_conso_ecs_depensier` | 114 | 114 | 53,1 % | hors-tol×114 |

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
| `aspect_traversant` | 316 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 316 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 316 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 316 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 316 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 316 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
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
| `ep_conso_fr_depensier` | 30 | la référence recopie ep_conso_fr dans ep_conso_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `ep_conso_ecs_depensier` | 87 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 108 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 30 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 90 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 116 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `cout_fr_depensier` | 28 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 403 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 403 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 188 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (3 vs 4 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 223 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (9 vs 20 kWh) |
| `cout_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `emission_ges_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `ep_conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_totale_auxiliaire` | 22 | la référence publie un total auxiliaire de 56.6, incompatible avec la somme de ses postes (272.0) |
| `emission_ges_totale_auxiliaire` | 22 | la référence publie un total auxiliaire de 3.6, incompatible avec la somme de ses postes (17.4) |
| `cout_total_auxiliaire` | 29 | la référence publie un total auxiliaire de 19.7, incompatible avec la somme de ses postes (94.4) |
| `ep_conso_totale_auxiliaire` | 22 | la référence publie un total auxiliaire de 107.5, incompatible avec la somme de ses postes (516.8) |
| `besoin_ch_depensier` | 25 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (13746 vs 98393), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 42 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 33 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `production_ch_solaire` | 8 | la référence publie production_ch_solaire = 14225.096 et conso_ch = 11968.755 pour fch = 0.28 : §9.2 p.62 et §9.6 p.65 posent les deux sur le même produit Bch × Σ(part × INT × Ich), d'où Prod / Cch = Fch / (1 − Fch) = 0.3889, quand le fichier publie 1.1885 |
| `hvent` | 7 | la référence publie hvent = 0 alors que son renouvellement d'air vaut 313.773 pour un hperm de 133.484 : les deux termes ne peuvent pas en sommer un troisième |
| `ep_conso_ecs` | 1 | la référence publie ep_conso_ecs et ep_conso_ecs_depensier permutés : leurs rapports à la consommation valent 1.41000 et 0.70922 en direct, mais 1.00000 de part et d'autre une fois croisés |
| `emission_ges_ecs` | 27 | la référence publie emission_ges_ecs = 972.3 mais sa propre ventilation par énergie en somme 10.3 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `emission_ges_ch` | 33 | la référence publie emission_ges_ch = 5040.5 mais sa propre ventilation par énergie en somme 53.4 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `k` | 19 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `cout_ch` | 2 | la référence publie cout_ch = 1214.0 mais sa propre ventilation par énergie en somme 0.0 sur 1 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ch` | 25 | la référence publie conso_ch = 19312.3 mais sa propre ventilation par énergie en somme 19542.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ecs` | 253 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 248 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 187 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `cop` | 4 | la référence déclare une saisie forfaitaire (enum_methode_saisie_carac_sys_id = 1) mais publie cop = 3.07 : §14.2 p.95 ne tabule que 2 ; 2.1 ; 2.2 ; 2.3 ; 2.4 ; 2.5 ; 2.6 ; 2.8 ; 2.9 pour les chauffe-eau thermodynamiques |
| `q4pa_conv` | 2 | la référence déclare une perméabilité mesurée de 1.700 m³/(h.m²) (enum_methode_saisie_q4pa_conv_id = 2) mais publie 1.500, la valeur forfaitaire : §4 p.44 ne l'emploie qu'à défaut de mesure |
| `rendement_generation` | 26 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
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
| `2406E2911962X.xml` | appartement_individuel | pre_2026 | 231 | 119 | 48,48 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 118 | 86,05 % |
| `2401E2888143P.xml` | maison_individuelle | pre_2026 | 344 | 113 | 67,15 % |
| `2601E0370414Y.xml` | maison_individuelle | post_2026 | 437 | 111 | 74,60 % |
| `2501E0139457Y.xml` | immeuble_collectif | pre_2026 | 421 | 111 | 73,63 % |
| `2601E0089001J.xml` | maison_individuelle | post_2026 | 374 | 108 | 71,12 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 724 | 107 | 85,22 % |
| `2601E0076852C.xml` | maison_individuelle | post_2026 | 389 | 107 | 72,49 % |
| `2501E0122493M.xml` | immeuble_collectif | pre_2026 | 276 | 106 | 61,59 % |
| `2401E0473146C.xml` | maison_individuelle | pre_2026 | 368 | 105 | 71,47 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 104 | 66,12 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 103 | 66,45 % |
| `2601E0869643B.xml` | maison_individuelle | post_2026 | 260 | 101 | 61,15 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |
| `2659E0412858Q.xml` | immeuble_collectif | post_2026 | 518 | 100 | 80,69 % |
| `2401E2025720M.xml` | appartement_individuel | pre_2026 | 239 | 99 | 58,58 % |
| `2501E0132767Q.xml` | immeuble_collectif | pre_2026 | 284 | 99 | 65,14 % |
| `2695E2587385R.xml` | appartement_issu_immeuble | post_2026 | 515 | 99 | 80,78 % |
| `2401E0490195V.xml` | appartement_individuel | pre_2026 | 242 | 94 | 61,16 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 294 | 91 | 69,05 % |

## Cas totalement conformes

- `2659E2613722G.xml` (appartement_individuel, post_2026) — 218 valeurs
