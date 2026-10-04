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
     * Le recalcul historique des bâtiments de deux logements conserve
     * le dimensionnement explicite de leur installation unique. Vérifier les surfaces et le nombre de
     * logements visités avant de distinguer ce cas d'un appartement moyen
     * extrapolé (§17.1.2). Sans preuve d'exhaustivité, garder l'échantillonnage.
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
        // Au-delà de deux logements, le modèle de l'appartement moyen reste
        // applicable, même si toutes les visites ont été réalisées.
        // L'adaptateur de nouvelles saisies impose l'éclatement des BAT à 2 lots.
        $group = $accessor->getFloatOrNull('./donnee_entree/surface_habitable', $installation) ?? 0.0;
        $rdim = $accessor->getFloatOrNull('./donnee_entree/rdim', $installation) ?? 0.0;
        if ($surface <= 0.0 || $count !== 2 || $rdim <= 0.0 || abs($surface - $group) > 0.01) { return null; }
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
