# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-17T07:12:07+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `dff02cc` — arbre de travail modifié : 3 fichier(s) src/ non commités_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 486
Exécutés                 : 486
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 486

Valeurs comparées        : 151 411
Exactes                  : 114 935
Dans tolérance           : 21 444
Hors tolérance           : 12 681
Balises manquantes       : 299
Balises supplémentaires  : 1 992
Écarts non numériques    : 60

Conformité               : 90,07 %
```

Sur ces écarts, **3 762 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **92,56 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 58389 | 56651 | 1302 | 375 | 18 | 43 | 99,25 % |
| Ventilation | 3328 | 2204 | 1069 | 51 | 3 | 1 | 98,35 % |
| Apports | 15030 | 13045 | 1370 | 304 | 0 | 311 | 95,91 % |
| Besoin chauffage | 2348 | 14 | 2065 | 265 | 4 | 0 | 88,54 % |
| Besoin ECS | 4358 | 4074 | 256 | 28 | 0 | 0 | 99,36 % |
| Génération chauffage | 9724 | 4124 | 3787 | 1730 | 24 | 59 | 81,36 % |
| Génération ECS | 9249 | 6037 | 1805 | 1355 | 38 | 14 | 84,79 % |
| Auxiliaires | 16524 | 10645 | 3052 | 2826 | 0 | 1 | 82,89 % |
| Froid | 6156 | 5512 | 53 | 396 | 188 | 7 | 90,40 % |
| PV | 3441 | 3358 | 7 | 70 | 6 | 0 | 97,79 % |
| Sorties énergie finale | 3204 | 1694 | 941 | 561 | 4 | 4 | 82,24 % |
| Sorties énergie primaire | 3888 | 1825 | 1273 | 755 | 0 | 1 | 79,68 % |
| GES | 6507 | 2987 | 2199 | 1283 | 6 | 6 | 79,70 % |
| Coûts | 5535 | 1098 | 2023 | 2402 | 6 | 6 | 56,39 % |
| Confort d'été | 2723 | 1053 | 0 | 134 | 0 | 1536 | 38,67 % |
| Autre | 1007 | 614 | 242 | 146 | 2 | 3 | 85,00 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 239 | 80239 | 62819 | 11963 | 3983 | 26 | 92,92 % |
| maison_individuelle | 110 | 32650 | 24722 | 3672 | 4013 | 151 | 86,67 % |
| appartement_issu_immeuble | 67 | 22411 | 15555 | 3438 | 2916 | 71 | 84,50 % |
| appartement_individuel | 70 | 16111 | 11839 | 2371 | 1769 | 51 | 87,82 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 349 | 106562 | 81846 | 15675 | 7249 | 187 | 91,22 % |
| pre_2026 | 137 | 44849 | 33089 | 5769 | 5432 | 112 | 86,38 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 319 | 98296 | 76596 | 14429 | 5763 | 12 | 92,30 % |
| 3cl_tribu_1.4.25.1 | 102 | 34060 | 25355 | 4531 | 3816 | 66 | 87,48 % |
| 3cl_tribu_2024.6.1.0 | 18 | 6296 | 4833 | 847 | 542 | 0 | 89,96 % |
| inconnu | 17 | 4980 | 3343 | 650 | 812 | 113 | 79,91 % |
| 3cl_bbs_V2025.11.1.0 | 13 | 3335 | 1884 | 539 | 758 | 70 | 72,37 % |
| 3cl-2024.6.1.0 | 5 | 1264 | 841 | 105 | 291 | 20 | 74,55 % |
| BBS_Slama_2024.6.1.0 | 3 | 937 | 689 | 134 | 107 | 0 | 87,55 % |
| 3cl_bbs_V2024.6.1.0 | 1 | 329 | 211 | 36 | 71 | 4 | 74,85 % |
| V 2024.6.1.0 | 1 | 295 | 196 | 7 | 85 | 5 | 68,58 % |
| 3cl_tribu_1.4.25.0 | 1 | 279 | 187 | 32 | 48 | 4 | 78,21 % |
| 3cl_tribu_1.4.24.0 | 1 | 260 | 170 | 1 | 88 | 0 | 65,52 % |
| V 1.4.25.1 | 1 | 231 | 103 | 3 | 119 | 5 | 45,69 % |
| 3cl_tribu_1.4.23.4 | 1 | 219 | 141 | 9 | 63 | 0 | 68,18 % |
| 3cl_BBS_2025.11.1.0 | 1 | 213 | 144 | 58 | 8 | 0 | 94,39 % |
| 3cl_tribu_1.4.23.7 | 1 | 213 | 127 | 31 | 55 | 0 | 73,83 % |

## Principales sources d'écart (par balise)

| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |
|---:|---|---|---:|---:|---:|---|
| 1 | Génération chauffage | `conso_ch` | 732 | 143 | 6 900,0 % | hors-tol×722 manquante×8 suppl×2 |
| 2 | Génération ECS | `conso_ecs` | 614 | 108 | 104,8 % | hors-tol×610 suppl×2 manquante×2 |
| 3 | Génération chauffage | `conso_ch_depensier` | 596 | 146 | 6 900,0 % | hors-tol×590 manquante×6 |
| 4 | Coûts | `cout_5_usages` | 584 | 283 | 1 132,9 % | hors-tol×580 suppl×2 manquante×2 |
| 5 | Génération ECS | `conso_ecs_depensier` | 510 | 107 | 103,4 % | hors-tol×510 |
| 6 | Coûts | `cout_ecs_depensier` | 464 | 464 | 122,3 % | hors-tol×464 |
| 7 | Coûts | `cout_ch_depensier` | 462 | 462 | 698,9 % | hors-tol×462 |
| 8 | GES | `emission_ges_5_usages` | 432 | 187 | 21 175,5 % | hors-tol×428 suppl×2 manquante×2 |
| 9 | Sorties énergie finale | `conso_5_usages` | 418 | 175 | 1 016 120,1 % | hors-tol×414 suppl×2 manquante×2 |
| 10 | Coûts | `cout_ch` | 387 | 178 | 1 132,9 % | hors-tol×383 suppl×2 manquante×2 |
| 11 | Coûts | `cout_ecs` | 386 | 210 | 73,4 % | hors-tol×382 suppl×2 manquante×2 |
| 12 | Confort d'été | `enum_indicateur_confort_ete_id` | 368 | 368 | 100,0 % | hors-tol×61 suppl×307 |
| 13 | Confort d'été | `protection_solaire_exterieure` | 336 | 336 | 100,0 % | hors-tol×29 suppl×307 |
| 14 | Confort d'été | `aspect_traversant` | 334 | 334 | — | hors-tol×27 suppl×307 |
| 15 | Confort d'été | `isolation_toiture` | 322 | 322 | 100,0 % | hors-tol×14 suppl×308 |
| 16 | GES | `emission_ges_ch` | 320 | 140 | 15 993,3 % | hors-tol×316 suppl×2 manquante×2 |
| 17 | Confort d'été | `brasseur_air` | 310 | 310 | 100,0 % | suppl×307 hors-tol×3 |
| 18 | Apports | `inertie_lourde` | 310 | 310 | — | suppl×308 hors-tol×2 |
| 19 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 255 | 255 | 753,8 % | hors-tol×255 |
| 20 | GES | `emission_ges_ecs` | 196 | 97 | 20 212,9 % | hors-tol×192 suppl×2 manquante×2 |
| 21 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 193 | 193 | 141,7 % | hors-tol×193 |
| 22 | Génération ECS | `rendement_stockage` | 182 | 67 | 40,0 % | hors-tol×156 manquante×26 |
| 23 | Génération chauffage | `rendement_generation` | 177 | 127 | 74,4 % | suppl×51 hors-tol×124 manquante×2 |
| 24 | Auxiliaires | `cout_total_auxiliaire` | 173 | 173 | 3 352,7 % | hors-tol×173 |
| 25 | Sorties énergie primaire | `ep_conso_5_usages` | 152 | 152 | 551,8 % | hors-tol×152 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 144 | 144 | 551,2 % | hors-tol×144 |
| 27 | Sorties énergie finale | `conso_5_usages_m2` | 141 | 141 | 299,4 % | hors-tol×141 |
| 28 | GES | `emission_ges_ch_depensier` | 139 | 139 | 12 237,9 % | hors-tol×139 |
| 29 | Besoin chauffage | `besoin_ch_depensier` | 138 | 73 | 1 009,5 % | hors-tol×136 manquante×2 |
| 30 | Sorties énergie primaire | `ep_conso_ch_depensier` | 134 | 134 | 757,1 % | hors-tol×134 |
| 31 | Auxiliaires | `cout_auxiliaire_generation_ch` | 133 | 133 | 355,8 % | hors-tol×133 |
| 32 | Sorties énergie primaire | `ep_conso_ch` | 132 | 132 | 230,9 % | hors-tol×132 |
| 33 | Besoin chauffage | `besoin_ch` | 131 | 63 | 290,4 % | hors-tol×129 manquante×2 |
| 34 | Coûts | `cout_eclairage` | 131 | 131 | 83,7 % | hors-tol×131 |
| 35 | Auxiliaires | `emission_ges_totale_auxiliaire` | 116 | 116 | 41 008,1 % | hors-tol×116 |
| 36 | Auxiliaires | `conso_totale_auxiliaire` | 114 | 114 | 42 072,2 % | hors-tol×114 |
| 37 | Auxiliaires | `ep_conso_totale_auxiliaire` | 114 | 114 | 42 109,2 % | hors-tol×114 |
| 38 | Auxiliaires | `conso_auxiliaire_generation_ch` | 112 | 112 | 300,0 % | hors-tol×112 |
| 39 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 112 | 112 | 300,0 % | hors-tol×112 |
| 40 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 112 | 112 | 4 460,4 % | hors-tol×112 |

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
| `cout_ecs_depensier` | 363 | la référence recopie cout_ecs dans cout_ecs_depensier alors que les consommations diffèrent (2217 vs 2879 kWh) |
| `cout_ch_depensier` | 363 | la référence recopie cout_ch dans cout_ch_depensier alors que les consommations diffèrent (4934 vs 6434 kWh) |
| `cout_auxiliaire_generation_ecs_depensier` | 168 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 202 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
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
| `rendement_generation` | 41 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
| `rendement_generation_stockage` | 4 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |

## Conformité structurelle du XML produit

Aucun chemin produit hors de ceux déclarés par `resources/ademe_DPE.xsd`.

## Cas les plus dégradés

| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |
|---|---|---|---:|---:|---:|
| `2400E0669495Y.xml` | immeuble_collectif | pre_2026 | 902 | 140 | 84,48 % |
| `2113E0368523M.xml` | appartement_individuel | pre_2026 | 262 | 140 | 46,56 % |
| `2406E2911962X.xml` | appartement_individuel | pre_2026 | 231 | 125 | 45,89 % |
| `2400E0575636Z.xml` | immeuble_collectif | pre_2026 | 846 | 123 | 85,46 % |
| `2401E2888143P.xml` | maison_individuelle | pre_2026 | 345 | 122 | 64,64 % |
| `2601E0370414Y.xml` | maison_individuelle | post_2026 | 438 | 113 | 74,20 % |
| `2601E0076852C.xml` | maison_individuelle | post_2026 | 389 | 111 | 71,47 % |
| `2313E3593866F.xml` | appartement_issu_immeuble | pre_2026 | 307 | 110 | 64,17 % |
| `2400E0669425G.xml` | immeuble_collectif | pre_2026 | 722 | 109 | 84,90 % |
| `2601E0089001J.xml` | maison_individuelle | post_2026 | 374 | 109 | 70,86 % |
| `2313E3593911Y.xml` | appartement_issu_immeuble | pre_2026 | 307 | 109 | 64,50 % |
| `2401E0473146C.xml` | maison_individuelle | pre_2026 | 368 | 105 | 71,47 % |
| `2601E0869643B.xml` | maison_individuelle | post_2026 | 261 | 103 | 60,54 % |
| `2659E2253310G.xml` | appartement_issu_immeuble | post_2026 | 256 | 101 | 60,55 % |
| `2400E0020849A.xml` | maison_individuelle | pre_2026 | 464 | 96 | 79,31 % |
| `2401E0490195V.xml` | appartement_individuel | pre_2026 | 242 | 94 | 61,16 % |
| `2659E2236157N.xml` | appartement_issu_immeuble | post_2026 | 283 | 94 | 66,78 % |
| `2659E2542205P.xml` | appartement_issu_immeuble | post_2026 | 261 | 94 | 63,98 % |
| `2601E0299209H.xml` | appartement_individuel | post_2026 | 244 | 93 | 61,89 % |
| `2659E2236995T.xml` | appartement_issu_immeuble | post_2026 | 283 | 93 | 67,14 % |
| `2593E3079342Z.xml` | maison_individuelle | pre_2026 | 295 | 92 | 68,81 % |
| `2600E0660731Y.xml` | immeuble_collectif | post_2026 | 589 | 90 | 84,72 % |
| `2659E2268156G.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2659E2268184I.xml` | appartement_issu_immeuble | post_2026 | 229 | 90 | 60,70 % |
| `2238E1985046L.xml` | appartement_individuel | pre_2026 | 260 | 89 | 65,77 % |

## Cas totalement conformes

_Aucun._
