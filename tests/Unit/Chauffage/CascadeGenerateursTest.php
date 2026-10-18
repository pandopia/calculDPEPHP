<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage;

use CalculDpePHP\Chauffage\CascadeGenerateurs;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

/**
 * Distinction des deux cascades de §13.2.1.3.
 *
 * L'enjeu n'est pas seulement le rendement : tant qu'une cascade avec priorité
 * n'est pas reconnue, chaque générateur se voit écrire la consommation entière
 * de son installation, et `sortie_par_energie` — qui somme les générateurs —
 * la publie autant de fois qu'il y en a.
 */
final class CascadeGenerateursTest extends TestCase
{
    /**
     * @param list<int|null> $priorites
     *
     * @return array{list<DOMElement>, NodeAccessor}
     */
    private function generateurs(array $priorites): array
    {
        $doc = new DOMDocument();
        $doc->loadXML('<?xml version="1.0"?><generateur_chauffage_collection/>');
        $collection = $doc->documentElement;
        self::assertInstanceOf(DOMElement::class, $collection);

        $generateurs = [];
        foreach ($priorites as $priorite) {
            $gen = $doc->createElement('generateur_chauffage');
            $de  = $doc->createElement('donnee_entree');
            if ($priorite !== null) {
                $de->appendChild($doc->createElement('priorite_generateur_cascade', (string) $priorite));
            }
            $gen->appendChild($de);
            $collection->appendChild($gen);
            $generateurs[] = $gen;
        }

        return [$generateurs, new NodeAccessor($doc)];
    }

    /**
     * §13.2.1.3.1 : le prioritaire assure le besoin, le subordonné n'assure
     * rien.
     */
    public function testUneCascadeHierarchiseeNeRetientQueLePrioritaire(): void
    {
        [$generateurs, $accessor] = $this->generateurs([1, 2]);

        self::assertTrue(CascadeGenerateurs::avecPriorite($generateurs, $accessor));
        self::assertSame([$generateurs[0]], CascadeGenerateurs::principaux($generateurs, $accessor));
    }

    /** La priorité minimale fait foi, quel que soit l'ordre du document. */
    public function testLaPrioriteMinimaleFaitFoiQuelQueSoitLOrdre(): void
    {
        [$generateurs, $accessor] = $this->generateurs([3, 2, 3]);

        self::assertSame([$generateurs[1]], CascadeGenerateurs::principaux($generateurs, $accessor));
    }

    /**
     * §13.2.1.3.2 : « dans le cas de deux générateurs en cascade sans
     * priorité, les deux générateurs sont déclarés comme principal ». Ils se
     * partagent alors le besoin au prorata de leur puissance.
     *
     * @return iterable<string, array{list<int|null>}>
     */
    public static function sansHierarchie(): iterable
    {
        yield 'memes priorites' => [[1, 1]];
        yield 'priorite absente' => [[null, null]];
        yield 'priorite absente d un seul cote' => [[1, null]];
        yield 'priorite nulle' => [[0, 2]];
        yield 'generateur unique' => [[2]];
    }

    /** @param list<int|null> $priorites */
    #[\PHPUnit\Framework\Attributes\DataProvider('sansHierarchie')]
    public function testHorsCascadeHierarchiseeTousLesGenerateursAssurent(array $priorites): void
    {
        [$generateurs, $accessor] = $this->generateurs($priorites);

        self::assertFalse(CascadeGenerateurs::avecPriorite($generateurs, $accessor));
        self::assertSame($generateurs, CascadeGenerateurs::principaux($generateurs, $accessor));
    }
}
