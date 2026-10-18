<?php

declare(strict_types=1);

namespace Tests\Unit\Tables;

use CalculDpePHP\Tables\TableRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Sollicitations de refroidissement — §18.2.1 à §18.2.3 p.121-143.
 *
 * La table venait d'open3cl et seule l'altitude ≤ 400 m avait été recoupée
 * avec l'annexe. Les deux colonnes `Nref` de l'altitude 400-800 m ne l'étaient
 * pas : elles valaient environ le tiers des valeurs publiées, sur les huit
 * zones. Ces tests figent les lignes reprises.
 */
final class TvSollicitationsFroidTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    /** @return array<int, array<int, array<int, array<string, float|int>>>> */
    private function table(): array
    {
        return (new TableRepository(self::PROJECT_ROOT . '/resources/tables'))
            ->load('reference/tv_sollicitations_froid');
    }

    /**
     * Totaux annuels des deux colonnes `Nref`, altitude 400-800 m, relevés
     * dans les tableaux « Nref (26°C) » et « Nref (28°C) » de §18.2.2.
     *
     * @return iterable<string, array{int, float, float}> [zone, ΣNref26, ΣNref28]
     */
    public static function totauxAltitude400a800(): iterable
    {
        yield 'H1a' => [1, 93.0, 35.0];
        yield 'H1b' => [2, 179.0, 72.0];
        yield 'H1c' => [3, 242.0, 116.0];
        yield 'H2a' => [4, 94.0, 34.0];
        yield 'H2b' => [5, 127.0, 67.0];
        yield 'H2c' => [6, 233.0, 124.0];
        yield 'H2d' => [7, 613.0, 370.0];
        yield 'H3'  => [8, 520.0, 222.0];
    }

    #[DataProvider('totauxAltitude400a800')]
    public function testLesHeuresDeRefroidissementDe400a800m(int $zone, float $n26, float $n28): void
    {
        $rows = $this->table()[$zone][2];
        $somme26 = $somme28 = 0.0;
        for ($mois = 1; $mois <= 12; $mois++) {
            $somme26 += (float) $rows[$mois]['Nref26'];
            $somme28 += (float) $rows[$mois]['Nref28'];
        }

        self::assertEqualsWithDelta($n26, $somme26, 1e-9, "ΣNref26 de la zone $zone");
        self::assertEqualsWithDelta($n28, $somme28, 1e-9, "ΣNref28 de la zone $zone");
    }

    /**
     * Le mois le plus chargé de H1c à 400-800 m, détaillé : c'est sur lui que
     * l'écart a été repéré, les quatre références de cette zone impliquant
     * 116 heures à 28 °C quand la table n'en portait que 35.
     */
    public function testLeDetailMensuelDeH1cEntre400Et800m(): void
    {
        $rows = $this->table()[3][2];

        foreach ([5 => 8.0, 6 => 46.0, 7 => 88.0, 8 => 91.0, 9 => 9.0] as $mois => $attendu) {
            self::assertEqualsWithDelta($attendu, (float) $rows[$mois]['Nref26'], 1e-9, "Nref26 mois $mois");
        }
        foreach ([5 => 5.0, 6 => 19.0, 7 => 46.0, 8 => 46.0, 9 => 0.0] as $mois => $attendu) {
            self::assertEqualsWithDelta($attendu, (float) $rows[$mois]['Nref28'], 1e-9, "Nref28 mois $mois");
        }
    }

    /**
     * Les trois classes d'altitude couvrent les huit zones sur douze mois,
     * sans trou : une table partielle est un bug en attente.
     */
    public function testLaTableEstExhaustive(): void
    {
        $table = $this->table();
        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8], array_keys($table));

        foreach ($table as $zone => $parAltitude) {
            self::assertSame([1, 2, 3], array_keys($parAltitude), "zone $zone");
            foreach ($parAltitude as $altitude => $parMois) {
                self::assertSame(range(1, 12), array_keys($parMois), "zone $zone altitude $altitude");
                foreach ($parMois as $mois => $ligne) {
                    self::assertSame(
                        ['Nref26', 'DH26', 'Nref28', 'DH28', 'E_fr_26', 'E_fr_28', 'Text'],
                        array_keys($ligne),
                        "zone $zone altitude $altitude mois $mois",
                    );
                }
            }
        }
    }

    /**
     * Les dix croisements où l'annexe publie des degrés-heures en regard d'un
     * nombre d'heures nul sont un défaut du texte officiel, recopié tel quel.
     * Le test les énumère pour qu'ils restent **exactement** dix : un
     * onzième signalerait une nouvelle erreur de transcription, pas un
     * nouveau défaut de l'annexe.
     */
    public function testLesIncoherencesDeLAnnexeRestentCellesQuiSontConnues(): void
    {
        $trouves = [];
        foreach ($this->table() as $zone => $parAltitude) {
            foreach ($parAltitude as $altitude => $parMois) {
                foreach ($parMois as $mois => $ligne) {
                    foreach ([26, 28] as $consigne) {
                        if ((float) $ligne['DH' . $consigne] > 0.0 && (float) $ligne['Nref' . $consigne] <= 0.0) {
                            $trouves[] = "$zone/$altitude/$mois/DH$consigne";
                        }
                    }
                }
            }
        }

        self::assertSame(
            [
                '1/2/5/DH26', '1/2/9/DH28', '2/2/5/DH28', '2/2/9/DH28', '3/2/9/DH28',
                '5/2/5/DH28', '7/2/4/DH26', '7/2/5/DH28', '8/1/10/DH26', '8/2/10/DH26',
            ],
            $trouves,
        );
    }
}
