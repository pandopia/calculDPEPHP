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
        return $document->documentElement?->getAttribute('version') === '0.1.0'
            ? 1000.0
            : 1.0;
    }
}
