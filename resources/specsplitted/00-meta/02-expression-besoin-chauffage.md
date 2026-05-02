---
section_id: "2"
title: "Expression du besoin de chauffage"
spec_pages: [6]
xml_outputs: ["besoin_ch", "besoin_ch_depensier"]
tables: []
depends_on: ["1"]
status: "verbatim"
---

# §2 — Expression du besoin de chauffage

> Source : `resources/spec.pdf` p.6
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
2 Expression du besoin de chauffage
BVj : besoins mensuels de chauffage d’un logement, divisés par l'écart moyen de température entre l'intérieur et
l'extérieur durant la période de chauffage. Son calcul se fait à partir du coefficient GV en tenant compte des apports
de chaleur dus à l'occupation et au rayonnement solaire. Il est exprimé en watts par kelvin (W/K) :

                                                   𝐵𝑉𝑗 = 𝐺𝑉 ∗ (1 − 𝐹𝑗 )

Avec :

    -    GV : déperditions de l’enveloppe en W/K (voir partie 3)
    -    Fj : fraction des besoins de chauffage couverts par les apports gratuits sur le mois j (voir partie 6.1)
```

## TODO digitalisation

### Balises XML produites par cette section
- `besoin_ch`
- `besoin_ch_depensier`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
