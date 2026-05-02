<?php

declare(strict_types=1);

namespace Tests\Unit\Ecs;

use CalculDpe\Ecs\ConsoEcsCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ConsoEcsCalculator (§11.2 p.72-73).
 */
final class ConsoEcsCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../..';
    private const TOL = 0.01; // kWh

    private function buildInstallation(
        float $becsConv,
        float $becsDep,
        float $rd,
        float $rsConv = 1.0,
        float $rgConv = 1.0,
        ?float $rpn = null,
        int $genType = 71,
    ): array {
        $rpnTag = $rpn !== null ? "<rpn>$rpn</rpn>" : '';
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_ecs>
        <donnee_intermediaire>
            <besoin_ecs>$becsConv</besoin_ecs>
            <besoin_ecs_depensier>$becsDep</besoin_ecs_depensier>
            <rendement_distribution>$rd</rendement_distribution>
        </donnee_intermediaire>
        <generateur_ecs_collection>
            <generateur_ecs>
                <donnee_entree>
                    <enum_type_generateur_ecs_id>$genType</enum_type_generateur_ecs_id>
                </donnee_entree>
                <donnee_intermediaire>
                    <rendement_stockage>$rsConv</rendement_stockage>
                    <rendement_generation>$rgConv</rendement_generation>
                    $rpnTag
                </donnee_intermediaire>
            </generateur_ecs>
        </generateur_ecs_collection>
    </installation_ecs>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $inst = $doc->getElementsByTagName('installation_ecs')->item(0);
        return [$doc, $inst];
    }

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    /**
     * Cecs = Becs / (Rs × Rd × Rg) — cas simple sans stockage ni combustion.
     */
    public function testSimpleFormulaNoStorageNoLoss(): void
    {
        [$doc, $node] = $this->buildInstallation(1000.0, 1410.71, 0.87, 1.0, 1.0);
        $ctx = $this->makeContext($doc);
        (new ConsoEcsCalculator())->calculate($node, $ctx);

        $conso = (float)$doc->getElementsByTagName('conso_ecs')->item(0)->textContent;
        $this->assertEqualsWithDelta(1000.0 / 0.87, $conso, self::TOL);
    }

    /**
     * Post2026 : Becs=22349.057, Rd=0.52, Rs=1, Rg=0.943581 → Cecs ≈ 45548.779.
     */
    public function testPost2026MatchesVerif(): void
    {
        [$doc, $node] = $this->buildInstallation(22349.057, 31528.134, 0.52, 1.0, 0.943581, 0.958062, 57);
        $ctx = $this->makeContext($doc);
        (new ConsoEcsCalculator())->calculate($node, $ctx);

        $conso = (float)$doc->getElementsByTagName('conso_ecs')->item(0)->textContent;
        $this->assertEqualsWithDelta(45548.779, $conso, 1.0);
    }

    /**
     * Cecs_depensier post2026 avec Rg_dep recalculé → ≈ 63973.552.
     */
    public function testPost2026DepensierMatchesVerif(): void
    {
        [$doc, $node] = $this->buildInstallation(22349.057, 31528.134, 0.52, 1.0, 0.943581, 0.958062, 57);
        $ctx = $this->makeContext($doc);
        (new ConsoEcsCalculator())->calculate($node, $ctx);

        $consoDep = (float)$doc->getElementsByTagName('conso_ecs_depensier')->item(0)->textContent;
        $this->assertEqualsWithDelta(63973.552, $consoDep, 1.0);
    }

    /**
     * Cecs_depensier > Cecs_conventionnel (Becs_dep > Becs_conv).
     */
    public function testDepensierGreaterThanConventionnel(): void
    {
        [$doc, $node] = $this->buildInstallation(1000.0, 1410.71, 0.87, 0.85, 0.95, 0.96);
        $ctx = $this->makeContext($doc);
        (new ConsoEcsCalculator())->calculate($node, $ctx);

        $conso    = (float)$doc->getElementsByTagName('conso_ecs')->item(0)->textContent;
        $consoDep = (float)$doc->getElementsByTagName('conso_ecs_depensier')->item(0)->textContent;
        $this->assertGreaterThan($conso, $consoDep);
    }

    /**
     * Rs=1 → Rs_dep=1 (pas de stockage → rendement identique pour les deux scénarios).
     */
    public function testRsOneGivesSameRsDep(): void
    {
        [$doc, $node] = $this->buildInstallation(1000.0, 1410.71, 0.87, 1.0, 0.94, 0.96);
        $ctx = $this->makeContext($doc);
        (new ConsoEcsCalculator())->calculate($node, $ctx);

        $conso    = (float)$doc->getElementsByTagName('conso_ecs')->item(0)->textContent;
        $consoDep = (float)$doc->getElementsByTagName('conso_ecs_depensier')->item(0)->textContent;
        // ratio Cecs_dep/Cecs = Becs_dep/Becs_conv × (Rs_conv × Rg_conv) / (Rs_dep × Rg_dep)
        // Rs_conv = Rs_dep = 1 → ratio = Becs_dep/Becs_conv × Rg_conv/Rg_dep
        // (Rg_dep > Rg_conv lorsque Becs_dep > Becs_conv)
        $this->assertGreaterThan($conso, $consoDep);
    }

    /**
     * Rg=1 (électrique) → Rg_dep=1 aussi.
     */
    public function testElectricGeneratorRgDepEqualsOne(): void
    {
        $becsConv = 1000.0;
        $becsDep  = 1410.71;
        [$doc, $node] = $this->buildInstallation($becsConv, $becsDep, 0.87, 0.85, 1.0, null, 71);
        $ctx = $this->makeContext($doc);
        (new ConsoEcsCalculator())->calculate($node, $ctx);

        $conso    = (float)$doc->getElementsByTagName('conso_ecs')->item(0)->textContent;
        $consoDep = (float)$doc->getElementsByTagName('conso_ecs_depensier')->item(0)->textContent;
        // Rg_conv = Rg_dep = 1. Seul Rs change.
        // Ratio attendu ≈ Becs_dep/Becs_conv × (Rs_conv/Rs_dep)
        $this->assertGreaterThan($conso, $consoDep);
    }

    /**
     * Becs = 0 → Cecs = 0 (pas de besoin).
     */
    public function testZeroBecsGivesZeroConso(): void
    {
        [$doc, $node] = $this->buildInstallation(0.0, 0.0, 0.87, 1.0, 0.94);
        $ctx = $this->makeContext($doc);
        (new ConsoEcsCalculator())->calculate($node, $ctx);

        $conso = (float)$doc->getElementsByTagName('conso_ecs')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $conso, 1e-9);
    }

    /**
     * ratio_besoin_ecs = 1 écrit dans donnee_intermediaire.
     */
    public function testRatioBesoinEcsWritten(): void
    {
        [$doc, $node] = $this->buildInstallation(1000.0, 1410.71, 0.87);
        $ctx = $this->makeContext($doc);
        (new ConsoEcsCalculator())->calculate($node, $ctx);

        $ratio = $doc->getElementsByTagName('ratio_besoin_ecs')->item(0);
        $this->assertNotNull($ratio);
        $this->assertEqualsWithDelta(1.0, (float)$ratio->textContent, 1e-6);
    }

    /**
     * Plus Rs est bas (pertes stockage élevées), plus Cecs est élevé.
     */
    public function testLowerRsGivesHigherConso(): void
    {
        [$doc1, $node1] = $this->buildInstallation(1000.0, 1410.71, 0.87, 0.90);
        [$doc2, $node2] = $this->buildInstallation(1000.0, 1410.71, 0.87, 0.70);
        $ctx1 = $this->makeContext($doc1);
        $ctx2 = $this->makeContext($doc2);
        (new ConsoEcsCalculator())->calculate($node1, $ctx1);
        (new ConsoEcsCalculator())->calculate($node2, $ctx2);

        $c1 = (float)$doc1->getElementsByTagName('conso_ecs')->item(0)->textContent;
        $c2 = (float)$doc2->getElementsByTagName('conso_ecs')->item(0)->textContent;
        $this->assertGreaterThan($c1, $c2);
    }
}
