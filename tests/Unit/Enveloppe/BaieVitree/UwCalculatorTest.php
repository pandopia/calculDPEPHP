<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\BaieVitree;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\BaieVitree\UwCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UwCalculatorTest extends TestCase
{
    private function compute(string $entry, ?float $ug = null): ?float
    {
        $doc = new DOMDocument();
        $di = $ug === null ? '' : '<donnee_intermediaire><ug>' . $ug . '</ug></donnee_intermediaire>';
        $doc->loadXML('<logement><baie_vitree><donnee_entree>' . $entry . '</donnee_entree>' . $di . '</baie_vitree></logement>');
        (new UwCalculator())->calculate($doc->getElementsByTagName('baie_vitree')->item(0), new CalculationContext($doc, new TableRepository(__DIR__ . '/../../../../resources/tables')));
        $value = $doc->getElementsByTagName('uw')->item(0);
        return $value === null ? null : (float)$value->textContent;
    }

    #[DataProvider('doublesFenetres')]
    public function testLaResistanceDesDeuxFenetresEstCombinee(float $u1, float $u2): void
    {
        // §3.3.2 p.30, y compris deux fenêtres différentes et ordre inversé.
        $actual = $this->compute('<double_fenetre>1</double_fenetre><uw_1>' . $u1 . '</uw_1><uw_2>' . $u2 . '</uw_2>');
        self::assertEqualsWithDelta(1 / (1 / $u1 + 1 / $u2 + 0.07), $actual, 1e-12);
        self::assertLessThan(min($u1, $u2), $actual);
    }

    public static function doublesFenetres(): iterable
    {
        foreach ([[5.9, 5.9], [6.4, 5.8], [5.4, 1.3], [1.0, 1.0], [0.5, 6.0]] as [$a, $b]) {
            yield "$a / $b" => [$a, $b];
            if ($a !== $b) { yield "$b / $a" => [$b, $a]; }
        }
    }

    #[DataProvider('doublesIncompletes')]
    public function testUneDoubleFenetreNeDevientPasSilencieusementSimple(string $values): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('double fenêtre');
        $this->compute('<double_fenetre>1</double_fenetre>' . $values, 1.1);
    }

    public static function doublesIncompletes(): iterable
    {
        yield 'aucune caractéristique' => [''];
        yield 'seconde absente' => ['<uw_1>5.9</uw_1>'];
        yield 'première absente' => ['<uw_2>1.3</uw_2>'];
        yield 'première nulle' => ['<uw_1>0</uw_1><uw_2>1.3</uw_2>'];
        yield 'seconde négative' => ['<uw_1>5.9</uw_1><uw_2>-1</uw_2>'];
    }

    public function testLaSaisieDuComplexeCompletPrime(): void
    {
        self::assertSame(0.8, $this->compute('<double_fenetre>1</double_fenetre><uw_saisi>0.8</uw_saisi><uw_1>5.9</uw_1><uw_2>1.3</uw_2>'));
    }

    public function testLaPremiereFenetrePeutEtreCaracteriseeParSesParametres(): void
    {
        $entry = '<double_fenetre>1</double_fenetre><uw_2>5.9</uw_2><enum_type_materiaux_menuiserie_id>6</enum_type_materiaux_menuiserie_id><enum_type_baie_id>5</enum_type_baie_id>';
        self::assertEqualsWithDelta(1 / (1 / 1.9 + 1 / 5.9 + 0.07), $this->compute($entry, 1.1), 1e-12);
    }

    public function testLaFenetreSimpleNeCombinePasUneSecondeValeurParasite(): void
    {
        foreach (['', '<double_fenetre>0</double_fenetre>'] as $flag) {
            self::assertSame(1.8, $this->compute($flag . '<uw_1>1.8</uw_1><uw_2>5.9</uw_2>'));
        }
    }

    public function testLeForfaitEtLInterpolationRestentInchanges(): void
    {
        $entry = '<enum_type_materiaux_menuiserie_id>6</enum_type_materiaux_menuiserie_id><enum_type_baie_id>5</enum_type_baie_id>';
        self::assertEqualsWithDelta(1.9, $this->compute($entry, 1.1), 1e-12);
        self::assertEqualsWithDelta(1.95, $this->compute($entry, 1.15), 1e-12);
        self::assertNull($this->compute($entry));
        self::assertNull($this->compute('<enum_type_baie_id>99</enum_type_baie_id>', 1.1));
        foreach ([1 => 3.5, 2 => 2.0, 3 => 3.0] as $type => $value) {
            self::assertSame($value, $this->compute('<enum_type_baie_id>' . $type . '</enum_type_baie_id>'));
        }
    }
}
