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
    private const FORMAT_NATIF_ADEME = '0.1.0';

    /**
     * Exports historiques qui sérialisent les apports et pertes récupérés en
     * Wh. Seul le format LICIEL 2 les publie en kWh.
     *
     * @var list<string> préfixes de l'attribut `version` de `<dpe>`
     */
    private const FORMATS_APPORTS_EN_WATTHEURES = ['6.', '7.', '8.', '9.'];

    /** Les formats historiques 6.x/7.x publient les intensités avec leurs décimales. */
    public static function usesDecimalIntensities(DOMDocument $document): bool
    {
        $version = $document->documentElement?->getAttribute('version') ?? '';
        return str_starts_with($version, '6.') || str_starts_with($version, '7.');
    }

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
     * Tous les formats sauf le format LICIEL 2 conservent ces seules
     * grandeurs intermédiaires en Wh, tout en sérialisant les besoins et
     * consommations en kWh.
     *
     * Le corpus le montre sans exception : les 35 fichiers 0.1.0, le 6.4.1,
     * les 7 fichiers 8.x et les 14 fichiers 9.x publient tous un
     * `apport_interne_ch` supérieur à cinquante fois leur `besoin_ch`, quand
     * 440 des 444 fichiers de format 2 le publient du même ordre de grandeur.
     * Les quatre exceptions du format 2 se contredisent sur leur seul
     * fichier et sont imputées à la référence
     * (`ReferenceDefects::apportsSerialisesEnWattheures`).
     */
    public static function apportXmlPerKwh(DOMDocument $document): float
    {
        $version = (string)($document->documentElement?->getAttribute('version') ?? '');
        if ($version === self::FORMAT_NATIF_ADEME) {
            return 1000.0;
        }

        foreach (self::FORMATS_APPORTS_EN_WATTHEURES as $prefixe) {
            if (str_starts_with($version, $prefixe)) {
                return 1000.0;
            }
        }

        return 1.0;
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
