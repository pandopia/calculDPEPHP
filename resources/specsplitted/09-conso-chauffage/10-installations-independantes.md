---
section_id: "9.10"
title: "Plusieurs installations différentes et indépendantes"
spec_pages: [66-67]
xml_outputs: []
tables: []
depends_on: ["9.1"]
status: "verbatim"
---

# §9.10 — Plusieurs installations différentes et indépendantes

> Source : `resources/spec.pdf` p.66-67
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.10 Chauffage avec plusieurs installations différentes et indépendantes et/ou
    plusieurs installations différentes et indépendantes couplées
Une installation de chauffage correspond à un générateur avec les émissions, distributions et régulations associées.

Surface chauffée par l’installation 1 : Sh1 (m2)                   Surface chauffée par l’installation 2 : Sh2 (m2)

Surface chauffée par l’installation 3 : Sh3 (m2)                   Surface chauffée par l’installation 4 : Sh4 (m2)

Surface chauffée par l’installation 5 : Sh5 (m2)                   Surface chauffée par l’installation 6 : Sh6 (m2)

Surface chauffée par l’installation i : Shi (m2) (la saisie de 6 installations minimum doit être possible)


Les consommations associées à ces installations sont :

                                                  𝑆ℎ𝑖
                                         𝐶𝑐ℎ = ∑ (    ∗ 𝐼𝑁𝑇𝑖 ∗ 𝐼𝑐ℎ𝑖 ) ∗ 𝐵𝑐ℎ
                                                   𝑆ℎ
                                                    𝑖

Soit dans notre cas :
        𝑆ℎ1                                        𝑆ℎ2                                       𝑆ℎ3
𝐶𝑐ℎ1 = 𝑆ℎ ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇1 ∗ 𝐼𝑐ℎ1           𝐶𝑐ℎ2 = 𝑆ℎ ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇2 ∗ 𝐼𝑐ℎ2                 𝐶𝑐ℎ3 = 𝑆ℎ ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇3 ∗ 𝐼𝑐ℎ3

        𝑆ℎ4                                        𝑆ℎ5                                       𝑆ℎ6
𝐶𝑐ℎ4 = 𝑆ℎ ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇4 ∗ 𝐼𝑐ℎ4           𝐶𝑐ℎ5 = 𝑆ℎ ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇5 ∗ 𝐼𝑐ℎ5                 𝐶𝑐ℎ6 = 𝑆ℎ ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇6 ∗ 𝐼𝑐ℎ6

L’intermittence sera déterminée pour chaque installation i associée à la surface Shj.

Dans le cas particulier où plusieurs équipements différents cohabitent dans une même pièce, avec des caractéristiques
différentes (c’est le cas parfois avec des émetteurs à effet joule ou des convecteurs et panneaux rayonnants ainsi que
des PAC air/air) on notera :

Pour la pièce j de surface Shj avec N équipements de puissance Pi (en W) la consommation devient :
                                               𝑁
                                                     𝑃𝑖                   𝑆ℎ𝑗
                                      𝐶𝑐ℎ𝑗 = ∑            ∗ 𝐼𝑐ℎ𝑖 ∗ 𝐼𝑁𝑇𝑖 ∗     ∗ 𝐵𝑐ℎ
                                                    ∑𝑖 𝑃𝑖                 𝑆ℎ
                                              𝑖=1

Dans le cas où les puissances Pi des équipements partageant la même pièce ne sont pas connues, la consommation
devient :
                                                𝑁
                                                        1                 𝑆ℎ𝑗
                                       𝐶𝑐ℎ𝑗 = ∑           ∗ 𝐼𝑐ℎ𝑖 ∗ 𝐼𝑁𝑇𝑖 ∗     ∗ 𝐵𝑐ℎ
                                                        𝑁                 𝑆ℎ
                                               𝑖=1

Dans cette configuration, tous les émetteurs associés aux différents générateurs sont de base.
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
