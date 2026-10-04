<?php
declare(strict_types=1);
namespace Tests\Unit\Collectif;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Collectif\ApartmentHeatingNeeds;
use CalculDpePHP\Dto\{ApartmentInput, BuildingInput};
use CalculDpePHP\Xml\{NodeAccessor, XmlReader};
use PHPUnit\Framework\TestCase;

final class ApartmentApproximationTest extends TestCase
{
    private function input(): BuildingInput
    {
        $doc = (new XmlReader())->loadString(file_get_contents(dirname(__DIR__, 2) . '/Fixtures/comparatif-collectif.xml'));
        $a = new NodeAccessor($doc);
        foreach (iterator_to_array($doc->getElementsByTagName('pont_thermique')) as $node) { $node->parentNode->removeChild($node); }
        $a->setChildValue($doc->getElementsByTagName('caracteristique_generale')->item(0), 'nombre_appartement', 3);
        $surface = $a->getFloatOrNull('//surface_habitable_immeuble');
        $links = [];
        foreach (['mur' => 'murs', 'plancher_bas' => 'plancher', 'plancher_haut' => 'plafond', 'porte' => 'porte', 'baie_vitree' => 'fenetre'] as $tag => $key) {
            foreach ($doc->getElementsByTagName($tag) as $part) { $links[$key][] = $a->getStringOrNull('./donnee_entree/reference', $part); }
        }
        $apartments = [];
        foreach (['bas', 'milieu', 'haut'] as $i => $id) {
            $own = $links;
            if ($i !== 0) { $own['plancher'] = []; }
            if ($i !== 2) { $own['plafond'] = []; }
            $apartments[] = new ApartmentInput($id, $surface / 3, associations: $own, position: $i + 1);
        }
        return new BuildingInput($doc->saveXML(), $apartments, 1, 0, individualizationCoefficient: 0.7);
    }

    public function test_la_toiture_orpheline_retrouve_les_logements_du_dernier_etage(): void
    {
        $input = $this->input();
        $building = CalculDpePHP::calculate($input->xml);
        $expected = ApartmentHeatingNeeds::calculate($input, $building);
        $apartments = [];
        foreach ($input->apartments as $apt) {
            $links = $apt->associations;
            $links['plafond'] = [];
            $apartments[] = new ApartmentInput($apt->reference, $apt->surface, associations: $links, position: $apt->position);
        }
        $approx = new BuildingInput($input->xml, $apartments, 1, 0, approximateMissingAssociations: true);
        $notes = [];
        $actual = ApartmentHeatingNeeds::calculate($approx, $building, assumptions: $notes);
        foreach ($expected as $id => $need) { self::assertEqualsWithDelta($need, $actual[$id], 1e-6); }
        self::assertStringContainsString('dernier étage', implode(' ', $notes));
        self::assertSame($input->xml, $approx->xml);
    }

    public function test_les_portes_sans_liaison_herient_des_logements_du_mur_support(): void
    {
        $input = $this->input();
        $doc = (new XmlReader())->loadString($input->xml);
        $a = new NodeAccessor($doc);
        $parent = $input->apartments[0]->associations['murs'][0];
        foreach ($doc->getElementsByTagName('porte') as $door) {
            $a->setChildValue($door->getElementsByTagName('donnee_entree')->item(0), 'reference_paroi', $parent);
        }
        $apartments = [];
        foreach ($input->apartments as $apt) {
            $links = $apt->associations;
            $links['porte'] = [];
            $apartments[] = new ApartmentInput($apt->reference, $apt->surface, associations: $links, position: $apt->position);
        }
        $xml = $doc->saveXML();
        $expected = ApartmentHeatingNeeds::calculate(new BuildingInput($xml, $input->apartments, 1, 0), CalculDpePHP::calculate($xml));
        $notes = [];
        $actual = ApartmentHeatingNeeds::calculate(new BuildingInput($xml, $apartments, 1, 0, approximateMissingAssociations: true), CalculDpePHP::calculate($xml), assumptions: $notes);
        foreach ($expected as $id => $need) { self::assertEqualsWithDelta($need, $actual[$id], 1e-6); }
        self::assertStringContainsString('mur support', implode(' ', $notes));
    }

    public function test_le_repli_sans_position_est_trace_et_conserve_le_chauffage_total(): void
    {
        $input = $this->input();
        $apartments = array_map(fn ($a) => new ApartmentInput($a->reference, $a->surface), $input->apartments);
        $approx = new BuildingInput($input->xml, $apartments, 1, 0, approximateMissingAssociations: true);
        $result = CalculDpePHP::calculateBuilding($approx);
        self::assertNotEmpty($result->assumptions);
        self::assertStringContainsString('ensemble des logements', implode(' ', $result->assumptions));
        $sum = 0.0;
        foreach ($result->apartments as $apartment) {
            self::assertNull($apartment->error);
            $sum += (new NodeAccessor((new XmlReader())->loadString($apartment->xml)))->getFloatOrNull('//sortie/ef_conso/conso_ch');
        }
        $building = new NodeAccessor((new XmlReader())->loadString($result->building->xml));
        self::assertEqualsWithDelta($building->getFloatOrNull('//sortie/ef_conso/conso_ch'), $sum, 1e-5);
        $reversed = new BuildingInput($input->xml, array_reverse($apartments), 1, 0, approximateMissingAssociations: true);
        $a = ApartmentHeatingNeeds::calculate($approx, $result->building->xml);
        $b = ApartmentHeatingNeeds::calculate($reversed, $result->building->xml);
        foreach ($a as $id => $need) { self::assertEqualsWithDelta($need, $b[$id], 1e-6); }
    }

    public function test_les_liens_explicites_ne_sont_pas_elargis_en_mode_approximatif(): void
    {
        $input = $this->input();
        $building = CalculDpePHP::calculate($input->xml);
        $notes = [];
        $exact = ApartmentHeatingNeeds::calculate($input, $building);
        $approx = ApartmentHeatingNeeds::calculate(new BuildingInput($input->xml, $input->apartments, 1, 0, approximateMissingAssociations: true), $building, assumptions: $notes);
        self::assertSame($exact, $approx);
        self::assertSame([], $notes);
    }

    public function test_un_coefficient_ifc_nul_n_exige_pas_de_repartition_par_les_parois(): void
    {
        $input = $this->input();
        $apartments = array_map(fn ($a) => new ApartmentInput($a->reference, $a->surface), $input->apartments);
        $surface = CalculDpePHP::calculateBuilding(new BuildingInput($input->xml, $apartments, 2, 0));
        $zero = CalculDpePHP::calculateBuilding(new BuildingInput($input->xml, $apartments, 1, 0, individualizationCoefficient: 0));
        foreach ($apartments as $apt) {
            self::assertNull($zero->apartments[$apt->reference]->error);
            self::assertSame($surface->apartments[$apt->reference]->xml, $zero->apartments[$apt->reference]->xml);
        }
        $default = CalculDpePHP::calculateBuilding(new BuildingInput($input->xml, $apartments, 1, 0));
        self::assertStringContainsString('sans association', $default->apartments['bas']->error);
        $invalid = CalculDpePHP::calculateBuilding(new BuildingInput($input->xml, $apartments, 1, 0, individualizationCoefficient: -0.1));
        self::assertStringContainsString('hors de', $invalid->apartments['bas']->error);
    }

    public function test_la_surface_photovoltaique_collective_est_repartie_une_seule_fois(): void
    {
        $input = $this->input();
        $doc = (new XmlReader())->loadString($input->xml);
        foreach (iterator_to_array($doc->getElementsByTagName('production_elec_enr')) as $old) { $old->parentNode->removeChild($old); }
        $fragment = $doc->createDocumentFragment();
        $fragment->appendXML('<production_elec_enr><donnee_entree><presence_production_pv>1</presence_production_pv></donnee_entree><panneaux_pv_collection><panneaux_pv><surface_totale_capteurs>12</surface_totale_capteurs><tv_coef_orientation_pv_id>1</tv_coef_orientation_pv_id></panneaux_pv></panneaux_pv_collection></production_elec_enr>');
        $doc->getElementsByTagName('logement')->item(0)->appendChild($fragment);
        $result = CalculDpePHP::calculateBuilding(new BuildingInput($doc->saveXML(), $input->apartments, 2, 0));
        $sum = 0.0;
        foreach ($result->apartments as $apt) {
            self::assertNull($apt->error);
            $a = new NodeAccessor((new XmlReader())->loadString($apt->xml));
            self::assertEqualsWithDelta(4, $a->getFloatOrNull('//surface_totale_capteurs'), 1e-9);
            $sum += $a->getFloatOrNull('//production_elec_enr/donnee_intermediaire/production_pv');
        }
        $building = new NodeAccessor((new XmlReader())->loadString($result->building->xml));
        self::assertGreaterThan(0, $sum);
        self::assertEqualsWithDelta($building->getFloatOrNull('//production_elec_enr/donnee_intermediaire/production_pv'), $sum, 1e-5);
    }
}
