<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use PHPUnit\Framework\TestCase;

/**
 * Le coût, l'énergie primaire et les émissions sont, à énergie donnée, des
 * multiples fixes de la consommation finale. Publier la même valeur pour le
 * scénario conventionnel et le scénario dépensier alors que leurs
 * consommations diffèrent est une contradiction du fichier seul.
 */
final class ValeurDepensiereRecopieeTest extends TestCase
{
    /** @return array<string, string> */
    private function sortie(float $ep, float $epDep, float $ges, float $gesDep): array
    {
        return [
            'dpe/logement/sortie/ef_conso/conso_ch' => '1000',
            'dpe/logement/sortie/ef_conso/conso_ch_depensier' => '1250',
            'dpe/logement/sortie/ep_conso/ep_conso_ch' => (string) $ep,
            'dpe/logement/sortie/ep_conso/ep_conso_ch_depensier' => (string) $epDep,
            'dpe/logement/sortie/emission_ges/emission_ges_ch' => (string) $ges,
            'dpe/logement/sortie/emission_ges/emission_ges_ch_depensier' => (string) $gesDep,
        ];
    }

    public function testEnergiePrimaireRecopieeEstImputeeALaReference(): void
    {
        $suspects = ReferenceDefects::detect($this->sortie(2300.0, 2300.0, 220.0, 275.0));

        self::assertArrayHasKey('ep_conso_ch_depensier', $suspects);
        self::assertArrayNotHasKey('emission_ges_ch_depensier', $suspects);
    }

    public function testEmissionsRecopieesSontImputeesALaReference(): void
    {
        $suspects = ReferenceDefects::detect($this->sortie(2300.0, 2875.0, 220.0, 220.0));

        self::assertArrayHasKey('emission_ges_ch_depensier', $suspects);
        self::assertArrayNotHasKey('ep_conso_ch_depensier', $suspects);
    }

    /** Deux valeurs cohérentes avec leurs consommations ne déclenchent rien. */
    public function testLesValeursCoherentesNeSontPasSuspectes(): void
    {
        $suspects = ReferenceDefects::detect($this->sortie(2300.0, 2875.0, 220.0, 275.0));

        self::assertArrayNotHasKey('ep_conso_ch_depensier', $suspects);
        self::assertArrayNotHasKey('emission_ges_ch_depensier', $suspects);
    }

    /** Des consommations identiques justifient des agrégats identiques. */
    public function testDesConsommationsEgalesNeSontPasSuspectes(): void
    {
        $expected = $this->sortie(2300.0, 2300.0, 220.0, 220.0);
        $expected['dpe/logement/sortie/ef_conso/conso_ch_depensier'] = '1000';

        $suspects = ReferenceDefects::detect($expected);

        self::assertArrayNotHasKey('ep_conso_ch_depensier', $suspects);
        self::assertArrayNotHasKey('emission_ges_ch_depensier', $suspects);
    }
}
