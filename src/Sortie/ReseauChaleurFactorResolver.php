<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Résout le contenu CO2 ACV d'un réseau de chaleur ou de froid.
 *
 * Le millésime applicable est celui dont la **plage d'application** contient
 * la date du DPE, lue dans `reference/tv_arrete_reseau_chaleur` : les arrêtés
 * ne paraissent pas à date fixe, et « année − 1 » faisait appliquer à un DPE
 * d'avril 2022 un arrêté du 16 mars 2023, publié après lui.
 *
 * Deux index se succèdent, la coupure étant au 18 janvier 2022 — le XSD dit de
 * `tv_reseau_chaleur_id` qu'il est « OBSOLETE et remplacé par
 * identifiant_reseau_chaleur à partir du 18 janvier 2022 ». Le millésime 2020
 * est donc lu sur le numéro de ligne, les suivants sur l'identifiant national.
 *
 * Lorsqu'un identifiant n'existe pas dans le millésime demandé, la recherche
 * remonte les millésimes de même index ; un identifiant absent de tous utilise
 * la valeur officielle « autres réseaux ».
 *
 * @tables reference/tv_reseau_chaleur, reference/tv_arrete_reseau_chaleur
 */
final class ReseauChaleurFactorResolver
{
    private const TABLE_CONTENUS = 'reference/tv_reseau_chaleur';
    private const TABLE_ARRETES = 'reference/tv_arrete_reseau_chaleur';

    /**
     * Millésime antérieur à l'identifiant national : son annexe est indexée
     * par `tv_reseau_chaleur_id`, et elle publie un contenu CO2 **direct**,
     * pas un contenu ACV.
     */
    private const MILLESIME_PAR_IDENTIFIANT_OBSOLETE = 2020;

    public const AUTRE_RESEAU_CHALEUR = 0.385;
    public const AUTRE_RESEAU_FROID = 0.120;

    /**
     * Générateurs « … multi bâtiment modélisée comme un réseau de chaleur » ⇒
     * facteur d'émission du combustible réellement brûlé (kgCO2e/kWh).
     *
     * Ces générateurs ne sont pas des réseaux urbains : ce sont les chaudières
     * d'un bâtiment voisin, que le schéma fait seulement transiter par le
     * vecteur « réseau de chaleur » (`enum_type_energie_id = 8`). Le mode de
     * livraison ne change pas le combustible, donc pas non plus son contenu
     * carbone — c'est le facteur du gaz, du fioul, du bois ou du charbon qui
     * s'applique, et non le forfait « autre réseau ».
     *
     * Chauffage 109-111 et 171, ECS 74-76 et 134. Les variantes « pompe(s) à
     * chaleur » (112 et 77) fonctionnent à l'électricité : leur facteur dépend
     * de l'usage et reste traité par l'appelant.
     *
     * @var array<int, float>
     */
    private const COMBUSTIBLE_CH = [109 => 0.030, 110 => 0.324, 111 => 0.227, 171 => 0.385];
    private const COMBUSTIBLE_ECS = [74 => 0.030, 75 => 0.324, 76 => 0.227, 134 => 0.385];

    /**
     * Facteur du combustible d'un générateur multi-bâtiment, ou null si le
     * générateur est un vrai réseau de chaleur (ou une PAC).
     */
    public static function combustibleMultiBatiment(DOMElement $generator, NodeAccessor $accessor): ?float
    {
        $ch = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $generator);
        if ($ch !== null) {
            return self::COMBUSTIBLE_CH[$ch] ?? null;
        }

        $ecs = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $generator);

        return $ecs === null ? null : (self::COMBUSTIBLE_ECS[$ecs] ?? null);
    }

    /**
     * Millésime applicable, lu sur les plages publiées.
     *
     * `date_arrete_reseau_chaleur` désigne le millésime sans ambiguïté quand
     * l'export la renseigne — le XSD la définit comme « la date de l'arrêté
     * qui modifie l'annexe 7 ». À défaut, la date d'établissement du DPE est
     * cherchée dans les plages, prises de la plus récente à la plus ancienne
     * parce que l'amont les laisse se chevaucher d'un mois entre 2021 et 2022.
     *
     * Un DPE antérieur au premier arrêté retombe sur le millésime le plus
     * ancien : la méthode 3CL ne s'applique que depuis le 1er juillet 2021,
     * à l'intérieur de la plage de 2020.
     */
    private static function millesime(?string $dateArrete, ?string $dateDpe, CalculationContext $context): int
    {
        /** @var list<array<string, mixed>> $arretes */
        $arretes = $context->tables->load(self::TABLE_ARRETES);

        if ($dateArrete !== null && $dateArrete !== '') {
            $jour = substr($dateArrete, 0, 10);
            foreach ($arretes as $arrete) {
                if ($arrete['date_arrete'] === $jour) {
                    return (int) $arrete['millesime'];
                }
            }
        }

        $jour = $dateDpe === null ? null : substr($dateDpe, 0, 10);
        if ($jour !== null && $jour !== '') {
            foreach ($arretes as $arrete) {
                $fin = $arrete['valid_to'];
                if ($jour >= (string) $arrete['valid_from'] && ($fin === null || $jour < (string) $fin)) {
                    return (int) $arrete['millesime'];
                }
            }
        }

        return (int) $arretes[array_key_last($arretes)]['millesime'];
    }

    public static function resolve(
        DOMElement $generator,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        $combustible = self::combustibleMultiBatiment($generator, $accessor);
        if ($combustible !== null) {
            return $combustible;
        }

        $millesime = self::millesime(
            $accessor->getStringOrNull('./donnee_entree/date_arrete_reseau_chaleur', $generator),
            $accessor->getStringOrNull('//date_etablissement_dpe', $generator),
            $context,
        );

        $reseauId = $accessor->getStringOrNull('./donnee_entree/identifiant_reseau_chaleur', $generator);
        if ($millesime === self::MILLESIME_PAR_IDENTIFIANT_OBSOLETE) {
            // Avant le 18 janvier 2022, l'annexe est indexée par le numéro de
            // ligne de sa table et non par l'identifiant national, que le XSD
            // n'introduit qu'à cette date.
            $ligne = $accessor->getIntOrNull('./donnee_entree/tv_reseau_chaleur_id', $generator);
            $reseauId = $ligne === null ? null : (string) $ligne;
        }

        if ($reseauId === null || $reseauId === '') {
            return self::AUTRE_RESEAU_CHALEUR;
        }

        $table = $context->tables->load(self::TABLE_CONTENUS);
        $cle = $millesime === self::MILLESIME_PAR_IDENTIFIANT_OBSOLETE ? (int) $reseauId : $reseauId;
        if (isset($table[$millesime][$cle])) {
            return (float) $table[$millesime][$cle];
        }

        // Réseau absent de son millésime : les millésimes voisins indexés de la
        // même façon sont parcourus du plus proche au plus ancien, un réseau
        // n'étant retiré de l'annexe que lorsqu'il cesse d'être exploité.
        if ($millesime !== self::MILLESIME_PAR_IDENTIFIANT_OBSOLETE) {
            for ($annee = $millesime; $annee > self::MILLESIME_PAR_IDENTIFIANT_OBSOLETE; $annee--) {
                if (isset($table[$annee][$reseauId])) {
                    return (float) $table[$annee][$reseauId];
                }
            }
        }

        return str_ends_with($reseauId, 'F')
            ? self::AUTRE_RESEAU_FROID
            : self::AUTRE_RESEAU_CHALEUR;
    }

    /**
     * Annexe 7 de l'arrêté du 15/09/2006, colonne « Taux EnR&R » : fraction
     * renouvelable/récupération publiée, distincte du contenu carbone.
     *
     * Ce taux ne qualifie PAS le réseau d'« efficace » au sens de L.711-4.
     * Il prépare le calcul de l'annexe 2 §1.2.6 (JO 13/08/2026, p.7), sans
     * autoriser à lui seul la mention A0 ni le pictogramme correspondant.
     *
     * `null` : taux absent, anomalie source hors [0,1], identifiant absent ou
     * chaudière/PAC multi-bâtiment. Le vrai zéro publié reste zéro. Un réseau
     * de chaleur absent de l'édition prend la ligne « autres réseaux » (0),
     * pas le taux d'une ancienne édition ; pour le froid cette ligne ne
     * publie pas de taux. Aucune déduction depuis le contenu CO2.
     *
     * @tables reference/tv_reseau_chaleur_enr, reference/tv_arrete_reseau_chaleur
     */
    public static function resolveRenewableShare(
        DOMElement $network,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): ?float {
        $typeCh = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $network);
        $typeEcs = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $network);
        if (in_array($typeCh, [109, 110, 111, 112, 171], true)
            || in_array($typeEcs, [74, 75, 76, 77, 134], true)) {
            return null;
        }

        $millesime = self::millesime(
            $accessor->getStringOrNull('./donnee_entree/date_arrete_reseau_chaleur', $network),
            $accessor->getStringOrNull('//date_etablissement_dpe', $network),
            $context,
        );
        // La feuille 2020 ne publie aucun taux et utilise l'ancien index.
        if ($millesime === self::MILLESIME_PAR_IDENTIFIANT_OBSOLETE) {
            return null;
        }
        $id = $accessor->getStringOrNull('./donnee_entree/identifiant_reseau_chaleur', $network);
        if ($id === null || !preg_match('/^[0-9]{4}[CF]$/D', $id)) {
            return null;
        }

        $table = $context->tables->load('reference/tv_reseau_chaleur_enr');
        if (!array_key_exists($id, $table[$millesime])) {
            return str_ends_with($id, 'C') ? 0.0 : null;
        }
        $rate = $table[$millesime][$id];

        return $rate !== null && is_finite((float)$rate) && $rate >= 0.0 && $rate <= 1.0
            ? (float)$rate
            : null;
    }
}
