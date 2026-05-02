---
section_id: "3.4.4"
title: "Pont thermique Refend / mur"
spec_pages: [36]
xml_outputs: ["k"]
tables: ["tv_pont_thermique_id"]
depends_on: ["3.4"]
status: "verbatim"
---

# §3.4.4 — Pont thermique Refend / mur

> Source : `resources/spec.pdf` p.36
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.4.4 Refend / mur
krf/m_j : Valeur du pont thermique de la liaison Refend/Mur j (W/(m.K))

                                                                     krf/m_j
                                                             Non Isolé       0,73
                                                                  ITI            0,82
                                                                  ITE            0,13
                                                 Mur
                                                                 ITR             0,2
                                               extérieur
                                                              ITI + ITE          0,13
                                                              ITI + ITR          0,2
                                                              ITE + ITR          0,13

Seuls les murs et refends constitués d’un matériau lourd (béton, brique, …) sont considérés ici. Pour les autres cas ce
pont thermique est pris nul.

Pour les murs, s’il n’est pas possible de distinguer le type d’isolation (ITI, ITE…), prendre par défaut ITI.

Les ponts thermiques des parois sur circulation sont négligés pour les appartements et les immeubles.

Lorsque le refend ne sépare pas deux volumes du même lot faisant l’objet du DPE, il faut prendre en compte dans les
calculs seulement la moitié de la valeur tabulée ci-dessus.
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
