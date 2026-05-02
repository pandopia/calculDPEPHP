---
section_id: "18.4"
title: "Facteur de couverture solaire"
spec_pages: [143]
xml_outputs: []
tables: ["tv_facteur_couverture_solaire_id"]
depends_on: ["18"]
status: "verbatim"
---

# §18.4 — Facteur de couverture solaire

> Source : `resources/spec.pdf` p.143
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
   18.4 Facteur de couverture solaire
                                           Maison                                         Immeuble collectif
   Zone    Chauffage solaire ECS solaire seule ECS solaire seule Chauffage + ECS ECS solaire seule ECS solaire seule
climatique (seul ou combiné)      > 5 ans          ≤ 5 ans           solaire          > 5ans            ≤ 5ans
                  Fch              Fecs              Fecs             Fecs             Fecs              Fecs
   H1a                 0,25         0,49               0,63              0,87               0,26               0,38
   H1b                 0,22         0,50               0,64              0,88               0,27               0,40
   H1c                 0,28         0,53               0,68              0,90               0,31               0,45
   H2a                 0,34         0,51               0,66              0,90               0,28               0,41
   H2b                 0,33         0,54               0,69              0,91               0,32               0,46
   H2c                 0,38         0,58               0,74              0,95               0,35               0,50
   H2d                 0,39         0,61               0,77              0,96               0,38               0,56
   H3                  0,52         0,64               0,80              0,98               0,40               0,58

   Les facteurs de couverture solaire peuvent être saisi directement quand ils sont connus et peuvent être justifiés.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_facteur_couverture_solaire_id`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
