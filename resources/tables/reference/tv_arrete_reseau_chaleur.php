<?php

declare(strict_types=1);

/**
 * Plage d'application de chaque arrêté « réseaux de chaleur et de froid ».
 *
 * Les contenus CO2 des réseaux sont republiés chaque année par un arrêté
 * modifiant l'annexe 7 de l'arrêté du 15 septembre 2006. Le millésime
 * applicable à un DPE est celui dont la plage contient sa date — **il ne se
 * déduit pas de l'année du DPE**. Les arrêtés ne paraissent pas à date fixe :
 * celui du millésime 2022 est du 16 mars 2023, celui du millésime 2021 du
 * 21 octobre 2021, et un « année − 1 » ferait appliquer à un DPE d'avril 2022
 * un arrêté publié onze mois après lui. C'est la faute que TASK-K02 avait
 * corrigée pour les tarifs des énergies.
 *
 * `date_arrete` est la valeur que les exports portent en
 * `donnee_entree/date_arrete_reseau_chaleur` ; le XSD y renvoie explicitement
 * à ce fichier amont : « les informations sur les arrêtés de réseaux de
 * chaleur sont disponibles dans observatoire_dpe.modele_donnee.
 * arrete_reseau_chaleur.json ». Quand l'export la renseigne, elle désigne
 * donc le millésime directement, sans passer par les plages.
 *
 * `valid_to` est exclusif : les plages publiées se touchent bout à bout
 * (2023-04-23 pour le millésime 2021, 2023-03-16 pour le 2022 — l'amont les
 * fait d'ailleurs se chevaucher d'un mois, et c'est l'arrêté le plus récent
 * qui s'applique, d'où l'ordre décroissant de lecture).
 *
 * @spec-source editeur/public/schemas/arrete_reseau_chaleur.json
 * @generated-on 2026-10-18
 * @tables reference/tv_reseau_chaleur
 */
return [
    ['millesime' => 2025, 'date_arrete' => '2026-04-25', 'valid_from' => '2026-05-10', 'valid_to' => null],
    ['millesime' => 2024, 'date_arrete' => '2025-04-11', 'valid_from' => '2025-04-11', 'valid_to' => '2026-09-01'],
    ['millesime' => 2023, 'date_arrete' => '2024-07-05', 'valid_from' => '2024-07-05', 'valid_to' => '2025-07-01'],
    ['millesime' => 2022, 'date_arrete' => '2023-03-16', 'valid_from' => '2023-03-16', 'valid_to' => '2024-09-20'],
    ['millesime' => 2021, 'date_arrete' => '2021-10-21', 'valid_from' => '2022-01-18', 'valid_to' => '2023-04-23'],
    ['millesime' => 2020, 'date_arrete' => '2020-10-12', 'valid_from' => '2020-10-17', 'valid_to' => '2022-01-18'],
];
