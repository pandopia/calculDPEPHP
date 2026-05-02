<?php

declare(strict_types=1);

namespace Tests\Unit\Ecs\Rendement;

use CalculDpe\Ecs\Rendement\DistributionCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour DistributionCalculator ECS (§11.5 p.73-74).
 */
final class DistributionCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 0.0001;

    private function buildInstallation(int $tvId): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_ecs>
        <donnee_entree>
            <tv_rendement_distribution_ecs_id>$tvId</tv_rendement_distribution_ecs_id>
        </donnee_entree>
    </installation_ecs>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $inst = $doc->getElementsByTagName('installation_ecs')->item(0);
        return [$doc, $inst];
    }

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    /**
     * tv_rendement_distribution_ecs_id=2 → Rd=0.87 (individuel, hors vol. habitable).
     */
    public function testId2ReturnsPoint87(): void
    {
        [$doc, $node] = $this->buildInstallation(2);
        $ctx = $this->makeContext($doc);
        (new DistributionCalculator())->calculate($node, $ctx);

        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.87, $rd, self::TOL);
    }

    /**
     * tv_rendement_distribution_ecs_id=7 → Rd=0.52 (collectif, isolé sans traçage, non contigu).
     */
    public function testId7ReturnsPoint52(): void
    {
        [$doc, $node] = $this->buildInstallation(7);
        $ctx = $this->makeContext($doc);
        (new DistributionCalculator())->calculate($node, $ctx);

        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.52, $rd, self::TOL);
    }

    /**
     * tv_rendement_distribution_ecs_id=1 → Rd=0.93 (individuel, vol. habitable, contigu).
     */
    public function testId1ReturnsPoint93(): void
    {
        [$doc, $node] = $this->buildInstallation(1);
        $ctx = $this->makeContext($doc);
        (new DistributionCalculator())->calculate($node, $ctx);

        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.93, $rd, self::TOL);
    }

    /**
     * Rd stocké dans le contexte.
     */
    public function testContextStoredCorrectly(): void
    {
        [$doc, $node] = $this->buildInstallation(7);
        $ctx = $this->makeContext($doc);
        (new DistributionCalculator())->calculate($node, $ctx);

        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta($rd, (float)$ctx->get('ecs.rendement_distribution', 0.0), self::TOL);
    }

    /**
     * ID absent → Rd = 1.0 par défaut.
     */
    public function testMissingIdDefaultsToOne(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_ecs>
        <donnee_entree></donnee_entree>
    </installation_ecs>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $inst = $doc->getElementsByTagName('installation_ecs')->item(0);
        $ctx = $this->makeContext($doc);
        (new DistributionCalculator())->calculate($inst, $ctx);

        $rd = (float)$doc->getElementsByTagName('rendement_distribution')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.0, $rd, self::TOL);
    }
}
