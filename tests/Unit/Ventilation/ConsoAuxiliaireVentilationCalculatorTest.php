<?php

declare(strict_types=1);

namespace Tests\Unit\Ventilation;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Ventilation\ConsoAuxiliaireVentilationCalculator;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConsoAuxiliaireVentilationCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function calculate(string $version, string $pventMoy): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML(sprintf(
            '<dpe version="%s"><ventilation><donnee_entree/>'
            . '<donnee_intermediaire><pvent_moy>%s</pvent_moy></donnee_intermediaire>'
            . '</ventilation></dpe>',
            $version,
            $pventMoy,
        ));
        (new ConsoAuxiliaireVentilationCalculator())->calculate(
            $document->getElementsByTagName('ventilation')->item(0),
            new CalculationContext(
                document: $document,
                tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            ),
        );

        return $document;
    }

    /**
     * §5 p.41 : Caux_vent = 8760 × Pventmoy / 1000, quel que soit le format.
     *
     * Le format natif ADEME publie la valeur en pleine précision ; les exports
     * LICIEL (`dpe version="2"`) arrondissent la copie `donnee_intermediaire` à
     * l'entier — 287 des 288 ventilations du corpus à ce format.
     *
     * @return iterable<string, array{string, string, string}>
     */
    public static function formats(): iterable
    {
        yield 'natif ADEME, pleine précision' => ['0.1.0', '47.219184', '413.64005184'];
        yield 'export LICIEL, arrondi entier' => ['2', '47.219184', '414'];
    }

    #[DataProvider('formats')]
    public function testConsommationSuitLaFormuleDuParagraphe5(
        string $version,
        string $pventMoy,
        string $attendu,
    ): void {
        $document = $this->calculate($version, $pventMoy);

        self::assertEqualsWithDelta(
            (float) $attendu,
            (float) $document->getElementsByTagName('conso_auxiliaire_ventilation')->item(0)->textContent,
            1e-8,
        );
    }

    /**
     * La puissance calculée n'est jamais remplacée par une sentinelle.
     *
     * Six des dix ventilations du corpus au format natif publient la paire
     * `pvent_moy = 0` / `conso_auxiliaire_ventilation = 1` tout en déclarant
     * une consommation non nulle dans `sortie` : le fichier se contredit, et
     * `ReferenceDefects` classe l'écart côté référence plutôt que de le
     * reproduire ici.
     */
    public function testLaPuissanceCalculeeEstConservee(): void
    {
        $document = $this->calculate('0.1.0', '47.219184');

        self::assertSame(
            '47.219184',
            $document->getElementsByTagName('pvent_moy')->item(0)->textContent,
        );
    }

    /** Ventilation naturelle : puissance nulle, donc consommation nulle. */
    public function testVentilationNaturelle(): void
    {
        $document = $this->calculate('0.1.0', '0');

        self::assertSame(
            0.0,
            (float) $document->getElementsByTagName('conso_auxiliaire_ventilation')->item(0)->textContent,
        );
    }
}
