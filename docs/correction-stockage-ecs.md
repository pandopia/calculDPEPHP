# Stockage ECS d’un groupe individuel unique

Validation du 4 octobre 2026, sur le dépôt moteur à partir de `8b7a875`.

## Correction

La règle déjà présente pour un groupe ECS individuel unique est désormais
appliquée **avant** la répartition par typologie. Renseigner une typologie
identique sur les logements visités ne doit pas réduire une deuxième fois
le volume du ballon de l’appartement moyen. L’ancien cas particulier de
visite exhaustive devient inutile. Les calculs des groupes multiples restent
inchangés.

Traçabilité : §11.6.2–3, pages 74–75, pour Qgw et Rs ; §17.1.2–17.1.3.2,
pages 107–109, pour l’appartement moyen et la multiplicité des installations.
Le XSD définit `volume_stockage` comme le volume associé au générateur ECS.

La fixture thermique minimale ne contient aucune identité ou adresse. Six
variantes vérifient les pertes et le rendement avec une, deux ou trois visites,
des surfaces différentes et des typologies identiques, différentes ou absentes.
Quatre échouaient avant correction ; toutes passent après.

## Même campagne de 50 bâtiments

Les trois modes ont été rejoués avant/après, hors ligne, sur les mêmes 150
instantanés SHA-256 : 1 062 cibles par mode, soit 3 186 résultats par passage.
Le moteur est chargé directement depuis ce dépôt, sans modifier Composer ni
la copie installée dans l’application. Les différences entre la base locale
v0.1.27 et le paquet installé v0.1.28 concernent la constante de version et le PDF,
pas les calculateurs évalués ici.

| ADEME | Bâtiment / logement | EP référence | Avant | Après | Classe DPE avant → après |
|---|---|---:|---:|---:|---|
| 2582E3494670S | 1538432 / 3847390 | 214 | 206 | 214 | D → D |
| 2582E3494672U | 1538433 / 3847391 | 183 | 175 | 183 | C → D |
| 2582E3494678A | 1538434 / 3847392 | 188 | 179 | 188 | C → D |

Le ballon de 200 L était ramené à 108,688772 L par la branche des typologies.
Les pertes retrouvent 741,06 kWh/an et Rs = 0,645298613, au lieu de 0,769991955.

- Moteur seul : 1 012 calculs possibles ; lettres DPE **et** GES identiques
  à la référence : **1 010 → 1 012** ; synthèses à 1 % : **934 → 937**.
- Trois cibles modifiées ; aucune régression de synthèse sur les 50 bâtiments.
- Chaîne archivée : 926 calculs, 734 mêmes lettres, 66 synthèses à 1 % : inchangé.
- Données actuelles : 926 calculs, 729 mêmes lettres, 61 synthèses à 1 % : inchangé.
- Sources manquantes et blocages conservés : 50 en moteur seul, 136 par mode chaîne.

## Référence incohérente conservée dans le corpus

Le cas `2513E1578589Q` publie un ballon individuel de 100 L catégorie B pour
18 logements. §11.6.2 donne Qgw = 434,97 kWh/an par ballon. Ses consommations
conventionnelle et dépensière impliquent pourtant chacune **24,165 kWh/an**,
soit précisément Qgw / 18, avec Becs déjà ramené à l’appartement moyen.
La division par le nombre de logements est donc appliquée une seconde fois
aux pertes du ballon individuel.

`ReferenceDefects` identifie ce défaut depuis les seules données de référence :
un groupe individuel couvrant tout l’immeuble, un ballon électrique, absence
de solaire et de virtualisation, et division Qgw/N retrouvée dans les deux
scénarios. Aucun numéro de DPE n’intervient dans la règle. Les données
incomplètes, configurations multiples, scénarios non concordants ou simples
désaccords avec le moteur ne déclenchent pas cette qualification.

Les consommations ECS, le bilan énergétique et les classes qui en dépendent
sont signalés comme suspects. **Les écarts restent comptés dans le bilan brut**.
Le cas n’est pas exclu ; aucune tolérance et aucun budget de test ne sont relevés.
Un test vérifie explicitement qu’un écart de classe reste `string_mismatch`.

## Vérification complète

A/B sur les mêmes **389 cas**, en gardant le comparateur identique des deux
côtés et en ne changeant que `StockageCalculator` :

| Mesure brute | Avant | Après |
|---|---:|---:|
| Valeurs comparées | 123 142 | 123 142 |
| Hors tolérance numérique | 8 990 | 8 990 |
| Valeurs manquantes | 145 | 145 |
| Valeurs supplémentaires | 1 871 | 1 871 |
| Écarts de texte/classe | 28 | 29 |
| Écarts non imputables à une référence défectueuse | 7 574 | 7 574 |

L’écart brut supplémentaire est la classe C → D de `2513E1578589Q`, dont la
sous-estimation ECS est démontrée ci-dessus. Aucun autre cas ne change de
compteur. Les 389 références sont conservées. Les empreintes des autres
fichiers source sont vérifiées identiques pendant l’A/B.

Le nouveau détecteur a aussi été évalué sur les **3 090 XML de référence**
disponibles dans les trois modes de la campagne : aucun déclenchement. Il ne
modifie donc ni les annotations ni les gains mesurés sur ces 50 bâtiments.

Suite complète : **1 642 tests, 3 466 assertions**, aucun échec ; quatre
dépréciations préexistantes. PHP lint et `git diff --check` passent.
La couverture n’est pas remesurée : Xdebug/PCOV indisponibles dans ce runtime.

Les rapports détaillés et les empreintes A/B sont conservés dans le dépôt
applicatif sous `tmp/dpe-storage-fix/` ; le bilan partageable se trouve dans
`docs/dpe-campaigns/dpe-campaign-50d/storage-fix/`. Les sources XML nominatives
restent dans le corpus privé `tmp/dpe-campaign-50d-frozen/`.

Les écarts de chaîne liés aux tables de chaudières absentes, à l’identifiant
du réseau de chaleur ECS absent et à l’enveloppe restent détaillés dans le
rapport initial. Ils ne sont pas résolus par cette correction de stockage.
