# calculDPE

Librairie et CLI PHP pour calculer un DPE 3CL-2021 a partir d'un XML ADEME.

## Installation

```bash
composer install
composer dump-autoload
```

## Utilisation en librairie

```bash
composer require pandopia/calcul-dpe-php
```

```php
<?php

use CalculDpePHP\CalculDpePHP;

$xml = file_get_contents('dpe.xml');

$calculatedXml = CalculDpePHP::calculate($xml);

$energy = CalculDpePHP::calculate($xml, ['energieOnly' => true]);
// $energy->epConso5UsagesM2
// $energy->classeBilanDpe
// $energy->emissionGes5UsagesM2
// $energy->classeEmissionGes
```

## Utilisation en CLI

```bash
php bin/calcul-dpe /chemin/vers/input.xml [/chemin/vers/output.xml]
```

## Outil de preparation des fixtures XML

```bash
php bin/process-xml /chemin/vers/fichier.xml
```

Le script :

- sauvegarde l'original dans `resources/XML/verif`
- sauvegarde une copie nettoyee dans `resources/XML/input` en supprimant `<donnee_intermediaire>` et `<sortie>`

## Rapport de conformité

Compare la sortie du moteur aux jeux de tests, balise par balise, et ecrit
`reports/official-tests.json` et `reports/official-tests.md` :

```bash
php bin/official-test-report
```

Options utiles :

```bash
php bin/official-test-report --list-corpora
php bin/official-test-report --tolerance=reglementaire
php bin/official-test-report --filter=2657E --famille="Génération chauffage" --top=30
```

Profils de tolerance : `strict` (0,1 %, defaut), `reglementaire` (1 %),
`repo` (reprend `tests/tolerances.php`).

## Jeux de tests

Les autotests et cas tests de la procedure officielle d'evaluation CSTB ne sont
pas diffuses publiquement. La conformite est mesuree contre les DPE opposables
de l'observatoire ADEME. Provenance, licence et limites :
`resources/XML/official/README.md`.

Construire un jeu stratifie (4 perimetres CSTB x 2 regimes du coefficient EP
electricite) :

```bash
php bin/fetch-official-corpus --build=ademe-2026-09 --per-stratum=15
```
