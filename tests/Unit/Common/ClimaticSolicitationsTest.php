<?php

declare(strict_types=1);

namespace Tests\Unit\Common;

use CalculDpePHP\Common\ClimaticSolicitations;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class ClimaticSolicitationsTest extends TestCase
{
    private function context(int $ancientMaterials): CalculationContext
    {
        $document = new DOMDocument();
        $document->loadXML(sprintf(
            '<dpe><logement><meteo><batiment_materiaux_anciens>%d</batiment_materiaux_anciens></meteo></logement></dpe>',
            $ancientMaterials,
        ));
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(__DIR__ . '/../../../resources/tables'),
        );
        return $context;
    }

    public function testSelectsHeavySolicitationsForAncientMaterials(): void
    {
        $months = ClimaticSolicitations::heating($this->context(1), 1, 1);

        self::assertSame(172, $months[5]['Nref19']);
        self::assertSame(1069.9, $months[5]['DH19']);
    }

    public function testSelectsStandardSolicitationsForNonAncientMaterials(): void
    {
        $months = ClimaticSolicitations::heating($this->context(3), 1, 1);

        self::assertSame(2762.0, $months[5]['DH19']);
    }

    public function testUnknownZoneReturnsEmptyMonths(): void
    {
        self::assertSame([], ClimaticSolicitations::heating($this->context(1), 99, 1));
    }
}
