---
section_id: "14.1"
title: "Générateurs ECS à combustion"
spec_pages: [93-95]
xml_outputs: ["rendement_generation"]
tables: []
depends_on: ["14"]
status: "verbatim"
---

# §14.1 — Générateurs ECS à combustion

> Source : `resources/spec.pdf` p.93-95
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
14.1 Générateurs à combustion
La scénarisation conventionnelle de la production d’eau chaude sanitaire suppose une absence de consommation
pendant 1 semaine au mois de décembre.

Il est donc considéré dans la suite de façon conventionnelle :

    -    Nombre annuel d’heures de fonctionnement de l’ECS = 1790h
    -    Nombre d’heures de vacances = 168h
    -    Durée de fonctionnement de l’ECS ramenée à la période de vacances = 105h

Les générateurs de production d’ECS ne sont pas maintenus en température.



14.1.1 Production d’ECS seule instantanée par chauffe-eau gaz
Le rendement conventionnel annuel moyen de génération d’ECS a pour expression :

                                                                 1
                                    𝑅𝑔 =
                                            1            𝑄𝑃0             𝑃𝑣𝑒𝑖𝑙
                                           𝑅𝑝𝑛 + (1790 ∗ 𝐵𝑒𝑐𝑠) + (6970 ∗ 𝐵𝑒𝑐𝑠 )

Avec :

    -    Becs : énergie annuelle à fournir par le générateur pour l’ECS en Wh
    -    Pveil : puissance de la veilleuse (W)
    -    QP0 : pertes à l’arrêt du générateur (W)
    -    R pn : rendement à pleine charge du générateur


Pour un chauffe-eau gaz, les valeurs de Pveil, QP0 , R pn sont données dans le tableau suivant :

                                    Pn ≤ 10 kW                       Pn > 10 kW
                                                 QP0                            QP0            Puissance
                            Rendement                       Rendement                       veilleuse en W
                                                en %                           en %
            Ancienneté         (PCI)                           (PCI)
                                              puissance                      puissance       (si veilleuse)
                              RPn (%)                         RPn (%)
                                             nominale Pn                    nominale Pn
            Avant 1981         70.0 %           4.0 %         70.0 %           4.0 %               150
             1981-1989         75.0 %            2.0 %        75.0 %            2.0 %              120
             1990-2000         81.0 %            1.2 %        82.0 %            1.2 %              120
             2001-2015         82.0 %            1.0 %        84.0 %            1.0 %              100
            Après 2015         82.0 %            1.0 %        84.0 %            0.6 %

Les valeurs des bases de données professionnelles peuvent aussi être utilisées pour les équipements récents ou
recommandés.

Pour les caractéristiques des autres générateurs, voir le paragraphe sur le rendement des générateurs à combustion.



14.1.2 Production mixte par chaudière gaz, fioul, bois
                                                                 1
                            𝑅𝑔 ∗ 𝑅𝑠 =
                                         1     1790 ∗ 𝑄𝑃0 + 𝑄𝑔,𝑤            0,5. 𝑃𝑣𝑒𝑖𝑙
                                        𝑅𝑝𝑛 + (      𝐵𝑒𝑐𝑠        ) + (6970 ∗ 𝐵𝑒𝑐𝑠 )

Avec :

    -    QP0 : pertes à l’arrêt de la chaudière (W)

    -    Becs : énergie annuelle à fournir par le générateur pour l’ECS en Wh

    -    R pn : rendement à 100% de charge

    -    Q g,w : pertes de stockage (Wh)



14.1.3 Accumulateur gaz
                                                                 1
                               𝑅𝑔 ∗ 𝑅𝑠 =
                                            1    (8592 ∗ 𝑄𝑃0 + 𝑄𝑔,𝑤 )           𝑃𝑣𝑒𝑖𝑙
                                           𝑅𝑝𝑛 +        𝐵𝑒𝑐𝑠          + (6970 ∗ 𝐵𝑒𝑐𝑠 )

Avec :

    -    R pn : rendement à 100% de charge

    -    Becs : besoin annuel à fournir par le générateur pour l’ECS (Wh)

    -    Q g,w : pertes de stockage (Wh)

    -    Pveil : puissance de la veilleuse (W)

    -     QP0 : pertes à l’arrêt de la chaudière (W) :
                                                                       𝑃𝑛
                                                      QP0 = 1,5 ∗
                                                                       100
Les caractéristiques par défaut peuvent être retrouvées dans le tableau suivant :

                                                               RPn (rendement PCI à        Pveil (Puissance de la
                 Ancienneté                 Type
                                                                100% de charge) %               veilleuse) W
                 Avant 1990                                            81 %                          200
                 1990-2000                Classique                    84 %                          150
                 Après 2000                                            84 %                          150
                 1996-2000
                                      A condensation                    98 %                        NA
                 Après 2000
```

## TODO digitalisation

### Balises XML produites par cette section
- `rendement_generation`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
