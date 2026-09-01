<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\BaieVitree;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\BaieVitree\BCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class BCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';

    /**
     * A cfg value for a solar buffer encodes the veranda orientation.  It must
     * take precedence over the geometric orientation of the individual bay.
     */
    public function testUsesSolarBufferOrientationEncodedInConfiguration(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<?xml version="1.0"?>
<logement>
  <baie_vitree>
    <donnee_entree>
      <enum_type_adjacence_id>10</enum_type_adjacence_id>
      <enum_cfg_isolation_lnc_id>9</enum_cfg_isolation_lnc_id>
      <enum_orientation_id>3</enum_orientation_id>
    </donnee_entree>
  </baie_vitree>
</logement>
XML);

        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '1',
        );
        $bay = $document->getElementsByTagName('baie_vitree')->item(0);

        (new BCalculator())->calculate($bay, $context);

        $this->assertSame('0.85', $document->getElementsByTagName('b')->item(0)->textContent);
    }
}
