<?php

declare(strict_types=1);

namespace CalculDpePHP\Collectif;

use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Échelle de calcul d'un appartement généré depuis le DPE de l'immeuble.
 *
 * §17.2.2 : les DPE individuels sont établis depuis les informations collectées
 * ou calculées pour le DPE immeuble. Les consommations sont ensuite réparties
 * à l'appartement selon les règles propres à chaque usage.
 *
 * @spec-section 17.2.2
 * @spec-pages 114-119
 * @spec-source resources/specsplitted/17-collectif/02-appartement.md
 */
final class GeneratedApartment
{
    private const METHODS = [10, 11, 12, 13, 33, 34, 38, 39, 40];

    public static function calculationSurface(
        DOMElement $descendant,
        NodeAccessor $accessor,
        ?float $fallback = null,
    ): ?float {
        $logement = self::ancestor($descendant, 'logement');
        if ($logement === null) {
            return $fallback;
        }

        $method = $accessor->getIntOrNull(
            './caracteristique_generale/enum_methode_application_dpe_log_id',
            $logement,
        );
        if (!in_array($method, self::METHODS, true)) {
            return $fallback;
        }

        return $accessor->getFloatOrNull(
            './caracteristique_generale/surface_habitable_immeuble',
            $logement,
        ) ?? $fallback;
    }

    public static function isGenerated(DOMElement $logement, NodeAccessor $accessor): bool
    {
        $method = $accessor->getIntOrNull(
            './caracteristique_generale/enum_methode_application_dpe_log_id',
            $logement,
        );
        return in_array($method, self::METHODS, true);
    }

    /** §17.2.2.2.1 : clé surfacique de répartition immeuble → appartement. */
    public static function surfaceShare(DOMElement $logement, NodeAccessor $accessor): ?float
    {
        if (!self::isGenerated($logement, $accessor)) {
            return null;
        }
        $apartment = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $logement);
        $building = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logement);
        return $apartment !== null && $building !== null && $building > 0.0
            ? $apartment / $building
            : null;
    }

    private static function ancestor(DOMElement $node, string $name): ?DOMElement
    {
        $current = $node;
        while ($current !== null) {
            if ($current instanceof DOMElement && $current->nodeName === $name) {
                return $current;
            }
            $current = $current->parentNode;
        }
        return null;
    }
}
