---
section_id: "6.1"
title: "Calcul de F"
spec_pages: [42-44]
xml_outputs: ["fraction_apport_gratuit_ch", "fraction_apport_gratuit_depensier_ch"]
tables: []
depends_on: ["6"]
status: "verbatim"
---

# §6.1 — Calcul de F

> Source : `resources/spec.pdf` p.42-44
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
6.1 Calcul de F
Données d’entrée :

Département

Altitude

Fj est la fraction des besoins de chauffage du mois j couverts par les apports gratuits, elle s’exprime en fonction de
l’inertie du bâtiment :

                                                Inertie                         Fj
                                                                                      3,6
                                                                             𝑋𝑗 − 𝑋𝑗
                                       Lourde / Très Lourde                          3,6
                                                                             1 − 𝑋𝑗
                                                                                      2,9
                                                                             𝑋𝑗 − 𝑋𝑗
                                       Moyenne                                       2,9
                                                                             1 − 𝑋𝑗
                                                                                      2,5
                                                                             𝑋𝑗 − 𝑋𝑗
                                       Légère                                        2,5
                                                                             1 − 𝑋𝑗
Avec :

                                                                 𝐴𝑠𝑗 + 𝐴𝑖𝑗
                                                          𝑋𝑗 =
                                                                 𝐺𝑉 ∗ 𝐷𝐻𝑗

   -     GV : déperditions de l’enveloppe en W/K (calculées dans la partie 3)



-   DHj : degrés-heures de chauffage sur le mois j (°Ch), déterminés à partir des tableaux des paragraphes 18.2 et
    18.3 :

            □    DH19 pour une consigne de chauffage à 19°C (comportement conventionnel)

            □    DH21 pour une consigne de chauffage à 21°C (comportement dépensier)

-   𝐴𝑖𝑗 : apports internes dans le logement sur le mois j (Wh) :
    Les apports internes de chaleur dus aux équipements prennent en compte l’ensemble des équipements
    « mobiliers » (cuisson, audiovisuel, informatique, lavage, froid, appareils ménagers). Pour distinguer le
    fonctionnement permanent du fonctionnement lié à l’occupation, on considère que la puissance de chaleur
    dégagée par l’ensemble des équipements est conventionnellement de :

               5,7 W/m² en occupation hors période de sommeil

               1,1 W/m² en inoccupation et pendant le sommeil

    Le scénario conventionnel d’occupation hebdomadaire des logements est le suivant :
               De 0h à 9h et de 17h à 24h avec une période de sommeil allant de 0h et de 6h et de 22h à 24h les lundi,
                mardi, jeudi et vendredi

               De 0h à 9h et de 13h à 24h avec une période de sommeil allant de 0h à 6h et de 22h à 24h le mercredi

               De 0h à 24h les samedi et dimanche avec une période de sommeil allant de 0h à 6h et de 22h à 24h

    Soit sur une semaine :
               132h d’occupation dont 56h de sommeil

               36h d’inoccupation

    Les apports internes moyens dus aux équipements sur une semaine type sont donc de 3,18 W/m².

    À ces apports il faut ajouter :

               Ceux de l’éclairage, qui correspondent à une puissance moyenne de 1,4 W/m² en fonctionnement. Les
                apports d’éclairage sont des moyennes annuelles sur toutes les zones climatiques. Cette valeur est
                pondérée par le nombre d’heures moyen d’éclairage (voir paragraphe 16.1) sur l’année c’est-à-dire
                2123 h sur 8760 h.
                                                                                     2123
                Les apports moyens annuels d’éclairage correspondent donc à 1,4 ∗         = 0,34 W/m².
                                                                                     8760

               Ceux dus aux occupants : on considère un apport de chaleur de 90W par adulte équivalent (variable
                Nadeq déterminée au paragraphe 11.1). Le nombre d’adultes équivalent est calculé en fonction de la
                                                                                                        132
                surface habitable. Les apports de chaleur dus aux occupants sont donc à 90 ∗ Nadeq ∗ 7∗24.

    En période de chauffe :

    Les apports internes sur le mois j (en Wh) en période de chauffe sont donc :

                                                                       132
                                  𝐴𝑖𝑗 = [(3,18 + 0,34 ) ∗ 𝑆ℎ + 90 ∗        ∗ 𝑁𝑎𝑑𝑒𝑞 ] ∗ 𝑁𝑟𝑒𝑓𝑗
                                                                       168

               Sh : surface habitable du logement (m²)

               Nadeq : nombre d’adultes équivalent (voir paragraphe 11.1)

               Nrefj : nombre d’heures de chauffage pour le mois j, déterminé à partir des tableaux des paragraphes
                18.2 et 18.3 :
                        □   Nref (19°C) pour une consigne de chauffage à 19°C (comportement conventionnel)

                        □   Nref (21°C) pour une consigne de chauffage à 21°C (comportement dépensier)

                    Pour une année complète, Nref est évalué seulement sur la saison de chauffe avec :

                                                          𝑁𝑟𝑒𝑓 = ∑ 𝑁𝑟𝑒𝑓𝑗
                                                                      𝑗

   -       Asj : apports solaires sur le mois j durant la période de chauffe (Wh) :

                                                    𝐴𝑠𝑗 = 1000 ∗ 𝑆𝑠𝑒𝑗 ∗ 𝐸𝑗

           En présence d’une véranda ou autre espace solarisé non chauffé, à ces apports solaires s’ajoutent ceux à
           travers cet espace. Le calcul des apports solaires à travers un espace solarisé non chauffé est détaillé au
           paragraphe 6.3.

              Ssej : « Surface transparente sud équivalente » du logement, c'est-à-dire la surface de paroi, fictive,
               exposée au sud, totalement transparente et sans ombrage, qui provoquerait les mêmes apports solaires
               que les parois du logement, pour le mois j (m²) (voir partie 6.2)

              Ei : ensoleillement reçu, sur le mois j, par une paroi verticale orientée au sud en absence d'ombrage
               (kWh/m²)

En période de refroidissement :

De la même manière, les apports internes sur le mois j (en Wh) en période de refroidissement sont donc :

                                                                          132
                                𝐴𝑖_𝑓𝑟𝑗 = [(3,18 + 0,34 ) ∗ 𝑆ℎ + 90 ∗          ∗ 𝑁𝑎𝑑𝑒𝑞 ] ∗ 𝑁𝑟𝑒𝑓𝑗
                                                                          168

Avec :

       -   Nrefj : nombre d’heures de chauffage pour le mois j, déterminé à partir des tableaux des paragraphes 18.2 et
           18.3 :
                Nref (28°C) pour une consigne de refroidissement à 28°C (comportement conventionnel)

                   Nref (26°C) pour une consigne de refroidissement à 26°C (comportement dépensier)

           Pour une année complète, Nref est évalué seulement sur la saison de refroidissement avec :

                                                      𝑁𝑟𝑒𝑓 = ∑ 𝑁𝑟𝑒𝑓𝑗
                                                                  𝑗

Les apports solaires sur le mois j As_frj (en Wh) durant la période de refroidissement sont :

                                                 𝐴𝑠_𝑓𝑟𝑗 = 1000 ∗ 𝑆𝑠𝑒𝑗 ∗ 𝐸_𝑓𝑟𝑗

Avec :

       -   E_frj : ensoleillement reçu en période de refroidissement, sur le mois j, par une paroi verticale orientée au sud
           en absence d’ombrage (kWh/m²), déterminé à partir des tableaux des paragraphes 18.2 et 18.3 :
                   E_frj (19°C) pour une consigne de chauffage à 19°C (comportement conventionnel)
                   E_frj (21°C) pour une consigne de chauffage à 21°C (comportement dépensier)
       -   Ssej : « Surface transparente sud équivalente » du logement pour le mois j (m²)
```

## TODO digitalisation

### Balises XML produites par cette section
- `fraction_apport_gratuit_ch`
- `fraction_apport_gratuit_depensier_ch`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
