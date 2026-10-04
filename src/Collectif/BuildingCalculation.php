<?php
declare(strict_types=1);
namespace CalculDpePHP\Collectif;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Dto\{BuildingInput, BuildingResult, CalculationDocument};
use CalculDpePHP\Xml\{NodeAccessor, XmlReader};
use DOMElement;
use RuntimeException;
use Throwable;

/**
 * Orchestration immeuble et appartements, sans persistance ni appel externe.
 * Chauffage homogène individuel, collectif avec ou sans IFC ; ECS homogène.
 * Les sous-cas hétérogènes restent explicites, jamais ramenés à une clé surfacique.
 * @spec-section 16.2, 17.2.2.2.1, 17.2.2.3.1, 17.2.2.4, 17.2.2.5, 17.2.2.6
 * @spec-pages 104, 115-119
 * @spec-source resources/specsplitted/17-collectif/02-appartement.md
 */
final class BuildingCalculation
{
    public static function run(BuildingInput $input): BuildingResult
    {
        try {
            $building = new CalculationDocument(CalculDpePHP::calculate($input->xml));
        } catch (Throwable $e) {
            $building = new CalculationDocument(null, $e->getMessage());
        }
        $assumptions = $input->assumptions;
        $needs = [];
        $coolingNeeds = [];
        $coolingError = null;
        $needsError = null;
        if ($building->xml !== null && ($input->heatingDistribution === 3 || ($input->heatingDistribution === 1 && ($input->individualizationCoefficient ?? 0.7) > 0))) {
            try { $needs = ApartmentHeatingNeeds::calculate($input, $building->xml, assumptions: $assumptions); }
            catch (Throwable $e) { $needsError = $e->getMessage(); }
        }
        if ($building->xml !== null) {
            $buildingDoc = (new XmlReader())->loadString($building->xml);
            $hasCooling = $buildingDoc->getElementsByTagName('climatisation')->length > 0;
            if ($hasCooling) {
                try { $coolingNeeds = ApartmentHeatingNeeds::calculate($input, $building->xml, true, $assumptions); }
                catch (Throwable $e) { $coolingError = $e->getMessage(); }
            }
        }
        $results = [];
        $counts = array_count_values(array_map(fn ($a) => $a->reference, $input->apartments));
        foreach ($input->apartments as $apt) {
            if ($input->apartmentReference !== null && $apt->reference !== $input->apartmentReference) { continue; }
            try {
                if ($apt->reference === '' || $counts[$apt->reference] !== 1) {
                    throw new RuntimeException('Référence appartement absente ou ambiguë.');
                }
                if ($apt->error !== null) { throw new RuntimeException($apt->error); }
                if ($building->xml === null) { throw new RuntimeException('Calcul immeuble indisponible : ' . $building->error); }
                if (!in_array($input->heatingDistribution, [1, 2, 3], true)) {
                    throw new RuntimeException('Répartition du chauffage hétérogène non encore prise en charge (§17.2.2.2.3).');
                }
                if (!in_array($input->hotWaterDistribution, [0, 1], true)) {
                    throw new RuntimeException('Répartition ECS hétérogène non encore prise en charge (§17.2.2.3.2).');
                }
                $doc = (new XmlReader())->loadString($input->xml);
                $a = new NodeAccessor($doc);
                $xp = new \DOMXPath($doc);
                $general = $xp->query('//logement/caracteristique_generale')->item(0);
                if (!$general instanceof DOMElement) { throw new RuntimeException('Caractéristiques générales absentes.'); }
                $surface = $a->getFloatOrNull('//caracteristique_generale/surface_habitable_immeuble') ?? 0;
                $nb = $a->getIntOrNull('//caracteristique_generale/nombre_appartement') ?? 0;
                $sum = array_sum(array_map(fn ($v) => $v->surface, $input->apartments));
                if (!is_finite($apt->surface) || !is_finite($surface) || !is_finite($sum) || $apt->surface <= 0 || $surface <= 0 || $nb !== count($input->apartments) || abs($sum - $surface) > 0.01) {
                    throw new RuntimeException('Collection incomplète ou surfaces logements/immeuble incohérentes.');
                }
                $method = $a->getIntOrNull('//enum_methode_application_dpe_log_id');
                if (!in_array($method, $input->heatingDistribution === 3 ? [6, 8] : [7, 9], true)) {
                    throw new RuntimeException('Méthode immeuble incompatible avec la répartition de chauffage demandée.');
                }
                if ($xp->query('//installation_ecs')->length === 0 || $xp->query('//installation_chauffage')->length === 0) {
                    throw new RuntimeException('Installation chauffage ou ECS absente : répartition impossible.');
                }
                $a->setChildValue($general, 'surface_habitable_logement', $apt->surface);
                $a->setChildValue($general, 'enum_methode_application_dpe_log_id', $method + 4);
                $chShare = $apt->surface / $surface;
                $coefficient = $input->heatingDistribution === 3 ? 1.0 : ($input->individualizationCoefficient ?? 0.7);
                if (!is_finite($coefficient) || $coefficient < 0 || $coefficient > 1) { throw new RuntimeException('Coefficient IFC hors de [0,1].'); }
                if ($input->heatingDistribution === 3 || ($input->heatingDistribution === 1 && ($input->individualizationCoefficient ?? 0.7) > 0)) {
                    if ($needsError !== null) { throw new RuntimeException($needsError); }
                    // §17.2.2.2.2 : (1-IFC) S_i/S + IFC Bch_i/somme(Bch).
                    $chShare = (1 - $coefficient) * $chShare + $coefficient * $needs[$apt->reference] / array_sum($needs);
                }
                // §11.1 : mêmes sollicitations climatiques, Becs est proportionnel à Nadeq.
                $ecsShare = self::occupants($apt->surface) / ($nb * self::occupants($surface / $nb));
                foreach (['chauffage' => $chShare, 'ecs' => $ecsShare] as $type => $share) {
                    foreach ($xp->query('//installation_' . $type . '/donnee_entree') as $de) {
                        $a->setChildValue($de, 'cle_repartition_' . ($type === 'chauffage' ? 'ch' : 'ecs'), $share);
                    }
                }
                // §17.2.2.4 : clé des besoins froid de l'appartement. La
                // collection Pandopia ne décrit pas de systèmes froid séparés
                // par logement : le refroidissement est supposé homogène.
                foreach ($xp->query('//climatisation/donnee_entree') as $de) {
                    if ($coolingError !== null) { throw new RuntimeException($coolingError); }
                    $sumCooling = array_sum($coolingNeeds);
                    $share = $sumCooling > 0 ? $coolingNeeds[$apt->reference] / $sumCooling : $apt->surface / $surface;
                    $a->setChildValue($de, 'cle_repartition_clim', $share);
                    $assumptions[] = 'Refroidissement supposé homogène : répartition selon les besoins froid des logements (§17.2.2.4).';
                    if ($sumCooling <= 0) { $assumptions[] = 'Besoins froid simplifiés nuls : clé surfacique utilisée.'; }
                }
                // §16.2 p.104 : les capteurs collectifs sont proratisés à la
                // surface habitable. Le nombre de modules original est conservé.
                foreach ($xp->query('//panneaux_pv') as $panel) {
                    $area = $a->getFloatOrNull('./surface_totale_capteurs', $panel);
                    if ($area === null || $area <= 0) {
                        $area = 1.6 * ($a->getIntOrNull('./nombre_module', $panel) ?? 0);
                    }
                    $a->setChildValue($panel, 'surface_totale_capteurs', $area * $apt->surface / $surface);
                }
                $results[$apt->reference] = new CalculationDocument(CalculDpePHP::calculate($doc->saveXML()));
            } catch (Throwable $e) {
                $results[$apt->reference] = new CalculationDocument(null, $e->getMessage());
            }
        }
        return new BuildingResult($building, $results, array_values(array_unique($assumptions)));
    }

    /** §11.1 p.71 : Nmax collectif puis Nadeq pour un appartement. */
    public static function occupants(float $surface): float
    {
        $nmax = $surface < 10 ? 1 : ($surface < 50 ? 1.75 - 0.01875 * (50 - $surface) : 0.035 * $surface);
        return $nmax < 1.75 ? $nmax : 1.75 + 0.3 * ($nmax - 1.75);
    }
}
