---
section_id: "9.1.2"
title: "Installation classique"
spec_pages: [59-60]
xml_outputs: []
tables: []
depends_on: ["9.1.1"]
status: "verbatim"
---

# §9.1.2 — Installation classique

> Source : `resources/spec.pdf` p.59-60
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.1.2 Installation classique
Ce cas correspond à une installation simple avec un unique rendement de génération, de distribution, d’émission et
de régulation pour tout le bâtiment.

                                                 Cch = Bch ∗ Ich ∗ INT

Avec :

    -    Bch et Cch sont respectivement les besoins et consommations annuels de chauffage (kWh PCI)

    -    INT : Facteur d’intermittence

    -    Ich : Inverse du rendement de l’installation :
                                                              1
                                               Ich = (                  )
                                                      Rg ∗ Re ∗ Rd ∗ Rr



         Rg, Re, Rd et Rr sont respectivement le rendement annuel conventionnel du générateur ou le coefficient de
         performance des pompes à chaleur (COP), le rendement d’émission, le rendement de distribution et le
         rendement de régulation.

L’émetteur dans ce cas est défini comme un émetteur de base.
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
