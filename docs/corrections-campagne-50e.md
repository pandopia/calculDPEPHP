# Corrections de la campagne de 50 bâtiments — lot 50e

CORRECTIONS DIRECTES DANS calculDPEPHP

1. Photovoltaïque : lecture de l'orientation et de l'inclinaison physiques
   au lieu d'une indexation tv_* supposée ; table exhaustive des 20 combinaisons.
   §16.2 p.103-105. Le coefficient électrique de la déduction EP est réaligné
   sur celui du reste du moteur : 2,3 avant 2026, 1,9 depuis 2026.
   Bâtiment 1533572, logement 3814129, ADEME 2524E2077820G : production PV
   700,639 → 653,023 kWh/an, référence 653,023 ; autoconsommation
   420,645 → 403,002, référence 403,033. La classe A reste identique.
   Une orientation/inclinaison manquante sur un panneau de surface positive
   est désormais signalée ; l'identifiant externe ne suffit pas à la déduire.

2. Cascade de deux chaudières sans priorité : le besoin est réparti selon
   les puissances, chaque part utilise son rendement et les consommations
   s'additionnent. Les auxiliaires utilisent la même part du besoin.
   §13.2.1.3.2 p.79-80, §9.1.2 p.59 et §15.1.1 p.97-98 ; XSD : les deux
   chaudières sans priorité sont toutes deux déclarées principales (priorité 1).
   Bâtiment 410653, ADEME 2659E1565595R ; logement 410721, 2659E1565661F :
   chaque chaudière recevait environ 802 297 kWh/an. Après correction,
   399 854,58 et 402 450,70 contre 399 854,45 et 402 450,58 en référence.
   Auxiliaires logement : 12,729 → 6,365 kWh/an, référence 6,365.
   La correction couvre exactement deux chaudières déclarées sans priorité,
   de puissances et rendements positifs ; les autres configurations gardent
   leur traitement existant. Aucune règle propre à un numéro ADEME.

3. Circulateur d'un plancher chauffant : le maximum initialisé au coefficient
   radiateur empêchait d'utiliser Fcot=0,156. Le maximum part désormais de zéro
   et porte uniquement sur les émetteurs réellement présents. §15.2.1 p.98-99.
   Bâtiment 416974, ADEME 2659E0939576A ; logement 417079, 2659E0939580E :
   auxiliaires 104,989 → 64,473 kWh/an, référence 64,488 ; EP 153 → 151,
   référence 151. Planchers, plafonds, radiateurs et assemblages testés.

RÉSULTATS AVANT / APRÈS
Convergence de synthèse : EP et GES à 1 % maximum, deux lettres identiques.
Ce seuil interne ne constitue pas une certification réglementaire et ne
valide pas tous les intermédiaires.

Mode ; Calculées ; Lettres identiques avant/après ; Seuil 1 % avant/après ; Erreurs
Moteur sur XML certifié ; 655/689 ; 655 → 655 ; 617 → 651 ; 34
Chaîne export archivé ; 631/689 ; 498 → 498 ; 160 → 216 ; 58
Saisie actuelle ; 632/689 ; 497 → 497 ; 157 → 213 ; 57

Aucune nouvelle erreur de calcul ni régression de synthèse en moteur seul.
Les gains des trois modes ne sont pas additionnés : ils concernent souvent
les mêmes logements. La saisie actuelle n'est pas une entrée historique équivalente.

RÉGRESSIONS CONSERVÉES DANS LE BILAN
Six logements du bâtiment 416974 sortent du seuil de synthèse à 1 % dans les
modes chaîne et actuel (12 lignes, six logements distincts). L'EP se rapproche
136 → 135, mais le GES calculé passe de 19 à 18 pour une référence à 19.
Les lettres restent C/C. Sur 417080, le total GES chaîne passe de 1 674,329 à
1 670,104 kg/an, référence 1 676,165 ; le moteur sur XML certifié donne
1 675,401. L'export a aussi des déperditions de 16 479,516 W/K contre
16 050,241 dans la référence. L'ancienne surestimation du circulateur
compensait en partie ces autres écarts ; la correction physique est conservée.
  Logement 417080 ; ADEME 2659E0939582G
  Logement 417086 ; ADEME 2659E0939589N
  Logement 417091 ; ADEME 2659E0939594S
  Logement 417007 ; ADEME 2659E0939668O
  Logement 417011 ; ADEME 2659E0939673T
  Logement 417017 ; ADEME 2659E0939679Z

QUATRE SYNTHÈSES RESTENT AU-DELÀ DE 1 % EN MOTEUR SEUL
  398594/building ; ADEME 2459E2493211D ; EP réf./moteur 143.241023885302/136 ; GES 27.68/26
  416974/416980 ; ADEME 2659E0939753V ; EP réf./moteur 133/133 ; GES 19/18
  419920/building ; ADEME 2559E3701383I ; EP réf./moteur 165/167 ; GES 18/18
  419920/419922 ; ADEME 2559E3701429C ; EP réf./moteur 134/152 ; GES 16/16
Le 398594 publie Umur0=2 malgré le doublage connu déclaré (la méthode donne
1/(1/2+0,21)=1,40845). Sa période d'émetteur manque également. Le 416980
reste au voisinage du seuil de troncature GES. Le 419920/419922 publie le
besoin ECS de l'immeuble avec des pertes correspondant à un seul ballon
pour 22 logements ; la référence doit être qualifiée avant d'en copier le
rendement. Ces écarts ne sont ni masqués ni exclus du comptage.

LIMITES ET COUVERTURE
32 références complètes absentes et 2 DPE tertiaires hors méthode logement
expliquent les 34 cibles non calculables en moteur seul. En chaîne :
45 sources archivées absentes et 13 cibles exigeant un éclatement.
En actuel : 41 liens de saisie manquants, 3 associations absentes/ambiguës,
13 cibles exigeant un éclatement. Aucun accroissement de ces erreurs.
Sélection : 24 maisons, 4 bâtiments de deux logements, 12 individuels
homogènes, 10 collectifs sans IFC. Quotas non atteints : appartements
isolés, maisons regroupées, collectifs avec IFC et hétérogènes. Le vivier
et les critères sont les mêmes que pour le lot précédent, après exclusion
supplémentaire des 50 bâtiments du lot 50d ; la couverture n'est pas universelle.
À 0,1 % sur tous les détails, aucune cible ne valide intégralement le rapport.
Les références ADEME sont attachées au diagnostic exact, sans substitution
par un diagnostic plus récent : 657 numéros par mode, 32 absents.

VALIDATION COMPLÉMENTAIRE
1 665 tests, 3 557 assertions, zéro échec, 4 dépréciations préexistantes.
Les tests ajoutés échouent avant correction puis passent après correction.
116 tests ciblés, 335 assertions, zéro dépréciation sur les fichiers concernés.
Syntaxe PHP et git diff --check valides. Couverture non mesurée : Xdebug/PCOV
indisponibles dans ce runtime ; aucun taux de couverture n'est revendiqué.

Corpus complémentaire ADEME : mêmes 389 cas, 123 142 valeurs, zéro crash.
Hors tolérance : 8 990 → 8 851 (-139). Exactes : 93 313 → 93 323.
Dans la tolérance : 18 794 → 18 923. Valeurs manquantes 145, supplémentaires
1 871, écarts textuels 29 et références suspectes 3 461 restent inchangés.
Indicateur global : 91,04 % → 91,15 %. Huit cas améliorés, aucun cas dégradé.
Aucune modification du comparateur, de ses tolérances, des exclusions ou de
la qualification des références. source-hashes.json permet d'isoler les
cinq fichiers de calcul/tables modifiés depuis df176be7.

L'application garde le paquet Composer v0.1.28 : les rejeux chargent directement
le dépôt ../../calculDPEPHP. Les changements seront visibles dans l'application
après publication et mise à jour de la dépendance.

