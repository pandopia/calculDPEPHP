<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use PHPUnit\Framework\TestCase;

/**
 * Conventionnel et dépensier publiés l'un à la place de l'autre.
 *
 * Même prémisse que {@see ValeurDepensiereRecopieeTest} : à énergie donnée,
 * le rapport `valeur / consommation` est le facteur de l'énergie, le même
 * dans les deux scénarios. S'il diffère en direct mais coïncide une fois
 * croisé, les deux valeurs sont justes et c'est leur place qui ne l'est pas.
 */
final class ValeursDepensieresPermuteesTest extends TestCase
{
    /**
     * Les chiffres sont ceux de `2242E2979513I`, dont l'ECS est desservie par
     * un réseau de chaleur à 0,109 kgCO2/kWh.
     *
     * @return array<string, string>
     */
    private function sortie(float $ges, float $gesDep): array
    {
        return [
            'dpe/logement/sortie/ef_conso/conso_ecs' => '206053.9',
            'dpe/logement/sortie/ef_conso/conso_ecs_depensier' => '290536.0',
            'dpe/logement/sortie/emission_ges/emission_ges_ecs' => (string) $ges,
            'dpe/logement/sortie/emission_ges/emission_ges_ecs_depensier' => (string) $gesDep,
        ];
    }

    /** Les deux balises sont imputées : aucune des deux n'est à sa place. */
    public function testUnePermutationEstImputeeSurLesDeuxBalises(): void
    {
        $suspects = ReferenceDefects::detect($this->sortie(31668.4, 22459.9));

        self::assertArrayHasKey('emission_ges_ecs', $suspects);
        self::assertArrayHasKey('emission_ges_ecs_depensier', $suspects);
        self::assertStringContainsString('permutés', $suspects['emission_ges_ecs']);
        self::assertStringContainsString('0.10900', $suspects['emission_ges_ecs']);
    }

    /** Les valeurs remises à l'endroit ne déclenchent rien. */
    public function testLOrdreCorrectNeDeclencheRien(): void
    {
        $suspects = ReferenceDefects::detect($this->sortie(22459.9, 31668.4));

        self::assertArrayNotHasKey('emission_ges_ecs', $suspects);
        self::assertArrayNotHasKey('emission_ges_ecs_depensier', $suspects);
    }

    /**
     * Un dépensier simplement faux, sans facteur commun croisé, n'est pas une
     * permutation : la règle ne doit pas servir de filet à tout désaccord.
     */
    public function testUnDepensierFauxSansFacteurCommunNEstPasUnePermutation(): void
    {
        $suspects = ReferenceDefects::detect($this->sortie(22459.9, 19000.0));

        self::assertArrayNotHasKey('emission_ges_ecs', $suspects);
    }

    /**
     * La règle de recopie passe la première : une valeur dépensière identique
     * au conventionnel reste imputée comme telle, et sa preuve ne devient pas
     * celle d'une permutation.
     */
    public function testLaRecopieResteQualifieeCommeRecopie(): void
    {
        $suspects = ReferenceDefects::detect($this->sortie(22459.9, 22459.9));

        self::assertArrayHasKey('emission_ges_ecs_depensier', $suspects);
        self::assertStringContainsString('recopie', $suspects['emission_ges_ecs_depensier']);
    }

    /** Une consommation nulle ne permet aucun rapport : rien n'est conclu. */
    public function testUneConsommationNulleNeConcluRien(): void
    {
        $suspects = ReferenceDefects::detect([
            'dpe/logement/sortie/ef_conso/conso_fr' => '0',
            'dpe/logement/sortie/ef_conso/conso_fr_depensier' => '0',
            'dpe/logement/sortie/emission_ges/emission_ges_fr' => '0',
            'dpe/logement/sortie/emission_ges/emission_ges_fr_depensier' => '0',
        ]);

        self::assertArrayNotHasKey('emission_ges_fr', $suspects);
    }
}
