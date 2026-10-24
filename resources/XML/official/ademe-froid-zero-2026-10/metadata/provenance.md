# Climatiseurs déclarés avec un besoin de froid nul

Source : observatoire public ADEME, DPE logements existants, Licence Ouverte 2.0.
Les XML de référence sont conservés sans retouche ; les entrées sont leur
version purgée par `OutputPurger`. Reconstitution :

```sh
php bin/fetch-official-corpus --manifest=resources/XML/official/ademe-froid-zero-2026-10/metadata/manifest.json
```

Le 24 octobre 2026, la requête publique
`surface_climatisee:[0.001 TO *] AND besoin_refroidissement:0`, triée par
`-date_etablissement_dpe`, retourne 18 résultats. Les douze premiers ont été
sélectionnés puis téléchargés avant calcul. Les quatre DPE directs sont
intégrés : leur climatiseur doit conserver un EER même si notre besoin est
nul (§10.3 p.69–70, déclaration obligatoire de `eer` au XSD ADEME).

Les huit autres sont des appartements générés du même immeuble. Ils publient
un besoin global de froid nul et dix besoins de climatiseur positifs, avec
des consommations positives et des clés de répartition. Leurs différences ne
relèvent pas du court-circuit à besoin nul ; ils restent des sondages
documentés au manifeste, pas des étalons ajoutés ni des exclusions du corpus.

Les DPE directs publient un epsilon de 0,00001 pour les besoins et consommations
nuls de leur climatiseur. Cet epsilon n'est pas recopié dans le moteur : le
zéro physique reste zéro, le profil strict reste inchangé et l'écart brut
demeure visible. Aucun autre résultat de la référence n'est pris en entrée.
