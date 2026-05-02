---
section_id: "3.2.1.2"
title: "Calcul des Umur0"
spec_pages: [14-16]
xml_outputs: ["umur0"]
tables: ["tv_umur0_id"]
depends_on: ["3.2.1"]
status: "verbatim"
---

# §3.2.1.2 — Calcul des Umur0

> Source : `resources/spec.pdf` p.14-16
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.2.1.2 Calcul des Umur0

Umur0 est le coefficient de transmission thermique du mur non isolé (W/(m².K)).

                        Epaisseur (en cm)                                       ≤ 20        25       30         35       40         45         50          55         60      65          70       75    ≥ 80


                                           Murs constitués d’un
   Murs en pierre de taille et                                                  3,2        2,85 2,65 2,45                2,3       2,15 2,05           1,90       1,80 1,75           1,65        1,55   1,50
                                          seul matériau / inconnu
moellons (granit, gneiss, porphyres,
 pierres calcaires, grès, meulières,
                                          Murs avec remplissage
   schistes, pierres volcaniques)                                                -          -         -          -        -          -        1,90     1,75       1,60 1,50           1,45        1,30   1,25
                                                tout venant




                              Epaisseur connue (en cm)                    ≤ 40          45           50             55        60          65         70          75        ≥ 80


                          Murs en pisé ou béton de terre
                                                                          1,75         1,65         1,55        1,45      1,35            1,25       1,2        1,15       1,1
                                         stabilisé




                                    Epaisseur connue (en cm)                                              ≤8             10               13               18              24             ≥ 32


                                                 Sans remplissage tout venant                              3             2,7             2,35          1,98                1,65           1,35
              Murs en pans de bois
                                                Avec remplissage tout venant                                                                         1,7




                                               Epaisseur connue (en cm)                     ≤ 10               15         20             ≥ 25


                                                     Murs bois (rondins)                    1,6            1,2           0,95             0,8




           Epaisseur connue (en cm)                  ≤9             12        15            19             23            28               34           45              55            60          ≥ 70


       Murs en briques pleines simples               3,9        3,45       3,05            2,75            2,5           2,25              2          1,65             1,45        1,35          1,2




                                    Epaisseur connue (en cm)                                ≤ 20               25         30             35           45          50          ≥ 60


                     Murs en briques pleines doubles avec lame d’air                            2          1,85          1,65            1,55        1,35        1,25         1,2




                      Epaisseur connue (en cm)              ≤ 15         18            20           23              25         28              33           38             ≥ 43


                       Murs en briques creuses              2,15         2,05          2            1,85         1,7          1,68         1,65            1,55            1,4




                        Epaisseur connue                     ≤ 20          23              25             28             30               33               35           38         ≥ 40


                 Murs en blocs de béton pleins               2,9          2,75             2,6            2,5            2,4              2,3          2,2             2,1         2,05



                                   Epaisseur connue (en cm)               ≤ 20              23                   ≥ 25


                                 Murs en blocs de béton creux             2,8               2,65                 2,3




                       Epaisseur connue (en cm)        ≤ 20        22,5          25       28         30          35           40       ≥ 45


                        Murs en béton banché           2,9         2,75      2,65         2,5        2,4         2,2          2,05     1,9

                      Murs en béton de mâchefer        2,75        2,5           2,4      2,25       2,15        1,95         1,8          -




                                    Epaisseur connue (en cm)                    30                    37,5

                                   Brique terre cuite alvéolaire            0,47                      0,40




                                                              Mur en béton cellulaire

  Epaisseur (cm)          15       17,5      20       22,5          25           27,5           30          32,5          35           37,5

Construction < 2013      0,90      0,79     0,70      0,63         0,57          0,53        0,49           0,45          0,42         0,40

Construction ≥ 2013      0,69      0,60     0,53      0,48         0,43          0,40        0,36           0,30          0,28         0,22




                                          Epaisseur connue (en cm)                                         ≤15           20         ≥ 25


                        Murs sandwich béton/isolant/béton (sans isolation rapportée)                       0,9         0,48         0,45




                         Epaisseur connue (en cm)                    10              15     20         25          30          35          40   ≥ 45


                   Murs en ossature bois avec isolant en                                   0,26                                                 0,11
                                                                    0,45          0,35                0,21        0,17        0,15     0,13
                            remplissage ≥ 2006




                         Epaisseur connue (en cm)                    10              15     20         25          30          35          40   ≥ 45


                   Murs en ossature bois avec isolant en
                                                                    0,52          0,41      0,3       0,24         0,2        0,17     0,15 0,13
                          remplissage 2001-2005




                         Epaisseur connue (en cm)                    10              15     20         25          30          35          40   ≥ 45


                   Murs en ossature bois avec isolant en
                                                                    0,65          0,45     0,34       0,28        0,23         0,2     0,18 0,16
                            remplissage <2001




                                Epaisseur connue (en cm)                ≤8       10    13         18     24     ≥ 32


                      Murs en ossature bois avec remplissage tout
                                                                                            1,7
                                        venant




                                Epaisseur connue (en cm)               ≤8        10    13         18     24     ≥ 32

                        Murs en ossature bois sans remplissage          3        2,7   2,35       1,98   1,65   1,35


Cloison de plâtre : Umur0 = 3,33 W/(m².K)

Pour les parois dites « anciennes », c’est-à-dire constituées de matériaux traditionnels à savoir pierres, terre, mur à
colombage, brique ancienne, la présence d’un enduit isolant n’est pas considérée comme une isolation. Cependant,
cet enduit apporte une correction d’isolation qu’il faut prendre en compte en considérant :
                                                                𝟏
                                        𝑼𝒎𝒖𝒓𝟎 =
                                                            𝟏
                                                                      + 𝑹𝒆𝒏𝒅𝒖𝒊𝒕
                                                     𝑼𝒎𝒖𝒓𝟎_𝒔𝒂𝒏𝒔𝑬𝒏𝒅𝒖𝒊𝒕

Avec :

    -    Renduit = 0,7 m².K/W



Pour l’ensemble des parois, la présence d’un doublage apporte une résistance thermique supplémentaire calculée
comme suit :

                                                                             1
                                           𝑼𝒎𝒖𝒓𝒅𝒐𝒖𝒃𝒍𝒂𝒈𝒆 =
                                                                      1
                                                                    𝑈𝑚𝑢𝑟0 + 𝑅𝑑𝑜𝑢𝑏𝑙𝑎𝑔𝑒



Avec les valeurs de résistances suivantes :

- Pour un mur avec un doublage rapporté de nature indéterminée ou avec lame d’air de moins de 15 mm : Rdoublage =
0,1 m².K/W

- Pour un mur avec un doublage rapporté avec une lame d’air de plus de 15 mm ou avec un matériau de doublage
connu (plâtre, brique, bois) : Rdoublage = 0,21 m².K/W

Les murs en pavés de verre sont traités comme des parois vitrées (voir paragraphe 3.3).

Pour les murs non répertoriés, saisir directement les coefficients de transmission thermique quand ils sont justifiés.

Pour les murs doubles ou de composants multiples connus et justifiés, saisir directement le U du mur calculé.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_umur0_id`

### Balises XML produites par cette section
- `umur0`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
