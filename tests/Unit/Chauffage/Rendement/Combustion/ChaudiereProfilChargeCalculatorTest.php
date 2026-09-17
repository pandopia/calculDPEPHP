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
    /**
     * @param list<array{int|null, int|null}> $emetteurs couples (type d'émission, température déclarée)
     * @return array{string, string} (temp_fonc_100, temp_fonc_30)
     */
    private function tfonc(array $emetteurs, int $periode, int $genId = 97): array
    {
        $blocs = '';
        foreach ($emetteurs as [$type, $temp]) {
            $blocs .= '<emetteur_chauffage><donnee_entree>'
                . ($type !== null ? "<enum_type_emission_distribution_id>$type</enum_type_emission_distribution_id>" : '')
                . ($temp !== null ? "<enum_temp_distribution_ch_id>$temp</enum_temp_distribution_ch_id>" : '')
                . "<enum_periode_installation_emetteur_id>$periode</enum_periode_installation_emetteur_id>"
                . '</donnee_entree></emetteur_chauffage>';
        }
        $document = new DOMDocument();
        $document->loadXML(<<<XML
<logement><installation_chauffage>
  <emetteur_chauffage_collection>$blocs</emetteur_chauffage_collection>
  <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>$genId</enum_type_generateur_ch_id>
    <presence_regulation_combustion>1</presence_regulation_combustion>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection>
</installation_chauffage></logement>
XML);
        $generator = $document->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $generator);
        (new ChaudiereProfilChargeCalculator())->calculate($generator, new CalculationContext(
            document: $document,
            tables: new TableRepository(__DIR__ . '/../../../../../resources/tables'),
        ));

        return [
            (string)$document->getElementsByTagName('temp_fonc_100')->item(0)?->textContent,
            (string)$document->getElementsByTagName('temp_fonc_30')->item(0)?->textContent,
        ];
    }

    /**
     * §13.2.1.5 p.81 : la ligne « Basse » du tableau Tfonc est celle des
     * planchers et plafonds basse température, pas celle de tout émetteur
     * déclaré en basse température. Un radiateur bitube sur réseau à moins de
     * 65 °C (type 33) est un radiateur à chaleur douce : ligne « Moyenne »,
     * donc 80 °C avant 1981 et non 60 °C — même quand
     * enum_temp_distribution_ch_id vaut 2.
     */
    public function testLowTemperatureRadiatorUsesTheMediumRow(): void
    {
        self::assertSame(['80', '38'], $this->tfonc([[33, 2]], periode: 1));
        self::assertSame(['60', '32'], $this->tfonc([[14, 2]], periode: 1));
    }

    /** Un émetteur sur réseau haute température relève d'« Autres émetteurs ». */
    public function testHighTemperatureNetworkUsesTheHighRow(): void
    {
        self::assertSame(['70', '35'], $this->tfonc([[32, 2]], periode: 3));
        self::assertSame(['60', '32'], $this->tfonc([[33, 2]], periode: 3));
    }

    /**
     * « Si un système de génération alimente des réseaux de distribution de
     * températures différentes, la température de fonctionnement est prise
     * égale à la température maximale. »
     */
    public function testSeveralNetworksKeepTheHighestTemperature(): void
    {
        self::assertSame(['70', '35'], $this->tfonc([[14, 2], [33, 2], [32, 4]], periode: 3));
    }

    /** Sans type d'émission, la température déclarée sert de repli. */
    public function testFallsBackToDeclaredDistributionTemperature(): void
    {
        self::assertSame(['35', '24.5'], $this->tfonc([[null, 2]], periode: 3));
        self::assertSame(['70', '35'], $this->tfonc([[null, 4]], periode: 3));
    }

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
