---
section_id: "3.2.2.2"
title: "Calcul des Upb0"
spec_pages: [20]
xml_outputs: ["upb0"]
tables: ["tv_upb0_id"]
depends_on: ["3.2.2"]
status: "verbatim"
---

# §3.2.2.2 — Calcul des Upb0

> Source : `resources/spec.pdf` p.20
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.2.2.2 Calcul des Upb0

Upb0 est le coefficient de transmission thermique du plancher bas non isolé (W/(m².K)).




       Upb0 = 1,45 W/(m².K)                          Upb0 = 1,45 W/(m².K)




       Upb0 = 1,1 W/(m².K)                        Upb0 = 1,6 W/(m².K)
                                                                                          Upb0 = 0,8 W/(m².K)




        Upb0 = 1,1 W/(m².K)
                                                    Upb0 = 1,75 W/(m².K)                   Upb0 = 2 W/(m².K)




      Upb0 = 1,6 W/(m².K)
                                                  Upb0 = 2 W/(m².K)

Plancher à entrevous isolant Upb0 = 0,45 W/(m².K)

Pour les planchers bas non répertoriés, saisir directement les coefficients de transmission thermique Upb0 quand ils
sont justifiés. Les données des règles TH-U peuvent être utilisées.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_upb0_id`

### Balises XML produites par cette section
- `upb0`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
