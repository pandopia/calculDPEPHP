<?php

declare(strict_types=1);

namespace Tests\Unit\Ecs;

use CalculDpePHP\Ecs\FacteurCouvertureSolaireEcsCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour FacteurCouvertureSolaireEcsCalculator (§18.4).
 */
final class FacteurCouvertureSolaireEcsCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function buildInstallation(int $typeSolaire, int $modeApp, int $zoneId, ?float $becs = null, ?float $fecsSaisi = null): array
    {
        $fecsTag = $fecsSaisi !== null ? "<fecs_saisi>$fecsSaisi</fecs_saisi>" : '';
        $becsTag = $becs !== null
            ? "<donnee_intermediaire><besoin_ecs>$becs</besoin_ecs></donnee_intermediaire>"
            : '';
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <enum_methode_application_dpe_log_id>$modeApp</enum_methode_application_dpe_log_id>
    </caracteristique_generale>
    <installation_ecs_collection>
        <installation_ecs>
            <donnee_entree>
                <enum_type_installation_solaire_id>$typeSolaire</enum_type_installation_solaire_id>
                $fecsTag
            </donnee_entree>
            $becsTag
        </installation_ecs>
    </installation_ecs_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $inst = $doc->getElementsByTagName('installation_ecs')->item(0);
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: (string)$zoneId,
        );
        return [$doc, $inst, $ctx];
    }

    public function testAppliesToInstallationEcs(): void
    {
        $calc = new FacteurCouvertureSolaireEcsCalculator();
        $doc  = new DOMDocument();
        $doc->loadXML('<installation_ecs/>');
        $this->assertTrue($calc->appliesTo($doc->documentElement));

        $doc2 = new DOMDocument();
        $doc2->loadXML('<logement/>');
        $this->assertFalse($calc->appliesTo($doc2->documentElement));
    }

    /**
     * Mode 6 immeuble + ECS seule >5 ans + zone H2c (id=6) → fecs_collectif_gt5 = 0.35.
     * (Cas du diag 2631E0794109B vérifié contre LICIEL.)
     */
    public function testEcsSeuleGt5CollectifZoneH2c(): void
    {
        [$doc, $inst, $ctx] = $this->buildInstallation(typeSolaire: 2, modeApp: 6, zoneId: 6, becs: 1135.87);

        (new FacteurCouvertureSolaireEcsCalculator())->calculate($inst, $ctx);

        $fecs = (float)$inst->getElementsByTagName('fecs')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.35, $fecs, 0.001);

        $prod = (float)$inst->getElementsByTagName('production_ecs_solaire')->item(0)->textContent;
        $this->assertEqualsWithDelta(1135.87 * 0.35 * 1000.0, $prod, 1.0);
    }

    /**
     * Mode 1 maison + ECS seule >5 ans + zone H1a → fecs_maison_gt5 = 0.49.
     */
    public function testEcsSeuleGt5MaisonZoneH1a(): void
    {
        [$doc, $inst, $ctx] = $this->buildInstallation(typeSolaire: 2, modeApp: 1, zoneId: 1, becs: 1000.0);

        (new FacteurCouvertureSolaireEcsCalculator())->calculate($inst, $ctx);

        $fecs = (float)$inst->getElementsByTagName('fecs')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.49, $fecs, 0.001);
    }

    /**
     * Mode 1 maison + ECS seule <5 ans + zone H1a → fecs_maison_le5 = 0.63.
     */
    public function testEcsSeuleLe5Maison(): void
    {
        [$doc, $inst, $ctx] = $this->buildInstallation(typeSolaire: 3, modeApp: 1, zoneId: 1, becs: 1000.0);

        (new FacteurCouvertureSolaireEcsCalculator())->calculate($inst, $ctx);

        $fecs = (float)$inst->getElementsByTagName('fecs')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.63, $fecs, 0.001);
    }

    /**
     * fecs_saisi présent → utilisé directement (court-circuite la table).
     */
    public function testFecsSaisiPrioritaire(): void
    {
        [$doc, $inst, $ctx] = $this->buildInstallation(typeSolaire: 2, modeApp: 6, zoneId: 1, becs: 1000.0, fecsSaisi: 0.42);

        (new FacteurCouvertureSolaireEcsCalculator())->calculate($inst, $ctx);

        $fecs = (float)$inst->getElementsByTagName('fecs')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.42, $fecs, 0.001);
    }

    /**
     * Pas de système solaire (typeSolaire absent) → pas d'écriture, fecs reste absent.
     */
    public function testNoSolarTypeIsNoOp(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<installation_ecs><donnee_entree/></installation_ecs>');
        $inst = $doc->documentElement;
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '1',
        );

        (new FacteurCouvertureSolaireEcsCalculator())->calculate($inst, $ctx);

        $this->assertSame(0, $inst->getElementsByTagName('fecs')->length);
    }

    /**
     * typeSolaire=1 (chauffage seul) → fecs ECS = 0 (pas d'apport ECS) → pas d'écriture.
     */
    public function testTypeChauffageSeulOnly(): void
    {
        [$doc, $inst, $ctx] = $this->buildInstallation(typeSolaire: 1, modeApp: 1, zoneId: 1, becs: 1000.0);

        (new FacteurCouvertureSolaireEcsCalculator())->calculate($inst, $ctx);

        // fecs = 0 → on n'écrit pas
        $this->assertSame(0, $inst->getElementsByTagName('fecs')->length);
    }
}
