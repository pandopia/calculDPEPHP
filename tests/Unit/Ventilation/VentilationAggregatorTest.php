<?php

declare(strict_types=1);

namespace Tests\Unit\Ventilation;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Ventilation\VentilationAggregator;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class VentilationAggregatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    public function testNativeAdemeUsesRealConsumptionBehindSentinels(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<dpe version="0.1.0"><logement><caracteristique_generale><surface_habitable_logement>64.56</surface_habitable_logement></caracteristique_generale>
<ventilation><donnee_entree><surface_ventile>64.56</surface_ventile></donnee_entree><donnee_intermediaire><hvent>34.9</hvent><hperm>3.74</hperm><pvent_moy>0</pvent_moy><conso_auxiliaire_ventilation>1</conso_auxiliaire_ventilation></donnee_intermediaire></ventilation></logement></dpe>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $context->set('ventilation.caux_reel', 413.64005184);

        (new VentilationAggregator())->calculate($document->getElementsByTagName('logement')->item(0), $context);

        $value = (float)$document->getElementsByTagName('ef_conso')->item(0)
            ->getElementsByTagName('conso_auxiliaire_ventilation')->item(0)->textContent;
        self::assertEqualsWithDelta(413.64005184, $value, 1e-8);
    }
}
