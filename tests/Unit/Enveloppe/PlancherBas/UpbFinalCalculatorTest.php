<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\PlancherBas;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\PlancherBas\UpbFinalCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /** §3.2.2.1 p.18-19 : Ue utilise 2S/P, arrondi à l'entier, dans les anciens exports. */
    #[DataProvider('geometriesAnciennes')]
    public function testGeometrieDuPlancherDesAnciensExports(
        string $version,
        int $adjacence,
        int $calculUe,
        string $geometrie,
        float $upb,
        float $attendu,
    ): void {
        [$document, $plancher] = $this->makePlancher(
            "<enum_type_adjacence_id>{$adjacence}</enum_type_adjacence_id>"
            . "<calcul_ue>{$calculUe}</calcul_ue>"
            . '<surface_paroi_opaque>211.56</surface_paroi_opaque>'
            . $geometrie,
            $upb,
        );
        $document->documentElement->setAttribute('version', $version);

        (new UpbFinalCalculator())->calculate($plancher, $this->context($document));

        self::assertEqualsWithDelta($attendu, $this->upbFinal($plancher), 1e-10);
    }

    public static function geometriesAnciennes(): iterable
    {
        $perimetre = '<perimetre_ue>57.5</perimetre_ue>';
        // 2 × 211.56 / 57.5 = 7.3586, arrondi à 7 ; interpolation entre Upb 1.43 et 3.33.
        yield 'format 6 et sous-sol' => ['6.0.0', 6, 1, $perimetre, 2.0, 0.346];
        yield 'format 7 et sous-sol' => ['7.0.0', 6, 1, $perimetre, 2.0, 0.346];
        yield 'format 7 et vide sanitaire' => ['7.0.0', 3, 1, $perimetre, 2.0, 0.346];
        yield 'format 7 et terre-plein récent' => ['7.0.0', 5, 1, $perimetre, 1.5, 0.42];
        yield 'surface Ue explicite prioritaire' => ['7.0.0', 6, 1, '<surface_ue>50</surface_ue><perimetre_ue>20</perimetre_ue>', 2.0, 0.366];
        yield 'Ue fourni prioritaire si surface Ue absente' => ['7.0.0', 6, 1, $perimetre . '<ue>0.2</ue>', 2.0, 0.2];
        yield 'format ADEME récent sans surface équivalente' => ['2.6', 6, 1, $perimetre, 2.0, 2.0];
        yield 'calcul Ue désactivé' => ['7.0.0', 6, 0, $perimetre, 2.0, 2.0];
        yield 'périmètre absent' => ['7.0.0', 6, 1, '', 2.0, 2.0];
        yield 'périmètre nul' => ['7.0.0', 6, 1, '<perimetre_ue>0</perimetre_ue>', 2.0, 2.0];
        yield 'plancher extérieur' => ['7.0.0', 1, 1, $perimetre, 2.0, 2.0];
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
