---
section_id: "3.2.1"
title: "Calcul des Umur"
spec_pages: [13]
xml_outputs: ["umur", "umur0"]
tables: ["tv_umur_id", "tv_umur0_id"]
depends_on: ["3.1"]
status: "verbatim"
---

# §3.2.1 — Calcul des Umur

> Source : `resources/spec.pdf` p.13
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.2.1 Calcul des Umur
3.2.1.1 Schéma du calcul de Umur


                                        Umur


                              Oui                                                             Umur_tab :
            Saisir Umur               Connu ?

                                     Non

                                    Type de mur


                                                      Non
                                       Connu ?                   Umur_nu = 2,5

                                     Oui

                                Umur_nu=
                              Min(Umur0 ; 2,5)

                                      Non                          Inconnue      Umur =
            Umur = Umur_nu                         Isolation ?                Min(Umur_nu ;
                                                                               Umur_tab)

                                                                           Oui


                                              Résistance isolant

                                               Oui
                          1
     Umur =
                1                                    Connu ?
              Umur_nu + R isolant
                                                   Non

                                               Epaisseur isolant

                                             Oui
                      1
      Umur =                                         Connu ?
                 1         e
               Umur_nu + 0,04
                                                   Non

                                               Année d’isolation


                                                                              Umur =
                                                     Connu ?               Min(Umur_nu ;
                                                                    Oui
                                                                            Umur_tab)
                                               Non

                                     Si Année de construction ≤74 alors Année d’isolation = 75-77

                                            Sinon Année d’isolation = Année de construction
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_umur_id`
- [ ] `tv_umur0_id`

### Balises XML produites par cette section
- `umur`
- `umur0`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
