# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-17T07:45:57+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `85ddcb4` — arbre de travail modifié : 6 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 488
Exécutés                 : 488
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 488

Valeurs comparées        : 152 015
Exactes                  : 115 447
Dans tolérance           : 21 597
Hors tolérance           : 12 677
Balises manquantes       : 299
Balises supplémentaires  : 1 941
Écarts non numériques    : 54

Conformité               : 90,15 %
```

Sur ces écarts, **3 755 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,62 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 58654 | 56914 | 1303 | 376 | 18 | 43 | 99,25 % |
| Ventilation | 3340 | 2212 | 1073 | 51 | 3 | 1 | 98,35 % |
| Apports | 15087 | 13092 | 1373 | 311 | 0 | 311 | 95,88 % |
| Besoin chauffage | 2356 | 14 | 2065 | 273 | 4 | 0 | 88,24 % |
| Besoin ECS | 4372 | 4088 | 256 | 28 | 0 | 0 | 99,36 % |
| Génération chauffage | 9728 | 4142 | 3822 | 1732 | 24 | 8 | 81,87 % |
| Génération ECS | 9274 | 6060 | 1807 | 1355 | 38 | 14 | 84,83 % |
| Auxiliaires | 16592 | 10680 | 3096 | 2815 | 0 | 1 | 83,03 % |
| Froid | 6180 | 5536 | 53 | 396 | 188 | 7 | 90,44 % |
| PV | 3455 | 3372 | 7 | 70 | 6 | 0 | 97,80 % |
| Sorties énergie finale | 3222 | 1705 | 951 | 558 | 4 | 4 | 82,43 % |
| Sorties énergie primaire | 3904 | 1839 | 1282 | 751 | 0 | 1 | 79,94 % |
| GES | 6541 | 3009 | 2217 | 1280 | 6 | 6 | 79,90 % |
| Coûts | 5565 | 1105 | 2049 | 2399 | 6 | 6 | 56,68 % |
| Confort d'été | 2733 | 1062 | 0 | 135 | 0 | 1536 | 38,86 % |
| Autre | 1012 | 617 | 243 | 147 | 2 | 3 | 84,98 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 239 | 80232 | 62819 | 11963 | 3983 | 26 | 92,93 % |
| maison_individuelle | 112 | 33270 | 25234 | 3821 | 4013 | 151 | 87,04 % |
| appartement_issu_immeuble | 67 | 22409 | 15555 | 3438 | 2916 | 71 | 84,50 % |
| appartement_individuel | 70 | 16104 | 11839 | 2375 | 1765 | 51 | 87,88 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 349 | 106532 | 81854 | 15732 | 7188 | 187 | 91,30 % |
| pre_2026 | 139 | 45483 | 33593 | 5865 | 5489 | 112 | 86,49 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 319 | 98270 | 76604 | 14485 | 5703 | 12 | 92,39 % |
| 3cl_tribu_1.4.25.1 | 104 | 34698 | 25858 | 4616 | 3885 | 66 | 87,56 % |
| 3cl_tribu_2024.6.1.0 | 18 | 6295 | 4833 | 854 | 535 | 0 | 90,08 % |
| inconnu | 17 | 4977 | 3343 | 651 | 811 | 113 | 79,98 % |
| 3cl_bbs_V2025.11.1.0 | 13 | 3334 | 1884 | 539 | 758 | 70 | 72,39 % |
| 3cl-2024.6.1.0 | 5 | 1263 | 842 | 109 | 286 | 20 | 75,00 % |
| BBS_Slama_2024.6.1.0 | 3 | 936 | 689 | 134 | 107 | 0 | 87,65 % |
| 3cl_bbs_V2024.6.1.0 | 1 | 329 | 211 | 36 | 71 | 4 | 74,85 % |
| V 2024.6.1.0 | 1 | 294 | 196 | 7 | 85 | 5 | 68,81 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 187 | 32 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 170 | 1 | 88 | 0 | 65,52 % |
| V 1.4.25.1 | 1 | 231 | 103 | 3 | 119 | 5 | 45,69 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |
| 3cl_tribu_1.4.23.7 | 1 | 213 | 127 | 31 | 55 | 0 | 73,83 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 732 | 142 | 6 900,0 % | hors-tol×722 manquante×8 suppl×2 |
| 2 | Génération ECS | `conso_ecs` | 614 | 108 | 104,8 % | hors-tol×610 suppl×2 manquante×2 |
| 3 | Génération chauffage | `conso_ch_depensier` | 593 | 145 | 6 900,0 % | hors-tol×587 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 583 | 283 | 936,3 % | hors-tol×579 suppl×2 manquante×2 |
| 5 | Génération ECS | `conso_ecs_depensier` | 510 | 107 | 103,4 % | hors-tol×510 |
| 6 | Coûts | `cout_ecs_depensier` | 466 | 466 | 122,4 % | hors-tol×466 |
| 7 | Coûts | `cout_ch_depensier` | 463 | 463 | 268,0 % | hors-tol×463 |
| 8 | GES | `emission_ges_5_usages` | 431 | 187 | 21 175,5 % | hors-tol×427 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 416 | 175 | 1 016 120,1 % | hors-tol×412 suppl×2 manquante×2 |
| 10 | Coûts | `cout_ch` | 388 | 178 | 621,5 % | hors-tol×384 suppl×2 manquante×2 |
| 11 | Coûts | `cout_ecs` | 381 | 208 | 73,4 % | hors-tol×377 suppl×2 manquante×2 |
| 12 | Confort d'été | `enum_indicateur_confort_ete_id` | 368 | 368 | 100,0 % | hors-tol×61 suppl×307 |
| 13 | Confort d'été | `protection_solaire_exterieure` | 336 | 336 | 100,0 % | hors-tol×29 suppl×307 |
| 14 | Confort d'été | `aspect_traversant` | 334 | 334 | — | hors-tol×27 suppl×307 |
| 15 | Confort d'été | `isolation_toiture` | 323 | 323 | 100,0 % | hors-tol×15 suppl×308 |
| 16 | GES | `emission_ges_ch` | 320 | 139 | 15 993,3 % | hors-tol×316 suppl×2 manquante×2 |
| 17 | Confort d'été | `brasseur_air` | 310 | 310 | 100,0 % | suppl×307 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 310 | 310 | — | suppl×308 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 257 | 257 | 359,4 % | hors-tol×257 |
| 20 | GES | `emission_ges_ecs` | 196 | 97 | 20 212,9 % | hors-tol×192 suppl×2 manquante×2 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 194 | 194 | 141,7 % | hors-tol×194 |
| 22 | Génération ECS | `rendement_stockage` | 182 | 67 | 40,0 % | hors-tol×156 manquante×26 |
| 23 | Auxiliaires | `cout_total_auxiliaire` | 173 | 173 | 3 352,7 % | hors-tol×173 |
| 24 | Sorties énergie primaire | `ep_conso_5_usages` | 151 | 151 | 551,8 % | hors-tol×151 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 143 | 143 | 551,2 % | hors-tol×143 |
| 26 | Besoin chauffage | `besoin_ch_depensier` | 142 | 75 | 1 009,5 % | hors-tol×140 manquante×2 |
| 27 | Sorties énergie finale | `conso_5_usages_m2` | 140 | 140 | 257,3 % | hors-tol×140 |
| 28 | GES | `emission_ges_ch_depensier` | 138 | 138 | 12 237,9 % | hors-tol×138 |
| 29 | Besoin chauffage | `besoin_ch` | 135 | 65 | 290,4 % | hors-tol×133 manquante×2 |
| 30 | Auxiliaires | `cout_auxiliaire_generation_ch` | 133 | 133 | 127,9 % | hors-tol×133 |
| 31 | Sorties énergie primaire | `ep_conso_ch_depensier` | 133 | 133 | 149,9 % | hors-tol×133 |
| 32 | Sorties énergie primaire | `ep_conso_ch` | 131 | 131 | 100,0 % | hors-tol×131 |
| 33 | Coûts | `cout_eclairage` | 130 | 130 | 83,7 % | hors-tol×130 |
| 34 | Génération chauffage | `rendement_generation` | 123 | 81 | 29,0 % | hors-tol×121 manquante×2 |
| 35 | Auxiliaires | `emission_ges_totale_auxiliaire` | 114 | 114 | 41 008,1 % | hors-tol×114 |
| 36 | Auxiliaires | `conso_totale_auxiliaire` | 112 | 112 | 42 072,2 % | hors-tol×112 |
| 37 | Auxiliaires | `ep_conso_totale_auxiliaire` | 112 | 112 | 42 109,2 % | hors-tol×112 |
| 38 | Auxiliaires | `conso_auxiliaire_generation_ch` | 111 | 111 | 100,0 % | hors-tol×111 |
| 39 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 111 | 111 | 101,3 % | hors-tol×111 |
| 40 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 111 | 111 | 4 460,4 % | hors-tol×111 |

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
| `aspect_traversant` | 289 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 289 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 289 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 289 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 289 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 289 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
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
| `qp0` | 20 | la référence sérialise QP0 en kW (1.500) malgré l’unité W imposée par le XSD ; la valeur cohérente avec Pn=125000 W est 1500 W |
| `cout_fr_depensier` | 23 | la référence recopie cout_fr dans cout_fr_depensier alors que les consommations diffèrent (51 vs 129 kWh) |
| `cout_ecs_depensier` | 365 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 365 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 169 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 204 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `cout_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `emission_ges_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `ep_conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_totale_auxiliaire` | 19 | la référence publie un total auxiliaire de 56.6, incompatible avec la somme de ses postes (272.0) |
| `emission_ges_totale_auxiliaire` | 21 | la référence publie un total auxiliaire de 3.6, incompatible avec la somme de ses postes (17.4) |
| `cout_total_auxiliaire` | 26 | la référence publie un total auxiliaire de 19.7, incompatible avec la somme de ses postes (94.4) |
| `ep_conso_totale_auxiliaire` | 19 | la référence publie un total auxiliaire de 107.5, incompatible avec la somme de ses postes (516.8) |
| `besoin_ch_depensier` | 17 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (13746 vs 98393), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 23 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 21 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `k` | 19 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `conso_ecs` | 231 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 230 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 160 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 24 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 4 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `upb` | 3 | la référence déclare un plancher bas isolé (résistance 3.50 m².K/W saisie et justifiée) mais publie upb = upb0 = 2.000, le coefficient non isolé : §3.2.2 p.20 impose upb = 1 / (1/upb0 + R) = 0.250 |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 902 | 140 | 84,48 % |
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 140 | 46,56 % |
| `2406E2911962X.xml` | appartement_individuel | pre_2026 | 231 | 125 | 45,89 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 123 | 85,46 % |
| `2401E2888143P.xml` | maison_individuelle | pre_2026 | 344 | 116 | 66,28 % |
| `2601E0370414Y.xml` | maison_individuelle | post_2026 | 437 | 112 | 74,37 % |
| `2601E0076852C.xml` | maison_individuelle | post_2026 | 389 | 110 | 71,72 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 722 | 109 | 84,90 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 109 | 64,50 % |
| `2601E0089001J.xml` | maison_individuelle | post_2026 | 374 | 106 | 71,66 % |
| `2401E0473146C.xml` | maison_individuelle | pre_2026 | 368 | 105 | 71,47 % |
| `2601E0869643B.xml` | maison_individuelle | post_2026 | 260 | 102 | 60,77 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 463 | 95 | 79,48 % |
| `2401E0490195V.xml` | appartement_individuel | pre_2026 | 242 | 94 | 61,16 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2601E0299209H.xml` | appartement_individuel | post_2026 | 244 | 93 | 61,89 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 93 | 67,14 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 294 | 91 | 69,05 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2268184I.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 89 | 65,77 % |
| `2675E2152874Y.xml` | appartement_issu_immeuble | post_2026 | 335 | 89 | 73,43 % |

## Cas totalement conformes

_Aucun._
