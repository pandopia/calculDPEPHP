# calculDPE

Script PHP qui :

- prend un fichier XML en entree ;
- sauvegarde l'original dans `resources/XML/verif` ;
- sauvegarde une copie du meme fichier dans `resources/XML/input` en supprimant les balises `<donnee_intermediaire>` et `<sortie>`.

## Installation

```bash
composer dump-autoload
chmod +x bin/process-xml
```

## Utilisation

```bash
php bin/process-xml /chemin/vers/fichier.xml
```

Exemple avec le fichier fourni :

```bash
php bin/process-xml resources/XML/verif/diag2356736.xml
```

Le script ecrit :

- l'original dans `resources/XML/verif/<nom-du-fichier>.xml`
- la version nettoyee dans `resources/XML/input/<nom-du-fichier>.xml`
