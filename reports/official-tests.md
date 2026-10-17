# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-17T09:48:22+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `6b07c8f` — arbre de travail modifié : 1 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 509
Exécutés                 : 509
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 509

Valeurs comparées        : 160 163
Exactes                  : 121 756
Dans tolérance           : 22 734
Hors tolérance           : 13 263
Balises manquantes       : 322
Balises supplémentaires  : 2 042
Écarts non numériques    : 46

Conformité               : 90,21 %
```

Sur ces écarts, **4 416 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,97 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 62318 | 60339 | 1475 | 431 | 19 | 54 | 99,19 % |
| Ventilation | 3485 | 2312 | 1119 | 50 | 3 | 1 | 98,45 % |
| Apports | 15978 | 13884 | 1452 | 317 | 0 | 325 | 95,98 % |
| Besoin chauffage | 2468 | 16 | 2139 | 309 | 4 | 0 | 87,32 % |
| Besoin ECS | 4593 | 4278 | 283 | 32 | 0 | 0 | 99,30 % |
| Génération chauffage | 10176 | 4340 | 3946 | 1858 | 24 | 8 | 81,43 % |
| Génération ECS | 9803 | 6351 | 1915 | 1481 | 40 | 16 | 84,32 % |
| Auxiliaires | 17306 | 11215 | 3245 | 2845 | 0 | 1 | 83,55 % |
| Froid | 6456 | 5777 | 55 | 409 | 208 | 7 | 90,33 % |
| PV | 3614 | 3528 | 10 | 67 | 6 | 3 | 97,90 % |
| Sorties énergie finale | 3363 | 1780 | 1004 | 571 | 4 | 4 | 82,78 % |
| Sorties énergie primaire | 4072 | 1914 | 1341 | 788 | 0 | 1 | 79,94 % |
| GES | 6826 | 3126 | 2328 | 1342 | 6 | 6 | 79,90 % |
| Coûts | 5808 | 1162 | 2167 | 2467 | 6 | 6 | 57,32 % |
| Confort d'été | 2849 | 1095 | 0 | 147 | 0 | 1607 | 38,43 % |
| Autre | 1048 | 639 | 255 | 149 | 2 | 3 | 85,31 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 244 | 82117 | 64115 | 12197 | 4286 | 40 | 92,66 % |
| maison_individuelle | 113 | 33571 | 25481 | 4016 | 3876 | 152 | 87,57 % |
| appartement_issu_immeuble | 76 | 27002 | 19267 | 3912 | 3268 | 71 | 85,60 % |
| appartement_individuel | 76 | 17473 | 12893 | 2609 | 1833 | 59 | 88,34 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 358 | 111125 | 85606 | 16378 | 7335 | 187 | 91,48 % |
| pre_2026 | 151 | 49038 | 36150 | 6356 | 5928 | 135 | 86,41 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 328 | 102863 | 80355 | 15113 | 5869 | 12 | 92,52 % |
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
| 1 | Génération chauffage | `conso_ch` | 795 | 152 | 6 900,0 % | hors-tol×785 manquante×8 suppl×2 |
| 2 | Génération ECS | `conso_ecs` | 670 | 112 | 104,8 % | hors-tol×666 suppl×2 manquante×2 |
| 3 | Génération chauffage | `conso_ch_depensier` | 644 | 155 | 6 900,0 % | hors-tol×638 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 607 | 293 | 936,3 % | hors-tol×603 suppl×2 manquante×2 |
| 5 | Génération ECS | `conso_ecs_depensier` | 564 | 113 | 103,4 % | hors-tol×564 |
| 6 | Coûts | `cout_ecs_depensier` | 484 | 484 | 104,4 % | hors-tol×484 |
| 7 | Coûts | `cout_ch_depensier` | 480 | 480 | 128,8 % | hors-tol×480 |
| 8 | GES | `emission_ges_5_usages` | 444 | 194 | 21 175,5 % | hors-tol×440 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 426 | 181 | 1 016 120,1 % | hors-tol×422 suppl×2 manquante×2 |
| 10 | Coûts | `cout_ch` | 401 | 178 | 741,6 % | hors-tol×397 suppl×2 manquante×2 |
| 11 | Confort d'été | `enum_indicateur_confort_ete_id` | 388 | 388 | 100,0 % | hors-tol×67 suppl×321 |
| 12 | Coûts | `cout_ecs` | 384 | 204 | 65,9 % | hors-tol×380 suppl×2 manquante×2 |
| 13 | Confort d'été | `protection_solaire_exterieure` | 353 | 353 | 100,0 % | hors-tol×32 suppl×321 |
| 14 | Confort d'été | `aspect_traversant` | 350 | 350 | — | hors-tol×29 suppl×321 |
| 15 | GES | `emission_ges_ch` | 341 | 148 | 15 993,3 % | hors-tol×337 suppl×2 manquante×2 |
| 16 | Confort d'été | `isolation_toiture` | 339 | 339 | 100,0 % | hors-tol×16 suppl×323 |
| 17 | Confort d'été | `brasseur_air` | 324 | 324 | 100,0 % | suppl×321 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 324 | 324 | — | suppl×322 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 260 | 260 | 359,4 % | hors-tol×260 |
| 20 | Génération ECS | `rendement_stockage` | 210 | 74 | 40,0 % | hors-tol×182 manquante×28 |
| 21 | GES | `emission_ges_ecs` | 206 | 102 | 20 212,9 % | hors-tol×202 suppl×2 manquante×2 |
| 22 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 197 | 197 | 141,7 % | hors-tol×197 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 181 | 181 | 20 278,1 % | hors-tol×181 |
| 24 | Besoin chauffage | `besoin_ch_depensier` | 160 | 75 | 1 009,5 % | hors-tol×158 manquante×2 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 155 | 155 | 551,8 % | hors-tol×155 |
| 26 | Besoin chauffage | `besoin_ch` | 153 | 65 | 741,6 % | hors-tol×151 manquante×2 |
| 27 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 148 | 148 | 551,2 % | hors-tol×148 |
| 28 | GES | `emission_ges_ch_depensier` | 147 | 147 | 12 237,9 % | hors-tol×147 |
| 29 | Sorties énergie finale | `conso_5_usages_m2` | 143 | 143 | 257,3 % | hors-tol×143 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 142 | 142 | 149,9 % | hors-tol×142 |
| 31 | Sorties énergie primaire | `ep_conso_ch` | 140 | 140 | 101,9 % | hors-tol×140 |
| 32 | Auxiliaires | `cout_auxiliaire_generation_ch` | 133 | 133 | 100,0 % | hors-tol×133 |
| 33 | Génération chauffage | `rendement_generation` | 129 | 84 | 29,0 % | hors-tol×127 manquante×2 |
| 34 | Coûts | `cout_eclairage` | 123 | 123 | 83,7 % | hors-tol×123 |
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
| `aspect_traversant` | 300 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 300 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 300 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 300 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 300 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 300 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
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
| `ep_conso_ecs_depensier` | 75 | la référence recopie ep_conso_ecs dans ep_conso_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `ep_conso_ch_depensier` | 101 | la référence recopie ep_conso_ch dans ep_conso_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
| `emission_ges_fr_depensier` | 25 | la référence recopie emission_ges_fr dans emission_ges_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `emission_ges_ecs_depensier` | 77 | la référence recopie emission_ges_ecs dans emission_ges_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `emission_ges_ch_depensier` | 106 | la référence recopie emission_ges_ch dans emission_ges_ch_depensier alors que les consommations diffèrent (1369 vs 1823 kWh) |
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
| `emission_ges_ecs` | 25 | la référence publie emission_ges_ecs = 327.6 mais sa propre ventilation par énergie en somme 0.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `emission_ges_ch` | 30 | la référence publie emission_ges_ch = 244.6 mais sa propre ventilation par énergie en somme 0.0 sur 2 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `k` | 19 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `cout_ch` | 2 | la référence publie cout_ch = 1214.0 mais sa propre ventilation par énergie en somme 0.0 sur 1 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ch` | 20 | la référence publie conso_ch = 13741.0 mais sa propre ventilation par énergie en somme 27482.0 sur 3 ligne(s) : le XSD définit sortie_par_energie comme la décomposition de <sortie>, leur somme est l'agrégat par construction |
| `conso_ecs` | 253 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 248 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 186 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
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
