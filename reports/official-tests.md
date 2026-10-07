# Conformité du moteur — jeux de tests DPE 3CL

_Généré le 2026-10-07T17:08:33+00:00 — profil de tolérance : `strict`_
_Révision mesurée : `6eb1805`_

Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et
`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise
n'est exclue : une balise attendue mais non produite compte comme non conforme.

## Synthèse

```
Cas                      : 396
Exécutés                 : 396
Crash                    : 0
Totalement conformes     : 0
Partiellement conformes  : 396

Valeurs comparées        : 125 150
Exactes                  : 95 209
Dans tolérance           : 18 784
Hors tolérance           : 9 067
Balises manquantes       : 162
Balises supplémentaires  : 1 899
Écarts non numériques    : 29

Conformité               : 91,09 %
```

Sur ces écarts, **3 521 sont imputables à la référence** et non au moteur : le corpus n'est pas la vérité réglementaire, ce sont les sorties d'autres logiciels. Les corriger nous éloignerait de la méthode. Plafond réellement atteignable sur ce corpus : **93,90 %**. Détail plus bas.

## Écarts par famille fonctionnelle

| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| Enveloppe | 48336 | 47137 | 935 | 218 | 11 | 35 | 99,45 % |
| Ventilation | 2711 | 1757 | 921 | 30 | 2 | 1 | 98,78 % |
| Apports | 12399 | 10748 | 1105 | 242 | 0 | 304 | 95,60 % |
| Besoin chauffage | 1942 | 8 | 1799 | 131 | 4 | 0 | 93,05 % |
| Besoin ECS | 3716 | 3487 | 208 | 21 | 0 | 0 | 99,43 % |
| Génération chauffage | 7983 | 3388 | 3406 | 1138 | 19 | 32 | 85,11 % |
| Génération ECS | 8150 | 5211 | 1715 | 1199 | 19 | 6 | 84,98 % |
| Auxiliaires | 13464 | 8520 | 2717 | 2226 | 0 | 1 | 83,46 % |
| Froid | 4944 | 4548 | 30 | 259 | 100 | 7 | 92,60 % |
| PV | 2787 | 2773 | 3 | 5 | 6 | 0 | 99,61 % |
| Sorties énergie finale | 2604 | 1413 | 853 | 336 | 0 | 2 | 87,02 % |
| Sorties énergie primaire | 3168 | 1530 | 1135 | 485 | 0 | 1 | 84,12 % |
| GES | 5292 | 2523 | 1937 | 817 | 0 | 3 | 84,28 % |
| Coûts | 4500 | 934 | 1794 | 1769 | 0 | 3 | 60,62 % |
| Confort d'été | 2269 | 693 | 0 | 75 | 0 | 1501 | 30,54 % |
| Autre | 885 | 539 | 226 | 116 | 1 | 3 | 86,44 % |

## Écarts par périmètre d'évaluation

Périmètres du règlement d'évaluation CSTB §1.1, déduits de
`enum_methode_application_dpe_log_id`.

| Périmètre | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| immeuble_collectif | 237 | 79380 | 62141 | 11854 | 3923 | 26 | 92,94 % |
| appartement_issu_immeuble | 62 | 21129 | 14734 | 3277 | 2666 | 55 | 84,99 % |
| maison_individuelle | 47 | 13023 | 9871 | 1768 | 1310 | 47 | 89,05 % |
| appartement_individuel | 50 | 11618 | 8463 | 1885 | 1168 | 34 | 88,69 % |

## Écarts par régime du coefficient EP électricité

| Régime | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| post_2026 | 297 | 91370 | 70357 | 14128 | 5269 | 97 | 92,17 % |
| pre_2026 | 99 | 33780 | 24852 | 4656 | 3798 | 65 | 87,10 % |

## Écarts par moteur de calcul de la référence

Un écart concentré sur un seul moteur éditeur signale plutôt une
particularité de ce logiciel qu'un défaut de notre implémentation.

| Moteur | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |
|---|---:|---:|---:|---:|---:|---:|---:|
| BBS_Slama_2025.11.1.0 | 280 | 86886 | 67569 | 13293 | 4559 | 11 | 92,77 % |
| 3cl_tribu_1.4.25.1 | 69 | 24435 | 18063 | 3517 | 2564 | 32 | 88,07 % |
| 3cl_tribu_2024.6.1.0 | 16 | 5703 | 4390 | 775 | 465 | 0 | 90,31 % |
| 3cl_bbs_V2025.11.1.0 | 9 | 2334 | 1276 | 396 | 552 | 49 | 71,36 % |
| inconnu | 8 | 2199 | 1489 | 382 | 242 | 45 | 84,78 % |
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
| 1 | Génération ECS | `conso_ecs` | 537 | 84 | 104,8 % | hors-tol×536 suppl×1 |
| 2 | Génération ECS | `conso_ecs_depensier` | 456 | 83 | 103,4 % | hors-tol×456 |
| 3 | Génération chauffage | `conso_ch` | 441 | 87 | 6 900,0 % | hors-tol×434 manquante×6 suppl×1 |
| 4 | Coûts | `cout_5_usages` | 409 | 216 | 936,3 % | hors-tol×408 suppl×1 |
| 5 | Coûts | `cout_ecs_depensier` | 381 | 381 | 122,3 % | hors-tol×381 |
| 6 | Génération chauffage | `conso_ch_depensier` | 381 | 90 | 6 900,0 % | hors-tol×375 manquante×6 |
| 7 | Coûts | `cout_ch_depensier` | 379 | 379 | 268,0 % | hors-tol×379 |
| 8 | Confort d'été | `enum_indicateur_confort_ete_id` | 333 | 333 | 100,0 % | hors-tol×33 suppl×300 |
| 9 | Confort d'été | `protection_solaire_exterieure` | 318 | 318 | 100,0 % | hors-tol×18 suppl×300 |
| 10 | Confort d'été | `aspect_traversant` | 316 | 316 | — | hors-tol×16 suppl×300 |
| 11 | Confort d'été | `isolation_toiture` | 308 | 308 | 100,0 % | hors-tol×7 suppl×301 |
| 12 | Apports | `inertie_lourde` | 303 | 303 | — | suppl×301 hors-tol×2 |
| 13 | Confort d'été | `brasseur_air` | 301 | 301 | 100,0 % | suppl×300 hors-tol×1 |
| 14 | Coûts | `cout_ecs` | 278 | 156 | 73,4 % | hors-tol×277 suppl×1 |
| 15 | GES | `emission_ges_5_usages` | 265 | 124 | 20 316,2 % | hors-tol×264 suppl×1 |
| 16 | Sorties énergie finale | `conso_5_usages` | 254 | 116 | 1 016 150,4 % | hors-tol×253 suppl×1 |
| 17 | Coûts | `cout_ch` | 241 | 118 | 301,0 % | hors-tol×240 suppl×1 |
| 18 | Auxiliaires | `cout_auxiliaire_generation_ch_depensier` | 227 | 227 | 202,6 % | hors-tol×227 |
| 19 | GES | `emission_ges_ch` | 181 | 84 | 9 092,3 % | hors-tol×180 suppl×1 |
| 20 | Auxiliaires | `cout_auxiliaire_generation_ecs_depensier` | 175 | 175 | 141,7 % | hors-tol×175 |
| 21 | Génération ECS | `rendement_stockage` | 170 | 56 | 40,0 % | hors-tol×155 manquante×15 |
| 22 | GES | `emission_ges_ecs` | 146 | 73 | 8 232,9 % | hors-tol×145 suppl×1 |
| 23 | Génération chauffage | `rendement_generation` | 118 | 78 | 30,7 % | suppl×25 hors-tol×91 manquante×2 |
| 24 | Auxiliaires | `cout_total_auxiliaire` | 115 | 115 | 3 352,7 % | hors-tol×115 |
| 25 | Auxiliaires | `cout_auxiliaire_generation_ch` | 109 | 109 | 100,0 % | hors-tol×109 |
| 26 | Sorties énergie primaire | `ep_conso_5_usages` | 94 | 94 | 551,8 % | hors-tol×94 |
| 27 | Auxiliaires | `conso_auxiliaire_generation_ch` | 92 | 92 | 100,0 % | hors-tol×92 |
| 28 | Auxiliaires | `conso_auxiliaire_generation_ch_depensier` | 92 | 92 | 101,3 % | hors-tol×92 |
| 29 | Auxiliaires | `emission_ges_auxiliaire_generation_ch` | 92 | 92 | 100,0 % | hors-tol×92 |
| 30 | Auxiliaires | `emission_ges_auxiliaire_generation_ch_depensier` | 92 | 92 | 100,0 % | hors-tol×92 |
| 31 | Auxiliaires | `ep_conso_auxiliaire_generation_ch` | 92 | 92 | 100,0 % | hors-tol×92 |
| 32 | Auxiliaires | `ep_conso_auxiliaire_generation_ch_depensier` | 92 | 92 | 170,4 % | hors-tol×92 |
| 33 | Sorties énergie primaire | `ep_conso_5_usages_m2` | 86 | 86 | 551,2 % | hors-tol×86 |
| 34 | Génération chauffage | `qp0` | 85 | 79 | 205 028,2 % | hors-tol×84 suppl×1 |
| 35 | Coûts | `cout_eclairage` | 84 | 84 | 83,7 % | hors-tol×84 |
| 36 | GES | `emission_ges_ch_depensier` | 84 | 84 | 9 093,8 % | hors-tol×84 |
| 37 | Sorties énergie finale | `conso_5_usages_m2` | 81 | 81 | 257,3 % | hors-tol×81 |
| 38 | Sorties énergie primaire | `ep_conso_ch` | 80 | 80 | 100,0 % | hors-tol×80 |
| 39 | Sorties énergie primaire | `ep_conso_ch_depensier` | 80 | 80 | 100,0 % | hors-tol×80 |
| 40 | Génération chauffage | `pn` | 80 | 72 | 275,0 % | hors-tol×76 manquante×3 suppl×1 |

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
| `aspect_traversant` | 285 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `brasseur_air` | 285 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `enum_indicateur_confort_ete_id` | 285 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `inertie_lourde` | 285 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `isolation_toiture` | 285 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `protection_solaire_exterieure` | 285 | la référence écrit un bloc <confort_ete> vide, alors que son schéma y rend protection_solaire_exterieure obligatoire |
| `besoin_ecs_depensier` | 16 | la référence viole Becs_dep = Becs × 79/56 (§11.1) : 10522 publié au lieu de 47333 |
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
| `cout_auxiliaire_generation_ecs_depensier` | 152 | la référence recopie cout_auxiliaire_generation_ecs dans cout_auxiliaire_generation_ecs_depensier alors que les consommations diffèrent (7 vs 10 kWh) |
| `cout_auxiliaire_generation_ch_depensier` | 188 | la référence recopie cout_auxiliaire_generation_ch dans cout_auxiliaire_generation_ch_depensier alors que les consommations diffèrent (39 vs 87 kWh) |
| `cout_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `emission_ges_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `ep_conso_auxiliaire_distribution_ecs` | 4 | la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1) |
| `cout_total_auxiliaire` | 15 | la référence publie un total auxiliaire de 73.5, incompatible avec la somme de ses postes (176.3) |
| `ep_conso_totale_auxiliaire` | 12 | la référence publie un total auxiliaire de 402.3, incompatible avec la somme de ses postes (964.7) |
| `conso_totale_auxiliaire` | 12 | la référence publie un total auxiliaire de 211.7, incompatible avec la somme de ses postes (507.7) |
| `emission_ges_totale_auxiliaire` | 12 | la référence publie un total auxiliaire de 13.6, incompatible avec la somme de ses postes (32.5) |
| `besoin_ch_depensier` | 13 | la référence publie un besoin de chauffage dépensier inférieur au conventionnel (18788 vs 109084), malgré les consignes réglementaires de 21 °C et 19 °C |
| `conso_auxiliaire_ventilation` | 8 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `pvent_moy` | 8 | la référence publie pvent_moy = 0 alors que le même fichier déclare une consommation d'auxiliaires de ventilation non nulle dans sortie/ef_conso, ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit |
| `k` | 15 | la référence publie des k déjà multipliés par la longueur : leur somme (22.038) reproduit deperdition_pont_thermique, alors que Σ k × l vaut 110.211 — le XSD définit k en W/(m·K) |
| `conso_ecs` | 231 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `conso_ecs_depensier` | 230 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_stockage` | 159 | la référence publie 3 rendements de stockage différents pour 3 installations ECS aux données d'entrée identiques, sans qu'aucun élément du XML ne les distingue |
| `rendement_generation` | 28 | la référence déclare un stockage intégré (type 3) mais sérialise les rendements dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage |
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
