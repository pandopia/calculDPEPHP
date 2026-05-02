<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpePHP\Chauffage\Rendement\RegulationCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour RegulationCalculator (§12.3 p.76).
 */
final class RegulationCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 0.0001;

    private function buildEmetteur(int $emId): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_chauffage>
        <emetteur_chauffage>
            <donnee_entree>
                <enum_type_emission_distribution_id>$emId</enum_type_emission_distribution_id>
            </donnee_entree>
        </emetteur_chauffage>
    </installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        return [$doc, $node];
    }

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    public function testConvecteurElecNfcReturns099(): void
    {
        [$doc, $node] = $this->buildEmetteur(1);
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.99, $rr, self::TOL);
    }

    public function testRadiateurElecNfcReturns099(): void
    {
        [$doc, $node] = $this->buildEmetteur(3); // verif post2026 uses id=3 → Rr=0.99
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.99, $rr, self::TOL);
    }

    public function testPlancherElecAvecRegulReturns098(): void
    {
        [$doc, $node] = $this->buildEmetteur(8); // plancher élec avec régulation
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.98, $rr, self::TOL);
    }

    public function testPlancherElecSansRegulReturns096(): void
    {
        [$doc, $node] = $this->buildEmetteur(9);
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.96, $rr, self::TOL);
    }

    public function testPlancherEauCollectifReturns090(): void
    {
        [$doc, $node] = $this->buildEmetteur(11); // plancher collectif eau HT
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.90, $rr, self::TOL);
    }

    public function testPlancherEauIndividuelReturns095(): void
    {
        [$doc, $node] = $this->buildEmetteur(13); // plancher individuel HT
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.95, $rr, self::TOL);
    }

    public function testPoeleReturns080(): void
    {
        [$doc, $node] = $this->buildEmetteur(21); // poêle bois
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.80, $rr, self::TOL);
    }

    public function testRadiateurEauSansRobinetReturns090(): void
    {
        [$doc, $node] = $this->buildEmetteur(24); // monotube sans robinet collectif HT
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.90, $rr, self::TOL);
    }

    public function testRadiateurEauAvecRobinetReturns095(): void
    {
        [$doc, $node] = $this->buildEmetteur(37); // bitube avec robinet collectif BT
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.95, $rr, self::TOL);
    }

    public function testAirSouffleReturns096(): void
    {
        [$doc, $node] = $this->buildEmetteur(5); // soufflage aéraulique
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.96, $rr, self::TOL);
    }

    public function testConvecteurBijonctionReturns090(): void
    {
        [$doc, $node] = $this->buildEmetteur(40);
        (new RegulationCalculator())->calculate($node, $this->makeContext($doc));
        $rr = (float)$doc->getElementsByTagName('rendement_regulation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.90, $rr, self::TOL);
    }
}
