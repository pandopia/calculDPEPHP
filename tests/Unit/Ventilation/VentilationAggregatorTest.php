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

    /**
     * `sortie/ef_conso/conso_auxiliaire_ventilation` est recalculée en pleine
     * précision depuis `pvent_moy` (§5 p.41), et non recopiée de la valeur
     * arrondie de `donnee_intermediaire`.
     */
    public function testConsommationRecalculeeDepuisPventMoy(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<dpe version="2"><logement><caracteristique_generale><surface_habitable_logement>64.56</surface_habitable_logement></caracteristique_generale>
<ventilation><donnee_entree><surface_ventile>64.56</surface_ventile></donnee_entree><donnee_intermediaire><hvent>34.9</hvent><hperm>3.74</hperm><pvent_moy>47.219184</pvent_moy><conso_auxiliaire_ventilation>414</conso_auxiliaire_ventilation></donnee_intermediaire></ventilation></logement></dpe>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );

        (new VentilationAggregator())->calculate($document->getElementsByTagName('logement')->item(0), $context);

        $value = (float) $document->getElementsByTagName('ef_conso')->item(0)
            ->getElementsByTagName('conso_auxiliaire_ventilation')->item(0)->textContent;
        self::assertEqualsWithDelta(413.64005184, $value, 1e-8);
    }

    /**
     * Sans `pvent_moy` (ventilation naturelle chez LICIEL, balise omise), la
     * copie `donnee_intermediaire` sert de repli.
     */
    public function testRepliSurLaCopieDonneeIntermediaire(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<dpe version="2"><logement><caracteristique_generale><surface_habitable_logement>64.56</surface_habitable_logement></caracteristique_generale>
<ventilation><donnee_entree><surface_ventile>64.56</surface_ventile></donnee_entree><donnee_intermediaire><hvent>34.9</hvent><hperm>3.74</hperm><conso_auxiliaire_ventilation>123</conso_auxiliaire_ventilation></donnee_intermediaire></ventilation></logement></dpe>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );

        (new VentilationAggregator())->calculate($document->getElementsByTagName('logement')->item(0), $context);

        $value = (float) $document->getElementsByTagName('ef_conso')->item(0)
            ->getElementsByTagName('conso_auxiliaire_ventilation')->item(0)->textContent;
        self::assertEqualsWithDelta(123.0, $value, 1e-8);
    }
}
