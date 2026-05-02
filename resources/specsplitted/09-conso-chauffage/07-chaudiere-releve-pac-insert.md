---
section_id: "9.7"
title: "Installation chaudière en relève de PAC + insert/poêle"
spec_pages: [64-65]
xml_outputs: []
tables: []
depends_on: ["9.1"]
status: "verbatim"
---

# §9.7 — Installation chaudière en relève de PAC + insert/poêle

> Source : `resources/spec.pdf` p.64-65
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.7 Installation de chauffage avec chaudière en relève de PAC avec insert ou
    poêle bois en appoint
Cette installation correspond à une PAC assurant principalement le chauffage sauf par temps de grand froid où la PAC
s’arrête pour laisser le relais à la chaudière. Dans le bâtiment, il y a un poêle bois ou un insert qui est utilisé de temps
en temps en remplacement du système principal.

La consommation annuelle de chauffage liée à la PAC (kWh PCI) est donnée par la formule :


                                        𝐶𝑐ℎ1 = 0,8 ∗ 0,75 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇1 ∗ 𝐼𝑐ℎ1

La consommation annuelle de chauffage liée à la chaudière (kWh PCI) est donnée par la formule :

                                        𝐶𝑐ℎ2 = 0,2 ∗ 0,75 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇2 ∗ 𝐼𝑐ℎ2

La consommation annuelle de chauffage lié à l’insert ou au poêle en appoint (kWh PCI) est donnée par la formule :

                                           𝐶𝑐ℎ3 = 0,25 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇3 ∗ 𝐼𝑐ℎ3

Dans cette configuration, les générateurs sont multiples et couplés, les émetteurs sont de base et peuvent aussi être
multiples.

L’émetteur traité en appoint est le poêle bois ou l’insert.

En présence de plusieurs générateurs et émetteurs, la part de la consommation de chauffage assurée par l’installation
est calculée en appliquant les règles du paragraphe 9.1.
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
