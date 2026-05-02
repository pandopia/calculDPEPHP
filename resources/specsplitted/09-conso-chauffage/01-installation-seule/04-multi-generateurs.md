---
section_id: "9.1.4"
title: "Installation avec plusieurs générateurs pour une même émission"
spec_pages: [61-62]
xml_outputs: []
tables: []
depends_on: ["9.1.1"]
status: "verbatim"
---

# §9.1.4 — Installation avec plusieurs générateurs pour une même émission

> Source : `resources/spec.pdf` p.61-62
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
9.1.4 Installation avec plusieurs générateurs pour une même émission
Notons qu’en présence de plusieurs émissions, les consommations assurées par chaque générateur dans ce
paragraphe doivent être proratisées selon les règles du paragraphe précédent.



9.1.4.1 Installation de chauffage avec une chaudière ou une PAC en relève d’une chaudière bois

Cette installation correspond à une chaudière bois assurant principalement le chauffage sauf par temps doux ou en
mi-saison où une PAC ou chaudière prend le relais de la chaudière bois.

La consommation annuelle de chauffage Cch1 liée à la chaudière bois (kWh PCI) est donnée par la formule :

                                         𝐶𝑐ℎ1 = 0,75 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇1 ∗ 𝐼𝑐ℎ1

La consommation annuelle de chauffage Cch2 liée à la PAC ou chaudière (kWh PCI) est donnée par la formule :

                                         𝐶𝑐ℎ2 = 0,25 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇2 ∗ 𝐼𝑐ℎ2

Dans cette configuration, les générateurs sont multiples et couplés, les émetteurs sont de base et peuvent aussi être
multiples.



9.1.4.2 Installation de chauffage avec chaudière en relève de PAC

Cette installation correspond à une PAC assurant principalement le chauffage sauf par temps de grand froid où la PAC
s’arrête pour laisser le relais à la chaudière.

La consommation annuelle de chauffage Cch1 liée à la PAC (kWh PCI) est donnée par la formule :

                                         𝐶𝑐ℎ1 = 0,8 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇1 ∗ 𝐼𝑐ℎ1

La consommation annuelle de chauffage liée à la chaudière (kWh PCI) est donnée par la formule :

                                         𝐶𝑐ℎ2 = 0,2 ∗ 𝐵𝑐ℎ ∗ 𝐼𝑁𝑇2 ∗ 𝐼𝑐ℎ2

Dans cette configuration, les générateurs sont multiples et couplés, les émetteurs sont de base et peuvent aussi être
multiples.



9.1.4.3 Les pompes à chaleur hybrides

Une pompe à chaleur (PAC) hybride est l’association d’une chaudière à condensation (gaz ou fioul) et d’une PAC
air/eau ou eau/eau. Le système de régulation permet selon les conditions climatiques de produire la chaleur avec le
générateur le plus performant. La modélisation choisie pour la PAC hybride correspond à une gestion des besoins de
chauffage du bâtiment selon la répartition suivante entre les deux systèmes :

                             % du besoin de chauffage assuré par chaque équipement
                                        Zone                     PAC       Chaudière
                                         H1                       80          20
                                         H2                       83          17
                                         H3                       88          12


La fourniture d’ECS est considérée assurée à 100% par la chaudière.

Dans cette configuration, tous les émetteurs associés au générateur sont de base.
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
