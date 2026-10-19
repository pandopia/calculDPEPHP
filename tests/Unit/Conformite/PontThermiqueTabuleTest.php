<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Un même `tv_pont_thermique_id` publié à la fois nul et non nul.
 *
 * L'identifiant tabulé d'un pont thermique désigne une valeur unique. Un DPE
 * qui lui donne 0,92 à une liaison et 0 à une autre se contredit sur son
 * propre fichier — la preuve tient sans rien recalculer, ce qui est le critère
 * d'imputation à la référence.
 *
 * Vingt fichiers du corpus le font, pour 139 annulations réparties sur 33
 * fichiers au total : les treize autres n'annulent qu'un identifiant qu'ils ne
 * publient nulle part ailleurs, et restent donc à notre charge.
 */
final class PontThermiqueTabuleTest extends TestCase
{
    /** @param list<array{string, string}> $ponts couples (tv_id, k) */
    private function suspects(array $ponts): array
    {
        $blocs = '';
        foreach ($ponts as [$tv, $k]) {
            $blocs .= <<<XML
                <pont_thermique>
                  <donnee_entree><tv_pont_thermique_id>{$tv}</tv_pont_thermique_id></donnee_entree>
                  <donnee_intermediaire><k>{$k}</k></donnee_intermediaire>
                </pont_thermique>
                XML;
        }

        $document = new DOMDocument();
        $document->loadXML(
            '<?xml version="1.0"?><dpe><logement><enveloppe><pont_thermique_collection>'
            . $blocs
            . '</pont_thermique_collection></enveloppe></logement></dpe>',
        );

        return ReferenceDefects::detect([], $document);
    }

    public function testUnIdentifiantPublieNulEtNonNulEstSignale(): void
    {
        $suspects = $this->suspects([['30', '0.92'], ['30', '0'], ['7', '0.71']]);

        $signales = array_filter($suspects, static fn(string $m): bool => str_contains($m, 'tv_pont_thermique_id'));
        self::assertCount(1, $signales, 'seul le k nul du tv 30 doit être imputé');
        self::assertStringContainsString('30', (string) reset($signales));
    }

    /** Un identifiant nul partout ne se contredit pas : la table peut valoir 0. */
    public function testUnIdentifiantToujoursNulNEstPasSignale(): void
    {
        $suspects = $this->suspects([['81', '0'], ['81', '0'], ['30', '0.92']]);

        self::assertSame(
            [],
            array_filter($suspects, static fn(string $m): bool => str_contains($m, 'tv_pont_thermique_id')),
        );
    }

    /** Ni un identifiant toujours non nul. */
    public function testUnIdentifiantToujoursRenseigneNEstPasSignale(): void
    {
        $suspects = $this->suspects([['30', '0.92'], ['30', '0.92']]);

        self::assertSame(
            [],
            array_filter($suspects, static fn(string $m): bool => str_contains($m, 'tv_pont_thermique_id')),
        );
    }
}
