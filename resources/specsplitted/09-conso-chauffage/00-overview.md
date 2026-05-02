---
section_id: "9"
title: "Calcul de la consommation de chauffage (Cch)"
spec_pages: [57]
xml_outputs: ["conso_ch", "conso_ch_depensier"]
tables: []
depends_on: ["2", "12", "13"]
status: "verbatim"
---

# §9 — Calcul de la consommation de chauffage (Cch)

> Source : `resources/spec.pdf` p.57
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9 Calcul de la consommation de chauffage (Cch)
Données d’entrée principales :

Rendement de génération : Rg (sans dimension)

Rendement d’émission : Re (sans dimension)

Rendement de distribution : Rd (sans dimension)

Rendement de régulation : Rr (sans dimension)

Type d’installation de chauffage : avec ou sans solaire ; base + appoint…

Présence d’une ventouse (ou assistance par ventilateur) sur l’équipement
```

## TODO digitalisation

### Balises XML produites par cette section
- `conso_ch`
- `conso_ch_depensier`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
