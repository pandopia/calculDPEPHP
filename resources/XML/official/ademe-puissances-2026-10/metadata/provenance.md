# Petits immeubles et appartements générés à plusieurs installations

24 DPE publics ADEME sélectionnés et téléchargés le 23 octobre 2026 sous
Licence Ouverte / Open Licence v2.0. Ce sont des sorties de logiciels évalués,
pas des cas tests officiels du CSTB ni une vérité réglementaire indépendante.

Sources : [open data ADEME](https://data.ademe.fr/datasets/dpe03existant) et
[observatoire DPE-Audit](https://observatoire-dpe-audit.ademe.fr/donnees-dpe-publiques).
Le manifeste donne l'URL XML originale de chaque DPE ; `expected/` conserve
le téléchargement sans retouche, `input/` est obtenu par `OutputPurger`.

## Sélection reproductible

Quatre strates, six configurations par strate : immeuble collectif ou
appartement généré depuis l'immeuble, avant ou depuis le 1er janvier 2026.
Fenêtres de date : 2024-01-01 à 2025-12-31 et 2026-01-01 à 2026-10-23.
Filtres communs de l'API `dpe03existant/lines` :

- `nombre_appartement` entre 2 et 15 ;
- `surface_habitable_immeuble` entre 150 et 1500 m² ;
- seconde installation de chauffage avec surface strictement positive ;
- énergie principale du chauffage : gaz naturel.

Les 60 résultats les plus récents, puis triés par numéro, ont été parcourus
dans chaque strate. Un seul cas est gardé par combinaison de nombre de
logements, surface d'immeuble, surfaces des deux premières installations et
type de générateur principal. Cela réduit les quasi-doublons sans supposer
que deux bâtiments de même surface sont physiquement identiques.

Ce jeu cible les puissances et la multiplicité ; il n'est pas représentatif
du parc. Un gain de moteur se mesure en A/B sur le même jeu figé, jamais en
comparant le compteur des 646 anciens cas avec celui des 670 cas enrichis.

```bash
php bin/fetch-official-corpus --manifest=resources/XML/official/ademe-puissances-2026-10/metadata/manifest.json
```
