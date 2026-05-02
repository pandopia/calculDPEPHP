---
section_id: "1"
title: "La méthode conventionnelle"
spec_pages: [6]
xml_outputs: []
tables: []
depends_on: []
status: "verbatim"
---

# §1 — La méthode conventionnelle

> Source : `resources/spec.pdf` p.6
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
1 La méthode conventionnelle
Le DPE a pour principal objectif d’informer sur la performance énergétique des bâtiments. Il affiche le bilan annuel des
consommations de chauffage, d’eau chaude sanitaire, de refroidissement, d’éclairage et des auxiliaires. Il contient
aussi une estimation des émissions de gaz à effet de serre associée aux consommations des 5 usages précédents.

Ces informations communiquées par le DPE doivent permettre de comparer objectivement les différents bâtiments
entre eux. Prenons le cas d’une maison individuelle occupée par une famille de 3 personnes, la consommation de cette
même maison ne sera pas la même si elle est occupée par une famille de 5 personnes. De plus, selon que l’hiver aura
été rigoureux ou non, que la famille se chauffe à 19°C, ou 21°C, les consommations du même bâtiment peuvent
significativement fluctuer. Il est dès lors nécessaire dans l’établissement de ce diagnostic de s’affranchir du
comportement des occupants afin d’avoir une information sur la qualité énergétique du bâtiment. C’est la raison pour
laquelle l’établissement du DPE se fait principalement par une méthode de calcul des consommations
conventionnelles. Elle s’appuie sur une utilisation standardisée du bâtiment pour des conditions climatiques moyennes
du lieu.

Les principaux critères caractérisant la méthode conventionnelle sont les suivants :

        En présence d’un système de chauffage dans le bâtiment autre que les équipements mobiles et les cheminées
         à foyer ouvert, toute la surface habitable du logement est considérée chauffée en permanence pendant la
         période de chauffe ;
        Les besoins de chauffage sont calculés mensuellement à partir de degrés heures base 19 pour des météos
         représentatives du climat des 8 zones climatiques de la France métropolitaine. Les degrés heures sont égaux
         à la somme, pour toutes les heures de la saison de chauffage pendant laquelle la température extérieure est
         inférieure à 19°C. Ils prennent en compte une inoccupation de 7 jours en décembre (dernière semaine)
         pendant la période de chauffe ainsi qu’un réduit des températures à 16°C pendant la journée en semaine ;
        Le besoin d’ECS est forfaitisé selon la surface habitable du bâtiment et la zone climatique. Dans le calcul du
         besoin d’ECS une semaine d’absence est comptée au mois de décembre ;
        Les besoins de refroidissements sont calculés mensuellement sur les périodes où la température extérieure
         est supérieure à 28°C.

Ces caractéristiques du calcul conventionnel peuvent être responsables de différences importantes entre les
consommations réelles facturées et celles calculées avec la méthode conventionnelle. En effet, tout écart entre les
hypothèses du calcul conventionnel et le scénario réel d’utilisation du bâtiment entraîne des différences au niveau des
consommations. De plus, certaines caractéristiques impactant les consommations du bâtiment ne sont connues que
de façon limitée (par exemple : les rendements des chaudières qui dépendent de leur dimensionnement et de leur
entretien, la qualité de mise en œuvre du bâtiment, le renouvellement d’air dû à la ventilation, etc.).
```

## TODO digitalisation

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
