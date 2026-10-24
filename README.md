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

Le modèle DPE 2.7 est pris en charge **partiellement**. Les technologies et
options dont le calcul n'est pas encore implémenté (MTA, certaines configurations
de régulation prédictive, récupération sur eaux grises, commande intelligente,
couverture hybride saisie et aération par fenêtres en 2.7), ainsi que les systèmes
de refroidissement autres que le froid électrique du réseau (gaz, réseau de froid urbain,
autre énergie explicitement renseignée), lèvent
`UnsupportedDpeModelException` avant toute purge du XML. Un fichier accepté
ne constitue donc pas une certification de conformité 2.7 complète ; les
travaux restants sont décrits dans `TASKS.md` (K63).

Les familles thermodynamiques génériques sans forfait exigent une performance
propre justifiée. En particulier, l'ECS thermodynamique gaz (type 83) exige un
COP en méthode 6 ; elle ne peut pas être assimilée à une chaudière.

## Rapport PDF du DPE

Genere le rapport PDF d'un DPE a partir de son XML ADEME valide (avec ses
resultats `<sortie>`), sur le fond du modele officiel du ministere en vigueur a sa date
d'etablissement : editions du 1er janvier 2023, du 1er juillet 2024 et du
1er septembre 2025 (seule cette derniere porte le QR code), pour maison,
appartement, appartement a partir de l'immeuble et immeuble. Les DPE etablis
avant 2023 levent `UnsupportedTemplateException`.

```php
<?php

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Dto\DiagnostiqueurRapport;
use CalculDpePHP\Dto\DonneesRapportPdf;

$pdf = CalculDpePHP::genererPdf(file_get_contents('dpe.xml'), new DonneesRapportPdf(
    photo: DonneesRapportPdf::image('photo.jpg'),        // JPEG ou PNG
    logo: DonneesRapportPdf::image('logo.png'),
    signature: DonneesRapportPdf::image('signature.png'),
    numeroDpe: '2674E1068548B',                          // si absent du XML
    nomProprietaire: 'Nom du proprietaire',              // remplace le XML s'il est renseigne
    adresseProprietaire: '1 rue X 75000 Paris',
    diagnostiqueur: new DiagnostiqueurRapport(
        entreprise: 'Entreprise', adresse: '37 rue Y 75008 PARIS', nom: 'Prenom Nom',
        telephone: '01 00 00 00 00', email: 'contact@exemple.fr',
        numeroCertification: 'C0000', organismeCertification: 'Organisme',
        adresseOrganismeCertification: 'Adresse de l\'organisme',
    ),
    explicationsPersonnalisees: 'Ecarts consommations estimees / reelles (annexe)',
    commentaires: 'Commentaires sous les travaux (p.5)',
));
file_put_contents('dpe.pdf', $pdf);
```

Tous les champs de `DonneesRapportPdf` sont facultatifs. Les donnees du XML
sont utilisees quand un champ n'est pas renseigne ; les XML de l'observatoire
ADEME n'ont pas les donnees personnelles (proprietaire, diagnostiqueur).

**Sans numero ADEME** (ni dans le XML, ni dans le DTO : calcul prealable,
simulation), la page 1 porte en diagonale « DOCUMENT NON OFFICIEL – DPE non
enregistre aupres de l'ADEME – sans valeur reglementaire », le numero est
« non attribue » et le QR code est remplace par une mention.

En CLI :

```bash
php bin/dpe-pdf dpe.xml dpe.pdf --photo=photo.jpg --logo=logo.png --signature=sig.png --numero-dpe=2674E1068548B
```

`php bin/dpe-pdf` sans argument liste toutes les options (proprietaire,
diagnostiqueur, organisme, explications, commentaires, lien du guide).
Preparation des modeles officiels et choix techniques :
`resources/pdf/README.md`.

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

### Audit des blocs calculés au XSD exact

```bash
php bin/check-output-schema --filter=2657E
php bin/check-output-schema --reference --limit=10
php bin/check-output-schema --input=output.xml
```

Ce contrôle indépendant choisit le schéma correspondant à `enum_version_id`
(les numéros de modèle et de livraison diffèrent). Il vérifie les enfants
obligatoires, types, bornes et `xsi:nil` des blocs calculés présents, même si
la référence omet aussi une balise. Les fratries restent indexées.
Il ne valide ni les entrées ni la complétude du document entier, ne remplit
aucune valeur et ne modifie pas le juge de conformité ou ses tolérances.
JSON sur stdout ; codes de sortie 0 (aucun bloc invalide), 1 (blocs invalides),
2 (lecture, calcul, schéma ou sélection en erreur). Les références peuvent
elles-mêmes être invalides : les auditer séparément du résultat du moteur.

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
