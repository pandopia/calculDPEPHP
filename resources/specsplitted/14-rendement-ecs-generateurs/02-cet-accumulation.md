---
section_id: "14.2"
title: "Chauffe-eau thermodynamique à accumulation"
spec_pages: [95]
xml_outputs: ["cop"]
tables: ["tv_scop_id"]
depends_on: ["14"]
status: "digitalized"
---

# §14.2 — Chauffe-eau thermodynamique à accumulation

> Source : resources/spec.pdf, p.95, annexe 1 d'octobre 2021.
> Tables et formule relues visuellement sur le PDF le 24/10/2026.
> Transcription mise en forme ; relecture indépendante encore à effectuer.

Les performances des chauffe-eau thermodynamiques sont définies par des COP
dépendant du type d'installation et de la zone climatique. Lorsque les
caractéristiques exactes ne peuvent pas être saisies, les valeurs par défaut
ci-dessous sont utilisables. Ces données sont annuelles.

## Zones H1 et H2 (p.95)

| Type d'installation | Avant 2010 | 2010–2014 | À partir de 2015 |
|---|---:|---:|---:|
| CET sur air extérieur ou ambiant, sur local non chauffé | 2 | 2,2 | 2,5 |
| CET sur air extrait | 2,3 | 2,5 | 2,8 |
| PAC double service | 2 | 2,1 | 2,3 |

## Zone H3 (p.95)

| Type d'installation | Avant 2010 | 2010–2014 | À partir de 2015 |
|---|---:|---:|---:|
| CET sur air extérieur ou ambiant, sur local non chauffé | 2,3 | 2,5 | 2,8 |
| CET sur air extrait | 2,3 | 2,5 | 2,9 |
| PAC double service | 2,3 | 2,4 | 2,6 |

## Rendement de l'installation (p.95)

La performance du ballon est prise en compte dans le COP du chauffe-eau
thermodynamique. Ainsi :

$$
I_{\mathrm{ecs}} = \frac{1}{R_d \times COP}
$$

## Repères d'implémentation — hors transcription réglementaire

- $R_d$ est le rendement de distribution ECS défini au §11.5.
- Ne pas multiplier une seconde fois par un rendement de stockage : le COP
  inclut déjà la performance du ballon.
- Les six lignes COP ajoutées par l'ADEME pour les installations depuis 2027
  sont transcrites séparément dans resources/tables/ecs/tv_cop_2027.php.
  Le présent document conserve les valeurs du PDF de 2021.
