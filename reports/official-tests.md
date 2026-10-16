# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-16T19:25:55+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `19dd7b0`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 410
Exécutés                 : 410
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 410

Valeurs comparées        : 128 773
Exactes                  : 97 915
Dans tolérance           : 19 312
Hors tolérance           : 9 400
Balises manquantes       : 183
Balises supplémentaires  : 1 933
Écarts non numériques    : 30

Conformité               : 91,03 %
```

Sur ces écarts, **3 558 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,80 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 49593 | 48307 | 1007 | 230 | 11 | 38 | 99,44 % |
| Ventilation | 2808 | 1820 | 954 | 31 | 2 | 1 | 98,79 % |
| Apports | 12720 | 11016 | 1149 | 246 | 0 | 309 | 95,64 % |
| Besoin chauffage | 1998 | 12 | 1837 | 145 | 4 | 0 | 92,54 % |
| Besoin ECS | 3814 | 3566 | 222 | 26 | 0 | 0 | 99,32 % |
| Génération chauffage | 8199 | 3487 | 3482 | 1175 | 22 | 33 | 85,00 % |
| Génération ECS | 8310 | 5324 | 1735 | 1225 | 20 | 6 | 84,95 % |
| Auxiliaires | 13940 | 8860 | 2802 | 2277 | 0 | 1 | 83,66 % |
| Froid | 5128 | 4716 | 30 | 259 | 116 | 7 | 92,55 % |
| PV | 2885 | 2871 | 3 | 5 | 6 | 0 | 99,62 % |
| Sorties énergie finale | 2700 | 1466 | 872 | 360 | 0 | 2 | 86,59 % |
| Sorties énergie primaire | 3280 | 1583 | 1159 | 519 | 0 | 1 | 83,60 % |
| GES | 5485 | 2615 | 1983 | 872 | 0 | 3 | 83,83 % |
| Coûts | 4665 | 981 | 1845 | 1836 | 0 | 3 | 60,58 % |
| Confort d'été | 2341 | 738 | 0 | 77 | 0 | 1526 | 31,52 % |
| Autre | 907 | 553 | 232 | 117 | 2 | 3 | 86,55 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 237 | 79380 | 62147 | 11854 | 3917 | 26 | 92,95 % |
| appartement_issu_immeuble | 67 | 22411 | 15555 | 3438 | 2916 | 71 | 84,50 % |
| maison_individuelle | 54 | 14914 | 11336 | 2070 | 1428 | 52 | 89,56 % |
| appartement_individuel | 52 | 12068 | 8877 | 1950 | 1139 | 34 | 89,33 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 311 | 94993 | 73063 | 14656 | 5602 | 118 | 92,04 % |
| pre_2026 | 99 | 33780 | 24852 | 4656 | 3798 | 65 | 87,10 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 290 | 89536 | 69685 | 13691 | 4682 | 11 | 92,82 % |
| 3cl_tribu_1.4.25.1 | 69 | 24435 | 18063 | 3517 | 2564 | 32 | 88,07 % |
| 3cl_tribu_2024.6.1.0 | 16 | 5703 | 4390 | 775 | 465 | 0 | 90,31 % |
| 3cl_bbs_V2025.11.1.0 | 12 | 3040 | 1686 | 509 | 697 | 65 | 71,92 % |
| inconnu | 9 | 2466 | 1669 | 399 | 307 | 50 | 83,56 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 689 | 134 | 107 | 0 | 87,55 % |
| 3cl-2024.6.1.0 | 3 | 644 | 442 | 81 | 105 | 12 | 80,83 % |
| 3cl_bbs_V2024.6.1.0 | 1 | 329 | 211 | 36 | 71 | 4 | 74,85 % |
| V 2024.6.1.0 | 1 | 295 | 196 | 7 | 85 | 5 | 68,58 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 187 | 32 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 170 | 1 | 88 | 0 | 65,52 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |
| 3cl_tribu_1.4.23.7 | 1 | 213 | 127 | 31 | 55 | 0 | 73,83 % |
| 3cl_tribu_1.4.25 | 1 | 204 | 115 | 32 | 55 | 0 | 71,71 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération ECS | `conso_ecs` | 551 | 89 | 104,8 % | hors-tol×550 suppl×1 |
| 2 | Génération ECS | `conso_ecs_depensier` | 465 | 88 | 103,4 % | hors-tol×465 |
| 3 | Génération chauffage | `conso_ch` | 461 | 93 | 6 900,0 % | hors-tol×454 manquante×6 suppl×1 |
| 4 | Coûts | `cout_5_usages` | 427 | 223 | 936,3 % | hors-tol×426 suppl×1 |
| 5 | Génération chauffage | `conso_ch_depensier` | 395 | 96 | 6 900,0 % | hors-tol×389 manquante×6 |
| 6 | Coûts | `cout_ecs_depensier` | 394 | 394 | 122,3 % | hors-tol×394 |
| 7 | Coûts | `cout_ch_depensier` | 391 | 391 | 268,0 % | hors-tol×391 |
| 8 | Confort d'été | `enum_indicateur_confort_ete_id` | 338 | 338 | 100,0 % | hors-tol×33 suppl×305 |
| 9 | Confort d'été | `protection_solaire_exterieure` | 324 | 324 | 100,0 % | hors-tol×19 suppl×305 |
| 10 | Confort d'été | `aspect_traversant` | 322 | 322 | — | hors-tol×17 suppl×305 |
| 11 | Confort d'été | `isolation_toiture` | 313 | 313 | 100,0 % | hors-tol×7 suppl×306 |
| 12 | Apports | `inertie_lourde` | 308 | 308 | — | suppl×306 hors-tol×2 |
| 13 | Confort d'été | `brasseur_air` | 306 | 306 | 100,0 % | suppl×305 hors-tol×1 |
| 14 | Coûts | `cout_ecs` | 288 | 161 | 73,4 % | hors-tol×287 suppl×1 |
| 15 | GES | `emission_ges_5_usages` | 282 | 130 | 20 316,2 % | hors-tol×281 suppl×1 |
| 16 | Sorties énergie finale | `conso_5_usages` | 272 | 122 | 1 016 120,1 % | hors-tol×271 suppl×1 |
| 17 | Coûts | `cout_ch` | 252 | 124 | 301,0 % | hors-tol×251 suppl×1 |
| 18 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 228 | 228 | 202,6 % | hors-tol×228 |
| 19 | GES | `emission_ges_ch` | 193 | 90 | 9 092,3 % | hors-tol×192 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 176 | 176 | 141,7 % | hors-tol×176 |
| 21 | Génération ECS | `rendement_stockage` | 172 | 58 | 40,0 % | hors-tol×156 manquante×16 |
| 22 | GES | `emission_ges_ecs` | 156 | 78 | 8 232,9 % | hors-tol×155 suppl×1 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 122 | 122 | 3 352,7 % | hors-tol×122 |
| 24 | Génération chauffage | `rendement_generation` | 121 | 81 | 30,7 % | suppl×26 hors-tol×93 manquante×2 |
| 25 | Auxiliaires | `cout_auxiliaire_generation_ch` | 111 | 111 | 100,0 % | hors-tol×111 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages` | 100 | 100 | 551,8 % | hors-tol×100 |
| 27 | Auxiliaires | `conso_auxiliaire_generation_ch` | 94 | 94 | 100,0 % | hors-tol×94 |
| 28 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 94 | 94 | 101,3 % | hors-tol×94 |
| 29 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 94 | 94 | 100,0 % | hors-tol×94 |
| 30 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 94 | 94 | 100,0 % | hors-tol×94 |
| 31 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 94 | 94 | 100,0 % | hors-tol×94 |
| 32 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 94 | 94 | 170,4 % | hors-tol×94 |
| 33 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 92 | 92 | 551,2 % | hors-tol×92 |
| 34 | GES | `emission_ges_ch_depensier` | 90 | 90 | 9 093,8 % | hors-tol×90 |
| 35 | Coûts | `cout_eclairage` | 87 | 87 | 83,7 % | hors-tol×87 |
| 36 | Sorties énergie finale | `conso_5_usages_m2` | 87 | 87 | 257,3 % | hors-tol×87 |
| 37 | Sorties énergie primaire | `ep_conso_ch` | 86 | 86 | 100,0 % | hors-tol×86 |
| 38 | Sorties énergie primaire | `ep_conso_ch_depensier` | 86 | 86 | 100,0 % | hors-tol×86 |
| 39 | Génération chauffage | `qp0` | 86 | 80 | 205 028,2 % | hors-tol×85 suppl×1 |
| 40 | Génération chauffage | `pn` | 84 | 76 | 275,0 % | hors-tol×77 manquante×6 suppl×1 |

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
| `aspect_traversant` | 287 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 287 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 287 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 287 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 287 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 287 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 19 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 16730 publié au lieu de 43884 |
| `apport_interne_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `apport_solaire_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `fraction_apport_gratuit_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `fraction_apport_gratuit_depensier_ch` | 3 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `besoin_ecs` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_distribution_ecs_recup` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_distribution_ecs_recup_depensier` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_generateur_ch_recup` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `pertes_generateur_ch_recup_depensier` | 1 | la référence sérialise les apports et les pertes récupérées en Wh (3312583) quand les besoins du même bloc sont en kWh (14675), et publie en conséquence une fraction d’apports gratuits saturée à 1 |
| `qp0` | 18 | la référence sérialise QP0 en kW (1.500) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=125000 W est 1500 W |
| `cout_fr_depensier` | 19 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 316 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 316 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 153 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 188 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `cout_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `emission_ges_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `ep_conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_totale_auxiliaire` | 15 | la référence publie un total auxiliaire de 56.6, incompatible avec la somme de ses postes (272.0) |
| `emission_ges_totale_auxiliaire` | 15 | la référence publie un total auxiliaire de 3.6, incompatible avec la somme de ses postes (17.4) |
| `cout_total_auxiliaire` | 18 | la référence publie un total auxiliaire de 19.7, incompatible avec la somme de ses postes (94.4) |
| `ep_conso_totale_auxiliaire` | 15 | la référence publie un total auxiliaire de 107.5, incompatible avec la somme de ses postes (516.8) |
| `besoin_ch_depensier` | 17 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (13746 vs 98393), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 9 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 9 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `k` | 15 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `conso_ecs` | 231 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 230 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 160 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 30 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 4 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 902 | 140 | 84,48 % |
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 140 | 46,56 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 123 | 85,46 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 722 | 109 | 84,90 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 109 | 64,50 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 464 | 96 | 79,31 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 93 | 67,14 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 295 | 92 | 68,81 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 90 | 84,72 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2268184I.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 89 | 65,77 % |
| `2675E2152874Y.xml` | appartement_issu_immeuble | post_2026 | 335 | 89 | 73,43 % |
| `2675E2540542G.xml` | appartement_issu_immeuble | post_2026 | 417 | 89 | 78,66 % |
| `2675E0942311V.xml` | immeuble_collectif | post_2026 | 410 | 86 | 79,02 % |
| `2675E0021756W.xml` | immeuble_collectif | post_2026 | 356 | 84 | 76,40 % |
| `2600E0035103I.xml` | immeuble_collectif | post_2026 | 642 | 83 | 87,07 % |
| `2459E4183923N.xml` | immeuble_collectif | pre_2026 | 329 | 82 | 75,08 % |
| `2675E0022506S.xml` | immeuble_collectif | post_2026 | 323 | 82 | 74,61 % |
| `2600E0025586H.xml` | maison_individuelle | post_2026 | 354 | 81 | 77,12 % |
| `2400E0124709Q.xml` | maison_individuelle | pre_2026 | 318 | 80 | 74,84 % |

## Cas totalement conformes

_Aucun._
