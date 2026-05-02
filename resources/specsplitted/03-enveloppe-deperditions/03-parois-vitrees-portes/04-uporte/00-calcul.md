---
section_id: "3.3.4"
title: "Coefficients U des portes"
spec_pages: [32]
xml_outputs: ["uporte"]
tables: ["tv_uporte_id"]
depends_on: ["3.3"]
status: "verbatim"
---

# §3.3.4 — Coefficients U des portes

> Source : `resources/spec.pdf` p.32
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.3.4 Coefficients U des portes
Si le coefficient U des portes est connu et justifié, le saisir directement. Sinon, prendre les valeurs tabulées ci-dessous :

                    Nature de la menuiserie                  Type de porte                   Uporte W/(m².K)
                                                          Porte opaque pleine                      3,5
                                                  Porte avec moins de 30% de vitrage
                    Porte simple en bois ou                                                          4
                                                                 simple
                             PVC
                                                  Porte avec 30-60% de vitrage simple               4,5
                                                       Porte avec double vitrage                    3,3
                                                          Porte opaque pleine                       5,8
                                                        Porte avec vitrage simple                   5,8
                     Porte simple en métal        Porte avec moins de 30% de double
                                                                                                    5,5
                                                                 vitrage
                                                  Porte avec 30-60% de double vitrage               4,8
                                                       Porte opaque pleine isolée                   1,5
                       Toute menuiserie                 Porte précédée d’un SAS                     1,5
                                                    Porte isolée avec double vitrage                1,5

Attention : une porte vitrée avec plus de 60% de vitrage est traitée comme une porte-fenêtre avec soubassement.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_uporte_id`

### Balises XML produites par cette section
- `uporte`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
