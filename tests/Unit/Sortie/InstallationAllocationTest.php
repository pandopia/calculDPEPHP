<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Common\Period;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\ConsoParEnergieCollector;
use CalculDpePHP\Sortie\EfConsoCalculator;
use CalculDpePHP\Sortie\EmissionGesCalculator;
use CalculDpePHP\Sortie\EpConsoCalculator;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** §17.2.2 p.115–118 : la même clé d'installation alimente EF, EP et GES. */
final class InstallationAllocationTest extends TestCase
{
    #[DataProvider('perimetres')]
    public function testLesSortiesEtLesLignesEnergieUtilisentLesMemesCles(?int $method, bool $reverse, Period $period): void
    {
        $ch = $ecs = [];
        $epElec = $period === Period::PRE_2026 ? 2.3 : 1.9;
        foreach ([[1000, 0.2, 1], [2000, 0.3, 2]] as [$conso, $key, $energy]) {
            foreach (['ch', 'ecs'] as $usage) {
                $install = $usage === 'ch' ? 'installation_chauffage' : 'installation_ecs';
                $gen = $usage === 'ch' ? 'generateur_chauffage' : 'generateur_ecs';
                $di = '<donnee_intermediaire><conso_' . $usage . '>' . $conso . '</conso_' . $usage . '><conso_' . $usage . '_depensier>' . (2 * $conso) . '</conso_' . $usage . '_depensier></donnee_intermediaire>';
                ${$usage}[] = '<' . $install . '><donnee_entree><rdim>1</rdim><cle_repartition_' . $usage . '>' . $key . '</cle_repartition_' . $usage . '></donnee_entree>' . $di
                    . '<' . $gen . '_collection><' . $gen . '><donnee_entree><enum_type_energie_id>' . $energy . '</enum_type_energie_id></donnee_entree>' . $di . '</' . $gen . '></' . $gen . '_collection></' . $install . '>';
            }
        }
        $mode = $method === null ? '' : '<enum_methode_application_dpe_log_id>' . $method . '</enum_methode_application_dpe_log_id>';
        $sh = $method === 6 ? '' : '<surface_habitable_logement>50</surface_habitable_logement>';
        $doc = new DOMDocument();
        $doc->loadXML('<dpe version="0.1.0"><logement><caracteristique_generale>' . $mode . $sh . '<surface_habitable_immeuble>1000</surface_habitable_immeuble></caracteristique_generale><installation_chauffage_collection>' . implode('', $reverse ? array_reverse($ch) : $ch)
            . '</installation_chauffage_collection><installation_ecs_collection>' . implode('', $reverse ? array_reverse($ecs) : $ecs) . '</installation_ecs_collection></logement></dpe>');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $context = new CalculationContext($doc, new TableRepository(__DIR__ . '/../../../resources/tables'), period: $period);
        foreach ([new EfConsoCalculator(), new EpConsoCalculator(), new EmissionGesCalculator()] as $calc) {
            $calc->calculate($logement, $context);
        }
        $xp = new DOMXPath($doc);
        $local = $method === null || $method === 10;
        [$electric, $gas] = $local ? [200.0, 600.0] : [1000.0, 2000.0];
        foreach (['ch', 'ecs'] as $usage) {
            $expectedEf = $electric + $gas;
            $expectedEp = $electric * $epElec + $gas;
            $expectedGes = $electric * ($usage === 'ch' ? 0.079 : 0.065) + $gas * 0.227;
            self::assertEqualsWithDelta($expectedEf, $xp->evaluate('number(//ef_conso/conso_' . $usage . ')'), 1e-10);
            self::assertEqualsWithDelta($expectedEp, $xp->evaluate('number(//ep_conso/ep_conso_' . $usage . ')'), 1e-10);
            self::assertEqualsWithDelta($expectedGes, $xp->evaluate('number(//emission_ges/emission_ges_' . $usage . ')'), 1e-10);
            self::assertEqualsWithDelta(2 * $expectedEp, $xp->evaluate('number(//ep_conso/ep_conso_' . $usage . '_depensier)'), 1e-10);
            self::assertEqualsWithDelta(2 * $expectedGes, $xp->evaluate('number(//emission_ges/emission_ges_' . $usage . '_depensier)'), 1e-10);
            $rows = ConsoParEnergieCollector::collecter(new NodeAccessor($doc), $logement, $usage === 'ch' ? 'installation_chauffage' : 'installation_ecs', $usage === 'ch' ? 'generateur_chauffage' : 'generateur_ecs', 'conso_' . $usage, $context)['byEnergie'];
            self::assertEqualsWithDelta($expectedEf, array_sum(array_column($rows, 0)), 1e-10);
        }
    }

    public static function perimetres(): iterable
    {
        foreach ([null, 2, 6, 10] as $method) {
            foreach ([false, true] as $reverse) {
                foreach ([Period::PRE_2026, Period::POST_2026] as $period) {
                    yield ($method ?? 'minimal') . ' ' . (int)$reverse . ' ' . $period->name => [$method, $reverse, $period];
                }
            }
        }
    }
}
