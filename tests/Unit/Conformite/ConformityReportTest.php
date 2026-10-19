<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ComparisonStatus;
use CalculDpePHP\Conformite\ConformityReport;
use CalculDpePHP\Conformite\MarkdownRenderer;
use CalculDpePHP\Conformite\TagFamily;
use PHPUnit\Framework\TestCase;

final class ConformityReportTest extends TestCase
{
    /** @param array<string, int> $counts */
    private function case(string $name, string $status, array $counts, array $deltas = [], array $meta = [], array $exact = [], int $reference_suspect = 0): array
    {
        return [
            'name' => $name,
            'corpus' => 'jeu',
            'status' => $status,
            'error' => null,
            'metadata' => $meta + ['perimetre' => 'maison_individuelle', 'regime_coef_ep_elec' => 'post_2026', 'version_moteur_calcul' => 'm1'],
            'counts' => array_fill_keys(ComparisonStatus::ALL, 0) + $counts === $counts
                ? $counts
                : array_merge(array_fill_keys(ComparisonStatus::ALL, 0), $counts),
            'deltas' => $deltas,
            'famille_exact' => $exact,
            'unknown_elements' => [],
            'reference_suspect' => $reference_suspect,
            'duration_ms' => 1.0,
        ];
    }

    public function testTauxDeConformiteCompteLesBalisesManquantes(): void
    {
        $report = new ConformityReport('strict', '2026-09-01T00:00:00+00:00');
        // 8 conformes (6 exactes + 2 tolérées) sur 10 valeurs comparées.
        $report->addCase($this->case('a.xml', 'ok', [
            ComparisonStatus::EXACT => 6,
            ComparisonStatus::WITHIN => 2,
            ComparisonStatus::OUT => 1,
            ComparisonStatus::MISSING => 1,
        ]));

        $data = $report->toArray();

        self::assertSame(10, $data['summary']['values_compared']);
        self::assertSame(80.0, $data['summary']['conformity_percent']);
        self::assertSame(0, $data['summary']['cases_fully_conform']);
        self::assertSame(1, $data['summary']['cases_partially_conform']);
    }

    /**
     * L'avancement rapporte la mesure au plafond que le corpus permet.
     *
     * La conformité brute ne peut pas atteindre 100 % tant qu'une partie des
     * écarts vient des références ; celui-ci le peut, et c'est pourquoi il
     * sert au suivi : 100 % y signifie « tout ce qui est démontrable est
     * démontré ».
     */
    public function testAvancementRapporteLaMesureAuPlafondAtteignable(): void
    {
        $report = new ConformityReport('strict', 'now');
        // 8 conformes sur 10, et l'un des deux écarts est imputé à la référence :
        // mesure 80 %, plafond 90 %, donc 88,89 % du chemin atteignable.
        $report->addCase($this->case('a.xml', 'ok', [
            ComparisonStatus::EXACT => 6,
            ComparisonStatus::WITHIN => 2,
            ComparisonStatus::OUT => 2,
        ], reference_suspect: 1));

        $s = $report->toArray()['summary'];

        self::assertSame(80.0, $s['conformity_percent']);
        self::assertSame(90.0, $s['reachable_percent']);
        self::assertSame(88.89, $s['progress_percent']);
    }

    /** Sans aucun défaut de référence, l'avancement se confond avec la mesure. */
    public function testSansDefautDeReferenceLAvancementEgaleLaConformite(): void
    {
        $report = new ConformityReport('strict', 'now');
        $report->addCase($this->case('a.xml', 'ok', [
            ComparisonStatus::EXACT => 8,
            ComparisonStatus::OUT => 2,
        ]));

        $s = $report->toArray()['summary'];

        self::assertSame($s['conformity_percent'], $s['progress_percent']);
    }

    public function testCasSansEcartEstTotalementConforme(): void
    {
        $report = new ConformityReport('strict', 'now');
        $report->addCase($this->case('a.xml', 'ok', [ComparisonStatus::EXACT => 5]));

        $data = $report->toArray();

        self::assertSame(1, $data['summary']['cases_fully_conform']);
        self::assertSame(100.0, $data['summary']['conformity_percent']);
    }

    public function testCrashEstComptabiliseSansFausserLeTaux(): void
    {
        $report = new ConformityReport('strict', 'now');
        $report->addCase($this->case('a.xml', 'crash', []));
        $report->addCase($this->case('b.xml', 'ok', [ComparisonStatus::EXACT => 4]));

        $data = $report->toArray();

        self::assertSame(2, $data['summary']['cases_total']);
        self::assertSame(1, $data['summary']['cases_run']);
        self::assertSame(1, $data['summary']['cases_crashed']);
        self::assertSame(100.0, $data['summary']['conformity_percent']);
    }

    public function testAgregationParFamilleEtParBalise(): void
    {
        $delta = [
            'path' => 'dpe/logement/sortie/cout/cout_ch', 'tag' => 'cout_ch', 'famille' => TagFamily::COUT,
            'expected' => '100', 'actual' => '150', 'status' => ComparisonStatus::OUT,
            'delta_abs' => 50.0, 'delta_percent' => 50.0, 'note' => null,
        ];

        $report = new ConformityReport('strict', 'now');
        $report->addCase($this->case('a.xml', 'ok', [ComparisonStatus::EXACT => 3, ComparisonStatus::OUT => 1], [$delta], [], [TagFamily::ENVELOPPE => 3]));
        $report->addCase($this->case('b.xml', 'ok', [ComparisonStatus::EXACT => 3, ComparisonStatus::OUT => 1], [$delta], [], [TagFamily::ENVELOPPE => 3]));

        $data = $report->toArray();

        $familles = array_column($data['by_famille'], null, 'famille');
        self::assertSame(6, $familles[TagFamily::ENVELOPPE][ComparisonStatus::EXACT]);
        self::assertSame(2, $familles[TagFamily::COUT][ComparisonStatus::OUT]);

        self::assertSame('cout_ch', $data['by_tag'][0]['tag']);
        self::assertSame(2, $data['by_tag'][0]['count']);
        self::assertSame(2, $data['by_tag'][0]['cases']);
        self::assertSame(50.0, $data['by_tag'][0]['worst_percent']);
    }

    public function testVentilationParPerimetreEtRegime(): void
    {
        $report = new ConformityReport('strict', 'now');
        $report->addCase($this->case('a.xml', 'ok', [ComparisonStatus::EXACT => 2], [], ['perimetre' => 'immeuble_collectif', 'regime_coef_ep_elec' => 'pre_2026']));
        $report->addCase($this->case('b.xml', 'ok', [ComparisonStatus::EXACT => 2]));

        $data = $report->toArray();

        self::assertCount(2, $data['by_perimetre']);
        self::assertCount(2, $data['by_regime']);
    }

    public function testRenduMarkdownContientLesSectionsAttendues(): void
    {
        $report = new ConformityReport('strict', 'now');
        $report->addCase($this->case('a.xml', 'ok', [ComparisonStatus::EXACT => 2]));

        $md = (new MarkdownRenderer())->render($report->toArray());

        self::assertStringContainsString('## Synthèse', $md);
        self::assertStringContainsString('## Écarts par famille fonctionnelle', $md);
        self::assertStringContainsString("## Écarts par périmètre d'évaluation", $md);
        self::assertStringContainsString('## Conformité structurelle du XML produit', $md);
        self::assertStringContainsString('`a.xml`', $md);
    }
}
