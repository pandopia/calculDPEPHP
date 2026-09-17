<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement\Combustion;

use CalculDpePHP\Chauffage\Rendement\Combustion\PuissanceDimensionnement;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pdim et Pn des générateurs à combustion (§13.2.2.4 p.91-92).
 */
final class PuissanceDimensionnementTest extends TestCase
{
    /** @return iterable<string, array{float, float}> */
    public static function pecsProvider(): iterable
    {
        yield 'instantanee'            => [0.0,    21_000.0];
        yield 'semi-instantanee 10 L'  => [10.0,   13_000.0];
        yield 'jonction Vs = 20 L'     => [20.0,    5_000.0];
        yield 'semi-accumulation 85 L' => [85.0,    3_249.0];
        yield 'jonction Vs = 150 L'    => [150.0,   1_498.0];
        yield 'accumulation 500 L'     => [500.0,   3_998.0];
    }

    #[DataProvider('pecsProvider')]
    public function testPecs(float $vs, float $expected): void
    {
        self::assertEqualsWithDelta($expected, PuissanceDimensionnement::pecsW($vs), 1.0);
    }

    /**
     * Les quatre branches de Pecs se raccordent aux deux jonctions du tableau
     * — exactement à 20 L, à 1 W près à 150 L — ce qui valide la lecture des
     * bornes et, avec elles, celle de la branche semi-accumulation, seule
     * formule que l'extraction du PDF rendait douteuse.
     */
    public function testPecsIsContinuousAtBothJunctions(): void
    {
        self::assertEqualsWithDelta(
            PuissanceDimensionnement::pecsW(20.0),
            PuissanceDimensionnement::pecsW(20.0001),
            1.0,
        );
        self::assertEqualsWithDelta(
            PuissanceDimensionnement::pecsW(150.0),
            PuissanceDimensionnement::pecsW(150.0001),
            1.1,
        );
    }

    /** @return iterable<string, array{float, bool, float}> */
    public static function pnProvider(): iterable
    {
        yield 'sur sol, Pdim 4 kW'   => [4.0,   false, 18.0];
        yield 'murale 2006, 4 kW'    => [4.0,   true,   5.0];
        yield 'murale 2006, 12 kW'   => [12.0,  true,  13.0];
        yield 'sur sol, 12 kW'       => [12.0,  false, 18.0];
        yield 'borne 18 kW'          => [18.0,  false, 18.0];
        yield 'Pecs instantanee'     => [21.0,  false, 24.0];
        yield 'borne 40 kW'          => [40.0,  false, 40.0];
        yield 'au-dela : 40,1 kW'    => [40.1,  false, 45.0];
        yield 'au-dela : 52,12 kW'   => [52.12, false, 55.0];
        yield 'au-dela : 400 kW'     => [400.0, false, 405.0];
    }

    #[DataProvider('pnProvider')]
    public function testPnFromPdim(float $pdim, bool $post2006, float $expected): void
    {
        self::assertEqualsWithDelta($expected, PuissanceDimensionnement::pnFromPdimKw($pdim, $post2006), 1e-9);
    }

    public function testDataComplementairesNeedsBothMuraleAndYear(): void
    {
        self::assertTrue(self::gen(null, '1', '2010'));
        self::assertFalse(self::gen(null, '1', '2004'));
        self::assertFalse(self::gen(null, '0', '2010'));
        self::assertFalse(self::gen(null, '1', ''));
        self::assertFalse(self::gen(null, null, null));
    }

    /**
     * Les attributs data_complementaires n'appartiennent ni à la méthode ni au
     * schéma ADEME et manquent sur une partie du corpus. Le millésime porté par
     * le libellé XSD du type de générateur suffit alors : « chaudière gaz à
     * condensation après 2015 » (97) est installée après 2006.
     */
    public function testXsdVintageAloneOpensTheSecondColumn(): void
    {
        self::assertTrue(self::gen(97, null, null));   // gaz condensation après 2015
        self::assertTrue(self::gen(80, null, null));   // fioul standard après 2015
        self::assertFalse(self::gen(89, null, null));  // gaz standard 2001-2015
        self::assertFalse(self::gen(85, null, null));  // gaz classique avant 1981
    }

    /** Un millésime 2001-2015 reste éligible via les attributs du logiciel. */
    public function testVintageAndDataComplementairesAreAlternatives(): void
    {
        self::assertTrue(self::gen(89, '1', '2010'));
    }

    private static function gen(?int $typeId, ?string $murale, ?string $annee): bool
    {
        $doc = new DOMDocument();
        $type = $typeId === null
            ? ''
            : "<enum_type_generateur_ch_id>$typeId</enum_type_generateur_ch_id>";
        $attrs = $murale === null
            ? ''
            : sprintf(
                '<data_complementaires data-chaudiere-murale="%s" data-annee-installation="%s"/>',
                $murale,
                $annee ?? '',
            );
        $doc->loadXML("<generateur_chauffage><donnee_entree>$type$attrs</donnee_entree></generateur_chauffage>");
        $node = $doc->documentElement;
        self::assertInstanceOf(DOMElement::class, $node);

        return PuissanceDimensionnement::isChaudierePost2006($node);
    }
}
