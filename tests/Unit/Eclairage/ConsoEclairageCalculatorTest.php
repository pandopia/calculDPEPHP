<?php

declare(strict_types=1);

namespace Tests\Unit\Eclairage;

use CalculDpePHP\Eclairage\ConsoEclairageCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ConsoEclairageCalculator (§16.1 p.102-103).
 *
 * Formule : Cecl = 0,9 × 1,4 × Nhj / 1000 × Sh
 * Nhj table par zone (1→h1a=1500, 5→h2b=1531, …)
 */
final class ConsoEclairageCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    private function buildDoc(int $zoneId, ?float $shLogement, float $shImmeuble): array
    {
        $shLogTag = $shLogement !== null
            ? "<surface_habitable_logement>$shLogement</surface_habitable_logement>"
            : '';
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <meteo><enum_zone_climatique_id>$zoneId</enum_zone_climatique_id></meteo>
    <caracteristique_generale>
        $shLogTag
        <surface_habitable_immeuble>$shImmeuble</surface_habitable_immeuble>
    </caracteristique_generale>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        return [$doc, $node];
    }

    /**
     * Zone h1a (id=1, Nhj=1500) — ZONE (sh_logement=102).
     * Cecl = 0.9 × 1.4 × 1.5 × 102 = 192.78
     */
    public function testZoneH1aSurface102(): void
    {
        [$doc, $node] = $this->buildDoc(zoneId: 1, shLogement: 102.0, shImmeuble: 9543.0);
        $ctx = $this->makeContext($doc);

        (new ConsoEclairageCalculator())->calculate($node, $ctx);

        $this->assertEqualsWithDelta(192.78, (float)$ctx->get('eclairage.conso_eclairage', 0.0), 0.01);
    }

    /**
     * Zone h1a (id=1, Nhj=1500) — BAT (sh_logement absent, sh_immeuble=9543).
     * Cecl = 0.9 × 1.4 × 1.5 × 9543 = 18036.27
     */
    public function testBatH1aSurfaceImmeuble(): void
    {
        [$doc, $node] = $this->buildDoc(zoneId: 1, shLogement: null, shImmeuble: 9543.0);
        $ctx = $this->makeContext($doc);

        (new ConsoEclairageCalculator())->calculate($node, $ctx);

        $this->assertEqualsWithDelta(18036.27, (float)$ctx->get('eclairage.conso_eclairage', 0.0), 0.1);
    }

    /**
     * Zone h3 (id=8, Nhj=1506) — surface=100.
     * Cecl = 0.9 × 1.4 × 1.506 × 100 = 189.756
     */
    public function testZoneH3Surface100(): void
    {
        [$doc, $node] = $this->buildDoc(zoneId: 8, shLogement: 100.0, shImmeuble: 1000.0);
        $ctx = $this->makeContext($doc);

        (new ConsoEclairageCalculator())->calculate($node, $ctx);

        $expected = 0.9 * 1.4 * (1506.0 / 1000.0) * 100.0;
        $this->assertEqualsWithDelta($expected, (float)$ctx->get('eclairage.conso_eclairage', 0.0), 0.01);
    }

    /**
     * Zone inconnue (id=99) → fallback Nhj=1500.
     */
    public function testUnknownZoneFallback(): void
    {
        [$doc, $node] = $this->buildDoc(zoneId: 99, shLogement: 50.0, shImmeuble: 500.0);
        $ctx = $this->makeContext($doc);

        (new ConsoEclairageCalculator())->calculate($node, $ctx);

        $expected = 0.9 * 1.4 * (1500.0 / 1000.0) * 50.0;
        $this->assertEqualsWithDelta($expected, (float)$ctx->get('eclairage.conso_eclairage', 0.0), 0.01);
    }

    /**
     * appliesTo() retourne vrai pour 'logement', faux sinon.
     */
    public function testAppliesToLogement(): void
    {
        [$doc] = $this->buildDoc(1, null, 100.0);
        $logNode = $doc->getElementsByTagName('logement')->item(0);
        $meteoNode = $doc->getElementsByTagName('meteo')->item(0);

        $calc = new ConsoEclairageCalculator();
        $this->assertTrue($calc->appliesTo($logNode));
        $this->assertFalse($calc->appliesTo($meteoNode));
    }

    /**
     * Surface nulle → Cecl = 0 (pas d'exception).
     */
    public function testZeroSurface(): void
    {
        [$doc, $node] = $this->buildDoc(zoneId: 1, shLogement: 0.0, shImmeuble: 0.0);
        $ctx = $this->makeContext($doc);

        (new ConsoEclairageCalculator())->calculate($node, $ctx);

        $this->assertEqualsWithDelta(0.0, (float)$ctx->get('eclairage.conso_eclairage', 0.0), 0.001);
    }

    /**
     * Zone post2026 ZONE collectif: h2c (id=6, Nhj=1566) — surface 62.86.
     * Cecl = 0.9 × 1.4 × 1.566 × 62.86 = 124.02...
     */
    public function testZonePost2026H2cLogement(): void
    {
        [$doc, $node] = $this->buildDoc(zoneId: 6, shLogement: 62.86, shImmeuble: 1034.74);
        $ctx = $this->makeContext($doc);

        (new ConsoEclairageCalculator())->calculate($node, $ctx);

        $expected = 0.9 * 1.4 * (1566.0 / 1000.0) * 62.86;
        $this->assertEqualsWithDelta($expected, (float)$ctx->get('eclairage.conso_eclairage', 0.0), 0.1);
    }
}
