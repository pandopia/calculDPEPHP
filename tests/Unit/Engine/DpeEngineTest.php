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

    /**
     * Document minimal portant un générateur du type donné.
     *
     * @param 'generateur_chauffage'|'generateur_ecs' $balise
     */
    private function xmlAvecGenerateur(string $balise, string $champ, int $type): string
    {
        $collection = $balise . '_collection';
        $installation = $balise === 'generateur_chauffage' ? 'installation_chauffage' : 'installation_ecs';

        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <dpe>
                <administratif><enum_modele_dpe_id>1</enum_modele_dpe_id></administratif>
                <logement>
                    <caracteristique_generale><surface_habitable_logement>80</surface_habitable_logement></caracteristique_generale>
                    <{$installation}_collection><{$installation}>
                        <{$collection}><{$balise}>
                            <donnee_entree><{$champ}>{$type}</{$champ}></donnee_entree>
                        </{$balise}></{$collection}>
                    </{$installation}></{$installation}_collection>
                </logement>
            </dpe>
            XML;
    }

    /**
     * §Règle d'or n° 2 : le modèle 2.7 ajoute des technologies dont les
     * caractéristiques ne sont pas digitalisées. Sans garde, le moteur ne
     * crashe pas — il produit une consommation calculée avec un rendement de
     * génération implicite de 1, sans rien signaler.
     *
     * @return iterable<string, array{string, string, int}>
     */
    public static function technologies27(): iterable
    {
        yield 'chaudière bois granulés à condensation après 2024' => ['generateur_chauffage', 'enum_type_generateur_ch_id', 177];
        yield 'PAC air/eau à partir de 2027' => ['generateur_chauffage', 'enum_type_generateur_ch_id', 173];
        yield 'CET sur air extérieur à partir de 2027' => ['generateur_ecs', 'enum_type_generateur_ecs_id', 136];
        yield 'ballon à commande intelligente' => ['generateur_ecs', 'enum_type_generateur_ecs_id', 140];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('technologies27')]
    public function testUneTechnologieDu27EstRefuseeAuLieuDEtreCalculeeAFaux(
        string $balise,
        string $champ,
        int $type,
    ): void {
        $this->expectException(UnsupportedDpeModelException::class);
        $this->expectExceptionMessage("de type $type : technologie introduite par le modèle DPE 2.7");

        $this->engine()->calculate($this->xmlAvecGenerateur($balise, $champ, $type));
    }

    /** Les bornes du modèle 2.6 restent calculables. */
    public function testLesBornesDu26RestentAcceptees(): void
    {
        $this->engine()->calculate($this->xmlAvecGenerateur('generateur_chauffage', 'enum_type_generateur_ch_id', 171));
        $this->engine()->calculate($this->xmlAvecGenerateur('generateur_ecs', 'enum_type_generateur_ecs_id', 134));

        $this->expectNotToPerformAssertions();
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
