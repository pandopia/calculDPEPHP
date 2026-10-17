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
        $rg = (float)$doc->getElementsByTagName('rendement_generation_stockage')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.75, $rg, self::TOL);
    }

    /** §14.3 — réseau isolé (ID 73) → Rg = 0.90 */
    public function testReseauIsole(): void
    {
        [$doc, $node] = $this->buildNode(73);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation_stockage')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.90, $rg, self::TOL);
    }

    /** §14.3 — réseau non répertorié ou inconnu (ID 119) → hypothèse non isolée. */
    public function testReseauInconnu(): void
    {
        [$doc, $node] = $this->buildNode(119);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation_stockage')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.75, $rg, self::TOL);
    }

    /** §14.3 — réseau non isolé logement neuf (ID 107) → Rg = 0.75 */
    public function testReseauNonIsoleNeuf(): void
    {
        [$doc, $node] = $this->buildNode(107);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation_stockage')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.75, $rg, self::TOL);
    }

    /** §14.3 — réseau isolé logement neuf (ID 108) → Rg = 0.90 */
    public function testReseauIsoleNeuf(): void
    {
        [$doc, $node] = $this->buildNode(108);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $rg = (float)$doc->getElementsByTagName('rendement_generation_stockage')->item(0)?->textContent;
        $this->assertEqualsWithDelta(0.90, $rg, self::TOL);
    }

    /** Autre type ECS (CET, chaudière) → pas de rendement_generation écrit */
    public function testNonReseauSkipped(): void
    {
        [$doc, $node] = $this->buildNode(1);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));
        $this->assertNull($doc->getElementsByTagName('rendement_generation')->item(0));
    }

    /** @return array{DOMDocument, \DOMElement} */
    private function sousStation(int $typeId, int $stockage, float $volume, string $posGen, string $posStock): array
    {
        $doc = new DOMDocument();
        $doc->loadXML(sprintf(<<<'XML'
<logement><generateur_ecs><donnee_entree>
  <enum_type_generateur_ecs_id>%d</enum_type_generateur_ecs_id>
  <enum_type_stockage_ecs_id>%d</enum_type_stockage_ecs_id>
  <volume_stockage>%s</volume_stockage>
  %s%s
</donnee_entree></generateur_ecs></logement>
XML, $typeId, $stockage, $volume,
            $posGen === '' ? '' : "<position_volume_chauffe>$posGen</position_volume_chauffe>",
            $posStock === '' ? '' : "<position_volume_chauffe_stockage>$posStock</position_volume_chauffe_stockage>"));

        return [$doc, $doc->getElementsByTagName('generateur_ecs')->item(0)];
    }

    private function rendement(int $typeId, int $stockage, float $volume, string $posGen, string $posStock): float
    {
        [$doc, $node] = $this->sousStation($typeId, $stockage, $volume, $posGen, $posStock);
        (new ReseauChaleurCalculator())->calculate($node, $this->makeContext($doc));

        return (float) $doc->getElementsByTagName('rendement_generation_stockage')->item(0)->textContent;
    }

    /**
     * §14.3 p.95 : « si l'installation est isolée : Rs × Rg = 0,9 ; sinon
     * 0,75 ». Sur une chaudière multi bâtiment, que le XSD ne qualifie pas,
     * le cas « non isolée » est celui où générateur et ballon sont tous deux
     * hors volume chauffé — 2501E1812848D et 2513E0192986F.
     */
    public function testSousStationEtBallonHorsVolumeChauffeDonnent075(): void
    {
        self::assertEqualsWithDelta(0.75, $this->rendement(76, 3, 1000.0, '0', '0'), self::TOL);
    }

    /** Le ballon dans le volume chauffé : l'installation reste isolée. */
    public function testBallonDansLeVolumeChauffeDonne090(): void
    {
        self::assertEqualsWithDelta(0.90, $this->rendement(76, 3, 1000.0, '0', '1'), self::TOL);
    }

    /** Le générateur dans le volume chauffé suffit également — 2673E0034873H. */
    public function testGenerateurDansLeVolumeChauffeDonne090(): void
    {
        self::assertEqualsWithDelta(0.90, $this->rendement(76, 3, 1500.0, '1', '0'), self::TOL);
    }

    /**
     * Sans ballon, il n'y a pas de stockage à placer : la position du seul
     * générateur ne suffit pas à déclarer l'installation non isolée.
     */
    public function testSansBallonLaPositionDuGenerateurNeSuffitPas(): void
    {
        self::assertEqualsWithDelta(0.90, $this->rendement(75, 1, 0.0, '0', '0'), self::TOL);
    }

    /** Un réseau urbain porte le qualificatif dans son propre type. */
    public function testReseauUrbainIgnoreLesPositions(): void
    {
        self::assertEqualsWithDelta(0.90, $this->rendement(73, 3, 500.0, '0', '0'), self::TOL);
    }
}
