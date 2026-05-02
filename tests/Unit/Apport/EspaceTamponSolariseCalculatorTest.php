<?php

declare(strict_types=1);

namespace Tests\Unit\Apport;

use CalculDpe\Apport\EspaceTamponSolariseCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class EspaceTamponSolariseCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 0.001;

    private function makeContext(DOMDocument $doc, ?string $zoneId = '1'): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: $zoneId,
        );
    }

    private function buildDoc(int $transparenceId, int $cfgId, array $baiesEts = []): DOMDocument
    {
        $baiesXml = '';
        foreach ($baiesEts as [$surface, $orient, $incl]) {
            $baiesXml .= <<<XML
        <baie_ets>
          <donnee_entree>
            <surface_totale_baie>{$surface}</surface_totale_baie>
            <enum_orientation_id>{$orient}</enum_orientation_id>
            <enum_inclinaison_vitrage_id>{$incl}</enum_inclinaison_vitrage_id>
          </donnee_entree>
        </baie_ets>
XML;
        }

        $xml = <<<XML
<?xml version="1.0"?>
<ets>
  <donnee_entree>
    <tv_coef_transparence_ets_id>{$transparenceId}</tv_coef_transparence_ets_id>
    <enum_cfg_isolation_lnc_id>{$cfgId}</enum_cfg_isolation_lnc_id>
  </donnee_entree>
  <baie_ets_collection>
{$baiesXml}
  </baie_ets_collection>
</ets>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc;
    }

    /**
     * id=1 → Bois simple vitrage → T = 0.62
     */
    public function testCoefficientTransparenceBoisSimple(): void
    {
        $doc = $this->buildDoc(1, 7); // cfg 7 = lc isolé + ETS sud
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, '1');
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $T = (float)$doc->getElementsByTagName('coef_transparence_ets')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.62, $T, self::TOL);
    }

    /**
     * id=10 → PVC triple vitrage peu émissif → T = 0.36
     */
    public function testCoefficientTransparencePvcTriplePE(): void
    {
        $doc = $this->buildDoc(10, 7);
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, '1');
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $T = (float)$doc->getElementsByTagName('coef_transparence_ets')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.36, $T, self::TOL);
    }

    /**
     * id=21 → Polycarbonate → T = 0.40
     */
    public function testCoefficientTransparencePolycarbonate(): void
    {
        $doc = $this->buildDoc(21, 7);
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, '1');
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $T = (float)$doc->getElementsByTagName('coef_transparence_ets')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.40, $T, self::TOL);
    }

    /**
     * cfg 7 = lc isolé + ETS sud → bver H1a = 0.58
     */
    public function testBverIsoleSud_H1a(): void
    {
        $doc = $this->buildDoc(1, 7);
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, '1'); // H1a
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $bver = (float)$doc->getElementsByTagName('bver')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.58, $bver, self::TOL);
    }

    /**
     * cfg 6 = lc isolé + ETS nord → bver H1 = 0.95
     */
    public function testBverIsoleNord_H1(): void
    {
        $doc = $this->buildDoc(1, 6);
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, '2'); // H1b → groupe H1
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $bver = (float)$doc->getElementsByTagName('bver')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.95, $bver, self::TOL);
    }

    /**
     * cfg 9 = lc non isolé + ETS nord → bver H2 = 0.85
     */
    public function testBverNonIsoleNord_H2(): void
    {
        $doc = $this->buildDoc(1, 9);
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, '4'); // H2a → groupe H2
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $bver = (float)$doc->getElementsByTagName('bver')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.85, $bver, self::TOL);
    }

    /**
     * cfg 11 = lc non isolé + ETS est/ouest → bver H3 = 0.53
     */
    public function testBverNonIsoleEstOuest_H3(): void
    {
        $doc = $this->buildDoc(1, 11);
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, '8'); // H3
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $bver = (float)$doc->getElementsByTagName('bver')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.53, $bver, self::TOL);
    }

    /**
     * Sans baies ETS → sse_ets_mensuel doit être 0 pour tous les mois
     */
    public function testNoBaiesEts_SseZero(): void
    {
        $doc = $this->buildDoc(1, 7, []);
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, '1');
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $mensuel = $ctx->get('apport.sse_ets_mensuel');
        $this->assertNotNull($mensuel);
        $this->assertEqualsWithDelta(0.0, array_sum($mensuel), self::TOL);
    }

    /**
     * Baie ETS sud sup75°, 10m², T=0.62 (id=1), bver=0.58 (cfg 7, H1)
     * sseFact = 0.8*0.62 + 0.024 = 0.52; C1 sud sup75 H1a jan = 1.0
     * Sst jan = 10 * 0.52 * 1.0 = 5.2 ; Sse_veranda jan = 5.2 * 0.58 = 3.016
     */
    public function testSseMensuelAvecBaieEts(): void
    {
        $doc = $this->buildDoc(1, 7, [[10.0, 1, 3]]); // sud, sup75°
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, '1'); // H1a
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $mensuel = $ctx->get('apport.sse_ets_mensuel');
        $this->assertIsArray($mensuel);
        $this->assertCount(12, $mensuel);
        $sseFact = 0.8 * 0.62 + 0.024; // 0.52
        $expectedJan = 10.0 * $sseFact * 1.0 * 0.58;
        $this->assertEqualsWithDelta($expectedJan, $mensuel[1], self::TOL);
    }

    /**
     * Zone inconnue → bver = 1.0 (fallback)
     */
    public function testNoZone_BverDefault(): void
    {
        $doc = $this->buildDoc(2, 7);
        $ets = $doc->getElementsByTagName('ets')->item(0);
        $ctx = $this->makeContext($doc, null);
        (new EspaceTamponSolariseCalculator())->calculate($ets, $ctx);

        $bver = (float)$doc->getElementsByTagName('bver')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.0, $bver, self::TOL);
    }
}
