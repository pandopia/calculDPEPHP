<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpe\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ChaudiereDefautCalculator (§13.2.2 p.86-92).
 */
final class ChaudiereDefautCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 1e-5;

    private function buildGenerator(int $genTypeId, int $tvId, int $methode = 1, ?array $realValues = null): array
    {
        $realTags = '';
        if ($realValues !== null) {
            foreach ($realValues as $k => $v) {
                $realTags .= "<$k>$v</$k>";
            }
        }
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_chauffage>
        <generateur_chauffage_collection>
            <generateur_chauffage>
                <donnee_entree>
                    <enum_type_generateur_ch_id>$genTypeId</enum_type_generateur_ch_id>
                    <tv_generateur_combustion_id>$tvId</tv_generateur_combustion_id>
                    <enum_methode_saisie_carac_sys_id>$methode</enum_methode_saisie_carac_sys_id>
                    $realTags
                </donnee_entree>
            </generateur_chauffage>
        </generateur_chauffage_collection>
    </installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('generateur_chauffage')->item(0);
        return [$doc, $node];
    }

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    /**
     * Chaudière gaz condensation après 2015, Pn=40kW (tv_id=13) — vérifié sur verif post2026.
     */
    public function testCondensationGaz40kw(): void
    {
        [$doc, $node] = $this->buildGenerator(97, 13, 1, ['pn' => 40000]);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $pn    = (float)$doc->getElementsByTagName('pn')->item(0)->textContent;
        $rpn   = (float)$doc->getElementsByTagName('rpn')->item(0)->textContent;
        $rpint = (float)$doc->getElementsByTagName('rpint')->item(0)->textContent;
        $qp0   = (float)$doc->getElementsByTagName('qp0')->item(0)->textContent;

        $this->assertEqualsWithDelta(40000.0, $pn,   1.0);
        $this->assertEqualsWithDelta(0.958062, $rpn, self::TOL);
        $this->assertEqualsWithDelta(1.070051, $rpint, self::TOL);
        $this->assertEqualsWithDelta(200.0, $qp0, 0.1);
    }

    /**
     * Générateurs PAC ou électrique (IDs hors 20-97) → ignorés.
     */
    public function testPacGeneratorIgnored(): void
    {
        [$doc, $node] = $this->buildGenerator(7, 13); // PAC air/eau → pas de combustion
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $pn = $doc->getElementsByTagName('pn')->item(0);
        $this->assertNull($pn); // rien écrit
    }

    /**
     * TV non digitalisé → rien n'est écrit (graceful degradation).
     */
    public function testUnknownTvIdDoesNothing(): void
    {
        [$doc, $node] = $this->buildGenerator(85, 999); // table entry non existante
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $pn = $doc->getElementsByTagName('pn')->item(0);
        $this->assertNull($pn);
    }

    /**
     * Methode=2 (valeurs réelles) → recopie depuis donnee_entree.
     */
    public function testMethode2CopiesRealValues(): void
    {
        [$doc, $node] = $this->buildGenerator(97, 0, 2, [
            'pn' => 35000, 'rpn' => 0.92, 'rpint' => 1.05, 'qp0' => 150, 'pveil' => 0,
        ]);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $pn  = (float)$doc->getElementsByTagName('pn')->item(0)->textContent;
        $rpn = (float)$doc->getElementsByTagName('rpn')->item(0)->textContent;
        $this->assertEqualsWithDelta(35000.0, $pn, 1.0);
        $this->assertEqualsWithDelta(0.92, $rpn, self::TOL);
    }
}
