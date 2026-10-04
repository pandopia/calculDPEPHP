<?php

declare(strict_types=1);

namespace Tests\Unit\Froid;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Froid\ConsoFroidCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ConsoFroidCalculator (§10.3 p.69-70).
 */
final class ConsoFroidCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 0.01;

    private function buildLogementWithClimatisation(?int $seerIdent = null, ?float $eerDirect = null, ?float $surfaceRefroidie = null): array
    {
        $deXml = '<donnee_entree>';
        if ($seerIdent !== null) {
            $deXml .= "<tv_seer_id>$seerIdent</tv_seer_id>";
        }
        if ($eerDirect !== null) {
            $deXml .= "<eer>$eerDirect</eer>";
        }
        if ($surfaceRefroidie !== null) {
            $deXml .= "<surface_refroidie>$surfaceRefroidie</surface_refroidie>";
        }
        $deXml .= '</donnee_entree>';

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <climatisation_collection>
        <climatisation>$deXml</climatisation>
    </climatisation_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return [$doc, $doc->getElementsByTagName('logement')->item(0)];
    }

    private function buildLogementNoCooling(): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <climatisation_collection/>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return [$doc, $doc->getElementsByTagName('logement')->item(0)];
    }

    private function makeContext(DOMDocument $doc, array $extra = []): CalculationContext
    {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '1',
            classeAltitude: '1',
        );
        foreach ($extra as $k => $v) {
            $ctx->set($k, $v);
        }
        return $ctx;
    }

    /**
     * Besoin = 0 → conso = 0.
     */
    public function testZeroBesoinOutputsZeroConso(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(3);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 0.0,
            'froid.besoin_fr_depensier' => 0.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $conso = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $conso, self::TOL);
    }

    /**
     * EER direct saisi dans donnee_entree est utilisé en priorité.
     */
    public function testEerDirectPriorityOverSeer(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 4.0);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1200.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        // Cfr = 0.9 × 1000 / 4.0 = 225 kWh
        $conso = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta(225.0, $conso, self::TOL);
    }

    /**
     * tv_seer_id=3 (H1/H2, à partir 2015) : EER = 0.95 × 6.7 = 6.365.
     * Cfr = 0.9 × 1000 / 6.365 ≈ 141.4 kWh
     */
    public function testSeerTableId3(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(3);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1000.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $eer      = 0.95 * 6.7;
        $expected = 0.9 * 1000.0 / $eer;
        $conso    = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta($expected, $conso, 0.1);
    }

    /**
     * tv_seer_id=1 (H1/H2, avant 2008) : EER = 3.6 (direct, non × 0.95).
     */
    public function testSeerTableId1Avant2008(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(1);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1000.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $expected = 0.9 * 1000.0 / 3.6;  // EER direct = 3.6 p.69
        $conso    = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta($expected, $conso, 0.1);
    }

    /**
     * Seule une partie du logement est refroidie → rapport surfaceRefroidie/surfaceHabitable.
     */
    public function testPartialCoolingRatio(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 4.0, 50.0); // surface_refroidie = 50 sur 100
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1000.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        // Cfr_logement = 0.9 × 1000 / 4.0 × (50/100) = 225 × 0.5 = 112.5
        $conso = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta(112.5, $conso, self::TOL);
    }

    /**
     * Conso dépensier est ≥ conso conventionnel quand besoin dépensier ≥ besoin conventionnel.
     */
    public function testDepensierConsoGeqConventionnel(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(3);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1500.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $conso    = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $consoDep = (float)$doc->getElementsByTagName('conso_fr_depensier')->item(0)->textContent;
        $this->assertGreaterThanOrEqual($conso, $consoDep);
    }

    /**
     * Résultat stocké dans le contexte.
     */
    public function testContextStoredCorrectly(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 4.0);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1000.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $conso = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta($conso, (float)$ctx->get('froid.conso_fr', 0.0), self::TOL);
    }

    /**
     * Pas de climatisation → conso = 0.
     */
    public function testNoCoolingSystemOutputsZero(): void
    {
        [$doc, $node] = $this->buildLogementNoCooling();
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 0.0,
            'froid.besoin_fr_depensier' => 0.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $conso = $doc->getElementsByTagName('conso_fr')->item(0);
        if ($conso !== null) {
            $this->assertEqualsWithDelta(0.0, (float)$conso->textContent, self::TOL);
        } else {
            $this->assertTrue(true); // no output element = handled by caller
        }
    }
    public function test_la_cle_appartement_pondere_les_sorties_et_preserve_les_intermediaires(): void
    {
        foreach ([2 => 1.0, 10 => 0.1, 11 => 0.1, 12 => 0.1, 13 => 0.1, 33 => 0.1, 34 => 0.1, 38 => 0.1, 39 => 0.1, 40 => 0.1] as $method => $share) {
            [$doc, $node] = $this->buildLogementWithClimatisation(null, 1.0, 1000.0);
            $general = $doc->getElementsByTagName('caracteristique_generale')->item(0);
            $general->appendChild($doc->createElement('enum_methode_application_dpe_log_id', (string) $method));
            $general->appendChild($doc->createElement('surface_habitable_immeuble', '1000'));
            $doc->getElementsByTagName('donnee_entree')->item(0)->appendChild($doc->createElement('cle_repartition_clim', '0.1'));
            $ctx = $this->makeContext($doc, ['froid.besoin_fr' => 1000.0, 'froid.besoin_fr_depensier' => 1200.0]);
            (new ConsoFroidCalculator())->calculate($node, $ctx);
            $xpath = new \DOMXPath($doc);
            $this->assertEqualsWithDelta(900.0, (float) $xpath->evaluate('string(//climatisation/donnee_intermediaire/conso_fr)'), 1e-9);
            $this->assertEqualsWithDelta(900.0 * $share, (float) $xpath->evaluate('string(//sortie/ef_conso/conso_fr)'), 1e-9);
            $this->assertEqualsWithDelta(1080.0 * $share, $ctx->get('froid.conso_fr_depensier'), 1e-9);
        }
    }

    public function test_une_cle_absente_ne_repartit_pas_arbitrairement_la_climatisation(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 1.0, 1000.0);
        $doc->getElementsByTagName('caracteristique_generale')->item(0)->appendChild($doc->createElement('enum_methode_application_dpe_log_id', '10'));
        $ctx = $this->makeContext($doc, ['froid.besoin_fr' => 1000.0]);
        $this->expectException(\RuntimeException::class);
        (new ConsoFroidCalculator())->calculate($node, $ctx);
    }

    public function test_le_refroidissement_partiel_garde_la_surface_immeuble_avant_repartition(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 4.0, 500.0);
        $general = $doc->getElementsByTagName('caracteristique_generale')->item(0);
        $general->appendChild($doc->createElement('enum_methode_application_dpe_log_id', '13'));
        $general->appendChild($doc->createElement('surface_habitable_immeuble', '1000'));
        $doc->getElementsByTagName('donnee_entree')->item(0)->appendChild($doc->createElement('cle_repartition_clim', '0.1'));
        $ctx = $this->makeContext($doc, ['froid.besoin_fr' => 1000.0, 'froid.besoin_fr_depensier' => 1200.0]);
        (new ConsoFroidCalculator())->calculate($node, $ctx);
        $xp = new \DOMXPath($doc);
        // 0,9 × 1000 / 4 × 500/1000 = 112,5 kWh immeuble, puis clé 0,1.
        self::assertEqualsWithDelta(112.5, (float) $xp->evaluate('string(//climatisation/donnee_intermediaire/conso_fr)'), 1e-9);
        self::assertEqualsWithDelta(11.25, (float) $xp->evaluate('string(//sortie/ef_conso/conso_fr)'), 1e-9);
        self::assertEqualsWithDelta(13.5, $ctx->get('froid.conso_fr_depensier'), 1e-9);
    }

}
