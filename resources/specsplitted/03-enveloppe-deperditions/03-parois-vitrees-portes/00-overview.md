---
section_id: "3.3"
title: "Calcul des U des parois vitrées et des portes"
spec_pages: [22-23]
xml_outputs: []
tables: []
depends_on: ["3"]
status: "verbatim"
---

# §3.3 — Calcul des U des parois vitrées et des portes

> Source : `resources/spec.pdf` p.22-23
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.3 Calcul des U des parois vitrées et des portes
Données d’entrée :

Type de vitrage (simple, double…)

Epaisseur lame d’air

Présence d’une couche peu émissive

Gaz de remplissage

Inclinaison vitrage

Type de menuiserie

Type de volets

Type de porte

Les grandes surfaces vitrées des vérandas chauffées seront traitées comme des portes-fenêtres avec des menuiseries
au nu extérieur.

Les parois en brique de verre sont traitées comme des parois vitrées avec :

    -    Brique de verre pleine Uw = 3,5 W/(m².K)

    -    Brique de verre creuse Uw = 2 W/(m².K)

Les parois en polycarbonate sont traitées comme des parois vitrées avec : Uw = 3 W/(m².K)

Définition de l’inclinaison des baies pour le calcul des U :

    -    Paroi verticale = angle par rapport à l’horizontal ≥ 75°

    -    Paroi horizontale = angle par rapport à l’horizontal < 75°

Le coefficient U des fenêtres est connu : saisir Uw et caractériser les occultations pour déterminer Ujn.

Si Uw est inconnu alors suivre la démarche suivante :


                                   Détermination de Ug à partir du type de vitrage

                  Détermination de Uw à partir de Ug, du type de paroi vitrée et de la menuiserie


                            Détermination de Ujn à partir de Uw et du type de fermeture


                         Si présence de protections solaires Ubaie = Ujn Sinon : Ubaie = Uw


Avec :

    -    Ug : coefficient de transmission thermique du vitrage (W/(m².K))

    -    Uw : coefficient de transmission thermique de la fenêtre ou de la porte-fenêtre (vitrage + menuiserie)
         (W/(m².K))

    -    Ujn : coefficient de transmission thermique de la fenêtre ou de la porte-fenêtre avec les protections solaires
         (W/(m².K))
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
