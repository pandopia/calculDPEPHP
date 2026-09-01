<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

/**
 * Agrège les résultats de comparaison et produit les rapports JSON et Markdown.
 *
 * Le taux de conformité annoncé est le rapport
 * `(exactes + dans tolérance) / valeurs comparées`, où « valeurs comparées »
 * inclut les balises manquantes et surnuméraires : ne pas produire une balise
 * attendue est un défaut de conformité, pas une valeur à ignorer.
 */
final class ConformityReport
{
    /** @var list<array<string, mixed>> */
    private array $cases = [];

    /**
     * @param array<string, string> $revision état du dépôt mesuré — plusieurs
     *        agents travaillent en parallèle sur ce moteur, un rapport sans
     *        révision n'est pas comparable au suivant
     */
    public function __construct(
        private readonly string $toleranceProfile,
        private readonly string $generatedAt,
        private readonly array $revision = [],
    ) {
    }

    /** @param array<string, mixed> $case */
    public function addCase(array $case): void
    {
        $this->cases[] = $case;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $summary = [
            'cases_total' => count($this->cases),
            'cases_run' => 0,
            'cases_crashed' => 0,
            'cases_fully_conform' => 0,
            'cases_partially_conform' => 0,
            'values_compared' => 0,
            'reference_suspect' => 0,
        ];
        foreach (ComparisonStatus::ALL as $status) {
            $summary[$status] = 0;
        }

        $byFamille = [];
        $byPerimetre = [];
        $byRegime = [];
        $byMoteur = [];
        $byTag = [];
        $caseRows = [];
        $unknownElements = [];

        foreach ($this->cases as $case) {
            $isRun = $case['status'] === 'ok';
            if ($isRun) {
                $summary['cases_run']++;
            }
            if ($case['status'] === 'crash') {
                $summary['cases_crashed']++;
            }

            $counts = $case['counts'];
            $compared = array_sum($counts);
            $failing = $this->failing($counts);

            $summary['values_compared'] += $compared;
            $summary['reference_suspect'] += (int) ($case['reference_suspect'] ?? 0);
            foreach (ComparisonStatus::ALL as $status) {
                $summary[$status] += $counts[$status];
            }

            if ($isRun && $compared > 0) {
                if ($failing === 0) {
                    $summary['cases_fully_conform']++;
                } else {
                    $summary['cases_partially_conform']++;
                }
            }

            $perimetre = (string) ($case['metadata']['perimetre'] ?? 'inconnu');
            $regime = (string) ($case['metadata']['regime_coef_ep_elec'] ?? 'inconnu');
            $moteur = (string) ($case['metadata']['version_moteur_calcul'] ?? 'inconnu');

            $this->accumulate($byPerimetre, $perimetre, $counts);
            $this->accumulate($byRegime, $regime, $counts);
            $this->accumulate($byMoteur, $moteur, $counts);

            foreach ($case['deltas'] as $delta) {
                $status = $delta['status'];
                $famille = $delta['famille'];
                $tag = $delta['tag'];

                $byFamille[$famille] ??= $this->emptyBucket();
                $byFamille[$famille][$status]++;

                if (in_array($status, ComparisonStatus::FAILING, true)) {
                    $key = $famille . '|' . $tag;
                    $byTag[$key] ??= [
                        'famille' => $famille, 'tag' => $tag, 'count' => 0, 'cases' => [],
                        'worst_percent' => 0.0, 'statuses' => [],
                    ];
                    $byTag[$key]['count']++;
                    $byTag[$key]['cases'][$case['name']] = true;
                    $byTag[$key]['statuses'][$status] = ($byTag[$key]['statuses'][$status] ?? 0) + 1;
                    if (($delta['delta_percent'] ?? 0.0) > $byTag[$key]['worst_percent']) {
                        $byTag[$key]['worst_percent'] = (float) $delta['delta_percent'];
                    }
                }
            }

            // Les valeurs exactes ne sont pas détaillées dans les deltas :
            // on les recrée par famille à partir du solde du cas.
            foreach (($case['famille_exact'] ?? []) as $famille => $n) {
                $byFamille[$famille] ??= $this->emptyBucket();
                $byFamille[$famille][ComparisonStatus::EXACT] += $n;
            }

            foreach (($case['unknown_elements'] ?? []) as $name) {
                $unknownElements[$name] = ($unknownElements[$name] ?? 0) + 1;
            }

            $caseRows[] = [
                'name' => $case['name'],
                'corpus' => $case['corpus'],
                'status' => $case['status'],
                'error' => $case['error'],
                'perimetre' => $perimetre,
                'regime_coef_ep_elec' => $regime,
                'version_moteur_calcul' => $moteur,
                'enum_methode_application_dpe_log_id' => $case['metadata']['enum_methode_application_dpe_log_id'] ?? null,
                'date_etablissement_dpe' => $case['metadata']['date_etablissement_dpe'] ?? null,
                'values_compared' => $compared,
                'counts' => $counts,
                'failing' => $failing,
                'conformity_percent' => $this->rate($counts),
                'duration_ms' => round((float) $case['duration_ms'], 1),
                'unknown_elements' => $case['unknown_elements'] ?? [],
                'reference_suspect' => (int) ($case['reference_suspect'] ?? 0),
                'deltas' => $case['deltas'],
            ];
        }

        // Plafond atteignable : ce que donnerait le taux si les écarts dont la
        // référence est responsable disparaissaient. Il borne ce que ce corpus
        // permet de démontrer, sans changer la mesure brute.
        $summary['reachable_percent'] = $summary['values_compared'] > 0
            ? round(
                ($summary[ComparisonStatus::EXACT] + $summary[ComparisonStatus::WITHIN] + $summary['reference_suspect'])
                / $summary['values_compared'] * 100,
                2,
            )
            : null;

        $summary['conformity_percent'] = $this->rate([
            ComparisonStatus::EXACT => $summary[ComparisonStatus::EXACT],
            ComparisonStatus::WITHIN => $summary[ComparisonStatus::WITHIN],
            ComparisonStatus::OUT => $summary[ComparisonStatus::OUT],
            ComparisonStatus::MISSING => $summary[ComparisonStatus::MISSING],
            ComparisonStatus::EXTRA => $summary[ComparisonStatus::EXTRA],
            ComparisonStatus::MISMATCH => $summary[ComparisonStatus::MISMATCH],
        ]);

        arsort($unknownElements);
        uasort($byTag, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        foreach ($byTag as $k => $row) {
            $byTag[$k]['cases'] = count($row['cases']);
        }

        return [
            'generated_at' => $this->generatedAt,
            'tolerance_profile' => $this->toleranceProfile,
            'revision' => $this->revision,
            'summary' => $summary,
            'by_famille' => $this->orderedFamilles($byFamille),
            'by_perimetre' => $this->withRates($byPerimetre),
            'by_regime' => $this->withRates($byRegime),
            'by_moteur_calcul' => $this->withRates($byMoteur),
            'by_tag' => array_values($byTag),
            'unknown_elements' => $unknownElements,
            'cases' => $caseRows,
        ];
    }

    /** @param array<string, int> $counts */
    private function failing(array $counts): int
    {
        $n = 0;
        foreach (ComparisonStatus::FAILING as $status) {
            $n += $counts[$status] ?? 0;
        }

        return $n;
    }

    /** @param array<string, int> $counts */
    private function rate(array $counts): ?float
    {
        $total = array_sum($counts);
        if ($total === 0) {
            return null;
        }
        $ok = ($counts[ComparisonStatus::EXACT] ?? 0) + ($counts[ComparisonStatus::WITHIN] ?? 0);

        return round($ok / $total * 100, 2);
    }

    /** @return array<string, int> */
    private function emptyBucket(): array
    {
        return array_fill_keys(ComparisonStatus::ALL, 0);
    }

    /**
     * @param array<string, array<string, int>> $bucket
     * @param array<string, int> $counts
     */
    private function accumulate(array &$bucket, string $key, array $counts): void
    {
        $bucket[$key] ??= $this->emptyBucket() + ['cases' => 0];
        $bucket[$key]['cases']++;
        foreach (ComparisonStatus::ALL as $status) {
            $bucket[$key][$status] += $counts[$status] ?? 0;
        }
    }

    /**
     * @param array<string, array<string, int>> $bucket
     * @return list<array<string, mixed>>
     */
    private function withRates(array $bucket): array
    {
        $out = [];
        foreach ($bucket as $key => $counts) {
            $out[] = ['key' => $key, ...$counts, 'compared' => array_sum($counts) - ($counts['cases'] ?? 0), 'conformity_percent' => $this->rate($counts)];
        }
        usort($out, static fn (array $a, array $b): int => $b['compared'] <=> $a['compared']);

        return $out;
    }

    /**
     * @param array<string, array<string, int>> $byFamille
     * @return list<array<string, mixed>>
     */
    private function orderedFamilles(array $byFamille): array
    {
        $out = [];
        foreach (TagFamily::ORDER as $famille) {
            if (!isset($byFamille[$famille])) {
                continue;
            }
            $counts = $byFamille[$famille];
            $out[] = [
                'famille' => $famille,
                ...$counts,
                'compared' => array_sum($counts),
                'failing' => $this->failing($counts),
                'conformity_percent' => $this->rate($counts),
            ];
        }

        return $out;
    }
}
