---
section_id: "3.2.2"
title: "Calcul des Uplancher bas (Upb)"
spec_pages: [17-19]
xml_outputs: ["upb", "upb0", "upb_final"]
tables: ["tv_upb_id", "tv_upb0_id"]
depends_on: ["3.1"]
status: "verbatim"
---

# §3.2.2 — Calcul des Uplancher bas (Upb)

> Source : `resources/spec.pdf` p.17-19
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.2.2 Calcul des Uplancher bas (Upb)
3.2.2.1 Schéma du calcul de Upb
Si le plancher donne sur l’extérieur ou un local non chauffé (hors sous-sol) :


                                        Upb


                             Oui                                                                Upb_tab :
            Saisir Upb                 Connu ?




                                                                                                                Année de construction ou d'isolation
                                     Non

                                   Type de plancher
                                                                                               ≤74 ou inconnu
                                                                                                    75-77
                                                                                                    78-82
                                                                                                    83-88
                                                       Non                                          89-00
                                       Connu ?                     Upb0 = 2                         01-05
                                                                                                    06-12
                                                                                                     ≥13
                                      Oui

                                        Upb0

                                                                                               Effet joule
                                                                                                    2
                                      Non                             Inconnue      Upb =          0,9
                                                                                                   0,8
             Upb = Upb0                              Isolation ?                  min(Upb0 ;      0,55
                                                                                                  0,55
                                                                                                   0,3
                                                                                  Upb_tab)        0,27
                                                                                                  0,23
                                                                                                                                        H1
                                                    Oui
                                                                                               Autres
                                                                                                  2
                                                                                                 0,9
                                                                                                 0,9
                                                                                                 0,8
                                              Résistance isolant                                 0,5
                                                                                                 0,3
                                                                                                0,27
                                                                                                0,23

                                                                                               Effet joule
                         1                                                                          2
                                                                                                  0,95
         Upb =                              Oui                                                   0,84
                  1                                   Connu ?                                     0,58
                      + R isolant                                                                 0,58
                 Upb0                                                                              0,3
                                                                                                  0,27
                                                                                                  0,23
                                                    Non                                                                                 H2
                                                                                               Autres
                                                                                                  2
                                                                                                0,95
                                                                                                0,95
                                                  Epaisseur isolant                             0,74
                                                                                                0,63
                                                                                                 0,3
                                                                                                0,27
                                                                                                0,23

                                                                                               Effet joule
                      1                     Oui
                                                                                                    2
                                                                                                    1
          Upb =                                                                                   0,89
                   1     e                            Connu ?                                     0,78
                      +                                                                            0,5
                  Upb0 0,042                                                                      0,47
                                                                                                   0,4
                                                                                                  0,25
                                                    Non                                                                                 H3
                                                                                               Autres
                                                                                                  2
                                                                                                  1
                                                  Année d’isolation
                                                                                                  1
                                                                                                0,89
                                                                                                0,56
                                                                                                0,47
                                                                                                 0,4
                                                                                                0,25

                                                                                   Upb =
                                                                                 min(Upb0 ;
                                                      Connu ?          Oui
                                                                                 Upb_tab)
                                                    Non

                         Si Année de construction ≤74 alors Année d’isolation = 75-77

                                    Sinon Année d’isolation = Année de construction




Pour les vides sanitaires, les sous-sol non chauffés et terre-plein, le calcul des déperditions se fait avec un coefficient
Ue en remplacement de Upb. Le calcul de Upb est toutefois nécessaire pour obtenir la valeur du coefficient Ue, selon
les tableaux ci-dessous.

Upb est le coefficient de transmission thermique de la partie du plancher située entre l’ambiance intérieure et le vide
sanitaire, le sous-sol ou le terre-plein. Il est calculé selon le schéma précédent (voir « plancher donnant sur l’extérieur
ou un local non chauffé »), en W/(m2.K).

Les données ne figurant pas dans le tableau peuvent être obtenues par interpolation et extrapolation en traçant des
droites entre les valeurs les plus proches présentes dans le tableau.

    -   P : périmètre ou linéaire du plancher déperditif du bâtiment ou du lot sur terre-plein, vide sanitaire ou sous-
        sol non chauffé donnant sur l’extérieur ou un local non chauffé (m)

    -   S : surface du plancher du bâtiment ou du lot sur terre-plein, vide sanitaire ou sous-sol non chauffé (m²)

    -   2S/P est arrondi à l’entier le plus proche

Le Ue d'un plancher est un Umoyen pour tout le plancher du bâtiment. Il prend en compte l’isolation périphérique du
plancher bas. Dès lors, tous les appartements d'un immeuble donnant sur un même terre-plein ont le même Ue.

Le Ue d’un plancher bas d’immeuble est toujours calculé à l’immeuble, même dans le cas d’un DPE seulement sur un
appartement.

Valeurs de Ue (W/(m².K)) selon Upb et 2S/P :

Si le plancher est sur vide sanitaire ou sous-sol non chauffé :

                            Upb
                                  3,33     1,43      0,83     0,45     0,41      0,37     0,34     0,31
                2S/P
                       3          0,45     0,42      0,39     0,36     0,33      0,3      0,28     0,26
                       4          0,43     0,4       0,37     0,34     0,31      0,29     0,27     0,25
                       5          0,38     0,36      0,34     0,32     0,3       0,28     0,26     0,25
                       6          0,37     0,35      0,33     0,31     0,29      0,27     0,25     0,24
                       7          0,36     0,34      0,32     0,3      0,28      0,26     0,24     0,23
                       8          0,35     0,33      0,31     0,29     0,27      0,25     0,24     0,22
                       9          0,34     0,32      0,3      0,28     0,26      0,24     0,23     0,22
                       10         0,33     0,31      0,29     0,27     0,25      0,24     0,22     0,21
                       12         0,28     0,27      0,26     0,25     0,24      0,22     0,21     0,2
                       14         0,28     0,27      0,26     0,24     0,23      0,21     0,2      0,19
                       16         0,28     0,27      0,25     0,23     0,21      0,2      0,19     0,18
                       18         0,28     0,26      0,24     0,22     0,2       0,19     0,19     0,18
                       20         0,24     0,23      0,22     0,21     0,2       0,19     0,18     0,17




Si le plancher donne sur terre-plein :

       Bâtiment construit avant 2001

                          Upb
                                3,4              1,5           0,85     0,59           0,46
                 2S/P
                 3              0,78             0,56          0,43     0,35           0,3
                 4              0,68             0,51          0,4      0,33           0,28
                 5              0,6              0,46          0,38     0,32           0,27
                 6              0,54             0,43          0,35     0,3            0,26
                 7              0,49             0,39          0,33     0,28           0,25
                 8              0,45             0,37          0,31     0,27           0,24
                 9              0,42             0,34          0,29     0,26           0,23
                 10             0,39             0,32          0,28     0,24           0,22
                 12             0,35             0,29          0,25     0,22           0,2
                 14             0,31             0,26          0,23     0,2            0,19
                 16             0,28             0,24          0,21     0,19           0,17
                 18             0,26             0,22          0,2      0,18           0,16
                 20             0,24             0,21          0,18     0,17           0,15


       Bâtiments à partir de 2001

                          Upb
                                3,4       1,5           0,85    0,6    0,46     0,37          0,31
                2S/P
                3               0,7       0,6           0,49    0,39   0,33     0,28          0,25
                4               0,65      0,55          0,45    0,36   0,31     0,26          0,23
                5               0,58      0,5           0,42    0,34   0,29     0,25          0,22
                6               0,52      0,45          0,38    0,32   0,27     0,24          0,21
                7               0,48      0,42          0,36    0,3    0,26     0,23          0,2
                8               0,45      0,39          0,33    0,28   0,25     0,22          0,2
                9               0,39      0,35          0,31    0,27   0,24     0,21          0,19
                10              0,38      0,34          0,3     0,26   0,23     0,2           0,18
                12              0,35      0,31          0,27    0,23   0,21     0,19          0,17
                14              0,3       0,27          0,24    0,21   0,19     0,17          0,16
                16              0,26      0,24          0,22    0,2    0,18     0,16          0,15
                18              0,25      0,24          0,21    0,18   0,17     0,15          0,14
                20              0,23      0,21          0,19    0,17   0,16     0,14          0,13
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_upb_id`
- [ ] `tv_upb0_id`

### Balises XML produites par cette section
- `upb`
- `upb0`
- `upb_final`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
