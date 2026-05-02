---
section_id: "14.2"
title: "Chauffe-eau thermodynamique à accumulation"
spec_pages: [95]
xml_outputs: ["cop"]
tables: []
depends_on: ["14"]
status: "verbatim"
---

# §14.2 — Chauffe-eau thermodynamique à accumulation

> Source : `resources/spec.pdf` p.95
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
14.2 Chauffe-eau thermodynamique à accumulation
Les performances des chauffe-eau thermodynamiques sont définies par des COP qui dépendent du type d’installation
et de la zone climatique. Le tableau suivant donne les caractéristiques par défaut des chauffe-eau thermodynamiques
si les caractéristiques exactes des équipements ne peuvent pas être saisies. Les valeurs tabulées sont des données
annuelles.

                                                                               Zone H1 et H2
                                   COP
                                                           Avant 2010          2010-2014 A partir de 2015
                    CET sur air extérieur ou ambiant
                                                                  2               2,2              2,5
                         (sur local non chauffé)
                            CET sur air extrait                  2,3              2,5              2,8
                           PAC double service                     2               2,1              2,3


                                                                                 Zone H3
                                   COP
                                                           Avant 2010          2010-2014    A partir de 2015
                    CET sur air extérieur ou ambiant
                                                                 2,3              2,5              2,8
                         (sur local non chauffé)
                            CET sur air extrait                  2,3              2,5              2,9
                           PAC double service                    2,3              2,4              2,6


Pour le chauffe-eau thermodynamique, la performance des ballons est prise en compte dans le COP.

Ainsi :

                                                                   1
                                                      𝐼𝑒𝑐𝑠 =
                                                               𝑅𝑑 ∗ 𝐶𝑂𝑃
```

## TODO digitalisation

### Balises XML produites par cette section
- `cop`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
