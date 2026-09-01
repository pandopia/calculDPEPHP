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

    public static function resolve(
        DOMElement $generator,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
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
