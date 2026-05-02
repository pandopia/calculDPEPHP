---
section_id: "6.2.2.2"
title: "Masques lointains (Fe2)"
spec_pages: [49-50]
xml_outputs: ["fe2"]
tables: ["tv_coef_masque_lointain_homogene_id", "tv_coef_masque_lointain_non_homogene_id"]
depends_on: ["6.2.2"]
status: "verbatim"
---

# §6.2.2.2 — Masques lointains (Fe2)

> Source : `resources/spec.pdf` p.49-50
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
6.2.2.2 Masques lointains

6.2.2.2.1 Obstacle d’environnement homogène




                                                   Configuration du masque

    Les masques lointains s’appliquent à toute une façade. Les angles sont mesurés à partir du centre de la façade.

       Le tableau ci-dessous donne les valeurs de Fe2 selon la hauteur du masque et l’orientation de la façade :

                                                               Orientation de la façade
                     Hauteur α (°)              Sud                  Est ou Ouest             Nord
                         < 15                    1                         1                    1
                      15 ≤….< 30                0,8                      0,77                 0,82
                      30 ≤….< 60                0,3                       0,4                  0,5
                      60 ≤….< 90                0,1                       0,2                  0,3


   6.2.2.2.2 Obstacle d’environnement non homogène




                                                      Configuration du masque

                                                                      𝑂𝑚𝑏
                                                      𝐹𝑒2 = 1 − ∑
                                                                      100

   Avec :

       -    Omb : l’ombrage créé par l’obstacle sur la paroi

   La méthode d’évaluation est la suivante :

       1. on découpe le champ de vision en quatre secteurs égaux ;

       2. on détermine, pour chacun d'eux, la hauteur moyenne des obstacles ;

       3. on lit dans le tableau ci-dessous les valeurs correspondantes de l'ombrage, Omb :

        Omb                  Façade au Sud ou Nord                                     Façade Est ou Ouest
Hauteur moyenne de       Pour les 2     Pour les 2 secteurs          Pour le secteur     Pour le secteur   Pour les 2 autres
  l'obstacle α, β (°) secteurs latéraux     centraux               latéral vers le sud central vers le sud    secteurs
         < 15                 0                  0                           0                  0                  0
     15 ≤….< 30               4                 14                          14                 17                  5
     30 ≤….< 60              13                 35                          27                 40                 17
     60 ≤….< 90              15                 40                          30                 45                 25

   Les masques lointains sont évalués au niveau de la baie la plus proche du centre de la façade avant de s’appliquer à
   toutes les baies de cette façade.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_coef_masque_lointain_homogene_id`
- [ ] `tv_coef_masque_lointain_non_homogene_id`

### Balises XML produites par cette section
- `fe2`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
