<?php

declare(strict_types=1);

namespace CalculDpePHP\Collectif;

use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Nombre de logements moyens représentés par une installation ECS individuelle.
 *
 * §17.1.3.2 p.109 : pour une installation individuelle échantillonnée, la
 * consommation d'un appartement moyen est multipliée par le nombre de logements
 * équipés du système. Le XML fournit la surface totale du groupe ; avec
 * Shmoy = Sh_immeuble / Nblgt, la multiplicité vaut surface_groupe / Shmoy.
 */
final class EcsInstallationMultiplicity
{
    public static function sampledOrNull(DOMElement $installation, NodeAccessor $accessor): ?float
    {
        $method = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $installation) ?? 1;
        $type = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $installation) ?? 1;
        if ($method !== 4 || $type !== 1) {
            return null;
        }

        $logement = self::findAncestor($installation, 'logement');
        if ($logement === null) {
            return null;
        }

        $buildingSurface = $accessor->getFloatOrNull(
            './caracteristique_generale/surface_habitable_immeuble',
            $logement,
        ) ?? 0.0;
        $apartmentCount = $accessor->getFloatOrNull(
            './caracteristique_generale/nombre_appartement',
            $logement,
        ) ?? 0.0;
        $groupSurface = $accessor->getFloatOrNull('./donnee_entree/surface_habitable', $installation) ?? 0.0;

        if ($buildingSurface <= 0.0 || $apartmentCount <= 0.0 || $groupSurface <= 0.0) {
            return null;
        }

        $averageApartmentSurface = $buildingSurface / $apartmentCount;
        return max(1e-9, $groupSurface / $averageApartmentSurface);
    }

    private static function findAncestor(DOMElement $node, string $tagName): ?DOMElement
    {
        $current = $node->parentNode;
        while ($current !== null) {
            if ($current instanceof DOMElement && $current->nodeName === $tagName) {
                return $current;
            }
            $current = $current->parentNode;
        }
        return null;
    }
}
