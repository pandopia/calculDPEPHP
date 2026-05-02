---
section_id: "15.1"
title: "Consommation des auxiliaires de génération"
spec_pages: [97-98]
xml_outputs: ["conso_auxiliaire_generation_ch", "conso_auxiliaire_generation_ecs"]
tables: []
depends_on: ["15"]
status: "verbatim"
---

# §15.1 — Consommation des auxiliaires de génération

> Source : `resources/spec.pdf` p.97-98
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
15.1 Consommation des auxiliaires de génération
Les consommations des auxiliaires des générateurs de chauffage et d’ECS sont calculées annuellement.

Détermination des puissances par défaut des auxiliaires :

                                              𝑃𝑎𝑢𝑥_𝑔 = 𝐺 + 𝐻 ∗ 𝑃𝑛        (W)

Dans cette équation :

         -   pour les chaudières gaz ou fioul : si Pn > 400 kW alors Pn = 400 kW

         -   pour les générateurs d’air chaud : si Pn > 300 kW alors Pn = 300 kW

         -   pour les chaudières bois : si Pn > 70 kW alors Pn = 70 kW

Avec pour G et H les valeurs tabulées suivantes selon le type d’équipements :

                                                                         G (W)       H (W/kW)
                            Chaudière au gaz ou au fioul                  20            1,6
                           Chaudière bois atmosphérique                    0             0
                        Chaudière bois assistée par ventilateur          73,3           10,5
                              Générateurs d’air chaud                      0             4
                                   Radiateurs gaz                         40             0
                                  Chauffe-eau gaz                          0             0
                                 Accumulateur gaz                          0             0

Les consommations des auxiliaires de génération sont nulles dans les cas suivants (Qaux_g = 0) :

    -    Pour les installations avec une production de chaleur (chauffage et/ou ECS) par PAC, les consommations des
         auxiliaires de génération sont prises en compte dans le SCOP (COP). Elles seront donc ignorées.

    -    Pour les installations avec une production de chaleur (chauffage et/ou ECS) par un réseau de chaleur urbain,
         les consommations des auxiliaires de génération sont prises conventionnellement nulles.



15.1.1 Consommation des auxiliaires de génération de chauffage
La consommation annuelle des auxiliaires de génération Qaux_g_ch (Wh) est :

                                                          𝑃𝑎𝑢𝑥_𝑔_𝑐ℎ ∗ 𝐵𝑐ℎ_𝑔
                                            𝑄𝑎𝑢𝑥_𝑔_𝑐ℎ =
                                                                𝑃𝑛_𝑐ℎ

Avec :

    -    𝑃𝑛_𝑐ℎ : puissance nominale du générateur de l’installation de chauffage (W)

    -    𝑃𝑎𝑢𝑥_𝑔_𝑐ℎ : puissance des auxiliaires de génération de l’installation de chauffage (W)

    -    𝐵𝑐ℎ_𝑔 : besoin annuel d’énergie assuré par le générateur pour le chauffage (Wh)

Par exemple dans les cas où le générateur n’assure pas 100% du besoin, seule la part du besoin qu’il couvre est prise
en compte.




15.1.2 Consommation des auxiliaires de génération d’ECS
La consommation annuelle Qaux_g_ecs (Wh) des auxiliaires de génération est :

                                                           𝑃𝑎𝑢𝑥_𝑔_𝑒𝑐𝑠 ∗ 𝐵𝑒𝑐𝑠_𝑔
                                            𝑄𝑎𝑢𝑥_𝑔_𝑒𝑐𝑠 =
                                                                  𝑃𝑛_𝑒𝑐𝑠

Avec :

    -    𝑃𝑛_𝑒𝑐𝑠 : puissance nominale du générateur de l’installation d’ECS (W)

    -    𝑃𝑎𝑢𝑥_𝑔_𝑒𝑐𝑠 : puissance des auxiliaires de génération de l’installation d’ECS (W)

    -    𝐵𝑒𝑐𝑠_𝑔 : besoin d’énergie annuel assuré par le générateur pour la production d’ECS (Wh)

Par exemple dans les cas où le générateur n’assure pas 100% du besoin, seule la part du besoin qu’il couvre est prise
en compte.
```

## TODO digitalisation

### Balises XML produites par cette section
- `conso_auxiliaire_generation_ch`
- `conso_auxiliaire_generation_ecs`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
