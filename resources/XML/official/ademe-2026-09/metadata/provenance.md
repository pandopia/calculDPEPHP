# Provenance — ademe-2026-09

| Champ | Valeur |
|---|---|
| Organisme | ADEME |
| Jeu de données | DPE Logements existants (depuis juillet 2021) |
| Portail | <https://data.ademe.fr/datasets/dpe03existant> |
| Licence | Licence Ouverte / Open Licence v2.0 |
| Généré le | 2026-09-01T14:26:06+00:00 |
| Nombre de cas | 120 |

**Statut** : Substitut public — ce n'est PAS le jeu de cas tests officiel du CSTB, qui n'est pas diffusé publiquement (voir resources/XML/official/README.md).

**Échantillonnage** : Stratifié par périmètre CSTB §1.1 × régime du coefficient EP électricité, tri déterministe par numero_dpe pour être reproductible.

## Répartition

| Périmètre visé | Régime EP élec | Cas |
|---|---|---:|
| appartement_individuel | post_2026 | 15 |
| appartement_individuel | pre_2026 | 15 |
| appartement_issu_immeuble | post_2026 | 15 |
| appartement_issu_immeuble | pre_2026 | 15 |
| immeuble_collectif | post_2026 | 15 |
| immeuble_collectif | pre_2026 | 15 |
| maison_individuelle | post_2026 | 15 |
| maison_individuelle | pre_2026 | 15 |

Reconstituer ce jeu :

```bash
php bin/fetch-official-corpus --manifest=resources/XML/official/ademe-2026-09/metadata/manifest.json
```
