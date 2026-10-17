<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * §3.2.1.2 p.16 : « pour l'ensemble des parois, la présence d'un doublage
 * apporte une résistance thermique supplémentaire ». Avec Rdoublage = 0,21 et
 * une table dont la plus grande valeur est 3,90 W/(m².K), aucun `umur0` ne
 * peut dépasser `1 / (1/3,90 + 0,21) = 2,144`.
 */
final class DoublageMurIgnoreTest extends TestCase
{
    private function mur(int $doublage, int $methode, float $umur0): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML(<<<XML
<dpe><logement><enveloppe><mur_collection><mur>
  <donnee_entree>
    <enum_type_doublage_id>$doublage</enum_type_doublage_id>
    <enum_methode_saisie_u0_id>$methode</enum_methode_saisie_u0_id>
  </donnee_entree>
  <donnee_intermediaire><umur0>$umur0</umur0></donnee_intermediaire>
</mur></mur_collection></enveloppe></logement></dpe>
XML);

        return $document;
    }

    /** @return iterable<string, array{int}> */
    public static function doublagesA021(): iterable
    {
        yield 'lame d\'air de plus de 15 mm' => [4];
        yield 'matériau de doublage connu' => [5];
    }

    /**
     * 2601E0076852C : blocs de béton creux de 20 cm, 2,80 tabulés, doublage
     * déclaré, et la référence publie 2,5 — le plafond des parois inconnues,
     * donc sans la résistance.
     */
    #[DataProvider('doublagesA021')]
    public function testUnUmur0AuDessusDeLaBorneEstSignale(int $doublage): void
    {
        $suspects = ReferenceDefects::detect([], $this->mur($doublage, 2, 2.5));

        self::assertArrayHasKey('umur0', $suspects);
        self::assertStringContainsString('2,144', str_replace('.', ',', $suspects['umur0']));
    }

    /** Sous la borne, rien n'est démontrable — le pisé à 1,55 du même fichier. */
    public function testUnUmur0SousLaBorneNEstPasSignale(): void
    {
        self::assertArrayNotHasKey('umur0', ReferenceDefects::detect([], $this->mur(5, 2, 1.55)));
    }

    /**
     * Avec `Rdoublage` = 0,10 — `enum_type_doublage_id` = 3 — le plafond de
     * 2,5 reste atteignable (`1 / (1/3,90 + 0,10) = 2,806`) : pas de preuve.
     */
    public function testUnDoublageADixCentiemesNEstPasConcerne(): void
    {
        self::assertArrayNotHasKey('umur0', ReferenceDefects::detect([], $this->mur(3, 2, 2.5)));
    }

    /** Un U0 saisi et justifié échappe à la table : rien à démontrer. */
    public function testUnU0SaisiEchappeALaRegle(): void
    {
        self::assertArrayNotHasKey('umur0', ReferenceDefects::detect([], $this->mur(5, 3, 2.5)));
    }

    /** Sans doublage déclaré non plus. */
    public function testSansDoublageAucuneImputation(): void
    {
        foreach ([1, 2] as $doublage) {
            self::assertArrayNotHasKey('umur0', ReferenceDefects::detect([], $this->mur($doublage, 2, 2.5)));
        }
    }
}
