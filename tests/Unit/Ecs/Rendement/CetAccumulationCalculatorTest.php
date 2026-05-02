<?php

declare(strict_types=1);

namespace Tests\Unit\Ecs\Rendement;

use CalculDpe\Ecs\Rendement\CetAccumulationCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class CetAccumulationCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 1e-9;

    private function buildNode(int $typeId): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <generateur_ecs>
        <donnee_entree>
            <enum_type_generateur_ecs_id>$typeId</enum_type_generateur_ecs_id>
        </donnee_entree>
    </generateur_ecs>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('generateur_ecs')->item(0);
        return [$doc, $node];
    }

    private function makeContext(DOMDocument $doc, string $zone = '1'): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: $zone,
        );
    }

    /** §14.2 — CET air ambiant avant 2010, zone H1 → COP = 2.0 */
    public function testAmbiantAvant2010H1(): void
    {
        [$doc, $node] = $this->buildNode(1);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '1'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $rg  = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.0, $cop, self::TOL);
        $this->assertEqualsWithDelta(2.0, $rg,  self::TOL);
    }

    /** §14.2 — CET air extérieur 2010-2014, zone H2 → COP = 2.2 */
    public function testExterieur2010_2014H2(): void
    {
        [$doc, $node] = $this->buildNode(5);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '5'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.2, $cop, self::TOL);
    }

    /** §14.2 — CET air extrait à partir 2015, zone H1 → COP = 2.8 */
    public function testAirExtraitApres2015H1(): void
    {
        [$doc, $node] = $this->buildNode(9);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '2'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.8, $cop, self::TOL);
    }

    /** §14.2 — PAC double service avant 2010, zone H3 → COP = 2.3 */
    public function testPacDoubleServiceAvant2010H3(): void
    {
        [$doc, $node] = $this->buildNode(10);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '8'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.3, $cop, self::TOL);
    }

    /** §14.2 — PAC double service à partir 2015, zone H3 → COP = 2.6 */
    public function testPacDoubleServiceApres2015H3(): void
    {
        [$doc, $node] = $this->buildNode(12);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '8'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.6, $cop, self::TOL);
    }

    /** ID hors plage (chaudière gaz) → aucun champ écrit */
    public function testNonCetTypeSkipped(): void
    {
        [$doc, $node] = $this->buildNode(15);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc));
        $this->assertNull($doc->getElementsByTagName('cop')->item(0));
    }
}
