<?php

declare(strict_types=1);

namespace Tests\Unit\Pdf;

use CalculDpePHP\Pdf\Template\TemplateCleaner;
use PHPUnit\Framework\TestCase;

final class TemplateCleanerTest extends TestCase
{
    /** Zone en haut à gauche de la page (origine en haut). */
    private const ZONE = [[0.0, 0.0, 200.0, 100.0]];

    public function testRetireLeTexteDontLOrigineEstDansUneZone(): void
    {
        $stream = "BT\n/F1 9 Tf\n1 0 0 1 50 780 Tm\n(Jean Dupont) Tj\n1 0 0 1 50 400 Tm\n(Texte fixe) Tj\nET\n";

        [$clean, $removed] = TemplateCleaner::cleanStream($stream, self::ZONE, 841.89);

        self::assertSame(1, $removed);
        self::assertStringNotContainsString('Jean Dupont', $clean);
        self::assertStringContainsString('(Texte fixe) Tj', $clean);
        self::assertStringContainsString('1 0 0 1 50 780 Tm', $clean, 'Le positionnement est conservé.');
    }

    public function testSuitLaMatriceCouranteEtLesDeplacements(): void
    {
        // cm décale de 0,700 : Td ramène l'origine à (60, 790) ⇒ dans la zone.
        $stream = "q\n1 0 0 1 0 700 cm\nBT\n10 50 Td\n[<0022>8 <0016>]TJ\n0 -12 TD\n(suite) Tj\nT*\n(fin) '\nET\nQ\nBT\n(dehors) Tj\nET\n";

        [$clean, $removed] = TemplateCleaner::cleanStream($stream, [[0.0, 0.0, 200.0, 800.0]], 841.89);

        self::assertSame(3, $removed);
        self::assertStringContainsString('(dehors) Tj', $clean, 'Origine (0, 0) de la page : hors zone.');
    }

    public function testConserveDictionnairesEtChainesHexadecimales(): void
    {
        $stream = "/Span <</ActualText <feff0009>>>BDC\nEMC\nBT\n1 0 0 1 300 300 Tm\n(a\\)b) Tj\nET\n";

        [$clean, $removed] = TemplateCleaner::cleanStream($stream, self::ZONE, 841.89);

        self::assertSame(0, $removed);
        self::assertStringContainsString('<</ActualText <feff0009>>>', $clean);
        self::assertStringContainsString('(a\\)b) Tj', $clean);
    }
}
