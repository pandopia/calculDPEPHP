---
section_id: "9.2"
title: "Installation de chauffage avec du chauffage solaire"
spec_pages: [62]
xml_outputs: []
tables: ["tv_facteur_couverture_solaire_id"]
depends_on: ["9.1"]
status: "verbatim"
---

# §9.2 — Installation de chauffage avec du chauffage solaire

> Source : `resources/spec.pdf` p.62
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.2 Installation de chauffage avec du chauffage solaire
Cette installation est valable seulement pour les maisons individuelles. Une partie de l’énergie destinée au chauffage
est apportée par une installation de panneaux solaires thermiques.

La consommation de chauffage annuelle (kWh PCI) s’exprime donc de la manière suivante :

                                          𝐶𝑐ℎ = 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇 ∗ (1 − 𝐹𝑐ℎ) ∗ 𝐼𝑐ℎ

Avec :

    -    Bch le besoin annuel de chauffage (kWh PCI)

    -    Fch : facteur de couverture solaire pour le chauffage, déterminé à partir du tableau du paragraphe 18.4

    -    Ich : Inverse du rendement de l’installation

Dans cette configuration, tous les émetteurs sont définis en base car ils sont des émetteurs principaux du chauffage.
L’appoint apporté par le solaire se fait en amont de l’émission.

En présence de plusieurs générateurs et émetteurs, la part de la consommation de chauffage assurée par l’installation
est calculée en appliquant les règles du paragraphe 9.1.

En présence de plusieurs émissions, les consommations assurées par chaque générateur dans ce paragraphe doivent
être proratisées selon les règles du paragraphe 9.1.3.
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_facteur_couverture_solaire_id`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
