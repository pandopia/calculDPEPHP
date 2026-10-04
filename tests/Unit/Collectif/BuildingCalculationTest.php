<?php
declare(strict_types=1);
namespace Tests\Unit\Collectif;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Collectif\BuildingCalculation;
use CalculDpePHP\Conformite\{CaseComparator, ToleranceProfile, ValueExtractor};
use CalculDpePHP\Dto\{ApartmentInput, BuildingInput};
use CalculDpePHP\Xml\{NodeAccessor, XmlReader};
use PHPUnit\Framework\TestCase;

final class BuildingCalculationTest extends TestCase
{
    public function test_repartit_chaque_usage_et_isole_une_erreur_de_logement(): void
    {
        $xml = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/comparatif-collectif.xml');
        $doc = (new XmlReader())->loadString($xml);
        $a = new NodeAccessor($doc);
        $surface = $a->getFloatOrNull('//surface_habitable_immeuble');
        $nb = $a->getIntOrNull('//nombre_appartement');
        $apartments = [];
        for ($i = 0; $i < $nb; $i++) {
            $apartments[] = new ApartmentInput('lot-' . $i, $surface / $nb, $i === 1 ? 'Donnée manquante' : null);
        }
        $result = CalculDpePHP::calculateBuilding(new BuildingInput($xml, $apartments, 2, 0));
        self::assertNull($result->building->error);
        self::assertNull($result->apartments['lot-0']->error);
        self::assertSame('Donnée manquante', $result->apartments['lot-1']->error);
        $building = new NodeAccessor((new XmlReader())->loadString($result->building->xml));
        $apt = new NodeAccessor((new XmlReader())->loadString($result->apartments['lot-0']->xml));
        foreach (['conso_ch', 'conso_ecs', 'conso_auxiliaire_generation_ch', 'conso_auxiliaire_distribution_ch', 'conso_auxiliaire_generation_ecs', 'conso_auxiliaire_distribution_ecs', 'conso_auxiliaire_ventilation', 'conso_eclairage'] as $tag) {
            $path = '//sortie/ef_conso/' . $tag;
            self::assertEqualsWithDelta($building->getFloatOrNull($path) / $nb, $apt->getFloatOrNull($path), 1e-5, $tag);
        }
        $single = CalculDpePHP::calculateBuilding(new BuildingInput($xml, $apartments, 2, 0, 'lot-0'));
        self::assertSame(['lot-0'], array_keys($single->apartments));
        $unsupported = CalculDpePHP::calculateBuilding(new BuildingInput($xml, $apartments, 3, 0));
        self::assertNotNull($unsupported->building->xml);
        self::assertStringContainsString('incompatible', $unsupported->apartments['lot-0']->error);
    }

    public function test_le_besoin_ecs_ne_se_repartit_pas_par_surface(): void
    {
        self::assertEqualsWithDelta(1.375, BuildingCalculation::occupants(30), 1e-8);
        self::assertNotEquals(30 / 60, BuildingCalculation::occupants(30) / BuildingCalculation::occupants(60));
    }

    public function test_refuse_les_references_de_logement_ambigues(): void
    {
        $input = new BuildingInput('<dpe/>', [new ApartmentInput('A', 20), new ApartmentInput('A', 30)], 2, 0);
        $result = CalculDpePHP::calculateBuilding($input);
        self::assertNotNull($result->building->error);
        self::assertStringContainsString('ambiguë', $result->apartments['A']->error);
    }

    public function test_compare_en_memoire_et_classe_tous_les_codes_ademe(): void
    {
        $doc = (new XmlReader())->loadString('<dpe><logement><sortie><conso_ch>0</conso_ch></sortie></logement></dpe>');
        $comparator = new CaseComparator(ToleranceProfile::strict(), null, new ValueExtractor(true));
        self::assertCount(1, $comparator->compareDocuments($doc, $doc));
        self::assertCount(0, $comparator->compareDocuments($doc, $doc, false));
        foreach ([6,7,8,9,26,27,28,29,30] as $method) self::assertSame('immeuble_collectif', ValueExtractor::perimetre($method));
        foreach ([2,3,4,5,31,32,35,36,37] as $method) self::assertSame('appartement_individuel', ValueExtractor::perimetre($method));
        foreach ([10,11,12,13,33,34,38,39,40] as $method) self::assertSame('appartement_issu_immeuble', ValueExtractor::perimetre($method));
    }
}
