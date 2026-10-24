# Photovoltaïque collectif et appartements générés

16 DPE publics ADEME téléchargés le 24 octobre 2026, sous Licence Ouverte
2.0 : quatre immeubles et quatre appartements générés dans chacun des régimes
pré-2026 (2024–2025) et post-2026. Ce jeu ciblé n'est ni représentatif du parc
ni un jeu officiel du CSTB. Les références sont des sorties d'autres logiciels,
pas une vérité réglementaire indépendante.

Sources : [jeu ADEME](https://data.ademe.fr/datasets/dpe03existant) et
[observatoire DPE-Audit](https://observatoire-dpe-audit.ademe.fr/donnees-dpe-publiques).
Les URL XML exactes sont conservées dans le manifeste. Les fichiers `expected/`
sont les téléchargements sans retouche ; `input/` est obtenu par `OutputPurger`.

## Sélection figée

API publique `dpe03existant/lines`, champ
`production_electricite_pv_kwhep_par_an > 0`, puis périmètre immeuble collectif
ou appartement généré. Pour chaque périmètre : 30 résultats récents pour le
post-2026, 100 résultats datés de 2024–2025 pour le pré-2026. Tri local par date
décroissante puis numéro croissant ; quatre premières géométries distinctes
selon nombre de logements, surface d'immeuble et surface de capteurs.

Attention : le champ de sélection open data est en **énergie primaire** ; il
ne sert pas à calibrer `production_pv`, dont la référence est le XML brut.
L'enquête porte sur §16.2 p.103–106 : consommation électrique au périmètre
calculé et prorata surfacique des capteurs collectifs vers l'appartement.

Tout gain doit être mesuré en A/B sur les mêmes 686 cas (670 précédents et ces
16 ajouts), jamais par comparaison des compteurs de corpus différents.

```bash
php bin/fetch-official-corpus --manifest=resources/XML/official/ademe-pv-collectif-2026-10/metadata/manifest.json
```
