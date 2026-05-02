---
section_id: "3.4.5"
title: "Pont thermique Menuiserie / mur"
spec_pages: [37]
xml_outputs: ["k"]
tables: ["tv_pont_thermique_id"]
depends_on: ["3.4"]
status: "verbatim"
---

# §3.4.5 — Pont thermique Menuiserie / mur

> Source : `resources/spec.pdf` p.37
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.4.5 Menuiserie / mur
kmen_i/m_j : Valeur du pont thermique de la liaison Menuiserie i/Mur j (W/(m.K))

On entend par menuiserie les fenêtres, portes ou portes-fenêtres.

                                                                                Menuiserie
                                                               Au nu                                   Au nu
                              kmen_i/m_j                                         En tunnel
                                                              extérieur                              intérieur
                                                            Lp=5     Lp=10     Lp=5     Lp=10      Lp=5   Lp=10
                                     Non isolé              0,43     0,29      0,31         0,19   0,38    0,25
                            ITI avec retour d’isolant       0,22     0,18      0,16         0,13    0        0
                            ITI sans retour d’isolant       0,43     0,29      0,31         0,19    0        0
                            ITE avec retour d’isolant         0        0       0,19         0,15   0,25     0,2
                            ITE sans retour d’isolant         0        0       0,45         0,4    0,9      0,8
                                           ITR                                        0,2
               Mur
                          ITI+ITE avec retour d’isolant       0        0       0,16         0,13    0        0
                          ITI+ITE sans retour d’isolant       0        0       0,31         0,19    0        0
                          ITI+ITR avec retour d’isolant      0,2     0,18      0,16         0,13    0        0
                          ITI+ITR sans retour d’isolant      0,2      0,2      0,2          0,19    0        0
                          ITE+ITR avec retour d’isolant       0        0       0,19         0,15   0,2      0,2
                          ITE+ITR sans retour d’isolant       0        0       0,2          0,2    0,2      0,2

Avec :

    -    Lp est la largeur approximative (arrondie à la valeur la plus proche apparaissant dans le tableau) du dormant
         de la menuiserie (cm).

En cas de double-fenêtre, la largeur du dormant est la plus importante des deux.

Pour les murs, s’il n’est pas possible de distinguer le type d’isolation (ITI, ITE…), prendre par défaut ITI.

Ces valeurs de pont thermique sont valables pour les appuis, tableaux et le linteau de la menuiserie.

Les ponts thermiques au niveau des seuils de porte et de porte-fenêtre ne sont pas pris en compte.

Les ponts thermiques avec les parois en structure bois (ossature bois, rondin de bois, pans de bois) sont négligés.

Les ponts thermiques au niveau des fenêtres de toit sont négligés.

Les ponts thermiques pour la liaison mur / pavés de verre, plancher pavé de verre et plafond pavés de verre ne sont
pas pris en compte.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_pont_thermique_id`

### Balises XML produites par cette section
- `k`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
