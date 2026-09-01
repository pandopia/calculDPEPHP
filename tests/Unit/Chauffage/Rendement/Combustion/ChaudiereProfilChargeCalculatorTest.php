<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement\Combustion;

use CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereProfilChargeCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

final class ChaudiereProfilChargeCalculatorTest extends TestCase
{
    public function testUsesRecentEmitterPeriodWhenOptionalPeriodAndYearAreMissing(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<dpe><logement>
  <caracteristique_generale><annee_construction>1920</annee_construction></caracteristique_generale>
  <installation_chauffage><emetteur_chauffage_collection><emetteur_chauffage><donnee_entree>
    <enum_temp_distribution_ch_id>3</enum_temp_distribution_ch_id>
  </donnee_entree></emetteur_chauffage></emetteur_chauffage_collection>
  <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>97</enum_type_generateur_ch_id>
    <presence_regulation_combustion>0</presence_regulation_combustion>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement></dpe>
XML);

        $generator = $document->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $generator);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(__DIR__ . '/../../../../../resources/tables'),
        );

        (new ChaudiereProfilChargeCalculator())->calculate($generator, $context);

        self::assertSame('60', $document->getElementsByTagName('temp_fonc_100')->item(0)?->textContent);
        self::assertSame('32', $document->getElementsByTagName('temp_fonc_30')->item(0)?->textContent);
    }

    public function testExplicitEmitterPeriodStillTakesPriority(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement><installation_chauffage>
  <emetteur_chauffage_collection><emetteur_chauffage><donnee_entree>
    <enum_temp_distribution_ch_id>3</enum_temp_distribution_ch_id>
    <enum_periode_installation_emetteur_id>1</enum_periode_installation_emetteur_id>
  </donnee_entree></emetteur_chauffage></emetteur_chauffage_collection>
  <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>97</enum_type_generateur_ch_id>
    <presence_regulation_combustion>1</presence_regulation_combustion>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection>
</installation_chauffage></logement>
XML);
        $generator = $document->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $generator);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(__DIR__ . '/../../../../../resources/tables'),
        );

        (new ChaudiereProfilChargeCalculator())->calculate($generator, $context);

        self::assertSame('80', $document->getElementsByTagName('temp_fonc_100')->item(0)?->textContent);
        self::assertSame('38', $document->getElementsByTagName('temp_fonc_30')->item(0)?->textContent);
    }
}
