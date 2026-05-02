---
section_id: "3.4"
title: "Calcul des déperditions par les ponts thermiques"
spec_pages: [32-34]
xml_outputs: ["k"]
tables: ["tv_pont_thermique_id"]
depends_on: ["3.2", "3.3"]
status: "verbatim"
---

# §3.4 — Calcul des déperditions par les ponts thermiques

> Source : `resources/spec.pdf` p.32-34
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
3.4 Calcul des déperditions par les ponts thermiques
Données d’entrée :

Type d’isolation (ITI, ITE, ITR)

Nombre de niveaux

Nombre d’appartements

Retour d’isolation autour des menuiseries (avec ou sans)

Hauteur moyenne sous plafond

Linéaires de pont thermique

Position des menuiseries (nu extérieur, nu intérieur, tunnel)

        𝑃𝑇 = ∑ 𝑘𝑝𝑏_𝑖/𝑚_𝑗 ∗ 𝑙𝑝𝑏_𝑖/𝑚_𝑗 + ∑ 𝑘𝑝𝑖_𝑖/𝑚_𝑗 ∗ 𝑙𝑝𝑖_𝑖/𝑚_𝑗 + ∑ 𝑘𝑟𝑓/𝑚_𝑗 ∗ 𝑙𝑟𝑓/𝑚_𝑗 + ∑ 𝑘𝑝ℎ_𝑖/𝑚_𝑗 ∗ 𝑙𝑝ℎ_𝑖/𝑚_𝑗
              𝑖,𝑗                         𝑖,𝑗                         𝑗                       𝑖,𝑗

                        + ∑ 𝑘𝑚𝑒𝑛_𝑖/𝑚_𝑗 ∗ 𝑙𝑚𝑒𝑛_𝑖/𝑚_𝑗
                           𝑖,𝑗

Avec :

    -     kpb_i/m_j : valeur du pont thermique de la liaison plancher bas i mur j (W/(m.K)) (définie ci-après)

    -   kpi_i/m_j : valeur du pont thermique de la liaison plancher intermédiaire i mur j (W/(m.K)) (définie ci-après)

    -   krf/m_j : valeur du pont thermique de la liaison refend mur j (W/(m.K)) (définie ci-après)

    -   kph_i/m_j : valeur du pont thermique de la liaison plancher haut i mur j (W/(m.K)) (définie ci-après)

    -   kmen_i/m_j : valeur du pont thermique de la liaison menuiserie i mur j (W/(m.K)) (définie ci-après)

    -   lpb_i/m_j : longueur du pont thermique plancher bas i mur j (m). Il prend en compte les seuils des portes et porte-
        fenêtre

    -   lpi_i/m_j : longueur du pont thermique plancher intermédiaire i mur j (m)

    -   lph_i/m_j : longueur du pont thermique plancher haut i mur j (m)

    -   lrf /m_j : longueur du pont thermique refend mur j (m)

        En immeuble collectif d’habitation, la longueur totale forfaitaire du pont thermique refend mur est :

                                                  𝑙𝑟𝑓/𝑚_𝑗 = 2 ∗ 𝐻𝑠𝑝 ∗ (𝑁𝑏𝑙𝑔𝑡 − 𝑁𝑖𝑣)
        Avec :
            𝐻𝑠𝑝 : hauteur moyenne sous plafond

                Nblgt : nombre d’appartements

                Niv : nombre de niveaux de logements

    -   lmen_i/m_j : longueur du pont thermique menuiserie i mur j (m)

    -   ITI, ITE, ITR respectivement isolation thermique intérieure, extérieure et répartie.

Si l’état d’isolation d’une paroi est inconnu :

    -   Pour les bâtiments d’avant 1975, la paroi est considérée comme non isolée ;

    -   Pour les bâtiments construits à partir de 1975 :

            o    Les murs sont considérés comme isolés par l’intérieur ;

            o    Les plafonds sont considérés isolés par l’extérieur ;

            o    Les planchers sur terre-plein sont considérés non isolés avant 2001 et isolés par l’extérieur (en sous
                 face) à partir de 2001 ;

            o    Les autres planchers sont considérés isolés par l’extérieur.

Dans la suite, les murs en ossature bois sont traités comme des murs à isolation répartie.

Si les valeurs des ponts thermiques sont connues et justifiées, les saisir directement pour le calcul, à l’exception des
ponts thermiques négligés dans les valeurs par défaut. Sinon les valeurs par défaut proposées dans la suite peuvent
être utilisées.

Les ponts thermiques des parois au niveau des circulations communes ne sont pas pris en compte.

Aucun coefficient de réduction des températures (b) n’est appliqué aux ponts thermiques.

Seuls les ponts thermiques entre parois lourdes ou entre une paroi et une menuiserie sont conservés.

Le schéma ci-dessous permet d’identifier les différents types de ponts thermiques.

Exemple de représentations de ponts thermiques
```

## TODO digitalisation

### Tables à transcrire en pipe-markdown
- [ ] `tv_pont_thermique_id`

### Balises XML produites par cette section
- `k`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
