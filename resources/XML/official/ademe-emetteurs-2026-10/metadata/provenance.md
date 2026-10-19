# Provenance — ademe-emetteurs-2026-10

| Champ | Valeur |
|---|---|
| Organisme | ADEME |
| Jeu de données | DPE Logements existants (depuis juillet 2021) |
| Portail | <https://data.ademe.fr/datasets/dpe03existant> |
| Licence | Licence Ouverte / Open Licence v2.0 |
| Généré le | 2026-10-19T07:42:46+00:00 |
| Nombre de cas | 68 |

**Statut** : Substitut public — ce n'est PAS le jeu de cas tests officiel du CSTB, qui n'est pas diffusé publiquement (voir resources/XML/official/README.md).

**Échantillonnage** : Ciblé par branche du moteur (configuration §9.x, type de ventilation, type de générateur, refroidissement, installation solaire) × régime du coefficient EP électricité, tri déterministe par numero_dpe pour être reproductible.

## Répartition

| Périmètre visé | Régime EP élec | Cas |
|---|---|---:|
| appartement_individuel | post_2026 | 14 |
| appartement_individuel | pre_2026 | 17 |
| immeuble_collectif | post_2026 | 1 |
| maison_individuelle | post_2026 | 19 |
| maison_individuelle | pre_2026 | 17 |

Reconstituer ce jeu :

```bash
php bin/fetch-official-corpus --manifest=resources/XML/official/ademe-emetteurs-2026-10/metadata/manifest.json
```
