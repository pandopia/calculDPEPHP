---
section_id: "18.4"
title: "Facteur de couverture solaire"
spec_pages: [143]
xml_outputs: ["fch", "fecs"]
tables: ["tv_facteur_couverture_solaire_id"]
depends_on: ["18"]
status: "digitalized"
---

# §18.4 — Facteur de couverture solaire

> Source : resources/spec.pdf, p.143, annexe 1 d'octobre 2021.
> Les 48 valeurs sont relues visuellement sur le PDF le 24/10/2026.
> Transcription mise en forme ; relecture indépendante encore à effectuer.

Les facteurs de couverture solaire peuvent être saisis directement lorsqu'ils
sont connus et justifiés. Sinon, le tableau distingue les maisons des immeubles
collectifs et, pour l'ECS solaire seule, l'ancienneté de l'installation.

## Tableau complet (p.143)

| Zone climatique | Maison : chauffage solaire seul ou combiné, $F_{\mathrm{ch}}$ | Maison : ECS solaire seule > 5 ans, $F_{\mathrm{ecs}}$ | Maison : ECS solaire seule ≤ 5 ans, $F_{\mathrm{ecs}}$ | Maison : chauffage + ECS solaire, $F_{\mathrm{ecs}}$ | Immeuble collectif : ECS solaire seule > 5 ans, $F_{\mathrm{ecs}}$ | Immeuble collectif : ECS solaire seule ≤ 5 ans, $F_{\mathrm{ecs}}$ |
|---|---:|---:|---:|---:|---:|---:|
| H1a | 0,25 | 0,49 | 0,63 | 0,87 | 0,26 | 0,38 |
| H1b | 0,22 | 0,50 | 0,64 | 0,88 | 0,27 | 0,40 |
| H1c | 0,28 | 0,53 | 0,68 | 0,90 | 0,31 | 0,45 |
| H2a | 0,34 | 0,51 | 0,66 | 0,90 | 0,28 | 0,41 |
| H2b | 0,33 | 0,54 | 0,69 | 0,91 | 0,32 | 0,46 |
| H2c | 0,38 | 0,58 | 0,74 | 0,95 | 0,35 | 0,50 |
| H2d | 0,39 | 0,61 | 0,77 | 0,96 | 0,38 | 0,56 |
| H3 | 0,52 | 0,64 | 0,80 | 0,98 | 0,40 | 0,58 |

## Repères d'implémentation — hors transcription réglementaire

- $F_{\mathrm{ch}}$ intervient dans les installations de chauffage solaire
  (§9.2 et §9.6) ; $F_{\mathrm{ecs}}$ intervient dans le calcul solaire ECS
  (§11.3 et §14.4).
- Le PDF ne donne pas de colonne « chauffage + ECS solaire » pour l'immeuble
  collectif. Ne pas en inventer une dans cette transcription.
- Le modèle 2.7 remplace les modalités ECS seules selon l'ancienneté par la
  modalité 5. Ses 16 valeurs ADEME datées figurent séparément dans
  resources/tables/ecs/tv_facteur_couverture_solaire_2027.php ; elles ne
  modifient pas la présente table de 2021.
