<?php

declare(strict_types=1);

namespace Tests\Unit\Apport;

use CalculDpePHP\Apport\SurfaceSudEquivalenteCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class SurfaceSudEquivalenteCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 0.001;

    private function makeContext(DOMDocument $doc, ?string $zone = '1'): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: $zone,
        );
    }

    /**
     * Vérifie que le résultat correspond exactement au verif bat_post2026 = 474.517248.
     *
     * Baies du fichier (orientations extraites du verif XML) :
     *   - 10.45 m², Est (3), ≥75° (3), sw=0.46, fe1=1, fe2=1
     *   - 31.87 m², Est (3), ≥75° (3), sw=0.46, fe1=1, fe2=1
     *   - 31.87 m², Sud (1), ≥75° (3), sw=0.46, fe1=1, fe2=1
     *   - 9.60  m², Est (3), ≥75° (3), sw=0.44, fe1=1, fe2=1
     *   - 40.00 m², Nord (2), ≥75° (3), sw=0.44, fe1=1, fe2=1
     * Zone H1a (zone_id=1)
     */
    public function testBatPost2026Sse(): void
    {
        $baies = [
            [10.45, 3, 3, 0.46, 1.0, 1.0],
            [31.87, 3, 3, 0.46, 1.0, 1.0],
            [31.87, 1, 3, 0.46, 1.0, 1.0],
            [9.60,  3, 3, 0.44, 1.0, 1.0],
            [40.00, 2, 3, 0.44, 1.0, 1.0],
        ];
        $doc = $this->buildDoc($baies);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, '1');
        (new SurfaceSudEquivalenteCalculator())->calculate($logement, $ctx);

        $val = (float)$ctx->get('apport.sse_annuel');
        $this->assertEqualsWithDelta(474.517248, $val, 0.01);
    }

    public function testSseStoresMonthlyInContext(): void
    {
        $baies = [[10.0, 1, 3, 0.5, 1.0, 1.0]]; // Sud ≥75° → C1=1.0 tous mois → 10*0.5*12=60
        $doc = $this->buildDoc($baies);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, '1');
        (new SurfaceSudEquivalenteCalculator())->calculate($logement, $ctx);

        $mensuel = $ctx->get('apport.sse_mensuel');
        $this->assertIsArray($mensuel);
        $this->assertCount(12, $mensuel);
        $this->assertEqualsWithDelta(5.0, $mensuel[1], self::TOL); // Sud ≥75° C1=1.00 → 10*0.5*1.0=5
        $total = array_sum($mensuel);
        $this->assertEqualsWithDelta(60.0, $total, self::TOL);
    }

    public function testSkipsBaieWithoutSw(): void
    {
        $baies = [[10.0, 1, 3, null, 1.0, 1.0]];
        $doc = $this->buildDoc($baies);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, '1');
        (new SurfaceSudEquivalenteCalculator())->calculate($logement, $ctx);

        $val = (float)($ctx->get('apport.sse_annuel') ?? 0.0);
        $this->assertEqualsWithDelta(0.0, $val, self::TOL);
    }

    public function testHorizontalInclinaison(): void
    {
        // Horizontal (incl=4) : C1 horizontal H1a Jan = 0.62
        $baies = [[10.0, 1, 4, 0.5, 1.0, 1.0]];
        $doc = $this->buildDoc($baies);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, '1');
        (new SurfaceSudEquivalenteCalculator())->calculate($logement, $ctx);

        $mensuel = $ctx->get('apport.sse_mensuel');
        $this->assertEqualsWithDelta(10.0 * 0.5 * 0.62, $mensuel[1], self::TOL); // Jan horizontal H1a=0.62
    }

    public function testNoZone_ReturnsC1Equals1(): void
    {
        $baies = [[10.0, 1, 3, 0.5, 1.0, 1.0]];
        $doc = $this->buildDoc($baies);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, null); // zone inconnue
        (new SurfaceSudEquivalenteCalculator())->calculate($logement, $ctx);

        // C1=1.0 par défaut (zone inconnue) → sse = 10*0.5*1.0*12 = 60
        $val = (float)($ctx->get('apport.sse_annuel') ?? 0.0);
        $this->assertEqualsWithDelta(60.0, $val, self::TOL);
    }

    public function testFe1Fe2Applied(): void
    {
        // Sud ≥75° C1=1.0, sw=0.5, fe1=0.8, fe2=0.9 → base = 10*0.5*0.8*0.9 = 3.6 * 12 = 43.2
        $baies = [[10.0, 1, 3, 0.5, 0.8, 0.9]];
        $doc = $this->buildDoc($baies);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, '1');
        (new SurfaceSudEquivalenteCalculator())->calculate($logement, $ctx);

        $val = (float)($ctx->get('apport.sse_annuel') ?? 0.0);
        $this->assertEqualsWithDelta(43.2, $val, self::TOL);
    }

    /**
     * @param list<array{0: float, 1: int, 2: int, 3: float|null, 4: float, 5: float}> $baies
     */
    private function buildDoc(array $baies): DOMDocument
    {
        $baiesXml = '';
        foreach ($baies as $b) {
            [$surface, $orient, $incl, $sw, $fe1, $fe2] = $b;
            $swXml = $sw !== null ? "<sw>{$sw}</sw>" : '';
            $baiesXml .= <<<XML
    <baie_vitree>
      <donnee_entree>
        <surface_totale_baie>{$surface}</surface_totale_baie>
        <enum_orientation_id>{$orient}</enum_orientation_id>
        <enum_inclinaison_vitrage_id>{$incl}</enum_inclinaison_vitrage_id>
      </donnee_entree>
      <donnee_intermediaire>
        {$swXml}
        <fe1>{$fe1}</fe1>
        <fe2>{$fe2}</fe2>
      </donnee_intermediaire>
    </baie_vitree>
XML;
        }

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
  <enveloppe>
    <baie_vitree_collection>
{$baiesXml}
    </baie_vitree_collection>
  </enveloppe>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc;
    }
}
