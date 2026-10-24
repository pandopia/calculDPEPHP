# Provenance des schémas

Copie du dossier `modele_donnee` du dépôt officiel de l'observatoire DPE de
l'ADEME : <https://gitlab.com/observatoire-dpe/observatoire-dpe>.
Dernière synchronisation : **2026-10-17**.

## Schémas DPE

| Fichier | En-tête de version | `enum_version_id` servis |
|---|---|---|
| DPEv1-obsolete.xsd (`DPEV1(OBSOLETE).xsd`) | V6.4.0 — 2022-04-15 | 1, 1.1 |
| DPEv2.xsd | V7.1.0 — 2022-09-28 | 2, 2.1, 2.2 |
| DPEv2.2.xsd | V8.0.4 — 2023-05-16 | 2.3 |
| DPEv2.3.xsd | V8.2.0 — 2024-05-20 | 2.4 |
| DPEv2.4.xsd | V9.1.1 — 2025-04-30 | 2.5 |
| DPEv2.6.xsd | V9.2.3 — 2026-06-09 | 2.6 |
| DPEv2.7.xsd | V10.0.0 — 2026-10-16 | **2.7** — câblé dans l'éditeur ; couverture partielle du moteur avec refus explicites des options non implémentées, cf. TASK-K63 |

Le nom des fichiers suit le **numéro de livraison**, pas la version du modèle :
l'amont publie aussi un `DPEv2.5.xsd` (V9.2.1 — 2025-10-17) qui est une
livraison intermédiaire du **même** modèle 2.6 que `DPEv2.6.xsd`, laquelle le
remplace. Il n'est donc pas copié ici.

## Schéma de l'audit réglementaire

| Fichier | En-tête de version | Version audit |
|---|---|---|
| audit_v2.6.xsd | V6.0.0 — 2026-10-16 | audit 2.6, en cohérence avec DPE 2.7 |

Le `README.md` amont le dit : `modele_commun_DPE_audit_reg.xsd` est la source
de vérité dont **les schémas DPE et audit sont tous deux générés**. L'audit
porte un `<audit><logement_collection>` de blocs `<logement>` au modèle 3CL,
chacun étiqueté par `enum_scenario_id` et `enum_etape_id`. Copié ici pour
TASK-K64 ; l'éditeur ne le sert pas encore.

## Fichiers d'accompagnement

`enums.json`, `deleted_enums.json`, `CHANGELOG.md` et `README.md` viennent du
même dossier amont et sont synchronisés à la même date.

`arrete_reseau_chaleur.json` y donne la correspondance **officielle** entre
millésime de table, date d'arrêté et plage d'application — six arrêtés de
2020 à 2025, avec le lien Légifrance de chacun. C'est la source à opposer à
la règle de `ReseauChaleurFactorResolver`, qui l'approche aujourd'hui par
`année(date) − 1` plafonnée à 2022 (cf. TASK-K65).
