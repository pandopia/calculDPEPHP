<?php

declare(strict_types=1);

namespace Tests\Unit\Dto;

use CalculDpePHP\Dto\DiagnostiqueurRapport;
use CalculDpePHP\Dto\DonneesRapportPdf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DonneesRapportPdfTest extends TestCase
{
    private static function png(): string
    {
        ob_start();
        imagepng(imagecreatetruecolor(4, 4));

        return (string) ob_get_clean();
    }

    public function testValeursParDefaut(): void
    {
        $donnees = new DonneesRapportPdf();

        self::assertNull($donnees->photo);
        self::assertSame(DonneesRapportPdf::URL_GUIDE_PEDAGOGIQUE, $donnees->urlGuidePedagogique);
        self::assertNull($donnees->diagnostiqueur->entreprise);
    }

    public function testAccepteImagesEtNumero(): void
    {
        $donnees = new DonneesRapportPdf(photo: self::png(), numeroDpe: '2674E1068548B');

        self::assertSame('2674E1068548B', $donnees->numeroDpe);
    }

    public function testRefuseUneImageNonSupportee(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DonneesRapportPdf(logo: 'GIF89a pas une image');
    }

    public function testRefuseUnNumeroDpeMalForme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DonneesRapportPdf(numeroDpe: '2674-E10');
    }

    public function testRefuseUneUrlInvalide(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DonneesRapportPdf(urlGuidePedagogique: 'www.exemple.fr');
    }

    public function testImageIllisible(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DonneesRapportPdf::image('/chemin/inexistant.png');
    }

    public function testDiagnostiqueurCompleteLeXml(): void
    {
        $xml = ['entreprise' => 'Du XML', 'nom' => 'Alex Testeur', 'telephone' => null, 'organisme' => 'Org XML'];
        $complete = (new DiagnostiqueurRapport(entreprise: 'Saisie', telephone: ' 01 02 ', organismeCertification: ''))->completer($xml);

        self::assertSame('Saisie', $complete['entreprise']);
        self::assertSame('Alex Testeur', $complete['nom'], 'Champ non renseigné : valeur du XML.');
        self::assertSame('01 02', $complete['telephone']);
        self::assertSame('Org XML', $complete['organisme'], 'Chaîne vide : valeur du XML.');
    }
}
