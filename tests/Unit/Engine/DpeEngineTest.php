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

    #[\PHPUnit\Framework\Attributes\DataProvider('froidsNonCouverts')]
    public function testFroidNonElectriqueRefuseAvantPurge(?int $type, ?int $energie): void
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<dpe><logement><climatisation><donnee_entree>'
            . ($type === null ? '' : '<enum_type_generateur_fr_id>' . $type . '</enum_type_generateur_fr_id>')
            . ($energie === null ? '' : '<enum_type_energie_id>' . $energie . '</enum_type_energie_id>')
            . '</donnee_entree></climatisation><sortie><a_conserver>123</a_conserver></sortie></logement></dpe>');
        $before = $doc->saveXML();
        try {
            $this->engine()->calculateDocument($doc);
            self::fail('Le froid non électrique ne doit pas être compté comme électricité du réseau.');
        } catch (UnsupportedDpeModelException $exception) {
            self::assertStringContainsString('refroidissement', $exception->getMessage());
            self::assertSame($before, $doc->saveXML());
        }
    }

    public static function froidsNonCouverts(): iterable
    {
        yield 'gaz sans énergie explicite' => [21, null];
        yield 'gaz avec énergie contradictoire' => [21, 1];
        yield 'réseau de froid sans énergie' => [23, null];
        foreach (range(2, 15) as $energie) {
            yield "énergie explicite $energie" => [null, $energie];
        }
    }

    public function testFroidElectriqueHistoriqueAccepte(): void
    {
        foreach ([null, 1, 20, 22] as $type) {
            foreach (['', '<enum_type_energie_id>1</enum_type_energie_id>', '<enum_type_energie_id xsi:nil="true"/>'] as $energie) {
                $this->engine()->calculate('<dpe xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><logement><climatisation><donnee_entree>'
                    . ($type === null ? '' : '<enum_type_generateur_fr_id>' . $type . '</enum_type_generateur_fr_id>')
                    . $energie . '</donnee_entree></climatisation></logement></dpe>');
            }
        }
        $this->expectNotToPerformAssertions();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('aerationsVersionnees')]
    public function testAeration27RefuseeSansChangerLesAnciensDpe(?string $version, int $type, bool $refus): void
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<dpe>' . ($version === null ? '' : '<administratif><enum_version_id>' . $version . '</enum_version_id></administratif>')
            . '<logement><ventilation><donnee_entree><enum_type_ventilation_id>' . $type . '</enum_type_ventilation_id></donnee_entree></ventilation>'
            . '<sortie><a_conserver>123</a_conserver></sortie></logement></dpe>');
        $before = $doc->saveXML();
        try {
            $this->engine()->calculateDocument($doc);
            self::assertFalse($refus, 'La répartition ventilation/aération 2.7 ne doit pas utiliser silencieusement la méthode historique.');
        } catch (UnsupportedDpeModelException $exception) {
            self::assertTrue($refus);
            self::assertStringContainsString('aération', $exception->getMessage());
            self::assertSame($before, $doc->saveXML());
        }
    }

    public static function aerationsVersionnees(): iterable
    {
        yield 'historique sans version' => [null, 1, false];
        yield 'modèle 2.6' => ['2.6', 1, false];
        yield 'aération 2.7 non couverte' => ['2.7', 1, true];
        yield 'ventilation naturelle par conduit 2.7' => ['2.7', 2, false];
        yield 'VMC 2.7' => ['2.7', 3, false];
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

    /** Options introduites par les appinfo du XSD 2.7, encore sans calcul. */
    public static function options27NonCouvertes(): iterable
    {
        yield 'solaire ECS seule' => ['installation_ecs', 'enum_type_installation_solaire_id', '5'];
        yield 'module thermique appartement' => ['installation_ecs', 'enum_bouclage_reseau_ecs_id', '4'];
        yield 'régulation prédictive' => ['emetteur_chauffage', 'enum_equipement_intermittence_id', '8'];
        yield 'CET installé en 2027' => ['generateur_ecs', 'enum_periode_installation_ecs_thermo_id', '4'];
        yield 'récupération eaux grises' => ['installation_ecs', 'presence_rceg', '1'];
        yield 'commande intelligente' => ['generateur_ecs', 'scf_saisi', '0.9'];
        yield 'commande intelligente zéro explicite' => ['generateur_ecs', 'scf_saisi', '0'];
        yield 'couverture hybride saisie' => ['installation_chauffage', 'couverture_pac_hybride_saisi', '0.75'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('options27NonCouvertes')]
    public function testOption27NonCouverteRefuseeAvantToutePurge(string $balise, string $champ, string $valeur): void
    {
        $document = new \DOMDocument();
        $document->loadXML('<dpe><administratif><enum_version_id>2.7</enum_version_id></administratif><logement>'
            . '<' . $balise . '><donnee_entree><' . $champ . '>' . $valeur . '</' . $champ . '></donnee_entree></' . $balise . '>'
            . '<sortie><valeur_a_conserver>123</valeur_a_conserver></sortie></logement></dpe>');
        $before = $document->saveXML();
        try {
            $this->engine()->calculateDocument($document);
            self::fail('Une option non implémentée ne doit pas être silencieusement ignorée.');
        } catch (UnsupportedDpeModelException $exception) {
            self::assertStringContainsString($champ, $exception->getMessage());
            self::assertSame($before, $document->saveXML());
        }
    }

    public function testOptionsAbsentesNilEtAnciennesRestentCalculables(): void
    {
        $this->engine()->calculate('<dpe xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><logement>'
            . '<installation_ecs><donnee_entree><presence_rceg>0</presence_rceg><enum_type_installation_solaire_id>4</enum_type_installation_solaire_id><enum_bouclage_reseau_ecs_id>3</enum_bouclage_reseau_ecs_id></donnee_entree></installation_ecs>'
            . '<generateur_ecs><donnee_entree><scf_saisi xsi:nil="true"/><enum_periode_installation_ecs_thermo_id>3</enum_periode_installation_ecs_thermo_id></donnee_entree></generateur_ecs>'
            . '<installation_chauffage><donnee_entree><couverture_pac_hybride_saisi xsi:nil="true"/></donnee_entree></installation_chauffage>'
            . '<emetteur_chauffage><donnee_entree><enum_equipement_intermittence_id>7</enum_equipement_intermittence_id></donnee_entree></emetteur_chauffage>'
            . '<autre><donnee_entree><presence_rceg>1</presence_rceg></donnee_entree></autre>'
            . '</logement></dpe>');
        $this->expectNotToPerformAssertions();
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
