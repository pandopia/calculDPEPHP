<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

use CalculDpePHP\CalculDpePHP;
use DOMDocument;
use Throwable;

/**
 * Exécute le moteur sur un cas et compare toutes les valeurs calculées à la
 * référence, balise par balise.
 *
 * Aucun filtrage par « balises couvertes » n'est appliqué : contrairement au
 * harness E2E historique, la totalité de `<donnee_intermediaire>` et
 * `<sortie>` est confrontée à la référence, y compris les balises que le
 * moteur ne produit pas encore (comptées `missing`).
 */
final class CaseComparator
{
    public function __construct(
        private readonly ToleranceProfile $tolerance,
        private readonly ?string $xsdPath = null,
        private readonly ValueExtractor $extractor = new ValueExtractor(),
    ) {
    }

    /**
     * @return array{
     *     name: string, corpus: string, input: string, expected_file: string,
     *     status: string, error: ?string, metadata: array<string, mixed>,
     *     deltas: list<array<string, mixed>>, counts: array<string, int>,
     *     famille_exact: array<string, int>, unknown_elements: list<string>,
     *     reference_suspect: int, duration_ms: float
     * }
     */
    public function run(string $corpus, string $name, string $inputPath, string $expectedPath): array
    {
        $started = microtime(true);

        $result = [
            'name' => $name,
            'corpus' => $corpus,
            'input' => $inputPath,
            'expected_file' => $expectedPath,
            'status' => 'ok',
            'error' => null,
            'metadata' => [],
            'deltas' => [],
            'counts' => array_fill_keys(ComparisonStatus::ALL, 0),
            'famille_exact' => [],
            'unknown_elements' => [],
            'reference_suspect' => 0,
            'duration_ms' => 0.0,
        ];

        $expectedDoc = $this->load($expectedPath);
        if ($expectedDoc === null) {
            $result['status'] = 'reference_illisible';
            $result['error'] = 'Référence XML illisible : ' . $expectedPath;
            $result['duration_ms'] = (microtime(true) - $started) * 1000;

            return $result;
        }

        $result['metadata'] = $this->extractor->metadata($expectedDoc);

        try {
            $calculated = CalculDpePHP::calculate((string) file_get_contents($inputPath));
        } catch (Throwable $e) {
            $result['status'] = 'crash';
            $result['error'] = $e::class . ' : ' . $e->getMessage();
            $result['duration_ms'] = (microtime(true) - $started) * 1000;

            return $result;
        }

        $actualDoc = new DOMDocument();
        $actualDoc->preserveWhiteSpace = false;
        if (!@$actualDoc->loadXML(is_string($calculated) ? $calculated : '')) {
            $result['status'] = 'sortie_illisible';
            $result['error'] = 'Le moteur a produit un XML non parsable.';
            $result['duration_ms'] = (microtime(true) - $started) * 1000;

            return $result;
        }

        $expectedValues = $this->extractor->extract($expectedDoc);
        $actualValues = $this->extractor->extract($actualDoc);

        if ($this->xsdPath !== null) {
            $result['unknown_elements'] = XsdVocabulary::unknownElements($actualValues, $this->xsdPath, $expectedValues);
        }

        // Écarts dont la référence est responsable : signalés, jamais retirés
        // du décompte (voir ReferenceDefects).
        $suspects = ReferenceDefects::detect($expectedValues, $expectedDoc);

        foreach ($this->compareValues($expectedValues, $actualValues) as $delta) {
            $result['counts'][$delta['status']]++;
            $motif = $this->suspectFor($suspects, $delta);
            if ($motif !== null && in_array($delta['status'], ComparisonStatus::FAILING, true)) {
                $delta['reference_suspect'] = $motif;
                $result['reference_suspect']++;
            }
            if ($delta['status'] === ComparisonStatus::EXACT) {
                // Les valeurs exactes ne sont pas détaillées (volume), mais
                // restent comptées par famille pour le taux de conformité.
                $famille = $delta['famille'];
                $result['famille_exact'][$famille] = ($result['famille_exact'][$famille] ?? 0) + 1;
                continue;
            }
            $result['deltas'][] = $delta;
        }

        $result['duration_ms'] = (microtime(true) - $started) * 1000;

        return $result;
    }

    /**
     * Motif de suspicion applicable à un delta, s'il y en a un.
     *
     * Une règle vise soit un chemin exact, soit une balise entière
     * (`cout_ch_depensier`), soit une occurrence précise dans une collection
     * (`rendement_stockage@3` pour la troisième `installation_ecs`).
     *
     * @param array<string, string> $suspects
     * @param array<string, mixed> $delta
     */
    private function suspectFor(array $suspects, array $delta): ?string
    {
        $path = (string) $delta['path'];
        if (isset($suspects[$path])) {
            return $suspects[$path];
        }

        $tag = (string) $delta['tag'];
        if (isset($suspects[$tag])) {
            return $suspects[$tag];
        }

        if (preg_match('/installation_ecs\[(\d+)\]/', $path, $m) === 1) {
            return $suspects[$tag . '@' . $m[1]] ?? null;
        }

        return null;
    }

    /**
     * @param array<string, string> $expected
     * @param array<string, string> $actual
     * @return list<array<string, mixed>>
     */
    public function compareValues(array $expected, array $actual): array
    {
        $keys = array_unique(array_merge(array_keys($expected), array_keys($actual)));
        sort($keys);

        $deltas = [];
        foreach ($keys as $key) {
            $e = $expected[$key] ?? null;
            $a = $actual[$key] ?? null;
            $tag = TagFamily::leaf($key);

            $deltas[] = [
                'path' => $key,
                'tag' => $tag,
                'famille' => TagFamily::of($key),
                ...$this->compareOne($tag, $e, $a),
            ];
        }

        return $deltas;
    }

    /**
     * @return array{expected: ?string, actual: ?string, status: string, delta_abs: ?float, delta_percent: ?float, note: ?string}
     */
    private function compareOne(string $tag, ?string $expected, ?string $actual): array
    {
        $base = ['expected' => $expected, 'actual' => $actual, 'delta_abs' => null, 'delta_percent' => null, 'note' => null];

        $expectedEmpty = $this->isEmpty($expected);
        $actualEmpty = $this->isEmpty($actual);

        // Deux absences (balise non produite des deux côtés, ou xsi:nil) se
        // valent : ce n'est pas un écart de calcul.
        if ($expectedEmpty && $actualEmpty) {
            return [...$base, 'status' => ComparisonStatus::EXACT];
        }

        if ($expectedEmpty) {
            return [...$base, 'status' => ComparisonStatus::EXTRA];
        }

        if ($actualEmpty) {
            return [...$base, 'status' => ComparisonStatus::MISSING];
        }

        if (!is_numeric($expected) || !is_numeric($actual)) {
            return [
                ...$base,
                'status' => $expected === $actual ? ComparisonStatus::EXACT : ComparisonStatus::MISMATCH,
            ];
        }

        $e = (float) $expected;
        $a = (float) $actual;
        $cmp = $this->tolerance->compareNumeric($tag, $e, $a);

        return [
            ...$base,
            'status' => $cmp['status'],
            'delta_abs' => $cmp['delta_abs'],
            'delta_percent' => $cmp['delta_percent'],
            'note' => $cmp['status'] === ComparisonStatus::OUT ? $this->diagnose($e, $a) : null,
        ];
    }

    /**
     * Signale les écarts dont la signature est caractéristique (facteur 1000
     * Wh/kWh, facteur exact, signe opposé, référence nulle). Ce sont des pistes
     * de diagnostic, pas des tolérances : le delta reste hors tolérance.
     */
    private function diagnose(float $expected, float $actual): ?string
    {
        if ($actual == 0.0 && $expected != 0.0) {
            return 'moteur=0 alors que la référence est non nulle';
        }
        if ($expected == 0.0 && $actual != 0.0) {
            return 'référence=0 alors que le moteur est non nul';
        }
        if ($expected * $actual < 0) {
            return 'signe opposé';
        }

        $ratio = $expected / $actual;
        foreach ([1000.0, 3600.0, 100.0, 10.0, 2.0] as $factor) {
            if (abs($ratio - $factor) / $factor < 1e-3) {
                return sprintf('référence ≈ %g × moteur (unité ?)', $factor);
            }
            if (abs((1 / $ratio) - $factor) / $factor < 1e-3) {
                return sprintf('moteur ≈ %g × référence (unité ?)', $factor);
            }
        }

        return null;
    }

    private function isEmpty(?string $value): bool
    {
        return $value === null || $value === '' || $value === '#nil';
    }

    private function load(string $path): ?DOMDocument
    {
        if (!is_file($path)) {
            return null;
        }
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;

        return @$doc->load($path) ? $doc : null;
    }
}
