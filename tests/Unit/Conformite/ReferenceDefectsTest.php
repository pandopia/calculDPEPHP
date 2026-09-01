<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use PHPUnit\Framework\TestCase;

final class ReferenceDefectsTest extends TestCase
{
    private const COUT = 'dpe/logement/sortie/cout/';
    private const EF = 'dpe/logement/sortie/ef_conso/';

    /** @param array<string, string> $extra */
    private function reference(array $extra = []): array
    {
        return array_merge([
            self::EF . 'conso_ch' => '10000',
            self::EF . 'conso_ch_depensier' => '13000',
            self::COUT . 'cout_ch' => '900',
            self::COUT . 'cout_ch_depensier' => '900',
        ], $extra);
    }

    public function testCoutDepensierRecopieEstSignale(): void
    {
        // Le schéma décrit cout_ch_depensier comme le coût du scénario
        // dépensier : deux consommations différentes ne peuvent pas coûter
        // exactement la même chose.
        $suspects = ReferenceDefects::detect($this->reference());

        self::assertArrayHasKey('cout_ch_depensier', $suspects);
        self::assertStringContainsString('10000', $suspects['cout_ch_depensier']);
        self::assertStringContainsString('13000', $suspects['cout_ch_depensier']);
    }

    public function testCoutDepensierDistinctNestPasSignale(): void
    {
        $suspects = ReferenceDefects::detect($this->reference([self::COUT . 'cout_ch_depensier' => '1150']));

        self::assertSame([], $suspects);
    }

    public function testConsommationsIdentiquesNeSontPasUnDefaut(): void
    {
        // Même consommation, même coût : c'est cohérent, pas suspect.
        $suspects = ReferenceDefects::detect($this->reference([self::EF . 'conso_ch_depensier' => '10000']));

        self::assertSame([], $suspects);
    }

    public function testCoutNulNestPasSignale(): void
    {
        $suspects = ReferenceDefects::detect($this->reference([
            self::COUT . 'cout_ch' => '0',
            self::COUT . 'cout_ch_depensier' => '0',
        ]));

        self::assertSame([], $suspects);
    }

    public function testBalisesAbsentesNeDeclenchentRien(): void
    {
        self::assertSame([], ReferenceDefects::detect([]));
    }

    /**
     * Le schéma rend `confort_ete` facultatif mais impose
     * `protection_solaire_exterieure` dès que le bloc est là : un bloc vide le
     * viole, et le contenu que nous produisons n'est pas de trop.
     */
    public function testBlocConfortEteVideEstSignale(): void
    {
        $suspects = ReferenceDefects::detect(['dpe/logement/sortie/confort_ete' => '']);

        self::assertArrayHasKey('protection_solaire_exterieure', $suspects);
        self::assertArrayHasKey('enum_indicateur_confort_ete_id', $suspects);
        self::assertArrayHasKey('inertie_lourde', $suspects);
    }

    public function testBlocConfortEteRenseigneNestPasSignale(): void
    {
        // Le bloc est un conteneur : renseigné, il n'apparaît pas comme feuille
        // vide dans les valeurs extraites.
        $suspects = ReferenceDefects::detect([
            'dpe/logement/sortie/confort_ete/protection_solaire_exterieure' => '1',
        ]);

        self::assertSame([], $suspects);
    }

    public function testBlocConfortEteAbsentNestPasSignale(): void
    {
        self::assertSame([], ReferenceDefects::detect(['dpe/logement/sortie/ep_conso/classe_bilan_dpe' => 'D']));
    }

    public function testTousLesPostesConcernesSontCouverts(): void
    {
        $suspects = ReferenceDefects::detect([
            self::EF . 'conso_ecs' => '5000', self::EF . 'conso_ecs_depensier' => '7000',
            self::COUT . 'cout_ecs' => '400', self::COUT . 'cout_ecs_depensier' => '400',
            self::EF . 'conso_auxiliaire_generation_ch' => '100',
            self::EF . 'conso_auxiliaire_generation_ch_depensier' => '150',
            self::COUT . 'cout_auxiliaire_generation_ch' => '30',
            self::COUT . 'cout_auxiliaire_generation_ch_depensier' => '30',
        ]);

        self::assertSame(
            ['cout_ecs_depensier', 'cout_auxiliaire_generation_ch_depensier'],
            array_keys($suspects),
        );
    }
}
