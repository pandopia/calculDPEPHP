---
section_id: "3.3.1"
title: "Détermination de la performance du vitrage Ug"
spec_pages: [23-25]
xml_outputs: ["ug"]
tables: ["tv_ug_id"]
depends_on: ["3.3"]
status: "verbatim"
---

# §3.3.1 — Détermination de la performance du vitrage Ug

> Source : `resources/spec.pdf` p.23-25
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.3.1 Détermination de la performance du vitrage Ug
        Simple vitrage et survitrage

Pour un simple vitrage, quelle que soit l’épaisseur du verre, prendre :

    -    Ug = 5,8 W/(m².K) pour un vitrage vertical ou horizontal

Le Ug d’un survitrage est déterminé en apportant une majoration de 0,1 W/(m².K) au Ug du double vitrage rempli à
l’air sec ayant la même épaisseur de lame d’air. Les épaisseurs des lames d’air pour le survitrage sont plafonnées à
20mm. C'est-à-dire que toute lame d’air d’un survitrage d’épaisseur supérieure à 20mm sera traitée dans les calculs
comme une lame d’air de 20mm d’épaisseur.

      -            Double vitrage vertical

                          Remplissage air sec ou inconnu                                     Remplissage Argon ou Krypton

                             Remplissage air sec                                                Remplissage argon ou krypton

Epaisseur de lame                            Ug W/(m².K)                     Epaisseur de                             Ug W/(m².K)
   d’air (mm)              Vitrages non traités    Vitrages peu émissifs   lame d’air (mm)     Vitrages non traités         Vitrages peu émissifs
          6                        3,3                      2,45                  6                     3                            2
          8                        3,1                      2,1                   8                    2,9                          1,7
       10                          2,9                      1,8                  10                    2,8                          1,4
       12                          2,8                      1,6                  12                    2,7                          1,3
       14                          2,8                      1,5                  14                    2,6                          1,2
       15                          2,7                      1,4                  15                    2,6                          1,1
       16                          2,7                      1,4                  16                    2,6                          1,1
       18                          2,7                      1,4                  18                    2,6                          1,1
       20                          2,7                      1,4                  20                    2,6                          1,1



      -            Double vitrage horizontal

                      Remplissage air sec ou inconnu                                             Remplissage Argon ou Krypton

                             Remplissage air sec                                               Remplissage argon ou krypton
 Epaisseur de lame                          Ug W/(m².K)                    Epaisseur de lame                          Ug W/(m².K)
    d’air (mm)              Vitrages non traités   Vitrages peu émissifs      d’air (mm)          Vitrages non traités       Vitrages peu émissifs
              6                      3,7                   2,6                    6                          3,3                     2,1
              8                      3,4                   2,2                    8                          3,2                     1,8
              10                     3,2                   1,9                    10                         3,1                     1,5
              12                     3,1                   1,7                    12                         2,9                     1,4
              14                     3,1                   1,6                    14                         2,8                     1,2
              15                     2,9                   1,5                    15                         2,8                     1,1
              16                     2,9                   1,5                    16                         2,8                     1,1
              18                     2,9                   1,5                    18                         2,8                     1,1
              20                     2,9                   1,5                    20                         2,8                     1,1


  Attention : si la valeur de l’épaisseur de la lame d’air n’est pas dans le tableau présenté, prendre la valeur directement
  inférieure qui s’y trouve.




    -        Triple vitrage vertical

             Remplissage air sec ou inconnu                                       Remplissage Argon ou Krypton

                        Remplissage air sec                                               Remplissage argon ou krypton

Epaisseur de lame                      Ug W/(m².K)                    Epaisseur de lame                      Ug W/(m².K)
   d’air (mm)         Vitrages non traités    Vitrages peu émissifs      d’air (mm)           Vitrages non traités   Vitrages peu émissifs
        6                     2,3                      1,7                   6                        2,1                     1,5
        8                     2,1                      1,4                   8                        1,9                     1,2
        10                    2,0                      1,2                   10                       1,8                     1,0
        12                    1,9                      1,1                   12                       1,8                     0,9
        14                    1,8                      1,0                   14                       1,7                     0,8
        15                    1,8                      0,9                   15                       1,7                     0,7
        16                    1,8                      0,9                   16                       1,7                     0,7
        18                    1,7                      0,8                   18                       1,6                     0,6
        20                    1,7                      0,8                   20                       1,6                     0,6



    -        Triple vitrage horizontal

             Remplissage air sec ou inconnu                                       Remplissage Argon ou Krypton

                        Remplissage air sec                                               Remplissage argon ou krypton
Epaisseur d’une                        Ug W/(m².K)                     Epaisseur d’une                        Ug W/(m².K)
lame d’air (mm)       Vitrages non traités    Vitrages peu émissifs    lame d’air (mm)        Vitrages non traités   Vitrages peu émissifs
        6                     2,5                     1,8                     6                        2,2                   1,6
        8                     2,2                     1,5                     8                        2,0                   1,2
        10                    2,1                     1,2                    10                        1,9                   1,0
        12                    2,0                     1,1                    12                        1,9                   0,9
        14                    1,9                     1,0                    14                        1,8                   0,8
        15                    1,9                     0,9                    15                        1,8                   0,7
        16                    1,9                     0,9                    16                        1,8                   0,7
        18                    1,8                     0,8                    18                        1,7                   0,6
        20                    1,8                     0,8                    20                        1,7                   0,6


Attention : Si un triple vitrage a des épaisseurs de lame d’air différentes, considérer que c’est un triple vitrage dont
l’épaisseur de chaque lame d’air est la moitié de l’épaisseur totale des deux lames d’air (ou la valeur consignée dans
les tableaux précédents la plus proche de la moitié de l’épaisseur).

Exemple : pour un triple vitrage 4/10/4/12/4, considérer que c’est équivalent à un 4/10/4/10/4.

Par défaut, les doubles et triples vitrages installés à partir de 2006 sont tous considérés remplis à l’Argon ou au
Krypton.

Si le Ug d’un vitrage est connu et justifié, le saisir directement.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_ug_id`

### Balises XML produites par cette section
- `ug`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
