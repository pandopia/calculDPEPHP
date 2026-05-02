---
section_id: "10.2"
title: "Calcul du besoin mensuel de froid"
spec_pages: [68-69]
xml_outputs: ["besoin_fr"]
tables: []
depends_on: ["10.1"]
status: "verbatim"
---

# §10.2 — Calcul du besoin mensuel de froid

> Source : `resources/spec.pdf` p.68-69
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
10.2 Calcul du besoin mensuel de froid
Le besoin mensuel de refroidissement dépend du ratio de bilan thermique 𝑅𝑏𝑡ℎ_𝑗 sur le mois j :

                                                               𝐴𝑖_𝑓𝑟𝑗 + 𝐴𝑠_𝑓𝑟𝑗
                                        𝑅𝑏𝑡ℎ_𝑗 =
                                                   𝐺𝑉 ∗ (𝑇𝑒𝑥𝑡𝑚𝑜𝑦_𝑐𝑙𝑖𝑚_𝑗 − 𝑇𝑖𝑛𝑡) ∗ 𝑁𝑟𝑒𝑓𝑗

Avec :

   -     𝐴𝑖_𝑓𝑟𝑗 : Apports internes sur le mois j sur la période de refroidissement (Wh) - calculés au paragraphe 6.1

   -     𝐴𝑠_𝑓𝑟𝑗 : Apports solaires sur le mois j sur la période de refroidissement (Wh) - calculés au paragraphe 6.1

   -     GV : Transfert thermique à travers l’enveloppe et le renouvellement d’air (W/K). Le GV prend en compte les
         échanges de chaleur par le renouvellement d‘air. Ces échanges sont calculés sur la période de refroidissement
         de la même façon que pour la période de chauffage

   -     𝑇𝑖𝑛𝑡 : Température de consigne en froid (°C) égale à 28°C ou 26°C selon le comportement traité

   -     𝑇𝑒𝑥𝑡𝑚𝑜𝑦_𝑐𝑙𝑖𝑚_𝑗 : Température extérieure moyenne sur le mois j pendant les périodes de climatisation (°C)

Besoin mensuel de refroidissement Bfrj :
             1
   -     Si 2 > 𝑅𝑏𝑡ℎ alors :

                                                               𝐵𝑓𝑟𝑗 = 0

   -     Sinon :

                                     (𝐴𝑖_𝑓𝑟𝑗 + 𝐴𝑠_𝑓𝑟𝑗 )           𝐺𝑉
                            𝐵𝑓𝑟𝑗 =                      − 𝑓𝑢𝑡𝑗 ∗      ∗ (𝑇𝑖𝑛𝑡 − 𝑇𝑒𝑥𝑡𝑚𝑜𝑦𝑐𝑙𝑖𝑚 ) ∗ 𝑁𝑟𝑒𝑓𝑗
                                           1000                  1000                      𝑗


         Avec :

                Nrefj : nombre d’heures de refroidissement pour le mois j, déterminé à partir des tableaux des
                 paragraphes 18.2 et 18.3 :

                 □   Nref (28°C) pour une consigne de refroidissement à 28°C (comportement conventionnel)

                 □   Nref (26°C) pour une consigne de refroidissement à 26°C (comportement dépensier)

                𝑓𝑢𝑡𝑗 : facteur d'utilisation des apports sur le mois j
            □    Si 𝑅bthj > 0 et 𝑅bthj ≠ 1 ∶

                                                             1 − 𝑅𝑏𝑡ℎ_𝑗 −𝑎
                                                  𝑓𝑢𝑡𝑗 =
                                                            1 − 𝑅𝑏𝑡ℎ_𝑗 −𝑎−1

            □    Si 𝑅bthj = 1 ∶

                                                                  𝑎
                                                       𝑓𝑢𝑡𝑗 =
                                                                 𝑎+1

                 Avec :
                                                                    𝑡
                                                        𝑎 =1+
                                                                   15

                     𝑡 : Constante de temps de la zone pour le refroidissement
                                                                    𝐶𝑖𝑛
                                                            𝑡=
                                                                 3600 ∗ 𝐺𝑉

                      o   𝐶𝑖𝑛 : Capacité thermique intérieure efficace de la zone (J/K) :

                                               Inertie                    𝐶𝑖𝑛 (J/K)
                                               Légère                   110 000 ∗ Sh
                                             Moyenne                    165 000 ∗ Sh
                                        Lourde ou très lourde           260 000 ∗ Sh
```

## TODO digitalisation

### Balises XML produites par cette section
- `besoin_fr`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
