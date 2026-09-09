<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\PlancherBas;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\PlancherBas\UpbFinalCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

final class UpbFinalCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';

    /** §3.2.2.1 p.18-19 et XSD : le Ue fourni remplace Upb si la géométrie manque. */
    public function testProvidedUeIsUsedWhenReconstructionGeometryIsIncomplete(): void
    {
        [$document, $plancher] = $this->makePlancher(<<<'XML'
            <enum_type_adjacence_id>5</enum_type_adjacence_id>
            <calcul_ue>1</calcul_ue>
            <perimetre_ue>18.45</perimetre_ue>
            <ue>0.17333</ue>
        XML, 0.23);

        (new UpbFinalCalculator())->calculate($plancher, $this->context($document));

        self::assertSame(0.17333, $this->upbFinal($plancher));
    }

    /** Sans Ue fourni, un plancher extérieur conserve Upb. */
    public function testExteriorFloorFallsBackToUpb(): void
    {
        [$document, $plancher] = $this->makePlancher(<<<'XML'
            <enum_type_adjacence_id>1</enum_type_adjacence_id>
            <calcul_ue>0</calcul_ue>
        XML, 0.31);

        (new UpbFinalCalculator())->calculate($plancher, $this->context($document));

        self::assertSame(0.31, $this->upbFinal($plancher));
    }

    /** @return array{DOMDocument, DOMElement} */
    private function makePlancher(string $entree, float $upb): array
    {
        $document = new DOMDocument();
        $document->loadXML("<plancher_bas><donnee_entree>{$entree}</donnee_entree><donnee_intermediaire><upb>{$upb}</upb></donnee_intermediaire></plancher_bas>");
        $plancher = $document->documentElement;
        self::assertInstanceOf(DOMElement::class, $plancher);

        return [$document, $plancher];
    }

    private function context(DOMDocument $document): CalculationContext
    {
        return new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            periodeConstructionId: 9,
        );
    }

    private function upbFinal(DOMElement $plancher): float
    {
        $nodes = $plancher->getElementsByTagName('upb_final');
        self::assertSame(1, $nodes->length);

        return (float)$nodes->item(0)?->textContent;
    }
}
