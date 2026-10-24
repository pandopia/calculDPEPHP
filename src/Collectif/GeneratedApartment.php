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

    /**
     * §17.2.1 p.112-113 : les consommations d'un appartement décrit
     * directement sont calculées depuis son propre besoin. Le XSD réserve
     * cle_repartition_ch au « DPE appartement calculé à partir du DPE
     * immeuble UNIQUEMENT » : la lui appliquer ferait un second prorata.
     * Sans méthode déclarée, conserver la clé pour les documents minimaux.
     */
    public static function heatingShare(DOMElement $logement, NodeAccessor $accessor, float $declaredShare): float
    {
        $method = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logement);
        if ($method !== null && !in_array($method, self::METHODS, true)) {
            return 1.0;
        }

        return $declaredShare >= 1.0
            ? (self::surfaceShare($logement, $accessor) ?? $declaredShare)
            : $declaredShare;
    }

    /**
     * §17.2.2.3 p.117 et XSD cle_repartition_ecs : clé immeuble → logement,
     * uniquement pour l'appartement généré. L'ECS d'un appartement décrit
     * directement est déjà calculée sur son besoin propre. Contrairement au
     * chauffage, une clé ECS unitaire ne se remplace pas par Sh_log/Sh_imm :
     * cette clé porte sur les besoins d'ECS, pas sur les surfaces.
     */
    public static function hotWaterShare(DOMElement $logement, NodeAccessor $accessor, float $declaredShare): float
    {
        $method = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logement);

        return $method !== null && !in_array($method, self::METHODS, true) ? 1.0 : $declaredShare;
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
