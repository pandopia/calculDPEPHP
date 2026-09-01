<?php

/**
 * Prix des énergies servant au calcul des frais annuels d'énergie du DPE.
 *
 * Barèmes successifs, sélectionnés sur `administratif/date_etablissement_dpe` :
 * l'arrêté du 31 mars 2021 impose que le DPE porte la date de la version de
 * l'arrêté utilisée à côté de l'estimation des frais annuels d'énergie.
 *
 * Deux barèmes existent à ce jour :
 *
 *  1. Annexe 7 de l'arrêté du 31 mars 2021, dans la rédaction issue de
 *     l'arrêté du 8 octobre 2021 — applicable aux DPE établis jusqu'au
 *     30 juin 2024.
 *  2. Annexe 2 de l'arrêté du 25 mars 2024 « modifiant les seuils des
 *     étiquettes du diagnostic de performance énergétique pour les logements
 *     de petites surfaces et actualisant les tarifs annuels de l'énergie »
 *     (JORF, NOR LOGL2408442A) — applicable aux DPE établis à compter du
 *     1er juillet 2024.
 *
 * L'électricité et le gaz naturel sont tarifés par tranches de consommation
 * annuelle, sous la forme `coût = terme_fixe + prix_kwh × Cef`, le terme fixe
 * représentant la part d'abonnement lissée. Les autres énergies ont un prix du
 * kWh unique.
 *
 * `kwh` est indexé par `enum_type_energie_id` (XSD ADEME) :
 *   3 fioul · 4 bois bûches · 5 bois granulés · 6 bois plaquettes forestières
 *   · 7 bois plaquettes d'industrie · 8 réseau de chauffage urbain
 *   · 9 propane · 10 butane · 11 charbon · 13 GPL.
 * Les identifiants 1 et 12 (électricité, électricité renouvelable) et 2 (gaz
 * naturel) relèvent des tranches, pas de `kwh`.
 *
 * @spec-section Annexe 7 — prix des énergies
 * @spec-source  https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000044202205 (arrêté du 8 octobre 2021)
 * @spec-source  https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000049446315 (arrêté du 25 mars 2024)
 * @generated-on 2026-09-01
 */
return [
    [
        'valid_from' => '2021-07-01',
        'valid_to'   => '2024-06-30',
        'source'     => "Arrêté du 31 mars 2021, annexe 7, dans la rédaction de l'arrêté du 8 octobre 2021",

        'kwh' => [
            3  => 0.09142, // fioul domestique
            4  => 0.03201, // bois – bûches
            5  => 0.05991, // bois – granulés (pellets) ou briquettes
            6  => 0.03201, // bois – plaquettes forestières
            7  => 0.03201, // bois – plaquettes d'industrie
            8  => 0.07870, // réseau de chauffage urbain
            9  => 0.14305, // propane
            10 => 0.20027, // butane
            11 => 0.02372, // charbon
            13 => 0.14305, // GPL — aligné sur le propane, la spec ne le distingue pas
        ],

        // [borne haute exclue de la tranche, terme fixe €, prix du kWh €]
        'electricite' => [
            [1000.0,  0.0,   0.29007],
            [2500.0,  149.0, 0.14066],
            [5000.0,  122.0, 0.15176],
            [15000.0, 94.0,  0.15735],
            [INF,     56.0,  0.15989],
        ],
        'gaz_naturel' => [
            [5009.0,  0.0,   0.11121],
            [50055.0, 230.0, 0.06533],
            [INF,     415.0, 0.06164],
        ],
    ],

    [
        'valid_from' => '2024-07-01',
        'valid_to'   => null,
        'source'     => 'Arrêté du 25 mars 2024, annexe 2 (tarifs annuels de l\'énergie)',

        'kwh' => [
            3  => 0.14821, // fioul domestique
            4  => 0.04200, // bois – bûches
            5  => 0.09897, // bois – granulés (pellets) ou briquettes
            6  => 0.04200, // bois – plaquettes forestières
            7  => 0.04200, // bois – plaquettes d'industrie
            8  => 0.08921, // chauffage urbain
            9  => 0.15672, // propane
            10 => 0.23429, // butane
            11 => 0.02787, // charbon
            13 => 0.15672, // GPL — aligné sur le propane
        ],

        'electricite' => [
            [1000.0,  0.0,   0.34721], // « 0 + (0,5 T1e + 0,5 T2e) Cef »
            [2500.0,  158.0, 0.18954],
            [5000.0,  158.0, 0.18949],
            [15000.0, 119.0, 0.19726],
            [INF,     78.0,  0.20001],
        ],
        'gaz_naturel' => [
            [5000.0,  0.0,   0.13120], // « 0 + (0,5 T1g + 0,5 T2g) Cef »
            [50000.0, 182.0, 0.09488],
            [INF,     288.0, 0.09274],
        ],
    ],
];
