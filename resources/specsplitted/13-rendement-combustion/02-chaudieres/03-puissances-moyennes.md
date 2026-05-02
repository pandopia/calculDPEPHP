---
section_id: "13.2.3"
title: "Puissances moyennes fournies et consommées"
spec_pages: [92]
xml_outputs: []
tables: []
depends_on: ["13.2.1"]
status: "verbatim"
---

# §13.2.3 — Puissances moyennes fournies et consommées

> Source : `resources/spec.pdf` p.92
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
13.2.3 Puissances moyennes fournies et consommées
On calcule les puissances fournies et consommées Pfoux-fonc et Pconsx-fonc (en kW) par un générateur au point de
fonctionnement x de la façon suivante :

                                         𝑃𝑓𝑜𝑢𝑥−𝑓𝑜𝑛𝑐 = 𝑃𝑥 ∗ 𝑐𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑𝑥_𝑓𝑖𝑛𝑎𝑙

                                                                          𝑃𝑥 + 𝑄𝑃𝑥
                                        𝑃𝑐𝑜𝑛𝑠𝑥−𝑓𝑜𝑛𝑐 = 𝑃𝑓𝑜𝑢𝑥−𝑓𝑜𝑛𝑐 ∗
                                                                             𝑃𝑥

                                                   𝑃𝑥 = 𝑃𝑛 ∗ 𝑇𝑐ℎ𝑥_𝑓𝑖𝑛𝑎𝑙

Les puissances moyennes fournies et consommées par un générateur s’exprime de la façon suivante :
                                                         𝑥=100%

                                             𝑃𝑚𝑓𝑜𝑢 =       ∑ 𝑃𝑓𝑜𝑢𝑥−𝑓𝑜𝑛𝑐
                                                          𝑥=0%

          𝑃𝑚𝑓𝑜𝑢 = 𝑃5 ∗ 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑5_𝑓𝑖𝑛𝑎𝑙 + 𝑃15 ∗ 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑15_𝑓𝑖𝑛𝑎𝑙 + ⋯ + 𝑃95 ∗ 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑95_𝑓𝑖𝑛𝑎𝑙

                                                         𝑥=100%

                                            𝑃𝑚𝑐𝑜𝑛𝑠 =       ∑ 𝑃𝑐𝑜𝑛𝑠𝑥−𝑓𝑜𝑛𝑐
                                                          𝑥=0%

                                              𝑃5 + 𝑄𝑃5                                𝑃15 + 𝑄𝑃15
         𝑃𝑚𝑐𝑜𝑛𝑠 = 𝑃5 ∗ 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑5_𝑓𝑖𝑛𝑎𝑙 ∗               + 𝑃15 ∗ 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑15_𝑓𝑖𝑛𝑎𝑙 ∗             + ⋯ 𝑃85
                                                  𝑃5                                      𝑃15
                                                 𝑃85 + 𝑄𝑃85                               𝑃95 + 𝑄𝑃95
                          ∗ 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑85_𝑓𝑖𝑛𝑎𝑙 ∗             + 𝑃95 ∗ 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑95_𝑓𝑖𝑛𝑎𝑙 ∗
                                                     𝑃85                                      𝑃95
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
