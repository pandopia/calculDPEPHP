<?php

declare(strict_types=1);

namespace Tests\Unit\Engine;

use CalculDpePHP\Engine\CalculatorPipeline;
use CalculDpePHP\Engine\DpeEngine;
use CalculDpePHP\Engine\UnsupportedDpeModelException;
use CalculDpePHP\Tables\TableRepository;
use PHPUnit\Framework\TestCase;

/**
 * TASK-H11 : un DPE hors méthode 3CL logement (enum_modele_dpe_id ≠ 1, ou
 * structure <logement_neuf> sans <logement>) doit être refusé AVANT la purge
 * d'idempotence, au lieu de produire un fichier calculé vide.
 */
final class DpeEngineTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function engine(): DpeEngine
    {
        return new DpeEngine(
            new CalculatorPipeline(),
            new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    private function xmlNeuf(int $modeleId): string
    {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <dpe>
                <administratif>
                    <enum_modele_dpe_id>$modeleId</enum_modele_dpe_id>
                </administratif>
                <logement_neuf>
                    <sortie>
                        <ep_conso><ep_conso_5_usages_m2>63.3</ep_conso_5_usages_m2></ep_conso>
                    </sortie>
                </logement_neuf>
            </dpe>
            XML;
    }

    public function testDpeNeufRt2012EstRefuse(): void
    {
        $this->expectException(UnsupportedDpeModelException::class);
        $this->expectExceptionMessage('DPE neuf (RT2012) : non calculable par la méthode 3CL');

        $this->engine()->calculate($this->xmlNeuf(2));
    }

    public function testDpeNeufRe2020EstRefuse(): void
    {
        $this->expectException(UnsupportedDpeModelException::class);
        $this->expectExceptionMessage('DPE neuf (RE2020) : non calculable par la méthode 3CL');

        $this->engine()->calculate($this->xmlNeuf(3));
    }

    public function testDpeTertiaireEstRefuse(): void
    {
        $this->expectException(UnsupportedDpeModelException::class);
        $this->expectExceptionMessage('DPE 2006 tertiaire et ERP : non calculable par la méthode 3CL');

        $this->engine()->calculate($this->xmlNeuf(4));
    }

    public function testLogementNeufSansModeleIdEstRefuse(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <dpe>
                <administratif/>
                <logement_neuf/>
            </dpe>
            XML;

        $this->expectException(UnsupportedDpeModelException::class);
        $this->expectExceptionMessage('structure <logement_neuf> détectée');

        $this->engine()->calculate($xml);
    }

    public function testXmlSansLogementEstRefuse(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <dpe>
                <administratif>
                    <enum_modele_dpe_id>1</enum_modele_dpe_id>
                </administratif>
            </dpe>
            XML;

        $this->expectException(UnsupportedDpeModelException::class);
        $this->expectExceptionMessage('balise <logement> absente');

        $this->engine()->calculate($xml);
    }

    public function testRunNecritPasDeFichierPourUnDpeNeuf(): void
    {
        $dir = sys_get_temp_dir() . '/dpe-engine-test-' . uniqid();
        mkdir($dir);
        $input  = $dir . '/neuf.xml';
        $output = $dir . '/neuf.calculated.xml';
        file_put_contents($input, $this->xmlNeuf(3));

        try {
            $this->engine()->run($input, $output);
            $this->fail('UnsupportedDpeModelException attendue');
        } catch (UnsupportedDpeModelException) {
            // attendu
        } finally {
            $this->assertFileDoesNotExist($output, 'Aucun fichier de sortie ne doit être écrit pour un DPE neuf');
            @unlink($input);
            @unlink($output);
            @rmdir($dir);
        }
    }

    public function testDpeModele1AvecLogementEstAccepte(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <dpe>
                <administratif>
                    <enum_modele_dpe_id>1</enum_modele_dpe_id>
                </administratif>
                <logement>
                    <sortie/>
                </logement>
            </dpe>
            XML;

        $document = $this->engine()->calculate($xml);

        // Le garde-fou laisse passer, et la purge d'idempotence a bien eu lieu.
        $this->assertSame(0, $document->getElementsByTagName('sortie')->length);
        $this->assertSame(1, $document->getElementsByTagName('logement')->length);
    }
}
