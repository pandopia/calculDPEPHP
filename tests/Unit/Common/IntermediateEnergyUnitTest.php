<?php

declare(strict_types=1);

namespace Tests\Unit\Common;

use CalculDpePHP\Common\IntermediateEnergyUnit;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Convention d'unité des apports et pertes récupérés selon le format source.
 */
final class IntermediateEnergyUnitTest extends TestCase
{
    private function document(?string $version): DOMDocument
    {
        $attribut = $version === null ? '' : sprintf(' version="%s"', $version);
        $doc = new DOMDocument();
        $doc->loadXML('<dpe' . $attribut . '><logement/></dpe>');

        return $doc;
    }

    /** @return iterable<string, array{?string, float}> */
    public static function formats(): iterable
    {
        // Le format LICIEL 2 est le seul à publier les apports en kWh : ses
        // 444 fichiers du corpus le font, contre aucun des autres formats.
        yield 'format LICIEL 2' => ['2', 1.0];
        yield 'format natif ADEME' => ['0.1.0', 1000.0];
        yield 'export 6.4.1' => ['6.4.1', 1000.0];
        yield 'export 8.0.2' => ['8.0.2', 1000.0];
        yield 'export 8.2.1' => ['8.2.1', 1000.0];
        yield 'export DPEWIN 9.2.2' => ['9.2.2', 1000.0];
        // Un document sans version ne déclare aucune convention : on reste en
        // kWh plutôt que de multiplier par mille à l'aveugle.
        yield 'version absente' => [null, 1.0];
    }

    #[DataProvider('formats')]
    public function testFacteurDeSerialisationDesApports(?string $version, float $attendu): void
    {
        self::assertSame($attendu, IntermediateEnergyUnit::apportXmlPerKwh($this->document($version)));
    }

    /** Les besoins et consommations, eux, ne suivent que le format natif. */
    public function testSeulLeFormatNatifSerialiseLesBesoinsEnWattheures(): void
    {
        self::assertSame(1000.0, IntermediateEnergyUnit::xmlPerKwh($this->document('0.1.0')));
        self::assertSame(1.0, IntermediateEnergyUnit::xmlPerKwh($this->document('9.2.2')));
        self::assertSame(1.0, IntermediateEnergyUnit::xmlPerKwh($this->document('2')));
    }
}
