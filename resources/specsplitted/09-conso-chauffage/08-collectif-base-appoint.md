---
section_id: "9.8"
title: "Installation de chauffage collectif avec base + appoint"
spec_pages: [65-66]
xml_outputs: []
tables: []
depends_on: ["9.1"]
status: "verbatim"
---

# §9.8 — Installation de chauffage collectif avec base + appoint

> Source : `resources/spec.pdf` p.65-66
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.8 Installation de chauffage collectif avec base + appoint
La base fonctionne seule tant que la température extérieure est supérieure à une température de dimensionnement
T(°C). A cette température T, le besoin instantané du bâtiment est égal à la puissance utile du générateur en base :

                                                              𝑃𝑒 ∗ 𝐷𝐻14
                                                   𝑇 = 14 −
                                                                 𝐵𝑐ℎ

Avec :

    -    DH14 : degrés heures de base 14 sur la saison de chauffe complète (°Ch) (voir paragraphes 18.2 et 18.3)

    -    Pe : puissance émise utile par le générateur en base (kW) :

                                                   𝑃𝑒 = 𝑃𝑛 ∗ 𝑅𝑑 ∗ 𝑅𝑟 ∗ 𝑅𝑒

         Avec :

            Pn : puissance nominale du générateur en base (kW)

            Rd, Rr et Re : respectivement les rendements annuels de distribution, de régulation et d’émission de
             l’installation de chauffage de base

Le besoin de chauffage assuré par la base Bch_basej (kWhef) est calculé pour le mois j par :

                                                                       𝐷𝐻𝑇𝑗
                                          𝐵𝑐ℎ_𝑏𝑎𝑠𝑒𝑗 = 𝐵𝑐ℎ𝑗 ∗ (1 −            )
                                                                       𝐷𝐻14𝑗

Avec :

    -    DHTj : degré heure base T sur le mois j

                     𝐷𝐻𝑇𝑗 = 𝑁𝑟𝑒𝑓𝑗 ∗ (𝑇𝑒𝑥𝑡𝑗 − 𝑇𝑏𝑎𝑠𝑒) ∗ 𝑋𝑗5 ∗ (14 − 28 ∗ 𝑋𝑗 + 20 ∗ 𝑋𝑗2 − 5 ∗ 𝑋𝑗3 )

         Avec :
                                                             𝑇 − 𝑇𝑏𝑎𝑠𝑒
                                              𝑋𝑗 = 0,5 ∗
                                                           𝑇𝑒𝑥𝑡𝑗 − 𝑇𝑏𝑎𝑠𝑒

            Nrefj : Nombre d’heures de chauffage sur le mois j
           Tbase : Température extérieure de base dans la zone climatique (°C)

           Textj : Température extérieure moyenne dans la zone climatique sur le mois j (°C)

Le besoin annuel est la somme des besoins mensuels :

                                              𝐵𝑐ℎ_𝑏𝑎𝑠𝑒 = ∑ 𝐵𝑐ℎ_𝑏𝑎𝑠𝑒𝑗
                                                               𝑗

La consommation annuelle de chauffage Cch1 liée à la base (kWhef PCI) est :

                                           𝐶𝑐ℎ1 = 𝐵𝑐ℎ_𝑏𝑎𝑠𝑒 ∗ 𝐼𝑁𝑇1 ∗ 𝐼𝑐ℎ1

La consommation annuelle de chauffage Cch2 liée à l’appoint (kWhef PCI) est :

                                       𝐶𝑐ℎ2 = (𝐵𝑐ℎ − 𝐵𝑐ℎ_𝑏𝑎𝑠𝑒) ∗ 𝐼𝑁𝑇2 ∗ 𝐼𝑐ℎ2

L’appoint est supposé être dimensionné pour assurer 50% du besoin.

La base constitué d’un ou plusieurs générateurs collectifs associés à un ou plusieurs émetteurs peut correspondre à
l’une des installations décrite dans ce paragraphe 9.

L’appoint constitué d’un ou plusieurs générateurs individuels associés à un ou plusieurs émetteurs peut correspondre
à l’une des installations décrite dans ce paragraphe 9.
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
