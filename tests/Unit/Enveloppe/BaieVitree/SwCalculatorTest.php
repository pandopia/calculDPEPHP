<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\BaieVitree;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\BaieVitree\SwCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SwCalculatorTest extends TestCase
{
    private function compute(string $entry): ?float
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement><baie_vitree><donnee_entree>' . $entry . '</donnee_entree></baie_vitree></logement>');
        (new SwCalculator())->calculate($doc->getElementsByTagName('baie_vitree')->item(0), new CalculationContext($doc, new TableRepository(__DIR__ . '/../../../../resources/tables')));
        $value = $doc->getElementsByTagName('sw')->item(0);
        return $value === null ? null : (float)$value->textContent;
    }

    #[DataProvider('doublesFenetres')]
    public function testLeFacteurSolaireEstLeProduitDesDeuxFenetres(float $s1, float $s2): void
    {
        // §6.2.1 p.46 : Sw = Sw1 × Sw2, sans addition ni moyenne.
        self::assertEqualsWithDelta($s1 * $s2, $this->compute('<double_fenetre>1</double_fenetre><sw_1>' . $s1 . '</sw_1><sw_2>' . $s2 . '</sw_2>'), 1e-12);
    }

    public static function doublesFenetres(): iterable
    {
        foreach ([[0.63, 0.7], [0.67, 0.58], [0.52, 0.42], [0.0, 0.5], [1.0, 1.0], [0.0, 0.0]] as [$a, $b]) {
            yield "$a / $b" => [$a, $b];
            if ($a !== $b) { yield "$b / $a" => [$b, $a]; }
        }
    }

    #[DataProvider('doublesIncompletes')]
    public function testUneDoubleFenetreIncompletementCaracteriseeEstRefusee(string $entry): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('double fenêtre');
        $this->compute('<double_fenetre>1</double_fenetre>' . $entry);
    }

    public static function doublesIncompletes(): iterable
    {
        yield 'deux absents' => [''];
        yield 'second absent' => ['<sw_1>0.5</sw_1>'];
        yield 'premier absent sans paramètres' => ['<sw_2>0.5</sw_2>'];
        yield 'négatif' => ['<sw_1>-0.1</sw_1><sw_2>0.5</sw_2>'];
        yield 'supérieur à un' => ['<sw_1>0.5</sw_1><sw_2>1.01</sw_2>'];
    }

    public function testLeFacteurDeLaPremiereFenetrePeutVenirDuForfait(): void
    {
        $entry = '<enum_type_materiaux_menuiserie_id>3</enum_type_materiaux_menuiserie_id><enum_type_baie_id>4</enum_type_baie_id><enum_type_vitrage_id>1</enum_type_vitrage_id><enum_type_pose_id>1</enum_type_pose_id>';
        self::assertSame(0.58, $this->compute($entry));
        self::assertEqualsWithDelta(0.58 * 0.42, $this->compute($entry . '<double_fenetre>1</double_fenetre><sw_2>0.42</sw_2>'), 1e-12);
    }

    public function testLaFenetreSimpleNeCombinePasUneSecondeValeurParasite(): void
    {
        foreach (['', '<double_fenetre>0</double_fenetre>'] as $flag) {
            self::assertSame(0.52, $this->compute($flag . '<sw_1>0.52</sw_1><sw_2>0.42</sw_2>'));
            self::assertSame(0.0, $this->compute($flag . '<sw_1>0</sw_1><sw_2>0.42</sw_2>'));
        }
        self::assertNull($this->compute(''));
        foreach ([1, 2, 3] as $type) {
            self::assertSame(0.4, $this->compute('<enum_type_baie_id>' . $type . '</enum_type_baie_id>'));
        }
    }
}
