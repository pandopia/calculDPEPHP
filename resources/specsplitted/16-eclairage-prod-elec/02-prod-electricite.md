---
section_id: "16.2"
title: "Production d'électricité (PV)"
spec_pages: [103-106]
xml_outputs: ["production_pv", "conso_elec_ac"]
tables: ["tv_coef_orientation_pv_id"]
depends_on: ["16"]
status: "verbatim"
---

# §16.2 — Production d'électricité (PV)

> Source : `resources/spec.pdf` p.103-106
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
16.2 Production d’électricité
Seule la production d’électricité renouvelable par des capteurs photovoltaïques est prise en compte. Cependant, la
présence de production d’électricité éolienne ou par cogénération devra être mentionnée.

La production d’électricité par des capteurs photovoltaïques Ppv (en kWh/m²) s’exprime de la manière suivante :

                                                      𝑃𝑝𝑣 = ∑ 𝑃𝑝𝑣𝑗
                                                              𝑗

                                                     𝑘𝑖 ∗ 𝑆𝑐𝑎𝑝𝑡𝑒𝑢𝑟𝑠𝑖 ∗ 𝑟 ∗ 𝐸𝑝𝑣𝑗 ∗ 𝐶
                                      𝑃𝑝𝑣𝑗 = ∑
                                                                  𝑆ℎ
                                                 𝑖

Avec :

    -    Scapteursi : surface des panneaux photovoltaïques i orientés et inclinés de la même manière (m²)
         Si la surface des panneaux n’est pas connue et ne peut être mesurée : 𝑆𝑐𝑎𝑝𝑡𝑒𝑢𝑟𝑠𝑖 = 1,6 ∗ 𝑁𝑚
         Avec :
          1,6 : surface par défaut d’un module photovoltaïque (m²)

            Nm : nombre de modules.

    -    r : rendement moyen des modules = 17%

    -    Epv_j : ensoleillement en kWh/m² pour le mois j (voir partie 18.2)

    -    C : coefficient de perte = 0,86

    -    ki : coefficient de pondération prenant en compte l’altération par rapport à l’orientation optimale (30° au
         Sud) des panneaux photovoltaïques i :


                                      Inclinaison des panneaux photovoltaïques par rapport à
                         ki                                 l’horizontale
                                       ≤ 15°        15° < ≤ 45°      45° < ≤ 75°      > 75°
                         Est             1              0,96             0,83          0,59
                       Sud-Est           1              1,03             0,94          0,71
                         Sud             1              1,07             0,97          0,73
                      Sud-Ouest          1              1,03             0,94          0,71
                        Ouest            1              0,96             0,83          0,59

Dans le cas d’un appartement dans un immeuble équipé d’une installation collective de PV, la surface de capteurs à
associer à l’appartement est proratisée par rapport à la surface habitable de l’immeuble.

De façon forfaitaire, une part de la production de photovoltaïque est considérée autoconsommée. Cette production
d’électricité autoconsommée est déduite de la consommation d’énergie finale électrique utilisée pour le calcul des
étiquettes énergie et gaz à effet de serre.

La part d’énergie photovoltaïque autoconsommée annuellement est déterminée de la façon suivante :

                                            𝐶𝑒𝑙𝑒𝑐_𝑎𝑐 = 𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡 ∗ 𝑇𝑎𝑝

Avec :

    -    Celec_ac : électricité photovoltaïque autoconsommée (kWhef/(m².an))

    -    Celec_tot : consommation totale annuelle d’électricité pour les 5 usages réglementaires et les usages
         mobiliers (kWhef/(m².an)) (voir ci-dessous)

    -    Tap : taux d’autoproduction, correspondant au rapport entre la production d’électricité autoconsommée et la
         consommation d’énergie (tous usages) du bâtiment (%) :

                                                             1
                                                 𝑇𝑎𝑝 =
                                                          1     1
                                                         𝑇𝑐𝑣 + 𝑇𝑎𝑝𝑙

                                                            𝑃𝑝𝑣
                                                  𝑇𝑐𝑣 =
                                                          𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡

            Tcv : taux de couverture, correspondant au ratio entre la production totale du site et la consommation
             annuelle tous usages (%)

            Ppv : production totale d’électricité photovoltaïque (kWhef/(m².an))

            Tapl : coefficient de calage représentant le taux d’auto-production maximum pouvant être atteint lorsque
             la production d’électricité renouvelable augmente :

                                                        ∑𝑖 𝑇𝑎𝑝𝑙𝑝𝑖 ∗ 𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡_𝑖
                                               𝑇𝑎𝑝𝑙 =
                                                              𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡

             Avec :

                                             𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡 = ∑ 𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡_𝑖
                                                           𝑖

             □   Chauffage :


    Celec_tot_ch : consommation annuelle d’électricité pour le chauffage, y compris les auxiliaires de
    génération (kWhef/(m².an)) :

                                    𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡_𝑐ℎ = 𝐶𝑒𝑙𝑒𝑐_𝑐ℎ + 𝐶𝑎𝑢𝑥_𝑔𝑒𝑛_𝑐ℎ

       Celec_ch : consommation annuelle d’électricité pour le chauffage, hors la consommation des
        auxiliaires de génération (kWhef/(m².an))

       Caux_gen_ch : consommation annuelle d’électricité pour les auxiliaires de génération de
        l’installation de chauffage (kWhef/(m².an))

□   Refroidissement :

    Celec_tot_ref : consommation annuelle d’électricité pour le refroidissement (kWhef/(m².an))

□   ECS :

    Celec_tot_ecs : consommation annuelle d’électricité pour l’ECS (kWhef/(m².an))

       Caux_gen_ecs : consommation annuelle d’électricité pour les auxiliaires de génération de
        l’installation d’ECS (kWhef/(m².an))

       Celec_ecs : consommation annuelle d’électricité pour l’ECS, hors la consommation des auxiliaires
        de génération (kWhef/(m².an))

□   Eclairage :

    Celec_tot_ecl : Consommation annuelle d’électricité pour l’éclairage (kWhef/(m².an))

□   Auxiliaires de ventilation :

    Celec_tot_aux_vent : consommation annuelle d’électricité pour les auxiliaires de ventilation
    (kWhef/(m².an))

□   Auxiliaires de distribution :

    Celec_tot_aux_dist : consommation annuelle d’électricité pour les auxiliaires de distribution
    (kWhef/(m².an)) :

                   𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡_𝑎𝑢𝑥_𝑑𝑖𝑠𝑡 = 𝐶𝑎𝑢𝑥_𝑑𝑖𝑠𝑡_𝑐ℎ + 𝐶𝑎𝑢𝑥_𝑑𝑖𝑠𝑡_𝑓𝑟 + 𝐶𝑎𝑢𝑥_𝑑𝑖𝑠𝑡_𝑒𝑐𝑠

       Caux_dist_ch : consommation annuelle d’électricité pour les auxiliaires de distribution de
        l’installation de chauffage (kWhef/(m².an))

       Caux_dist_fr : consommation annuelle d’électricité pour les auxiliaires de distribution de
        l’installation de refroidissement (kWhef/(m².an))

       Caux_dist_ecs : consommation annuelle d’électricité pour les auxiliaires de distribution de
        l’installation d’ECS (kWhef/(m².an))

□   Autres usages :

    Celec_tot_au : consommation annuelle d’électricité pour les autres usages (kWhef/(m².an)) :

                               𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡_𝑎𝑢 = 𝐶𝑐𝑜𝑚_𝑒𝑐𝑙 + 𝐶𝑢𝑚

       Ccom_ecl : consommation annuelle d’éclairage des parties communes en logement collectif
        (kWhef/(m².an)) :

            o     En maison individuelle : Ccom_ecl = 0
                         o   En immeuble collectif : Ccom_ecl = 1,1 kWhef/(m².an)

                    Cum : consommation annuelle d’électricité des usages mobiliers (kWhef/(m².an))

                                                                        Cum
                                                                    (kWhef/(m².an))
                                         Maison individuelle             29
                                         Immeuble collectif              27

            □    Taplp_i : valeur d’autoproduction partielle pour l'usage de l'électricité i :

                                       Usage de l’électricité i           Taplp_i
                                               Chauffage                   0,02
                                          Refroidissement                  0,25
                                                  ECS                      0,05
                                               Eclairage                   0,05
                                      Auxiliaires de ventilation           0,50
                                      Auxiliaires de distribution          0,10
                                            Autres usages                  0,45

                Si un usage n’est pas électrique : Taplp_i = 0

                L’électricité « autoconsommée » Celec_ac est répartie conventionnellement par usage de l'électricité
                i au prorata des valeurs Taplp_i et Celec_tot_i :

                                                                         𝑇𝑎𝑝𝑙𝑝_𝑖 ∗ 𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡_𝑖
                                             𝐶𝑒𝑙𝑒𝑐_𝑎𝑐_𝑖 = 𝐶𝑒𝑙𝑒𝑐_𝑎𝑐 ∗
                                                                           𝑇𝑎𝑝𝑙 ∗ 𝐶𝑒𝑙𝑒𝑐_𝑡𝑜𝑡

                Celec_ac_i : électricité autoconsommée pour l’usage de l’électricité i (kWhef/(m².an))
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_coef_orientation_pv_id`

### Balises XML produites par cette section
- `production_pv`
- `conso_elec_ac`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
