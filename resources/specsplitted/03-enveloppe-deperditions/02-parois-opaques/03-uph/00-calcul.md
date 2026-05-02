---
section_id: "3.2.3"
title: "Calcul des Uplancher haut (Uph)"
spec_pages: [21]
xml_outputs: ["uph", "uph0"]
tables: ["tv_uph_id", "tv_uph0_id"]
depends_on: ["3.1"]
status: "verbatim"
---

# §3.2.3 — Calcul des Uplancher haut (Uph)

> Source : `resources/spec.pdf` p.21
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.2.3 Calcul des Uplancher haut (Uph)
3.2.3.1 Schéma du calcul de Uph
                                                                                                Uph_tab :
                                        Uph




                                                                                                                 Année de construction ou d'isolation
                             Oui
            Saisir Uph                Connu ?                                                  ≤74 ou inconnue
                                                                                                    75-77
                                                                                                    78-82
                                                                                                    83-88
                                    Non                                                             89-00
                                                                                                    01-05
                                                                                                    06-12
                                   Type de plafond                                                   ≥13



                                                      Non
                                      Connu ?                     Uph0 = 2,5
                                                                                                         H1
                                                                                               Effet joule Autres
                                                                                                   2,5       2,5
                                     Oui                                                           0,5       0,5
                                                                                                   0,4       0,5
                                                                                                   0,3       0,3
                                       Uph0                                                       0,25      0,25
                                                                                                  0,23      0,23
                                                                                                   0,2       0,2
                                                                                                  0,14      0,14

                                      Non                              Inconnue     Uph =             Combles
                                                                                                         H2
                 Uph = Uph0                         Isolation ?                   min(Uph0 ;   Effet joule Autres
                                                                                                   2,5       2,5
                                                                                  Uph_tab)        0,53      0,53
                                                                                                  0,42      0,53
                                                   Oui                                            0,32
                                                                                                  0,26
                                                                                                            0,32
                                                                                                            0,26
                                                                                                  0,23      0,23
                                                                                                   0,2       0,2
                                              Résistance isolant                                  0,14      0,14
                                                                                                         H3
                                                                                               Effet joule Autres
                         1                                                                         2,5       2,5
        Uph =                              Oui                                                    0,56      0,56
                 1                                   Connu ?                                      0,44      0,56
                     + R isolant                                                                  0,33      0,33
                Uph0                                                                               0,3       0,3
                                                                                                   0,3       0,3
                                                   Non                                            0,25      0,25
                                                                                                  0,14      0,14

                                                 Epaisseur isolant                                       H1
                                                                                               Effet joule Autres
                                                                                                   2,5       2,5
                                                                                                  0,75      0,75
                                                                                                   0,7      0,75
                         1                 Oui                                                     0,4      0,55
        Uph =                                                                                     0,35       0,4
                 1     e                             Connu ?                                       0,3       0,3
                    +
                Uph0 0,04                                                                         0,27
                                                                                                  0,14
                                                                                                            0,27
                                                                                                            0,14
                                                   Non                                                 Terrasse
                                                                                                          H2
                                                                                               Effet joule Autres
                                                 Année d’isolation                                 2,5         2,5
                                                                                                  0,79        0,79
                                                                                                  0,74        0,79
                                                                                                  0,42        0,58
                                                                                                  0,37        0,42
                                                                           Uph =                   0,3         0,3
                                                                                                  0,27        0,27
                                                     Connu ?             min(Uph0 ;               0,14        0,14
                                                                     Oui Uph_tab)
                                                                                                         H3
                                                                                               Effet joule Autres
                                                   Non                                             2,5       2,5
                                                                                                  0,83      0,83
                                                                                                  0,78      0,83
            Année de construction ≤74 alors Année d’isolation = 75-77                             0,44      0,61
                                                                                                  0,39      0,44
                                                                                                   0,3       0,3
                 Sinon Année d’isolation = Année de construction                                  0,27      0,27
                                                                                                  0,14      0,14

Lorsque le local au-dessus du logement est un local non chauffé, ou un local autre que d’habitation., Uph_tab est
pris dans la catégorie « Terrasse ».
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_uph_id`
- [ ] `tv_uph0_id`

### Balises XML produites par cette section
- `uph`
- `uph0`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
