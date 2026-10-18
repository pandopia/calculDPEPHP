<?php

declare(strict_types=1);

namespace Tests\Unit\Tables;

use CalculDpePHP\Tables\TableRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Table des contenus CO2 des réseaux de chaleur et de froid.
 *
 * Les arrêtés annuels publient, pour chaque réseau, un contenu CO2 et un
 * contenu CO2 ACV ; c'est la colonne ACV que cette table porte **à partir du
 * millésime 2021**. Aucun réseau n'y a de contenu nul : un zéro signalerait
 * une cellule perdue à la digitalisation, et il se traduirait en production
 * par des émissions de chauffage ou d'ECS nulles pour tout DPE desservi par
 * ce réseau.
 *
 * Le millésime 2020 fait exception à double titre, et ce n'est pas un défaut :
 * il est indexé par `tv_reseau_chaleur_id` (entier) et non par l'identifiant
 * national, et l'arrêté du 12 octobre 2020 étant antérieur au comptage ACV, il
 * publie un contenu **direct** — lequel affecte zéro à la combustion de
 * biomasse, d'où ses réseaux bois à 0,000.
 */
final class TvReseauChaleurTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    /** @return array<int, array<string, float>> */
    private function table(): array
    {
        return (new TableRepository(self::PROJECT_ROOT . '/resources/tables'))
            ->load('reference/tv_reseau_chaleur');
    }

    public function testAucunReseauNAUnContenuCo2NulDepuisLeComptageAcv(): void
    {
        foreach ($this->table() as $millesime => $reseaux) {
            if ($millesime === 2020) {
                continue;
            }

            $nuls = array_keys(array_filter($reseaux, static fn(float $v): bool => $v <= 0.0));

            self::assertSame(
                [],
                $nuls,
                sprintf('Millésime %d : contenu CO2 nul pour %s', $millesime, implode(', ', $nuls)),
            );
        }
    }

    /**
     * Les six millésimes couvrent les arrêtés de 2020 à 2026 — la méthode 3CL
     * s'appliquant depuis le 1er juillet 2021, aucun DPE ne peut tomber hors
     * de cette plage — et leur nombre de réseaux croît avec les mises à jour
     * successives.
     */
    public function testLesMillesimesSontComplets(): void
    {
        $table = $this->table();

        self::assertSame([2020, 2021, 2022, 2023, 2024, 2025], array_keys($table));

        $precedent = 0;
        foreach ($table as $reseaux) {
            self::assertGreaterThan($precedent, count($reseaux));
            $precedent = count($reseaux);
        }
    }

    /**
     * Les deux index ne se mélangent pas : le millésime 2020 est entièrement
     * entier, les suivants entièrement en identifiants `NNNNC` ou `NNNNF`.
     * Le XSD date la bascule du 18 janvier 2022, en déclarant
     * `tv_reseau_chaleur_id` « OBSOLETE et remplacé par
     * identifiant_reseau_chaleur à partir du 18 janvier 2022 ».
     */
    public function testChaqueMillesimeNaQuUnSeulTypeDIndex(): void
    {
        foreach ($this->table() as $millesime => $reseaux) {
            foreach (array_keys($reseaux) as $cle) {
                if ($millesime === 2020) {
                    self::assertIsInt($cle, "Millésime 2020 : clé non entière $cle");
                    continue;
                }

                self::assertMatchesRegularExpression(
                    '/^[0-9]{4}[CF]$/',
                    (string) $cle,
                    "Millésime $millesime : identifiant hors format",
                );
            }
        }
    }

    /**
     * Millésime 2021 — arrêté du 21 octobre 2021, colonne « Contenu CO2 ACV ».
     *
     * `4211C` est la valeur que la référence `2242E2979513I` (DPE du
     * 6 avril 2022) implique : ses trois lignes `sortie_par_energie` pour le
     * réseau de chaleur donnent un rapport émissions / consommation de
     * 0,109000 à six décimales, quand le millésime 2022 donnerait 0,075.
     *
     * @return iterable<string, array{string, float}>
     */
    public static function valeurs2021(): iterable
    {
        yield 'Andrezieux-Boutheon' => ['4211C', 0.109];
        yield 'Quartier de La Reyssouze, Bourg-en-Bresse' => ['0101C', 0.194];
        yield 'Oyonnax BioChaleur' => ['0103C', 0.095];
        yield 'Belena, Belley' => ['0106C', 0.061];
    }

    #[DataProvider('valeurs2021')]
    public function testMillesime2021(string $identifiant, float $attendu): void
    {
        self::assertEqualsWithDelta($attendu, $this->table()[2021][$identifiant], 1e-9);
    }

    /**
     * Millésime 2020 — arrêté du 12 octobre 2020, indexé par numéro de ligne
     * et publiant le contenu CO2 **direct**.
     *
     * @return iterable<string, array{int, float}>
     */
    public static function valeurs2020(): iterable
    {
        yield 'La Reyssouze, Bourg-en-Bresse' => [1, 0.155];
        yield 'Oyonnax BioChaleur' => [3, 0.046];
        yield 'Andrezieux-Boutheon' => [274, 0.111];
        yield 'reseau bois, comptage direct a zero' => [6, 0.0];
    }

    #[DataProvider('valeurs2020')]
    public function testMillesime2020(int $ligne, float $attendu): void
    {
        self::assertEqualsWithDelta($attendu, $this->table()[2020][$ligne], 1e-9);
    }

    /**
     * Valeurs relues ligne à ligne dans l'annexe de l'arrêté du 11 avril 2025
     * (JORFTEXT000051520810), colonne « Contenu CO2 ACV ».
     *
     * @return iterable<string, array{string, float}>
     */
    public static function valeursRelues(): iterable
    {
        yield 'Belena, Belley' => ['0106C', 0.040];
        yield 'Chaufferie bois, Valgorge' => ['0703C', 0.020];
        yield 'Reseau de Bourganeuf' => ['2301C', 0.040];
        yield 'Reseau d Amneville' => ['5720C', 0.040];
        yield 'Reseau de Denain' => ['5922C', 0.020];
        yield 'Eco-quartier Brasserie Cronenbourg' => ['6704C', 0.030];
        yield 'Reseau d Argenteuil' => ['9509C', 0.040];
        yield 'Reseau froid CCIAG Grenoble' => ['3802F', 0.030];
    }

    #[DataProvider('valeursRelues')]
    public function testMillesime2024(string $identifiant, float $attendu): void
    {
        self::assertEqualsWithDelta($attendu, $this->table()[2024][$identifiant], 1e-9);
    }
}
