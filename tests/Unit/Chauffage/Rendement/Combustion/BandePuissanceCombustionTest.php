<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement\Combustion;

use CalculDpePHP\Chauffage\Rendement\Combustion\BandePuissanceCombustion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * §13.2.2 p.88 : les chaudières à condensation récentes ont trois lignes de
 * table départagées par la puissance nominale — Pn ≤ 70 kW, 70 < Pn ≤ 400 kW,
 * Pn > 400 kW.
 */
final class BandePuissanceCombustionTest extends TestCase
{
    /** @return iterable<string, array{int, float, int}> */
    public static function bandes(): iterable
    {
        yield 'gaz, petite puissance'      => [13, 40.0, 13];
        yield 'gaz, borne 70 kW'           => [13, 70.0, 13];
        yield 'gaz, juste au-dessus de 70' => [13, 70.1, 14];
        yield 'gaz, borne 400 kW'          => [13, 400.0, 14];
        yield 'gaz, au-dessus de 400'      => [13, 400.025, 15];

        // La bande se relit sur Pn quel que soit l'identifiant saisi.
        yield 'gaz saisi en 15, Pn de 40'  => [15, 40.0, 13];
        yield 'fioul, 200 kW'              => [25, 200.0, 26];
        yield 'fioul, 500 kW'              => [26, 500.0, 27];

        foreach ([[28,29,30], [31,32,33], [34,35,36], [37,38,39], [40,41,42], [43,44,45], [50,51,52], [53,54,55], [56,57,58], [59,60,61], [62,63,64]] as $famille) {
            foreach ($famille as $id) {
                foreach ([20.0, 70.0, 70.001, 400.0, 400.001] as $pn) {
                    $attendu = $pn <= 70 ? $famille[0] : ($pn <= 400 ? $famille[1] : $famille[2]);
                    yield "bois $id, puissance $pn" => [$id, $pn, $attendu];
                }
            }
        }
        foreach ([[46,47,48,49], [65,66,67,68]] as $famille) {
            foreach ($famille as $id) {
                foreach ([20.0, 20.001, 70.0, 70.001, 400.0, 400.001] as $pn) {
                    $attendu = $pn <= 20 ? $famille[0] : ($pn <= 70 ? $famille[1] : ($pn <= 400 ? $famille[2] : $famille[3]));
                    yield "bois récent $id, puissance $pn" => [$id, $pn, $attendu];
                }
            }
        }
        // Hors familles à bandes : inchangé.
        yield 'chaudière standard'         => [4, 500.0, 4];
        yield 'chaudière bois'             => [59, 20.0, 59];
    }

    #[DataProvider('bandes')]
    public function testPourPuissance(int $tvId, float $pnKw, int $attendu): void
    {
        self::assertSame($attendu, BandePuissanceCombustion::pourPuissance($tvId, $pnKw));
    }
}
