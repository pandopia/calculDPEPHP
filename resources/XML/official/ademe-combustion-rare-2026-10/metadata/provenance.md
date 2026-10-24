# Générateurs à combustion rares

Source : observatoire public ADEME, Licence Ouverte 2.0. Le 24 octobre 2026,
les douze résultats les plus récents de chacune des trois requêtes du manifeste
ont été sélectionnés avant calcul : air chaud, poêles bouilleurs, radiateurs gaz.
Les 36 références XML sont conservées sans retouche ; les entrées sont leur
version purgée par OutputPurger. Aucun cas n'est écarté selon son score.

Reconstitution :

```sh
php bin/fetch-official-corpus --manifest=resources/XML/official/ademe-combustion-rare-2026-10/metadata/manifest.json
```

Ce jeu complète les tests synthétiques sans identifiant de table ; tous ses
générateurs à combustion portent déjà un identifiant. Il ne démontre donc pas
un gain de présence des caractéristiques sans TV. Sur ces mêmes 36 cas,
la correction des bouilleurs résout trois écarts numériques et l'omission
des zéros inapplicables des radiateurs supprime 32 balises supplémentaires.
Aucune balise manquante n'y est rétablie ; aucun cas ne se dégrade en décompte
brut. Les budgets de ces nouveaux cas sont initialisés, ceux des anciens cas
ne sont jamais relevés. Les manques de représentation demeurent comptés.
