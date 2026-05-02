---
section_id: "12.1"
title: "Rendement d'émission"
spec_pages: [75-76]
xml_outputs: ["rendement_emission"]
tables: ["tv_rendement_emission_id"]
depends_on: ["12"]
status: "verbatim"
---

# §12.1 — Rendement d'émission

> Source : `resources/spec.pdf` p.75-76
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
12.1 Rendement d’émission
                                              Type d'émetteurs                                      Re
                    Convecteur électrique NFC, NF** et NF***                                       0,95
                    Panneau rayonnant ou radiateur électrique NFC, NF** et NF***                   0,97
                    Autres émetteurs à effet joule                                                 0,95
                    Soufflage d'air chaud                                                          0,95
                    Plancher chauffant                                                          1
                    Plafond chauffant                                                          0,98
                    Autres équipements                                                         0,95
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_rendement_emission_id`

### Balises XML produites par cette section
- `rendement_emission`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
