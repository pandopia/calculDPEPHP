---
section_id: "12.4"
title: "Rendement de génération hors combustion (effet Joule, PAC)"
spec_pages: [76, 77, 78]
xml_outputs: ["rendement_generation", "scop"]
tables: ["tv_rendement_generation_id", "tv_scop_id"]
depends_on: ["12"]
status: "digitalized"
---

# §12.4 — Rendement de génération des générateurs autres qu'à combustion

> Source : resources/spec.pdf, p.76–78, annexe 1 d'octobre 2021.
> Tables et formule relues visuellement sur le PDF le 24/10/2026.
> Transcription mise en forme ; relecture indépendante encore à effectuer.

## §12.4.1 — Générateurs à effet Joule et réseaux de chaleur (p.76–77)

| Type de générateur | $R_g$ |
|---|---:|
| Générateur à effet Joule direct | 1 |
| Chaudière électrique | 0,97 |
| Réseau de chaleur | 0,97 |

Un chauffe-eau électrique instantané est assimilé à un ballon électrique,
mais sans pertes de stockage.

Une chaudière électrique mixte (chauffage et ECS) est modélisée comme une
chaudière électrique et un ballon électrique, selon la présence ou non de
stockage.

## §12.4.2 — Pompes à chaleur (p.77–78)

Le SCOP dépend du type de PAC et de la zone climatique. La performance réelle
connue et justifiée peut être saisie directement ; à défaut, les valeurs
annuelles tabulées ci-dessous sont utilisables.

### Zones H1 et H2 — PAC sur eau (p.77)

| Type de PAC | Type d'émetteur | Avant 2008* | 2008–2014 | 2015–2016 | À partir de 2017 |
|---|---|---:|---:|---:|---:|
| Air/Eau | Autres | 2,2 | 2,4 | 2,6 | 2,8 |
| Air/Eau | Planchers / Plafonds | 2,4 | 2,6 | 2,9 | 3,2 |
| Eau/Eau | Autres | 2,2 | 2,4 | 2,7 | 3 |
| Eau/Eau | Planchers / Plafonds | 2,4 | 2,6 | 3 | 3,3 |
| Eau glycolée/Eau | Autres | 2,2 | 2,4 | 2,7 | 3 |
| Eau glycolée/Eau | Planchers / Plafonds | 2,4 | 2,6 | 3 | 3,3 |
| Géothermie | Autres | 2,2 | 2,4 | 2,7 | 3 |
| Géothermie | Planchers / Plafonds | 2,4 | 2,6 | 3 | 3,3 |

### Zone H3 — PAC sur eau (p.77)

| Type de PAC | Type d'émetteur | Avant 2008* | 2008–2014 | 2015–2016 | À partir de 2017 |
|---|---|---:|---:|---:|---:|
| Air/Eau | Autres | 2,5 | 2,8 | 3 | 3,2 |
| Air/Eau | Planchers / Plafonds | 2,9 | 3,1 | 3,5 | 3,8 |
| Eau/Eau | Autres | 2,5 | 2,8 | 3,1 | 3,5 |
| Eau/Eau | Planchers / Plafonds | 2,9 | 3,1 | 3,6 | 4 |
| Eau glycolée/Eau | Autres | 2,5 | 2,8 | 3,1 | 3,5 |
| Eau glycolée/Eau | Planchers / Plafonds | 2,9 | 3,1 | 3,6 | 4 |
| Géothermie | Autres | 2,5 | 2,8 | 3,1 | 3,5 |
| Géothermie | Planchers / Plafonds | 2,9 | 3,1 | 3,6 | 4 |

### PAC Air/Air (p.77)

| Zone | Avant 2008* | 2008–2014 | À partir de 2015 |
|---|---:|---:|---:|
| H1 et H2 | 2,2 | 2,3 | 3 |
| H3 | 2,4 | 2,6 | 3,3 |

*Pour les colonnes « Avant 2008 », le PDF précise « COP ».*

### Inverse du rendement de l'installation (p.78)

$$
I_{\mathrm{ch}} = \frac{1}{SCOP \times R_e \times R_d \times R_r}
$$

Si plusieurs émetteurs sont reliés à la PAC, le COP le plus défavorable est
retenu pour le calcul de $I_{\mathrm{ch}}$.

## Repères d'implémentation — hors transcription réglementaire

- Les rendements d'émission, de distribution et de régulation sont définis
  respectivement aux §12.1, §12.2 et §12.3.
- Le SEER concerne le refroidissement : il ne figure pas dans cette section.
- La transcription de la règle multi-émetteurs ne clôt pas TASK-K91 :
  les variantes de sélection déjà mesurées restent documentées dans TASKS.md.
- Les extensions ADEME du modèle 2.7 sont datées séparément dans
  resources/tables/chauffage/tv_scop_2027.php ; elles ne remplacent pas
  rétroactivement les valeurs du PDF de 2021.
