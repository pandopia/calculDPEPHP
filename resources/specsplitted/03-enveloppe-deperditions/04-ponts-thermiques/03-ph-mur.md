---
section_id: "3.4.3"
title: "Pont thermique Plancher haut / mur"
spec_pages: [36]
xml_outputs: ["k"]
tables: ["tv_pont_thermique_id"]
depends_on: ["3.4"]
status: "verbatim"
---

# §3.4.3 — Pont thermique Plancher haut / mur

> Source : `resources/spec.pdf` p.36
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.4.3 Plancher haut / mur
kph_i/m_j : Valeur du pont thermique de la liaison Plancher haut i/Mur j (W/(m.K))

Terrasse ou plancher haut lourd :

                                                                          Plancher Haut
                             kph_i/m_j                                                         ITI + ITE
                                                      Non Isolé           ITI           ITE
                                    Non Isolé              0,3            0,83          0,4      0,4
                                         ITI               0,27           0,07          0,75    0,07
                                         ITE               0,55           0,76          0,58    0,58
                        Mur
                                         ITR               0,4            0,3           0,48     0,3
                      extérieur
                                     ITI + ITE             0,27           0,07          0,58    0,07
                                     ITI + ITR             0,27           0,07          0,48    0,07
                                     ITE + ITR             0,4            0,3           0,48     0,3

Pour les murs, s’il n’est pas possible de distinguer le type d’isolation (ITI, ITE…), prendre par défaut ITI.

Pour les planchers haut, s’il n’est pas possible de distinguer le type d’isolation (ITI, ITE…), prendre par défaut ITE.

Pour un plancher haut, ITI correspond à une isolation sous plancher haut et ITE à une isolation sur plancher haut.

Les ponts thermiques des planchers haut en structure légère sont négligés.
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
