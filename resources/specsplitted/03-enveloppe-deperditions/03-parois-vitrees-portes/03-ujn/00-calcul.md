---
section_id: "3.3.3"
title: "Coefficients Ujn des fenêtres/portes-fenêtres"
spec_pages: [30-32]
xml_outputs: ["ujn"]
tables: ["tv_ujn_id", "tv_deltar_id"]
depends_on: ["3.3.2"]
status: "verbatim"
---

# §3.3.3 — Coefficients Ujn des fenêtres/portes-fenêtres

> Source : `resources/spec.pdf` p.30-32
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.3.3 Coefficients Ujn des fenêtres/portes-fenêtres
La présence de volets aux fenêtres et portes-fenêtres leur apporte un supplément d’isolation avec une résistance
additionnelle ΔR.

                                                 Fermetures                                                      ΔR (m2.K/W)
        Jalousie accordéon, fermeture à lames orientables y compris les vénitiens
                                                                                                                      0,08
        extérieurs tout métal, volets battants ou persiennes avec ajours fixes
        Fermeture sans ajours en position déployée, volets roulants alu                                               0,15
        Volets roulants PVC ou bois (e ≤ 12 mm)                                                                       0,19
        Persienne coulissante et volet battant PVC ou bois (e ≤ 22 mm)                                                0,19
        Volets roulants PVC ou bois (e > 12 mm)                                                                       0,25
        Persienne coulissante et volet battant PVC ou bois (e > 22 mm)                                                0,25
        Fermeture isolée sans ajours en position déployée                                                             0,25
        Note : e est l’épaisseur du tablier.



Dans la suite, les Ujn associés à des Uw non présents dans les tableaux peuvent être obtenus par interpolation ou
extrapolation avec les deux Uw tabulés les plus proches.

                                               Ujn pour une valeur de résistance supplémentaire ΔR (en m².K/W) de :
                              Uw
                                                 0,08                0,15          0,19                  0,25
                            W/(m2.K)
                              0,8                0,8                 0,8            0,7                   0,7
                               0,9               0,9                 0,8            0,8                   0,8
                               1                 1,0                 0,9            0,9                   0,9
                               1,1               1,1                 1,0            1,0                   1,0
                               1,2               1,1                 1,1            1,1                   1,1
                               1,3               1,2                 1,2            1,2                   1,1
                               1,4               1,3                 1,3            1,3                   1,2
                               1,5               1,4                 1,4            1,3                   1,3
                               1,6               1,5                 1,5            1,4                   1,4

1,7   1,6         1,5            1,5               1,4
1,8   1,7         1,6            1,6               1,5
1,9   1,8         1,7            1,6               1,6
2     1,9         1,8            1,7               1,7
2,1   1,9         1,9            1,8               1,7
2,2   2,0         1,9            1,9               1,8
2,3   2,1         2,0            2,0               1,9
2,4   2,2         2,1            2,0               2,0
2,5   2,3         2,2            2,1               2,0
2,6   2,4         2,3            2,2               2,1
2,7   2,5         2,3            2,2               2,2
2,8   2,5         2,4            2,3               2,2
2,9   2,6         2,5            2,4               2,3
3     2,7         2,6            2,5               2,4
3,1   2,8         2,6            2,5               2,4
3,2   2,9         2,7            2,6               2,5
3,3   3,0         2,8            2,7               2,6
3,4   3,0         2,9            2,7               2,6
3,5   3,1         2,9            2,8               2,7
3,6   3,2         3,0            2,9               2,7
3,7   3,3         3,1            2,9               2,8
3,8   3,4         3,1            3,0               2,9
3,9   3,4         3,2            3,1               2,9
4     3,5         3,3            3,1               3,0
4,1   3,6         3,4            3,2               3,1
4,2   3,7         3,4            3,3               3,1
4,3   3,7         3,5            3,3               3,2
4,4   3,8         3,6            3,4               3,2
4,5   3,9         3,6            3,5               3,3
4,6   4,0         3,7            3,5               3,4
4,7   4,1         3,8            3,6               3,4
4,8   4,1         3,8            3,7               3,5
4,9   4,2         3,9            3,7               3,6
5     4,3         4,0            3,8               3,6
5,1   4,4         4,0            3,8               3,7
5,2   4,4         4,1            3,9               3,7
5,3   4,5         4,2            4,0               3,8
5,4   4,6         4,2            4,0               3,8
5,5   4,7         4,3            4,1               3,9
5,6   4,7         4,4            4,2               4,0
5,7   4,8         4,4            4,2               4,0
5,8   4,9         4,5            4,3               4,1
5,9   5,0         4,6            4,3               4,1
6     5,0         4,6            4,4               4,2
6,1   5,1         4,7            4,5               4,3
6,2   5,2         4,8            4,5               4,3
6,3   5,2         4,8            4,6               4,4
6,4   5,3         4,9            4,6               4,4

                            6,5           5,4           5,0               4,7               4,5
                            6,6           5,5           5,0               4,8               4,5

Si le Ujn d’une menuiserie est connu et justifié, le saisir directement.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_ujn_id`
- [ ] `tv_deltar_id`

### Balises XML produites par cette section
- `ujn`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
