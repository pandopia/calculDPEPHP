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
