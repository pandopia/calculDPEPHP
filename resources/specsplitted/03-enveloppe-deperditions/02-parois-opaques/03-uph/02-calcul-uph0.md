---
section_id: "3.2.3.2"
title: "Calcul des Uph0"
spec_pages: [22]
xml_outputs: ["uph0"]
tables: ["tv_uph0_id"]
depends_on: ["3.2.3"]
status: "verbatim"
---

# §3.2.3.2 — Calcul des Uph0

> Source : `resources/spec.pdf` p.22
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.2.3.2 Calcul des Uph0

Uph0 est le coefficient de transmission thermique du plancher haut non isolé (W/(m².K)).




Combles aménagés sous rampant : Uph0 = 2,5 W/(m².K)

Toiture en chaume : Uph0 = 0,24 W/(m².K)

Plafond en plaque de plâtre : Uph0 = 2,5 W/(m².K)

Les toitures en bac acier sont traités comme des combles aménagés sous rampants : Uph0 = 2.5W/m².KPour les murs,
plafonds, planchers non répertoriés, saisir directement les coefficients de transmission thermique quand ceux si
peuvent être justifiés. Les données des règles TH-U peuvent être utilisées à défaut.

Attention : Les valeurs par défaut des caractéristiques des parois dépendent des années de construction dans certains
cas. Pour les bâtiments ayant fait l’objet d’extension, les valeurs par défaut des caractéristiques des parois peuvent
donc être différentes entre l’extension et le bâtiment originel.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_uph0_id`

### Balises XML produites par cette section
- `uph0`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
