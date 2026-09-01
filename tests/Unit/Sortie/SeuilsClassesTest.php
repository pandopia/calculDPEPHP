<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\SeuilsClasses;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class SeuilsClassesTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function context(): CalculationContext
    {
        return new CalculationContext(
            document: new DOMDocument(),
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    /**
     * Le seuil est strict : 70 kWh/m²/an n'est pas de classe A, il ouvre la
     * classe B. Comparer avec `<=` décalait d'une classe tous les logements
     * pile sur un seuil.
     */
    public function testLeSeuilEstStrict(): void
    {
        $ctx = $this->context();

        self::assertSame('A', SeuilsClasses::energie(69, 100.0, 1, 1, $ctx));
        self::assertSame('B', SeuilsClasses::energie(70, 100.0, 1, 1, $ctx));
        self::assertSame('A', SeuilsClasses::ges(5, 100.0, 1, 1, $ctx));
        self::assertSame('B', SeuilsClasses::ges(6, 100.0, 1, 1, $ctx));
    }

    /** @dataProvider baremeNationalProvider */
    public function testBaremeNational(int $valeur, string $attendue): void
    {
        self::assertSame($attendue, SeuilsClasses::energie($valeur, 100.0, 1, 1, $this->context()));
    }

    public static function baremeNationalProvider(): array
    {
        return [
            [0, 'A'], [69, 'A'], [70, 'B'], [109, 'B'], [110, 'C'], [179, 'C'],
            [180, 'D'], [249, 'D'], [250, 'E'], [329, 'E'], [330, 'F'], [419, 'F'], [420, 'G'], [1000, 'G'],
        ];
    }

    /**
     * Arrêté du 25 mars 2024 : les seuils sont relevés en deçà de 40 m². À
     * 8 m², la classe A va jusqu'à 146 kWh/m²/an et non 70.
     */
    public function testPetitesSurfacesOntDesSeuilsReleves(): void
    {
        $ctx = $this->context();

        self::assertSame('A', SeuilsClasses::energie(145, 8.0, 1, 1, $ctx));
        self::assertSame('B', SeuilsClasses::energie(146, 8.0, 1, 1, $ctx));
        self::assertSame('A', SeuilsClasses::ges(10, 8.0, 1, 1, $ctx));
        self::assertSame('B', SeuilsClasses::ges(11, 8.0, 1, 1, $ctx));

        // À 40 m² pile, on retrouve le barème national.
        self::assertSame('B', SeuilsClasses::energie(70, 40.0, 1, 1, $ctx));
        // Au-delà, plus aucun relèvement.
        self::assertSame('B', SeuilsClasses::energie(70, 41.0, 1, 1, $ctx));
    }

    public function testLaSurfaceEstArrondiePourLireLaTable(): void
    {
        $ctx = $this->context();

        // 20,11 m² → 20 m² : seuil A = 88.
        self::assertSame('A', SeuilsClasses::energie(87, 20.11, 1, 1, $ctx));
        self::assertSame('B', SeuilsClasses::energie(88, 20.11, 1, 1, $ctx));
    }

    /**
     * Au-dessus de 800 m en zone H1b, H1c ou H2d, seuls E et F sont relevés :
     * E passe de 330 à 390, mais A reste à 70.
     */
    public function testAltitudeAuDessusDe800NeReleveQueEEtF(): void
    {
        $ctx = $this->context();
        $h1b = 2;
        $sup800 = 3;

        self::assertSame('E', SeuilsClasses::energie(389, 100.0, $h1b, $sup800, $ctx));
        self::assertSame('F', SeuilsClasses::energie(390, 100.0, $h1b, $sup800, $ctx));
        self::assertSame('B', SeuilsClasses::energie(70, 100.0, $h1b, $sup800, $ctx));
    }

    public function testAltitudeNeReleveRienDansLesAutresZones(): void
    {
        $ctx = $this->context();
        $h1a = 1;
        $sup800 = 3;

        self::assertSame('F', SeuilsClasses::energie(330, 100.0, $h1a, $sup800, $ctx));
    }

    public function testAltitudeSousLes800MetresNeReleveRien(): void
    {
        $ctx = $this->context();

        self::assertSame('F', SeuilsClasses::energie(330, 100.0, 2, 2, $ctx));
        self::assertSame('F', SeuilsClasses::energie(330, 100.0, 2, 1, $ctx));
    }

    public function testAltitudeEtPetiteSurfaceSeCombinent(): void
    {
        $ctx = $this->context();

        // 8 m² au-dessus de 800 m en H1c : E monte à 682, mais A reste à 146.
        self::assertSame('E', SeuilsClasses::energie(681, 8.0, 3, 3, $ctx));
        self::assertSame('F', SeuilsClasses::energie(682, 8.0, 3, 3, $ctx));
        self::assertSame('B', SeuilsClasses::energie(146, 8.0, 3, 3, $ctx));
    }

    public function testZoneOuAltitudeInconnueRetombeSurLeBaremeNational(): void
    {
        $ctx = $this->context();

        self::assertSame('F', SeuilsClasses::energie(330, 100.0, null, null, $ctx));
    }

    public function testSurfaceNulleUtiliseLeBaremeNational(): void
    {
        self::assertSame('B', SeuilsClasses::energie(70, 0.0, 1, 1, $this->context()));
    }
}
