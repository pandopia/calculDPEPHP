---
section_id: "12.4"
title: "Rendement de génération hors combustion (effet Joule, PAC)"
spec_pages: [76-78]
xml_outputs: ["rendement_generation", "scop", "seer"]
tables: ["tv_scop_id", "tv_seer_id"]
depends_on: ["12"]
status: "verbatim"
---

# §12.4 — Rendement de génération hors combustion (effet Joule, PAC)

> Source : `resources/spec.pdf` p.76-78
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
12.4 Rendement de génération des générateurs autres qu’à combustion
12.4.1 Générateurs à effet joule et réseaux de chaleur
                                                 Type de générateur          Rg
                                          Générateur à effet joule direct     1
                                          Chaudières électriques            0,97
                                          Réseau de chaleur                 0,97

Un chauffe-eau électrique instantané est assimilé à un ballon électrique au niveau du modèle mais sans les pertes de
stockage.

La modélisation pour les chaudières électriques mixtes (chauffage et ECS) est identique à celle d'une chaudière
électrique et d'un ballon électrique selon qu'il y ait stockage ou pas.

12.4.2 Pompe à Chaleur
Les performances des PAC sont définies par leur SCOP qui dépend de leur type et de la zone climatique.

Le SCOP réel de la PAC peut être saisi directement quand il est connu et justifié. A défaut de disposer des perfomances
réelles des PAC, les valeurs par défaut tabulées ci-dessous sont utilisables.

                                                              Zone H1 et H2
       Type de PAC
                         Type d’émetteur        Avant 2008*       2008-2014        2015-2016      A partir de 2017
                             Autres                 2,2               2,4             2,6                2,8
       PAC Air/Eau
                       Planchers / Plafonds         2,4               2,6             2,9                3,2
                              Autres                2,2               2,4             2,7                3
       PAC Eau/Eau
                       Planchers / Plafonds         2,4               2,6              3                 3,3
          PAC Eau             Autres                2,2               2,4             2,7                3
       glycolée/Eau    Planchers / Plafonds         2,4               2,6              3                 3,3
                              Autres                2,2               2,4             2,7                3
   PAC Géothermie
                       Planchers / Plafonds         2,4               2,6              3                 3,3



                                                                Zone H3
       Type de PAC
                          Type d’émetteur        Avant 2008*      2008-2014       2015-2016       A partir de 2017
                              Autres                 2,5              2,8              3                 3,2
       PAC Air/Eau
                        Planchers / Plafonds          2,9             3,1             3,5                3,8
                               Autres                 2,5             2,8             3,1                3,5
       PAC Eau/Eau
                        Planchers / Plafonds          2,9             3,1             3,6                4
          PAC Eau              Autres                 2,5             2,8             3,1                3,5
       glycolée/Eau     Planchers / Plafonds          2,9             3,1             3,6                4
                               Autres                 2,5             2,8             3,1                3,5
   PAC Géothermie
                        Planchers / Plafonds          2,9             3,1             3,6                4



                                                    Zone H1 et H2
                           Type de PAC         Avant 2008*    2008-2014       A partir de 2015
                           PAC Air/Air             2,2            2,3                 3



                                                       Zone H3
                           Type de PAC         Avant 2008*    2008-2014       A partir de 2015
                           PAC Air/Air             2,4           2,6                 3,3
*COP

L’inverse du rendement de l’installation s’exprimera alors comme :

                                                           1
                                            𝐼𝑐ℎ = (                    )
                                                   𝑆𝐶𝑂𝑃 ∗ 𝑅𝑒 ∗ 𝑅𝑑 ∗ 𝑅𝑟

Dans le cas où plusieurs émetteurs sont reliés à la PAC, le COP le plus défavorable sera pris pour le calcul d’Ich.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_scop_id`
- [ ] `tv_seer_id`

### Balises XML produites par cette section
- `rendement_generation`
- `scop`
- `seer`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
