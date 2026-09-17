<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\ConfortEteCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfortEteCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function inertieLourde(?int $classeInertie): int
    {
        $inertie = $classeInertie === null
            ? ''
            : "<inertie><enum_classe_inertie_id>{$classeInertie}</enum_classe_inertie_id></inertie>";

        $document = new DOMDocument();
        $document->loadXML(
            '<logement><enveloppe>' . $inertie
            . '<baie_vitree_collection/><plancher_haut_collection/></enveloppe><sortie/></logement>'
        );
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        if ($classeInertie !== null) {
            $context->set('inertie.classe_id', $classeInertie);
        }

        (new ConfortEteCalculator())->calculate($document->documentElement, $context);

        return (int) $document->getElementsByTagName('inertie_lourde')->item(0)->textContent;
    }

    /**
     * `enum_classe_inertie_id` se lit du plus lourd au plus léger dans le XSD :
     * 1 très lourde, 2 lourde, 3 moyenne, 4 légère.
     *
     * @return iterable<string, array{int, int}>
     */
    public static function classesInertie(): iterable
    {
        yield 'très lourde' => [1, 1];
        yield 'lourde' => [2, 1];
        yield 'moyenne' => [3, 0];
        yield 'légère' => [4, 0];
    }

    #[DataProvider('classesInertie')]
    public function testInertieLourdeSuitLOrdreDuXsd(int $classe, int $attendu): void
    {
        self::assertSame($attendu, $this->inertieLourde($classe));
    }
}
