<?php

declare(strict_types=1);

namespace Tests\Unit\Ventilation;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Ventilation\Q4PaConvCalculator;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** §4 p.39 : forfaits et exceptions de perméabilité, avec ou sans ID de table. */
final class Q4PaConvCalculatorTest extends TestCase
{
    private function calculate(string $general, string $ventilation = '', string $enveloppe = ''): ?float
    {
        $document = new DOMDocument();
        $document->loadXML('<dpe><logement><caracteristique_generale>' . $general
            . '</caracteristique_generale><enveloppe>' . $enveloppe
            . '</enveloppe><ventilation_collection><ventilation><donnee_entree>'
            . $ventilation . '</donnee_entree></ventilation></ventilation_collection></logement></dpe>');
        $node = $document->getElementsByTagName('ventilation')->item(0);
        self::assertInstanceOf(DOMElement::class, $node);
        (new Q4PaConvCalculator())->calculate($node, new CalculationContext(
            document: $document,
            tables: new TableRepository(__DIR__ . '/../../../resources/tables'),
        ));
        $values = (new DOMXPath($document))->query('//ventilation/donnee_intermediaire/q4pa_conv');
        return $values->length === 0 ? null : (float) $values->item(0)->textContent;
    }

    private static function general(int $mode, int $periode): string
    {
        return "<enum_methode_application_dpe_log_id>$mode</enum_methode_application_dpe_log_id>"
            . "<enum_periode_construction_id>$periode</enum_periode_construction_id>";
    }

    /** L'intégralité des dix périodes et des quarante méthodes du XSD. */
    public static function forfaits(): iterable
    {
        $maison = [1 => 3.3, 2.2, 1.9, 1.9, 1.9, 1.9, 1.9, 1.3, 0.6, 0.6];
        $collectif = [1 => 4.6, 2.0, 1.5, 1.5, 1.5, 1.5, 1.5, 1.5, 1.0, 1.0];
        foreach (range(1, 40) as $mode) {
            foreach (range(1, 10) as $periode) {
                yield "méthode $mode période $periode" => [$mode, $periode, (in_array($mode, [1, 14, 18], true) ? $maison : $collectif)[$periode]];
            }
        }
    }

    #[DataProvider('forfaits')]
    public function testForfaitSansIdCouvreToutesLesPeriodesEtMethodes(int $mode, int $periode, float $expected): void
    {
        self::assertSame($expected, $this->calculate(self::general($mode, $periode)));
    }

    public static function annees(): iterable
    {
        foreach ([1947 => [3.3, 4.6], 1948 => [2.2, 2.0], 1974 => [2.2, 2.0], 1975 => [1.9, 1.5],
            2005 => [1.9, 1.5], 2006 => [1.3, 1.5], 2012 => [1.3, 1.5], 2013 => [0.6, 1.0]] as $annee => $values) {
            yield (string) $annee => [$annee, ...$values];
        }
    }

    #[DataProvider('annees')]
    public function testAnneeConnueEtBornesDeLaTable(int $annee, float $maison, float $appartement): void
    {
        $year = "<annee_construction>$annee</annee_construction>";
        self::assertSame($maison, $this->calculate(self::general(1, 10) . $year));
        self::assertSame($appartement, $this->calculate(self::general(3, 10) . $year));
    }

    public static function saisies(): iterable
    {
        yield 'mesure récente' => [2];
        yield 'étude thermique' => [3];
    }

    #[DataProvider('saisies')]
    public function testSaisieJustifieePrimeSurLaTableEtLeBatiment(int $methode): void
    {
        self::assertSame(0.8, $this->calculate(self::general(1, 1),
            "<enum_methode_saisie_q4pa_conv_id>$methode</enum_methode_saisie_q4pa_conv_id>"
            . '<q4pa_conv_saisi>0,8</q4pa_conv_saisi><tv_q4pa_conv_id>1</tv_q4pa_conv_id>'));
    }

    public function testValeurSaisieEnMethodeForfaitaireNestPasUneMesure(): void
    {
        self::assertSame(2.0, $this->calculate(self::general(3, 2),
            '<enum_methode_saisie_q4pa_conv_id>1</enum_methode_saisie_q4pa_conv_id><q4pa_conv_saisi>1</q4pa_conv_saisi>'));
    }

    public function testIdExistantEtSaisieJustifieeManquanteConserventLeForfaitDeclare(): void
    {
        self::assertSame(2.5, $this->calculate('',
            '<enum_methode_saisie_q4pa_conv_id>2</enum_methode_saisie_q4pa_conv_id><tv_q4pa_conv_id>12</tv_q4pa_conv_id>'));
    }

    public function testIdInconnuNeBloquePasLesParametresPhysiques(): void
    {
        self::assertSame(1.3, $this->calculate(self::general(1, 8), '<tv_q4pa_conv_id>999</tv_q4pa_conv_id>'));
    }

    private static function paroi(string $type, float $surface, int $isolation, float $b = 1.0, int $adjacence = 1): string
    {
        return "<{$type}_collection><$type><donnee_entree><surface_paroi_opaque>$surface</surface_paroi_opaque>"
            . "<enum_type_isolation_id>$isolation</enum_type_isolation_id><enum_type_adjacence_id>$adjacence</enum_type_adjacence_id>"
            . "</donnee_entree><donnee_intermediaire><b>$b</b></donnee_intermediaire></$type></{$type}_collection>";
    }

    public function testIsolationStrictementMajoritaireEtNonDeperditifsExclus(): void
    {
        $majorite = self::paroi('mur', 51, 3) . self::paroi('plancher_haut', 49, 2)
            . self::paroi('mur', 1000, 2, 0) . self::paroi('mur', 1000, 2, 1, 22);
        self::assertSame(2.0, $this->calculate(self::general(1, 1), '', $majorite));
        self::assertSame(1.9, $this->calculate(self::general(3, 2), '', $majorite));
        $egalite = self::paroi('mur', 50, 9) . self::paroi('plancher_haut', 50, 1);
        self::assertSame(4.6, $this->calculate(self::general(3, 1), '', $egalite));
        self::assertSame(2.0, $this->calculate(self::general(3, 2), '', $egalite));
    }

    private static function menuiserie(string $type, float $surface, ?int $joint, float $b = 1.0): string
    {
        $champ = $type === 'porte' ? 'surface_porte' : 'surface_totale_baie';
        $jointXml = $joint !== null ? "<presence_joint>$joint</presence_joint>" : '';
        return "<{$type}_collection><$type><donnee_entree><$champ>$surface</$champ>$jointXml"
            . "</donnee_entree><donnee_intermediaire><b>$b</b></donnee_intermediaire></$type></{$type}_collection>";
    }

    public function testJointsStrictementMajoritairesSurLesBaiesEtPortes(): void
    {
        $joints = self::menuiserie('baie_vitree', 51, 1) . self::menuiserie('porte', 49, 0)
            . self::menuiserie('porte', 1000, 0, 0);
        self::assertSame(2.5, $this->calculate(self::general(1, 1), '', $joints));
        self::assertSame(2.5, $this->calculate(self::general(3, 1), '', $joints));
        self::assertSame(2.0, $this->calculate(self::general(1, 1), '', $joints . self::paroi('mur', 100, 4)));
        self::assertSame(2.2, $this->calculate(self::general(1, 2), '', $joints));
        $egalite = self::menuiserie('porte', 50, 1) . self::menuiserie('baie_vitree', 50, null);
        self::assertSame(3.3, $this->calculate(self::general(1, 1), '', $egalite));
    }

    public function testPasDeForfaitInventeQuandLeBatimentEstIndetermine(): void
    {
        self::assertNull($this->calculate(''));
        self::assertNull($this->calculate(self::general(0, 1)));
        self::assertNull($this->calculate(self::general(41, 1)));
        self::assertNull($this->calculate(self::general(1, 0)));
        self::assertNull($this->calculate(self::general(1, 11)));
        self::assertNull($this->calculate('<enum_methode_application_dpe_log_id>1</enum_methode_application_dpe_log_id>'));
        self::assertSame(1.9, $this->calculate(self::general(1, 7) . '<annee_construction>0</annee_construction>'));
    }

    public function testVentilationSansLogementNeFabriquePasDeForfait(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<ventilation><donnee_entree/></ventilation>');
        (new Q4PaConvCalculator())->calculate($document->documentElement, new CalculationContext(
            $document, new TableRepository(__DIR__ . '/../../../resources/tables'),
        ));
        self::assertSame(0, $document->getElementsByTagName('q4pa_conv')->length);
    }

    public function testVentilationSansDonneesEntreeSignaleUneErreur(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<ventilation/>');
        $this->expectException(RuntimeException::class);
        (new Q4PaConvCalculator())->calculate($document->documentElement, new CalculationContext(
            $document, new TableRepository(__DIR__ . '/../../../resources/tables'),
        ));
    }
}
