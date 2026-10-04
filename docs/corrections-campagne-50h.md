# Campagne 50h — DPE établis en 2026

50 bâtiments supplémentaires, sans recoupement avec les 350 précédents.
Statut réel vérifié : `SITE_PRISEDPE_STATUT=envoye_au_client`.
Les 49 bâtiments éligibles de sites créés en 2026 sont tous retenus ; le
complément est tiré au hasard parmi 111 bâtiments de sites plus anciens avec
un diagnostic de 2026. Les 872 XML certifiés calculables sont datés de 2026.
19 cibles restent sans référence complète. Les sources sont figées avant/après.

## Corrections

### Ue déjà calculé fourni dans les entrées

`UpbFinalCalculator` conserve maintenant `ue` lorsque `calcul_ue=1`, même
si `surface_ue` et `perimetre_ue` sont présents. Sans `ue`, le calcul par les
tables reste inchangé, y compris le repli de géométrie des formats 6.x/7.x.

Source : XSD ADEME, `plancher_bas/donnee_entree/calcul_ue` (« est ce que le
plancher bas est passé par le calcul du Ue ») et `ue`, coefficient remplaçant
Upb. §3.2.2.1 p.18–19 précise la portée du Ue à l'immeuble entier. La géométrie
documentée n'annule pas la donnée fournie. L'implémentation refactorisée
Open3CL confirme cette priorité (`deperdition-plancher-bas.service.js`,
commit `521fa64d977065dc331c226249955844e87cabc6`).

Exemples : **2660E2530818R** (1600930/4695905) et **2660E2530828B**
(1600932/4695914). Ue passe de 0,148333333 à 0,15333333, conforme au XML ;
GES passe de 16 à 17, conforme à la référence, EP 86 et lettres C/C inchangés.

### Trois branches de chauffage dans une seule installation

`AppointInsertElecSdb` réutilise `computeAndWriteParLien` pour une installation
unique portant les trois liens XSD : principal=1, poêle d'appoint=2,
électrique de salle de bains=3. §9.5 p.63–64 affecte respectivement
67,5 %, 22,5 % et 10 % du besoin, avec intermittence et rendements propres.
La sérialisation en plusieurs installations ou avec liens incomplets conserve
son traitement. Les extensions restantes de K34 sont précisées dans TASKS.md.

Quatre logements de 1549866 retrouvent B/A au lieu de A/A :

| ADEME | EP avant | EP après = référence |
|---|---:|---:|
| 2624E1215905S | 63 | 72 |
| 2624E1215816H | 64 | 74 |
| 2624E1215722R | 63 | 72 |
| 2624E1215661I | 64 | 74 |

## Résultats et limites

- 843/872 références à 1 % avec mêmes lettres (96,67 %), contre 837.
- 871/872 paires de lettres identiques, contre 867. Six gains de synthèse,
  aucune perte sur ce lot. Il reste 29 écarts de synthèse dans six bâtiments.
- Détails stricts à 0,1 % : 16 933 → 15 359 valeurs hors tolérance ;
  48 → 44 divergences non numériques. 278 manquantes et 4 729 supplémentaires
  restent comptées. Aucun dossier n'est entièrement conforme sur tous les détails.
- Les 389 fichiers du corpus ADEME de contrôle restent identiques :
  123 142 valeurs, aucun plantage, 8 833 → 8 739 hors tolérance,
  29 → 27 divergences non numériques, conformité brute 91,17 → 91,24 %.
  Manquantes=145 et supplémentaires=1 871 inchangées.

**Deux tests E2E régressent malgré le gain global.** Les budgets restent
inchangés : ils continuent à signaler ces échecs, sans nouvelle exclusion.

| ADEME | Écarts supplémentaires | Cause identifiée |
|---|---:|---|
| 2659E0139658Y | +15 | Ue et déperdition du plancher deviennent conformes ; Pn reste à 140 kW contre 135 kW, avant comme après. |
| 2682E0137948B | +17 | Les deux Ue et la déperdition du plancher deviennent conformes ; Pn reste à 135 kW contre 125 kW. |

Le besoin chauffage devient lui aussi conforme. L'ancien plancher sous-estimé
compensait partiellement le désaccord de chaudière déjà connu (K31).
Plusieurs consommations dérivées dépassent maintenant le seuil de 0,1 %.
La correction d'entrée est conservée et la régression reste visible.

- 915 tests unitaires passent, 2 163 assertions ; dix nouveaux cas dont six
  échouaient avant correction.
- Suite complète : 1 697 tests, 3 724 assertions, **deux échecs E2E**,
  quatre dépréciations préexistantes. La suite complète n'est pas verte.
- Couverture non mesurée : Xdebug et PCOV absents.

Le seul écart de lettre du lot restant est **2675E2055841X**
(1597658/4660664) : EP 155/154, GES 33/32, D/D contre C/C.
Pn=40/45 kW, à qualifier avec K31. Le réseau 3802C de **2638E1939755Q**
(1563791/4012840) présente un désaccord entre date d'arrêté XML et facteur
utilisé par la référence ; voir K47. Aucun facteur ni arrondi ajusté aux seuls
résultats observés.

Rapport applicatif avec tous les numéros ADEME :
`docs/dpe-campaigns/dpe-campaign-50h/rapport.txt` dans le dépôt Pandopia.
Baseline moteur : `5c842c82e2de7f08aca25e1eecdc254b539fd6b1`.
Rejeux locaux, aucune donnée métier modifiée, aucun déploiement.
