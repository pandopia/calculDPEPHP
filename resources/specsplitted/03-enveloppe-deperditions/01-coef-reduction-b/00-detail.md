---
section_id: "3.1"
title: "Détermination du coefficient de réduction des déperditions b"
spec_pages: [8-12]
xml_outputs: ["b"]
tables: ["tv_coef_reduction_deperdition_id"]
depends_on: ["3"]
status: "verbatim"
---

# §3.1 — Détermination du coefficient de réduction des déperditions b

> Source : `resources/spec.pdf` p.8-12
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.1 Détermination du coefficient de réduction des déperditions b
Données d’entrée :

Surface des parois séparant le local non chauffé des locaux chauffés : Aiu (m2)

Surface des parois séparant le local non chauffé de l’extérieur ou du sol : Aue (m2)

Type de local non chauffé (garage, comble, circulation…)

Etat d’isolation des parois du local non chauffé (isolées, non isolées)

Pour une paroi enterrée ou donnant sur l’extérieur, ou un plancher sur terre-plein, vide sanitaire ou sous-sol non
chauffé, b = 1.

Dans le cas de locaux non chauffés non accessibles (mitoyenneté, espace sans accès…), forfaitairement b = 0,95.

Les parois donnant sur un bâtiment ou un espace autre que d’habitation (occupation discontinue) sont considérées
comme déperditives avec b = 0,2.

Pour les circulations communes au niveau d’un appartement en bâtiment collectif d’habitation, le calcul de b se fait
en considérant les parois situées au même niveau que le lot traité. Pour un calcul fait à l’immeuble, un seul b est pris
pour toutes les circulations communes si elles ne sont pas en volume intérieur chauffé.

La méthode de caractérisation des espaces communs en volume chauffé ou non chauffé est détaillée au §17.1.1.3.
Une paroi donnant sur un volume non intérieur ou sur un volume intérieur non chauffé sera considérée comme
déperditive. Le b sera déterminé à l’aide de la méthode suivante.

Dans les autres cas, b est déterminé à l’aide des tableaux suivants, en fonction du rapport des surfaces Aiu/Aue et du
coefficient surfacique équivalent UV,ue :

    -   Aue est la surface des parois du local non chauffé donnant sur l’extérieur ou en contact avec le sol (paroi
        enterrée, terre-plein)
    -   Aiu est la surface des parois du local non chauffé qui donnent sur des locaux chauffés

Il est considéré qu’il n’y a pas d’échange entre deux locaux non chauffés distincts (sans liaison aéraulique). La surface
des parois du local non chauffé donnant sur un vide sanitaire ou un autre local non chauffé n’entre donc ni dans Aiu ni
dans Aue.




Le coefficient surfacique équivalent UV,ue est déterminé via le tableau ci-dessous :

                                    Locaux non chauffés types                          UV,ue W/(m2.K)
          Maison individuelle
           Garage                                                                           3
           Cellier                                                                          3
           Comble
              - fortement ventilé                                                            9
              - faiblement ventilé                                                           3
              - très faiblement ventilé                                                     0,3
          Logement collectif
           Circulations communes
              - sans ouverture directe sur l’extérieur                                       0,0
              - avec ouverture directe sur l’extérieur                                       0,3
              - avec bouche ou gaine de désenfumage, ouverte en permanence                    3
              - halls d’entrée                                                          3 ou 0,3(2)
                                                                                         (1)

              - garage privé collectif                                                        3
           Autres dépendances                                                                3
           Comble
                  - fortement ventilé                                                        9
                  - faiblement ventilé                                                       3
              - très faiblement ventilé                                                     0,3
          (1) Portes d'accès sans dispositif de fermeture automatique
          (2) Portes d’accès avec dispositif de fermeture automatique



L’identification du niveau de ventilation des combles peut s’appuyer sur les définitions ci-dessous. Cependant, la
présence d’ouvertures dans les parois des combles doit aussi être prise en compte pour déterminer leur niveau de
ventilation :

    -   Combles fortement ventilés : combles couverts en tuiles ou autres éléments de couverture discontinus, sans
        support continu ;
    -   Combles faiblement ventilés : combles couverts avec éléments de couverture continus sur support discontinu,
        ou avec éléments de couverture discontinus sur support continu ;
    -   Combles très faiblement ventilés : combles couverts avec éléments de couverture continus sur support
        continu.

Dans le cas où Aue = 0, alors b = 0.

Dans les tableaux suivants :

    -   lnc désigne un local non chauffé ;
    -   lc désigne le local chauffé.

Les parois du local non chauffé sont considérées comme isolées si plus de 50% de leur surface est isolée.

Les parois en double vitrage et les portes seront considérées comme non isolées pour le calcul de b. Les parois en
triple vitrage seront considérées isolées.

Les parois déperditives dont l’état d’isolation n’est pas connu sont considérées :

    -   Pour les bâtiments d’avant 1975, la paroi est considérée comme non isolée ;
    -   Pour les bâtiments construits à partir de 1975 :
           o Les murs sont considérés comme isolés par l’intérieur ;
           o Les plafonds sont considérés isolés par l’extérieur ;
           o Les planchers sur terre-plein sont considérés isolés par l’extérieur (en sous face) à partir de 2001.

On en déduit la valeur de b en fonction des différents cas suivants :




                                           UV,ue                                               UV,ue
                   Aiu/Aue                                              Aiu/Aue
                                  0,0    0,3    3,0     9,0                           0,0    0,3    3,0    9,0
                         ≤ 0,25   0,95   0,95   1,00   1,00                  ≤ 0,25   0,80   0,85   0,90   0,95
              0,25 <     ≤ 0,50   0,95   0,95   0,95   1,00      0,25 <      ≤ 0,50   0,65   0,75   0,80   0,90
              0,50 <     ≤ 0,75   0,90   0,95   0,95   1,00      0,50 <      ≤ 0,75   0,55   0,65   0,75   0,85
              0,75 <     ≤ 1,00   0,85   0,90   0,95   0,95      0,75 <      ≤ 1,00   0,50   0,55   0,70   0,80
              1,00 <     ≤ 1,25   0,85   0,90   0,90   0,95      1,00 <      ≤ 1,25   0,45   0,50   0,65   0,80
              1,25 <     ≤ 2,00   0,80   0,80   0,90   0,95      1,25 <      ≤ 2,00   0,35   0,40   0,50   0,70
              2,00 <     ≤ 2,50   0,75   0,80   0,85   0,90      2,00 <      ≤ 2,50   0,30   0,35   0,45   0,65
              2,50 <     ≤ 3,00   0,70   0,75   0,85   0,90      2,50 <      ≤ 3,00   0,25   0,30   0,40   0,60
              3,00 <     ≤ 3,50   0,65   0,75   0,80   0,90      3,00 <      ≤ 3,50   0,20   0,30   0,40   0,55
              3,50 <     ≤ 4,00   0,65   0,70   0,80   0,90      3,50 <      ≤ 4,00   0,20   0,25   0,35   0,50
              4,00 <     ≤ 6,00   0,55   0,60   0,70   0,85      4,00 <      ≤ 6,00   0,15   0,20   0,25   0,40
              6,00 <     ≤ 8,00   0,45   0,55   0,65   0,80      6,00 <      ≤ 8,00   0,10   0,15   0,20   0,35
              8,00 <    ≤ 10,00   0,40   0,50   0,60   0,75      8,00 <     ≤ 10,00   0,10   0,10   0,20   0,30
              10,00 <   ≤ 25,00   0,35   0,40   0,50   0,70      10,00 <    ≤ 25,00   0,05   0,10   0,15   0,25
              25,00 <   ≤ 50,00   0,20   0,25   0,35   0,50      25,00 <    ≤ 50,00   0,05   0,05   0,05   0,15
              50,00 <             0,10   0,15   0,20   0,30      50,00 <              0,00   0,00   0,05   0,05


                                          UV,ue                                               UV,ue
                 Aiu/Aue                                             Aiu/Aue
                                  0,0    0,3     3,0    9,0                          0,0    0,3    3,0    9,0
                        ≤ 0,25   0,35   0,50    0,85   0,95                 ≤ 0,25   0,80   0,90   0,95   1,00
             0,25 <     ≤ 0,50   0,20   0,35    0,70   0,90      0,25 <     ≤ 0,50   0,65   0,80   0,95   1,00
             0,50 <     ≤ 0,75   0,15   0,25    0,65   0,85      0,50 <     ≤ 0,75   0,55   0,70   0,90   0,95
             0,75 <     ≤ 1,00   0,15   0,20    0,55   0,80      0,75 <     ≤ 1,00   0,50   0,65   0,90   0,95
             1,00 <     ≤ 1,25   0,10   0,15    0,50   0,75      1,00 <     ≤ 1,25   0,45   0,60   0,90   0,95
             1,25 <     ≤ 2,00   0,05   0,10    0,40   0,65      1,25 <     ≤ 2,00   0,35   0,45   0,80   0,95
             2,00 <     ≤ 2,50   0,05   0,10    0,35   0,60      2,00 <     ≤ 2,50   0,30   0,40   0,80   0,90
             2,50 <     ≤ 3,00   0,05   0,10    0,30   0,55      2,50 <     ≤ 3,00   0,25   0,35   0,75   0,90
             3,00 <     ≤ 3,50   0,05   0,05    0,25   0,50      3,00 <     ≤ 3,50   0,20   0,35   0,70   0,90
             3,50 <     ≤ 4,00   0,05   0,05    0,25   0,45      3,50 <     ≤ 4,00   0,20   0,30   0,70   0,85
             4,00 <     ≤ 6,00   0,00   0,05    0,20   0,35      4,00 <     ≤ 6,00   0,15   0,25   0,60   0,80
             6,00 <     ≤ 8,00   0,00   0,05    0,15   0,30      6,00 <     ≤ 8,00   0,10   0,20   0,55   0,75
             8,00 <    ≤ 10,00   0,00   0,05    0,10   0,25      8,00 <    ≤ 10,00   0,10   0,15   0,45   0,70
             10,00 <   ≤ 25,00   0,00   0,00    0,10   0,20      10,00 <   ≤ 25,00   0,05   0,10   0,40   0,65
             25,00 <   ≤ 50,00   0,00   0,00    0,05   0,10      25,00 <   ≤ 50,00   0,05   0,05   0,25   0,45
             50,00 <             0,00   0,00    0,00   0,05      50,00 <             0,00   0,05   0,10   0,30

Les espaces tampons solarisés (vérandas, loggias fermées) non chauffés bénéficient d’apports solaires qui y génèrent
des températures supérieures à celles atteintes dans les espaces non solarisés.

Rappelons que les vérandas chauffées sont traitées en surface habitable.

Dans le cas de vérandas ou loggias fermées non chauffées, les coefficients de réduction de température pris sont
donnés dans le tableau ci-dessous :

           Zone climatique       Orientation de la véranda    Paroi donnant sur la véranda         bver
                                                                         Isolé                     0.95
                                           Nord
                                                                       Non isolé                   0.85
                                                                         Isolé                     0.63
                 H1                     Est / Ouest
                                                                       Non isolé                   0.6
                                                                         Isolé                     0.58
                                               Sud
                                                                       Non isolé                   0.55
                                                                         Isolé                     0.95
                                           Nord
                                                                       Non isolé                   0.85
                                                                         Isolé                     0.6
                 H2                     Est / Ouest
                                                                       Non isolé                   0.58
                                                                         Isolé                     0.57
                                               Sud
                                                                       Non isolé                   0.55
                                                                         Isolé                     0.95
                                           Nord
                                                                       Non isolé                   0.85
                                                                         Isolé                     0.53
                 H3                     Est / Ouest
                                                                       Non isolé                   0.53
                                                                         Isolé                     0.48
                                               Sud
                                                                       Non isolé                   0.55


Les orientations Nord intègrent les limites Nord-Est et Nord-Ouest.

Les orientations Sud intègrent les limites Sud-Est et Sud-Ouest.

L’orientation de la véranda prise en compte est celle de sa façade principale (avec la plus grande surface de vitrages
verticaux). S’il existe plusieurs façades principales, c’est-à-dire qu’au moins deux façades d’orientation présentent de
façon égale les surfaces vitrées les plus importantes, bver est la moyenne des bver sur ces orientations.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_coef_reduction_deperdition_id`

### Balises XML produites par cette section
- `b`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
