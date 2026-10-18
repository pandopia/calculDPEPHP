<?php

declare(strict_types=1);

namespace Tests\Unit\Engine;

use CalculDpePHP\Engine\CalculatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tout Calculator écrit doit être branché dans le pipeline.
 *
 * `CollectifBaseAppoint` (§9.8, `enum_cfg_installation_ch_id` = 10) est resté
 * absent de `DefaultDpeEngineFactory` alors que la classe, ses tables et ses
 * tests unitaires existaient : une branche entière de la méthode ne produisait
 * rien, et les fichiers concernés sortaient sans `besoin_ch` ni `conso_ch`.
 * Rien ne l'avait signalé — ses tests unitaires l'instanciaient directement,
 * et le harness ne mesurait que les balises présentes.
 *
 * Le contrôle est générique et porte sur la source : il ne connaît pas la
 * liste des Calculators, il la découvre.
 */
final class PipelineCompletTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    /** Domaines dont chaque Calculator doit être cité par la fabrique. */
    private const DOSSIERS = [
        'src/Chauffage/Strategy',
        'src/Ecs',
        'src/Froid',
        'src/Auxiliaire',
        'src/Ventilation',
        'src/Sortie',
    ];

    public function testChaqueCalculatorEstBrancheDansLaFabrique(): void
    {
        $fabrique = file_get_contents(self::PROJECT_ROOT . '/src/Engine/DefaultDpeEngineFactory.php');
        self::assertIsString($fabrique);

        $absents = [];
        foreach ($this->calculators() as $classe) {
            $court = substr($classe, strrpos($classe, '\\') + 1);
            if (!str_contains($fabrique, $court . '(')) {
                $absents[] = $classe;
            }
        }

        self::assertSame([], $absents, "Calculators jamais ajoutés au pipeline :\n" . implode("\n", $absents));
    }

    /**
     * Le test ne vaut que s'il voit réellement des classes : une erreur de
     * chemin le rendrait vert sans rien contrôler.
     */
    public function testLaDecouverteTrouveBienDesCalculators(): void
    {
        self::assertGreaterThan(30, count($this->calculators()));
    }

    /** @return list<class-string<CalculatorInterface>> */
    private function calculators(): array
    {
        $trouves = [];
        foreach (self::DOSSIERS as $dossier) {
            $chemin = self::PROJECT_ROOT . '/' . $dossier;
            if (!is_dir($chemin)) {
                continue;
            }

            $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($chemin));
            foreach ($fichiers as $fichier) {
                if (!$fichier->isFile() || $fichier->getExtension() !== 'php') {
                    continue;
                }

                $source = file_get_contents($fichier->getPathname());
                if ($source === false
                    || !preg_match('/^namespace\s+([^;]+);/m', $source, $ns)
                    || !preg_match('/^(?:final\s+)?class\s+(\w+)/m', $source, $cl)) {
                    continue;
                }

                $classe = $ns[1] . '\\' . $cl[1];
                if (class_exists($classe) && is_a($classe, CalculatorInterface::class, true)) {
                    $trouves[] = $classe;
                }
            }
        }

        return $trouves;
    }
}
