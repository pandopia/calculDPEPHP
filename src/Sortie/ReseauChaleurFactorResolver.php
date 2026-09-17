<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Résout le contenu CO2 ACV d'un réseau de chaleur ou de froid.
 *
 * L'arrêté applicable à un DPE référence les données de l'année précédente.
 * Lorsqu'un identifiant n'existe pas dans le millésime demandé, la recherche
 * remonte les millésimes disponibles ; un identifiant absent utilise la valeur
 * officielle « autres réseaux ».
 */
final class ReseauChaleurFactorResolver
{
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

    public static function resolve(
        DOMElement $generator,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        $combustible = self::combustibleMultiBatiment($generator, $accessor);
        if ($combustible !== null) {
            return $combustible;
        }

        $reseauId = $accessor->getStringOrNull('./donnee_entree/identifiant_reseau_chaleur', $generator);
        if ($reseauId === null || $reseauId === '') {
            return self::AUTRE_RESEAU_CHALEUR;
        }

        $dateArrete = $accessor->getStringOrNull('./donnee_entree/date_arrete_reseau_chaleur', $generator);
        $dateRef = $dateArrete ?: $accessor->getStringOrNull('//date_etablissement_dpe', $generator);
        $year = 2022;
        if ($dateRef !== null) {
            $timestamp = strtotime($dateRef);
            if ($timestamp !== false) {
                $year = max(2022, (int)date('Y', $timestamp) - 1);
            }
        }

        $table = $context->tables->load('reference/tv_reseau_chaleur');
        for ($candidateYear = $year; $candidateYear >= 2022; $candidateYear--) {
            if (isset($table[$candidateYear][$reseauId])) {
                return (float)$table[$candidateYear][$reseauId];
            }
        }

        return str_ends_with($reseauId, 'F')
            ? self::AUTRE_RESEAU_FROID
            : self::AUTRE_RESEAU_CHALEUR;
    }
}
