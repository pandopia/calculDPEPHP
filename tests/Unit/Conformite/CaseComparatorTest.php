<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\CaseComparator;
use CalculDpePHP\Conformite\ComparisonStatus;
use CalculDpePHP\Conformite\ToleranceProfile;
use PHPUnit\Framework\TestCase;

final class CaseComparatorTest extends TestCase
{
    private function comparator(): CaseComparator
    {
        return new CaseComparator(ToleranceProfile::strict());
    }

    /** @return array<string, array<string, mixed>> path ⇒ delta */
    private function index(array $deltas): array
    {
        $out = [];
        foreach ($deltas as $d) {
            $out[$d['path']] = $d;
        }

        return $out;
    }

    public function testStatutsDeBase(): void
    {
        $deltas = $this->index($this->comparator()->compareValues(
            ['a/conso_ch' => '100', 'b/conso_ecs' => '100', 'c/besoin_ch' => '50', 'd/classe_bilan_dpe' => 'D'],
            ['a/conso_ch' => '100', 'b/conso_ecs' => '200', 'e/conso_fr' => '7',  'd/classe_bilan_dpe' => 'E'],
        ));

        self::assertSame(ComparisonStatus::EXACT, $deltas['a/conso_ch']['status']);
        self::assertSame(ComparisonStatus::OUT, $deltas['b/conso_ecs']['status']);
        self::assertSame(ComparisonStatus::MISSING, $deltas['c/besoin_ch']['status']);
        self::assertSame(ComparisonStatus::EXTRA, $deltas['e/conso_fr']['status']);
        self::assertSame(ComparisonStatus::MISMATCH, $deltas['d/classe_bilan_dpe']['status']);
    }

    public function testAbsenceDesDeuxCotesNestPasUnEcart(): void
    {
        // Une balise `xsi:nil` en référence et non produite par le moteur
        // décrivent la même chose : une donnée non renseignée.
        $deltas = $this->index($this->comparator()->compareValues(
            ['a/cout_ch' => '#nil', 'b/cout_ecs' => ''],
            ['b/cout_ecs' => '#nil'],
        ));

        self::assertSame(ComparisonStatus::EXACT, $deltas['a/cout_ch']['status']);
        self::assertSame(ComparisonStatus::EXACT, $deltas['b/cout_ecs']['status']);
    }

    public function testDiagnosticFacteurMilleSignaleUneUnite(): void
    {
        $deltas = $this->index($this->comparator()->compareValues(
            ['x/besoin_ecs' => '1250000'],
            ['x/besoin_ecs' => '1250'],
        ));

        self::assertSame(ComparisonStatus::OUT, $deltas['x/besoin_ecs']['status']);
        self::assertStringContainsString('1000', (string) $deltas['x/besoin_ecs']['note']);
    }

    public function testDiagnosticMoteurNul(): void
    {
        $deltas = $this->index($this->comparator()->compareValues(['x/conso_ch' => '900'], ['x/conso_ch' => '0']));

        self::assertSame('moteur=0 alors que la référence est non nulle', $deltas['x/conso_ch']['note']);
    }

    public function testDiagnosticSigneOppose(): void
    {
        $deltas = $this->index($this->comparator()->compareValues(['x/conso_ch' => '900'], ['x/conso_ch' => '-900']));

        self::assertSame('signe opposé', $deltas['x/conso_ch']['note']);
    }

    public function testFamilleEstRenseigneeSurChaqueDelta(): void
    {
        $deltas = $this->index($this->comparator()->compareValues(
            ['dpe/logement/sortie/cout/cout_ch' => '100'],
            ['dpe/logement/sortie/cout/cout_ch' => '120'],
        ));

        self::assertSame('Coûts', $deltas['dpe/logement/sortie/cout/cout_ch']['famille']);
        self::assertSame('cout_ch', $deltas['dpe/logement/sortie/cout/cout_ch']['tag']);
    }

    public function testReferenceIllisibleEstSignaleeSansCrash(): void
    {
        $result = $this->comparator()->run('c', 'x.xml', '/inexistant/in.xml', '/inexistant/exp.xml');

        self::assertSame('reference_illisible', $result['status']);
        self::assertNotNull($result['error']);
    }

    public function testSuspicionPeutViserUnCheminExact(): void
    {
        $method = new \ReflectionMethod(CaseComparator::class, 'suspectFor');
        $path = 'dpe/logement/sortie/ef_conso/conso_ecs';

        self::assertSame('motif ciblé', $method->invoke(
            $this->comparator(),
            [$path => 'motif ciblé'],
            ['path' => $path, 'tag' => 'conso_ecs'],
        ));
        self::assertNull($method->invoke(
            $this->comparator(),
            [$path => 'motif ciblé'],
            ['path' => 'dpe/logement/installation_ecs/conso_ecs', 'tag' => 'conso_ecs'],
        ));
    }
}
