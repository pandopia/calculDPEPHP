---
section_id: "10.3"
title: "Les consommations de refroidissement"
spec_pages: [69-70]
xml_outputs: ["conso_fr"]
tables: ["tv_seer_id"]
depends_on: ["10.2"]
status: "verbatim"
---

# §10.3 — Les consommations de refroidissement

> Source : `resources/spec.pdf` p.69-70
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
10.3 Les consommations de refroidissement
Données d’entrée :

Performance de l’installation de refroidissement (SEER ou année d’installation)

Zone climatique

Surface habitable

Surface habitable refroidie

La consommation de refroidissement est :

                                                                   𝐵𝑓𝑟
                                                     𝐶𝑓𝑟 = 0,9 ∗
                                                                   𝐸𝐸𝑅

Avec :

    -    0,9 : coefficient d’intermittence pour le froid.

    -    EER : coefficient d’efficience énergétique. Il représente la performance de l’installation de refroidissement :

                                                       𝐸𝐸𝑅 = 0,95 ∗ 𝑆𝐸𝐸𝑅

                 SEER : coefficient d’efficience énergétique saisonnier :

                                  SEER          Avant 2008*       2008-2014     A partir de 2015
                              Zone H1 et H2         3,6              6,5               6,7
                                Zone H3            3,25              5,7               7,5

                 *EER

Si le coefficient SEER du système de refroidissement est connu et justifié, le saisir directement.

La consommation de refroidissement est déterminée pour le logement entier. Si seule une partie du logement est
refroidie, alors la consommation de refroidissement du logement est obtenue en multipliant la consommation de froid
calculée pour le logement entier par le rapport de la surface habitable de la partie refroidie à celle du logement.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_seer_id`

### Balises XML produites par cette section
- `conso_fr`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
