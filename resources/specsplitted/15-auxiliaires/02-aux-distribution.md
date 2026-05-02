---
section_id: "15.2"
title: "Consommation des auxiliaires de distribution"
spec_pages: [98-102]
xml_outputs: ["conso_auxiliaire_distribution_ch", "conso_auxiliaire_distribution_ecs"]
tables: []
depends_on: ["15"]
status: "verbatim"
---

# §15.2 — Consommation des auxiliaires de distribution

> Source : `resources/spec.pdf` p.98-102
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
15.2 Consommation des auxiliaires de distribution
15.2.1 Puissance des circulateurs de chauffage
        Pertes de charge du réseau (kPa) :

                                           ∆𝑃𝑒𝑚𝑛𝑜𝑚 = 0,15 ∗ 𝐿𝑒𝑚 + ∆𝑃𝑒𝑚

Avec :

    -    0,15 kPa/m de pertes de charge linéaires

    -    𝐿𝑒𝑚 : la longueur du réseau le plus défavorisé (m)

    -    ∆𝑃𝑒𝑚 : la perte de charge de l’émetteur (kPa) :

                                     Type d’émetteur                    ΔPem (en kPa) en chaud
                                                                        30 si boucle monotube
                                        Radiateurs
                                                                                10 sinon
                               Plancher/plafond chauffant                          15
                                       Autres cas                                  35

        Calcul de la longueur du réseau le plus défavorisé :
                                                                               0,5
                                                                      𝑆ℎ
                                 𝐿𝑒𝑚 = 5 ∗ 𝐹𝑐𝑜𝑡 ∗ [𝑁𝑖𝑣_𝑖𝑛𝑠𝑡_𝑐ℎ + (            ) ]
                                                                  𝑁𝑖𝑣_𝑖𝑛𝑠𝑡_𝑐ℎ

Avec :

    -    𝑁𝑖𝑣_𝑖𝑛𝑠𝑡_𝑐ℎ : le nombre de niveaux desservis par l’installation de chauffage

    -    𝑆ℎ : surface habitable du bâtiment (m²)

                                                                   Fcot
                                              Emetteur          Chauffage
                                              Plancher            0,156
                                               Autre              0,802


En présence de plusieurs types d’émetteurs, le coefficient Fcot le plus défavorable sera pris, c’est-à-dire pour
l’émetteur « Autre ».

        Calcul de la puissance du circulateur (W)
                                                                                     0,676
                                                                    𝑞𝑣𝑒𝑚𝑛𝑜𝑚                               𝑆ℎ
                  𝑃𝑐𝑖𝑟𝑐𝑒𝑚 = 𝑚𝑎𝑥 (30 ; 6,44 ∗ (∆𝑃𝑒𝑚𝑛𝑜𝑚 ∗                         )            ∗ 𝑚𝑎𝑥 (1 ;       ))
                                                                            𝑆ℎ                            400
                                                                   𝑚𝑎𝑥 (1 ; 400)

Avec :

    -    Le débit nominal du circulateur 𝑞𝑣𝑒𝑚𝑛𝑜𝑚 (m3/h) en mode chaud étant donné par les formules ci-dessous :
                                                                       𝑃𝑛𝑐 ∗ 𝑟𝑎𝑡
                                           𝑞𝑣𝑒𝑚𝑛𝑜𝑚 (𝑐ℎ𝑎𝑢𝑑) =
                                                                     1,163 ∗ 𝛿𝜃𝑑𝑖𝑚



    -    𝛿𝜃𝑑𝑖𝑚 : chute nominale de température de dimensionnement :

                                             Température de
                                                                             δθdim
                                        distribution de chauffage
                                            Moyenne / Basse                  7,5°C
                                                  Haute                      15°C

    -    rat : ratio du besoin couvert par l’équipement

    -    Pnc : puissance nominale en chaud (kW) :

                                           𝑃𝑛𝑐 = 10−3 ∗ (𝐺𝑉 ) ∗ (20 − 𝑇𝑏𝑎𝑠𝑒 )

            𝑇𝑏𝑎𝑠𝑒 : température de base (°C)

            GV correspond aux déperditions par l’enveloppe définies au paragraphe 3



15.2.2 Consommation des auxiliaires de distribution de chauffage
                                           𝐶𝑎𝑢𝑥_𝑑𝑖𝑠𝑡_𝑐ℎ = 𝑃𝑐𝑖𝑟𝑐𝑒𝑚_𝑐ℎ ∗ 𝑁𝑟𝑒𝑓

Avec :

    -    Caux_dist_ch : consommation annuelle des auxiliaires de distribution de chauffage (Wh)

    -    Pcircem_ch : puissance du circulateur de l’installation de chauffage (W)

    -    Nref : nombre d’heures annuel de chauffage (voir paragraphes 18.2 et 18.3)



15.2.3 Consommation des auxiliaires de distribution d’ECS
Les consommations des auxiliaires de distribution pour une installation d’ECS individuelle sont nulles.

Les pertes de distribution (kWh) sont données par :

                                                                0,5 ∗ 𝐿𝑣𝑐
                                              𝑄𝑑,𝑤𝑖𝑛𝑑 ,𝑣𝑐,𝑗 =             ∗ 𝐵𝑒𝑐𝑠𝑗
                                                                   𝑆ℎ

                                               𝑄𝑑,𝑤𝑐𝑜𝑙,𝑣𝑐,𝑗 = 0,112 ∗ 𝐵𝑒𝑐𝑠𝑗

                                               𝑄𝑑,𝑤𝑐𝑜𝑙 ,ℎ𝑣𝑐,𝑗 = 0,028 ∗ 𝐵𝑒𝑐𝑠𝑗

Avec :

    -    𝑄𝑑,𝑤𝑖𝑛𝑑 ,𝑣𝑐,𝑗 : pertes de distribution individuelle en volume chauffé pour le mois j (Wh)

    -    𝑄𝑑,𝑤𝑐𝑜𝑙,𝑣𝑐,𝑗 : pertes de distribution collective en volume chauffé pour le mois j (Wh)

    -    𝑄𝑑,𝑤𝑐𝑜𝑙,ℎ𝑣𝑐,𝑗 : pertes de distribution collective hors volume chauffé pour le mois j (Wh)

    -    Becsj : besoin annuel d’eau chaude sanitaire pour le mois j (Wh)

    -    Lvc : longueur du réseau d’ECS en volume chauffé :

                                                 𝐿𝑣𝑐 = 0,2 ∗ 𝑆ℎ ∗ 𝑅𝑎𝑡𝑒𝑐𝑠

            𝑅𝑎𝑡𝑒𝑐𝑠 : part du besoin d’eau chaude assurée par le générateur :

             □   Si 2 systèmes de production d’ECS sont considérés (voir paragraphe 11.4) : 𝑅𝑎𝑡𝑒𝑐𝑠 = 0,5

             □   Sinon : 𝑅𝑎𝑡𝑒𝑐𝑠 = 1

Pour une installation d’ECS collective, aux consommations d’auxiliaires du générateur, il faut ajouter celles éventuelles
du bouclage ou du traçage de l’ECS :

             o   La prise en compte du bouclage pour l’ECS se fait toujours à l’échelle de l’immeuble pour une
                 installation collective; Dans le cas d’un appartement alimenté par une installation collective d’ECS, les
                 pertes de distribution de l’immeuble nécessaires au calcul des consommations de bouclage sont
                 obtenues en multipliant les pertes de distribution de l’appartement par le rapport de la SHAB de
                 l’immeuble à la SHAB de l’appartement.



Débit au départ de la boucle (m3/h) pour une chute de température de 5°C pour le mois j :

                                                                𝑄𝑑,𝑤,𝑗
                                              𝑞𝑑,𝑤,𝑗 =
                                                         5,815 ∗ 𝑁ℎ𝑝𝑢𝑖𝑠𝑎𝑔𝑒,𝑗

Avec :

    -    𝑁ℎ𝑝𝑢𝑖𝑠𝑎𝑔𝑒,𝑗 : nombre d’heures de puisage pour le mois j (h)

                                                       𝑁ℎ𝑝𝑢𝑖𝑠𝑎𝑔𝑒,𝑗 = 𝑛𝑗𝑗 ∗ 5

         On a en effet puisage d’eau chaude sanitaire entre 7h et 9h ; 18h et 19h ; 20h et 22h, soit 5h par jour.

                njj : Nombre de jours d’occupation sur le mois j (voir paragraphe 11.1)

    -    𝑄𝑑,𝑤,𝑗 : pertes de distribution pour le mois j (Wh) :

                                    𝑄𝑑,𝑤,𝑗 = 𝑄𝑑,𝑤𝑖𝑛𝑑 ,𝑣𝑐,𝑗 + 𝑄𝑑,𝑤𝑐𝑜𝑙 ,𝑣𝑐,𝑗 + 𝑄𝑑,𝑤𝑐𝑜𝑙 ,ℎ𝑣𝑐,𝑗

La longueur par défaut du bouclage d’ECS Lb (en m) est donnée par :

                                                𝑆ℎ
                                 𝐿𝑏 = 4 ∗ √             + 6 ∗ (𝑁𝑖𝑣_𝑖𝑛𝑠𝑡_𝑒𝑐𝑠 − 0,5)
                                           𝑁𝑖𝑣_𝑖𝑛𝑠𝑡_𝑒𝑐𝑠

Avec :

    -    𝑁𝑖𝑣_𝑖𝑛𝑠𝑡_𝑒𝑐𝑠 : nombre de niveaux entre la génération et l’appartement le plus haut desservi

    -    Sh : surface habitable des logements desservis par l’installation d’ECS



La perte de charge dans le bouclage (kPa) est alors :

                                                   ∆𝑝𝑏 = 0,2 ∗ 𝐿𝑏 + 10

La puissance hydraulique du bouclage pour le mois j (W) est :

                                                                𝑞𝑑,𝑤,𝑗 ∗ ∆𝑝𝑏
                                                   𝑃ℎ𝑦𝑑,𝑗 =
                                                                    3,6

L’efficacité du circulateur pour le mois j est :
                                                                       0,324
                                                                      𝑃ℎ𝑦𝑑,𝑗
                                                     𝐸𝑓𝑓𝑐𝑖𝑟𝑏,𝑗 =
                                                                      15,3

La puissance électrique du circulateur pour le mois j (W) est :

                                                                         𝑃ℎ𝑦𝑑,𝑗
                                               𝑃𝑐𝑖𝑟𝑏 ,𝑗 = 𝑚𝑎𝑥 (20 ;               )
                                                                        𝐸𝑓𝑓𝑐𝑖𝑟𝑏,𝑗

La consommation électrique des circulateurs sur une heure (Wh/h) pour le mois j est :

                                                         𝑄𝑐𝑖𝑟𝑏 ,𝑗 = 𝑃𝑐𝑖𝑟𝑏 ,𝑗

La consommation mensuelle du circulateur de bouclage (Wh) est donnée par :

                            𝑄𝑐𝑖𝑟𝑏 ,𝑗 = 𝑁ℎ𝑝𝑢𝑖𝑠𝑎𝑔𝑒,𝑗 ∗ 𝑃𝑐𝑖𝑟𝑏 ,𝑗 + (𝑁ℎ𝑚𝑜𝑖𝑠, 𝑗 − 𝑁ℎ𝑝𝑢𝑖𝑠𝑎𝑔𝑒,𝑗 ) ∗ 20

Avec :

    -    Nhmois,j : nombre d’heure dans le mois j (h)

    -    20 W la puissance appelée lorsqu’il n’y a pas puisage d’eau chaude sanitaire

                                                             𝑁ℎ𝑚𝑜𝑖𝑠, 𝑗 = 𝑛𝑗𝑗 ∗ 24

La consommation annuelle circulateur de bouclage (Wh) est donnée par :

                                                    𝑄𝑐𝑖𝑟𝑏 = ∑ 𝑄𝑐𝑖𝑟𝑏 ,𝑗
                                                                  𝑗

Dans le cas d’un DPE appartement, la consommation annuelle du circulateur de bouclage pour l’appartement est
obtenue en multipliant la consommation annuelle du circulateur de bouclage de l’immeuble par le rapport de la SHAB
de l’appartement à la SHAB de l’immeuble.

             o   Prise en compte du traçage pour l’ECS :

                                           𝑄𝑡𝑟𝑎𝑐 = ∑ 𝑄𝑑,𝑤𝑐𝑜𝑙,𝑣𝑐,𝑗 + 𝑄𝑑,𝑤𝑐𝑜𝑙,ℎ𝑣𝑐,𝑗
                                                     𝑗


La consommation annuelle du traceur (Wh) est :

                                                   𝑄𝑡𝑟𝑎𝑐 = 0,14 ∗ 𝐵𝑒𝑐𝑠

Avec :

    -    Becs : besoin annuel d’eau chaude sanitaire (Wh)

Les auxiliaires des installations d’ECS solaire ne sont pas pris en compte.
```

## TODO digitalisation

### Balises XML produites par cette section
- `conso_auxiliaire_distribution_ch`
- `conso_auxiliaire_distribution_ecs`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
