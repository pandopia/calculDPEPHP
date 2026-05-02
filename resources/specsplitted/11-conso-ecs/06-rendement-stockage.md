---
section_id: "11.6"
title: "Rendement de stockage de l'ECS"
spec_pages: [74-75]
xml_outputs: ["rendement_stockage"]
tables: ["tv_pertes_stockage_id"]
depends_on: ["11"]
status: "verbatim"
---

# §11.6 — Rendement de stockage de l'ECS

> Source : `resources/spec.pdf` p.74-75
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
11.6 Rendement de stockage de l’ECS
Données d’entrée secondaires :

Volume des ballons

Type de ballon

Catégorie des ballons

Type d’alimentation du ballon

L’ensemble de ce paragraphe ne s’applique pas aux chauffe-eau thermodynamiques, traités en partie 14.2.

Le rendement de stockage est calculé annuellement.

S’il n’y a pas de stockage : Q g,w = 0



11.6.1 Pertes de stockage des ballons d’accumulation
La présence d’un ballon de préparation de l’ECS est responsable de pertes de stockage Qg,w (Wh) :

                                                  𝑄𝑔,𝑤 = 67662 ∗ 𝑉𝑠 0,55

Avec :

    -    Vs : volume du ballon de stockage (litres)
11.6.2 Pertes des ballons électriques
Les pertes de stockage des ballons électriques (Wh) sont données par la relation suivante :

                                                                   45
                                                 Q g,w = 8592 ∗       ∗ Vs ∗ Cr
                                                                   24

Avec :

    -    Vs : volume du ballon de stockage (litres)

    -    Cr : coefficient de perte du ballon de stockage (Wh/l.°C.jour) :

                                                                                  Volume du ballon (litre)
             Coefficient de perte (Cr)
                                                           ≤ 100            100 < ≤ 200          200 < ≤ 300   > 300
            Chauffe-eau horizontal                         0,39                   0,33                0,3       0,3
                      Autres ou inconnue                   0,32                   0,23                0,22     0,22
   Chauffe-eau      Catégorie B ou 2 étoiles               0,27                   0,22                0,2      0,18
     vertical
                    Catégorie C ou 3 étoiles               0,25                    0,2                0,18     0,16



11.6.3 Rendement de stockage
    -    Pour les ballons électriques verticaux de catégorie C ou 3*,
                                                              1,08
                                                    𝑅𝑠 =
                                                              𝑄𝑔,𝑤 ∗ 𝑅𝑑
                                                           1+
                                                                𝐵𝑒𝑐𝑠

    -    Pour les autres ballons électriques :
                                                                1
                                                    𝑅𝑠 =
                                                              𝑄𝑔,𝑤 ∗ 𝑅𝑑
                                                           1 + 𝐵𝑒𝑐𝑠

Avec :

    -    Q g,w : pertes de stockage (Wh)

    -    Rd : rendement de distribution

    -    Becs : besoin annuel d’ECS (Wh)
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_pertes_stockage_id`

### Balises XML produites par cette section
- `rendement_stockage`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
