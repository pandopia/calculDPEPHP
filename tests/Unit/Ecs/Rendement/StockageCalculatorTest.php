<?php

declare(strict_types=1);

namespace Tests\Unit\Ecs\Rendement;

use CalculDpePHP\Ecs\Rendement\StockageCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour StockageCalculator (§11.6 p.74-75).
 */
final class StockageCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 0.001;

    private function buildGen(
        float $vs,
        int $energieId = 1,
        int $genTypeId = 71,
        ?int $tvPertesId = 4,
        float $rd = 0.87,
        float $becsKwh = 1318.425,
    ): array {
        $tvPertesTag = $tvPertesId !== null ? "<tv_pertes_stockage_id>$tvPertesId</tv_pertes_stockage_id>" : '';
        $rdTag = "<rendement_distribution>$rd</rendement_distribution>";
        $becsTag = "<besoin_ecs>$becsKwh</besoin_ecs>";

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_ecs>
        <donnee_intermediaire>
            $rdTag
            $becsTag
        </donnee_intermediaire>
        <generateur_ecs_collection>
            <generateur_ecs>
                <donnee_entree>
                    <enum_type_energie_id>$energieId</enum_type_energie_id>
                    <enum_type_generateur_ecs_id>$genTypeId</enum_type_generateur_ecs_id>
                    $tvPertesTag
                    <volume_stockage>$vs</volume_stockage>
                </donnee_entree>
            </generateur_ecs>
        </generateur_ecs_collection>
    </installation_ecs>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $gen = $doc->getElementsByTagName('generateur_ecs')->item(0);
        return [$doc, $gen];
    }

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    /**
     * Vs = 0 → pas de stockage → Rs = 1.
     */
    public function testNoStorageRsEqualsOne(): void
    {
        [$doc, $node] = $this->buildGen(0.0, 1, 71);
        $ctx = $this->makeContext($doc);
        (new StockageCalculator())->calculate($node, $ctx);
        $rs = (float)$doc->getElementsByTagName('rendement_stockage')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.0, $rs, self::TOL);
    }

    /**
     * Cat C/3* vertical, VS=100, Cr=0.25 :
     *   Qg,w = 8592 × 45/24 × 100 × 0.25 = 402750
     *   Rs = 1.08 / (1 + 402750 × Rd / Becs_Wh)
     */
    public function testCatCVerticalVs100(): void
    {
        $rd = 0.87;
        $becsKwh = 1318.425;
        [$doc, $node] = $this->buildGen(100.0, 1, 71, 4, $rd, $becsKwh);
        $ctx = $this->makeContext($doc);
        (new StockageCalculator())->calculate($node, $ctx);

        $qgw = 8592.0 * 45.0 / 24.0 * 100.0 * 0.25;
        $expected = 1.08 / (1.0 + $qgw * $rd / ($becsKwh * 1000.0));

        $rs = (float)$doc->getElementsByTagName('rendement_stockage')->item(0)->textContent;
        $this->assertEqualsWithDelta($expected, $rs, self::TOL);
    }

    /**
     * Cat C/3* vertical, VS=200, Cr=0.20 (tv_pertes_stockage_id=8) :
     *   Qg,w = 8592 × 45/24 × 200 × 0.20
     */
    public function testCatCVerticalVs200Id8(): void
    {
        $rd = 0.87;
        $becsKwh = 1318.425;
        [$doc, $node] = $this->buildGen(200.0, 1, 71, 8, $rd, $becsKwh);
        $ctx = $this->makeContext($doc);
        (new StockageCalculator())->calculate($node, $ctx);

        $qgw = 8592.0 * 45.0 / 24.0 * 200.0 * 0.20;
        $expected = 1.08 / (1.0 + $qgw * $rd / ($becsKwh * 1000.0));

        $rs = (float)$doc->getElementsByTagName('rendement_stockage')->item(0)->textContent;
        $this->assertEqualsWithDelta($expected, $rs, self::TOL);
    }

    /**
     * Autres ballons électriques (pas cat C) → Rs = 1/(1+…), pas 1.08/(1+…).
     */
    public function testNonCatCElectricUsesFormulaOne(): void
    {
        $rd = 0.87;
        $becsKwh = 1318.425;
        // id=69 (vertical autres/inconnue) avec tv_pertes_id=2 (Cr=0.32)
        [$doc, $node] = $this->buildGen(100.0, 1, 69, 2, $rd, $becsKwh);
        $ctx = $this->makeContext($doc);
        (new StockageCalculator())->calculate($node, $ctx);

        $qgw = 8592.0 * 45.0 / 24.0 * 100.0 * 0.32;
        $expected = 1.0 / (1.0 + $qgw * $rd / ($becsKwh * 1000.0));

        $rs = (float)$doc->getElementsByTagName('rendement_stockage')->item(0)->textContent;
        $this->assertEqualsWithDelta($expected, $rs, self::TOL);
        $this->assertLessThan(1.08 / (1.0 + $qgw * $rd / ($becsKwh * 1000.0)), $rs);
    }

    /**
     * Ballon non-électrique avec stockage → Qg,w = 67662 × VS^0.55.
     */
    public function testNonElectricStorageFormula(): void
    {
        $rd = 0.87;
        $becsKwh = 1318.425;
        // energieId=2 (gaz), genTypeId=48 (chaudière gaz + stockage)
        [$doc, $node] = $this->buildGen(100.0, 2, 48, null, $rd, $becsKwh);
        $ctx = $this->makeContext($doc);
        (new StockageCalculator())->calculate($node, $ctx);

        $qgw = 67662.0 * (100.0 ** 0.55);
        $expected = 1.0 / (1.0 + $qgw * $rd / ($becsKwh * 1000.0));

        $rs = (float)$doc->getElementsByTagName('rendement_stockage')->item(0)->textContent;
        $this->assertEqualsWithDelta($expected, $rs, self::TOL);
    }

    /**
     * CET (chauffe-eau thermodynamique, id 1-12) → pas de Rs écrit (§14.2 traite séparément).
     */
    public function testCetNotHandled(): void
    {
        // id=3 = CET sur air ambiant après 2014
        [$doc, $node] = $this->buildGen(200.0, 1, 3, null);
        $ctx = $this->makeContext($doc);
        (new StockageCalculator())->calculate($node, $ctx);

        // Aucun rendement_stockage ne doit être écrit
        $rs = $doc->getElementsByTagName('rendement_stockage')->item(0);
        $this->assertNull($rs);
    }

    /**
     * Rs stocké dans le contexte.
     */
    public function testContextStoredCorrectly(): void
    {
        [$doc, $node] = $this->buildGen(100.0, 1, 71, 4);
        $ctx = $this->makeContext($doc);
        (new StockageCalculator())->calculate($node, $ctx);

        $rs = (float)$doc->getElementsByTagName('rendement_stockage')->item(0)->textContent;
        // Le contexte doit stocker au moins la valeur sous une clé ecs.rendement_stockage.*
        $keys = array_filter(
            array_keys($ctx->all()),
            fn($k) => str_starts_with($k, 'ecs.rendement_stockage.')
        );
        $this->assertNotEmpty($keys);
        foreach ($keys as $key) {
            $this->assertEqualsWithDelta($rs, (float)$ctx->get($key, 0.0), self::TOL);
        }
    }

    /**
     * Rs < 1 toujours quand stockage présent (pertes de chaleur).
     */
    public function testRsStrictlyLessThanOne(): void
    {
        [$doc, $node] = $this->buildGen(100.0, 1, 71, 4);
        $ctx = $this->makeContext($doc);
        (new StockageCalculator())->calculate($node, $ctx);

        $rs = (float)$doc->getElementsByTagName('rendement_stockage')->item(0)->textContent;
        $this->assertLessThan(1.0, $rs);
        $this->assertGreaterThan(0.0, $rs);
    }

    /**
     * Rs augmente si Becs augmente (moins de pertes relatives).
     */
    public function testLargerBecsGivesHigherRs(): void
    {
        [$doc1, $node1] = $this->buildGen(100.0, 1, 71, 4, 0.87, 1000.0);
        [$doc2, $node2] = $this->buildGen(100.0, 1, 71, 4, 0.87, 5000.0);
        $ctx1 = $this->makeContext($doc1);
        $ctx2 = $this->makeContext($doc2);
        (new StockageCalculator())->calculate($node1, $ctx1);
        (new StockageCalculator())->calculate($node2, $ctx2);

        $rs1 = (float)$doc1->getElementsByTagName('rendement_stockage')->item(0)->textContent;
        $rs2 = (float)$doc2->getElementsByTagName('rendement_stockage')->item(0)->textContent;
        $this->assertGreaterThan($rs1, $rs2);
    }
}
