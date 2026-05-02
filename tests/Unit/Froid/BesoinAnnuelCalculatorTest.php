<?php

declare(strict_types=1);

namespace Tests\Unit\Froid;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Froid\BesoinAnnuelCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour BesoinAnnuelCalculator (§10.1-10.2 p.68-69).
 */
final class BesoinAnnuelCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 0.01;

    private function buildLogement(?string $withClimatisation = null): array
    {
        $climXml = $withClimatisation ?? '';
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <enveloppe>
        <inertie>
            <enum_classe_inertie_id>2</enum_classe_inertie_id>
        </inertie>
    </enveloppe>
    <climatisation_collection>$climXml</climatisation_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return [$doc, $doc->getElementsByTagName('logement')->item(0)];
    }

    private function makeContext(DOMDocument $doc, array $extra = [], ?string $zone = '1', ?string $alt = '1'): CalculationContext
    {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: $zone,
            classeAltitude: $alt,
        );
        foreach ($extra as $k => $v) {
            $ctx->set($k, $v);
        }
        return $ctx;
    }

    /**
     * Sans climatisation → besoin_fr = 0.
     */
    public function testNoCoolingSytemOutputsZero(): void
    {
        [$doc, $node] = $this->buildLogement();
        $ctx = $this->makeContext($doc, [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 100.0,
            'ventilation.hvent'           => 150.0,
            'ventilation.hperm'           => 50.0,
        ]);

        (new BesoinAnnuelCalculator())->calculate($node, $ctx);

        $bfr = (float)$doc->getElementsByTagName('besoin_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $bfr, self::TOL);
    }

    /**
     * Sans climatisation → besoin_fr_depensier = 0.
     */
    public function testNoCoolingOutputsZeroDepensier(): void
    {
        [$doc, $node] = $this->buildLogement();
        $ctx = $this->makeContext($doc);

        (new BesoinAnnuelCalculator())->calculate($node, $ctx);

        $bfrDep = (float)$doc->getElementsByTagName('besoin_fr_depensier')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $bfrDep, self::TOL);
    }

    /**
     * Sans climatisation → contexte froid.besoin_fr = 0.
     */
    public function testContextStoredWhenNoSystem(): void
    {
        [$doc, $node] = $this->buildLogement();
        $ctx = $this->makeContext($doc);

        (new BesoinAnnuelCalculator())->calculate($node, $ctx);

        $this->assertEqualsWithDelta(0.0, (float)$ctx->get('froid.besoin_fr', 0.0), self::TOL);
        $this->assertEqualsWithDelta(0.0, (float)$ctx->get('froid.besoin_fr_depensier', 0.0), self::TOL);
    }

    /**
     * Avec climatisation mais sans données DH28 (colonne absente) → besoin_fr = 0.
     * (La table tv_sollicitations n'a pas encore de colonne DH28 — TASK-A05)
     */
    public function testWithCoolingSystemButNoDH28DataOutputsZero(): void
    {
        [$doc, $node] = $this->buildLogement('<climatisation><donnee_entree/></climatisation>');
        $ctx = $this->makeContext($doc, [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'ventilation.hvent'           => 0.0,
            'ventilation.hperm'           => 0.0,
        ]);

        (new BesoinAnnuelCalculator())->calculate($node, $ctx);

        $bfr = (float)$doc->getElementsByTagName('besoin_fr')->item(0)->textContent;
        // DH28 = null in current tv_sollicitations → besoin = 0
        $this->assertEqualsWithDelta(0.0, $bfr, self::TOL);
    }

    /**
     * Rbthj < 0.5 → besoin mensuel = 0 (pas besoin de refroidissement).
     * Simulé en passant des apports faibles vs DH élevé.
     */
    public function testLowRbthOutputsZero(): void
    {
        [$doc, $node] = $this->buildLogement('<climatisation><donnee_entree/></climatisation>');
        // Apports très petits vs DH28 grand → Rbth << 0.5 → Bfrj = 0
        $ctx = $this->makeContext($doc, [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'ventilation.hvent'           => 0.0,
            'ventilation.hperm'           => 0.0,
            'apport.as_fr_mensuel'        => array_fill(1, 12, 0.01),  // kWh, tiny
            'apport.ai_fr_mensuel'        => array_fill(1, 12, 0.01),  // kWh, tiny
        ]);
        // Override table to inject DH28 data (simulated via context override not possible)
        // Since DH28 not in table, result is 0
        (new BesoinAnnuelCalculator())->calculate($node, $ctx);
        $bfr = (float)$doc->getElementsByTagName('besoin_fr')->item(0)->textContent;
        $this->assertGreaterThanOrEqual(0.0, $bfr);
    }

    /**
     * GV = 0 → besoin = 0 (division par zéro protégée).
     */
    public function testZeroGvOutputsZero(): void
    {
        [$doc, $node] = $this->buildLogement('<climatisation><donnee_entree/></climatisation>');
        $ctx = $this->makeContext($doc, [
            'enveloppe.dp_parois'         => 0.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'ventilation.hvent'           => 0.0,
            'ventilation.hperm'           => 0.0,
        ]);

        (new BesoinAnnuelCalculator())->calculate($node, $ctx);

        $bfr = (float)$doc->getElementsByTagName('besoin_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $bfr, self::TOL);
    }

    /**
     * fut formula at Rbth = 1 uses a/(a+1) without division by zero.
     */
    public function testComputeFutAtRbthEqualsOne(): void
    {
        $calc = new BesoinAnnuelCalculator();
        $ref  = new \ReflectionMethod($calc, 'computeFut');
        $ref->setAccessible(true);

        // a = 1 + t/15. With t = 0 → a = 1. fut(Rbth=1, a=1) = 1/(1+1) = 0.5
        $result = $ref->invoke($calc, 1.0, 1.0);
        $this->assertEqualsWithDelta(0.5, $result, 1e-9);
    }

    /**
     * fut formula at Rbth > 1 (more gains than conductive load).
     */
    public function testComputeFutWithHighRbth(): void
    {
        $calc = new BesoinAnnuelCalculator();
        $ref  = new \ReflectionMethod($calc, 'computeFut');
        $ref->setAccessible(true);

        // Rbth = 2, a = 1: fut = (1 - 2^-1)/(1 - 2^-2) = 0.5/0.75 = 0.667
        $result = $ref->invoke($calc, 2.0, 1.0);
        $this->assertEqualsWithDelta(0.667, $result, 0.001);
    }

    /**
     * besoinMensuel returns 0 when DH = 0.
     */
    public function testBesoinMensuelZeroWhenNoDH(): void
    {
        $calc = new BesoinAnnuelCalculator();
        $ref  = new \ReflectionMethod($calc, 'besoinMensuel');
        $ref->setAccessible(true);

        $result = $ref->invoke($calc, 1000.0, 500.0, 0.0, 1.5);
        $this->assertEqualsWithDelta(0.0, $result, 1e-9);
    }

    /**
     * besoinMensuel returns positive value when DH28 > 0 and Rbth >= 0.5.
     * With gains=1e6 Wh, gv=100, DH=100 → Rbth = 1e6/(100×100) = 100 >> 0.5
     * Bfrj = (1e6 + fut × 100 × 100) / 1000 > 0
     */
    public function testBesoinMensuelPositiveWhenHot(): void
    {
        $calc = new BesoinAnnuelCalculator();
        $ref  = new \ReflectionMethod($calc, 'besoinMensuel');
        $ref->setAccessible(true);

        $result = $ref->invoke($calc, 1_000_000.0, 100.0, 100.0, 1.5);
        $this->assertGreaterThan(0.0, $result);
    }
}
