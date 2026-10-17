<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * §4 p.44 : la valeur forfaitaire de Q4Pa_conv ne s'emploie qu'à défaut de
 * mesure. Déclarer une mesure de moins de deux ans, en donner la valeur, puis
 * publier le forfait est une contradiction du fichier seul.
 */
final class MesureEtancheiteTest extends TestCase
{
    private function document(string $methode, string $saisi, string $publie): DOMDocument
    {
        $doc = new DOMDocument();
        $doc->loadXML(sprintf(
            '<dpe><logement><ventilation_collection><ventilation>'
            . '<donnee_entree><enum_methode_saisie_q4pa_conv_id>%s</enum_methode_saisie_q4pa_conv_id>'
            . '<q4pa_conv_saisi>%s</q4pa_conv_saisi></donnee_entree>'
            . '<donnee_intermediaire><q4pa_conv>%s</q4pa_conv></donnee_intermediaire>'
            . '</ventilation></ventilation_collection></logement></dpe>',
            $methode,
            $saisi,
            $publie,
        ));

        return $doc;
    }

    /** 2313E3593911Y déclare 1,700 mesuré et publie le forfait 1,500. */
    public function testUneMesureIgnoreeEstImputeeALaReference(): void
    {
        $suspects = ReferenceDefects::detect([], $this->document('2', '1.700', '1.500'));

        self::assertArrayHasKey('q4pa_conv', $suspects);
        self::assertStringContainsString('1.700', $suspects['q4pa_conv']);
    }

    /** Une mesure publiée telle quelle ne déclenche rien. */
    public function testUneMesurePublieeNEstPasSuspecte(): void
    {
        self::assertArrayNotHasKey('q4pa_conv', ReferenceDefects::detect([], $this->document('2', '1.700', '1.700')));
    }

    /** Sans méthode de saisie mesurée, le forfait est légitime. */
    public function testLaSaisieForfaitaireNEstPasSuspecte(): void
    {
        self::assertArrayNotHasKey('q4pa_conv', ReferenceDefects::detect([], $this->document('1', '1.700', '1.500')));
    }
}
