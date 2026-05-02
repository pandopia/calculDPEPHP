---
section_id: "11.3"
title: "Un seul système d'ECS avec solaire"
spec_pages: [73]
xml_outputs: []
tables: ["tv_facteur_couverture_solaire_id"]
depends_on: ["11.2"]
status: "verbatim"
---

# §11.3 — Un seul système d'ECS avec solaire

> Source : `resources/spec.pdf` p.73
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
11.3 Un seul système d’ECS avec solaire
Dans le cas où un seul système de production d’ECS solaire est installé, la consommation d’ECS Cecs (kWh PCI)
s’exprime de la manière suivante :

                                             𝐶𝑒𝑐𝑠 = 𝐵𝑒𝑐𝑠 ∗ (1 − 𝐹𝑒𝑐𝑠) ∗ 𝐼𝑒𝑐𝑠

Avec :

    -    Becs : besoin en eau chaude sanitaire (kWh)

    -    Fecs : facteur de couverture solaire, déterminé à partir du tableau du paragraphe 18.4

    -    Iecs : inverse du produit des rendements

La production d’ECS solaire Prodecs_solaire (kWh PCI) s’écrit alors :

                                           𝑃𝑟𝑜𝑑𝑒𝑐𝑠_𝑠𝑜𝑙𝑎𝑖𝑟𝑒 = 𝐵𝑒𝑐𝑠 ∗ 𝐹𝑒𝑐𝑠 ∗ 𝐼𝑒𝑐𝑠
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_facteur_couverture_solaire_id`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
