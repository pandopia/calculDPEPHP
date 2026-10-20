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

    /** §17.2 : seule la génération depuis un DPE immeuble utilise une clé immeuble → logement. */
    #[DataProvider('methodesEtCles')]
    public function testLaCleDeChauffageDependDuPerimetre(?int $methode, float $cle, float $attendu): void
    {
        $document = new DOMDocument();
        $mode = $methode === null ? '' : '<enum_methode_application_dpe_log_id>' . $methode . '</enum_methode_application_dpe_log_id>';
        $document->loadXML('<logement><caracteristique_generale>' . $mode
            . '<surface_habitable_logement>50</surface_habitable_logement><surface_habitable_immeuble>1000</surface_habitable_immeuble>'
            . '</caracteristique_generale></logement>');

        self::assertSame($attendu, GeneratedApartment::heatingShare($document->documentElement, new NodeAccessor($document), $cle));
    }

    public static function methodesEtCles(): iterable
    {
        foreach ([1, 2, 3, 4, 5, 14, 18, 31, 32, 35, 36, 37] as $mode) {
            yield 'calcul direct ' . $mode => [$mode, 0.2134016, 1.0];
        }
        foreach ([10, 11, 12, 13, 33, 34, 38, 39, 40] as $mode) {
            yield 'genere avec cle ' . $mode => [$mode, 0.015, 0.015];
            yield 'genere sans cle utile ' . $mode => [$mode, 1.0, 0.05];
        }
        yield 'document minimal sans methode' => [null, 0.2, 0.2];
    }
}
