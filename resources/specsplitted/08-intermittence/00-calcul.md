---
section_id: "8"
title: "Modélisation de l'intermittence"
spec_pages: [55-57]
xml_outputs: ["i0"]
tables: ["tv_intermittence_id"]
depends_on: ["7"]
status: "verbatim"
---

# §8 — Modélisation de l'intermittence

> Source : `resources/spec.pdf` p.55-57
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
     8 Modélisation de l’intermittence
     Données d’entrée :

     Type de bâtiment

     Type de chauffage (divisé, central)

     Type de régulation (par pièce ou non)

     Equipement d’intermittence (absent, central sans minimum de température, …)

     Type d’émetteur (air soufflé, convecteurs, …)

     Présence d’un comptage

     Hauteur moyenne sous plafond

     Le facteur d’intermittence traduit les baisses temporaires de température, réalisées pour différentes raisons, absence,
     ralenti de nuit et éventuellement de façon inégale dans les pièces.

     Il est égal au rapport entre les besoins réels, compte tenu d'un comportement moyen des occupants, et les besoins
     théoriques. Le facteur d’intermittence est donné par la formule :

                                                                                   𝐼𝑜
                                                                 𝐼𝑁𝑇 =
                                                                            1 + 0,1 ∗ (𝐺 − 1)
     Avec :

                                                                                  𝐺𝑉
                                                                          𝐺=
                                                                                𝐻𝑠𝑝 ∗ 𝑆ℎ

          -    GV : déperditions annuelles de l’enveloppe (W/K) (déterminé en partie 3)

          -    Sh : surface habitable (m2)

          -    Hsp : hauteur moyenne sous plafond (m)

                                                                                       Équipements d’intermittence
                                                        Inertie Légère ou moyenne                                        Inertie Lourde ou très lourde
                                                                                            Par pièce avec                                                      Par pièce avec
              I0                                   Central sans Central avec Par pièce avec minimum de                Central sans   Central avec Par pièce avec minimum de
Pour les maisons individuelles            Absent   minimum de minimum de minimum de température et           Absent   minimum de     minimum de minimum de température
                                                   température température température détection de                   température    température température et détection
   (chauffage individuel)                                                                     présence                                                           de présence
                         Air soufflé      0,84        0,83         0,81          0,77           0,75         0,86        0,85           0,83          0,80          0,78
             Avec        Radiateur /
Chauffage régulation                      0,84        0,83         0,81          0,77           0,75         0,86        0,85           0,83          0,80          0,78
                         Convecteur
  divisé   pièce par
                     Plafond chauffant    0,84        0,83         0,81          0,77           0,75         0,86        0,85           0,83          0,80          0,78
             pièce
                     Plancher chauffant   0,90        0,89         0,88          0,86             -          0,92        0,91           0,90          0,88            -
                         Air soufflé      0,86        0,85         0,83          0,79           0,77         0,88        0,87           0,85          0,82          0,80
             Avec
          régulation     Radiateur        0,88        0,87         0,85          0,82           0,80         0,90        0,89           0,87          0,85          0,82
           pièce par Plafond chauffant    0,88        0,87         0,85          0,82           0,80         0,90        0,89           0,87          0,85          0,82
Chauffage    pièce
                     Plancher chauffant   0,90        0,89         0,88          0,86             -          0,92        0,91           0,90          0,88            -
 central
                         Air soufflé      0,90        0,89         0,87            -                         0,91        0,91           0,89            -
             Sans
                         Radiateur        0,91        0,90         0,88            -                         0,93        0,92           0,90            -
          régulation
                     Plafond chauffant    0,91        0,90         0,88            -                         0,93        0,92           0,90            -

        pièce par
                  Plancher chauffant     0,92       0,91       0,90        -                         0,94   0,93           0,92          -
          pièce


   Une maison individuelle branchée sur un réseau collectif de fourniture d’énergie pour le chauffage sera traitée comme
   une maison individuelle avec un chauffage individuel central.

                                                                                          Équipements d’intermittence
                    I0                                                   Central sans       Central avec     Par pièce avec       Par pièce avec minimum
Pour les immeubles collectifs avec chauffage                  Absent     minimum de         minimum de        minimum de             de température et
                individuel                                               température        température       température          détection de présence
                                          Air soufflé          0,90            0,89                 0,88            0,86                     0,83
 Chauffage     Avec régulation Radiateur/Convecteur            0,90            0,89                 0,88            0,86                     0,83
   divisé      pièce par pièce   Plafond chauffant             0,90            0,89                 0,88            0,86                     0,83
                                    Plancher chauffant         0,95            0,94                 0,93            0,91                       -
                                          Air soufflé          0,91            0,90                 0,89            0,87                     0,84
               Avec régulation            Radiateur            0,93            0,92                 0,91            0,89                     0,86
               pièce par pièce       Plafond chauffant         0,93            0,92                 0,91            0,89                     0,86
 Chauffage                          Plancher chauffant         0,95            0,94                 0,93            0,91                       -
  central                                 Air soufflé          0,95            0,94                 0,93             -
               Sans régulation            Radiateur            0,96            0,95                 0,94             -
               pièce par pièce       Plafond chauffant         0,96            0,95                 0,94             -
                                    Plancher chauffant         0,97            0,96                 0,95             -


                                                               Absence de comptage individuel                 Présence d’un comptage individuel

                   I0                                           Équipements d’intermittence                        Équipements d’intermittence
                                                                                        Central collectif                                    Central collectif
   Pour les immeubles collectifs avec                                     Central                                            Central
                                                            Absent                      avec détection      Absent                           avec détection
           chauffage collectif                                            collectif                                          collectif
                                                                                         de présence                                          de présence
                                     Air soufflé             1,01              0,99           0,96           0,93             0,91                  0,88
             Avec régulation           Radiateur             1,03              1,01           0,98           0,95             0,93                  0,90
             pièce par pièce     Plafond chauffant           1,03              1,01           0,98           0,95             0,93                  0,90
Chauffage                        Plancher chauffant          1,05              1,03             -            0,97             0,95                   -
 central                             Air soufflé             1,03              1,01                          0,95             0,93
             Sans régulation           Radiateur             1,05              1,03                          0,97             0,95
             pièce par pièce     Plafond chauffant           1,05              1,03                          0,97             0,95
                                 Plancher chauffant          1,07              1,05                          0,99             0,97


   En immeuble collectif, le chauffage mixte, c'est-à-dire dont une partie est facturée collectivement et une autre
   individuellement, est traité au niveau de l’intermittence comme un système collectif avec comptage individuel.

   Seule l’intermittence de l’appoint est prise en compte sur les installations base + appoint. Une régulation zonale peut
   être considérée comme une régulation pièce par pièce.

   L’équipement d’intermittence peut être :

            En chauffage individuel
                  o   Absent : pas d’équipement permettant de programmer des réduits de température ;
                  o   Central sans minimum de température : équipements permettant une programmation seulement de
                      la fonction marche arrêt et donc ne garantissant pas un minimum de température ;
                  o   Central avec un minimum de température : équipement pouvant assurer :


                       Centralement un ralenti ou un abaissement de température fixe, non modifiable par
                        l’occupant, ainsi que la fonction hors gel ;
                       Centralement un ralenti ou un abaissement de température au choix de l’occupant ;
            o   Pièce par pièce avec minimum de température : équipement permettant d’obtenir par pièce un ralenti
                ou un abaissement de température fixe, non modifiable par l’occupant.
       En chauffage collectif
            o   Absent : pas de réduit de nuit ;
            o   Central collectif : possibilité de ralenti de nuit.
Un plancher chauffant avec une régulation zone jour/zone nuit peut être associée à une régulation pièce par pièce.

Un poêle sera modélisé comme un radiateur/convecteur pour la détermination de l’intermittence.

Un système de chauffage divisé est un système pour lequel la génération et l’émission sont confondues. C’est le cas
des convecteurs électriques, planchers chauffants électriques, …

Un système de chauffage central comporte un générateur central, individuel ou collectif, et une distribution par fluide
chauffant : air ou eau.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_intermittence_id`

### Balises XML produites par cette section
- `i0`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
