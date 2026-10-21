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

    #[\PHPUnit\Framework\Attributes\DataProvider('reseauxDesservis')]
    public function testMaximumDesTemperaturesDesSeulsReseauxDesservis(array $emetteurs, ?int $lien, int $typeGenerateur, array $attendu): void
    {
        foreach ([false, true] as $inverse) {
            $blocs = '';
            foreach ($inverse ? array_reverse($emetteurs) : $emetteurs as [$type, $periode, $lienEmetteur]) {
                $blocs .= '<emetteur_chauffage><donnee_entree><enum_type_emission_distribution_id>' . $type . '</enum_type_emission_distribution_id>'
                    . '<enum_periode_installation_emetteur_id>' . $periode . '</enum_periode_installation_emetteur_id>'
                    . '<enum_lien_generateur_emetteur_id>' . $lienEmetteur . '</enum_lien_generateur_emetteur_id></donnee_entree></emetteur_chauffage>';
            }
            $document = new DOMDocument();
            $document->loadXML('<logement><installation_chauffage><emetteur_chauffage_collection>' . $blocs . '</emetteur_chauffage_collection>'
                . '<generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>' . $typeGenerateur . '</enum_type_generateur_ch_id>'
                . ($lien === null ? '' : '<enum_lien_generateur_emetteur_id>' . $lien . '</enum_lien_generateur_emetteur_id>')
                . '</donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage></logement>');
            (new ChaudiereProfilChargeCalculator())->calculate($document->getElementsByTagName('generateur_chauffage')->item(0), new CalculationContext(
                $document, new TableRepository(__DIR__ . '/../../../../../resources/tables'),
            ));
            self::assertSame($attendu, [
                $document->getElementsByTagName('temp_fonc_100')->item(0)?->textContent,
                $document->getElementsByTagName('temp_fonc_30')->item(0)?->textContent,
            ]);
        }
    }

    public static function reseauxDesservis(): iterable
    {
        yield 'le poele ne chauffe pas le reseau de la chaudiere' => [[[31, 3, 1], [21, 3, 2]], 1, 97, ['60', '32']];
        yield 'chaque emetteur garde sa periode condensation' => [[[14, 3, 1], [31, 1, 1], [21, 3, 2]], 1, 97, ['80', '38']];
        yield 'chaque emetteur garde sa periode basse temperature' => [[[14, 3, 1], [31, 1, 1], [21, 3, 2]], 1, 81, ['80', '48.5']];
        yield 'pas de lien conserve le maximum global' => [[[31, 3, 1], [21, 3, 2]], null, 97, ['70', '35']];
        yield 'lien inexploitable conserve le maximum global' => [[[31, 3, 1], [21, 3, 2]], 3, 97, ['70', '35']];
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

    /**
     * §13.2.1.5 p.81 tabule les températures de fonctionnement par type de
     * chaudière — à condensation, basse température, standard —, c'est-à-dire
     * les familles gaz et fioul. Une chaudière bois relève de ses propres
     * rendements et n'a pas de température de fonctionnement tabulée : lui en
     * publier une inventait une donnée. Sur les 277 chaudières du corpus, les
     * références en publient pour 275 des 277 gaz/fioul et pour aucune bois.
     */
    public function testWoodBoilerGetsNoOperatingTemperature(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement><installation_chauffage>
  <emetteur_chauffage_collection><emetteur_chauffage><donnee_entree>
    <enum_type_emission_distribution_id>37</enum_type_emission_distribution_id>
    <enum_periode_installation_emetteur_id>2</enum_periode_installation_emetteur_id>
  </donnee_entree></emetteur_chauffage></emetteur_chauffage_collection>
  <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>72</enum_type_generateur_ch_id>
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

        self::assertSame(0, $document->getElementsByTagName('temp_fonc_100')->length);
        self::assertSame(0, $document->getElementsByTagName('temp_fonc_30')->length);
    }

    /** Une chaudière gaz du même fichier en reçoit bien une. */
    public function testGasBoilerStillGetsOneInTheSameShape(): void
    {
        self::assertSame(['70', '35'], $this->tfonc([[32, 2]], periode: 3));
    }

    /** §13.2.1.5 p.81-85 : familles standard, BT et condensation gaz/fioul. */
    #[\PHPUnit\Framework\Attributes\DataProvider('famillesEtPeriodes')]
    public function testLesTablesDeChaqueFamille(int $type, int $periode, int $emetteur, array $attendu): void
    {
        self::assertSame($attendu, $this->tfonc([[$emetteur, null]], $periode, $type));
    }

    public static function famillesEtPeriodes(): iterable
    {
        yield 'standard ancien reseau ancien' => [85, 1, 39, ['80', '59']];
        yield 'standard ancien reseau intermediaire' => [85, 2, 39, ['70', '56']];
        yield 'standard ancien reseau recent' => [85, 3, 39, ['60', '53']];
        yield 'standard recent reseau ancien' => [89, 1, 39, ['80', '55.5']];
        yield 'standard recent reseau intermediaire' => [89, 2, 39, ['70', '52.5']];
        yield 'standard recent reseau recent' => [89, 3, 39, ['60', '49.5']];
        yield 'BT gaz plancher ancien' => [93, 1, 14, ['60', '42.5']];
        yield 'BT gaz plancher intermediaire' => [93, 2, 14, ['35', '35']];
        yield 'BT gaz plancher recent' => [93, 3, 14, ['35', '35']];
        yield 'condensation fioul plancher ancien' => [83, 1, 14, ['60', '32']];
        yield 'condensation fioul plancher recent' => [83, 3, 14, ['35', '24.5']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('anneesEmetteurs')]
    public function testAnneeDuReseauQuandLaPeriodeEstAbsente(int $annee, array $attendu): void
    {
        $document = new DOMDocument();
        $document->loadXML('<logement><installation_chauffage><emetteur_chauffage_collection><emetteur_chauffage><donnee_entree>'
            . '<enum_type_emission_distribution_id>39</enum_type_emission_distribution_id><annee_installation_emetteur>' . $annee . '</annee_installation_emetteur>'
            . '</donnee_entree></emetteur_chauffage></emetteur_chauffage_collection><generateur_chauffage_collection><generateur_chauffage><donnee_entree>'
            . '<enum_type_generateur_ch_id>97</enum_type_generateur_ch_id></donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage></logement>');
        (new ChaudiereProfilChargeCalculator())->calculate($document->getElementsByTagName('generateur_chauffage')->item(0), new CalculationContext(
            $document, new TableRepository(__DIR__ . '/../../../../../resources/tables'),
        ));
        self::assertSame($attendu, [
            $document->getElementsByTagName('temp_fonc_100')->item(0)?->textContent,
            $document->getElementsByTagName('temp_fonc_30')->item(0)?->textContent,
        ]);
    }

    public static function anneesEmetteurs(): iterable
    {
        yield 'avant 1981' => [1980, ['80', '38']];
        yield 'borne 1981' => [1981, ['70', '35']];
        yield 'borne 2000' => [2000, ['70', '35']];
        yield 'apres 2000' => [2001, ['60', '32']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('temperaturesSaisies')]
    public function testLaMethodeCinqPreserveLesTemperaturesDocumentees(int $methode, ?float $cent, ?float $trente, array $attendu): void
    {
        $document = new DOMDocument();
        $document->loadXML('<logement><installation_chauffage><emetteur_chauffage_collection><emetteur_chauffage><donnee_entree>'
            . '<enum_type_emission_distribution_id>39</enum_type_emission_distribution_id><enum_periode_installation_emetteur_id>3</enum_periode_installation_emetteur_id>'
            . '</donnee_entree></emetteur_chauffage></emetteur_chauffage_collection><generateur_chauffage_collection><generateur_chauffage><donnee_entree>'
            . '<enum_type_generateur_ch_id>97</enum_type_generateur_ch_id><enum_methode_saisie_carac_sys_id>' . $methode . '</enum_methode_saisie_carac_sys_id></donnee_entree><donnee_intermediaire>'
            . ($cent === null ? '' : '<temp_fonc_100>' . $cent . '</temp_fonc_100>')
            . ($trente === null ? '' : '<temp_fonc_30>' . $trente . '</temp_fonc_30>')
            . '</donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection></installation_chauffage></logement>');
        \CalculDpePHP\Xml\OutputPurger::purge($document);
        (new ChaudiereProfilChargeCalculator())->calculate($document->getElementsByTagName('generateur_chauffage')->item(0), new CalculationContext(
            $document, new TableRepository(__DIR__ . '/../../../../../resources/tables'),
        ));
        self::assertSame($attendu, [
            $document->getElementsByTagName('temp_fonc_100')->item(0)?->textContent,
            $document->getElementsByTagName('temp_fonc_30')->item(0)?->textContent,
        ]);
    }

    public static function temperaturesSaisies(): iterable
    {
        yield 'deux temperatures saisies' => [5, 44.0, 29.0, ['44', '29']];
        yield 'seul cent est saisi' => [5, 44.0, null, ['44', '32']];
        yield 'seul trente est saisi' => [5, null, 29.0, ['60', '29']];
        yield 'absence de saisie' => [5, null, null, ['60', '32']];
        yield 'methode quatre ne saisit pas les temperatures' => [4, 44.0, 29.0, ['60', '32']];
        yield 'forfaits purgent les anciennes temperatures' => [1, 44.0, 29.0, ['60', '32']];
    }
}
