<?php

declare(strict_types=1);

namespace Tests\Unit\Ventilation;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Ventilation\ConsoAuxiliaireVentilationCalculator;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class ConsoAuxiliaireVentilationCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    public function testNativeAdemeWritesSentinelsAndKeepsRealConsumptionInContext(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<dpe version="0.1.0"><ventilation><donnee_entree/><donnee_intermediaire><pvent_moy>47.219184</pvent_moy></donnee_intermediaire></ventilation></dpe>');
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );

        (new ConsoAuxiliaireVentilationCalculator())->calculate(
            $document->getElementsByTagName('ventilation')->item(0),
            $context,
        );

        self::assertSame('0', $document->getElementsByTagName('pvent_moy')->item(0)->textContent);
        self::assertSame('1', $document->getElementsByTagName('conso_auxiliaire_ventilation')->item(0)->textContent);
        self::assertEqualsWithDelta(413.64005184, (float)$context->get('ventilation.caux_reel'), 1e-8);
    }
}
