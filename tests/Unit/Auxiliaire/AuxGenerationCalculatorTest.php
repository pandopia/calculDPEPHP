<?php

declare(strict_types=1);

namespace Tests\Unit\Auxiliaire;

use CalculDpePHP\Auxiliaire\AuxGenerationCalculator;
use CalculDpePHP\Common\Period;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class AuxGenerationCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function buildDoc(string $xml): array
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            period: Period::POST_2026,
        );
        return [$doc, $doc->documentElement, $ctx];
    }

    private function efValue(DOMDocument $doc, string $tag): float
    {
        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query("//ef_conso/$tag");
        $this->assertNotNull($nodes);
        $this->assertGreaterThan(0, $nodes->length, "Missing tag: $tag");
        return (float)$nodes->item(0)->textContent;
    }

    private function gasCombinedXml(
        float $besoinCh,
        float $besoinChDep,
        float $besoinEcs,
        float $besoinEcsDep,
        float $pn = 40000.0,
        ?float $cleRepCh = null,
        ?float $cleRepEcs = null,
    ): string {
        $cleChAttr  = $cleRepCh  !== null ? "<cle_repartition_ch>$cleRepCh</cle_repartition_ch>"   : '';
        $cleEcsAttr = $cleRepEcs !== null ? "<cle_repartition_ecs>$cleRepEcs</cle_repartition_ecs>" : '';
        $shLogement = $cleRepCh  !== null ? '<surface_habitable_logement>62.86</surface_habitable_logement>' : '';
        return <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>1034.74</surface_habitable_immeuble>
        $shLogement
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                $cleChAttr
            </donnee_entree>
            <donnee_intermediaire>
                <besoin_ch>$besoinCh</besoin_ch>
                <besoin_ch_depensier>$besoinChDep</besoin_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>2</enum_type_energie_id>
                        <tv_generateur_combustion_id>13</tv_generateur_combustion_id>
                    </donnee_entree>
                    <donnee_intermediaire>
                        <pn>$pn</pn>
                    </donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection>
        <installation_ecs>
            <donnee_entree>
                $cleEcsAttr
            </donnee_entree>
            <donnee_intermediaire>
                <besoin_ecs>$besoinEcs</besoin_ecs>
                <besoin_ecs_depensier>$besoinEcsDep</besoin_ecs_depensier>
            </donnee_intermediaire>
            <generateur_ecs>
                <donnee_entree>
                    <enum_type_energie_id>2</enum_type_energie_id>
                    <tv_generateur_combustion_id>13</tv_generateur_combustion_id>
                </donnee_entree>
                <donnee_intermediaire>
                    <pn>$pn</pn>
                </donnee_intermediaire>
            </generateur_ecs>
        </installation_ecs>
    </installation_ecs_collection>
    <sortie/>
</logement>
XML;
    }

    public function testAppliesToLogement(): void
    {
        $calc = new AuxGenerationCalculator();
        $doc  = new DOMDocument();
        $doc->loadXML('<logement/>');
        $this->assertTrue($calc->appliesTo($doc->documentElement));

        $doc2 = new DOMDocument();
        $doc2->loadXML('<installation_chauffage/>');
        $this->assertFalse($calc->appliesTo($doc2->documentElement));
    }

    public function testGasBoilerBatMode(): void
    {
        // bat_post2026 profile: Paux=84W, Pn=40kW, besoin_ch=21632.74, besoin_ecs=22349.06
        [$doc, $logement, $ctx] = $this->buildDoc(
            $this->gasCombinedXml(21632.7364635101, 28838.2832215158, 22349.0573350388, 31528.1344547869)
        );
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(45.4287, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
        $this->assertEqualsWithDelta(105.989, $this->efValue($doc, 'conso_auxiliaire_generation_ch_depensier'), 0.01);
        $this->assertEqualsWithDelta(46.933,  $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 0.001);
        $this->assertEqualsWithDelta(66.209,  $this->efValue($doc, 'conso_auxiliaire_generation_ecs_depensier'), 0.01);
    }

    public function testGasBoilerZoneMode(): void
    {
        // zone_post2026 profile: same Pn/Paux, cle_repartition scaling
        $cleRepCh  = 0.060749560276011361;
        $cleRepEcs = 0.056226656335245144;
        [$doc, $logement, $ctx] = $this->buildDoc(
            $this->gasCombinedXml(
                21632.7364635101, 28838.2832215158,
                22349.0573350388, 31528.1344547869,
                40000.0,
                $cleRepCh, $cleRepEcs
            )
        );
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(2.7598, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
        $this->assertEqualsWithDelta(6.4388, $this->efValue($doc, 'conso_auxiliaire_generation_ch_depensier'), 0.001);
        $this->assertEqualsWithDelta(2.6389, $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 0.001);
        $this->assertEqualsWithDelta(3.7227, $this->efValue($doc, 'conso_auxiliaire_generation_ecs_depensier'), 0.001);
    }

    public function testElectricGeneratorIsZero(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>500.0</surface_habitable_immeuble>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree/>
            <donnee_intermediaire>
                <besoin_ch>10000.0</besoin_ch>
                <besoin_ch_depensier>15000.0</besoin_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>1</enum_type_energie_id>
                    </donnee_entree>
                    <donnee_intermediaire>
                        <pn>10000</pn>
                    </donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
    <sortie/>
</logement>
XML;
        [$doc, $logement, $ctx] = $this->buildDoc($xml);
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ch_depensier'), 1e-9);
    }

    public function testPnCapAt400kWForGas(): void
    {
        // Pn = 600kW → capped at 400kW for gas boiler
        // Paux = 20 + 1.6 × 400 = 660 W
        // Q_aux_ch = 660 × 10000 / 600000 = 11.0 kWh
        [$doc, $logement, $ctx] = $this->buildDoc(
            $this->gasCombinedXml(10000.0, 15000.0, 8000.0, 12000.0, 600000.0)
        );
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $expectedPaux = 20.0 + 1.6 * 400.0; // 660 W (capped at 400kW)
        $expectedQch  = $expectedPaux * 10000.0 / 600000.0;
        $this->assertEqualsWithDelta($expectedQch, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
    }

    public function testNoPnSkipsGenerator(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>1000.0</surface_habitable_immeuble>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree/>
            <donnee_intermediaire>
                <besoin_ch>20000.0</besoin_ch>
                <besoin_ch_depensier>25000.0</besoin_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>2</enum_type_energie_id>
                        <tv_generateur_combustion_id>13</tv_generateur_combustion_id>
                    </donnee_entree>
                    <donnee_intermediaire/>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
    <sortie/>
</logement>
XML;
        [$doc, $logement, $ctx] = $this->buildDoc($xml);
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 1e-9);
    }

    public function testNoInstallationReturnsZero(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>500.0</surface_habitable_immeuble>
    </caracteristique_generale>
    <installation_chauffage_collection/>
    <installation_ecs_collection/>
    <sortie/>
</logement>
XML;
        [$doc, $logement, $ctx] = $this->buildDoc($xml);
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 1e-9);
    }
}
