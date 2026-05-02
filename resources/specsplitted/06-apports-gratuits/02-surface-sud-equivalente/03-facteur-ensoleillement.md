---
section_id: "6.2.2"
title: "Détermination du facteur d'ensoleillement"
spec_pages: [47]
xml_outputs: ["fe1", "fe2"]
tables: []
depends_on: ["6.2"]
status: "verbatim"
---

# §6.2.2 — Détermination du facteur d'ensoleillement

> Source : `resources/spec.pdf` p.47
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
6.2.2 Détermination du facteur d’ensoleillement
On considère successivement les obstacles liés au bâtiment (balcons, loggias, avancées, ...) et les obstacles liés à
l'environnement (autres bâtiments, reliefs, végétation, ...). On obtient ainsi deux coefficients, Fe1 et Fe2, dont on fait
le produit, soit :

                                                    𝐹𝑒 = 𝐹𝑒1 ∗ 𝐹𝑒2

En l'absence d'obstacles liés au bâtiment et pour les configurations non présentées ci-dessous, Fe1 = 1 ;

En l'absence d'obstacles liés à l’environnement, Fe2 = 1 ;

Conventionnellement, les orientations Nord, Sud, Est et Ouest correspondent aux secteurs situés de part et d’autre de
ces orientations dans un angle de 45°. Pour respectivement le Nord et le Sud, les orientations incluent les limites Nord-
Est, Nord-Ouest et Sud-Est, Sud-Ouest.
```

## TODO digitalisation

### Balises XML produites par cette section
- `fe1`
- `fe2`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
