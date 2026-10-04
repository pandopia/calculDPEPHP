# Campagne aléatoire 50g — 4 octobre 2026

Lot applicatif : 50 bâtiments supplémentaires, tirés sans remise ni quotas parmi
1 852 bâtiments visibles de sites `SITE_PRISEDPE_STATUT = envoye_au_client`,
après exclusion des 300 bâtiments précédents. Le libellé demandé
`SITE_PRISEDPE_VALIDE = envoye_client` ne correspond pas au champ stocké.
Graine : `39b6901ce30703ae259b3029504d944a`.

49 dossiers ont pu être collectés en lecture seule. Le bâtiment 1346464 refuse
l'accès (« Compte Reliquat non accessible ») : conservé dans le tirage, sans
remplacement ni contournement. Ses 21 logements ne sont pas testés. Le corpus
contient 897 cibles issues des données et un marqueur d'échec de collecte,
soit 898 entrées par mode (moteur seul, chaîne archivée, saisie actuelle).

## Planchers bas des exports 7.x

`UpbFinalCalculator` reconstruisait Ue depuis `surface_paroi_opaque` et
`perimetre_ue` seulement en format 6.x. Les exports 7.x décrivent aussi cette
géométrie sans répéter `surface_ue`. Extension du garde de format à 7.x, sans
changer la priorité d'une surface Ue explicite ni celle d'un Ue fourni lorsque
la géométrie dédiée manque. Aucun changement de table ni de tolérance.

Source : §3.2.2.1 p.18–19, définition de S, P et arrondi de 2S/P.
Exemple bâtiment 1427474, ADEME **2231E2780776E** : S=211,56 m², P=57,5 m,
Upb=2 ; 2S/P arrondi à 7 donne Ue=0,346 au lieu de 2.
Les neuf cibles bâtiment/logements retrouvent les lettres D/D attendues.

Effet isolé : 665 → 739 synthèses à 1 %, 840 → 849 paires de lettres exactes
sur 872 cibles calculées. 75 gains au seuil et une perte : le bâtiment
1382945, **2369E0794198V**, était proche par compensation d'écarts.
Son Upb_final passe de 1,024390 à 0,336480, conforme à la référence, mais les
ponts thermiques restent à 327,75 W/K contre 472,35. Son EP s'écarte de
276,500 à 266,534 pour une référence de 276,029 ; lettres E/D conservées.
Les 20 appartements de ce bâtiment gagnent, eux, le seuil de 1 %.
Ne pas réintroduire un Upb erroné pour compenser les autres différences.

## PAC avec chaudière en relève, dans une installation unique

`MultiGenerateurs` utilisait le rang de l'installation pour affecter 80 % du
besoin, puis moyennait les rendements et recopiait une consommation identique
sur chacun des générateurs. Or le format courant décrit souvent les deux
générateurs couplés dans une même installation sur les émetteurs de base.

Le calcul reconnaît une PAC et une chaudière par leurs types explicites,
indépendamment de leur ordre, et applique le rendement de chacune. Parts
80/20 pour §9.1.4.2 ; pour une PAC hybride, 80/20 en H1, 83/17 en H2 et 88/12
en H3 (§9.1.4.3 p.61–62). Le besoin de l'installation reste entier ; les
consommations individuelles utilisent rdim et s'additionnent. Le rendement
dépensier déjà calculé est utilisé quand disponible. Les parts sont aussi
transmises au calcul existant des auxiliaires (§15.1.1 p.97).

Garde : installation unique, exactement deux générateurs reconnus et distincts,
émetteurs/générateurs explicitement liés à la branche de base (lien 1).
Les sérialisations séparées, les branches ambiguës et la configuration 6
conservent leur traitement existant ; aucune déduction par numéro ADEME.

Exemples :
- 52041673573575 / 3646393 — **2451E3586304W** : EP 162 → 148, référence 148 ;
  GES 8 → 13, référence 13, lettre GES B → C attendue.
- 52041673577814 — **2557E3037246N** : EP 108 → 100, référence 100 ;
  GES 3 → 5, référence 5. Six logements associés bénéficient aussi du partage ; les six autres ne décrivent pas ce couple et gardent leurs écarts.
- 1448692 / 2634953 — **2655E2134741T** : EP 306 → 289, référence 289 ;
  GES 20 → 31, référence 31.

La référence bâtiment **2655E2134717V** reste incohérente dans ses échelles :
conso_ch de l'installation = 2 666,27749 kWh, rdim=5, tandis que la sortie EF
publie 66 656,93727 kWh, soit 25 fois cette consommation au lieu de 5.
Ce cas reste dans les compteurs bruts, sans exclusion ni ajustement ciblé.

## Vérifications

19 nouveaux cas unitaires : géométries anciennes, priorités Ue/surface dédiée,
absence de données, PAC classique/hybride H1/H2/H3, inversion de l'ordre,
rdim=5, rendement dépensier, générateurs indistincts et installations multiples.
Neuf nouveaux cas échouent avant leurs corrections respectives.
Suite complète : **1 687 tests, 3 666 assertions, aucun échec**, quatre
dépréciations préexistantes. Couverture non mesurée, Xdebug/PCOV absents.

A/B ADEME de contrôle : mêmes **389 cas, 123 142 valeurs**, aucun plantage,
compteurs inchangés : 8 833 hors tolérance, 145 manquantes, 1 871 supplémentaires,
29 divergences non numériques, 3 461 valeurs suspectes de référence.
Conformité brute 91,17 %. Ce corpus de contrôle ne mesure donc pas ces nouveaux gains.

Rapport détaillé, numéros ADEME, résultats des trois modes et empreintes :
`docs/dpe-campaigns/dpe-campaign-50g/` du dépôt applicatif.
Corpus privés : `tmp/dpe-campaign-50g-frozen/`, scripts `tmp/dpe-convergence-50g/`.
Les modifications restent locales au moteur ; aucune publication ni mise à
jour Composer applicative dans cette campagne.

Résultat final du lot : 665 → **752/872** synthèses à 1 %, soit 88 cibles
gagnantes et une perte ; 840 → **852/872** paires de lettres exactes, aucun
nouvel écart de lettres, aucune nouvelle erreur. Les comparaisons détaillées
strictes passent de 37 673 à **36 109** valeurs hors tolérance (−1 564), de 248
à 229 divergences non numériques. Les 2 080 valeurs manquantes et 4 504
supplémentaires restent comptées. Aucune cible ne passe tous les détails.
La chaîne archivée et la saisie actuelle ne changent pas avec ces deux correctifs.

Autre observation à instruire : **2595E1189146F**, réseau 9514C, arrêté
2024-07-05. Les sorties de référence utilisent exactement 0,097 kgCO2/kWh,
contre 0,118 calculé. Les deux nombres figurent dans les colonnes CO2 direct
et CO2 ACV de l'[arrêté du 5 juillet 2024](https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000049925781).
Qualifier le choix réglementaire et celui de la référence avant de modifier
une table complète. Les 33 cibles en écart restent comptées ; aucun coefficient
ajusté spécifiquement pour le réseau.
