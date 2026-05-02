---
section_id: "3.4.2"
title: "Pont thermique Plancher intermédiaire / mur"
spec_pages: [35]
xml_outputs: ["k"]
tables: ["tv_pont_thermique_id"]
depends_on: ["3.4"]
status: "verbatim"
---

# §3.4.2 — Pont thermique Plancher intermédiaire / mur

> Source : `resources/spec.pdf` p.35
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.4.2 Plancher intermédiaire / mur
kpi_i/m_j : Valeur du pont thermique de la liaison Plancher intermédiaire i/Mur j (W/(m.K))

                                                                         kpi_i/m_j
                                                                Non Isolé        0,86
                                                                   ITI           0,92
                                                                    ITE          0,13
                                              Mur extérieur         ITR          0,24
                                                                 ITI + ITE       0,13
                                                                ITI + ITR        0,24
                                                                ITE + ITR        0,13

Seuls les murs et planchers constitués d’un matériau lourd (béton, brique, …) sont considérés ici. Pour les autres cas
ce pont thermique est pris nul.

Pour les murs, s’il n’est pas possible de distinguer le type d’isolation (ITI, ITE…), prendre par défaut ITI.

Les ponts thermiques des planchers intermédiaires en structure légère (ossature bois ou autre matériau) / murs sont
négligés.

Lorsque le plancher intermédiaire ne sépare pas deux niveaux du lot faisant l’objet du DPE, il faut prendre en compte
dans les calculs seulement la moitié de la valeur tabulée ci-dessus.
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
