---
section_id: "9.6"
title: "Installation chauffage solaire + insert ou poêle bois"
spec_pages: [64]
xml_outputs: []
tables: []
depends_on: ["9.2", "9.3"]
status: "verbatim"
---

# §9.6 — Installation chauffage solaire + insert ou poêle bois

> Source : `resources/spec.pdf` p.64
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.6 Installation de chauffage avec chauffage solaire et insert ou poêle bois en
    appoint
Cette configuration, valable seulement pour les maisons individuelles, correspond à un insert ou à un poêle en appoint
dans le logement en plus d’un système général composé d’un équipement principal accompagné par du chauffage
solaire chauffant presque tout le logement.

La consommation annuelle de chauffage Cch1 liée au système principal de chauffage (kWh PCI) est donnée par la
formule :

                                     𝐶𝑐ℎ1 = 0,75 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇1 ∗ (1 − 𝐹𝑐ℎ) ∗ 𝐼𝑐ℎ1

En présence de plusieurs générateurs et émetteurs, la part de la consommation de chauffage assurée par l’installation
est calculée en appliquant les règles du paragraphe 9.1.

La consommation annuelle de chauffage Cch2 liée à l’insert ou au poêle bois (kWh PCI) est donnée par la formule :

                                     𝐶𝑐ℎ2 = 0,25 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇2 ∗ (1 − 𝐹𝑐ℎ) ∗ 𝐼𝑐ℎ2

La production annuelle de chauffage solaire Prodchauff_sol (kWh PCI) est donnée par la formule :

                      𝑃𝑟𝑜𝑑𝑐ℎ𝑎𝑢𝑓𝑓_𝑠𝑜𝑙 = 𝐵𝑐ℎ ∗ 𝐹𝑐ℎ ∗ (0,75 ∗ 𝐼𝑁𝑇1 ∗ 𝐼𝑐ℎ1 + 0,25 ∗ 𝐼𝑁𝑇2 ∗ 𝐼𝑐ℎ2)

L’émetteur traité en appoint est le poêle bois ou l’insert.
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
