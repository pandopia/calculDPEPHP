<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ComparisonStatus;
use CalculDpePHP\Conformite\ToleranceProfile;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ToleranceProfileTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    public function testProfilStrictNAucunAssouplissementParBalise(): void
    {
        $profile = ToleranceProfile::strict();

        // `hperm` bénéficie de 1 % dans tests/tolerances.php : le profil de
        // mesure de conformité ne doit pas hériter de cet assouplissement.
        self::assertSame(1e-3, $profile->toleranceFor('hperm'));
        self::assertSame(1e-3, $profile->toleranceFor('conso_ch'));
    }

    public function testProfilRepoReprendLesOverridesDuHarness(): void
    {
        $profile = ToleranceProfile::repo(self::PROJECT_ROOT);

        self::assertSame(0.01, $profile->toleranceFor('hperm'));
        self::assertSame(1e-3, $profile->toleranceFor('balise_sans_override'));
    }

    public function testValeursIdentiquesSontExactes(): void
    {
        $r = ToleranceProfile::strict()->compareNumeric('conso_ch', 1234.5, 1234.5);

        self::assertSame(ComparisonStatus::EXACT, $r['status']);
        self::assertSame(0.0, $r['delta_abs']);
    }

    public function testEcartSousLaToleranceEstAccepte(): void
    {
        $r = ToleranceProfile::strict()->compareNumeric('conso_ch', 1000.0, 1000.5);

        self::assertSame(ComparisonStatus::WITHIN, $r['status']);
        self::assertEqualsWithDelta(0.05, $r['delta_percent'], 1e-9);
    }

    public function testEcartAuDessusDeLaToleranceEstSignale(): void
    {
        $r = ToleranceProfile::strict()->compareNumeric('conso_ch', 1000.0, 1002.0);

        self::assertSame(ComparisonStatus::OUT, $r['status']);
        self::assertEqualsWithDelta(0.2, $r['delta_percent'], 1e-9);
    }

    public function testBalisesArrondiesDansLaReferenceTolerentUnDemi(): void
    {
        // La référence ADEME stocke ces balises à l'entier : exiger mieux que
        // ±0,5 reviendrait à comparer à une précision inexistante.
        $strict = ToleranceProfile::strict();

        self::assertSame(ComparisonStatus::WITHIN, $strict->compareNumeric('conso_5_usages_m2', 139.0, 138.6)['status']);
        self::assertSame(ComparisonStatus::OUT, $strict->compareNumeric('conso_5_usages_m2', 139.0, 137.9)['status']);
        // …et cela ne déborde pas sur les balises non arrondies.
        self::assertSame(ComparisonStatus::OUT, $strict->compareNumeric('conso_5_usages', 139.0, 138.6)['status']);
    }

    public function testReferenceNulleUtiliseLEcartAbsolu(): void
    {
        $strict = ToleranceProfile::strict();

        self::assertSame(ComparisonStatus::EXACT, $strict->compareNumeric('conso_fr', 0.0, 0.0)['status']);
        self::assertNull($strict->compareNumeric('conso_fr', 0.0, 5.0)['delta_percent']);
        self::assertSame(ComparisonStatus::OUT, $strict->compareNumeric('conso_fr', 0.0, 5.0)['status']);
    }

    public function testProfilInconnuEstRefuse(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ToleranceProfile::named('laxiste', self::PROJECT_ROOT);
    }

    public function testProfilsNommes(): void
    {
        self::assertSame('strict', ToleranceProfile::named('strict', self::PROJECT_ROOT)->name);
        self::assertSame('reglementaire', ToleranceProfile::named('reglementaire', self::PROJECT_ROOT)->name);
        self::assertSame('repo', ToleranceProfile::named('repo', self::PROJECT_ROOT)->name);
        self::assertSame(1e-2, ToleranceProfile::reglementaire()->toleranceFor('conso_ch'));
    }
}
