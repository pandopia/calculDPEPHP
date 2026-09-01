<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\PlancherHaut;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\PlancherHaut\UphCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class UphCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';

    public function testTypeEightAboveHeatedNonLossyRoomUsesTerraceTable(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<plancher_haut><donnee_entree><enum_type_adjacence_id>22</enum_type_adjacence_id><enum_type_plancher_haut_id>8</enum_type_plancher_haut_id><enum_type_isolation_id>9</enum_type_isolation_id><enum_methode_saisie_u_id>2</enum_methode_saisie_u_id></donnee_entree><donnee_intermediaire><uph0>2.5</uph0></donnee_intermediaire></plancher_haut>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneGroupe: 'H2',
            energieChauffagePrincipale: 'autres',
            periodeConstructionId: 6,
        );

        (new UphCalculator())->calculate($document->documentElement, $context);

        self::assertSame('0.42', $document->getElementsByTagName('uph')->item(0)->textContent);
    }
}
