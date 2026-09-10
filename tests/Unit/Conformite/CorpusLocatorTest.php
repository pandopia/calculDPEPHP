<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\CorpusLocator;
use PHPUnit\Framework\TestCase;

final class CorpusLocatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/conformite_' . bin2hex(random_bytes(6));
        foreach ([
            '/resources/XML/input',
            '/resources/XML/verif',
            '/resources/XML/official/jeu-a/input',
            '/resources/XML/official/jeu-a/expected',
        ] as $dir) {
            mkdir($this->root . $dir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($it as $path) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }
        rmdir($this->root);
    }

    private function write(string $relative, string $xml = '<dpe><logement/></dpe>'): void
    {
        file_put_contents($this->root . $relative, $xml);
    }

    public function testAppariementInputExpected(): void
    {
        $this->write('/resources/XML/official/jeu-a/input/A.xml');
        $this->write('/resources/XML/official/jeu-a/expected/A.xml');
        $this->write('/resources/XML/input/B.xml');
        $this->write('/resources/XML/verif/B.xml');

        $cases = (new CorpusLocator($this->root))->locate();

        self::assertCount(2, $cases);
        self::assertSame('jeu-a', $cases[0]['corpus']);
        self::assertSame(CorpusLocator::LEGACY_CORPUS, $cases[1]['corpus']);
    }

    /**
     * Une référence démontrée fausse est hors mesure, y compris si un nouveau
     * `bin/fetch-dpe` la réécrit sur le disque.
     */
    public function testReferenceDemontreeFausseEstEcartee(): void
    {
        $invalide = array_key_first(CorpusLocator::REFERENCES_INVALIDES);
        self::assertIsString($invalide);

        $this->write('/resources/XML/input/' . $invalide);
        $this->write('/resources/XML/verif/' . $invalide);
        $this->write('/resources/XML/input/valide.xml');
        $this->write('/resources/XML/verif/valide.xml');

        $cases = (new CorpusLocator($this->root))->locate();

        self::assertCount(1, $cases);
        self::assertSame('valide.xml', $cases[0]['name']);
    }

    /** Chaque exclusion porte sa preuve, établie sur le fichier de référence seul. */
    public function testChaqueReferenceInvalidePorteSaPreuve(): void
    {
        self::assertNotSame([], CorpusLocator::REFERENCES_INVALIDES);
        foreach (CorpusLocator::REFERENCES_INVALIDES as $nom => $preuve) {
            self::assertMatchesRegularExpression('/\.xml$/', $nom);
            self::assertGreaterThan(40, strlen($preuve), "Preuve trop courte pour $nom");
        }
    }

    public function testInputSansReferenceEstIgnore(): void
    {
        $this->write('/resources/XML/input/orphelin.xml');

        self::assertSame([], (new CorpusLocator($this->root))->locate());
    }

    public function testSortiesDeTravailCalculatedSontIgnorees(): void
    {
        $this->write('/resources/XML/input/A.calculated.xml');
        $this->write('/resources/XML/verif/A.calculated.xml');

        self::assertSame([], (new CorpusLocator($this->root))->locate());
    }

    public function testLogementNeufEstHorsPerimetre3cl(): void
    {
        // RT2012 / RE2020 relèvent d'une autre méthode que le 3CL existant.
        $this->write('/resources/XML/input/neuf.xml', '<dpe><logement_neuf/></dpe>');
        $this->write('/resources/XML/verif/neuf.xml', '<dpe><logement_neuf/></dpe>');

        self::assertSame([], (new CorpusLocator($this->root))->locate());
        self::assertTrue(CorpusLocator::isLogementNeuf($this->root . '/resources/XML/verif/neuf.xml'));
    }

    public function testFiltresCorpusEtNom(): void
    {
        $this->write('/resources/XML/official/jeu-a/input/A.xml');
        $this->write('/resources/XML/official/jeu-a/expected/A.xml');
        $this->write('/resources/XML/input/2657E1981571R.xml');
        $this->write('/resources/XML/verif/2657E1981571R.xml');

        $locator = new CorpusLocator($this->root);

        self::assertCount(1, $locator->locate('jeu-a'));
        self::assertCount(1, $locator->locate(null, '2657E'));
        self::assertSame([], $locator->locate('jeu-inexistant'));
    }

    public function testDecouverteDesJeuxOfficiels(): void
    {
        self::assertSame(['jeu-a'], array_keys((new CorpusLocator($this->root))->officialCorpora()));
    }
}
