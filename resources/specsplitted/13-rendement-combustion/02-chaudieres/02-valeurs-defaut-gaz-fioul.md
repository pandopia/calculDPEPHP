---
section_id: "13.2.2"
title: "Valeurs par défaut chaudières gaz et fioul"
spec_pages: [86-92]
xml_outputs: ["pn", "qp0", "rpn", "rpint"]
tables: []
depends_on: ["13.2"]
status: "verbatim"
---

# §13.2.2 — Valeurs par défaut chaudières gaz et fioul

> Source : `resources/spec.pdf` p.86-92
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
  13.2.2 Valeurs par défaut des caractéristiques des chaudières gaz et fioul
  Le tableau suivant donne les valeurs par défaut des chaudières gaz et fioul :

                                CHAUDIERES GAZ (Valeurs par défaut Rpn, Rpint et Qp0)
                                                                   Rendements         Qp0 en %                    Puissance
                                Puissance nominale Rendements
    Type         Ancienneté                                         (PCI) Rpint       puissance                veilleuse en W
                                      Pn (kW)      (PCI) Rpn (%)
                                                                        (%)         nominale Pn                 (si veilleuse)
                 Avant 1980                                                              4%                          240
 Classique       1981 - 1985            Pn          84 + 2 logPn 80 + 3 logPn            2%                          150
                 1986 - 1990                                                            1,5%                         150
                 1991 - 2000                                                            1,2%                         120
                 2001 - 2015                                                             1%
  Standard                              Pn          84 + 2 logPn 80 + 3 logPn
                 A partir de                                                        Pn * (E + F *
                    2016                                                            logPn) / 100
                 1991 - 2000                                                            1,2%                        120
   Basse         2001 - 2015                           87,5 +         87,5 +             1%
                                        Pn
Température      A partir de                         1,5logPn        1,5logPn       Pn * (E + F *
                    2016                                                            logPn) / 100
                 1981 - 1985                                                                                        150
                 1986 - 2000             Pn              91 + logPn      97 + logPn               1%                120
                 2001 - 2015
Condensation                                                                103 +
                                      Pn ≤ 70 kW        91 + 3 logPn                             0,5%
                                                                          2,5logPn
                 A partir de
                                   70 kW < Pn ≤ 400                         105 +
                    2016                                 94 + logPn
                                         kW                               0,5logPn               0,3%
                                     Pn > 400 kW            96,6           106,3


                                 CHAUDIERES FIOUL (Valeurs par défaut Rpn, Rpint et Qp0)
                                                                                                               Qp0 en %
                                      Puissance nominale     Rendements (PCI)      Rendements (PCI)
       Type          Ancienneté                                                                               puissance
                                            Pn (kW)              Rpn (%)              Rpint (%)
                                                                                                             nominale Pn
                     Avant 1970                                                                                   4%
                     1970 - 1975                                                                                  3%
     Classique                                 Pn               84 + 2 logPn             80 + 3 logPn
                     1976 - 1980                                                                                  2%
                     1981 - 1990                                                                                  1%
                     1991 - 2015                                                                                  1%
     Standard                                  Pn               84 + 2 logPn             80 + 3 logPn     Pn * (E + F * logPn)
                   A partir de 2016
                                                                                                                 / 100
                     1991 - 2015               Pn             87,5 + 1,5 logPn       87,5 + 1,5 logPn             1%

    Basse                                                                                                   Pn * (E + F * logPn)
             A partir de 2016
 Température                                                                                                       / 100
               1996 - 2015                   Pn                 91 + logPn             97 + logPn                   1%
                                         Pn ≤ 70 kW            91 + 3 logPn           98 + 3 logPn                 0,5%
 Condensation                         70 kW < Pn ≤ 400
                  A partir de 2016                               94 + logPn           100 + logPn                  0,6%
                                             kW
                                        Pn > 400 kW                96,6                  102,6                     0,3%




Avec :

                                                                                           E          F
                   Chaudières à combustible liquide ou gazeux
                   Absence de ventilateur ou autre dispositif de circulation d’air
                                                                                          2,5        -0,8
                   ou de produit de combustion dans le circuit de combustion
                   Présence de ventilateur ou autre dispositif de circulation d’air
                                                                                          1,75   -0,55
                   ou de produit de combustion dans le circuit de combustion


13.2.2.1 Générateurs d’air chaud

Pour les générateurs d’air chaud standard, le point de fonctionnement w correspond à un fonctionnement à 50% de
charge.

Entre 0 et 50% de charge :

                                             [𝑄𝑃50 − 0,15 ∗ 𝑄𝑃0 ] ∗ 𝑥
                                     𝑄𝑃𝑥 =                            + 0,15 ∗ 𝑄𝑃0
                                                       0,5

Entre 50 et 100% de charge :

                                             [𝑄𝑃100 − 𝑄𝑃50 ] ∗ 𝑥
                                     𝑄𝑃𝑥 =                       + 2 ∗ 𝑄𝑃50 − 𝑄𝑃100
                                                    0,5

                                                                 100 − 𝑅𝑃𝑖𝑛𝑡
                                             𝑄𝑃50 = 0,5 ∗ 𝑃𝑛 ∗
                                                                    𝑅𝑃𝑖𝑛𝑡

                                                               100 − 𝑅𝑃𝑛
                                                𝑄𝑃100 = 𝑃𝑛 ∗
                                                                  𝑅𝑃𝑛

                                                  𝑃𝑛 ∗ (1,75 − 0,55 ∗ 𝑙𝑜𝑔𝑃𝑛)
                                          𝑄𝑃0 =
                                                             100

L’expression de QP0 est valable pour une puissance nominale inférieure ou égale à 300 kW. On conservera les valeurs
pour Pn = 300 kW si Pn > 300 kW.

    -    Si les équipements sont anciens (avant 2006)

                                        R pn = 77%                 R pint = 74%

    -    Si les équipements sont neufs (à partir de 2006)

            Pour un générateur standard :


                                        R pn = 84%                 R pint = 77%

           Pour un générateur à condensation :

                                       R pn = 90%                   R pint = 83%

Pour les installations récentes ou recommandées, les caractéristiques réelles des générateurs à air chaud sur les bases
de données professionnelles peuvent être utilisées.



13.2.2.2 Radiateurs à gaz
                                                           100 − 𝑅𝑃𝑛
                                            𝑄𝑃𝑥 = 1,04 ∗             ∗ 𝑃𝑛 ∗ 𝑥
                                                              𝑅𝑃𝑛

    -   Pour les radiateurs à gaz neufs (à partir de 2006) :

        Si Pn < 5𝑘𝑊 :                   R pn = 80%

        Si Pn ≥ 5kW :                   R pn = 82%

    -   Pour les radiateurs à gaz anciens (avant 2006) :

        Si Pn < 5𝑘𝑊 :                   R pn = 70%

        Si Pn ≥ 5kW :                   R pn = 73%



13.2.2.3 Chaudières bois

Les chaudières au charbon sont traitées comme des chaudières bois bûche.

Le point de fonctionnement w des chaudières bois correspond à 50% de charge.

Entre 0 et 50% de charge :

                                             [𝑄𝑃50 − 0,15 ∗ 𝑄𝑃0 ] ∗ 𝑥
                                     𝑄𝑃𝑥 =                            + 0,15 ∗ 𝑄𝑃0
                                                       0,5

Entre 50 et 100% de charge :

                                            [𝑄𝑃100 − 𝑄𝑃50 ] ∗ 𝑥
                                    𝑄𝑃𝑥 =                       + 2 ∗ 𝑄𝑃50 − 𝑄𝑃100
                                                   0,5

                                                                 100 − 𝑅𝑃𝑖𝑛𝑡
                                             𝑄𝑃50 = 0,5 ∗ 𝑃𝑛 ∗
                                                                    𝑅𝑃𝑖𝑛𝑡

                                                               100 − 𝑅𝑃𝑛
                                               𝑄𝑃100 = 𝑃𝑛 ∗
                                                                  𝑅𝑃𝑛




Le tableau suivant donne les caractéristiques Rpn, Rpint et Qp0 en fonction des années de fabrication du générateur.

 Générateurs Chauffage à     Critère Pn               Rendements       Rendements
                                        Pn (kW)                                               Qp0 (kW)
      combustion                (kW)                  (PCI) Rpn (%)   (PCI) Rpint (%)
                               Pn≤70      Pn          47 + 6𝑙𝑜𝑔𝑃𝑛      48 + 6𝑙𝑜𝑔𝑃𝑛      0,08 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,27
 Chaudière bois bûche ou
                             70<Pn≤400                                                           1,8
    plaquette <1978                       70               58               59
                              Pn>400                                                             1,1
                               Pn≤70      Pn          47 + 6𝑙𝑜𝑔𝑃𝑛      48 + 6𝑙𝑜𝑔𝑃𝑛      0,07 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,3
 Chaudière bois bûche ou
                             70<Pn≤400    70                                                     1,4
  plaquette 1978-1994                                      58               59
                              Pn>400      70                                                     0,8
                               Pn≤70                  47 + 6𝑙𝑜𝑔𝑃𝑛      48 + 6𝑙𝑜𝑔𝑃𝑛      0,085 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,4
 Chaudière bois bûche ou
                             70<Pn≤400    70                                                     1,1
  plaquette 1995-2003                                      58               59
                              Pn>400      70                                                     0,5
                               Pn≤70                  57 + 6𝑙𝑜𝑔𝑃𝑛      58 + 6𝑙𝑜𝑔𝑃𝑛          0,085 ∗ 𝑃𝑛 .∗
 Chaudière bois bûche ou
                             70<Pn≤400    70                                                     1,1
  plaquette 2004-2012                                      68               69
                              Pn>400      70                                                     0,5
                               Pn≤70                  67 + 6𝑙𝑜𝑔𝑃𝑛      68 + 6𝑙𝑜𝑔𝑃𝑛      0,085 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,4
 Chaudière bois bûche ou
                             70<Pn≤400    70                                                     1,1
  plaquette 2013-2017                                      78               79
                              Pn>400      70                                                     0,5
                               Pn≤70                  80 + 2𝑙𝑜𝑔𝑃𝑛      77 + 3𝑙𝑜𝑔𝑃𝑛      0,085 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,4
 Chaudière bois bûche ou
                             70<Pn≤400    70                                                     1,1
  plaquette 2018-2019                                      84               83
                              Pn>400      70                                                     0,5
                               Pn≤20                  89 + 2𝑙𝑜𝑔𝑃𝑛      84 + 2𝑙𝑜𝑔𝑃𝑛
                                                                                        0,085 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,4
Chaudière bois bûche ou      20<Pn≤70                 90 + 2𝑙𝑜𝑔𝑃𝑛      85 + 2𝑙𝑜𝑔𝑃𝑛
plaquette >2019              70<Pn≤400    70                                                     1,1
                                                           94               89
                              Pn>400      70                                                     0,5
                               Pn≤70                  47 + 6𝑙𝑜𝑔𝑃𝑛      48 + 6𝑙𝑜𝑔𝑃𝑛      0,08 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,27
  Chaudière bois granulés
                             70<Pn≤400    70                                                     1,8
          <1978                                            58               59
                              Pn>400      70                                                     1,1
                               Pn≤70                  47 + 6𝑙𝑜𝑔𝑃𝑛      48 + 6𝑙𝑜𝑔𝑃𝑛      0,07 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,3
  Chaudière bois granulés
                             70<Pn≤400    70                                                     1,4
       1978-1994                                           58               59
                              Pn>400      70                                                     0,8
                               Pn≤70                  57 + 6𝑙𝑜𝑔𝑃𝑛      58 + 6𝑙𝑜𝑔𝑃𝑛      0,085 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,4
  Chaudière bois granulés
                             70<Pn≤400    70                                                     1,1
       1995-2003                                           68               69
                              Pn>400      70                                                     0,5
  Chaudière bois granulés      Pn≤70                  67 + 6𝑙𝑜𝑔𝑃𝑛      68 + 6𝑙𝑜𝑔𝑃𝑛      0,085 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,4
       2004-2012             70<Pn≤400    70              78               79                    1,1

                                Pn>400          70                                                   0,5
                                 Pn≤70                   80 + 2𝑙𝑜𝑔𝑃𝑛       77 + 3𝑙𝑜𝑔𝑃𝑛      0,085 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,4
  Chaudière bois granulés
                               70<Pn≤400        70                                                   1,1
       2013-2019                                              84                83
                                Pn>400          70                                                   0,5
                                 Pn≤20                   91 + 2𝑙𝑜𝑔𝑃𝑛       88 + 2𝑙𝑜𝑔𝑃𝑛
                                                                                            0,085 ∗ 𝑃𝑛 ∗ (𝑃𝑛 )−0,4
  Chaudière bois granulés      20<Pn≤70                  92 + 2𝑙𝑜𝑔𝑃𝑛       89 + 2𝑙𝑜𝑔𝑃𝑛
          >2019                70<Pn≤400        70                                                   1,1
                                                              96                93
                                Pn>400          70                                                   0,5

Si l’année d’installation de la chaudière bois n’est pas connue, elle sera considérée par défaut correspondre à l’année
de construction du bâtiment.

Les valeurs des bases de données professionnelles peuvent aussi être utilisées pour les chaudières récentes ou
recommandées.



13.2.2.4 Calcul des puissances nominales

Lorsque les puissances des générateurs à combustion individuels ne sont pas connues et pour les recommandations,
il est possible d’en faire une estimation selon la méthode suivante :

                                                     1,2 ∗ 𝐺𝑉 ∗ (19 − 𝑇𝑏𝑎𝑠𝑒)
                                           𝑃𝑐ℎ =
                                                           1000 ∗ 0,953

Avec :

    -    Pch : puissance nominale du générateur pour le chauffage (kW)

    -    Tbase : température extérieure de base selon la zone climatique et l’altitude (°C) (voir paragraphe 18.1)

    -    GV : déperditions à travers l’enveloppe et par renouvellement d’air (W/K)

Dans le cas de la réalisation d’un DPE à l’échelle de l’appartement, et lorsque celui-ci est alimenté par une installation
collective, le calcul de la puissance nominale du générateur collectif Pchimmeuble (kW) est :

                                                     1,2 ∗ 𝐺𝑉𝑖𝑚𝑚𝑒𝑢𝑏𝑙𝑒 ∗ (19 − 𝑇𝑏𝑎𝑠𝑒)
                                  𝑃𝑐ℎ𝑖𝑚𝑚𝑒𝑢𝑏𝑙𝑒 =
                                                               1000 ∗ 0,953

Avec :

    -    GVimmeuble : déperditions à travers l’enveloppe et par renouvellement d’air pour l’immeuble (W/K) :
                                                                        𝑆ℎ𝑖𝑚𝑚𝑒𝑢𝑏𝑙𝑒
                                    𝐺𝑉𝑖𝑚𝑚𝑒𝑢𝑏𝑙𝑒 = 𝐺𝑉𝑎𝑝𝑝𝑎𝑟𝑡𝑒𝑚𝑒𝑛𝑡 ∗
                                                                       𝑆ℎ𝑎𝑝𝑝𝑎𝑟𝑡𝑒𝑚𝑒𝑛𝑡

    -    Tbase : température extérieure de base selon la zone climatique et l’altitude (°C) (voir paragraphe 18.1)

Dans le cas de la réalisation d’un DPE à l’échelle de l’appartement à partir des données de l’immeuble (voir §17.2.2),
et lorsque le chauffage est individuel et géré de manière homogène, le calcul de la puissance nominale du générateur
de chaque appartement Pch (kW) est :

                                                          𝐺𝑉
                                                     1,2 ∗ 𝑁 ∗ (19 − 𝑇𝑏𝑎𝑠𝑒)
                                            𝑃𝑐ℎ =
                                                          1000 ∗ 0,953


Avec :

    -    Pch : puissance nominale du générateur pour le chauffage (kW)

    -    Tbase : température extérieure de base selon la zone climatique et l’altitude (°C) (voir paragraphe 18.1)

    -    GV : déperditions à travers l’enveloppe et par renouvellement d’air (W/K)

    -    N : nombre de logements dans l’immeuble

Si le générateur n'alimente qu'une partie du logement, il est nécessaire de proratiser cette puissance Pch.

Dans le cas de 2 générateurs alimentant pour le premier une surface Sh1 et pour le second une surface Sh2 (Sh1 +
Sh2 = Sh avec Sh la surface du logement) :
                                              𝑆ℎ1     1,2 ∗ 𝐺𝑉 ∗ (19 − 𝑇𝑏𝑎𝑠𝑒)
                                    𝑃𝑐ℎ1 =          ∗
                                             𝑆ℎ𝑡𝑜𝑡          1000 ∗ 0,953
                                                𝑆ℎ2    1,2 ∗ 𝐺𝑉 ∗ (19 − 𝑇𝑏𝑎𝑠𝑒)
                                      𝑃𝑐ℎ2 =         ∗
                                               𝑆ℎ𝑡𝑜𝑡         1000 ∗ 0,953
Avec :

    -    Pch1 la puissance nominale du générateur pour le chauffage (kW) pour la surface Sh1

    -    Pch2 la puissance nominale du générateur pour le chauffage (kW) pour la surface Sh2


La puissance nécessaire pour la production d’eau chaude sanitaire (Pecs) dépend du type de production et donc du
volume de stockage :

            Type de production d’ECS       Volume de stockage (L)      Puissance de dimensionnement (kW)
                   Instantanée                      Vs = 0                           𝑃𝑒𝑐𝑠 = 21
                Semi-instantanée                  0 < Vs ≤ 20                𝑃𝑒𝑐𝑠 = 21 − 0,8 ∗ 𝑉𝑠
                                                                                                  𝑉𝑠 − 20
                Semi-accumulation               20 < Vs ≤ 150              𝑃𝑒𝑐𝑠 = 5 − 1,751 ∗       65
                                                                                      7,14 ∗ 𝑉𝑠 + 428
                  Accumulation                     150 < Vs                 𝑃𝑒𝑐𝑠 =
                                                                                            1000

La puissance de dimensionnement du générateur est :
                                         𝑃𝑑𝑖𝑚 = max(𝑃𝑐ℎ ; 𝑃𝑒𝑐𝑠)

La puissance nominale Pn (kW) des chaudières est déterminée à partir de Pdim :

                                 CHAUDIERES MURALES INSTALLEES           CHAUDIERES MURALES INSTALLEES
                                  avant 2005 ou chaudières sur sol              à partir de 2006
               Pdim (kW)                      Pn (kW)                               Pn (kW)
                  ≤5                            18                                      5
                5< ≤10                          18                                     10
                10< ≤13                         18                                     13
                13< ≤18                         18                                     18
                18< ≤24                         24                                     24
                24< ≤28                         28                                     28
                28< ≤32                         32                                     32
                32< ≤40                         40                                     40
                                                                      𝑃𝑑𝑖𝑚
                  40<                                (𝑃𝑎𝑟𝑡𝑖𝑒 𝑒𝑛𝑡𝑖è𝑟𝑒 (     ) + 1) ∗ 5
                                                                       5
Dans le cas d’un logement chauffé avec n radiateurs gaz, la puissance de chaque radiateur gaz est Pn (kW) tel que :

                                                               𝑃𝑐ℎ
                                                        𝑃𝑛 =
                                                                𝑛
```

## TODO digitalisation

### Balises XML produites par cette section
- `pn`
- `qp0`
- `rpn`
- `rpint`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
