---
section_id: "7"
title: "Détermination de l'inertie"
spec_pages: [53-54]
xml_outputs: ["enum_classe_inertie_id"]
tables: []
depends_on: []
status: "verbatim"
---

# §7 — Détermination de l'inertie

> Source : `resources/spec.pdf` p.53-54
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
7 Détermination de l’inertie
7.1 Plancher haut lourd :
       Plancher sous toiture (terrasse, combles perdus, rampant lourd) non isolé ou isolé par l’extérieur et sans faux
        plafond (*) et constitué de :
            o   Béton plein de plus de 8 cm,
            o   Poutrelles et hourdis béton ou terre cuite ;
       Sous-face de plancher intermédiaire sans isolant et sans faux plafond (*) constitué de :
            o   Béton plein de plus de 15 cm
            o   Poutrelles et hourdis béton ou terre cuite
(*) Ne sont considérés que les faux plafonds possédant une lame d’air non ventilée ou faiblement ventilée (moins de
1 500 mm² d’ouverture par m² de surface), couvrant plus de la moitié de la surface du plafond du niveau considéré.

Un plancher haut dont l’inertie est inconnue est considéré par défaut à inertie légère.




7.2 Plancher bas lourd :
       Face supérieure de plancher intermédiaire avec un revêtement non isolant :
            o   Béton plein de plus de 15 cm sans isolant,
            o   Chape ou dalle de béton de 4 cm ou plus sur entrevous lourds (béton, terre cuite), sur béton cellulaire
                armé ou sur dalles alvéolées en béton ;
       Plancher bas non isolé ou avec un isolant thermique en sous-face et un revêtement non isolant :
            o   Béton plein de plus de 10 cm d’épaisseur,
            o   Chape ou dalle de béton de 4 cm ou plus sur entrevous lourds (béton, terre cuite), béton cellulaire
                arme ou dalles alvéolées en béton,
            o   Dalle de béton de 5 cm ou plus sur entrevous en matériau isolant,
            o   Autres planchers dans un matériau lourd (pierre, brique ancienne, terre…) sans revêtement isolant.
Un plancher bas (autre que sur terre-plein) dont l’inertie est inconnue est considéré par défaut à inertie lourde.



7.3 Paroi verticale lourde :
    Une paroi verticale est dite lourde si elle remplit l’une ou l’autre des conditions suivantes :

       Lorsque les murs de façade, de pignon et de refends mitoyens sont non isolés ou isolés par l’extérieur avec en
        matériau constitutif :
            o   Béton plein (banche, bloc, préfabriqué) de 7 cm ou plus,
            o   Bloc agglo béton 11 cm ou plus,
            o   Bloc perforé en béton (ou autres matériaux lourds) 10 cm ou plus,
            o   Bloc creux béton 11 cm ou plus,
            o   Brique pleine ou perforée 10,5 cm ou plus,
            o   Tout matériau ancien lourd (pierre, brique ancienne, terre, pisée, …),
            o   Mur sandwich (béton / isolant / béton).
       Murs extérieurs à isolation répartie de 30 cm minimum, avec un cloisonnement réalisé en bloc de béton, en
        brique plâtrière enduite ou en carreau de plâtre de 5 cm minimum ou en béton cellulaire de 7 cm minimum ;

       Environ les trois quarts (en surface) des doublages intérieurs des murs extérieurs et des murs de
        cloisonnements (parois intérieures), font 5 cm minimum et sont réalisés en bloc de béton, brique enduite ou
        carreau de plâtre ;

       Lorsque la taille moyenne des locaux est inférieure à 30 m2 :
            o   Environ les trois quarts des murs de cloisonnement intérieur lourds, réalisés en :
                       Béton plein de 7 cm minimum,
                       Bloc de béton creux ou perforé (ou autres matériaux lourds) de 10 cm minimum,
                       Brique pleine ou perforée de 10,5 cm minimum,
                       Autre brique de 15 cm minimum avec un enduit plâtre sur chaque face.
Les murs inconnus sont considérés à faible inertie.




7.4 Inertie du bâtiment
                  Plancher bas             Plancher haut             Paroi verticale       Classe d'inertie
                     Lourd                     Lourd                    Lourde               Très Lourde
                       -                       Lourd                    Lourde                 Lourde
                     Lourd                       -                      Lourde                 Lourde
                     Lourd                     Lourd                        -                  Lourde
                       -                         -                      Lourde                Moyenne
                       -                       Lourd                        -                 Moyenne
                     Lourd                       -                          -                 Moyenne
                       -                         -                          -                  Légère

En présence de plusieurs types de murs, de planchers hauts ou de planchers bas, l’inertie de la paroi à considérer dans
le tableau ci-dessus est donnée par celle des surfaces majoritaires.

Pour déterminer l’inertie d’un bâtiment de plusieurs niveaux (immeuble ou maison) la démarche est la suivante :

       Déterminer l’inertie de chaque niveau de logements ;
       Considérer que l’inertie du bâtiment est celle la plus représentative en surface habitable ;
       Pour les situations d’égalité, la règle est la suivante :

                                        Inertie des niveaux
                              Lourde ou                                 Inertie bâtiment
                                             Moyenne        Légère
                              très lourde
                                    X            X            X            Moyenne
                                    X            X                          Lourde
                                    X                         X            Moyenne
                                                 X            X            Moyenne
```

## TODO digitalisation

### Balises XML produites par cette section
- `enum_classe_inertie_id`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
