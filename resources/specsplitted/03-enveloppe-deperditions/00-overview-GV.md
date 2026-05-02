---
section_id: "3"
title: "Calcul des déperditions de l'enveloppe GV"
spec_pages: [7]
xml_outputs: ["deperdition_enveloppe"]
tables: []
depends_on: ["2"]
status: "verbatim"
---

# §3 — Calcul des déperditions de l'enveloppe GV

> Source : `resources/spec.pdf` p.7
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3 Calcul des déperditions de l’enveloppe GV
Données d’entrée :

Caractéristiques de l’enveloppe (linéaires, surfaces, U)

Surface des parois déperditives i (murs, plafonds, planchers, baies, portes)

Linéaires de ponts thermiques

La somme GV des déperditions par les parois et par renouvellement d'air (W/K) s’exprime de la manière suivante :

               𝐺𝑉 = 𝐷𝑃𝑚𝑢𝑟 + 𝐷𝑃𝑝𝑙𝑎𝑛𝑐ℎ𝑒𝑟_𝑏𝑎𝑠 + 𝐷𝑃𝑝𝑙𝑎𝑛𝑐ℎ𝑒𝑟_ℎ𝑎𝑢𝑡 + 𝐷𝑃𝑚𝑒𝑛𝑢𝑖𝑠𝑒𝑟𝑖𝑒 + 𝑃𝑇 + 𝐷𝑅

Avec :

    -    PT : déperditions par les ponts thermiques (W/K) (voir partie 3.4)
    -    DR : déperditions par le renouvellement d’air (W/K) (voir partie 4)
    -    DPparoi : déperdition par la paroi (W/K) :

                                             𝐷𝑃𝑚𝑢𝑟 = ∑ 𝑏𝑖 ∗ 𝑆𝑚𝑢𝑟𝑖 ∗ 𝑈𝑚𝑢𝑟𝑖
                                                            𝑖


                                          𝐷𝑃𝑝𝑙𝑎𝑛𝑐ℎ𝑒𝑟_𝑏𝑎𝑠 = ∑ 𝑏𝑖 ∗ 𝑆𝑝𝑏𝑖 ∗ 𝑈𝑝𝑏𝑖
                                                                  𝑖


                                         𝐷𝑃𝑝𝑙𝑎𝑛𝑐ℎ𝑒𝑟_ℎ𝑎𝑢𝑡 = ∑ 𝑏𝑖 ∗ 𝑆𝑝ℎ𝑖 ∗ 𝑈𝑝ℎ𝑖
                                                                   𝑖


                                𝐷𝑃𝑚𝑒𝑛𝑢𝑖𝑠𝑒𝑟𝑖𝑒 = ∑ 𝑏𝑖 ∗ 𝑆𝑚𝑒𝑛𝑢𝑖𝑠𝑒𝑟𝑖𝑒𝑖 ∗ 𝑈𝑚𝑒𝑛𝑠𝑢𝑖𝑠𝑒𝑟𝑖𝑒𝑖
                                                      𝑖

Avec :

    -    bi : coefficient de réduction des déperditions pour la paroi i (voir partie 3.1)
    -    Sparoii : surface de la paroi déperditive i (m²)
    -    Uparoii : coefficient de transmission thermique de la paroi i (W/(m².K)) (voir parties 3.2 et 3.3)

On appelle menuiserie l'ensemble vitrage-protection solaire des fenêtres, portes-fenêtres et portes.

Attention : Les parois donnant sur un bâtiment autre que d’habitation sont aussi considérées déperditives.

La surface prise en compte pour l’établissement du DPE est la surface habitable du bâtiment. Cette surface intègre les
vérandas chauffées.

En présence d’un espace non habitable chauffé (par exemple un garage ou un sous-sol), cet espace est traité dans le
DPE comme un espace non chauffé. Dans ce cas, le diagnostiqueur devra obligatoirement mentionner dans le rapport
que cet espace ne doit pas être chauffé et intégrer ce commentaire dans la justification des écarts entre les factures
et les consommations conventionnelles.
```

## TODO digitalisation

### Balises XML produites par cette section
- `deperdition_enveloppe`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
