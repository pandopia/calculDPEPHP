<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpePHP\Chauffage\Rendement\EmissionCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour EmissionCalculator (§12.1 p.75-76).
 */
final class EmissionCalculatorTest extends TestCase
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

    public function testConvecteurElecNfcReturns095(): void
    {
        [$doc, $node] = $this->buildEmetteur(1); // convecteur électrique NFC
        (new EmissionCalculator())->calculate($node, $this->makeContext($doc));
        $re = (float)$doc->getElementsByTagName('rendement_emission')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.95, $re, self::TOL);
    }

    public function testPanneauRayonnantReturns097(): void
    {
        [$doc, $node] = $this->buildEmetteur(2); // panneau rayonnant NFC
        (new EmissionCalculator())->calculate($node, $this->makeContext($doc));
        $re = (float)$doc->getElementsByTagName('rendement_emission')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.97, $re, self::TOL);
    }

    public function testRadiateurElecNfcReturns097(): void
    {
        [$doc, $node] = $this->buildEmetteur(3); // radiateur électrique NFC
        (new EmissionCalculator())->calculate($node, $this->makeContext($doc));
        $re = (float)$doc->getElementsByTagName('rendement_emission')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.97, $re, self::TOL);
    }

    public function testPlancherElecReturns100(): void
    {
        [$doc, $node] = $this->buildEmetteur(8); // plancher rayonnant élec avec régulation
        (new EmissionCalculator())->calculate($node, $this->makeContext($doc));
        $re = (float)$doc->getElementsByTagName('rendement_emission')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.00, $re, self::TOL);
    }

    public function testPlafondElecReturns098(): void
    {
        [$doc, $node] = $this->buildEmetteur(6); // plafond rayonnant élec avec régulation
        (new EmissionCalculator())->calculate($node, $this->makeContext($doc));
        $re = (float)$doc->getElementsByTagName('rendement_emission')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.98, $re, self::TOL);
    }

    public function testRadiateurEauReturns095(): void
    {
        [$doc, $node] = $this->buildEmetteur(37); // radiateur bitube avec robinet collectif BT
        (new EmissionCalculator())->calculate($node, $this->makeContext($doc));
        $re = (float)$doc->getElementsByTagName('rendement_emission')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.95, $re, self::TOL);
    }

    public function testPlancherEauReturns100(): void
    {
        [$doc, $node] = $this->buildEmetteur(14); // plancher individuel eau BT
        (new EmissionCalculator())->calculate($node, $this->makeContext($doc));
        $re = (float)$doc->getElementsByTagName('rendement_emission')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.00, $re, self::TOL);
    }

    public function testFluideFrigorigeneRadiateurReturns095(): void
    {
        [$doc, $node] = $this->buildEmetteur(45); // radiateur frigorigène
        (new EmissionCalculator())->calculate($node, $this->makeContext($doc));
        $re = (float)$doc->getElementsByTagName('rendement_emission')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.95, $re, self::TOL);
    }
}
