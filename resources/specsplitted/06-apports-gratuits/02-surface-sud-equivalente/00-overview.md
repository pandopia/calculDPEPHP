---
section_id: "6.2"
title: "Détermination de la surface Sud équivalente"
spec_pages: [45]
xml_outputs: ["surface_sud_equivalente"]
tables: ["tv_sw_id"]
depends_on: ["6"]
status: "verbatim"
---

# §6.2 — Détermination de la surface Sud équivalente

> Source : `resources/spec.pdf` p.45
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
6.2 Détermination de la surface Sud équivalente
Données d’entrée :

Inclinaison des baies (verticale, pente, horizontale)

Orientation des baies (Nord, Sud, Est, Ouest)

Position des baies en flanc de loggias

Nature des menuiseries (bois, PVC…)

Type de vitrage (Simple, double…)

Positionnement de la menuiserie (tunnel, nu intérieur…)

Type de masque

Masques proches (balcon, loggias…)

Masques lointains

Profondeur des masques proches (profondeur balcon)

Largeur des baies

Positionnement des masques (Nord, Sud…)

Angle de vue des masques lointains

Type de fenêtre ou de porte fenêtre (coulissante, battante, avec ou sans soubassement…)

La prise en compte des apports solaires exige à minima une saisie par façade des fenêtres du bâtiment. Le calcul de la
surface sud équivalente se fait en sommant les valeurs de Sse pour chaque paroi vitrée i :

                                             𝑆𝑠𝑒𝑗 = ∑ 𝐴𝑖 ∗ 𝑆𝑤𝑖 ∗ 𝐹𝑒𝑖 ∗ 𝐶1𝑖,𝑗
                                                        𝑖

Avec :

    -    𝐴𝑖 : surface de la baie i (m²)

    -    𝑆𝑤𝑖 : proportion d’énergie solaire incidente qui pénètre dans le logement par la paroi vitrée i

    -    𝐹𝑒𝑖 : facteur d'ensoleillement, qui traduit la réduction d'énergie solaire reçue par une paroi vitrée du fait
         des masques

    -    𝐶1𝑖,𝑗 : coefficient d’orientation et d’inclinaison pour la paroi vitrée i pour le mois j, voir paragraphe 18.5

La surface vitrée des portes n’est pas prise en compte dans le calcul de Ssej.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_sw_id`

### Balises XML produites par cette section
- `surface_sud_equivalente`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
