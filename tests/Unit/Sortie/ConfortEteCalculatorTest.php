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

    private function isolationToiture(string $planchersHauts): int
    {
        $document = new DOMDocument();
        $document->loadXML(
            '<logement><enveloppe><baie_vitree_collection/>'
            . "<plancher_haut_collection>$planchersHauts</plancher_haut_collection>"
            . '</enveloppe><sortie/></logement>'
        );
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );

        (new ConfortEteCalculator())->calculate($document->documentElement, $context);

        return (int) $document->getElementsByTagName('isolation_toiture')->item(0)->textContent;
    }

    private function plancherHaut(int $adjacence, int $isolation): string
    {
        return '<plancher_haut><donnee_entree>'
            . "<enum_type_adjacence_id>$adjacence</enum_type_adjacence_id>"
            . "<enum_type_isolation_id>$isolation</enum_type_isolation_id>"
            . '</donnee_entree></plancher_haut>';
    }

    /**
     * Un comble perdu ou un local non chauffé est une toiture : son isolation
     * est évaluée, au même titre qu'un plancher haut sur l'extérieur.
     */
    public function testUnCombleEstUneToiture(): void
    {
        self::assertSame(1, $this->isolationToiture($this->plancherHaut(7, 3)));
        self::assertSame(0, $this->isolationToiture($this->plancherHaut(7, 2)));
    }

    /**
     * XSD : « as-t-on une isolation de toiture ». Un plafond donnant sur un
     * logement chauffé (adjacence 22) n'est pas une toiture : le logement n'a
     * donc aucune isolation de toiture.
     */
    public function testUnPlafondSurLogementChauffeNEstPasUneToiture(): void
    {
        self::assertSame(0, $this->isolationToiture($this->plancherHaut(22, 3)));
    }

    public function testSansAucunPlancherHautIlNYAPasDIsolationDeToiture(): void
    {
        self::assertSame(0, $this->isolationToiture(''));
    }

    /**
     * Isolation inconnue : convention pénalisante de la méthode, comme « non
     * isolé ».
     */
    public function testUneIsolationInconnueCompteCommeNonIsolee(): void
    {
        self::assertSame(0, $this->isolationToiture($this->plancherHaut(1, 1)));
    }
}
