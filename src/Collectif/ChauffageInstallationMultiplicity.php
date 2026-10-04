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

    /** Part des déperditions affectée à UN générateur saisi directement. */
    public static function directShareOrNull(DOMElement $generator, NodeAccessor $accessor): ?float
    {
        $install = self::findAncestor($generator, 'installation_chauffage');
        $logement = self::findAncestor($generator, 'logement');
        if ($install === null || $logement === null
            || $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $install) !== 1
            || $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $install) !== 1) { return null; }
        // Si les installations couvrent déjà tous les logements, la méthode
        // de l'appartement moyen s'applique. Une description agrégée complète
        // peut au contraire représenter moins de systèmes que de logements :
        // sa multiplicité explicite ne doit pas être remplacée par Nblgt.
        $count = $accessor->getIntOrNull('./caracteristique_generale/nombre_appartement', $logement) ?? 0;
        $totalMultiplicity = 0.0;
        $totalSurface = 0.0;
        foreach ($logement->getElementsByTagName('installation_chauffage') as $other) {
            if ($accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $other) !== 1
                || $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $other) !== 1) { return null; }
            $multiplicity = $accessor->getFloatOrNull('./donnee_entree/rdim', $other) ?? 0.0;
            if ($multiplicity <= 0.0) { return null; }
            $totalMultiplicity += $multiplicity;
            $totalSurface += $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $other) ?? 0.0;
        }
        if ($totalMultiplicity >= $count) { return null; }
        $rdim = $accessor->getFloatOrNull('./donnee_entree/rdim', $install) ?? 0.0;
        $building = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logement) ?? 0.0;
        $group = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $install) ?? 0.0;
        return $rdim > 0.0 && $building > 0.0 && $group > 0.0 && abs($totalSurface - $building) <= 0.01
            ? min(1.0, $group / $building) / $rdim : null;
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
