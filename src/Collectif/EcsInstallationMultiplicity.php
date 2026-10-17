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

        $exhaustive = self::exhaustiveHomogeneousOrNull($installation, $accessor);
        if ($exhaustive !== null) { return $exhaustive; }

        $averageApartmentSurface = $buildingSurface / $apartmentCount;
        return max(1e-9, $groupSurface / $averageApartmentSurface);
    }

    /**
     * §17.1.2 p.107 n'introduit l'appartement « moyen » que pour extrapoler
     * un échantillon. Quand le fichier déclare `enum_calcul_echantillonnage_id`
     * = 1, « absence de méthode d'échantillonnage », il n'y a rien à
     * extrapoler : l'installation unique qui couvre l'immeuble garde le
     * dimensionnement explicite qu'elle porte. Les surfaces visitées le
     * corroborent ; sans cette preuve, garder l'échantillonnage.
     */
    public static function exhaustiveHomogeneousOrNull(DOMElement $installation, NodeAccessor $accessor): ?float
    {
        if ($accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $installation) !== 1
            || $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $installation) !== 4) {
            return null;
        }
        $logement = self::findAncestor($installation, 'logement');
        if ($logement === null || $logement->getElementsByTagName('installation_ecs')->length !== 1) { return null; }
        $surface = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logement) ?? 0.0;
        $count = $accessor->getIntOrNull('./caracteristique_generale/nombre_appartement', $logement) ?? 0;
        $group = $accessor->getFloatOrNull('./donnee_entree/surface_habitable', $installation) ?? 0.0;
        $rdim = $accessor->getFloatOrNull('./donnee_entree/rdim', $installation) ?? 0.0;
        if ($accessor->getIntOrNull('./caracteristique_generale/enum_calcul_echantillonnage_id', $logement) !== 1) { return null; }
        if ($surface <= 0.0 || $count < 2 || $rdim <= 0.0 || abs($surface - $group) > 0.01) { return null; }
        $xp = new \DOMXPath($installation->ownerDocument);
        $visited = $xp->query('//dpe_immeuble/logement_visite_collection/logement_visite/surface_habitable_logement');
        if ($visited === false || $visited->length !== $count) { return null; }
        $total = 0.0;
        foreach ($visited as $node) {
            $value = (float)str_replace(',', '.', trim($node->textContent));
            if ($value <= 0.0) { return null; }
            $total += $value;
        }
        return abs($surface - $total) <= 0.01 ? $rdim : null;
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
