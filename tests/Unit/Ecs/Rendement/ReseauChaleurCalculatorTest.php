<?php

declare(strict_types=1);

namespace Tests\Unit\Ecs\Rendement;

use CalculDpePHP\Ecs\Rendement\ReseauChaleurCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class ReseauChaleurCalculatorTest extends TestCase
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

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    /** §14.3 — réseau non isolé (ID 72) → Rg = 0.75 */
    public function testReseauNonIsole(): void
    {
        [$doc, $node] = $this->buildNode(72);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.75, $rg, self::TOL);
    }

    /** §14.3 — réseau isolé (ID 73) → Rg = 0.90 */
    public function testReseauIsole(): void
    {
        [$doc, $node] = $this->buildNode(73);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.90, $rg, self::TOL);
    }

    /** §14.3 — réseau non isolé logement neuf (ID 107) → Rg = 0.75 */
    public function testReseauNonIsoleNeuf(): void
    {
        [$doc, $node] = $this->buildNode(107);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.75, $rg, self::TOL);
    }

    /** §14.3 — réseau isolé logement neuf (ID 108) → Rg = 0.90 */
    public function testReseauIsoleNeuf(): void
    {
        [$doc, $node] = $this->buildNode(108);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.90, $rg, self::TOL);
    }

    /** Autre type ECS (CET, chaudière) → pas de rendement_generation écrit */
    public function testNonReseauSkipped(): void
    {
        [$doc, $node] = $this->buildNode(1);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $this->assertNull($doc->getElementsByTagName('rendement_generation')->item(0));
    }
}
