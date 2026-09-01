<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class RendementAnnuelMoyenCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';

    public function testComputesSeparateDepensierLoadProfileAt21Degrees(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement>
  <caracteristique_generale>
    <enum_methode_application_dpe_log_id>2</enum_methode_application_dpe_log_id>
    <enum_zone_climatique_id>5</enum_zone_climatique_id>
    <enum_classe_altitude_id>1</enum_classe_altitude_id>
  </caracteristique_generale>
  <installation_chauffage><generateur_chauffage_collection><generateur_chauffage>
    <donnee_entree>
      <enum_type_generateur_ch_id>93</enum_type_generateur_ch_id>
      <reference>gen-1</reference>
      <enum_type_energie_id>2</enum_type_energie_id>
      <presence_regulation_combustion>1</presence_regulation_combustion>
    </donnee_entree>
    <donnee_intermediaire>
      <pn>23000</pn><rpn>0.895425917540264</rpn><rpint>0.895425917540264</rpint>
      <qp0>324.442078172763</qp0><pveil>0</pveil>
      <temp_fonc_30>45.5</temp_fonc_30><temp_fonc_100>70</temp_fonc_100>
    </donnee_intermediaire>
  </generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $context->set('chauffage.gv', 102.070658177748);

        (new RendementAnnuelMoyenCalculator())->calculate(
            $document->getElementsByTagName('generateur_chauffage')->item(0),
            $context,
        );

        $di = $document->getElementsByTagName('donnee_intermediaire')->item(0);
        $nominal = (float)$di->getElementsByTagName('rendement_generation')->item(0)->textContent;
        $depensier = (float)$context->get('chauffage.rendement_generation_depensier')['gen-1'];
        self::assertEqualsWithDelta(0.74299, $nominal, 1e-4);
        self::assertEqualsWithDelta(0.75257, $depensier, 1e-4);
        self::assertGreaterThan($nominal, $depensier);
    }
}
