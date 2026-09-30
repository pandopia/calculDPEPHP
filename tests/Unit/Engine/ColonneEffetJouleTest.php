<?php

declare(strict_types=1);

namespace Tests\Unit\Engine;

use CalculDpePHP\Engine\CalculationContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Colonne « effet joule » ou « autres » des tables forfaitaires de U
 * (§3.2.1 p.13 et suivantes).
 *
 * §3.2 p.12 : « On considère qu'un logement est chauffé par effet joule
 * lorsque la chaleur est fournie par une résistance électrique. »
 */
final class ColonneEffetJouleTest extends TestCase
{
    /** @return iterable<string, array{int|null, int|null, string|null}> */
    public static function cas(): iterable
    {
        yield 'convecteur électrique'            => [1, 98, 'joule'];
        yield 'panneau rayonnant'                => [1, 99, 'joule'];
        yield 'plancher rayonnant électrique'    => [1, 102, 'joule'];
        yield 'radiateur à accumulation'         => [1, 104, 'joule'];
        yield 'chaudière électrique'             => [1, 106, 'joule'];

        // Électriques mais thermodynamiques : la chaleur ne vient pas d'une résistance.
        yield 'PAC air/air'                      => [1, 1, 'autres'];
        yield 'PAC air/eau'                      => [1, 4, 'autres'];
        yield 'PAC géothermique'                 => [1, 19, 'autres'];
        yield 'autre système thermodynamique'    => [1, 117, 'autres'];
        yield 'partie PAC d\'un hybride'         => [1, 147, 'autres'];

        // Autres énergies : toujours la colonne « autres ».
        yield 'chaudière gaz'                    => [2, 88, 'autres'];
        yield 'chaudière bois'                   => [5, 72, 'autres'];

        // Type inconnu sur énergie électrique : rien ne prouve la résistance.
        yield 'électricité sans type'            => [1, null, 'autres'];
        yield 'énergie absente'                  => [null, 98, null];
    }

    #[DataProvider('cas')]
    public function testColonne(?int $energieId, ?int $typeGenerateurId, ?string $attendu): void
    {
        self::assertSame($attendu, CalculationContext::colonneEffetJoule($energieId, $typeGenerateurId));
    }
}
