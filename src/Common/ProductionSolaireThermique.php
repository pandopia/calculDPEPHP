<?php

declare(strict_types=1);

namespace CalculDpePHP\Common;

use CalculDpePHP\Engine\CalculationContext;
use DOMElement;

/**
 * Production solaire thermique conventionnelle, en kWh, au périmètre calculé.
 *
 * Chaque installation publie son énergie une seule fois, multiplicité incluse.
 * Le canal reste interne : les unités des anciens exports XML n'interviennent
 * pas dans le bloc production_enr du modèle 2.7.
 *
 * @spec-section Annexe 2, point 1.2.4
 * @spec-pages 7
 * @spec-source resources/textes/arrete-2026-06-11-epbd.pdf
 */
final class ProductionSolaireThermique
{
    private const KEY = 'enr.solaire_thermique_par_installation';

    public static function publier(CalculationContext $context, DOMElement $installation, float $kwh): void
    {
        $productions = (array)$context->get(self::KEY, []);
        // La clé est propre au document et au passage du pipeline ; remplacer
        // une valeur permet aussi de recalculer une installation sans doublon.
        $productions[$installation->getNodePath()] = max(0.0, $kwh);
        $context->set(self::KEY, $productions);
    }

    public static function total(CalculationContext $context): float
    {
        return array_sum((array)$context->get(self::KEY, []));
    }
}
