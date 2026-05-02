---
section_id: "4"
title: "Calcul des déperditions par renouvellement d'air"
spec_pages: [38-41]
xml_outputs: ["hvent", "hperm", "q4pa_conv"]
tables: ["tv_q4pa_conv_id", "tv_debits_ventilation_id"]
depends_on: ["3"]
status: "verbatim"
---

# §4 — Calcul des déperditions par renouvellement d'air

> Source : `resources/spec.pdf` p.38-41
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
4 Calcul des déperditions par renouvellement d’air
Données d’entrée :

Type de bâtiment

Surface des parois déperditives hors plancher bas

Surface habitable

Nombre de niveaux

Hauteur moyenne sous plafond

Type de ventilation

Année de construction ou de l’installation

Zone climatique

Les déperditions DR par renouvellement d’air par degré d’écart entre l’intérieur et l’extérieur (W/K) sont données par
la formule suivante :

                                                  𝐷𝑅 = 𝐻𝑣𝑒𝑛𝑡 + 𝐻𝑝𝑒𝑟𝑚

Avec :

    -    Hvent : déperdition thermique par renouvellement d’air due au système de ventilation par degré d’écart entre
         l’intérieur et l’extérieur (W/K) :

                                             𝐻𝑣𝑒𝑛𝑡 = 0,34 ∗ 𝑄𝑣𝑎𝑟𝑒𝑝𝑐𝑜𝑛𝑣 ∗ 𝑆ℎ

            Qvarepconv : débit d’air extrait conventionnel par unité de surface habitable (m3/(h.m2)) (voir tableau par
             type de ventilation ci-après)

            Sh : surface habitable (m2)

            0,34 : chaleur volumique de l'air (Wh/(m3.K))

    -    Hperm : déperdition thermique par renouvellement d’air due au vent par degré d’écart entre l’intérieur et
         l’extérieur (W/K) :

                                                  𝐻𝑝𝑒𝑟𝑚 = 0,34 ∗ 𝑄𝑣𝑖𝑛𝑓

            Qvinf : débit d’air dû aux infiltrations liées au vent (m3/h) :
                                                          𝐻𝑠𝑝 ∗ 𝑆ℎ ∗ 𝑛50 ∗ 𝑒
                                   𝑄𝑣𝑖𝑛𝑓 =
                                                  𝑓 𝑄𝑣𝑎𝑠𝑜𝑢𝑓𝑐𝑜𝑛𝑣 − 𝑄𝑣𝑎𝑟𝑒𝑝𝑐𝑜𝑛𝑣 2
                                                1+𝑒∗(       𝐻𝑠𝑝 ∗ 𝑛         )
                                                                               50

             □    Hsp : hauteur moyenne sous plafond (m)

             □    Sh : surface habitable (m²)

             □    Qvasoufconv : débit volumique conventionnel à souffler (m3/(h.m²)) (voir tableau par type de ventilation
                  ci-après)

             □    Qvarepconv : débit volumique conventionnel à reprendre (m3/(h.m²)) (voir tableau par type de
                  ventilation ci-après)

             □   e et f sont des coefficients de protection prenant les valeurs tabulées ci-dessous :

                         Coefficient   Plusieurs façades exposées           Une seule façade exposée
                              e                     0,07                                0,02
                              f                      15                                   20

                 Une façade exposée est une façade donnant sur l’extérieur.

             □   n50 : Renouvellement d’air sous 50 Pascals (h-1) :
                                                                   𝑄4𝑝𝑎
                                                   𝑛50 =       2
                                                            4  3
                                                           ( ) ∗ 𝐻𝑠𝑝 ∗ 𝑆ℎ
                                                            50

                         Q4Pa : perméabilité sous 4 Pa de la zone (m3/h) :

                                        𝑄4𝑃𝑎 = 𝑄4𝑃𝑎𝑒𝑛𝑣 + 0,45 ∗ 𝑆𝑚𝑒𝑎𝑐𝑜𝑛𝑣 ∗ 𝑆ℎ

                          o   Smeaconv : somme des modules d’entrée d’air sous 20 Pa par unité de surface habitable
                              (m3/(h.m2)) (voir tableau par type de ventilation ci-après)

                          o   Q4Paenv : perméabilité de l’enveloppe (m3/h) :

                                             𝑄4𝑃𝑎𝑒𝑛𝑣 = 𝑄4𝑃𝑎𝑐𝑜𝑛𝑣/𝑚² ∗ 𝑆𝑑𝑒𝑝

                               Sdep : surface des parois déperditives hors plancher bas (m²)

                               Q4Paconv/m² : valeur conventionnelle de la perméabilité sous 4Pa (m3/(h.m2)) :

                          Appartement/Immeuble                                                 Maison

                  Avant       1948 -      1975 -      >2012          Avant       1948 -        1975 -   2006 -   >2012
                  1948         1974        2012                      1948         1974          2005     2012

   Q4Paconv/m²     4,6            2        1,5             1          3,3         2,2           1,9      1,3      0,6


Pour les bâtiments qui ont fait l’objet d’une mesure d’étanchéité à l’air moins de deux ans avant le diagnostic, la valeur
mesurée de Q4Paconv/m² peut être saisie.

Pour les bâtiments ou logements construits avant 1948 avec une isolation des murs et/ou du plafond (isolation de plus
de 50% des surfaces), Q4Paconv/m² = 2 m3/(h.m2)

Pour les bâtiments ou logements construits entre 1948 et 1974 avec une isolation des murs et/ou du plafond (isolation
de plus de 50% des surfaces), Q4Paconv/m² = 1,9 m3/(h.m2)

Pour les bâtiments ou logements construits avant 1948 et dont les menuiseries possèdent des joints, Q4Paconv/m² = 2,5
m3/(h.m2). On considère cette condition respectée si les menuiseries représentant plus de 50% de la surface totale
possèdent des joints.




                                                                Qvarepconv       Qvasoufconv       Smeaconv
                    Type de ventilation
                                                               (m3/(h.m²))       (m3/(h.m²))      (m3/(h.m²))
Ventilation par ouverture des fenêtres                             1,2              1,2               0
Ventilation par entrées d'air hautes et basses                    2,23               0                4
VMC SF Auto réglable < 1982                                       1,97               0                2
VMC SF Auto réglable de 1982 à 2000                               1,65               0                2
VMC SF Auto réglable de 2001 à 2012                               1,50               0                2
VMC SF Auto réglable après 2012                                   1,32               0                2
VMC SF Hygro A < 2001                                             1,50               0                2
VMC SF Hygro A de 2001 à 2012                                     1,44               0                2
VMC SF Hygro A après 2012                                         1,16               0                2
VMC SF Gaz < 2001                                                 1,59               0                2
VMC SF Gaz de 2001 à 2012                                         1,53               0                2
VMC SF Gaz après 2012                                             1,22               0                2
VMC SF Hygro B < 2001                                             1,36               0               1,5
VMC SF Hygro B de 2001 à 2012                                     1,24               0               1,5
VMC SF Hygro B après 2012                                         1,09               0               1,5
VMC Basse pression Auto-réglable                                  1,97               0                2
VMC Basse pression Hygro A                                        1,30               0                2
VMC Basse pression Hygro B                                        1,24               0               1,5
VMC DF individuelle avec échangeur ≤ 2012                         0,60              0,6               0
VMC DF individuelle avec échangeur après 2012                     0,26              0,26              0
VMC DF collective avec échangeur ≤ 2012                           0,75              0,75              0
VMC DF collective avec échangeur après 2012                       0,46              0,46              0
VMC DF sans échangeur ≤ 2012                                      1,65              1,65              0
VMC DF sans échangeur après 2012                                  1,32              1,32              0
Ventilation naturelle par conduit                                 2,23               0                4
Ventilation hybride < 2001                                        1,52               0                3
Ventilation hybride de 2001 à 2012                                1,33               0                3
Ventilation hybride après 2012                                    1,17               0                3
Ventilation hybride avec entrées d'air hygro < 2001               1,52               0                2
Ventilation hybride avec entrées d'air hygro de 2001 à            1,33               0                2
2012
Ventilation hybride avec entrées d'air hygro après 2012           1,17               0                2
Ventilation mécanique sur conduit existant ≤ 2012                 2,24               0                4
Ventilation mécanique sur conduit existant après 2012             1,97               0                4
Ventilation naturelle par conduit avec entrées d'air
                                                                   2,23                0               3
hygro
Puits climatique sans échangeur ≤ 2012                             0,99               0,99             0
Puits climatique sans échangeur après 2012                         0,79               0,79             0
Puits climatique avec échangeur ≤ 2012                             0,36               0,36             0
Puits climatique avec échangeur après 2012                         0,16               0,16             0

Cas des VMC par insufflation :
Les VMC par insufflation sont traitées comme des VMC simple flux autoréglables et avec les mêmes caractéristiques
selon les années d’installation.
Cas des puits climatiques (intégrés au tableau ci-dessus) :
Le puits climatique est considéré comme une VMC double flux faisant rentrer dans le logement de l’air à une
température proche de celle du sol.
Par hypothèse la température moyenne en sortie du puits canadien est de 12°C. La température moyenne extérieure
d’Octobre à Avril est de 8°C.
La modélisation du puits climatique est donc comparable à une celle d’une VMC double flux avec une correction sur
                                   Tint−12
les températures  correction = Tint−8 = 0,6 appliquée pour obtenir les valeurs présentes dans le tableau.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_q4pa_conv_id`
- [ ] `tv_debits_ventilation_id`

### Balises XML produites par cette section
- `hvent`
- `hperm`
- `q4pa_conv`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
