---
section_id: "6.2.2.1"
title: "Masques proches (Fe1)"
spec_pages: [48-49]
xml_outputs: ["fe1"]
tables: ["tv_coef_masque_proche_id"]
depends_on: ["6.2.2"]
status: "verbatim"
---

# §6.2.2.1 — Masques proches (Fe1)

> Source : `resources/spec.pdf` p.48-49
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
6.2.2.1 Masques proches

6.2.2.1.1 Baie en fond de balcon ou fond et flanc de loggias




                                                 Configuration du masque

Le tableau ci-dessous donne les valeurs de Fe1 en fonction de l'orientation de la façade et de l'avancée I de la loggia
ou du balcon :

                                                        Orientation de la façade
                            Avancée l (m)          Nord           Sud         Est ou Ouest
                                <1                  0,4           0,5             0,45
                              1 ≤…< 2               0,3           0,4             0,35
                              2 ≤…< 3               0,2           0,3             0,25
                                3≤                  0,1           0,2             0,15

L’orientation Nord va du Nord-Est au Nord-Ouest bornes comprises.

L’orientation Sud va du Sud-Est au Sud-Ouest bornes comprises.

L’orientation Est va du Nord-Est au Sud-Est bornes exclues.

L’orientation Ouest va du Nord-Ouest au Sud-Ouest bornes exclues.



6.2.2.1.2 Baie sous un balcon ou auvent




                                                 Configuration du masque

 Le tableau ci-dessous donne les valeurs de Fe1 quelle que soit l'orientation de la façade en fonction de l'avancée I.




                                               Avancée l (m)       Fe1
                                                   <1              0,8
                                                 1 ≤…< 2           0,6
                                                 2 ≤…< 3           0,5
                                                   ≥3              0,4

6.2.2.1.3 Baie masquée par une paroi latérale




Une paroi latérale est considérée faire obstacle si les angles β et γ sont supérieurs à 30°. Les angles sont pris au centre
des baies.

Le tableau ci-dessous donne les valeurs de Fe1 selon la position de l’obstacle latéral :

                                    Le retour ne fait pas obstacle au Sud       0,7
                                        Le retour fait obstacle au Sud          0,5

Attention : en présence de plusieurs types de masques proches, seul l’impact du masque le plus pénalisant est pris en
compte.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_coef_masque_proche_id`

### Balises XML produites par cette section
- `fe1`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
