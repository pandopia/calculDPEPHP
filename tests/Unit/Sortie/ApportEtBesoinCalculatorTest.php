<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Common\Period;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\ApportEtBesoinCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class ApportEtBesoinCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function buildContextWithValues(array $contextValues, float $sse = 4324.509324): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <sortie/>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            period: Period::PRE_2026,
        );
        $ctx->set('apport.sse_annuel', $sse);
        foreach ($contextValues as $key => $val) {
            $ctx->set($key, $val);
        }
        return [$doc, $doc->documentElement, $ctx];
    }

    private function runAndGetBlock(array $contextValues, float $sse = 4324.509324): \DOMElement
    {
        [$doc, $logement, $ctx] = $this->buildContextWithValues($contextValues, $sse);
        (new ApportEtBesoinCalculator())->calculate($logement, $ctx);

        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query('.//apport_et_besoin', $logement);
        $this->assertNotNull($nodes);
        $this->assertGreaterThan(0, $nodes->length);
        return $nodes->item(0);
    }

    private function childText(\DOMElement $parent, string $tag): ?string
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof \DOMElement && $c->nodeName === $tag) return $c->textContent;
        }
        return null;
    }

    public function testAppliesToLogement(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement/>');
        $calc = new ApportEtBesoinCalculator();
        $this->assertTrue($calc->appliesTo($doc->documentElement));

        $doc2 = new DOMDocument();
        $doc2->loadXML('<installation_ecs/>');
        $this->assertFalse($calc->appliesTo($doc2->documentElement));
    }

    public function testSurfaceSudEquivalente(): void
    {
        $block = $this->runAndGetBlock([], 4324.51);
        $this->assertEqualsWithDelta(4324.51, (float)$this->childText($block, 'surface_sud_equivalente'), 0.001);
    }

    public function testBesoinChauffage(): void
    {
        $block = $this->runAndGetBlock([
            'chauffage.besoin_ch'          => 474598.74,
            'chauffage.besoin_ch_depensier'=> 618836.98,
        ]);
        $this->assertEqualsWithDelta(474598.74, (float)$this->childText($block, 'besoin_ch'), 0.01);
        $this->assertEqualsWithDelta(618836.98, (float)$this->childText($block, 'besoin_ch_depensier'), 0.01);
    }

    public function testBesoinEcs(): void
    {
        $block = $this->runAndGetBlock([
            'ecs.besoin_ecs'          => 167440.02,
            'ecs.besoin_ecs_depensier'=> 236210.02,
        ]);
        $this->assertEqualsWithDelta(167440.02, (float)$this->childText($block, 'besoin_ecs'), 0.01);
        $this->assertEqualsWithDelta(236210.02, (float)$this->childText($block, 'besoin_ecs_depensier'), 0.01);
    }

    public function testBesoinFroid(): void
    {
        $block = $this->runAndGetBlock([
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier'=> 1200.0,
        ]);
        $this->assertEqualsWithDelta(1000.0, (float)$this->childText($block, 'besoin_fr'), 0.01);
        $this->assertEqualsWithDelta(1200.0, (float)$this->childText($block, 'besoin_fr_depensier'), 0.01);
    }

    public function testApportsChauffage(): void
    {
        $block = $this->runAndGetBlock([
            'apport.apport_solaire_ch'       => 167794.78,
            'apport.apport_interne_ch'       => 299321.37,
            'apport.fraction_ch'             => 0.43970,
            'apport.fraction_ch_depensier'   => 0.39335,
        ]);
        $this->assertEqualsWithDelta(167794.78, (float)$this->childText($block, 'apport_solaire_ch'), 0.01);
        $this->assertEqualsWithDelta(299321.37, (float)$this->childText($block, 'apport_interne_ch'), 0.01);
        $this->assertEqualsWithDelta(0.43970, (float)$this->childText($block, 'fraction_apport_gratuit_ch'), 0.001);
        $this->assertEqualsWithDelta(0.39335, (float)$this->childText($block, 'fraction_apport_gratuit_depensier_ch'), 0.001);
    }

    public function testNadeqAndV40(): void
    {
        $block = $this->runAndGetBlock([
            'ecs.nadeq'            => 255.7765,
            'ecs.v40_journalier'   => 14323.484,
            'ecs.v40_journalier_dep'=> 20206.344,
        ]);
        $this->assertEqualsWithDelta(255.7765, (float)$this->childText($block, 'nadeq'), 0.001);
        $this->assertEqualsWithDelta(14323.484, (float)$this->childText($block, 'v40_ecs_journalier'), 0.01);
        $this->assertEqualsWithDelta(20206.344, (float)$this->childText($block, 'v40_ecs_journalier_depensier'), 0.01);
    }

    public function testPertesDefaultsToZero(): void
    {
        $block = $this->runAndGetBlock([]);
        $this->assertEquals(0.0, (float)$this->childText($block, 'pertes_distribution_ecs_recup'));
        $this->assertEquals(0.0, (float)$this->childText($block, 'pertes_stockage_ecs_recup'));
        $this->assertEquals(0.0, (float)$this->childText($block, 'pertes_generateur_ch_recup'));
    }

    public function testAll21TagsAreWritten(): void
    {
        $block = $this->runAndGetBlock([]);
        $expected = [
            'surface_sud_equivalente', 'apport_solaire_fr', 'apport_interne_fr',
            'apport_solaire_ch', 'apport_interne_ch', 'fraction_apport_gratuit_ch',
            'fraction_apport_gratuit_depensier_ch', 'pertes_distribution_ecs_recup',
            'pertes_distribution_ecs_recup_depensier', 'pertes_stockage_ecs_recup',
            'pertes_generateur_ch_recup', 'pertes_generateur_ch_recup_depensier',
            'nadeq', 'v40_ecs_journalier', 'v40_ecs_journalier_depensier',
            'besoin_ch', 'besoin_ch_depensier', 'besoin_ecs', 'besoin_ecs_depensier',
            'besoin_fr', 'besoin_fr_depensier',
        ];
        foreach ($expected as $tag) {
            $this->assertNotNull($this->childText($block, $tag), "Missing tag: $tag");
        }
    }
}
