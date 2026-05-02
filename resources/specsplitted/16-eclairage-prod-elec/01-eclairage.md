---
section_id: "16.1"
title: "Consommation d'éclairage (Cecl)"
spec_pages: [102-103]
xml_outputs: ["conso_eclairage"]
tables: []
depends_on: ["16"]
status: "verbatim"
---

# §16.1 — Consommation d'éclairage (Cecl)

> Source : `resources/spec.pdf` p.102-103
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
16.1 Consommation d’éclairage (Cecl)
La consommation d’éclairage est forfaitaire dans les bâtiments d’habitation. La puissance d’éclairage conventionnelle
est prise égale à 1,4 W/m².

Consommation d’éclairage conventionnelle (kWh/m²) :

                                                     𝐶𝑒𝑐𝑙 = ∑ 𝐶𝑒𝑐𝑙𝑗
                                                               𝑗

                                                           𝐶 ∗ 𝑃𝑒𝑐𝑙 ∗ 𝑁ℎ𝑗
                                                 𝐶𝑒𝑐𝑙𝑗 =
                                                                1000

Avec :

    -    C : coefficient correspondant au taux d'utilisation de l'éclairage en l'absence d'éclairage naturel. Il prend la
         valeur de 0,9 pour une commande de l’éclairage par interrupteur (considéré dans les logements).

    -    Pecl : puissance d’éclairage conventionnelle, égale à 1,4 W/m2

    -    Nhj : nombre d’heures de fonctionnement de l’éclairage sur le mois j (h)

Pour chaque zone climatique, les heures de lever et de coucher du soleil sont croisées avec les heures d’occupation
où l’éclairage peut être nécessaire. Il en ressort pour chaque zone climatique et pour chaque mois le nombre moyen
d’heure d’éclairage journalier :

                                              Nbr moyen d'heures d'éclairage par jour
                   Mois         H1a       H1b     H1c     H2a      H2b       H2c      H2d              H3
                 Janvier         7         6       6       7         7        6        6                6
                 Février         6         6       6       6         6        6        6                6
                  Mars           5         5       5       5         5        5        5                5
                   Avril         3         3       3       3         3        4        4                4
                   Mai           2         2       2       2         2        2        2                2
                   Juin          1         1       1       1         1        2        2                2
                  Juillet        1         1       2       1         2        2        2                2
                   Aout          3         3       3       3         3        3        3                3
               Septembre         4         4       4       4         4        5        5                4
                Octobre          6         6       6       6         6        6        6                6
               Novembre          6         6       6       6         6        6        6                5

                Décembre         7          6          6          7     7        6         6        6

Ainsi, le nombre d’heures de fonctionnement de l’éclairage sur le mois j est :

                                             Nbr moyen d'heures d'éclairage par mois : Nhj
                   Mois        H1a         H1b     H1c     H2a       H2b       H2c       H2d        H3
                 Janvier       217         186     186      217       217      186       186       186
                 Février       168         168     168      168       168      168       168       168
                  Mars         155         155     155      155       155      155       155       155
                   Avril        90          90     90       90        90       120       120       120
                   Mai          62          62     62       62        62        62        62        62
                   Juin         30          30     30       30        30        60        60        60
                  Juillet       31          31     62       31        62        62        62        62
                   Aout         93          93     93       93        93        93        93        93
               Septembre       120         120     120      120       120      150       150       120
                Octobre        186         186     186      186       186      186       186       186
               Novembre        180         180     180      180       180      180       180       150
               Décembre        168         144     144      168       168      144       144       144
                  Total        1500        1445   1476     1500      1531     1566      1566       1506
```

## TODO digitalisation

### Balises XML produites par cette section
- `conso_eclairage`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
