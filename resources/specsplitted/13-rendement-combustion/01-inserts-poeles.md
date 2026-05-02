---
section_id: "13.1"
title: "Inserts et poêles"
spec_pages: [78]
xml_outputs: ["rendement_generation"]
tables: []
depends_on: ["13"]
status: "verbatim"
---

# §13.1 — Inserts et poêles

> Source : `resources/spec.pdf` p.78
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
13.1 Inserts et poêles
Données d’entrée :

Type de générateur

Type de cascade

Présence d’une régulation

Type d’émetteur

Type de combustible bois

          Type de générateur                                                                              Rg
          Cuisinière, Foyer fermé, Poêle bûche, insert installé avant 1990                               0,5
          Cuisinière, Foyer fermé, Poêle bûche, insert installé entre 1990 et 2004                       0,60
          Cuisinière, Foyer fermé, Poêle bûche, insert installé à partir de 2005 sans label flamme
                                                                                                         0,65
          verte
          Cuisinière, Foyer fermé, Poêle bûche, insert installé de 2005 à 2006 avec label flamme
                                                                                                         0,65
          verte
          Cuisinière, Foyer fermé, Poêle bûche, insert installé de 2007 à 2017 avec label flamme
                                                                                                         0,70
          verte
          Cuisinière, Foyer fermé, Poêle bûche, insert installé à partir de 2018 avec label flamme
                                                                                                         0,75
          verte
          Poêle à granulés installée avant 2012 ou sans label flamme verte                               0,8
          Poêle à granulés flamme verte installé entre 2012 et 2019                                      0,85
          Poêle à granulés flamme verte installé à partir de 2020                                        0,87
          Poêle fioul GPL ou charbon                                                                     0,72

Les poêles à bois bouilleur installées à partir de 2012 sont traités comme des chaudières bois installées entre 2004 et
2012.

Les poêles à bois bouilleur installées avant 2012 sont traités comme des chaudières bois installées entre 1978 et 1994.
```

## TODO digitalisation

### Balises XML produites par cette section
- `rendement_generation`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
