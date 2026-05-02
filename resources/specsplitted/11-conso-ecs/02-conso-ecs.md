---
section_id: "11.2"
title: "Calcul des consommations d'ECS"
spec_pages: [72-73]
xml_outputs: ["conso_ecs"]
tables: []
depends_on: ["11.1", "14"]
status: "verbatim"
---

# §11.2 — Calcul des consommations d'ECS

> Source : `resources/spec.pdf` p.72-73
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
11.2 Calcul des consommations d’ECS
Données d’entrée :

Rendement de génération : Rg (sans dimension)

Rendement de distribution : Rd (sans dimension)

Rendement de stockage : Rs (sans dimension)

Type d’installation d’ECS : avec ou sans solaire ; base + appoint…

Puissance nominale des générateurs : Pn (W)

La consommation annuelle d’eau chaude sanitaire Cecs (kWh PCI) s’exprime de la manière suivante :

                                                  𝐶𝑒𝑐𝑠 = 𝐵𝑒𝑐𝑠 ∗ 𝐼𝑒𝑐𝑠
Avec :

    -    Becs : Besoin annuel d’ECS (kWh)

    -    Iecs : Inverse du rendement de l’installation :
                                                                       1
                                                        𝐼𝑒𝑐𝑠 =
                                                                 𝑅𝑠 ∗ 𝑅𝑑 ∗ 𝑅𝑔
            Rs : rendement de stockage

            Rd : rendement de distribution

            Rg : rendement de génération

La consommation d’ECS sur un mois j peut être déduite de la consommation annuelle :

                                                              Becs𝑗
                                                    Cecs𝑗 =         ∗ 𝐶𝑒𝑐𝑠
                                                              𝐵𝑒𝑐𝑠
```

## TODO digitalisation

### Balises XML produites par cette section
- `conso_ecs`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
