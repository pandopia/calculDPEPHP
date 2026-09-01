<?php

declare(strict_types=1);

namespace CalculDpePHP\Common;

use CalculDpePHP\Engine\CalculationContext;
use DOMXPath;

/**
 * Sélectionne les sollicitations de chauffage selon la construction ancienne.
 *
 * §18.2 fournit le cas courant ; §18.3 remplace E, Nref et DH lorsque le
 * bâtiment est déclaré en matériaux anciens (`batiment_materiaux_anciens=1`).
 * Cette sélection est indépendante de la classe d'inertie globale, comme dans
 * l'algorithme de référence open3cl (engine.js, variable `ilpa`).
 *
 * @spec-section 18.2, 18.3
 * @spec-pages 121-145
 * @spec-source resources/specsplitted/18-annexes/03-inertie-lourde/
 */
final class ClimaticSolicitations
{
    /** @return array<int, array<string, float|int|null>|null> */
    public static function heating(CalculationContext $context, int $zoneId, int $altitudeId): array
    {
        $xpath = new DOMXPath($context->document);
        $ancientMaterials = trim((string)$xpath->evaluate('string(//logement/meteo/batiment_materiaux_anciens)')) === '1';
        $tableName = $ancientMaterials
            ? 'reference/tv_sollicitations_inertie_lourde'
            : 'reference/tv_sollicitations';

        return $context->tables->load($tableName)[$zoneId][$altitudeId] ?? [];
    }
}
