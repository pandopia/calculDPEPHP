---
section_id: "3.4.1"
title: "Pont thermique Plancher bas / mur"
spec_pages: [35]
xml_outputs: ["k"]
tables: ["tv_pont_thermique_id"]
depends_on: ["3.4"]
status: "verbatim"
---

# §3.4.1 — Pont thermique Plancher bas / mur

> Source : `resources/spec.pdf` p.35
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.4.1 Mur Plancher bas / mur
kpb_i/m_j : Valeur du pont thermique de la liaison Plancher bas i/Mur j (W/(m.K))

                            kpb_i/m_j                                      Plancher Bas
                                                    Non Isolé            ITI            ITE    ITI + ITE
                                   Non Isolé          0,39           0,47               0,8      0,47
                                        ITI           0,31           0,08               0,71     0,08
                                        ITE           0,49           0,48               0,64     0,48
                       Mur
                                       ITR            0,35           0,1                0,45     0,1
                     extérieur
                                    ITI + ITE         0,31           0,08               0,45     0,08
                                    ITI + ITR         0,31           0,08               0,45     0,08
                                    ITE + ITR         0,35            0,1               0,45     0,1

Seuls les murs et planchers bas constitués d’un matériau lourd (béton, brique, …) sont considérés ici. Pour les autres
cas ce pont thermique est pris nul.

Pour les murs, s’il n’est pas possible de distinguer le type d’isolation (ITI, ITE…), prendre par défaut ITI.

Pour les planchers bas, s’il n’est pas possible de distinguer le type d’isolation (ITI, ITE…), prendre par défaut ITE.

Pour un plancher bas, ITI correspond à une isolation sous chape et ITE à une isolation en sous face.

Les planchers en hourdis polystyrène sont traités comme des planchers avec ITE.
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
