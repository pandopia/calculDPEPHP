<?php
declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\ProductionElec\ProductionPvCalculator;
use CalculDpePHP\Sortie\ProductionElectriciteCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class ProductionElectriciteCalculatorTest extends TestCase
{
    private function context(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext($doc, new TableRepository(__DIR__ . '/../../../resources/tables'), zoneClimatique: '1');
    }

    public function testReprendLesPartsCalculeesAvantDeductionSansAttribuerDePvAuGaz(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement><caracteristique_generale><surface_habitable_logement>100</surface_habitable_logement></caracteristique_generale>'
            . '<production_elec_enr><donnee_entree><presence_production_pv>1</presence_production_pv></donnee_entree><panneaux_pv_collection><panneaux_pv><surface_totale_capteurs>10</surface_totale_capteurs><enum_orientation_pv_id>3</enum_orientation_pv_id><enum_inclinaison_pv_id>2</enum_inclinaison_pv_id></panneaux_pv></panneaux_pv_collection></production_elec_enr>'
            . '<installation_chauffage_collection><installation_chauffage><donnee_intermediaire><conso_ch>5000</conso_ch></donnee_intermediaire><generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_energie_id>2</enum_type_energie_id></donnee_entree><donnee_intermediaire><conso_ch>5000</conso_ch></donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>'
            . '<installation_ecs_collection><installation_ecs><donnee_intermediaire><conso_ecs>300</conso_ecs></donnee_intermediaire><generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_energie_id>1</enum_type_energie_id></donnee_entree><donnee_intermediaire><conso_ecs>300</conso_ecs></donnee_intermediaire></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>'
            . '<sortie><ef_conso><conso_ch>5000</conso_ch><conso_ecs>300</conso_ecs><conso_eclairage>200</conso_eclairage><conso_totale_auxiliaire>100</conso_totale_auxiliaire><conso_auxiliaire_ventilation>100</conso_auxiliaire_ventilation><conso_5_usages>5600</conso_5_usages></ef_conso></sortie></logement>');
        $ctx = $this->context($doc);
        (new ProductionPvCalculator())->calculate($doc->documentElement, $ctx);
        $ac = (float)$ctx->get('pv.conso_elec_ac');
        $parts = ['ch' => 0.0, 'ecs' => 15 / 1380, 'fr' => 0.0, 'eclairage' => 10 / 1380, 'auxiliaire' => 50 / 1380, 'autre_usage' => 1305 / 1380];
        foreach ([1, 2] as $passage) {
            (new ProductionElectriciteCalculator())->calculate($doc->documentElement, $ctx);
            $bloc = $doc->getElementsByTagName('production_electricite')->item(0);
            self::assertSame(1, $doc->getElementsByTagName('production_electricite')->length);
            $somme = 0.0;
            foreach ($parts as $usage => $part) {
                $valeur = (float)$bloc->getElementsByTagName('conso_elec_ac_' . $usage)->item(0)->textContent;
                self::assertEqualsWithDelta($ac * $part, $valeur, 1e-8, $usage);
                $somme += $valeur;
            }
            self::assertEqualsWithDelta($ac, $somme, 1e-8);
        }
    }

    public function testSansPvNeProduitAucuneAutoconsommation(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement/>');
        (new ProductionElectriciteCalculator())->calculate($doc->documentElement, $this->context($doc));
        foreach ($doc->getElementsByTagName('production_electricite')->item(0)->childNodes as $child) {
            self::assertSame(0.0, (float)$child->textContent);
        }
    }

    public function testChaquePosteDuCanalInterneEstProjeteSansNouvellePonderation(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement><production_elec_enr><donnee_intermediaire><production_pv>1000</production_pv><conso_elec_ac>210</conso_elec_ac></donnee_intermediaire></production_elec_enr></logement>');
        $ctx = $this->context($doc);
        $parts = ['ch' => 10.0, 'ecs' => 20.0, 'fr' => 30.0, 'eclairage' => 40.0, 'auxiliaire' => 50.0, 'autre_usage' => 60.0];
        $ctx->set('pv.autoconsommation', $parts);
        (new ProductionElectriciteCalculator())->calculate($doc->documentElement, $ctx);
        $bloc = $doc->getElementsByTagName('production_electricite')->item(0);
        foreach ($parts as $usage => $attendu) {
            self::assertSame($attendu, (float)$bloc->getElementsByTagName('conso_elec_ac_' . $usage)->item(0)->textContent);
        }
        self::assertSame(210.0, (float)$bloc->getElementsByTagName('conso_elec_ac')->item(0)->textContent);
        self::assertSame(1000.0, (float)$bloc->getElementsByTagName('production_pv')->item(0)->textContent);
    }
}
