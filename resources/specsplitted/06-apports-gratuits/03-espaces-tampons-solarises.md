---
section_id: "6.3"
title: "Traitement des espaces tampons solarisés"
spec_pages: [50-52]
xml_outputs: ["bver", "coef_transparence_ets"]
tables: []
depends_on: ["6"]
status: "verbatim"
---

# §6.3 — Traitement des espaces tampons solarisés

> Source : `resources/spec.pdf` p.50-52
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
   6.3 Traitement des espaces tampons solarisés
   Un logement donnant sur un espace tampon solarisé (véranda, loggia fermée) est influencé dans son bilan énergétique
   par les apports solaires. Il en existe deux types :

-   Les apports solaires indirects qui sont associés au rayonnement solaire qui rentre dans le logement après de
    multiples réflexions dans l’espace tampon solarisé ;

-   Les apports solaires directs qui sont associés au rayonnement solaire qui rentre directement dans le logement
    après avoir traversé l’espace tampon solarisé pour pénétrer dans le logement (en direct ou diffus).

Les apports solaires à travers un espace tampon solarisé sont donnés sur un mois j par :

                                          𝐴𝑠𝑣𝑒𝑟𝑗 = 1000 ∗ 𝑆𝑠𝑒𝑣𝑒𝑟𝑎𝑛𝑑𝑎,𝑗 ∗ 𝐸𝑗

    Avec :

-   Ej : ensoleillement reçu par une paroi verticale orientée au sud en l’absence d'ombrage sur le mois j (kWh/m²),
    déterminé à partir des tableaux des paragraphes 18.2 et 18.3

-   Dans le cas de baies vitrées séparant l’espace tampon solarisé de la partie habitable du logement, l’impact de
    l’espace tampon solarisé sur les apports solaires à travers ces baies vitrées est modélisé par une surface sud
    équivalente pour le mois j 𝑆𝑠𝑒𝑣𝑒𝑟𝑎𝑛𝑑𝑎,𝑗 :

                                      𝑆𝑠𝑒𝑣𝑒𝑟𝑎𝑛𝑑𝑎,𝑗 = 𝑆𝑠𝑑𝑗 + 𝑆𝑠𝑖𝑛𝑑𝑗 ∗ 𝑏𝑣𝑒𝑟

       Ssd : Surface sud équivalente représentant l’impact des apports solaires associés au rayonnement solaire
        traversant directement l’espace tampon pour arriver dans la partie habitable du logement (apports
        directs) :

                                      𝑆𝑠𝑑𝑗 = 𝑇 ∗ ∑ 𝐴𝑖 ∗ 𝑆𝑤𝑖 ∗ 𝐹𝑒𝑖 ∗ 𝐶1𝑖,𝑗
                                                    𝑖

        Avec :
        □ 𝐴𝑖 : Surface de la baie i séparant le logement de l’espace tampon solarisé (m²)

        □    𝑆𝑤𝑖 : Facteur solaire de la baie i séparant le logement de l’espace tampon solarisé

        □    𝐹𝑒𝑖 : Facteur d'ensoleillement, qui traduit la réduction d'énergie solaire reçue par la baie i du fait des
             masques (la présence de l’espace tampon solarisé n’est pas prise en compte pour déterminer ce
             coefficient)

        □    𝐶1𝑖,𝑗 : Coefficient d’orientation et d’inclinaison de la baie i séparant le logement de l’espace tampon
             solarisé pour le mois j, voir paragraphe 18.5

        □    T : Coefficient représentant la transparence de l’espace tampon solarisé. Il correspond à l’atténuation
             du rayonnement solaire arrivant directement dans le logement par la traversée de l’espace tampon
             solarisé. Il prend les valeurs du tableau suivant selon les caractéristiques des parois vitrées de l’espace
             tampon solarisé :

                         Menuiserie               Type de Vitrage            Transparence T
                                           Simple vitrage                         0,62
                                           Double vitrage                         0,55
                      Bois / Bois métal    Double vitrage peu émissif             0,48
                                           Triple vitrage                         0,49
                                           Triple vitrage peu émissif             0,44
                                           Simple vitrage                         0,5
                                           Double vitrage                         0,45
                             PVC
                                           Double vitrage peu émissif             0,39
                                           Triple vitrage                         0,4

                                          Triple vitrage peu émissif                0,36
                                          Simple vitrage                            0,63
                                          Double vitrage                            0,56
                       Métal avec rupture
                                          Double vitrage peu émissif                0,48
                       de pont thermique
                                          Triple vitrage                            0,5
                                          Triple vitrage peu émissif                0,45
                                          Simple vitrage                            0,64
                                          Double vitrage                            0,58
                             Métal        Double vitrage peu émissif                0,5
                                          Triple vitrage                            0,52
                                          Triple vitrage peu émissif                0,47

               Pour les parois en polycarbonate, on prendra T = 0,4.

               Dans le cas où les vitrages séparant l’espace tampon solarisé de l’extérieur sont hétérogènes, le
               coefficient T est celui du vitrage majoritaire. Dans le cas où aucun vitrage n’est majoritaire, le
               coefficient T est proratisé à la surface.

          bver : Coefficient de réduction des déperditions (voir partie 3.1)

          Ssindj : Surface sud équivalente représentant l’impact des apports solaires associés au rayonnement
           solaire entrant dans la partie habitable du logement après de multiples réflexions dans l’espace tampon
           solarisé (apports indirects) pour le mois j.

           La surface sud équivalente représentant les apports solaires indirects dans le logement pour le mois j
           Ssindj, correspond à la surface sud équivalente des apports totaux dans la véranda Sstj, à laquelle il faut
           déduire celle des apports directs Ssdj :

                                                𝑆𝑠𝑖𝑛𝑑𝑗 = 𝑆𝑠𝑡𝑗 − 𝑆𝑠𝑑𝑗

                                  𝑆𝑠𝑡𝑗 = ∑ 𝐴𝑘 ∗ (0,8 ∗ 𝑇 + 0,024) ∗ 𝐹𝑒𝑘 ∗ 𝐶1𝑘,𝑗
                                            𝑘

           Avec :

           □   𝐴𝑘 : Surface de la baie k séparant la véranda de l’extérieur (m²)

           □   𝑇 : Coefficient de transparence des baies séparant la véranda de l’extérieur (m²)
           □   𝐹𝑒𝑘 : Facteur d'ensoleillement, qui traduit la réduction d'énergie solaire reçue par la baie k du fait des
               masques lointains. Pour les espaces tampons solarisés, 𝐹𝑒𝑘 = 1 car l’impact des masques sera négligé.

           □   𝐶1𝑘,𝑗 : Coefficient d’orientation et d’inclinaison de la baie k séparant la véranda de l’extérieur pour le
               mois j, voir paragraphe 18.5

Les grandes surfaces vitrées séparant la véranda de l’extérieur seront traitées comme des portes-fenêtres avec des
menuiseries au nu extérieur.
```

## TODO digitalisation

### Balises XML produites par cette section
- `bver`
- `coef_transparence_ets`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
