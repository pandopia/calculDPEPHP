<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * `hvent` nul sous un renouvellement d'air qui ne l'est pas.
 *
 * Les déperditions par renouvellement d'air sont la somme de `Hvent` et
 * `Hperm`. Une référence qui publie `hvent = 0,000` et un
 * `deperdition_renouvellement_air` très supérieur à son seul `hperm` se
 * contredit sur son propre fichier — la preuve tient sans rien recalculer,
 * ce qui est le critère d'imputation à la référence.
 *
 * Cinq des six références de mode 26 du corpus le font ; la sixième,
 * `2501E0014261S`, publie 202,2966 + 76,8765 = 279,1731 et doit rester
 * conforme.
 */
final class HventNulTest extends TestCase
{
    private const DEP = 'dpe/logement/sortie/deperdition/';

    /** @param array<string, string> $valeurs */
    private function suspects(array $valeurs): array
    {
        $reference = [];
        foreach ($valeurs as $tag => $valeur) {
            $reference[self::DEP . $tag] = $valeur;
        }

        return ReferenceDefects::detect($reference);
    }

    /** Le cas réel de `2242E2979513I` : 0 + 818,943 ne font pas 2 861,935. */
    public function testHventNulSousUnRenouvellementDAirSuperieurEstSignale(): void
    {
        $suspects = $this->suspects([
            'hvent' => '0.000',
            'hperm' => '818.943',
            'deperdition_renouvellement_air' => '2861.935',
        ]);

        self::assertArrayHasKey('hvent', $suspects);
        self::assertStringContainsString('2861.935', $suspects['hvent']);
    }

    /**
     * Les situations où la référence est cohérente, ou simplement muette.
     *
     * @return iterable<string, array{array<string, string>}>
     */
    public static function referencesSaines(): iterable
    {
        yield 'reference coherente de 2501E0014261S' => [[
            'hvent' => '202.29660000000004',
            'hperm' => '76.87647268547326',
            'deperdition_renouvellement_air' => '279.1730726854733',
        ]];
        yield 'hvent nul et renouvellement reduit au seul hperm' => [[
            'hvent' => '0',
            'hperm' => '818.943',
            'deperdition_renouvellement_air' => '818.943',
        ]];
        yield 'hvent renseigne, meme si le total ne colle pas' => [[
            'hvent' => '100',
            'hperm' => '818.943',
            'deperdition_renouvellement_air' => '2861.935',
        ]];
        yield 'renouvellement d air absent' => [[
            'hvent' => '0',
            'hperm' => '818.943',
        ]];
    }

    /** @param array<string, string> $valeurs */
    #[DataProvider('referencesSaines')]
    public function testAucunSignalementHorsContradiction(array $valeurs): void
    {
        self::assertArrayNotHasKey('hvent', $this->suspects($valeurs));
    }
}
