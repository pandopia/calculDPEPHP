---
section_id: "9.1.1"
title: "Consommation de chauffage (installation seule)"
spec_pages: [57-59]
xml_outputs: ["conso_ch"]
tables: []
depends_on: ["9.1"]
status: "verbatim"
---

# §9.1.1 — Consommation de chauffage (installation seule)

> Source : `resources/spec.pdf` p.57-59
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.1.1 Consommation de chauffage
La consommation de chauffage est calculée pour une consigne de température à 19°C correspondant à un
comportement conventionnel (ou 21°C pour un comportement dépensier).

Le besoin de chauffage sur le mois j Bchj (kWh PCI) est déterminé de la façon suivante :

                                      𝐵𝑉𝑗 ∗ 𝐷𝐻𝑗   (𝑄𝑟𝑒𝑐𝑐ℎ𝑎𝑢𝑓𝑓 + 𝑄𝑔,𝑤𝑟𝑒𝑐 + 𝑄𝑔𝑒𝑛𝑟𝑒𝑐 )
                                                             𝑗         𝑗         𝑗
                             𝐵𝑐ℎ𝑗 =             −
                                        1000                    1000

Avec :

    -    BVj : besoin de chauffage d’un logement par kelvin sur le mois j (W/K) (voir chapitre 2)

    -    DHj : degrés heures de chauffage sur le mois j (°Ch) (différents selon le comportement choisi) voir paragraphe
         18.2 et 18.3

    -    𝑄𝑟𝑒𝑐_𝑐ℎ𝑎𝑢𝑓𝑓_𝑗 : pertes récupérées de distribution d’ECS pour le chauffage sur le mois j (Wh)

    -    𝑄𝑔,𝑤_𝑟𝑒𝑐_𝑗 : pertes récupérées de stockage d’ECS pour le chauffage sur le mois j (Wh)

    -    𝑄𝑔𝑒𝑛_𝑟𝑒𝑐_𝑗 : pertes récupérées de génération pour le chauffage sur le mois j (Wh)

Pertes récupérées de distribution d’ECS pour le chauffage sur le mois j (Wh) :

                                                                𝑄𝑑,𝑤_𝑖𝑛𝑑,𝑣𝑐_𝑗 + 𝑄𝑑,𝑤_𝑐𝑜𝑙,𝑣𝑐_𝑗
                              𝑄𝑟𝑒𝑐_𝑐ℎ𝑎𝑢𝑓𝑓_𝑗 = 0,48 ∗ 𝑁𝑟𝑒𝑓𝑗 ∗
                                                                           8760

Avec :

    -    𝑄𝑑,𝑤_𝑖𝑛𝑑,𝑣𝑐_𝑗 : pertes de la distribution individuelle en volume chauffé pour le mois j (Wh) (voir paragraphe
         15.2.3)

    -    𝑄𝑑,𝑤_𝑐𝑜𝑙,𝑣𝑐_𝑗 : pertes de la distribution collective en volume chauffé pour le mois j (Wh) (voir paragraphe 15.2.3)

Pertes récupérées de stockage d’ECS pour le chauffage sur le mois j (Wh) :

                                                                          𝑄𝑔,𝑤
                                            𝑄𝑔,𝑤_𝑟𝑒𝑐_𝑗 = 0,48 ∗ 𝑁𝑟𝑒𝑓𝑗 ∗
                                                                          8760

Avec :

    -    𝑄𝑔,𝑤 : pertes brutes annuelles de stockage (Wh) (voir paragraphe 14 ou 11.6)

Pertes récupérées de génération pour le chauffage sur le mois j (Wh) :

                                         𝑄𝑔𝑒𝑛_𝑟𝑒𝑐_𝑗 = 0,48 ∗ 𝐶𝑝𝑒𝑟 ∗ 𝑄𝑝0 ∗ 𝐷𝑝𝑒𝑟𝑗

Avec :

    -    𝐶𝑝𝑒𝑟 : part des pertes par les parois égale à 0,75 pour les équipements à ventouse ou assistés par ventilateur
         et 0,5 pour les autres

    -    𝑄𝑝0 : pertes à l’arrêt du générateur (W)

    -    𝐷𝑝𝑒𝑟𝑗 : durée pendant laquelle les pertes sont récupérées sur le mois j (h) :



            Pour les générateurs assurant le chauffage uniquement :

                                                                  1,3 ∗ 𝐵𝑐ℎℎ𝑝_𝑗
                                          𝐷𝑝𝑒𝑟𝑗 = 𝑚𝑖𝑛 (𝑁𝑟𝑒𝑓𝑗 ;                  )
                                                                    0,3 ∗ 𝑃𝑛

            Pour les générateurs assurant l’ECS uniquement :
                                                                     1790
                                                  𝐷𝑝𝑒𝑟𝑗 = 𝑁𝑟𝑒𝑓𝑗 ∗
                                                                     8760

            Pour les générateurs assurant le chauffage et l’ECS :

                                                          1,3 ∗ 𝐵𝑐ℎℎ𝑝_𝑗           1790
                                𝐷𝑝𝑒𝑟𝑗 = 𝑚𝑖𝑛 (𝑁𝑟𝑒𝑓𝑗 ;                    + 𝑁𝑟𝑒𝑓𝑗 ∗      )
                                                            0,3 ∗ 𝑃𝑛              8760

             Avec :

             □   𝑃𝑛 : puissance nominale du générateur (W)

             □   𝐵𝑐ℎℎ𝑝_𝑗 : besoin de chauffage hors pertes récupérées sur le mois j (kWh) :
                                                                      𝐵𝑉𝑗 ∗ 𝐷𝐻𝑗
                                                          𝐵𝑐ℎℎ𝑝_𝑗 =
                                                                        1000

                 Avec :

                     BVj : besoin de chauffage d’un logement par kelvin sur le mois j (W/K) (voir chapitre 2)

                     DHj : degrés heures de chauffage pour le mois j (°Ch)

Ce calcul ne s’applique qu’au générateur pour lesquels des pertes à l’arrêt 𝑄𝑝0 sont prises en compte.

Seules les pertes des générateurs et des ballons de stockage en volume chauffé sont récupérables. Les pertes
récupérées des générateurs d’air chaud sont nulles.

Le besoin annuel de chauffage (𝐵𝑐ℎ) est égal à la somme des besoins mensuels (𝐵𝑐ℎ𝑗 ) sur la période de chauffe :

                                                    𝐵𝑐ℎ = ∑ 𝐵𝑐ℎ𝑗
                                                               𝑗

Les performances des équipements étant données sur une saison de chauffe complète, il n’est donc possible de
calculer la consommation de chauffage Cch (kWh PCI) que sur la saison complète de chauffe (donc sur l’année).

Les émetteurs sont classables en plusieurs catégories selon leur place dans l’installation :

            Emetteurs de base qui sont ceux assurant la plus grande partie du chauffage ;

            Emetteurs d’appoint qui apportent un complément à la base ;

            Emetteurs de salle de bain qui gèrent le chauffage dans les salles de bains.
```

## TODO digitalisation

### Balises XML produites par cette section
- `conso_ch`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
