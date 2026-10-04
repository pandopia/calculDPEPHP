<?php
declare(strict_types=1);
namespace Tests\Unit\Collectif;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Collectif\ApartmentHeatingNeeds;
use CalculDpePHP\Dto\{ApartmentInput, BuildingInput};
use CalculDpePHP\Xml\{NodeAccessor, XmlReader};
use PHPUnit\Framework\TestCase;

final class ApartmentHeatingNeedsTest extends TestCase
{
    private function input(): BuildingInput
    {
        $xml = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/comparatif-collectif.xml');
        $doc = (new XmlReader())->loadString($xml);
        $a = new NodeAccessor($doc);
        // Ce scénario synthétique isole les déperditions des surfaces.
        foreach (iterator_to_array($doc->getElementsByTagName('pont_thermique')) as $bridge) {
            $bridge->parentNode->removeChild($bridge);
        }
        $general = $doc->getElementsByTagName('caracteristique_generale')->item(0);
        $a->setChildValue($general, 'nombre_appartement', 3);
        $surface = $a->getFloatOrNull('//surface_habitable_immeuble');
        $links = [];
        foreach (['mur' => 'murs', 'plancher_bas' => 'plancher', 'plancher_haut' => 'plafond', 'porte' => 'porte', 'baie_vitree' => 'fenetre'] as $tag => $key) {
            foreach ($doc->getElementsByTagName($tag) as $part) {
                $links[$key][] = $a->getStringOrNull('./donnee_entree/reference', $part);
            }
        }
        // Logements de même surface mais le dernier seul sous la toiture.
        $lower = $links;
        $lower['plafond'] = [];
        $apartments = [new ApartmentInput('bas', $surface / 3, associations: $lower), new ApartmentInput('milieu', $surface / 3, associations: $lower), new ApartmentInput('haut', $surface / 3, associations: $links)];
        return new BuildingInput($doc->saveXML(), $apartments, 1, 0, individualizationCoefficient: 0.7);
    }

    public function test_la_toiture_augmente_le_besoin_et_les_cles_ifc_conservent_le_chauffage(): void
    {
        $input = $this->input();
        $building = CalculDpePHP::calculate($input->xml);
        $needs = ApartmentHeatingNeeds::calculate($input, $building);
        self::assertEqualsWithDelta($needs['bas'], $needs['milieu'], 1e-6);
        self::assertGreaterThan($needs['milieu'], $needs['haut']);
        $result = CalculDpePHP::calculateBuilding($input);
        $sum = 0.0;
        foreach ($input->apartments as $apt) {
            $out = $result->apartments[$apt->reference];
            self::assertNull($out->error);
            $a = new NodeAccessor((new XmlReader())->loadString($out->xml));
            $expected = 0.3 / 3 + 0.7 * $needs[$apt->reference] / array_sum($needs);
            self::assertEqualsWithDelta($expected, $a->getFloatOrNull('//installation_chauffage/donnee_entree/cle_repartition_ch'), 1e-10);
            $sum += $a->getFloatOrNull('//sortie/ef_conso/conso_ch');
        }
        $a = new NodeAccessor((new XmlReader())->loadString($building));
        self::assertEqualsWithDelta($a->getFloatOrNull('//sortie/ef_conso/conso_ch'), $sum, 1e-6);
    }

    public function test_une_paroi_non_associee_interdit_une_cle_inventee(): void
    {
        $input = $this->input();
        $invalid = new BuildingInput($input->xml, [new ApartmentInput('sans-paroi', 20)], 1, 0);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('sans association logement');
        ApartmentHeatingNeeds::calculate($invalid, CalculDpePHP::calculate($input->xml));
    }
}
