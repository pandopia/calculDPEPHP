<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Sortie\SortieParEnergieAggregator;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class SortieParEnergieAggregatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 1e-3;

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    private function buildDoc(int $energieChId, int $energieEcsId, float $consoChGen, float $consoEcsGen, float $consoEcl, float $consoAux): DOMDocument
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
                    <donnee_intermediaire>
                        <conso_ch>{$consoChGen}</conso_ch>
                        <conso_ch_depensier>0</conso_ch_depensier>
                    </donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection>
        <installation_ecs>
            <generateur_ecs_collection>
                <generateur_ecs>
                    <donnee_entree>
                        <enum_type_energie_id>{$energieEcsId}</enum_type_energie_id>
                    </donnee_entree>
                    <donnee_intermediaire>
                        <conso_ecs>{$consoEcsGen}</conso_ecs>
                        <conso_ecs_depensier>0</conso_ecs_depensier>
                    </donnee_intermediaire>
                </generateur_ecs>
            </generateur_ecs_collection>
        </installation_ecs>
    </installation_ecs_collection>
    <sortie>
        <ef_conso>
            <conso_eclairage>{$consoEcl}</conso_eclairage>
            <conso_totale_auxiliaire>{$consoAux}</conso_totale_auxiliaire>
            <conso_fr>0</conso_fr>
            <conso_fr_depensier>0</conso_fr_depensier>
        </ef_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc;
    }

    private function getValues(DOMDocument $doc, string $xpath): array
    {
        $xp = new DOMXPath($doc);
        $nodes = $xp->query($xpath);
        if ($nodes === false) {
            return [];
        }
        $result = [];
        foreach ($nodes as $node) {
            $result[] = (float)$node->textContent;
        }
        return $result;
    }

    /**
     * Gas heating+ecs : electricity row for eclairage+aux, gas row for ch+ecs.
     */
    public function testGazChauffageElecEclairage(): void
    {
        $doc = $this->buildDoc(2, 2, 10000.0, 5000.0, 200.0, 300.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new SortieParEnergieAggregator())->calculate($logement, $this->makeContext($doc));

        $energieIds = $this->getValues($doc, '//sortie_par_energie/enum_type_energie_id');
        $this->assertContains(1.0, $energieIds, 'Electricity always present');
        $this->assertContains(2.0, $energieIds, 'Gas present when generators use gas');

        // Gas row: conso_ch = 10000, conso_ecs = 5000, conso_5 = 15000
        $gasConso5 = $this->getValues($doc, '//sortie_par_energie[enum_type_energie_id=2]/conso_5_usages');
        $this->assertEqualsWithDelta(15000.0, $gasConso5[0], self::TOL, 'gas conso_5 = ch + ecs');

        // Electricity row: conso_5 = eclairage + aux = 500
        $elecConso5 = $this->getValues($doc, '//sortie_par_energie[enum_type_energie_id=1]/conso_5_usages');
        $this->assertEqualsWithDelta(500.0, $elecConso5[0], self::TOL, 'elec conso_5 = ecl + aux');
    }

    /**
     * Gas GES : 10000 kWh * 0.227 = 2270 kgCO2e
     */
    public function testGasGes(): void
    {
        $doc = $this->buildDoc(2, 2, 10000.0, 5000.0, 0.0, 0.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new SortieParEnergieAggregator())->calculate($logement, $this->makeContext($doc));

        $gesChValues = $this->getValues($doc, '//sortie_par_energie[enum_type_energie_id=2]/emission_ges_ch');
        $this->assertEqualsWithDelta(10000.0 * 0.227, $gesChValues[0], self::TOL, 'gas GES ch');
    }

    /**
     * Electricity GES for eclairage (0.069) and aux (0.064).
     */
    public function testElecGes(): void
    {
        $doc = $this->buildDoc(2, 2, 0.0, 0.0, 100.0, 200.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new SortieParEnergieAggregator())->calculate($logement, $this->makeContext($doc));

        $ges5Values = $this->getValues($doc, '//sortie_par_energie[enum_type_energie_id=1]/emission_ges_5_usages');
        $expected = 100.0 * 0.069 + 200.0 * 0.064; // eclairage + aux
        $this->assertEqualsWithDelta($expected, $ges5Values[0], self::TOL, 'elec GES 5 usages');
    }

    /**
     * All-electric: single electricity row contains all consumption.
     */
    public function testAllElectricity(): void
    {
        $doc = $this->buildDoc(1, 1, 5000.0, 2000.0, 300.0, 400.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new SortieParEnergieAggregator())->calculate($logement, $this->makeContext($doc));

        $energieIds = $this->getValues($doc, '//sortie_par_energie/enum_type_energie_id');
        $this->assertCount(1, $energieIds, 'Only electricity row');

        $conso5 = $this->getValues($doc, '//sortie_par_energie[enum_type_energie_id=1]/conso_5_usages');
        $this->assertEqualsWithDelta(5000.0 + 2000.0 + 300.0 + 400.0, $conso5[0], self::TOL, 'elec all-in conso_5');
    }

    /**
     * Fioul — fixed tarif 0.09142 €/kWh.
     */
    public function testFioulCout(): void
    {
        $doc = $this->buildDoc(3, 3, 4000.0, 2000.0, 100.0, 50.0);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new SortieParEnergieAggregator())->calculate($logement, $this->makeContext($doc));

        $coutCh = $this->getValues($doc, '//sortie_par_energie[enum_type_energie_id=3]/cout_ch');
        $this->assertEqualsWithDelta(0.09142 * 4000.0, $coutCh[0], self::TOL, 'fioul cout_ch');
    }
}
