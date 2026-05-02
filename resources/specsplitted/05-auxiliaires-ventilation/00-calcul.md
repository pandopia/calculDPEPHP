---
section_id: "5"
title: "Calcul des consommations d'auxiliaires de ventilation"
spec_pages: [41-42]
xml_outputs: ["pvent_moy", "conso_auxiliaire_ventilation"]
tables: []
depends_on: ["4"]
status: "verbatim"
---

# §5 — Calcul des consommations d'auxiliaires de ventilation

> Source : `resources/spec.pdf` p.41-42
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
5 Calcul des consommations d’auxiliaires de ventilation
Données d’entrée :

Type de VMC

Type de bâtiment

Surface habitable

La consommation annuelle d’auxiliaires de ventilation (kWhef/an) est donnée par la formule :

                                                                 𝑃𝑣𝑒𝑛𝑡𝑚𝑜𝑦
                                                 𝐶𝑎𝑢𝑥 = 8760 ∗
                                                                   1000

Avec :

    -    Pventmoy : puissance moyenne des auxiliaires (W)

            Pventmoy en maison individuelle :

                                     Pventmoy             jusqu’à 2012       Après 2012
                                 Simple Flux Auto           65 W-ThC          35 W-ThC
                                Simple Flux hygro           50 W-ThC          15 W-ThC
                                   Double Flux              80 W-ThC          35 W-ThC

             Les puissances d’auxiliaires tabulées ci-dessus pour les VMC double flux intègrent les puissances du
             soufflage et de l’extraction.
            Pventmoy en immeuble collectif :

                                        𝑃𝑣𝑒𝑛𝑡𝑚𝑜𝑦 = 𝑃𝑣𝑒𝑛𝑡 ∗ 𝑄𝑣𝑎𝑟𝑒𝑝𝑐𝑜𝑛𝑣 ∗ 𝑆ℎ

             □   Qvarepconv : débit d'air extrait conventionnel par unité de surface habitable (m³/(h.m²)) (voir chapitre
                 4)

             □   Sh : surface habitable (m2)

             □   Pvent : puissance des auxiliaires (W/(m³/h)) :

                           Pvent                         Jusqu’à 2012                 Après 2012
                 Simple Flux Auto réglable            0,46 W-ThC/(m³/h)           0,25 W-ThC/(m³/h)
                 Simple Flux hygro réglable           0,46 W-ThC/(m³/h)           0,25 W-ThC/(m³/h)
                 Double Flux Auto réglable            1,1 W-ThC/(m³/h)            0,6 W-ThC/(m³/h)
         Les puissances d’auxiliaires des VMC basse pression sont les mêmes que pour les VMC classiques.

           Les puissances d’auxiliaires tabulées ci-dessus pour les VMC double flux intègrent les puissances du soufflage
           et de l’extraction.
              Ventilation Hybride :

           On considère que le système bascule d'un mode mécanique à un mode naturel et inversement. Les
           consommations d’auxiliaire ont lieu pendant le mode de fonctionnement mécanique.
           Par défaut la durée de fonctionnement de l’extracteur mécanique est prise pour le mode grand débit :

                                                     Durée d’utilisation en grand débit
                                                              (en h/semaine)
                                        Collectif                    28
                                       individuel                    14

           Les consommations d’auxiliaires pour une VMC hybride correspondent aux consommations d’une VMC
           classique autoréglable de 2001 à 2012 multipliées par le ratio du temps d’utilisation :

                                                      Ratio du temps d’utilisation du
                                                             mode mécanique
                                        Collectif                 0,167
                                       individuel                 0,083
```

## TODO digitalisation

### Balises XML produites par cette section
- `pvent_moy`
- `conso_auxiliaire_ventilation`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
