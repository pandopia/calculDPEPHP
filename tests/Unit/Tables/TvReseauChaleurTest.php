<?php

declare(strict_types=1);

namespace Tests\Unit\Tables;

use CalculDpePHP\Tables\TableRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Table des contenus CO2 des réseaux de chaleur et de froid.
 *
 * Les arrêtés annuels publient, pour chaque réseau, un contenu CO2 et un
 * contenu CO2 ACV ; c'est la colonne ACV que cette table porte. Aucun réseau
 * n'y a jamais un contenu nul : un zéro signale une cellule perdue à la
 * digitalisation, et il se traduirait en production par des émissions de
 * chauffage ou d'ECS nulles pour tout DPE desservi par ce réseau.
 */
final class TvReseauChaleurTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    /** @return array<int, array<string, float>> */
    private function table(): array
    {
        return (new TableRepository(self::PROJECT_ROOT . '/resources/tables'))
            ->load('reference/tv_reseau_chaleur');
    }

    public function testAucunReseauNAUnContenuCo2Nul(): void
    {
        foreach ($this->table() as $millesime => $reseaux) {
            $nuls = array_keys(array_filter($reseaux, static fn(float $v): bool => $v <= 0.0));

            self::assertSame(
                [],
                $nuls,
                sprintf('Millésime %d : contenu CO2 nul pour %s', $millesime, implode(', ', $nuls)),
            );
        }
    }

    /**
     * Les quatre millésimes couvrent les arrêtés de 2023 à 2026, et leur
     * nombre de réseaux croît avec les mises à jour successives.
     */
    public function testLesMillesimesSontComplets(): void
    {
        $table = $this->table();

        self::assertSame([2022, 2023, 2024, 2025], array_keys($table));

        $precedent = 0;
        foreach ($table as $reseaux) {
            self::assertGreaterThan($precedent, count($reseaux));
            $precedent = count($reseaux);
        }
    }

    /**
     * Valeurs relues ligne à ligne dans l'annexe de l'arrêté du 11 avril 2025
     * (JORFTEXT000051520810), colonne « Contenu CO2 ACV ».
     *
     * @return iterable<string, array{string, float}>
     */
    public static function valeursRelues(): iterable
    {
        yield 'Belena, Belley' => ['0106C', 0.040];
        yield 'Chaufferie bois, Valgorge' => ['0703C', 0.020];
        yield 'Reseau de Bourganeuf' => ['2301C', 0.040];
        yield 'Reseau d Amneville' => ['5720C', 0.040];
        yield 'Reseau de Denain' => ['5922C', 0.020];
        yield 'Eco-quartier Brasserie Cronenbourg' => ['6704C', 0.030];
        yield 'Reseau d Argenteuil' => ['9509C', 0.040];
        yield 'Reseau froid CCIAG Grenoble' => ['3802F', 0.030];
    }

    #[DataProvider('valeursRelues')]
    public function testMillesime2024(string $identifiant, float $attendu): void
    {
        self::assertEqualsWithDelta($attendu, $this->table()[2024][$identifiant], 1e-9);
    }
}
