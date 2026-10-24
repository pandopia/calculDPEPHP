<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\ReseauChaleurFactorResolver;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReseauChaleurEnrResolverTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    #[DataProvider('editions')]
    public function testLesUnitesDeChaqueEditionSontNormalisees(string $date, float $rate): void
    {
        self::assertEqualsWithDelta($rate, $this->resolve($date, '0103C'), 1e-12);
    }

    public static function editions(): iterable
    {
        yield '2021 fraction' => ['2022-02-01', 0.715];
        yield '2022 fraction' => ['2023-06-01', 0.688];
        yield 'veille entrée édition 2023' => ['2024-08-09', 0.688];
        yield '2023 pourcentage' => ['2024-08-10', 0.896];
        yield 'veille entrée édition 2024' => ['2025-05-25', 0.896];
        yield 'entrée édition 2024' => ['2025-05-26', 0.848];
        yield '2024 pourcentage' => ['2025-06-01', 0.848];
        yield 'veille entrée édition 2025' => ['2026-05-24', 0.848];
        yield 'entrée édition 2025' => ['2026-05-25', 0.857];
        yield '2025 fraction' => ['2027-01-01', 0.857];
    }

    public function testLaDateArreteExplicitePrimeSurLaDateDpe(): void
    {
        self::assertEqualsWithDelta(0.715, $this->resolve('2027-01-01', '0103C', '<date_arrete_reseau_chaleur>2021-10-21</date_arrete_reseau_chaleur>'), 1e-12);
    }

    public function testUnZeroPublieNEstPasUneAbsence(): void
    {
        self::assertSame(0.0, $this->resolve('2027-01-01', '0611C'));
        self::assertNull($this->resolve('2027-01-01', '0606F'));
        self::assertNull($this->resolve('2021-08-01', '0103C'));
    }

    public function testLesLignesAutresReseauxNeRecuperentPasUnAncienTaux(): void
    {
        self::assertSame(0.0, $this->resolve('2027-01-01', '9999C'));
        self::assertNull($this->resolve('2027-01-01', '9999F'));
        $table = (new TableRepository(self::ROOT . '/resources/tables'))->load('reference/tv_reseau_chaleur_enr');
        $removed = array_diff_key($table[2021], $table[2025]);
        $tested = 0;
        foreach ($removed as $id => $rate) {
            if ($rate !== null && $rate > 0 && str_ends_with($id, 'C')) {
                self::assertSame(0.0, $this->resolve('2027-01-01', $id), $id);
                ++$tested;
            }
        }
        self::assertGreaterThan(0, $tested);
    }

    public function testLeTauxSourceImpossibleNEstNiPlafonneNiDiviseAuHasard(): void
    {
        $table = (new TableRepository(self::ROOT . '/resources/tables'))->load('reference/tv_reseau_chaleur_enr');
        // La source officielle publie réellement 1000 %, pas 100 %.
        self::assertSame(10.0, $table[2022]['5415C']);
        self::assertNull($this->resolve('2023-06-01', '5415C'));
        self::assertSame(1.0, $this->resolve('2025-06-01', '5415C'));
    }

    #[DataProvider('nonReseaux')]
    public function testUneChaufferieMultiBatimentNEstPasUnReseauUrbain(string $field, int $type): void
    {
        self::assertNull($this->resolve('2027-01-01', '0103C', "<$field>$type</$field>"));
    }

    public static function nonReseaux(): iterable
    {
        foreach ([109, 110, 111, 112, 171] as $type) {
            yield 'CH ' . $type => ['enum_type_generateur_ch_id', $type];
        }
        foreach ([74, 75, 76, 77, 134] as $type) {
            yield 'ECS ' . $type => ['enum_type_generateur_ecs_id', $type];
        }
    }

    #[DataProvider('identifiantsInvalides')]
    public function testUnIdentifiantAbsentOuInvalideNEstPasUnTauxNul(?string $id): void
    {
        self::assertNull($this->resolve('2027-01-01', $id));
    }

    public static function identifiantsInvalides(): iterable
    {
        foreach ([null, '', '103C', '0103X', '0103Cextra'] as $id) {
            yield [$id];
        }
    }

    public function testLaNormalisationXmlDesEspacesEstConservee(): void
    {
        self::assertEqualsWithDelta(0.857, $this->resolve('2027-01-01', " 0103C\n"), 1e-12);
    }

    public function testToutesLesLignesOfficiellesSontCouvertesSansInventerLesTauxAbsents(): void
    {
        $tables = new TableRepository(self::ROOT . '/resources/tables');
        $enr = $tables->load('reference/tv_reseau_chaleur_enr');
        $co2 = $tables->load('reference/tv_reseau_chaleur');
        $counts = [2020 => [797, 797], 2021 => [870, 32], 2022 => [895, 35], 2023 => [963, 40], 2024 => [1032, 42], 2025 => [1086, 47]];
        foreach ($counts as $year => [$rows, $absent]) {
            $expectedKeys = array_keys($co2[$year]);
            $actualKeys = array_keys($enr[$year]);
            sort($expectedKeys);
            sort($actualKeys);
            self::assertSame($expectedKeys, $actualKeys);
            self::assertCount($rows, $enr[$year]);
            self::assertCount($absent, array_filter($enr[$year], static fn($rate) => $rate === null));
        }
    }

    private function resolve(string $date, ?string $id, string $extra = ''): ?float
    {
        $networkId = $id === null ? '' : '<identifiant_reseau_chaleur>' . htmlspecialchars($id, ENT_XML1) . '</identifiant_reseau_chaleur>';
        $doc = new DOMDocument();
        $doc->loadXML("<dpe><date_etablissement_dpe>$date</date_etablissement_dpe><generateur_chauffage><donnee_entree>$networkId$extra</donnee_entree></generateur_chauffage></dpe>");
        $network = $doc->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $network);

        return ReseauChaleurFactorResolver::resolveRenewableShare($network, new NodeAccessor($doc), new CalculationContext($doc, new TableRepository(self::ROOT . '/resources/tables')));
    }
}
