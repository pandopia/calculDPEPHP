<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpe\Common\Period;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Sortie\DeperditionCalculator;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class DeperditionCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function buildDoc(float $hvent, float $hperm, float $dpParois, float $dpPT): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <sortie>
        <deperdition>
            <hvent>$hvent</hvent>
            <hperm>$hperm</hperm>
        </deperdition>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            period: Period::PRE_2026,
        );
        $ctx->set('ventilation.hvent', $hvent);
        $ctx->set('ventilation.hperm', $hperm);
        $ctx->set('enveloppe.dp_parois', $dpParois);
        $ctx->set('enveloppe.dp_pont_thermique', $dpPT);

        (new DeperditionCalculator())->calculate($doc->documentElement, $ctx);

        return [$doc, $doc->documentElement];
    }

    private function depValue(DOMDocument $doc, string $tag): float
    {
        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query("//deperdition/$tag");
        $this->assertNotNull($nodes);
        $this->assertGreaterThan(0, $nodes->length, "Missing tag: $tag");
        return (float)$nodes->item(0)->textContent;
    }

    public function testAppliesToLogement(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement/>');
        $calc = new DeperditionCalculator();
        $this->assertTrue($calc->appliesTo($doc->documentElement));

        $doc2 = new DOMDocument();
        $doc2->loadXML('<ventilation/>');
        $this->assertFalse($calc->appliesTo($doc2->documentElement));
    }

    public function testDeperditionRenouvellementAir(): void
    {
        [$doc] = $this->buildDoc(3536.6358, 1693.884, 9584.0, 0.0);
        $dr = $this->depValue($doc, 'deperdition_renouvellement_air');
        $this->assertEqualsWithDelta(5230.5198, $dr, 0.001);
    }

    public function testDeperditionEnveloppeIncludesAllComponents(): void
    {
        // deperdition_enveloppe = dp_parois + dp_PT + dr
        // = 9584.0 + 1927.789 + (3536.6358 + 1693.884) = 16742.309
        [$doc] = $this->buildDoc(3536.6358, 1693.884, 9584.0, 1927.789);
        $dpEnv = $this->depValue($doc, 'deperdition_enveloppe');
        $this->assertEqualsWithDelta(16742.309, $dpEnv, 0.01);
    }

    public function testDeperditionEnvelopeMatchesVerifProfile(): void
    {
        // bat_pre2026 verif values (approximate):
        // parois = 4979.98 + 752.21 + 166.88 + 1685.06 + 73.13 = 7657.26
        // PT = 1927.789
        // hvent = 3536.6358, hperm = 1693.884
        // expected deperdition_enveloppe = 14815.57
        $dpParois = 4979.981 + 752.208 + 166.88 + 1685.056 + 73.133;
        [$doc] = $this->buildDoc(3536.6358, 1693.8840, $dpParois, 1927.789);
        $dpEnv = $this->depValue($doc, 'deperdition_enveloppe');
        $this->assertEqualsWithDelta(14815.566, $dpEnv, 0.1);
    }
}
