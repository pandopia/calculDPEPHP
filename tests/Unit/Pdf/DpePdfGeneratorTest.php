<?php

declare(strict_types=1);

namespace Tests\Unit\Pdf;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Dto\DiagnostiqueurRapport;
use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\DpePdfGenerator;
use CalculDpePHP\Pdf\Render\Page\Page1Synthese;
use CalculDpePHP\Pdf\Render\Page\Page2Enveloppe;
use CalculDpePHP\Pdf\Render\Page\Page5Travaux;
use CalculDpePHP\Pdf\Render\Page\Page7Annexes;
use CalculDpePHP\Pdf\Render\QrCode;
use CalculDpePHP\Pdf\Template\TemplateCatalog;
use CalculDpePHP\Pdf\Template\TemplateVariant;
use CalculDpePHP\Pdf\Template\UnsupportedTemplateException;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DpePdfGeneratorTest extends TestCase
{
    private static function xml(string $classe = 'E', string $methode = '1'): string
    {
        return str_replace(
            ['<classe_bilan_dpe>E<', '<enum_methode_application_dpe_log_id>1<'],
            ["<classe_bilan_dpe>$classe<", "<enum_methode_application_dpe_log_id>$methode<"],
            (string) file_get_contents(DpeDataTest::FIXTURE),
        );
    }

    private static function pageCount(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page[^s]#', $pdf);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function casProvider(): iterable
    {
        yield 'maison classée E' => ['E', '1'];
        yield 'appartement passoire F' => ['F', '2'];
        yield 'appartement issu d\'immeuble passoire G' => ['G', '10'];
        yield 'immeuble classé A' => ['A', '6'];
    }

    #[DataProvider('casProvider')]
    public function testGenereLeRapportComplet(string $classe, string $methode): void
    {
        $pdf = (new DpePdfGenerator())->generate(self::xml($classe, $methode));

        self::assertStringStartsWith('%PDF-', $pdf);
        // Pages 1 à 7 du modèle ; la page 8 (suite des annexes) seulement si la fiche déborde.
        self::assertSame(7, self::pageCount($pdf));
    }

    public function testDonneesDuRapportEtFacade(): void
    {
        $image = imagecreatetruecolor(40, 30);
        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        $pdf = CalculDpePHP::genererPdf(self::xml(), new DonneesRapportPdf(
            photo: $png,
            logo: $png,
            signature: $png,
            numeroDpe: '2600E9999999Z',
            nomProprietaire: 'Propriétaire Saisi',
            diagnostiqueur: new DiagnostiqueurRapport(
                entreprise: 'Entreprise Saisie',
                adresseOrganismeCertification: '1 rue de la Certification 75000 Paris',
            ),
            explicationsPersonnalisees: 'Explication fictive.',
            commentaires: 'Commentaire fictif.',
        ));

        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertStringContainsString('2600E9999999Z', $pdf, 'Le numéro figure dans le titre du document.');
    }

    /**
     * Sans numéro ADEME (calcul préalable), le rapport ne doit pas pouvoir
     * passer pour un DPE opposable.
     */
    public function testSansNumeroAdemeLeRapportEstMarqueNonOfficiel(): void
    {
        $sansNumero = preg_replace('#<numero_dpe>[^<]*</numero_dpe>#', '', self::xml());
        $pdf = (new DpePdfGenerator())->generate((string) $sansNumero);
        $avecNumero = (new DpePdfGenerator())->generate(self::xml());

        self::assertStringContainsString('document non officiel', self::titre($pdf));
        self::assertStringNotContainsString('document non officiel', self::titre($avecNumero));
        self::assertStringContainsString('/ca 0.22', $pdf, 'Filigrane semi-transparent présent.');
        self::assertStringNotContainsString('/ca 0.22', $avecNumero);
    }

    private static function titre(string $pdf): string
    {
        preg_match('#/Title \(((?:\\.|[^\\)])*)\)#s', $pdf, $m);
        $titre = stripcslashes($m[1] ?? '');

        return str_starts_with($titre, "\xFE\xFF") ? (string) mb_convert_encoding(substr($titre, 2), 'UTF-8', 'UTF-16BE') : $titre;
    }

    /**
     * Un DPE se présente avec le modèle en vigueur à sa date d'établissement ;
     * seule l'édition 2025 porte le cartouche du QR code.
     */
    public function testEditionSelonLaDateDEtablissement(): void
    {
        $edition = static fn (string $date): string => TemplateCatalog::editionPour(new DateTimeImmutable($date));

        self::assertSame(TemplateCatalog::EDITION_2025, $edition('2025-09-01'));
        self::assertSame(TemplateCatalog::EDITION_2024, $edition('2025-08-31'));
        self::assertSame(TemplateCatalog::EDITION_2024, $edition('2024-07-01'));
        self::assertSame(TemplateCatalog::EDITION_2023, $edition('2024-06-30'));
        self::assertSame(TemplateCatalog::EDITION_2023, $edition('2023-01-01'));

        $catalog = TemplateCatalog::default();
        self::assertTrue($catalog->resolve(TemplateVariant::MAISON, new DateTimeImmutable('2026-01-01'))->hasQrCode());
        self::assertFalse($catalog->resolve(TemplateVariant::MAISON, new DateTimeImmutable('2024-01-01'))->hasQrCode());
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function editionsAnterieuresProvider(): iterable
    {
        foreach (['2024-08-15', '2023-03-01'] as $date) {
            foreach (['1', '2', '10', '6'] as $methode) {
                yield "$date méthode $methode" => [$date, $methode];
            }
        }
    }

    #[DataProvider('editionsAnterieuresProvider')]
    public function testGenereAvecLesModelesAnterieurs(string $date, string $methode): void
    {
        $xml = str_replace('2026-03-12', $date, self::xml('E', $methode));
        $pdf = (new DpePdfGenerator())->generate($xml);

        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertSame(7, self::pageCount($pdf));
    }

    public function testRefuseUnDpeAnterieurAuxModelesPrisEnCharge(): void
    {
        $this->expectException(UnsupportedTemplateException::class);
        (new DpePdfGenerator())->generate(str_replace('2026-03-12', '2022-12-31', self::xml()));
    }

    public function testChoixDuModeleSelonLaMethode(): void
    {
        self::assertSame(TemplateVariant::MAISON, TemplateVariant::fromMethodeApplication(1));
        self::assertSame(TemplateVariant::APPARTEMENT, TemplateVariant::fromMethodeApplication(3));
        self::assertSame(TemplateVariant::APPARTEMENT_IMMEUBLE, TemplateVariant::fromMethodeApplication(11));
        self::assertSame(TemplateVariant::IMMEUBLE, TemplateVariant::fromMethodeApplication(9));
        self::assertTrue(TemplateVariant::IMMEUBLE->isBatiment());
        self::assertTrue(TemplateVariant::APPARTEMENT_IMMEUBLE->isAppartement());
    }

    public function testQrCodeVersLObservatoire(): void
    {
        $url = QrCode::urlFor('2674E1068548B');
        $matrix = QrCode::matrix($url);

        self::assertSame('https://observatoire-dpe-audit.ademe.fr/afficher-dpe/2674E1068548B', $url);
        self::assertCount(count($matrix), $matrix[0]);
        self::assertTrue($matrix[0][0], 'Motif de repérage en haut à gauche.');
    }

    public function testRegroupementDesTravauxParLot(): void
    {
        $rows = Page5Travaux::rows([
            ['lot' => 5, 'description' => "Remplacer la chaudière.\nConseil.", 'avertissement' => null, 'performance' => 'SCOP = 4'],
            ['lot' => 5, 'description' => 'Installer des robinets thermostatiques.', 'avertissement' => 'autorisation', 'performance' => null],
            ['lot' => 1, 'description' => 'Isoler les murs.', 'avertissement' => null, 'performance' => 'R = 4'],
        ]);

        self::assertCount(2, $rows);
        self::assertSame('chauffage', $rows[0]['label']);
        self::assertSame('SCOP = 4', $rows[0]['paragraphs'][0]['value']);
        self::assertSame('I', $rows[0]['paragraphs'][1]['style']);
        self::assertTrue($rows[0]['paragraphs'][3]['warning']);
        self::assertSame('murs', $rows[1]['label']);
    }

    public function testConventionsDAffichage(): void
    {
        self::assertSame(['37 Rue des Mathurins,', '75008 PARIS'], Page1Synthese::adresseLignes('37 Rue des Mathurins 75008 PARIS'));
        self::assertSame(['Sans code postal'], Page1Synthese::adresseLignes('Sans code postal'));
        self::assertSame(['Surface du mur', '19,18 m²'], Page7Annexes::split('Surface du mur: 19,18 m²', '19,18 m²'));
        self::assertSame(['Isolation', 'oui'], Page7Annexes::split('Isolation: oui', ''));
        self::assertSame(['a' => 36, 'b' => 26], Page2Enveloppe::pourcentagesArrondis(['a' => 36.1, 'b' => 26.2]));
    }
}
