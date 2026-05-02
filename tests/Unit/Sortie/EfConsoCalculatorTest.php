<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\EfConsoCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour EfConsoCalculator.
 *
 * Formule clé :
 *   BAT  : ef_conso.conso_ch = Σ(install.conso_ch × rdim)
 *   ZONE : ef_conso.conso_ch = Σ(install.conso_ch × rdim_effectif) × cle_repartition_ch
 */
final class EfConsoCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 1e-3;

    private function makeContext(DOMDocument $doc, array $contextVars = []): CalculationContext
    {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        foreach ($contextVars as $k => $v) {
            $ctx->set($k, $v);
        }
        return $ctx;
    }

    /**
     * Cas BAT : 1 installation avec rdim=12.7, conso_ch=3478.52.
     * ef_conso.conso_ch = 3478.52 × 12.7 = 44176.59
     */
    public function testBatSingleInstallation(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>500</surface_habitable_immeuble>
        <nombre_appartement>1</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id>
                <enum_type_installation_id>1</enum_type_installation_id>
                <rdim>12.7</rdim>
                <ratio_virtualisation>1</ratio_virtualisation>
            </donnee_entree>
            <donnee_intermediaire>
                <conso_ch>3478.52</conso_ch>
                <conso_ch_depensier>4535.69</conso_ch_depensier>
            </donnee_intermediaire>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc, [
            'eclairage.conso_eclairage' => 1000.0,
            'froid.conso_fr'            => 0.0,
        ]);

        (new EfConsoCalculator())->calculate($node, $ctx);

        $efConso = $doc->getElementsByTagName('ef_conso')->item(0);
        $consoChActual = (float)$efConso->getElementsByTagName('conso_ch')->item(0)->textContent;

        $this->assertEqualsWithDelta(3478.52 * 12.7, $consoChActual, 0.1, 'conso_ch BAT');
    }

    /**
     * Cas BAT : 10 installations identiques, rdim=12.7, conso_ch=3478.52.
     * ef_conso.conso_ch = 10 × 3478.52 × 12.7 = 441771.59
     */
    public function testBatTenInstallationsRdim127(): void
    {
        $installXml = str_repeat(<<<INSTALL
        <installation_chauffage>
            <donnee_entree>
                <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id>
                <enum_type_installation_id>1</enum_type_installation_id>
                <rdim>12.7</rdim>
                <ratio_virtualisation>1</ratio_virtualisation>
            </donnee_entree>
            <donnee_intermediaire>
                <conso_ch>3478.51644779203</conso_ch>
                <conso_ch_depensier>4535.69386669655</conso_ch_depensier>
            </donnee_intermediaire>
        </installation_chauffage>
INSTALL, 10);

        $ecsXml = str_repeat(<<<ECS
        <installation_ecs>
            <donnee_entree>
                <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id>
                <enum_type_installation_id>1</enum_type_installation_id>
                <rdim>12.7</rdim>
                <ratio_virtualisation>1</ratio_virtualisation>
                <nombre_logement>1</nombre_logement>
            </donnee_entree>
            <generateur_ecs_collection>
                <generateur_ecs>
                    <donnee_intermediaire>
                        <conso_ecs>1924.509</conso_ecs>
                        <conso_ecs_depensier>2500.81</conso_ecs_depensier>
                    </donnee_intermediaire>
                </generateur_ecs>
            </generateur_ecs_collection>
        </installation_ecs>
ECS, 10);

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>9543</surface_habitable_immeuble>
        <nombre_appartement>127</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>$installXml</installation_chauffage_collection>
    <installation_ecs_collection>$ecsXml</installation_ecs_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc, [
            'eclairage.conso_eclairage' => 18036.27,
            'froid.conso_fr'            => 0.0,
        ]);

        (new EfConsoCalculator())->calculate($node, $ctx);

        $efConso  = $doc->getElementsByTagName('ef_conso')->item(0);
        $consoChActual  = (float)$efConso->getElementsByTagName('conso_ch')->item(0)->textContent;
        $consoEcsActual = (float)$efConso->getElementsByTagName('conso_ecs')->item(0)->textContent;
        $consoEclActual = (float)$efConso->getElementsByTagName('conso_eclairage')->item(0)->textContent;
        $conso5mActual  = (int)$efConso->getElementsByTagName('conso_5_usages_m2')->item(0)->textContent;

        $expectedConsoChBuilding = 10 * 3478.51644779203 * 12.7;
        $expectedConsoEcsBuilding = 10 * 1924.509 * 12.7;

        $this->assertEqualsWithDelta($expectedConsoChBuilding,   $consoChActual,  1.0, 'conso_ch building BAT');
        $this->assertEqualsWithDelta($expectedConsoEcsBuilding,  $consoEcsActual, 1.0, 'conso_ecs building BAT');
        $this->assertEqualsWithDelta(18036.27,                   $consoEclActual, 0.1, 'conso_eclairage BAT');

        $conso5 = $consoChActual + $consoEcsActual + 18036.27;
        $this->assertSame((int)floor($conso5 / 9543), $conso5mActual, 'conso_5_usages_m2 floor BAT');
    }

    /**
     * Cas ZONE individuel : 10 installations, rdim=1, cle_repartition_ch=0.011169.
     * building_total = 10 × 3478.52 × 12.7 = 441771.59
     * ef_conso.conso_ch = 441771.59 × 0.011169 = ~4934.16
     */
    public function testZoneIndividuelCleRepartition(): void
    {
        $cle = 0.011169022961613994;
        $installXml = '';
        for ($i = 0; $i < 10; $i++) {
            $installXml .= <<<INSTALL
            <installation_chauffage>
                <donnee_entree>
                    <enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
                    <enum_type_installation_id>1</enum_type_installation_id>
                    <rdim>1</rdim>
                    <ratio_virtualisation>1</ratio_virtualisation>
                    <nombre_logement_echantillon>1</nombre_logement_echantillon>
                    <cle_repartition_ch>$cle</cle_repartition_ch>
                </donnee_entree>
                <donnee_intermediaire>
                    <conso_ch>3478.51644779203</conso_ch>
                    <conso_ch_depensier>4535.69386669655</conso_ch_depensier>
                </donnee_intermediaire>
            </installation_chauffage>
INSTALL;
        }

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>102</surface_habitable_logement>
        <surface_habitable_immeuble>9543</surface_habitable_immeuble>
        <nombre_appartement>127</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>$installXml</installation_chauffage_collection>
    <installation_ecs_collection/>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc, [
            'eclairage.conso_eclairage' => 192.78,
            'froid.conso_fr'            => 0.0,
        ]);

        (new EfConsoCalculator())->calculate($node, $ctx);

        $efConso  = $doc->getElementsByTagName('ef_conso')->item(0);
        $consoChActual = (float)$efConso->getElementsByTagName('conso_ch')->item(0)->textContent;

        // rdim_effectif = 127 × 1 / 10 = 12.7
        $buildingTotal = 10 * 3478.51644779203 * 12.7;
        $expected      = $buildingTotal * $cle;

        $this->assertEqualsWithDelta($expected, $consoChActual, 1.0, 'conso_ch ZONE individuel');
        $this->assertEqualsWithDelta(4934.15, $consoChActual, 5.0, 'conso_ch ZONE ~4934');
    }

    /**
     * Cas ZONE collectif : 1 installation, rdim=1, type=2, cle_repartition_ch=(62.86/1034.74).
     * building_total = 27615.05 × 1 = 27615.05
     * ef_conso.conso_ch = 27615.05 × (62.86/1034.74) = 1677.60
     */
    public function testZoneCollectifCleRepartition(): void
    {
        $cle = 62.86 / 1034.74;
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>62.86</surface_habitable_logement>
        <surface_habitable_immeuble>1034.74</surface_habitable_immeuble>
        <nombre_appartement>19</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                <enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
                <enum_type_installation_id>2</enum_type_installation_id>
                <rdim>1</rdim>
                <ratio_virtualisation>1</ratio_virtualisation>
                <cle_repartition_ch>$cle</cle_repartition_ch>
            </donnee_entree>
            <donnee_intermediaire>
                <conso_ch>27615.0475180499</conso_ch>
                <conso_ch_depensier>36927.1997513452</conso_ch_depensier>
            </donnee_intermediaire>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc, [
            'eclairage.conso_eclairage' => 118.8054,
            'froid.conso_fr'            => 0.0,
        ]);

        (new EfConsoCalculator())->calculate($node, $ctx);

        $efConso  = $doc->getElementsByTagName('ef_conso')->item(0);
        $consoChActual = (float)$efConso->getElementsByTagName('conso_ch')->item(0)->textContent;

        $this->assertEqualsWithDelta(1677.60, $consoChActual, 1.0, 'conso_ch ZONE collectif');
    }

    /**
     * conso_5_usages_m2 utilise floor() (pas round()).
     *
     * 4580.15 / 62.86 = 72.87 → floor = 72, round = 73 → doit être 72.
     */
    public function testConso5UsagesM2UsesFloor(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>62.86</surface_habitable_logement>
        <surface_habitable_immeuble>1034.74</surface_habitable_immeuble>
        <nombre_appartement>19</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection/>
    <installation_ecs_collection/>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc, [
            'eclairage.conso_eclairage' => 4580.15,  // simulate total via eclairage
            'froid.conso_fr'            => 0.0,
        ]);

        (new EfConsoCalculator())->calculate($node, $ctx);

        $efConso = $doc->getElementsByTagName('ef_conso')->item(0);
        $m2 = (int)$efConso->getElementsByTagName('conso_5_usages_m2')->item(0)->textContent;

        $this->assertSame(72, $m2, 'floor of 72.87 = 72');
    }

    /**
     * Auxiliaires ventilation déjà dans le DOM sont inclus dans conso_totale_auxiliaire.
     */
    public function testVentilationAuxIncludedInTotal(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>1000</surface_habitable_immeuble>
        <nombre_appartement>10</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection/>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            <conso_auxiliaire_ventilation>500.0</conso_auxiliaire_ventilation>
        </ef_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc, [
            'eclairage.conso_eclairage' => 0.0,
            'froid.conso_fr'            => 0.0,
        ]);

        (new EfConsoCalculator())->calculate($node, $ctx);

        $efConso = $doc->getElementsByTagName('ef_conso')->item(0);
        $cauxTotal = (float)$efConso->getElementsByTagName('conso_totale_auxiliaire')->item(0)->textContent;
        $cauxVent  = (float)$efConso->getElementsByTagName('conso_auxiliaire_ventilation')->item(0)->textContent;

        $this->assertEqualsWithDelta(500.0, $cauxVent,  0.001, 'conso_auxiliaire_ventilation preserved');
        $this->assertEqualsWithDelta(500.0, $cauxTotal, 0.001, 'conso_totale_auxiliaire includes ventilation');
    }

    public function testAppliesToLogement(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement><meteo/></logement>');
        $logNode  = $doc->getElementsByTagName('logement')->item(0);
        $metaNode = $doc->getElementsByTagName('meteo')->item(0);

        $calc = new EfConsoCalculator();
        $this->assertTrue($calc->appliesTo($logNode));
        $this->assertFalse($calc->appliesTo($metaNode));
    }
}
