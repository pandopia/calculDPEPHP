---
section_id: "12.3"
title: "Rendement de régulation"
spec_pages: [76]
xml_outputs: ["rendement_regulation"]
tables: ["tv_rendement_regulation_id"]
depends_on: ["12"]
status: "verbatim"
---

# §12.3 — Rendement de régulation

> Source : `resources/spec.pdf` p.76
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
12.3 Rendement de régulation
                                              Type d'équipements                                    Rr
                 Convecteur électrique NFC, NF** et NF***                                           0,99
                 Panneau rayonnant ou radiateur électrique NFC, NF** et NF***                       0,99
                 Autres émetteurs à effet joule                                                     0,96
                 Plancher ou plafond rayonnant électrique avec régulation terminale                 0,98
                 Plancher ou plafond rayonnant électrique sans régulation                           0,96
                 Radiateur électrique à accumulation                                                0,95
                 Plancher ou plafond chauffant à eau en individuel                                  0,95
                 Plancher ou plafond chauffant à eau en collectif                                   0,9
                 Radiateur gaz à ventouse ou sur conduit de fumée                                   0,96
                 Poêle charbon / bois / fioul / GPL ou insert                                       0,8
                 Radiateur eau chaude sans robinet thermostatique                                   0,9
                 Radiateur eau chaude avec robinet thermostatique                                   0,95
                 Convecteur bi-jonction                                                             0,9
                 Air soufflé                                                                        0,96

Pour tous les cas non listés : Rr = 0.9
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_rendement_regulation_id`

### Balises XML produites par cette section
- `rendement_regulation`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
