# Hybrides ECS bois

Huit DPE publics ADEME téléchargés le 24 octobre 2026 sous Licence Ouverte
2.0, pour couvrir les catégories ECS 124–131 absentes du corpus antérieur.
Ce sondage ciblé n'est pas représentatif du parc ni un jeu officiel CSTB.

Source : [jeu public ADEME](https://data.ademe.fr/datasets/dpe03existant).
Les URL XML exactes et les libellés techniques sont dans le manifeste.
Les références sont conservées sans retouche, les entrées sont purgées par
`OutputPurger`. Le manifeste ne contient pas de données d'identité.

Sélection sur `type_generateur_n1_ecs_n1`, libellé exact « pompe à chaleur
hybride : partie chaudière Chaudière bois … » : granulés après 2019 (18
résultats), plaquette après 2019 (2), bûche 2013–2017 (4). Tri par date
d'établissement décroissante. Quatre premiers granulés (deux de 2026, deux
de 2025), puis deux premières plaquettes et deux premières bûches.

Objet de l'enquête : §15.1 p.97–98, auxiliaires ECS de la partie chaudière.
Les coefficients G/H dépendent de la famille et de son assistance par
ventilateur, pas du fait que la chaudière participe à une hybride.

Comparer tout changement sur les mêmes 694 cas (686 précédents + ces huit
ajouts), jamais les totaux de deux corpus différents.

```bash
php bin/fetch-official-corpus --manifest=resources/XML/official/ademe-ecs-bois-hybride-2026-10/metadata/manifest.json
```
