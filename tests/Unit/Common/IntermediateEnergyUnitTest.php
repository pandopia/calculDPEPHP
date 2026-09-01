<?php

declare(strict_types=1);

namespace Tests\Unit\Common;

use CalculDpePHP\Common\IntermediateEnergyUnit;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IntermediateEnergyUnitTest extends TestCase
{
    /** @return iterable<string, array{string, float}> */
    public static function formats(): iterable
    {
        yield 'ADEME natif' => ['0.1.0', 1000.0];
        yield 'LICIEL historique' => ['2', 1.0];
        yield 'format sans version' => ['', 1.0];
    }

    #[DataProvider('formats')]
    public function testXmlUnitsPerKwh(string $version, float $expected): void
    {
        $document = new DOMDocument();
        $document->loadXML('<dpe/>');
        if ($version !== '') {
            $document->documentElement->setAttribute('version', $version);
        }

        self::assertSame($expected, IntermediateEnergyUnit::xmlPerKwh($document));
    }

    public function testDpewinUsesWhOnlyForApportsAndAscendingEnergyOrder(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<dpe version="9.2.2"/>');

        self::assertSame(1.0, IntermediateEnergyUnit::xmlPerKwh($document));
        self::assertSame(1000.0, IntermediateEnergyUnit::apportXmlPerKwh($document));
        self::assertTrue(IntermediateEnergyUnit::usesAscendingEnergyOrder($document));
        self::assertTrue(IntermediateEnergyUnit::usesRoundedGesTotal($document));
    }
}
