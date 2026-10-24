<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\BaieVitree;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorPipeline;
use CalculDpePHP\Enveloppe\BaieVitree\{UgCalculator, UwCalculator, SwCalculator};
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UgCalculatorTest extends TestCase
{
    #[DataProvider('caracteristiquesVitrages')]
    public function testLesVitragesPrincipauxEtSecondairesUtilisentLesMemesRegles(string $name, string $entry, ?float $expected): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement><' . $name . '><donnee_entree>' . $entry . '</donnee_entree></' . $name . '></logement>');
        $node = $doc->getElementsByTagName($name)->item(0);
        $calculator = new UgCalculator();
        self::assertTrue($calculator->appliesTo($node));
        $calculator->calculate($node, new CalculationContext($doc, new TableRepository(__DIR__ . '/../../../../resources/tables')));
        $value = $doc->getElementsByTagName('ug')->item(0);
        if ($expected === null) { self::assertNull($value); }
        else { self::assertEqualsWithDelta($expected, (float)$value?->textContent, 1e-12); }
    }

    public static function caracteristiquesVitrages(): iterable
    {
        foreach (['baie_vitree', 'baie_vitree_double_fenetre'] as $name) {
            yield "$name Ug justifié" => [$name, '<ug_saisi>1.23</ug_saisi>', 1.23];
            yield "$name Ug historique" => [$name, '<ug_1>1.24</ug_1>', 1.24];
            yield "$name simple vertical" => [$name, '<enum_type_vitrage_id>1</enum_type_vitrage_id><enum_inclinaison_vitrage_id>3</enum_inclinaison_vitrage_id>', 5.8];
            yield "$name double argon VIR" => [$name, '<enum_type_vitrage_id>2</enum_type_vitrage_id><enum_type_gaz_lame_id>2</enum_type_gaz_lame_id><vitrage_vir>1</vitrage_vir><epaisseur_lame>16</epaisseur_lame>', 1.1];
            yield "$name vitrage spécial" => [$name, '<enum_type_vitrage_id>5</enum_type_vitrage_id>', null];
            yield "$name sans caractéristiques" => [$name, '', null];
        }
    }

    #[DataProvider('formatsDoubleFenetre')]
    public function testLeSousBlocNatifCaracteriseLaSecondeFenetreAvantLaCombinaison(string $format, bool $saisi): void
    {
        $direct = $saisi ? '<uw_saisi>1.3</uw_saisi><sw_saisi>0.42</sw_saisi>' : '';
        $doc = new DOMDocument();
        $doc->loadXML(<<<XML
            <dpe version="$format"><logement><enveloppe><baie_vitree_collection><baie_vitree>
              <donnee_entree><double_fenetre>1</double_fenetre><enum_type_vitrage_id>1</enum_type_vitrage_id><enum_type_materiaux_menuiserie_id>3</enum_type_materiaux_menuiserie_id><enum_type_baie_id>4</enum_type_baie_id><enum_type_pose_id>2</enum_type_pose_id></donnee_entree>
              <baie_vitree_double_fenetre><donnee_entree><enum_type_vitrage_id>2</enum_type_vitrage_id><enum_type_gaz_lame_id>2</enum_type_gaz_lame_id><vitrage_vir>1</vitrage_vir><epaisseur_lame>16</epaisseur_lame><enum_type_materiaux_menuiserie_id>3</enum_type_materiaux_menuiserie_id><enum_type_baie_id>4</enum_type_baie_id><enum_type_pose_id>1</enum_type_pose_id>$direct</donnee_entree></baie_vitree_double_fenetre>
            </baie_vitree></baie_vitree_collection></enveloppe></logement></dpe>
            XML);
        $context = new CalculationContext($doc, new TableRepository(__DIR__ . '/../../../../resources/tables'));
        $pipeline = new CalculatorPipeline();
        foreach ([new SwCalculator(), new UwCalculator(), new UgCalculator()] as $calculator) { $pipeline->add($calculator); }
        $pipeline->run($doc, $context);
        $xp = new \DOMXPath($doc);
        $u2 = $saisi ? 1.3 : 1.6;
        $s2 = $saisi ? 0.42 : 0.45;
        self::assertEqualsWithDelta(1.1, $xp->evaluate('number(//baie_vitree_double_fenetre/donnee_intermediaire/ug)'), 1e-12);
        self::assertEqualsWithDelta($u2, $xp->evaluate('number(//baie_vitree_double_fenetre/donnee_intermediaire/uw)'), 1e-12);
        self::assertEqualsWithDelta($s2, $xp->evaluate('number(//baie_vitree_double_fenetre/donnee_intermediaire/sw)'), 1e-12);
        self::assertEqualsWithDelta(1 / (1 / 5.4 + 1 / $u2 + 0.07), $xp->evaluate('number(//baie_vitree/donnee_intermediaire/uw)'), 1e-12);
        self::assertEqualsWithDelta(0.52 * $s2, $xp->evaluate('number(//baie_vitree/donnee_intermediaire/sw)'), 1e-12);
        self::assertSame(1, $doc->getElementsByTagName('baie_vitree')->length);
        // Recalculer sans sanitizer ne doit pas recombiner le résultat final.
        $first = $doc->saveXML();
        $pipeline->run($doc, $context);
        self::assertSame($first, $doc->saveXML());
    }

    public static function formatsDoubleFenetre(): iterable
    {
        foreach (['2', '0.1.0'] as $format) {
            yield "$format forfait" => [$format, false];
            yield "$format performances justifiées" => [$format, true];
        }
    }

    public function testLesSousBlocsNulsOuVidesNeSontPasDesFenetresSupplementaires(): void
    {
        foreach (['', ' xsi:nil="true"', ' xsi:nil="1"'] as $attribute) {
            $doc = new DOMDocument();
            $doc->loadXML('<logement xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><baie_vitree_double_fenetre' . $attribute . '/><mur/></logement>');
            foreach ([new UgCalculator(), new UwCalculator(), new SwCalculator()] as $calculator) {
                self::assertFalse($calculator->appliesTo($doc->getElementsByTagName('baie_vitree_double_fenetre')->item(0)));
                self::assertFalse($calculator->appliesTo($doc->getElementsByTagName('mur')->item(0)));
            }
        }
    }
}
