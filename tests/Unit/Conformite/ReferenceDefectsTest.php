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
