<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpe\Chauffage\Rendement\Combustion\InsertsPoelesCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class InsertsPoelesCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 1e-9;

    private function buildNode(int $genTypeId): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <generateur_chauffage>
        <donnee_entree>
            <enum_type_generateur_ch_id>$genTypeId</enum_type_generateur_ch_id>
        </donnee_entree>
    </generateur_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('generateur_chauffage')->item(0);
        return [$doc, $node];
    }

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    /** §13.1 — cuisinière avant 1990 → Rg = 0.50 */
    public function testCuisiniereAvant1990(): void
    {
        [$doc, $node] = $this->buildNode(20);
        (new InsertsPoelesCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.50, $rg, self::TOL);
    }

    /** §13.1 — insert avant 1990 → Rg = 0.50 */
    public function testInsertAvant1990(): void
    {
        [$doc, $node] = $this->buildNode(23);
        (new InsertsPoelesCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.50, $rg, self::TOL);
    }

    /** §13.1 — insert 1990-2004 → Rg = 0.60 */
    public function testInsert1990_2004(): void
    {
        [$doc, $node] = $this->buildNode(27);
        (new InsertsPoelesCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.60, $rg, self::TOL);
    }

    /** §13.1 — insert 2007-2017 avec label → Rg = 0.70 */
    public function testInsert2007_2017AvecLabel(): void
    {
        [$doc, $node] = $this->buildNode(39);
        (new InsertsPoelesCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.70, $rg, self::TOL);
    }

    /** §13.1 — poêle granulés avant 2012 → Rg = 0.80 */
    public function testPoeleGranulesAvant2012(): void
    {
        [$doc, $node] = $this->buildNode(44);
        (new InsertsPoelesCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.80, $rg, self::TOL);
    }

    /** §13.1 — poêle granulés flamme verte à partir 2020 → Rg = 0.87 */
    public function testPoeleGranulesApres2020(): void
    {
        [$doc, $node] = $this->buildNode(46);
        (new InsertsPoelesCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.87, $rg, self::TOL);
    }

    /** §13.1 — poêle fioul/GPL/charbon → Rg = 0.72 */
    public function testPoeleFioulGplCharbon(): void
    {
        [$doc, $node] = $this->buildNode(47);
        (new InsertsPoelesCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.72, $rg, self::TOL);
    }

    /** ID hors plage (chaudière gaz) → pas de rendement_generation écrit */
    public function testChaudiereGazSkipped(): void
    {
        [$doc, $node] = $this->buildNode(55);
        (new InsertsPoelesCalculator())->calculate($node, $this->makeContext($doc));
        $this->assertNull($doc->getElementsByTagName('rendement_generation')->item(0));
    }

    /** ID inconnu → pas de rendement_generation écrit */
    public function testUnknownIdSkipped(): void
    {
        [$doc, $node] = $this->buildNode(999);
        (new InsertsPoelesCalculator())->calculate($node, $this->makeContext($doc));
        $this->assertNull($doc->getElementsByTagName('rendement_generation')->item(0));
    }
}
