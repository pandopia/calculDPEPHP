---
section_id: "9.4"
title: "Installation par insert/poêle bois + chauffage électrique SdB"
spec_pages: [63]
xml_outputs: []
tables: []
depends_on: ["9.1"]
status: "verbatim"
---

# §9.4 — Installation par insert/poêle bois + chauffage électrique SdB

> Source : `resources/spec.pdf` p.63
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.4 Installation de chauffage par insert, poêle bois (ou biomasse) avec un
    chauffage électrique dans la salle de bains
Dans cette configuration, valable que pour les maisons individuelles, tout le bâtiment est chauffé par un poêle bois.
Seule la salle de bains est chauffée par un système électrique.

La consommation annuelle de chauffage Cch1 liée au poêle bois (kWh PCI) est donnée par la formule :

                                          𝐶𝑐ℎ1 = 0,9 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇1 ∗ 𝐼𝑐ℎ1

La consommation annuelle de chauffage Cch2 liée au chauffage électrique de la salle de bains (kWh PCI) est donnée
par la formule :

                                          𝐶𝑐ℎ2 = 0,1 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇2 ∗ 𝐼𝑐ℎ2

L’émetteur poêle bois ou insert est traité ici comme un émetteur de base. Le chauffage électrique dans la salle de bain
est saisi comme un émetteur de SDB. En présence de plusieurs salles de bain avec un chauffage électrique différent,
la part de la consommation apportée par l’appoint est répartie entre les deux équipements par un prorata de surface
habitable. C’est-à-dire pour le cas d’un logement avec deux salles de bains de surface Shsdb1 et Shsdb2 :

                                                  𝑆ℎ𝑠𝑑𝑏1
                           𝐶𝑐ℎ2𝑠𝑏𝑑1 = 0,1 ∗                   ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇𝑠𝑑𝑏1 ∗ 𝐼𝑐ℎ𝑠𝑑𝑏1
                                              𝑆ℎ𝑠𝑑𝑏1 + 𝑆ℎ𝑠𝑑𝑏2
                                                  𝑆ℎ𝑠𝑑𝑏2
                           𝐶𝑐ℎ2𝑠𝑏𝑑2 = 0,1 ∗                   ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇𝑠𝑑𝑏2 ∗ 𝐼𝑐ℎ𝑠𝑑𝑏2
                                              𝑆ℎ𝑠𝑑𝑏1 + 𝑆ℎ𝑠𝑑𝑏2

Avec :

                                           𝐶𝑐ℎ2 = 𝐶𝑐ℎ2𝑠𝑑𝑏1 + 𝐶𝑐ℎ2𝑠𝑑𝑏2
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
