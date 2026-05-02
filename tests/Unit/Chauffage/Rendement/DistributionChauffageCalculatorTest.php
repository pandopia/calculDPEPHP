<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpe\Chauffage\Rendement\DistributionCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour DistributionCalculator chauffage (§12.2 p.76).
 */
final class DistributionChauffageCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 0.0001;

    private function buildEmetteur(int $emId, int $isole = 0): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_chauffage>
        <emetteur_chauffage>
            <donnee_entree>
                <enum_type_emission_distribution_id>$emId</enum_type_emission_distribution_id>
                <reseau_distribution_isole>$isole</reseau_distribution_isole>
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

    public function testLocalElecNoNetworkReturns1(): void
    {
        [$doc, $node] = $this->buildEmetteur(1, 0); // convecteur élec → pas de réseau
        (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.0, $rd, self::TOL);
    }

    public function testAerauliqueNonIsoleReturns080(): void
    {
        [$doc, $node] = $this->buildEmetteur(5, 0); // soufflage aéraulique non isolé
        (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.80, $rd, self::TOL);
    }

    public function testAerauliqueIsoleReturns085(): void
    {
        [$doc, $node] = $this->buildEmetteur(5, 1); // soufflage aéraulique isolé
        (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.85, $rd, self::TOL);
    }

    public function testCollectifHtNonIsoleReturns085(): void
    {
        [$doc, $node] = $this->buildEmetteur(24, 0); // radiateur monotube sans robinet collectif HT
        (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.85, $rd, self::TOL);
    }

    public function testCollectifBtIsoleReturns090(): void
    {
        [$doc, $node] = $this->buildEmetteur(37, 1); // radiateur bitube avec robinet collectif BT, isolé
        (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.90, $rd, self::TOL);
    }

    public function testIndividuelBtIsoleReturns095(): void
    {
        [$doc, $node] = $this->buildEmetteur(39, 1); // radiateur bitube avec robinet individuel BT, isolé
        (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.95, $rd, self::TOL);
    }

    public function testFluidesFrigorigenesReturns100(): void
    {
        foreach ([42, 43, 44, 45] as $emId) {
            [$doc, $node] = $this->buildEmetteur($emId, 0);
            (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
            $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
            $this->assertEqualsWithDelta(1.0, $rd, self::TOL, "ID $emId should return Rd=1");
        }
    }
}
