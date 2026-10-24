# Provenance — ECS bois autonome

Sélection publique ADEME du 24 octobre 2026, Licence Ouverte 2.0.
Ces DPE opposables ne sont pas les cas officiels CSTB. Les XML `expected/`
restent strictement ceux téléchargés ; `input/` ne fait que purger les sorties.

Le manifeste conserve les champs de sélection, dates, périmètres et liens XML.
Sur l’API `dpe03existant/lines`, avec tri `-date_etablissement_dpe` et
`usage_generateur_n1_ecs_n1:"ecs"` :

- huit premiers `Chaudière bois granulés après 2019` sur 154 résultats ;
- quatre premiers `Chaudière bois bûche après 2019` sur 20 résultats ;
- quatre premiers granulés antérieurs à 2026 sur 95 résultats.

La même recherche charbon après 2019 ne donne aucun résultat. La sélection
précède le calcul, sans choix sur les écarts. « ECS seule » est l’usage du
générateur publié, pas la garantie d’absence d’un chauffage bois ailleurs.

Reconstitution :

```bash
php bin/fetch-official-corpus --manifest=resources/XML/official/ademe-ecs-autonome-2026-10/metadata/manifest.json
```
