<?php

declare(strict_types=1);

namespace CalculDpePHP\Collectif;

use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Nombre de logements moyens représentés par une installation de chauffage
 * individuelle échantillonnée.
 *
 * §17.1.2 p.107 définit l'appartement « moyen » par Shmoy = Sh / Nblgt : sa
 * surface, et donc son besoin, **ne dépendent pas de l'installation**. §17.1.4.2
 * p.110 calcule ensuite chaque grandeur à l'échelle de cet appartement moyen
 * puis la multiplie par `Nblgt_syst_i`, le nombre d'appartements de l'immeuble
 * équipés du système i, avant de sommer.
 *
 * Le XML ne publie pas `Nblgt_syst_i` de façon exploitable — `nombre_logement_
 * echantillon` y vaut souvent le nombre de logements visités, pas le nombre
 * représenté, et sa somme ne fait alors pas `nombre_appartement`. En revanche
 * `surface_chauffee` donne la surface de l'immeuble desservie par le système, et
 * Nblgt_syst_i = surface_chauffee / Shmoy en découle directement.
 *
 * Répartir uniformément `nombre_appartement` entre les installations conserve
 * bien la somme, mais donne à chaque système le même nombre de logements quelle
 * que soit la surface desservie — et fait donc varier le besoin de
 * l'appartement « moyen » d'une installation à l'autre, ce que §17.1.2 exclut.
 *
 * Pendant chauffage de {@see EcsInstallationMultiplicity}.
 *
 * @spec-section 17.1.2, 17.1.4.2
 * @spec-pages   107, 110
 * @spec-source  resources/specsplitted/17-collectif/01-immeuble-collectif.md
 */
final class ChauffageInstallationMultiplicity
{
    /**
     * Nblgt_syst_i, ou null si l'installation ne relève pas de ce cas.
     */
    public static function sampledOrNull(DOMElement $installation, NodeAccessor $accessor): ?float
    {
        $methode = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $installation) ?? 1;
        $type = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $installation) ?? 1;
        if ($methode === 1 || $type !== 1) {
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
        $groupSurface = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $installation) ?? 0.0;

        if ($buildingSurface <= 0.0 || $apartmentCount <= 0.0 || $groupSurface <= 0.0) {
            return null;
        }

        $ratioVirtualisation = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation', $installation) ?? 1.0;
        $averageApartmentSurface = $buildingSurface / $apartmentCount;

        return max(1e-9, $groupSurface * $ratioVirtualisation / $averageApartmentSurface);
    }

    private static function findAncestor(DOMElement $node, string $name): ?DOMElement
    {
        $current = $node->parentNode;
        while ($current instanceof DOMElement) {
            if ($current->nodeName === $name) {
                return $current;
            }
            $current = $current->parentNode;
        }

        return null;
    }
}
