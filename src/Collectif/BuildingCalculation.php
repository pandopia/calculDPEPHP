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
 * Première répartition supportée : chauffage collectif sans IFC, ECS homogène.
 * Les autres méthodes sont explicitement non prises en charge, jamais ramenées
 * arbitrairement à une clé surfacique.
 * @spec-section 17.2.2.2.1, 17.2.2.3.1, 17.2.2.5, 17.2.2.6
 * @spec-pages 115-119
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
                if ($input->heatingDistribution !== 2) {
                    throw new RuntimeException('Répartition chauffage par besoins/IFC ou systèmes hétérogènes non encore prise en charge (§17.2.2.2). Les associations parois, fenêtres et motifs doivent être complétées pour estimer les besoins de chaque logement.');
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
                if ($apt->surface <= 0 || $surface <= 0 || $nb !== count($input->apartments) || abs($sum - $surface) > 0.01) {
                    throw new RuntimeException('Collection incomplète ou surfaces logements/immeuble incohérentes.');
                }
                if ($xp->query('//climatisation/donnee_entree')->length > 0 || $a->getIntOrNull('//presence_production_pv') === 1) {
                    throw new RuntimeException('Répartition refroidissement ou photovoltaïque non encore prise en charge.');
                }
                $method = $a->getIntOrNull('//enum_methode_application_dpe_log_id');
                if (!in_array($method, [7, 9], true)) {
                    throw new RuntimeException('Méthode immeuble incompatible avec une répartition de chauffage collectif homogène.');
                }
                if ($xp->query('//installation_ecs')->length !== 1 || $xp->query('//installation_chauffage')->length !== 1) {
                    throw new RuntimeException('Multiplicité des installations : répartition à compléter.');
                }
                $a->setChildValue($general, 'surface_habitable_logement', $apt->surface);
                $a->setChildValue($general, 'enum_methode_application_dpe_log_id', $method + 4);
                $chShare = $apt->surface / $surface;
                // §11.1 : mêmes sollicitations climatiques, Becs est proportionnel à Nadeq.
                $ecsShare = self::occupants($apt->surface) / ($nb * self::occupants($surface / $nb));
                foreach (['chauffage' => $chShare, 'ecs' => $ecsShare] as $type => $share) {
                    foreach ($xp->query('//installation_' . $type . '/donnee_entree') as $de) {
                        $a->setChildValue($de, 'cle_repartition_' . ($type === 'chauffage' ? 'ch' : 'ecs'), $share);
                    }
                }
                $results[$apt->reference] = new CalculationDocument(CalculDpePHP::calculate($doc->saveXML()));
            } catch (Throwable $e) {
                $results[$apt->reference] = new CalculationDocument(null, $e->getMessage());
            }
        }
        return new BuildingResult($building, $results);
    }

    /** §11.1 p.71 : Nmax collectif puis Nadeq pour un appartement. */
    public static function occupants(float $surface): float
    {
        $nmax = $surface < 10 ? 1 : ($surface < 50 ? 1.75 - 0.01875 * (50 - $surface) : 0.035 * $surface);
        return $nmax < 1.75 ? $nmax : 1.75 + 0.3 * ($nmax - 1.75);
    }
}
