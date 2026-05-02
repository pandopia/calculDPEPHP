<?php

declare(strict_types=1);

namespace Tests\Unit\Ecs\Rendement;

use CalculDpe\Ecs\Rendement\CombustionCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour CombustionCalculator ECS (§14.1 p.93-95).
 */
final class CombustionCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 0.0001;

    private function buildElectricGen(int $genTypeId = 71, float $becsKwh = 22349.057): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_ecs>
        <donnee_intermediaire>
            <besoin_ecs>$becsKwh</besoin_ecs>
        </donnee_intermediaire>
        <generateur_ecs_collection>
            <generateur_ecs>
                <donnee_entree>
                    <enum_type_energie_id>1</enum_type_energie_id>
                    <enum_type_generateur_ecs_id>$genTypeId</enum_type_generateur_ecs_id>
                </donnee_entree>
            </generateur_ecs>
        </generateur_ecs_collection>
    </installation_ecs>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $gen = $doc->getElementsByTagName('generateur_ecs')->item(0);
        return [$doc, $gen];
    }

    private function buildCombustionGen(
        int $tvId,
        float $becsKwh,
        int $methode = 1,
        ?float $pn = null,
        ?float $rpn = null,
        ?float $qp0 = null,
    ): array {
        $extraTags = '';
        if ($pn !== null) {
            $extraTags .= "<pn>$pn</pn>";
        }
        if ($rpn !== null) {
            $extraTags .= "<rpn>$rpn</rpn>";
        }
        if ($qp0 !== null) {
            $extraTags .= "<qp0>$qp0</qp0>";
        }

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_ecs>
        <donnee_intermediaire>
            <besoin_ecs>$becsKwh</besoin_ecs>
        </donnee_intermediaire>
        <generateur_ecs_collection>
            <generateur_ecs>
                <donnee_entree>
                    <enum_type_energie_id>2</enum_type_energie_id>
                    <enum_type_generateur_ecs_id>84</enum_type_generateur_ecs_id>
                    <tv_generateur_combustion_id>$tvId</tv_generateur_combustion_id>
                    <enum_methode_saisie_carac_sys_id>$methode</enum_methode_saisie_carac_sys_id>
                    $extraTags
                </donnee_entree>
            </generateur_ecs>
        </generateur_ecs_collection>
    </installation_ecs>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $gen = $doc->getElementsByTagName('generateur_ecs')->item(0);
        return [$doc, $gen];
    }

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    /**
     * Générateur électrique → Rg = 1.
     */
    public function testElectricGeneratorRgEqualsOne(): void
    {
        [$doc, $node] = $this->buildElectricGen(71);
        $ctx = $this->makeContext($doc);
        (new CombustionCalculator())->calculate($node, $ctx);

        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.0, $rg, self::TOL);
    }

    /**
     * Condensation gaz ≥ 2016, Pn=40kW, tv_id=13 :
     *   Rpn = 0.958062, Qp0 = 200W, Pveil = 0
     *   Rg = 1 / (1/0.958062 + 1790×200 / 22349057) ≈ 0.943581
     */
    public function testCondensationGaz2016MatchesVerif(): void
    {
        [$doc, $node] = $this->buildCombustionGen(13, 22349.057, pn: 40000.0);
        $ctx = $this->makeContext($doc);
        (new CombustionCalculator())->calculate($node, $ctx);

        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.943581, $rg, 0.0001);
    }

    /**
     * Les champs pn, qp0, rpn sont écrits dans donnee_intermediaire.
     */
    public function testCombustionFieldsWrittenToDonneeIntermediaire(): void
    {
        [$doc, $node] = $this->buildCombustionGen(13, 22349.057, pn: 40000.0);
        $ctx = $this->makeContext($doc);
        (new CombustionCalculator())->calculate($node, $ctx);

        $gen = $doc->getElementsByTagName('generateur_ecs')->item(0);
        $di  = null;
        foreach ($gen->childNodes as $c) {
            if ($c instanceof \DOMElement && $c->nodeName === 'donnee_intermediaire') {
                $di = $c;
                break;
            }
        }
        $this->assertNotNull($di);
        $pn  = $di->getElementsByTagName('pn')->item(0);
        $qp0 = $di->getElementsByTagName('qp0')->item(0);
        $rpn = $di->getElementsByTagName('rpn')->item(0);

        $this->assertNotNull($pn);
        $this->assertEqualsWithDelta(40000.0, (float)$pn->textContent, 1.0);
        $this->assertNotNull($qp0);
        $this->assertEqualsWithDelta(200.0, (float)$qp0->textContent, 0.1);
        $this->assertNotNull($rpn);
        $this->assertEqualsWithDelta(0.958062, (float)$rpn->textContent, self::TOL);
    }

    /**
     * Rg augmente avec Becs (moins de pertes relatives à l'arrêt).
     */
    public function testLargerBecsGivesHigherRg(): void
    {
        [$doc1, $node1] = $this->buildCombustionGen(13, 5000.0, pn: 40000.0);
        [$doc2, $node2] = $this->buildCombustionGen(13, 50000.0, pn: 40000.0);
        $ctx1 = $this->makeContext($doc1);
        $ctx2 = $this->makeContext($doc2);
        (new CombustionCalculator())->calculate($node1, $ctx1);
        (new CombustionCalculator())->calculate($node2, $ctx2);

        $rg1 = (float)$doc1->getElementsByTagName('rendement_generation')->item(0)->textContent;
        $rg2 = (float)$doc2->getElementsByTagName('rendement_generation')->item(0)->textContent;
        $this->assertGreaterThan($rg1, $rg2);
    }

    /**
     * Méthode 2+ : lire pn/rpn/qp0 directement depuis donnee_entree.
     */
    public function testDirectInputMethodUsesProvidedValues(): void
    {
        [$doc, $node] = $this->buildCombustionGen(13, 22349.057, 4, 40000.0, 0.958062, 200.0);
        $ctx = $this->makeContext($doc);
        (new CombustionCalculator())->calculate($node, $ctx);

        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.943581, $rg, 0.0001);
    }

    /**
     * Rg < 1 toujours pour générateur combustion avec Becs fini.
     */
    public function testCombustionRgStrictlyLessThanOne(): void
    {
        [$doc, $node] = $this->buildCombustionGen(13, 22349.057, pn: 40000.0);
        $ctx = $this->makeContext($doc);
        (new CombustionCalculator())->calculate($node, $ctx);

        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)->textContent;
        $this->assertLessThan(1.0, $rg);
        $this->assertGreaterThan(0.0, $rg);
    }

    /**
     * Rg stocké dans le contexte.
     */
    public function testContextStoredCorrectly(): void
    {
        [$doc, $node] = $this->buildCombustionGen(13, 22349.057, pn: 40000.0);
        $ctx = $this->makeContext($doc);
        (new CombustionCalculator())->calculate($node, $ctx);

        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)->textContent;
        $this->assertEqualsWithDelta($rg, (float)$ctx->get('ecs.rendement_generation', 0.0), self::TOL);
    }

    /**
     * tv_generateur_combustion_id absent → Rg = 1 (fallback sans données).
     */
    public function testMissingTableEntryFallback(): void
    {
        // tv_id=99 n'existe pas dans la table
        [$doc, $node] = $this->buildCombustionGen(99, 22349.057);
        $ctx = $this->makeContext($doc);
        (new CombustionCalculator())->calculate($node, $ctx);

        $rg = (float)$doc->getElementsByTagName('rendement_generation')->item(0)->textContent;
        // Rpn=0 → dénominateur infini → Rg=1 (convention sécurisée)
        $this->assertEqualsWithDelta(1.0, $rg, self::TOL);
    }
}
