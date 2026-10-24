<?php

declare(strict_types=1);

namespace Tests\Unit\Xml;

use CalculDpePHP\Xml\OutputPurger;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour OutputPurger : purge donnee_intermediaire/sortie en
 * préservant les caractéristiques saisies selon enum_methode_saisie_carac_sys_id.
 */
final class OutputPurgerTest extends TestCase
{
    private function load(string $inner): DOMDocument
    {
        $doc = new DOMDocument();
        $doc->loadXML("<?xml version=\"1.0\"?><logement>$inner</logement>");
        return $doc;
    }

    public function testMethode1PurgeToutLeDonneeIntermediaire(): void
    {
        $doc = $this->load(<<<XML
            <generateur_chauffage>
                <donnee_entree>
                    <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id>
                </donnee_entree>
                <donnee_intermediaire><pn>25000</pn><rpn>0.87</rpn></donnee_intermediaire>
            </generateur_chauffage>
            <sortie><ef_conso><conso_ch>100</conso_ch></ef_conso></sortie>
        XML);

        OutputPurger::purge($doc);

        $this->assertSame(0, $doc->getElementsByTagName('donnee_intermediaire')->length);
        $this->assertSame(0, $doc->getElementsByTagName('sortie')->length);
    }

    public function testMethode2PreservePnMaisPurgeLeReste(): void
    {
        $doc = $this->load(<<<XML
            <generateur_chauffage>
                <donnee_entree>
                    <enum_methode_saisie_carac_sys_id>2</enum_methode_saisie_carac_sys_id>
                </donnee_entree>
                <donnee_intermediaire>
                    <pn>25000</pn>
                    <rpn>0.867959</rpn>
                    <qp0>250</qp0>
                    <rendement_generation>0.776571</rendement_generation>
                </donnee_intermediaire>
            </generateur_chauffage>
        XML);

        OutputPurger::purge($doc);

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $this->assertNotNull($di, 'le donnee_intermediaire doit être conservé (pn saisi)');
        $this->assertSame('25000', $di->getElementsByTagName('pn')->item(0)?->textContent);
        $this->assertSame(0, $di->getElementsByTagName('rpn')->length);
        $this->assertSame(0, $di->getElementsByTagName('qp0')->length);
        $this->assertSame(0, $di->getElementsByTagName('rendement_generation')->length);
    }

    public function testMethode3PreservePnRpnRpint(): void
    {
        $doc = $this->load(<<<XML
            <generateur_ecs>
                <donnee_entree>
                    <enum_methode_saisie_carac_sys_id>3</enum_methode_saisie_carac_sys_id>
                </donnee_entree>
                <donnee_intermediaire>
                    <pn>18000</pn><rpn>0.9</rpn><rpint>0.88</rpint>
                    <qp0>180</qp0>
                </donnee_intermediaire>
            </generateur_ecs>
        XML);

        OutputPurger::purge($doc);

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $this->assertNotNull($di);
        $this->assertSame(1, $di->getElementsByTagName('pn')->length);
        $this->assertSame(1, $di->getElementsByTagName('rpn')->length);
        $this->assertSame(1, $di->getElementsByTagName('rpint')->length);
        $this->assertSame(0, $di->getElementsByTagName('qp0')->length, 'qp0 forfaitaire en méthode 3');
    }

    public function testMethode2SansValeurSaisiePurgeTout(): void
    {
        // pn absent du DI : rien à préserver, la balise disparaît entièrement
        $doc = $this->load(<<<XML
            <generateur_chauffage>
                <donnee_entree>
                    <enum_methode_saisie_carac_sys_id>2</enum_methode_saisie_carac_sys_id>
                </donnee_entree>
                <donnee_intermediaire><rendement_generation>0.8</rendement_generation></donnee_intermediaire>
            </generateur_chauffage>
        XML);

        OutputPurger::purge($doc);

        $this->assertSame(0, $doc->getElementsByTagName('donnee_intermediaire')->length);
    }

    public function testDonneeIntermediaireHorsGenerateurToujoursPurge(): void
    {
        $doc = $this->load(<<<XML
            <mur>
                <donnee_entree><enum_methode_saisie_carac_sys_id>2</enum_methode_saisie_carac_sys_id></donnee_entree>
                <donnee_intermediaire><umur>2.5</umur><pn>1</pn></donnee_intermediaire>
            </mur>
        XML);

        OutputPurger::purge($doc);

        $this->assertSame(0, $doc->getElementsByTagName('donnee_intermediaire')->length);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('saisiesVentilation')]
    public function testLaPuissanceVentilationJustifieeEstUneEntreeDuModele27(?string $version, ?int $methode, bool $preserve): void
    {
        $doc = new DOMDocument();
        $model = $version === null ? '' : '<enum_version_id>' . $version . '</enum_version_id>';
        $method = $methode === null ? '' : '<enum_methode_saisie_pvent_moy_id>' . $methode . '</enum_methode_saisie_pvent_moy_id>';
        $doc->loadXML('<dpe><administratif>' . $model . '</administratif><logement><ventilation_collection><ventilation><donnee_entree>' . $method
            . '</donnee_entree><donnee_intermediaire><pvent_moy>0</pvent_moy><q4pa_conv>1.7</q4pa_conv><hvent>12</hvent><conso_auxiliaire_ventilation>100</conso_auxiliaire_ventilation></donnee_intermediaire>'
            . '</ventilation></ventilation_collection></logement></dpe>');
        OutputPurger::purge($doc);
        self::assertSame($preserve ? 1 : 0, $doc->getElementsByTagName('pvent_moy')->length);
        if ($preserve) { self::assertSame('0', $doc->getElementsByTagName('pvent_moy')->item(0)->textContent); }
        foreach (['q4pa_conv', 'hvent', 'conso_auxiliaire_ventilation'] as $tag) { self::assertSame(0, $doc->getElementsByTagName($tag)->length); }
        OutputPurger::purge($doc);
        self::assertSame($preserve ? 1 : 0, $doc->getElementsByTagName('pvent_moy')->length);
    }

    public static function saisiesVentilation(): iterable
    {
        foreach ([null, '1', '2.6', '2.7'] as $version) {
            foreach ([null, 1, 2, 3] as $method) {
                yield ($version ?? 'absent') . '/' . ($method ?? 'absent') => [$version, $method, $version === '2.7' && in_array($method, [2, 3], true)];
            }
        }
    }
}
