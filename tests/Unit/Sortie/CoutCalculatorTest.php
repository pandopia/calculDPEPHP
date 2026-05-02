<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Sortie\CoutCalculator;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class CoutCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 1e-4;

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    private function buildDoc(int $energieChId, float $consoChEf, float $consoEcsEf, float $consoEcl, float $consoAuxVent): DOMDocument
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_chauffage_collection>
        <installation_chauffage>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>{$energieChId}</enum_type_energie_id>
                    </donnee_entree>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection>
        <installation_ecs>
            <generateur_ecs_collection>
                <generateur_ecs>
                    <donnee_entree>
                        <enum_type_energie_id>{$energieChId}</enum_type_energie_id>
                    </donnee_entree>
                </generateur_ecs>
            </generateur_ecs_collection>
        </installation_ecs>
    </installation_ecs_collection>
    <sortie>
        <ef_conso>
            <conso_ch>{$consoChEf}</conso_ch>
            <conso_ch_depensier>0</conso_ch_depensier>
            <conso_ecs>{$consoEcsEf}</conso_ecs>
            <conso_ecs_depensier>0</conso_ecs_depensier>
            <conso_eclairage>{$consoEcl}</conso_eclairage>
            <conso_fr>0</conso_fr>
            <conso_fr_depensier>0</conso_fr_depensier>
            <conso_auxiliaire_generation_ch>0</conso_auxiliaire_generation_ch>
            <conso_auxiliaire_generation_ch_depensier>0</conso_auxiliaire_generation_ch_depensier>
            <conso_auxiliaire_distribution_ch>0</conso_auxiliaire_distribution_ch>
            <conso_auxiliaire_generation_ecs>0</conso_auxiliaire_generation_ecs>
            <conso_auxiliaire_generation_ecs_depensier>0</conso_auxiliaire_generation_ecs_depensier>
            <conso_auxiliaire_distribution_ecs>0</conso_auxiliaire_distribution_ecs>
            <conso_auxiliaire_ventilation>{$consoAuxVent}</conso_auxiliaire_ventilation>
        </ef_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc;
    }

    private function getLeaf(DOMDocument $doc, string $xpath): float
    {
        $xp = new DOMXPath($doc);
        $nodes = $xp->query($xpath);
        if ($nodes === false || $nodes->length === 0) {
            $this->fail("XPath not found: $xpath");
        }
        return (float)$nodes->item(0)->textContent;
    }

    /**
     * Gaz naturel — conso_ch=10000 (tranche 5009-50055) : 230 + 0.06533×10000 = 883.3
     */
    public function testGazNaturelMediumConso(): void
    {
        $doc = $this->buildDoc(2, 10000.0, 5000.0, 200.0, 100.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new CoutCalculator())->calculate($logement, $this->makeContext($doc));

        $coutCh = $this->getLeaf($doc, '//sortie/cout/cout_ch');
        $expected = 230.0 + 0.06533 * 10000.0;
        $this->assertEqualsWithDelta($expected, $coutCh, self::TOL * $expected, 'cout_ch gaz medium');
    }

    /**
     * Gaz naturel — petite conso (<5009) : 0.11121×conso
     */
    public function testGazNaturelSmallConso(): void
    {
        $doc = $this->buildDoc(2, 1000.0, 500.0, 100.0, 50.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new CoutCalculator())->calculate($logement, $this->makeContext($doc));

        $coutCh = $this->getLeaf($doc, '//sortie/cout/cout_ch');
        $this->assertEqualsWithDelta(0.11121 * 1000.0, $coutCh, self::TOL * 111.21, 'cout_ch gaz small');
    }

    /**
     * Gaz naturel — grande conso (>50055) : 415 + 0.06164×conso
     */
    public function testGazNaturelLargeConso(): void
    {
        $doc = $this->buildDoc(2, 60000.0, 0.0, 0.0, 0.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new CoutCalculator())->calculate($logement, $this->makeContext($doc));

        $coutCh = $this->getLeaf($doc, '//sortie/cout/cout_ch');
        $expected = 415.0 + 0.06164 * 60000.0;
        $this->assertEqualsWithDelta($expected, $coutCh, self::TOL * $expected, 'cout_ch gaz large');
    }

    /**
     * Électricité — tranche 5000-15000 : 94 + 0.15735×conso
     */
    public function testElectriciteMediumConso(): void
    {
        $doc = $this->buildDoc(1, 8000.0, 2000.0, 500.0, 300.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new CoutCalculator())->calculate($logement, $this->makeContext($doc));

        $coutCh = $this->getLeaf($doc, '//sortie/cout/cout_ch');
        $expected = 94.0 + 0.15735 * 8000.0;
        $this->assertEqualsWithDelta($expected, $coutCh, self::TOL * $expected, 'cout_ch elec medium');
    }

    /**
     * Électricité — très petite conso (<1000) : 0.29007×conso
     */
    public function testElectriciteSmallConso(): void
    {
        $doc = $this->buildDoc(1, 500.0, 0.0, 0.0, 0.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new CoutCalculator())->calculate($logement, $this->makeContext($doc));

        $coutCh = $this->getLeaf($doc, '//sortie/cout/cout_ch');
        $this->assertEqualsWithDelta(0.29007 * 500.0, $coutCh, self::TOL * 145.0, 'cout_ch elec small');
    }

    /**
     * Fioul — tarif fixe 0.09142 €/kWh
     */
    public function testFioul(): void
    {
        $doc = $this->buildDoc(3, 5000.0, 2000.0, 200.0, 100.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new CoutCalculator())->calculate($logement, $this->makeContext($doc));

        $coutCh = $this->getLeaf($doc, '//sortie/cout/cout_ch');
        $this->assertEqualsWithDelta(0.09142 * 5000.0, $coutCh, self::TOL * 500.0, 'cout_ch fioul');
    }

    /**
     * cout_total_auxiliaire = sum des auxiliaires individuels.
     */
    public function testTotalAuxiliaire(): void
    {
        $doc = $this->buildDoc(1, 1000.0, 500.0, 200.0, 300.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new CoutCalculator())->calculate($logement, $this->makeContext($doc));

        $total = $this->getLeaf($doc, '//sortie/cout/cout_total_auxiliaire');
        $vent  = $this->getLeaf($doc, '//sortie/cout/cout_auxiliaire_ventilation');
        // Only ventilation non-zero in this test (others are 0)
        $this->assertEqualsWithDelta($vent, $total, self::TOL, 'cout_total_auxiliaire = sum');
    }

    /**
     * cout_5_usages = ch + ecs + fr + total_aux + eclairage.
     */
    public function testCout5Usages(): void
    {
        $doc = $this->buildDoc(2, 10000.0, 5000.0, 200.0, 100.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new CoutCalculator())->calculate($logement, $this->makeContext($doc));

        $expected = $this->getLeaf($doc, '//sortie/cout/cout_ch')
                  + $this->getLeaf($doc, '//sortie/cout/cout_ecs')
                  + $this->getLeaf($doc, '//sortie/cout/cout_fr')
                  + $this->getLeaf($doc, '//sortie/cout/cout_total_auxiliaire')
                  + $this->getLeaf($doc, '//sortie/cout/cout_eclairage');
        $actual = $this->getLeaf($doc, '//sortie/cout/cout_5_usages');
        $this->assertEqualsWithDelta($expected, $actual, self::TOL, 'cout_5_usages');
    }
}
