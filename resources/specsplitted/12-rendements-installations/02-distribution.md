---
section_id: "12.2"
title: "Rendement de distribution"
spec_pages: [76]
xml_outputs: ["rendement_distribution"]
tables: ["tv_rendement_distribution_ch_id"]
depends_on: ["12"]
status: "verbatim"
---

# §12.2 — Rendement de distribution

> Source : `resources/spec.pdf` p.76
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
12.2 Rendement de distribution
                                                                                                 Rd
                                   Type de distribution
                                                                                   Non isolé               Isolé
        Pas de réseau de distribution                                                    1                  1
        Réseau aéraulique                                                                0,8               0,85
        Réseau collectif eau chaude haute température (≥ 65°C)                         0,85                0,87
        Réseau collectif eau chaude moyenne ou basse température (< 65°C)              0,87                0,9
        Réseau individuel eau chaude moyenne ou basse température (< 65°C)             0,91                0,95
        Réseau individuel eau chaude haute température (≥ 65°C)                        0,88                0,92

Les réseaux de distribution par fluide frigorigène sont considérés sans pertes (Rd=1).
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_rendement_distribution_ch_id`

### Balises XML produites par cette section
- `rendement_distribution`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
