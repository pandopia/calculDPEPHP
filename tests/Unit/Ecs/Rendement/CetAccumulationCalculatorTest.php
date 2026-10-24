<?php

declare(strict_types=1);

namespace Tests\Unit\Ecs\Rendement;

use CalculDpePHP\Ecs\Rendement\CetAccumulationCalculator;
use CalculDpePHP\Ecs\Rendement\CombustionCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class CetAccumulationCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 1e-9;

    #[\PHPUnit\Framework\Attributes\DataProvider('copDepuis2027')]
    public function testLesNouveauxCetConserventUnCopStockageInclus(int $type, string $zone, float $expected, bool $saisi): void
    {
        [$doc, $node] = $this->buildNode($type);
        $entry = $doc->getElementsByTagName('donnee_entree')->item(0);
        $entry->appendChild($doc->createElement('enum_type_energie_id', '1'));
        if ($saisi) {
            $entry->appendChild($doc->createElement('enum_methode_saisie_carac_sys_id', '6'));
            $di = $doc->createElement('donnee_intermediaire');
            $di->appendChild($doc->createElement('cop', '4.7'));
            $node->appendChild($di);
            $expected = 4.7;
        }
        $context = $this->makeContext($doc, $zone);
        (new CetAccumulationCalculator())->calculate($node, $context);
        (new CombustionCalculator())->calculate($node, $context);
        self::assertEqualsWithDelta($expected, (float)$doc->getElementsByTagName('cop')->item(0)?->textContent, 1e-12);
        self::assertSame(0, $doc->getElementsByTagName('rendement_generation')->length);
        self::assertSame(0, $doc->getElementsByTagName('rendement_stockage')->length);
    }

    public static function copDepuis2027(): iterable
    {
        // ADEME valeur_tables.xlsx 05aabba7, scop lignes 108 à 113.
        foreach ([135 => [2.7, 3.0], 136 => [2.7, 3.0], 137 => [3.0, 3.1], 138 => [2.8, 3.1]] as $type => [$cold, $warm]) {
            foreach (range(1, 8) as $zone) {
                foreach ([false, true] as $saisi) {
                    yield "$type zone $zone, saisie " . (int)$saisi => [$type, (string)$zone, $zone === 8 ? $warm : $cold, $saisi];
                }
            }
        }
    }

    public function testAutreThermodynamiquePeriode2027UtiliseLeCopDoubleServiceRecent(): void
    {
        [$doc, $node] = $this->buildNode(82);
        $doc->getElementsByTagName('donnee_entree')->item(0)->appendChild($doc->createElement('enum_periode_installation_ecs_thermo_id', '4'));
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '1'));
        self::assertSame('2.8', $doc->getElementsByTagName('cop')->item(0)?->textContent);
    }

    private function buildNode(int $typeId): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <generateur_ecs>
        <donnee_entree>
            <enum_type_generateur_ecs_id>$typeId</enum_type_generateur_ecs_id>
        </donnee_entree>
    </generateur_ecs>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('generateur_ecs')->item(0);
        return [$doc, $node];
    }

    private function makeContext(DOMDocument $doc, string $zone = '1'): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: $zone,
        );
    }

    /** §14.2 — CET air ambiant avant 2010, zone H1 → COP = 2.0 */
    public function testAmbiantAvant2010H1(): void
    {
        [$doc, $node] = $this->buildNode(1);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '1'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.0, $cop, self::TOL);
        // Le COP inclut le rendement de stockage (XSD) : aucun rendement de
        // génération séparé n'est publié pour un ballon intégré.
        $this->assertSame(0, $doc->getElementsByTagName('rendement_generation')->length);
    }

    /** §14.2 — CET air extérieur 2010-2014, zone H2 → COP = 2.2 */
    public function testExterieur2010_2014H2(): void
    {
        [$doc, $node] = $this->buildNode(5);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '5'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.2, $cop, self::TOL);
    }

    /** §14.2 — CET air extrait à partir 2015, zone H1 → COP = 2.8 */
    public function testAirExtraitApres2015H1(): void
    {
        [$doc, $node] = $this->buildNode(9);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '2'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.8, $cop, self::TOL);
    }

    /** §14.2 — PAC double service avant 2010, zone H3 → COP = 2.3 */
    public function testPacDoubleServiceAvant2010H3(): void
    {
        [$doc, $node] = $this->buildNode(10);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '8'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.3, $cop, self::TOL);
    }

    /** §14.2 — PAC double service à partir 2015, zone H3 → COP = 2.6 */
    public function testPacDoubleServiceApres2015H3(): void
    {
        [$doc, $node] = $this->buildNode(12);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc, '8'));
        $cop = (float)$doc->getElementsByTagName('cop')->item(0)?->textContent;
        $this->assertEqualsWithDelta(2.6, $cop, self::TOL);
    }

    /** ID hors plage (chaudière gaz) → aucun champ écrit */
    public function testNonCetTypeSkipped(): void
    {
        [$doc, $node] = $this->buildNode(15);
        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc));
        $this->assertNull($doc->getElementsByTagName('cop')->item(0));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('copSaisis')]
    public function testLeCopSaisiEstPreserveSansRecopierUneAncienneSortie(int $methode, ?float $cop, ?float $copEntree, float $attendu): void
    {
        [$doc, $node] = $this->buildNode(12);
        $de = $node->getElementsByTagName('donnee_entree')->item(0);
        $de->appendChild($doc->createElement('enum_methode_saisie_carac_sys_id', (string)$methode));
        if ($copEntree !== null) {
            $de->appendChild($doc->createElement('cop', (string)$copEntree));
        }
        $di = $doc->createElement('donnee_intermediaire');
        if ($cop !== null) {
            $di->appendChild($doc->createElement('cop', (string)$cop));
        }
        $node->appendChild($di);
        \CalculDpePHP\Xml\OutputPurger::purge($doc);

        (new CetAccumulationCalculator())->calculate($node, $this->makeContext($doc));

        self::assertEqualsWithDelta($attendu, (float)(new \DOMXPath($doc))->evaluate('string(//generateur_ecs/donnee_intermediaire/cop)'), self::TOL);
        self::assertSame(0, $node->getElementsByTagName('rendement_generation')->length);
    }

    public static function copSaisis(): iterable
    {
        yield 'COP saisi propre au CET' => [6, 3.14, null, 3.14];
        yield 'entree prioritaire' => [6, 3.14, 3.5, 3.5];
        yield 'saisie absente' => [6, null, null, 2.3];
        yield 'saisie invalide' => [6, 0.0, null, 2.3];
        yield 'forfait ne recopie pas un ancien resultat' => [1, 3.14, null, 2.3];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('autresSystemesThermodynamiques')]
    public function testLAliasThermodynamiqueNeDevientPasUnBallonJoule(int $type, ?int $periode, float $cop): void
    {
        $document = new DOMDocument();
        $document->loadXML('<logement><installation_ecs><donnee_intermediaire><besoin_ecs>1000</besoin_ecs><besoin_ecs_depensier>1200</besoin_ecs_depensier>'
            . '<rendement_distribution>1</rendement_distribution></donnee_intermediaire><generateur_ecs_collection><generateur_ecs><donnee_entree>'
            . '<enum_type_energie_id>1</enum_type_energie_id><enum_type_generateur_ecs_id>' . $type . '</enum_type_generateur_ecs_id><volume_stockage>200</volume_stockage>'
            . ($periode === null ? '' : '<enum_periode_installation_ecs_thermo_id>' . $periode . '</enum_periode_installation_ecs_thermo_id>')
            . '</donnee_entree></generateur_ecs></generateur_ecs_collection></installation_ecs></logement>');
        $generator = $document->getElementsByTagName('generateur_ecs')->item(0);
        $context = $this->makeContext($document);
        (new \CalculDpePHP\Ecs\Rendement\StockageCalculator())->calculate($generator, $context);
        (new CetAccumulationCalculator())->calculate($generator, $context);
        (new CombustionCalculator())->calculate($generator, $context);
        (new \CalculDpePHP\Ecs\ConsoEcsCalculator())->calculate($document->getElementsByTagName('installation_ecs')->item(0), $context);

        $xp = new \DOMXPath($document);
        self::assertEqualsWithDelta($cop, (float)$xp->evaluate('string(//generateur_ecs/donnee_intermediaire/cop)'), self::TOL);
        self::assertEqualsWithDelta(1000 / $cop, (float)$xp->evaluate('string(//generateur_ecs/donnee_intermediaire/conso_ecs)'), self::TOL);
        self::assertEqualsWithDelta(1200 / $cop, (float)$xp->evaluate('string(//generateur_ecs/donnee_intermediaire/conso_ecs_depensier)'), self::TOL);
        self::assertSame(0, $generator->getElementsByTagName('rendement_generation')->length);
        self::assertSame(0, $generator->getElementsByTagName('rendement_stockage')->length);
    }

    public static function autresSystemesThermodynamiques(): iterable
    {
        yield 'alias avant 2010' => [82, 1, 2.0];
        yield 'alias 2010-2014' => [82, 2, 2.1];
        yield 'alias apres 2015' => [82, 3, 2.3];
        yield 'alias periode inconnue penalisee' => [82, null, 2.0];
        yield 'PAC double service native' => [12, 3, 2.3];
    }
}
