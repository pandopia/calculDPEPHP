---
section_id: "6.2.1"
title: "Détermination du facteur solaire"
spec_pages: [45-47]
xml_outputs: ["sw"]
tables: ["tv_sw_id"]
depends_on: ["6.2"]
status: "verbatim"
---

# §6.2.1 — Détermination du facteur solaire

> Source : `resources/spec.pdf` p.45-47
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
6.2.1 Détermination du facteur solaire

La proportion d’énergie solaire incidente qui pénètre dans le logement à travers une paroi est donnée par :

    -    Pour les parois en polycarbonate : Sw = 0,4
    -    Pour les parois en brique de verre pleine ou creuse : Sw = 0,4

    -    Pour les doubles-fenêtres composées de deux fenêtres de facteur solaire Sw1 et Sw2, le facteur solaire de la
         double-fenêtre est : Sw = Sw1 ∗ Sw2
    -    Dans le cas d’un survitrage, on prendra en compte le Sw du double vitrage équivalent.
Si les facteurs solaires des menuiseries sont connus et justifiés, les saisir directement. Sinon, les tableaux suivants
donnent des valeurs par défaut des facteurs solaires en fonction des caractéristiques des menuiseries :

                              Sw                                     Fenêtre ou porte-fenêtre au nu extérieur
                                                                                                             Triple
                                                               Simple     Double      Double       Triple
   Menuiserie                   Type de fenêtre                                                             vitrage
                                                               vitrage    vitrage   vitrage VIR   vitrage
                                                                                                              VIR

                               Fenêtre battante                  0,58      0,52        0,45        0,46       0,41
                              Fenêtre coulissante                0,58      0,52        0,45        0,46       0,41
   Bois / Bois-      Porte-fenêtre battante ou coulissante
                                                                 0,62      0,55        0,48        0,49       0,44
     métal                    sans soubassement
                          Porte-fenêtre battante avec
                                                                 0,53      0,48        0,41        0,42       0,38
                                 soubassement

                               Fenêtre battante                  0,54      0,48        0,42        0,43       0,39
                          Porte-fenêtre battante sans
                                                                 0,57      0,51        0,44        0,45       0,40
                                 soubassement
        PVC               Porte-fenêtre battante avec
                                                                 0,50      0,45        0,39        0,40       0,36
                                 soubassement
                              Fenêtre coulissante                0,60      0,54        0,46        0,47       0,43
                           Porte-fenêtre coulissante             0,64      0,57        0,49        0,51       0,45

                               Fenêtre battante                  0,59      0,53        0,46        0,47       0,42
   Métal avec
                            Porte-fenêtre battante               0,63      0,56        0,48        0,50       0,45
   rupture de
                             Fenêtre coulissante                 0,65      0,58        0,50        0,52       0,46
 pont thermique
                           Porte-fenêtre coulissante             0,70      0,62        0,54        0,55       0,50

                               Fenêtre battante                  0,61      0,55        0,48        0,49       0,44
                            Porte-fenêtre battante               0,64      0,58        0,50        0,52       0,47
        Métal
                             Fenêtre coulissante                 0,67      0,60        0,52        0,53       0,48
                           Porte-fenêtre coulissante             0,71      0,64        0,55        0,56       0,51




                                                                      Fenêtre ou porte-fenêtre au nu intérieur ou en
                                 Sw
                                                                                          tunnel
                                                                                        Double
                                                                      Simple Double               Triple    Triple
 Menuiserie                      Type de fenêtre                                        vitrage
                                                                      vitrage vitrage            vitrage vitrage VIR
                                                                                          VIR

                               Fenêtre battante                         0,52     0,47      0,40       0,41      0,37
                              Fenêtre coulissante                       0,52     0,47      0,40       0,41      0,37
 Bois / Bois-
                   Porte-fenêtre battante ou coulissante sans
   métal                                                                0,56     0,50      0,43       0,44      0,40
                                 soubassement
                   Porte-fenêtre battante avec soubassement             0,48     0,43      0,37       0,38      0,34

                               Fenêtre battante                         0,49     0,44      0,38       0,39      0,35
                   Porte-fenêtre battante sans soubassement             0,51     0,46      0,39       0,40      0,36
     PVC           Porte-fenêtre battante avec soubassement             0,45     0,40      0,35       0,36      0,32
                              Fenêtre coulissante                       0,54     0,48      0,41       0,43      0,38
                           Porte-fenêtre coulissante                    0,57     0,51      0,44       0,45      0,41


 Métal avec                     Fenêtre battante                        0,53     0,48      0,41       0,42      0,38
 rupture de                  Porte-fenêtre battante                     0,56     0,51      0,44       0,45      0,40
    pont                      Fenêtre coulissante                       0,58     0,52      0,45       0,46      0,42
 thermique                  Porte-fenêtre coulissante                   0,63     0,56      0,48       0,50      0,45

                                Fenêtre battante                        0,55     0,49      0,43       0,44      0,40
                             Porte-fenêtre battante                     0,58     0,52      0,45       0,46      0,42
    Métal
                              Fenêtre coulissante                       0,60     0,54      0,47       0,48      0,43
                            Porte-fenêtre coulissante                   0,64     0,57      0,49       0,51      0,46
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_sw_id`

### Balises XML produites par cette section
- `sw`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
