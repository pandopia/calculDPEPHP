<?php

declare(strict_types=1);

namespace CalculDpePHP\Common;

use DOMDocument;

/**
 * Convention d'unité des énergies intermédiaires selon le format XML source.
 *
 * Le format ADEME natif 0.1.0 sérialise Becs et les apports/pertes récupérés
 * en Wh, conformément aux formules §9.1 et §11.1. Les exports historiques
 * LICIEL (`dpe version="2"`) les sérialisent en kWh.
 *
 * @spec-section 9.1, 11.1
 * @spec-pages 53-54, 70-72
 * @spec-source resources/specsplitted/09-conso-chauffage/01-installation-seule/01-conso.md
 */
final class IntermediateEnergyUnit
{
    public static function xmlPerKwh(DOMDocument $document): float
    {
        return self::isNativeAdeme($document)
            ? 1000.0
            : 1.0;
    }

    public static function isNativeAdeme(DOMDocument $document): bool
    {
        return $document->documentElement?->getAttribute('version') === '0.1.0';
    }

    /**
     * Facteur de sérialisation des apports et pertes récupérés.
     *
     * Les exports DPEWIN 9.x conservent ces seules grandeurs intermédiaires
     * en Wh, tout en sérialisant les besoins et consommations en kWh.
     */
    public static function apportXmlPerKwh(DOMDocument $document): float
    {
        $version = $document->documentElement?->getAttribute('version');
        return $version === '0.1.0' || str_starts_with((string)$version, '9.')
            ? 1000.0
            : 1.0;
    }

    /** Les formats ADEME/DPEWIN ordonnent les sorties par ID énergie croissant. */
    public static function usesAscendingEnergyOrder(DOMDocument $document): bool
    {
        $version = $document->documentElement?->getAttribute('version');
        return $version === '0.1.0' || str_starts_with((string)$version, '9.');
    }

    /** Formats qui publient réellement les agrégats du scénario dépensier. */
    public static function usesDepensierOutputs(DOMDocument $document): bool
    {
        return self::usesAscendingEnergyOrder($document);
    }

    /** DPEWIN 9.x reconstruit le total GES depuis l'intensité entière. */
    public static function usesRoundedGesTotal(DOMDocument $document): bool
    {
        $version = $document->documentElement?->getAttribute('version');
        return str_starts_with((string)$version, '9.');
    }
}
