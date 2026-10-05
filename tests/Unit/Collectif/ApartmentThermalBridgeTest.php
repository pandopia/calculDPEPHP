<?php
declare(strict_types=1);
namespace Tests\Unit\Collectif;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Collectif\ApartmentHeatingNeeds;
use CalculDpePHP\Dto\{ApartmentInput, BuildingInput};
use CalculDpePHP\Xml\{NodeAccessor, XmlReader};
use PHPUnit\Framework\TestCase;

/** Ponts thermiques saisis sans référence de paroi (ponts « manuels » LICIEL). */
final class ApartmentThermalBridgeTest extends TestCase
{
    /** @return array{string, list<string>, array<string, list<string>>, float} */
    private function building(): array
    {
        $doc = (new XmlReader())->loadString(file_get_contents(dirname(__DIR__, 2) . '/Fixtures/comparatif-collectif.xml'));
        $a = new NodeAccessor($doc);
        $bridges = [];
        foreach ($doc->getElementsByTagName('pont_thermique') as $bridge) {
            $de = $bridge->getElementsByTagName('donnee_entree')->item(0);
            foreach (['reference_1', 'reference_2'] as $tag) {
                foreach (iterator_to_array($de->getElementsByTagName($tag)) as $node) { $node->parentNode->removeChild($node); }
            }
            $bridges[] = $a->getStringOrNull('./reference', $de);
        }
        $a->setChildValue($doc->getElementsByTagName('caracteristique_generale')->item(0), 'nombre_appartement', 2);
        $links = [];
        foreach (['mur' => 'murs', 'plancher_bas' => 'plancher', 'plancher_haut' => 'plafond', 'porte' => 'porte', 'baie_vitree' => 'fenetre'] as $tag => $key) {
            foreach ($doc->getElementsByTagName($tag) as $part) { $links[$key][] = $a->getStringOrNull('./donnee_entree/reference', $part); }
        }
        return [$doc->saveXML(), $bridges, $links, $a->getFloatOrNull('//surface_habitable_immeuble')];
    }

    public function test_un_pont_sans_paroi_ni_liaison_est_refuse_avec_son_nom(): void
    {
        [$xml, $bridges, $links, $surface] = $this->building();
        $input = new BuildingInput($xml, [new ApartmentInput('a', $surface / 2, associations: $links), new ApartmentInput('b', $surface / 2, associations: $links)], 1, 0, individualizationCoefficient: 0.7);
        $this->expectExceptionMessage('Pont thermique ' . $bridges[0] . ' sans parois associées');
        ApartmentHeatingNeeds::calculate($input, CalculDpePHP::calculate($xml));
    }

    public function test_les_ponts_relies_aux_logements_comptent_pour_eux_seuls(): void
    {
        [$xml, $bridges, $links, $surface] = $this->building();
        $calculated = CalculDpePHP::calculate($xml);
        $with = $links + ['pont_thermique' => $bridges];
        $input = new BuildingInput($xml, [new ApartmentInput('a', $surface / 2, associations: $with), new ApartmentInput('b', $surface / 2, associations: $links + ['pont_thermique' => [$bridges[0]]])], 1, 0, individualizationCoefficient: 0.7);
        $needs = ApartmentHeatingNeeds::calculate($input, $calculated);
        self::assertGreaterThan($needs['b'], $needs['a']);
        $result = CalculDpePHP::calculateBuilding($input);
        self::assertNull($result->apartments['a']->error);
        self::assertNull($result->apartments['b']->error);
    }

    public function test_l_approximation_repartit_les_ponts_entre_tous_les_logements(): void
    {
        [$xml, $bridges, $links, $surface] = $this->building();
        $input = new BuildingInput($xml, [new ApartmentInput('a', $surface / 2, associations: $links), new ApartmentInput('b', $surface / 2, associations: $links)], 1, 0, individualizationCoefficient: 0.7, approximateMissingAssociations: true);
        $notes = [];
        $needs = ApartmentHeatingNeeds::calculate($input, CalculDpePHP::calculate($xml), assumptions: $notes);
        self::assertEqualsWithDelta($needs['a'], $needs['b'], 1e-6);
        self::assertStringContainsString('Pont thermique ' . $bridges[0] . ' sans liaison', implode("\n", $notes));
    }

    public function test_les_ponts_rattaches_a_leurs_parois_suivent_les_logements_de_ces_parois(): void
    {
        $doc = (new XmlReader())->loadString(file_get_contents(dirname(__DIR__, 2) . '/Fixtures/comparatif-collectif.xml'));
        $a = new NodeAccessor($doc);
        $a->setChildValue($doc->getElementsByTagName('caracteristique_generale')->item(0), 'nombre_appartement', 2);
        $links = [];
        foreach (['mur' => 'murs', 'plancher_bas' => 'plancher', 'plancher_haut' => 'plafond', 'porte' => 'porte', 'baie_vitree' => 'fenetre'] as $tag => $key) {
            foreach ($doc->getElementsByTagName($tag) as $part) { $links[$key][] = $a->getStringOrNull('./donnee_entree/reference', $part); }
        }
        $surface = $a->getFloatOrNull('//surface_habitable_immeuble');
        // refends référencés par reference_2 seulement : acceptés, sans approximation
        $input = new BuildingInput($doc->saveXML(), [new ApartmentInput('a', $surface / 2, associations: $links), new ApartmentInput('b', $surface / 2, associations: $links)], 1, 0, individualizationCoefficient: 0.7);
        $notes = [];
        $needs = ApartmentHeatingNeeds::calculate($input, CalculDpePHP::calculate($input->xml), assumptions: $notes);
        self::assertEqualsWithDelta($needs['a'], $needs['b'], 1e-6);
        self::assertSame([], $notes);
    }
}
