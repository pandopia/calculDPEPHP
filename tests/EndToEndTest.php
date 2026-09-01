<?php

declare(strict_types=1);

namespace Tests;

use CalculDpePHP\Conformite\CaseComparator;
use CalculDpePHP\Conformite\ComparisonStatus;
use CalculDpePHP\Conformite\CorpusLocator;
use CalculDpePHP\Conformite\ToleranceProfile;
use PHPUnit\Framework\TestCase;

/**
 * Harness bout-en-bout : le moteur ne doit régresser sur aucun cas des corpus.
 *
 * Ce test comparait autrefois une liste de balises « couvertes », avec des
 * exclusions nominatives par fichier, et son indexation de chemin écrasait les
 * fratries homonymes — sur un logement à 30 murs, un seul `umur` était
 * réellement vérifié. Un moteur pouvait donc être vert et faux.
 *
 * Il s'appuie désormais sur le même comparateur que `bin/official-test-report` :
 * **toutes** les balises de `<donnee_intermediaire>` et `<sortie>`, chemins
 * indexés, aucune exclusion. Comme le moteur n'est pas encore conforme, le
 * critère n'est pas « zéro écart » mais « pas plus d'écarts qu'au dernier
 * relevé », cas par cas : `tests/conformity-baseline.php`.
 *
 * Les écarts dont la référence est responsable sont exclus du budget
 * (voir `ReferenceDefects`) : il mesure notre qualité, pas celle du corpus.
 *
 * Après une amélioration mesurée, resserrer le budget :
 *
 *     php bin/official-test-report --write-baseline
 *
 * Ne jamais le régénérer pour faire passer une régression.
 */
final class EndToEndTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/..';

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: int}>
     */
    public static function casesProvider(): array
    {
        /** @var array<string, int> $baseline */
        $baseline = require self::PROJECT_ROOT . '/tests/conformity-baseline.php';

        $cases = [];
        foreach ((new CorpusLocator(self::PROJECT_ROOT))->locate() as $case) {
            // Un cas absent du budget est un cas nouvellement ajouté au corpus :
            // il n'a pas encore de référence de non-régression.
            if (!array_key_exists($case['name'], $baseline)) {
                continue;
            }
            $key = $case['corpus'] . '/' . $case['name'];
            $cases[$key] = [$case['corpus'], $case['name'], $case['input'], $case['expected'], $baseline[$case['name']]];
        }

        return $cases;
    }

    /**
     * @dataProvider casesProvider
     */
    public function testLeCasNeRegressePas(
        string $corpus,
        string $name,
        string $input,
        string $expected,
        int $budget,
    ): void {
        $comparator = new CaseComparator(
            ToleranceProfile::strict(),
            self::PROJECT_ROOT . '/resources/ademe_DPE.xsd',
        );

        $result = $comparator->run($corpus, $name, $input, $expected);

        self::assertSame('ok', $result['status'], sprintf(
            "Le cas %s n'a pas pu être calculé : %s",
            $name,
            (string) $result['error'],
        ));

        $failing = [];
        foreach ($result['deltas'] as $delta) {
            if (isset($delta['reference_suspect'])) {
                continue;
            }
            if (in_array($delta['status'], ComparisonStatus::FAILING, true)) {
                $failing[] = $delta;
            }
        }

        self::assertLessThanOrEqual(
            $budget,
            count($failing),
            sprintf(
                "Régression sur %s : %d écarts pour un budget de %d.\n%s",
                $name,
                count($failing),
                $budget,
                self::render($failing),
            ),
        );
    }

    /**
     * Le XML produit ne doit contenir aucun chemin absent du schéma ADEME :
     * un fichier qui en contient serait rejeté à la transmission.
     *
     * @dataProvider casesProvider
     */
    public function testLeCasNeProduitAucunCheminHorsSchema(
        string $corpus,
        string $name,
        string $input,
        string $expected,
    ): void {
        $comparator = new CaseComparator(
            ToleranceProfile::strict(),
            self::PROJECT_ROOT . '/resources/ademe_DPE.xsd',
        );

        $result = $comparator->run($corpus, $name, $input, $expected);

        self::assertSame([], $result['unknown_elements'], sprintf(
            'Le cas %s produit des chemins hors schéma ADEME.',
            $name,
        ));
    }

    /**
     * @param list<array<string, mixed>> $failing
     */
    private static function render(array $failing): string
    {
        $lines = [];
        foreach (array_slice($failing, 0, 25) as $delta) {
            $lines[] = sprintf(
                '  - %s : attendu=%s obtenu=%s (%s)',
                $delta['path'],
                $delta['expected'] ?? '—',
                $delta['actual'] ?? '—',
                $delta['delta_percent'] !== null
                    ? sprintf('%.2f %%', $delta['delta_percent'])
                    : $delta['status'],
            );
        }
        if (count($failing) > 25) {
            $lines[] = sprintf('  … et %d autres', count($failing) - 25);
        }

        return implode("\n", $lines);
    }
}
