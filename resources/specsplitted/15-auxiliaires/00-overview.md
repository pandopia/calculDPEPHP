---
section_id: "15"
title: "Calcul des consommations d'auxiliaires Caux_ch et Caux_ecs"
spec_pages: [96]
xml_outputs: []
tables: []
depends_on: ["9", "11"]
status: "verbatim"
---

# §15 — Calcul des consommations d'auxiliaires Caux_ch et Caux_ecs

> Source : `resources/spec.pdf` p.96
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
15 Calcul des consommations d’auxiliaires des installations de
  chauffage (Caux_ch) et d’ECS (Caux_ecs)
Les consommations des auxiliaires des installations de chauffage, de refroidissement et d’ECS sont la somme des
consommations des auxiliaires de génération et de distribution.

Consommation des auxiliaires des installations de chauffage :

                                      𝐶𝑎𝑢𝑥_𝑐ℎ = 𝐶𝑎𝑢𝑥_𝑔𝑒𝑛_𝑐ℎ + 𝐶𝑎𝑢𝑥_𝑑𝑖𝑠𝑡_𝑐ℎ

Avec :

    -    Caux_gen_ch : consommation annuelle des auxiliaires de génération de l’installation de chauffage (Wh) :

                                              𝐶𝑎𝑢𝑥_𝑔𝑒𝑛_𝑐ℎ = 𝑄𝑎𝑢𝑥_𝑔_𝑐ℎ

                Qaux_g_ch : consommation annuelle des auxiliaires de génération de l’installation de chauffage (Wh)

    -    Caux_dist_ch : consommation annuelle des auxiliaires de distribution de l’installation de chauffage (Wh)

Consommation des auxiliaires des installations d’ECS :

                                    𝐶𝑎𝑢𝑥_𝑒𝑐𝑠 = 𝐶𝑎𝑢𝑥_𝑔𝑒𝑛_𝑒𝑐𝑠 + 𝐶𝑎𝑢𝑥_𝑑𝑖𝑠𝑡_𝑒𝑐𝑠

Avec :

    -    Caux_gen_ecs : consommation annuelle des auxiliaires de génération de l’installation d’ECS (Wh)

                                             𝐶𝑎𝑢𝑥_𝑔𝑒𝑛_𝑒𝑐𝑠 = 𝑄𝑎𝑢𝑥_𝑔_𝑒𝑐𝑠

                Qaux_g_ecs : consommation annuelle des auxiliaires de génération de l’installation d’ECS (Wh)

    -    Caux_dist_ecs : consommation annuelle des auxiliaires de distribution de l’installation d’ECS (Wh) :

                                           𝐶𝑎𝑢𝑥_𝑑𝑖𝑠𝑡_𝑒𝑐𝑠 = 𝑄𝑐𝑖𝑟_𝑏 + 𝑄𝑡𝑟𝑎𝑐

                Qcir_b : consommation annuelle du circulateur de bouclage (Wh)

                Qtrac : consommation annuelle du traceur (Wh)

Les consommations des auxiliaires de distribution de chauffage et d’ECS sont prises nulles pour les installations
individuelles en l’absence d’un circulateur externe au générateur.

Pour les installations de refroidissement, les consommations des auxiliaires de génération sont prises en compte dans
le SEER (EER). Seules les consommations des auxiliaires de distribution sont donc à comptabiliser :

                                              𝐶𝑎𝑢𝑥_𝑓𝑟 = 𝐶𝑎𝑢𝑥_𝑑𝑖𝑠𝑡_𝑓𝑟

Avec :

    -    Caux_dist_fr : consommation annuelle des auxiliaires de distribution de l’installation de refroidissement (Wh)
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
