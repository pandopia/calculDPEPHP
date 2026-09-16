<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\PlancherBas;

use PHPUnit\Framework\TestCase;

/**
 * Valeurs de la table forfaitaire Upb_tab — §3.3 p.17.
 *
 * La colonne H3/Autres avait été digitalisée avec les valeurs de la colonne
 * H3/Joule sur les périodes 78-82 et 83-88. Ce test fige les trois colonnes
 * « Autres » telles que la spec les imprime.
 */
final class UpbTabTableTest extends TestCase
{
    /** @return array<string, array<string, array<int, float>>> */
    private function table(): array
    {
        return require __DIR__ . '/../../../../resources/tables/enveloppe/tv_upb_tab.php';
    }

    /**
     * Colonnes « Autres » de la spec p.17, dans l'ordre des périodes
     * ≤74 | 75-77 | 78-82 | 83-88 | 89-00 | 01-05 | 06-12 | ≥13.
     */
    public function testColonnesAutres(): void
    {
        $attendu = [
            'H1' => [2.00, 0.90, 0.90, 0.80, 0.50, 0.30, 0.27, 0.23],
            'H2' => [2.00, 0.95, 0.95, 0.74, 0.63, 0.30, 0.27, 0.23],
            'H3' => [2.00, 1.00, 1.00, 0.89, 0.56, 0.47, 0.40, 0.25],
        ];

        foreach ($attendu as $zone => $valeurs) {
            $ligne = $this->table()[$zone]['autres'];
            self::assertSame($valeurs, [
                $ligne[1], $ligne[3], $ligne[4], $ligne[5],
                $ligne[6], $ligne[7], $ligne[8], $ligne[9],
            ], "colonne Autres de la zone $zone");
        }
    }

    /** Colonnes « Joule », inchangées, pour garder les deux lisibles côte à côte. */
    public function testColonnesJoule(): void
    {
        $attendu = [
            'H1' => [2.00, 0.90, 0.80, 0.55, 0.55, 0.30, 0.27, 0.23],
            'H2' => [2.00, 0.95, 0.84, 0.58, 0.58, 0.30, 0.27, 0.23],
            'H3' => [2.00, 1.00, 0.89, 0.78, 0.50, 0.47, 0.40, 0.25],
        ];

        foreach ($attendu as $zone => $valeurs) {
            $ligne = $this->table()[$zone]['joule'];
            self::assertSame($valeurs, [
                $ligne[1], $ligne[3], $ligne[4], $ligne[5],
                $ligne[6], $ligne[7], $ligne[8], $ligne[9],
            ], "colonne Joule de la zone $zone");
        }
    }

    /** Les périodes regroupées partagent la même valeur. */
    public function testPeriodesRegroupees(): void
    {
        foreach ($this->table() as $zone => $parEnergie) {
            foreach ($parEnergie as $energie => $ligne) {
                self::assertSame($ligne[1], $ligne[2], "$zone/$energie : ≤74 regroupe 1 et 2");
                self::assertSame($ligne[9], $ligne[10], "$zone/$energie : ≥13 regroupe 9 et 10");
            }
        }
    }
}
