<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
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
     * Methode=2 (XSD : « pn, autres données forfaitaires ») → pn saisi est repris,
     * rpn/rpint/qp0 sont calculés par les formules forfaitaires de la table.
     * Cas réel 2662E2147774H : chaudière gaz standard 2001-2015 (tv_id=5), pn=25 kW
     *   rpn = (84 + 2·log10(25))/100 = 0.867959 ; qp0 = 1 % × Pn = 250 W.
     */
    public function testMethode2PnSaisiAutresForfaitaires(): void
    {
        [$doc, $node] = $this->buildGenerator(89, 5, 2, ['pn' => 25000]);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $get = fn(string $tag): float => (float)$di->getElementsByTagName($tag)->item(0)->textContent;
        $this->assertEqualsWithDelta(25000.0, $get('pn'), 1.0);
        $this->assertEqualsWithDelta((84.0 + 2.0 * log10(25.0)) / 100.0, $get('rpn'), self::TOL);
        $this->assertEqualsWithDelta((80.0 + 3.0 * log10(25.0)) / 100.0, $get('rpint'), self::TOL);
        $this->assertEqualsWithDelta(250.0, $get('qp0'), self::TOL);
    }

    /**
     * Methode=2 avec pn stocké en donnee_intermediaire (convention LICIEL,
     * préservé par OutputPurger) → même résultat que pn en donnee_entree.
     */
    public function testMethode2PnFromDonneeIntermediaire(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_chauffage>
        <generateur_chauffage_collection>
            <generateur_chauffage>
                <donnee_entree>
                    <enum_type_generateur_ch_id>89</enum_type_generateur_ch_id>
                    <tv_generateur_combustion_id>5</tv_generateur_combustion_id>
                    <enum_methode_saisie_carac_sys_id>2</enum_methode_saisie_carac_sys_id>
                </donnee_entree>
                <donnee_intermediaire>
                    <pn>25000</pn>
                </donnee_intermediaire>
            </generateur_chauffage>
        </generateur_chauffage_collection>
    </installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('generateur_chauffage')->item(0);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $get = fn(string $tag): float => (float)$di->getElementsByTagName($tag)->item(0)->textContent;
        $this->assertEqualsWithDelta(25000.0, $get('pn'), 1.0);
        $this->assertEqualsWithDelta((84.0 + 2.0 * log10(25.0)) / 100.0, $get('rpn'), self::TOL);
        $this->assertEqualsWithDelta(250.0, $get('qp0'), self::TOL);
    }

    /**
     * Methode=2 sans table digitalisée → seuls les champs saisis sont recopiés.
     */
    public function testMethode2SansTableRecopieSaisie(): void
    {
        [$doc, $node] = $this->buildGenerator(97, 999, 2, ['pn' => 35000]);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $this->assertNotNull($di);
        $pn = $di->getElementsByTagName('pn')->item(0);
        $this->assertNotNull($pn);
        $this->assertEqualsWithDelta(35000.0, (float)$pn->textContent, 1.0);
        $this->assertNull($di->getElementsByTagName('rpn')->item(0));
    }
}
