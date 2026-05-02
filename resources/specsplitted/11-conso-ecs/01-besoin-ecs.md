---
section_id: "11.1"
title: "Calcul du besoin d'ECS"
spec_pages: [70-72]
xml_outputs: ["besoin_ecs", "besoin_ecs_depensier", "v40_ecs_journalier", "nadeq"]
tables: []
depends_on: ["11"]
status: "verbatim"
---

# §11.1 — Calcul du besoin d'ECS

> Source : `resources/spec.pdf` p.70-72
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
11.1 Calcul du besoin d’ECS
Les besoins journaliers moyens par personne (adulte équivalent) sur une année sont en moyenne de 56 ± 23 litres à
40°C. Le scénario d’utilisation conventionnel du DPE s’appuie sur un comportement conventionnel, qui correspond à
une consommation de 56 l/j.pers d’eau chaude à 40°C, contre 79 l/j.pers pour un comportement dépensier. Cela
correspond environ à une variation du besoin de + 40% entre le profil conventionnel de consommation et le profil
dépensier.

On considère conventionnellement que le logement est inoccupé 7 jours par an (du 24 au 30 décembre inclus).

Pour les logements individuels et les logements collectifs, le nombre d’adultes équivalent est déterminé selon le
coefficient d’occupation maximal (Nmax) de la façon suivante :

Logements individuels :

    -   On définit la surface habitable moyenne d’un logement (m²) comme suit :

                                                               𝑆ℎ
                                                    Sh𝑚𝑜𝑦 =
                                                              𝑁𝑏𝑙𝑔𝑡

        Avec :

           Sh : surface habitable totale de la maison individuelle (m²)

           Nblgt : nombre de logements (=1 pour le traitement d’une maison individuelle contenant un seul logement)

    -   Calcul du coefficient d’occupation maximal Nmax :

           Si Shmoy < 30m² :

                                                           𝑁𝑚𝑎𝑥 = 1

           Si 30m² ≤ Shmoy < 70m² :

                                     𝑁𝑚𝑎𝑥 = 1,75 − 0,01875 ∗ (70 − 𝑆ℎ𝑚𝑜𝑦 )

           Si Shmoy ≥ 70m² :

                                              𝑁𝑚𝑎𝑥 = 0,025 ∗ 𝑆ℎ𝑚𝑜𝑦

    -   Calcul du nombre d’adultes équivalent Nadeq :

           Si Nmax < 1,75

                                               𝑁𝑎𝑑𝑒𝑞 = 𝑁𝑏𝑙𝑔𝑡 ∗ 𝑁𝑚𝑎𝑥

           Si Nmax ≥ 1,75

                                𝑁𝑎𝑑𝑒𝑞 = 𝑁𝑏𝑙𝑔𝑡 ∗ (1,75 + 0,3 ∗ (𝑁𝑚𝑎𝑥 − 1,75))

        Avec :
        Nblgt : nombre de logements

Logements collectifs :

    -   On définit la surface habitable moyenne d’un logement (m²) comme suit :

                                                                𝑆ℎ
                                                  Sh𝑚𝑜𝑦 =
                                                               𝑁𝑏𝑙𝑔𝑡

        Avec :

           Sh : surface habitable totale de l’immeuble (m²)

           Nblgt : nombre de logements (=1 pour le traitement d’un appartement)

    -   Cette surface moyenne permet de déterminer le Nmax pour un logement moyen :

           Si Shmoy < 10m² :

                                                         𝑁𝑚𝑎𝑥 = 1

           Si 10m² ≤ Shmoy < 50m² :

                                    𝑁𝑚𝑎𝑥 = 1,75 − 0,01875 ∗ (50 − 𝑆ℎ𝑚𝑜𝑦 )

           Si Shmoy ≥ 50m² :

                                              𝑁𝑚𝑎𝑥 = 0,035 ∗ 𝑆ℎ𝑚𝑜𝑦

    -   Calcul du nombre d’adultes équivalent Nadeq :

           Si Nmax < 1,75 :

                                               𝑁𝑎𝑑𝑒𝑞 = 𝑁𝑏𝑙𝑔𝑡 ∗ 𝑁𝑚𝑎𝑥

           Si Nmax ≥ 1,75 :

                                𝑁𝑎𝑑𝑒𝑞 = 𝑁𝑏𝑙𝑔𝑡 ∗ (1,75 + 0,3 ∗ (𝑁𝑚𝑎𝑥 − 1,75))

La quantité de chaleur Becsj (Wh) nécessaire sur le mois j pour préparer l’eau chaude sanitaire est obtenue selon la
formule suivante :

    -   Pour un comportement conventionnel


                                  𝐵𝑒𝑐𝑠𝑗 = 1,163 ∗ 𝑁𝑎𝑑𝑒𝑞 ∗ 56 ∗ (40 − 𝑇𝑒𝑓𝑠𝑗 ) ∗ 𝑛𝑗𝑗

    -    Pour un comportement dépensier

                                   𝐵𝑒𝑐𝑠𝑗 = 1,163 ∗ 𝑁𝑎𝑑𝑒𝑞 ∗ 79 ∗ (40 − 𝑇𝑒𝑓𝑠𝑗 ) ∗ 𝑛𝑗𝑗

Avec :

    -    Tefs_j : température moyenne d’eau froide sanitaire sur le mois j (°C). La température d’eau froide est une
         donnée climatique mensuelle pour chacune des 8 zones climatiques (voir parties 18.2 et 18.3)

    -    njj : Nombre de jours d’occupation sur le mois j :



                                                   Mois              njj
                                                 Janvier             31
                                                 Février             28
                                                  Mars               31
                                                   Avril             30
                                                   Mai               31
                                                   Juin              30
                                                  Juillet            31
                                                   Août              31
                                               Septembre             30
                                                Octobre              31
                                               Novembre              30
                                               Décembre*             24

*Dans l’approche conventionnelle une absence d’une semaine est comptée en décembre.

Le besoin annuel d’eau chaude sanitaire Becs est la somme des besoins mensuels d’ECS (Wh) :

                                                   𝐵𝑒𝑐𝑠 = ∑ 𝐵𝑒𝑐𝑠𝑗
                                                              𝑗
```

## TODO digitalisation

### Balises XML produites par cette section
- `besoin_ecs`
- `besoin_ecs_depensier`
- `v40_ecs_journalier`
- `nadeq`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
