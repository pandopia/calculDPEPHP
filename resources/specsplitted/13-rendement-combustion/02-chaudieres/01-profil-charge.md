---
section_id: "13.2.1"
title: "Profil de charge des générateurs"
spec_pages: [79-86]
xml_outputs: ["rpint", "rpn", "temp_fonc_100", "temp_fonc_30"]
tables: []
depends_on: ["13.2"]
status: "verbatim"
---

# §13.2.1 — Profil de charge des générateurs

> Source : `resources/spec.pdf` p.79-86
> Extraction verbatim via `pdftotext -layout`. À digitaliser : tables en pipe-markdown, formules en LaTeX.

## Texte verbatim

```
    13.2.1 Profil de charge des générateurs
    Le profil de charge conventionnel donne pour chaque intervalle de taux de charge le coefficient de pondération
    correspondant.



    13.2.1.1 Profil de charge conventionnel

    Pour les bâtiments d’habitation, un profil de charge long est considéré (correspond au type d’horaire d’occupation
    longue).

    Le tableau suivant donne le coefficient de pondération pour un profil de charge correspondant à une occupation
    longue (ex. : logement).

                             De 0%     De 10%    De 20%     De 30%    De 40%     De 50%    De 60%     De 70%      De 80%   De 90%
  Taux de charge TchX
                             à 10%      à 20%     à 30%      à 40%     à 50%      à 60%     à 70%      à 80%       à 90%   à 100%
    Coefficient de
                                0,1      0,25      0,2       0,15       0,1        0,1       0,05         0,025   0,025      0
pondération coeff_pondX

    Ce profil de charge est donné sur une période de chauffe et non mensuellement. Le calcul du rendement de génération
    se fera donc sur toute la saison de chauffe et non mensuellement.

    Pour les calculs les taux de charge sont pris en milieu de classe (5% ; 15% ; 25% ; … ; 85% ; 95%),

    Le coefficient de pondération Coeff_pondx est associé au taux de charge Tchx qui correspond à l’intervalle
    [Tchx − 5%; Tchx + 5%[




13.2.1.2 Présence d’un ou plusieurs générateurs à combustion indépendants

Nous considérerons la présence dans la zone au maximum de N générateurs à combustion indépendants.

Les taux de charge doivent être pondérés par un coefficient Cdimref qui permet de prendre en compte les charges
partielles.

      -   Pour un seul générateur à combustion de puissance installée Pngen :

                                                            1000 ∗ 𝑃𝑛𝑔𝑒𝑛
                                           𝐶𝑑𝑖𝑚𝑟𝑒𝑓 =
                                                        𝐺𝑉 ∗ (𝑇𝑐𝑜𝑛𝑠 − 𝑇𝑏𝑎𝑠𝑒)

      -   Pour N générateurs à combustion :
                                              1000 ∗ (𝑃𝑛𝑔𝑒𝑛1 + 𝑃𝑛𝑔𝑒𝑛2 + ⋯ + 𝑃𝑛𝑔𝑒𝑛𝑁 )
                                 𝐶𝑑𝑖𝑚𝑟𝑒𝑓 =
                                                       𝐺𝑉 ∗ (𝑇𝑐𝑜𝑛𝑠 − 𝑇𝑏𝑎𝑠𝑒)

Avec :

      -   Pngen_i : puissance installée du générateur à combustion i (kW)

      -   GV : déperditions totales du bâtiment (W/K)

      -   Tbase : température extérieure de base (°C)

      -   Tcons : température de consigne (19°C en comportement conventionnel et 21°C en comportement
          dépensier)

Les profils de charge conventionnels sont modifiés pour prendre en compte les charges partielles Cdimref, le
coefficient Coeff_pondx_dim est alors affecté au taux de charge Tchx_dim,on aura :

                                           𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑𝑥_𝑑𝑖𝑚 = 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑𝑥

                                                                 𝑇𝑐ℎ
                                                                  𝑥
                                              𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 = 𝑀𝑖𝑛 (𝐶𝑑𝑖𝑚𝑟𝑒𝑓 ; 1)

      Tchx
Si           > 1, alors sous-dimensionnement de l’installation
     Cdimref

Sauf pour le taux de charge Tch95 (correspondant à une charge entre 90% et 100%), on notera :

                                                   𝑇𝑐ℎ95_𝑑𝑖𝑚 = 𝑇𝑐ℎ95

En présence d’un ou de N générateurs indépendants :

      -   le taux de charge final x de chaque générateur est : Tchx_final = Tchx_dim

      -   Le coefficient de pondération final est : Coeff_pondx_final = Coeff_pondx_dim



13.2.1.3 Cascade de deux générateurs à combustion

Ne seront traités que les configurations de cascade à deux générateurs. En présence d’une cascade avec plus de deux
générateurs, seuls les deux premiers de la cascade seront pris en compte. Aux deux générateurs seront affectés la
puissance totale de l’installation. La répartition des puissances des générateurs non retenus sur les 2 générateurs
modélisés dans la cascade se fera de façon à maintenir le même ratio de puissance entre les deux.

      -   Une donnée d’entrée est la puissance relative du générateur i : Prel(gen_i)

    -   Pn(gen_i) : puissance nominale du générateur i (W)



Dans notre cas avec 2 générateurs :

                                                               𝑃𝑛(𝑔𝑒𝑛_1)
                                         𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_1) =
                                                          𝑃𝑛(𝑔𝑒𝑛_1) + 𝑃𝑛(𝑔𝑒𝑛_2)

                                                               𝑃𝑛(𝑔𝑒𝑛_2)
                                         𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_2) =
                                                          𝑃𝑛(𝑔𝑒𝑛_1) + 𝑃𝑛(𝑔𝑒𝑛_2)

On détermine pour chaque point de fonctionnement x et pour chaque générateur i sa contribution CTchx_dim (gen_i)
au taux de charge du système Tchx_dim.



13.2.1.3.1 Cascade avec priorité

Dans notre cas avec 2 générateurs en cascade, le générateur 1 sera le plus performant ou à défaut le plus puissant, Il
sera considéré comme prioritaire si aucune information complémentaire n’est disponible :

La contribution CTchx_dim de chaque générateur au taux de charge Tchx_dim est :

                                 𝐶𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 (𝑔𝑒𝑛_1) = 𝑚𝑖𝑛(𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_1) ; 𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 )

                     𝐶𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 (𝑔𝑒𝑛_2) = 𝑚𝑖𝑛(𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_2) ; 𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 − 𝐶𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 (𝑔𝑒𝑛_1))

Avec le taux de charge final suivant :

                                                                  𝐶𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 (𝑔𝑒𝑛_1)
                                 𝑇𝑐ℎ𝑥_𝑓𝑖𝑛𝑎𝑙 (𝑔𝑒𝑛_1) = 𝑚𝑖𝑛 (1 ;                     )
                                                                    𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_1)

                                                                  𝐶𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 (𝑔𝑒𝑛_2)
                                 𝑇𝑐ℎ𝑥_𝑓𝑖𝑛𝑎𝑙 (𝑔𝑒𝑛_2) = 𝑚𝑖𝑛 (1 ;                     )
                                                                    𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_2)

                                   𝐶𝑜𝑒𝑓𝑓𝑝𝑜𝑛𝑑          (𝑔𝑒𝑛_1) = 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑𝑥 (𝑔𝑒𝑛_1)
                                               𝑥𝑑𝑖𝑚


                                   𝐶𝑜𝑒𝑓𝑓𝑝𝑜𝑛𝑑 𝑥        (𝑔𝑒𝑛_2) = 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑𝑥 (𝑔𝑒𝑛_2)
                                                𝑑𝑖𝑚




13.2.1.3.2 Cascade sans priorité (même contribution au taux de charge)

Dans ce cas les générateurs contribuent de manière au taux de charge proportionnellement à leur puissance, on
écrira :

                                    𝐶𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 (𝑔𝑒𝑛_1) = 𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_1) ∗ 𝑇𝑐ℎ𝑥_𝑑𝑖𝑚

                                    𝐶𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 (𝑔𝑒𝑛_2) = 𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_2) ∗ 𝑇𝑐ℎ𝑥_𝑑𝑖𝑚

Avec le taux de charge final suivant :

                                                                  𝐶𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 (𝑔𝑒𝑛_1)
                                 𝑇𝑐ℎ𝑥_𝑓𝑖𝑛𝑎𝑙 (𝑔𝑒𝑛_1) = 𝑚𝑖𝑛 (1 ;                     )
                                                                    𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_1)

                                                                            𝐶𝑇𝑐ℎ𝑥_𝑑𝑖𝑚 (𝑔𝑒𝑛_2)
                                       𝑇𝑐ℎ𝑥_𝑓𝑖𝑛𝑎𝑙 (𝑔𝑒𝑛_2) = 𝑚𝑖𝑛 (1 ;                         )
                                                                              𝑃𝑟𝑒𝑙(𝑔𝑒𝑛_2)

                                        𝐶𝑜𝑒𝑓𝑓𝑝𝑜𝑛𝑑 𝑥          (𝑔𝑒𝑛_1) = 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑𝑥 (𝑔𝑒𝑛_1)
                                                       𝑑𝑖𝑚


                                        𝐶𝑜𝑒𝑓𝑓𝑝𝑜𝑛𝑑 𝑥          (𝑔𝑒𝑛_2) = 𝐶𝑜𝑒𝑓𝑓_𝑝𝑜𝑛𝑑𝑥 (𝑔𝑒𝑛_2)
                                                       𝑑𝑖𝑚


Le coefficient de pondération final est :

                                                               CTchx_dim (gen_1)
                                                                                 . Coeff_pondx_dim (gen_1)
                                                                   Tchx_dim
        Coeff_pondx_final (gen_1) =
                                    CTch5_dim (gen_1)                                   CTch95_dim (gen_1)
                                                      . Coeff_pond5_dim (gen_1) + ⋯ +                      . Coeff_pond95_dim (gen_1)
                                        Tch5_dim                                            Tch95_dim



                                                               CTchx_dim (gen_2)
                                                                                 . Coeff_pondx_dim (gen_2)
                                                                   Tchx_dim
        Coeff_pondx_final (gen_2) =
                                    CTch5_dim (gen_2)                                   CTch95_dim (gen_2)
                                                      . Coeff_pond5_dim (gen_2) + ⋯ +                      . Coeff_pond95_dim (gen_2)
                                        Tch5_dim                                            Tch95_dim




13.2.1.4 Pertes au point de fonctionnement
    -     QPx (kW) : pertes au point de fonctionnement x (taux de charge x = Tchx_final )

    -     QP0 : pertes à l’arrêt (kW)

    -     R Pn et R Pint : respectivement les rendements à pleine charge et à charge intermédiaire

    -     Pn : puissance nominale du générateur (kW)

Dans les paragraphes suivants, les rendements à pleine charge Rpn et à charge intermédiaire Rpint sont donnés dans
les tableaux en PCI. Cependant, les calculs des rendements de génération sont effectués en PCS (pour éviter d’avoir
des rendements>100%). Dans les équations pour le calcul du rendement de génération, ils sont donc convertis en PCS
(en les divisant par k 𝑃𝐶𝑆/𝑃𝐶𝐼 ). Le DPE exprimant les consommations en kWh PCI, les rendements de génération calculés
en PCS sont ensuite convertis en PCI pour leur calcul.

De même, les pertes à l’arrêt QP0 et les puissances des veilleuses Pveil sont données pour du PCI. Pour les avoir pour
du PCS avant de les utiliser dans les calculs, elles doivent être multipliées par le coefficient de conversion kPCS/PCI.

Selon les énergies, le coefficient de conversion en PCI/PCS est donné dans le tableau suivant :

                                                 Coefficient de conversion k 𝑃𝐶𝑆/𝑃𝐶𝐼
                                            Electricité                              1
                                           Gaz naturel                             1,11
                                               GPL                                 1,09
                                               Fioul                               1,07
                                               Bois                                1,08
                                                RCU                                  1
                                             Charbon                               1,04
13.2.1.5 Chaudières basse température et condensation :

Pour les chaudières basse température et condensation, le point de fonctionnement w correspond à un
fonctionnement à 15% de charge.

Entre 0 et 15% de charge :

                                               [𝑄𝑃15 − 0,15. 𝑄𝑃0 ] ∗ 𝑥
                                       𝑄𝑃𝑥 =                           + 0,15 ∗ 𝑄𝑃0
                                                       0,15



Entre 15 et 30% de charge :

                                       [𝑄𝑃30 − 𝑄𝑃15 ] ∗ 𝑥          [𝑄𝑃30 − 𝑄𝑃15 ] ∗ 0,15
                               𝑄𝑃𝑥 =                      + 𝑄𝑃15 −
                                             0,15                         0,15

Entre 30 et 100% de charge :

                                       [𝑄𝑃100 − 𝑄𝑃30 ] ∗ 𝑥          [𝑄𝑃100 − 𝑄𝑃30 ] ∗ 0,3
                               𝑄𝑃𝑥 =                       + 𝑄𝑃30 −
                                              0,7                           0,7

                                                                𝑄𝑃30
                                                       𝑄𝑃15 =
                                                                 2

   -   Pour les chaudières basse températures :

          S’il y a une régulation :

                                                  100 − (𝑅𝑃𝑖𝑛𝑡 + 0,1 ∗ (40 − 𝑇𝑓𝑜𝑛𝑐_30 ))
                              𝑄𝑃30 = 0,3 ∗ 𝑃𝑛 ∗
                                                       𝑅𝑃𝑖𝑛𝑡 + 0,1 ∗ (40 − 𝑇𝑓𝑜𝑛𝑐_30 )

          En l’absence de régulation :

                                                  100 − (𝑅𝑃𝑖𝑛𝑡 + 0,1 ∗ (40 − 𝑇𝑓𝑜𝑛𝑐_100 ))
                              𝑄𝑃30 = 0,3 ∗ 𝑃𝑛 ∗
                                                       𝑅𝑃𝑖𝑛𝑡 + 0,1 ∗ (40 − 𝑇𝑓𝑜𝑛𝑐_100 )

                                                100 − (𝑅𝑃𝑛 + 0,1 ∗ (70 − 𝑇𝑓𝑜𝑛𝑐_100 ))
                                𝑄𝑃100 = 𝑃𝑛 ∗
                                                     𝑅𝑃𝑛 + 0,1 ∗ (70 − 𝑇𝑓𝑜𝑛𝑐_100 )

   -   Pour les chaudières à condensation :

          S’il y a une régulation :

                                                  100 − (𝑅𝑃𝑖𝑛𝑡 + 0,2 ∗ (33 − 𝑇𝑓𝑜𝑛𝑐_30 ))
                              𝑄𝑃30 = 0,3 ∗ 𝑃𝑛 ∗
                                                       𝑅𝑃𝑖𝑛𝑡 + 0,2 ∗ (33 − 𝑇𝑓𝑜𝑛𝑐_30 )

          En l’absence de régulation :

                                                  100 − (𝑅𝑃𝑖𝑛𝑡 + 0,2 ∗ (33 − 𝑇𝑓𝑜𝑛𝑐_100 ))
                              𝑄𝑃30 = 0,3 ∗ 𝑃𝑛 ∗
                                                       𝑅𝑃𝑖𝑛𝑡 + 0,2 ∗ (33 − 𝑇𝑓𝑜𝑛𝑐_100 )


                                              100 − (𝑅𝑃𝑛 + 0,1 ∗ (70 − 𝑇𝑓𝑜𝑛𝑐_100 ))
                               𝑄𝑃100 = 𝑃𝑛 ∗
                                                  𝑅𝑃𝑛 + 0,1 ∗ (70 − 𝑇𝑓𝑜𝑛𝑐_100 )

Tfonc_100 (°C) est la température de fonctionnement de la chaudière à 100% de charge. Elle est donnée dans le tableau
suivant en fonction des types d’émetteur et des différentes périodes de leur installation :

                                                                           Période
           Température de distribution / Type                            Entre 1981 et
                                                      Avant 1981                             Après 2000
                        d’émetteur                                           2000
            Basse / Plancher ou plafond basse
                                                           60                 35                 35
                       température
          Moyenne / Radiateur à chaleur douce              80                 70                 60
                Haute / Autres émetteurs                   80                 70                 70

Tfonc_30 (°C) est la température de fonctionnement de la chaudière à 30% de charge. Elle est donnée dans les tableaux
suivants selon le type d’installation.




Pour les chaudières à condensation :

                                                                     Période (émetteurs)
           Température de distribution / Type                            Entre 1981 et
                                                      Avant 1981                             Après 2000
                        d’émetteur                                           2000
            Basse / Plancher ou plafond basse
                                                           32                24,5               24,5
                       température
          Moyenne / Radiateur à chaleur douce              38                 35                 32
                Haute / Autres émetteurs                   38                 35                 35

Pour les chaudières basse température :

                                                                     Période (émetteurs)
           Température de distribution / Type                            Entre 1981 et
                                                      Avant 1981                             Après 2000
                        d’émetteur                                           2000
            Basse / Plancher ou plafond basse
                                                          42,5                35                 35
                       température
          Moyenne / Radiateur à chaleur douce             48,5               45,5               42,5
                Haute / Autres émetteurs                  48,5               45,5               45,5

Si un système de génération alimente des réseaux de distribution de températures différentes, la température de
fonctionnement est prise égale à la température maximale.

Pour les installations récentes ou recommandées, les caractéristiques réelles des chaudières présentées sur les bases
de données professionnelles peuvent être utilisées.

Si l’année d’installation des émetteurs est inconnue, prendre l’année de construction du bâtiment.




13.2.1.6 Chaudières standard

Pour les chaudières standards, le point de fonctionnement w correspond à un fonctionnement à 30% de charge,

Entre 0 et 30% de charge :

                                               [𝑄𝑃30 − 0,15 ∗ 𝑄𝑃0 ] ∗ 𝑥
                                       𝑄𝑃𝑥 =                            + 0,15 ∗ 𝑄𝑃0
                                                         0,3

Entre 30 et 100% de charge :

                                       [𝑄𝑃100 − 𝑄𝑃30 ] ∗ 𝑥          [𝑄𝑃100 − 𝑄𝑃30 ] ∗ 0,3
                               𝑄𝑃𝑥 =                       + 𝑄𝑃30 −
                                              0,7                           0,7

    -    S’il y a une régulation

                                                   100 − (𝑅𝑃𝑖𝑛𝑡 + 0,1 ∗ (50 − 𝑇𝑓𝑜𝑛𝑐_30 ))
                               𝑄𝑃30 = 0,3 ∗ 𝑃𝑛 ∗
                                                        𝑅𝑃𝑖𝑛𝑡 + 0,1 ∗ (50 − 𝑇𝑓𝑜𝑛𝑐_30 )

    -    En l’absence de régulation

                                                   100 − (𝑅𝑃𝑖𝑛𝑡 + 0,1 ∗ (50 − 𝑇𝑓𝑜𝑛𝑐_100 ))
                              𝑄𝑃30 = 0,3 ∗ 𝑃𝑛 ∗
                                                       𝑅𝑃𝑖𝑛𝑡 + 0,1 ∗ (50 − 𝑇𝑓𝑜𝑛𝑐_100 )

                                                  100 − (𝑅𝑃𝑛 + 0,1 ∗ (70 − 𝑇𝑓𝑜𝑛𝑐_100 ))
                                   𝑄𝑃100 = 𝑃𝑛 ∗
                                                      𝑅𝑃𝑛 + 0,1 ∗ (70 − 𝑇𝑓𝑜𝑛𝑐_100 )

Avec :

   -     Tfonc_100 (°C) : température de fonctionnement de la chaudière à 100% de charge. Elle est donnée dans le
         paragraphe précédent pour les chaudières basse température et condensation

   -     Tfonc_30 (°C) : température de fonctionnement de la chaudière à 30% de charge. Elle est donnée selon le
         type d’installation dans les tableaux suivants

Pour une chaudière standard, jusqu’en 1990 :

                                                                      Période (émetteurs)
             Température de distribution / Type                           Entre 1981 et
                                                        Avant 1981                           Après 2000
                          d’émetteur                                          2000
              Basse / Plancher ou plafond basse
                                                             53                 50              50
                         température
               Moyenne / Radiateur à chaleur
                                                             59                 56              53
                             douce
                  Haute / Autres émetteurs                   59                 56              56

Pour une chaudière standard, depuis 1991 :

                                                                       Période (émetteurs)
            Température de distribution / Type                             Entre 1981 et
                                                         Avant 1981                          Après 2000
                         d’émetteur                                            2000
             Basse / Plancher ou plafond basse
                                                             49,5               45               45
                        température
           Moyenne / Radiateur à chaleur douce               55,5              52,5             49,5

                   Haute / Autres émetteurs                 55,5                  52,5                  52,5

  Si un système de génération alimente des réseaux de distribution de températures différentes, la température de
  fonctionnement est prise égale à la température maximale.

  Pour les installations récentes ou recommandées, les caractéristiques réelles des chaudières présentées sur les bases
  de données professionnelles peuvent être utilisées.

  Si l’année d’installation des émetteurs est inconnue, prendre l’année de construction du bâtiment.
```

## TODO digitalisation

### Balises XML produites par cette section
- `rpint`
- `rpn`
- `temp_fonc_100`
- `temp_fonc_30`

### Reformatages requis
- [ ] Reformater les formules en LaTeX (`$$ … $$` ou `$`)
- [ ] Vérifier les indices et exposants (texte verbatim peut perdre la mise en forme)
- [ ] Référencer les annexes citées (§18.x)
