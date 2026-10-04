<?php

declare(strict_types=1);

namespace CalculDpePHP\Xml;

use DOMDocument;
use DOMElement;
use DOMXPath;
use SplObjectStorage;

/**
 * Compatibilité des exports LICIEL/BBS 2.6 d'appartements sans échantillonnage.
 *
 * Ces exports conservent parfois le code installation 4 mais décrivent des
 * groupes complets, avec surface et rdim explicites. Les interpréter comme
 * des appartements moyens ajoute une extrapolation qui n'existe pas dans
 * ce format. Le calcul direct est limité aux collections dont l'exhaustivité
 * et la couverture sont vérifiables depuis les seules données d'entrée.
 *
 * §17.1.2-17.1.4 décrit l'appartement moyen issu d'un échantillon ; §17.2.2
 * répartit ensuite les consommations au logement. Il ne s'agit pas de changer
 * ces formules, mais de conserver l'échelle des installations sérialisées.
 * Les anciens exports 2.5 utilisent une autre représentation : même visités
 * exhaustivement, leurs systèmes restent à l'échelle de l'appartement moyen.
 * Aucun résultat officiel, seuil de convergence ou identifiant métier n'est lu.
 * Les valeurs XML d'origine sont restaurées par le moteur après le calcul.
 */
final class ExhaustiveInstallationNormalizer
{
    /** @return SplObjectStorage<DOMElement, string> Champs temporairement normalisés et texte original. */
    public static function apply(DOMDocument $document): SplObjectStorage
    {
        $original = new SplObjectStorage();
        $a = new NodeAccessor($document);
        $software = $a->getStringOrNull('//administratif/diagnostiqueur/version_logiciel') ?? '';
        $engine = $a->getStringOrNull('//administratif/diagnostiqueur/version_moteur_calcul') ?? '';
        if (($a->getFloatOrNull('//administratif/enum_version_id') ?? 0.0) < 2.6
            || !preg_match('/LICIEL.*Version XML:(\d+)/i', $software, $xmlVersion) || (int)$xmlVersion[1] < 319
            || !preg_match('/^(?:BBS_Slama_|3cl_bbs_V)(\d+\.\d+)/i', $engine, $engineVersion)
            || version_compare($engineVersion[1], '2025.11', '<')) {
            return $original;
        }
        $xp = new DOMXPath($document);
        $logement = $xp->query('/dpe/logement')->item(0);
        if (!$logement instanceof DOMElement
            || !in_array($a->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logement), [10, 11, 12, 13], true)
            || $a->getIntOrNull('./caracteristique_generale/enum_calcul_echantillonnage_id', $logement) !== 1) {
            return $original;
        }
        $count = $a->getIntOrNull('./caracteristique_generale/nombre_appartement', $logement) ?? 0;
        $surface = $a->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logement) ?? 0.0;
        $visited = $xp->query('/dpe/dpe_immeuble/logement_visite_collection/logement_visite');
        if ($count < 2 || $surface <= 0.0 || $visited === false || $visited->length !== $count) {
            return $original;
        }
        $visitedSurface = 0.0;
        foreach ($visited as $node) {
            $area = $a->getFloatOrNull('./surface_habitable_logement', $node) ?? 0.0;
            if ($area <= 0.0 || !is_finite($area)) { return $original; }
            $visitedSurface += $area;
        }
        if (abs($visitedSurface - $surface) > 0.01) { return $original; }

        foreach (['chauffage' => 'surface_chauffee', 'ecs' => 'surface_habitable'] as $usage => $field) {
            $installations = $xp->query('./installation_' . $usage . '_collection/installation_' . $usage, $logement);
            $covered = 0.0;
            $multiplicity = 0.0;
            foreach ($installations as $installation) {
                $rdim = $a->getFloatOrNull('./donnee_entree/rdim', $installation) ?? 0.0;
                $area = $a->getFloatOrNull('./donnee_entree/' . $field, $installation) ?? 0.0;
                $ratio = $a->getFloatOrNull('./donnee_entree/ratio_virtualisation', $installation) ?? 1.0;
                if ($a->getIntOrNull('./donnee_entree/enum_type_installation_id', $installation) !== 1
                    || !in_array($a->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $installation), [1, 4], true)
                    || $rdim <= 0.0 || !is_finite($rdim) || $area <= 0.0 || !is_finite($area)
                    || !is_finite($ratio) || abs($ratio - 1.0) > 1e-9) {
                    continue 2;
                }
                // Le changement d'échelle est établi pour les générateurs à
                // combustion. Les ballons électriques gardent un volume par
                // logement, même lorsque la surface porte sur le groupe.
                $generators = $xp->query('./generateur_' . $usage . '_collection/generateur_' . $usage, $installation);
                if ($generators->length === 0) { continue 2; }
                foreach ($generators as $generator) {
                    if (($a->getIntOrNull('./donnee_entree/tv_generateur_combustion_id', $generator) ?? 0) <= 0) {
                        continue 3;
                    }
                }
                $covered += $area;
                $multiplicity += $rdim;
            }
            if ($multiplicity >= $count || abs($covered - $surface) > 0.01) { continue; }
            foreach ($installations as $installation) {
                $method = $xp->query('./donnee_entree/enum_methode_calcul_conso_id', $installation)->item(0);
                if ($method instanceof DOMElement && trim($method->textContent) === '4') {
                    $original[$method] = $method->textContent;
                    $method->textContent = '1';
                }
            }
        }
        return $original;
    }
}
