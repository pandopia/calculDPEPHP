---
section_id: "9.1.3"
title: "Installation avec plusieurs émissions pour un même générateur"
spec_pages: [60]
xml_outputs: []
tables: []
depends_on: ["9.1.1"]
status: "verbatim"
---

# §9.1.3 — Installation avec plusieurs émissions pour un même générateur

> Source : `resources/spec.pdf` p.60
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.1.3 Installation avec plusieurs émissions pour un même générateur
Ce cas correspond aux installations centralisées avec plusieurs émetteurs de types différents

Les consommations associées à ces installations sont :

                                                   𝑆ℎ𝑖
                                          𝐶𝑐ℎ = ∑ (    ∗ 𝐼𝑁𝑇𝑖 ∗ 𝐼𝑐ℎ𝑖 ) ∗ 𝐵𝑐ℎ
                                                    𝑆ℎ
                                                   𝑖

La part de la consommation traitée par chaque émetteur est proratisé par le ratio des surfaces habitables.

Par exemple, pour un générateur alimentant un plancher chauffant au rez-de-chaussée et des radiateurs en étage
d’une habitation, il faut considérer une installation avec deux émetteurs et éventuellement deux régulations et
distributions.

    -    Surface chauffée par l’émission 1 (installation 1) : Sh1 (m2)
    -    Surface chauffée par l’émission 2 (installation 2) : Sh2 (m2)

Soit dans notre cas :

                                                  𝐶𝑐ℎ = 𝐶𝑐ℎ1 + 𝐶𝑐ℎ2

Avec :

                                                       𝑆ℎ1
                                           𝐶𝑐ℎ1 =          ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇1 ∗ 𝐼𝑐ℎ1
                                                       𝑆ℎ
                                                       𝑆ℎ2
                                           𝐶𝑐ℎ2 =          ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇2 ∗ 𝐼𝑐ℎ2
                                                       𝑆ℎ

Avec :

                                                              1
                                             Ich1 = (                    )
                                                     Rg ∗ Re1 ∗ Rd ∗ Rr1

                                                              1
                                             Ich2 = (                    )
                                                     Rg ∗ Re2 ∗ Rd ∗ Rr2

Dans cette configuration, tous les émetteurs sont définis en base car ils sont des émetteurs principaux du chauffage.

Les consommations sont mensualisées de la façon suivante :

                                                                  𝐵𝑐ℎ𝑗
                                                   𝐶𝑐ℎ𝑗 = 𝐶𝑐ℎ ∗
                                                                  𝐵𝑐ℎ

Cchj et Bchj respectivement les consommations et besoins de chauffage sur le mois j (kWh PCI).
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
