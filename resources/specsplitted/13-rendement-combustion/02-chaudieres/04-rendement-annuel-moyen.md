---
section_id: "13.2.4"
title: "Rendement conventionnel annuel moyen de génération"
spec_pages: [92]
xml_outputs: ["rendement_generation"]
tables: []
depends_on: ["13.2.1", "13.2.3"]
status: "verbatim"
---

# §13.2.4 — Rendement conventionnel annuel moyen de génération

> Source : `resources/spec.pdf` p.92
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
13.2.4 Rendement conventionnel annuel moyen de génération de chauffage
Une chaudière standard avec un condenseur sur ses fumées est traitée comme une chaudière condensation de même
ancienneté :

                                                             𝑃𝑚𝑓𝑜𝑢
                                       𝑅𝑔𝑐ℎ_𝑃𝐶𝑆 =
                                                    𝑃𝑚𝑐𝑜𝑛𝑠 + 0,45 ∗ 𝑄𝑃0 + 𝑃𝑣𝑒𝑖𝑙

Avec :

    -    Pveil : puissance de la veilleuse (kW)

    -    QP0 : Pertes à l’arrêt (kW)

Pour le calcul des consommations, la conversion en PCI du rendement donne :

                                             𝑅𝑔𝑐ℎ_𝑃𝐶𝐼 = 𝑘𝑃𝐶𝑆/𝑃𝐶𝐼 ∗ 𝑅𝑔𝑐ℎ_𝑃𝐶𝑆

Avec :

    -    k 𝑃𝐶𝑆/𝑃𝐶𝐼 : coefficient de conversion en PCI / PCS (défini au §13.2.1.4)
```

## TODO digitalisation

### Balises XML produites par cette section
- `rendement_generation`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
