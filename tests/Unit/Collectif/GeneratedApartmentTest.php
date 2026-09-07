<?php

declare(strict_types=1);

namespace Tests\Unit\Collectif;

use CalculDpePHP\Collectif\GeneratedApartment;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GeneratedApartmentTest extends TestCase
{
    /** @return iterable<string, array{int, float}> */
    public static function surfaceCases(): iterable
    {
        yield 'appartement généré chauffage mixte ECS collectif' => [34, 16696.0];
        yield 'appartement autonome' => [2, 68.0];
    }

    #[DataProvider('surfaceCases')]
    public function testResolvesRegulatoryCalculationSurface(int $method, float $expected): void
    {
        $document = new DOMDocument();
        $document->loadXML(sprintf(<<<'XML'
<dpe><logement><caracteristique_generale>
  <enum_methode_application_dpe_log_id>%d</enum_methode_application_dpe_log_id>
  <surface_habitable_logement>68</surface_habitable_logement>
  <surface_habitable_immeuble>16696</surface_habitable_immeuble>
</caracteristique_generale><ventilation_collection><ventilation/></ventilation_collection></logement></dpe>
XML, $method));
        $ventilation = $document->getElementsByTagName('ventilation')->item(0);
        self::assertInstanceOf(DOMElement::class, $ventilation);

        $accessor = new NodeAccessor($document);
        self::assertSame($expected, GeneratedApartment::calculationSurface($ventilation, $accessor, 68.0));
        self::assertSame($method === 34, GeneratedApartment::isGenerated(
            $document->getElementsByTagName('logement')->item(0),
            $accessor,
        ));
        self::assertSame(
            $method === 34 ? 68.0 / 16696.0 : null,
            GeneratedApartment::surfaceShare($document->getElementsByTagName('logement')->item(0), $accessor),
        );
    }
}
