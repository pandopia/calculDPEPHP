<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpePHP\Chauffage\Rendement\DistributionCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour DistributionCalculator chauffage (§12.2 p.76).
 */
final class DistributionChauffageCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 0.0001;

    #[\PHPUnit\Framework\Attributes\DataProvider('reseauxDesEmetteursGeneriques')]
    public function testUnEmetteurGeneriqueConserveSonReseauDeclare(int $tvId, ?int $isole, float $expected): void
    {
        [$doc, $node] = $this->buildEmetteur(41, $isole ?? 0);
        $entree = $node->getElementsByTagName('donnee_entree')->item(0);
        $entree->appendChild($doc->createElement('tv_rendement_distribution_ch_id', (string)$tvId));
        if ($isole === null) {
            $entree->removeChild($entree->getElementsByTagName('reseau_distribution_isole')->item(0));
        }
        (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
        self::assertEqualsWithDelta($expected, (float)$node->getElementsByTagName('rendement_distribution')->item(0)->textContent, 1e-12);
    }

    public static function reseauxDesEmetteursGeneriques(): iterable
    {
        // ADEME rendement_distribution_ch A2:E13, coefficients §12.2 p.76.
        $rows = [1 => [1, 1, 1], 2 => [.8, .85, .8], 3 => [.85, .87, .85],
            4 => [.87, .9, .87], 5 => [.88, .92, .88], 6 => [.91, .95, .91],
            7 => [.8, .85, .85], 8 => [.85, .87, .87], 9 => [.87, .9, .9],
            10 => [.88, .92, .92], 11 => [.91, .95, .95], 12 => [1, 1, 1]];
        foreach ($rows as $tvId => [$nonIsole, $isole, $sansDrapeau]) {
            yield "$tvId/non isolé déclaré" => [$tvId, 0, $nonIsole];
            yield "$tvId/isolé déclaré" => [$tvId, 1, $isole];
            yield "$tvId/sans drapeau" => [$tvId, null, $sansDrapeau];
        }
    }

    public function testLaLigneDeTableNeContreditPasUnReseauDecritPhysiquement(): void
    {
        foreach ([5 => .8, 11 => .85, 12 => .87, 13 => .88, 14 => .91, 42 => 1] as $type => $expected) {
            [$doc, $node] = $this->buildEmetteur($type, 0);
            $node->getElementsByTagName('donnee_entree')->item(0)->appendChild($doc->createElement('tv_rendement_distribution_ch_id', '1'));
            (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
            self::assertEqualsWithDelta($expected, (float)$node->getElementsByTagName('rendement_distribution')->item(0)->textContent, 1e-12);
        }
    }

    public function testSansReseauQualifieLeGeneriqueGardeLeRepliHistorique(): void
    {
        [$doc, $node] = $this->buildEmetteur(41);
        (new DistributionCalculator())->calculate($node, $this->makeContext($doc));
        self::assertSame('1', $node->getElementsByTagName('rendement_distribution')->item(0)->textContent);
    }

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
