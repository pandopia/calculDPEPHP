<?php

declare(strict_types=1);

namespace Tests\Unit\Apport;

use CalculDpePHP\Apport\EspaceTamponSolariseCalculator;
use CalculDpePHP\Apport\SurfaceSudEquivalenteCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EspaceTamponSolariseApportsDirectsTest extends TestCase
{
    private function document(array $ets, array $baies): DOMDocument
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement><enveloppe><ets_collection/><baie_vitree_collection/></enveloppe></logement>');
        foreach ($ets as $definition) {
            $node = $doc->createElement('ets');
            $doc->getElementsByTagName('ets_collection')->item(0)->appendChild($node);
            $this->block($node, 'donnee_entree', [
                'reference' => $definition['reference'] ?? null,
                'tv_coef_transparence_ets_id' => $definition['id'] ?? 2,
                'enum_cfg_isolation_lnc_id' => $definition['cfg'] ?? 7,
            ]);
            $collection = $doc->createElement('baie_ets_collection');
            $node->appendChild($collection);
            $exterieure = $doc->createElement('baie_ets');
            $collection->appendChild($exterieure);
            $this->block($exterieure, 'donnee_entree', ['surface_totale_baie' => 10, 'enum_orientation_id' => 1, 'enum_inclinaison_vitrage_id' => 3]);
        }
        foreach ($baies as $definition) {
            $node = $doc->createElement('baie_vitree');
            $doc->getElementsByTagName('baie_vitree_collection')->item(0)->appendChild($node);
            $this->block($node, 'donnee_entree', [
                'reference_lnc' => $definition['reference_lnc'] ?? null,
                'enum_type_adjacence_id' => $definition['adjacence'] ?? 10,
                'surface_totale_baie' => $definition['surface'] ?? 5,
                'enum_orientation_id' => 1,
                'enum_inclinaison_vitrage_id' => 3,
            ]);
            $this->block($node, 'donnee_intermediaire', [
                'sw' => array_key_exists('sw', $definition) ? $definition['sw'] : 0.5,
                'fe1' => array_key_exists('fe1', $definition) ? $definition['fe1'] : 1,
                'fe2' => array_key_exists('fe2', $definition) ? $definition['fe2'] : 1,
            ]);
        }
        return $doc;
    }

    private function block(DOMElement $parent, string $name, array $values): void
    {
        $doc = $parent->ownerDocument;
        $block = $doc->createElement($name);
        $parent->appendChild($block);
        foreach ($values as $field => $value) {
            if ($value === null) { continue; }
            $child = $doc->createElement($field);
            $child->appendChild($doc->createTextNode((string)$value));
            $block->appendChild($child);
        }
    }

    private function compute(DOMDocument $doc): array
    {
        $context = new CalculationContext($doc, new TableRepository(dirname(__DIR__, 3) . '/resources/tables'), zoneClimatique: '1');
        foreach ($doc->getElementsByTagName('ets') as $node) {
            (new EspaceTamponSolariseCalculator())->calculate($node, $context);
        }
        return $context->get('apport.sse_ets_mensuel');
    }

    public function testLesApportsDirectsNeSontPasDejaComptesDansLaSurfaceSudExterieure(): void
    {
        $doc = $this->document([['reference' => 'ETS']], [['reference_lnc' => 'ETS', 'fe1' => 0.8, 'fe2' => 0.5]]);
        $monthly = $this->compute($doc);
        $sst = 10 * (0.8 * 0.62 + 0.024);
        $ssd = 0.62 * 5 * 0.5 * 0.8 * 0.5;
        self::assertEqualsWithDelta($sst * 0.58 + $ssd * (1 - 0.58), $monthly[1], 1e-12);
        self::assertGreaterThan($sst * 0.58, $monthly[1]);
        self::assertContains(SurfaceSudEquivalenteCalculator::class, (new EspaceTamponSolariseCalculator())->dependencies());
    }

    public function testLesMasquesAbsentsSontNeutresEtLesReferencesSontDuTexte(): void
    {
        $reference = 'ETS\'"&[]';
        $doc = $this->document([['reference' => $reference]], [['reference_lnc' => $reference, 'fe1' => null, 'fe2' => null]]);
        self::assertEqualsWithDelta(10 * 0.52 * 0.58 + 0.62 * 5 * 0.5 * 0.42, $this->compute($doc)[1], 1e-12);
    }

    public static function apportsNonRattaches(): iterable
    {
        yield 'cle absente' => [[]];
        yield 'cle autre local' => [['reference_lnc' => 'autre']];
        yield 'exterieur' => [['reference_lnc' => 'ETS', 'adjacence' => 1]];
        yield 'surface nulle' => [['reference_lnc' => 'ETS', 'surface' => 0]];
        yield 'surface negative' => [['reference_lnc' => 'ETS', 'surface' => -1]];
        yield 'sw absent' => [['reference_lnc' => 'ETS', 'sw' => null]];
        yield 'sw nul' => [['reference_lnc' => 'ETS', 'sw' => 0]];
        yield 'masque total' => [['reference_lnc' => 'ETS', 'fe1' => 0]];
    }

    #[DataProvider('apportsNonRattaches')]
    public function testUnLienOuUneDonneeAbsentNeCreePasUnApportDirect(array $baie): void
    {
        $doc = $this->document([['reference' => 'ETS']], [$baie]);
        self::assertEqualsWithDelta(10 * 0.52 * 0.58, $this->compute($doc)[1], 1e-12);
    }

    public function testUneReferenceEtsAbsenteOuDupliqueeNeComptePasUneBaieDeuxFois(): void
    {
        $sansRef = $this->document([[]], [['reference_lnc' => 'ETS']]);
        self::assertEqualsWithDelta(10 * 0.52 * 0.58, $this->compute($sansRef)[1], 1e-12);
        $duplicate = $this->document([['reference' => 'ETS'], ['reference' => 'ETS']], [['reference_lnc' => 'ETS']]);
        self::assertEqualsWithDelta(2 * 10 * 0.52 * 0.58, $this->compute($duplicate)[1], 1e-12);
    }

    public function testDeuxEtsGardentLeursPropresT_BverEtBaiesQuelQueSoitLOrdre(): void
    {
        $ets = [['reference' => 'A'], ['reference' => 'B', 'id' => 7, 'cfg' => 6]];
        $baies = [['reference_lnc' => 'A'], ['reference_lnc' => 'B', 'surface' => 2]];
        $first = $this->compute($this->document($ets, $baies));
        $second = $this->compute($this->document(array_reverse($ets), array_reverse($baies)));
        $expected = 10 * 0.52 * 0.58 + 0.62 * 5 * 0.5 * 0.42
            + 10 * (0.8 * 0.5 + 0.024) * 0.95 + 0.5 * 2 * 0.5 * 0.05;
        self::assertEqualsWithDelta($expected, $first[1], 1e-12);
        foreach (range(1, 12) as $month) { self::assertEqualsWithDelta($first[$month], $second[$month], 1e-12); }
    }
}
