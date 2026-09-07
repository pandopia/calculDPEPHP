<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ChaudiereDefautCalculator (§13.2.2 p.86-92).
 */
final class ChaudiereDefautCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 1e-5;

    private function buildGenerator(int $genTypeId, int $tvId, int $methode = 1, ?array $realValues = null): array
    {
        $realTags = '';
        if ($realValues !== null) {
            foreach ($realValues as $k => $v) {
                $realTags .= "<$k>$v</$k>";
            }
        }
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_chauffage>
        <generateur_chauffage_collection>
            <generateur_chauffage>
                <donnee_entree>
                    <enum_type_generateur_ch_id>$genTypeId</enum_type_generateur_ch_id>
                    <tv_generateur_combustion_id>$tvId</tv_generateur_combustion_id>
                    <enum_methode_saisie_carac_sys_id>$methode</enum_methode_saisie_carac_sys_id>
                    $realTags
                </donnee_entree>
            </generateur_chauffage>
        </generateur_chauffage_collection>
    </installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('generateur_chauffage')->item(0);
        return [$doc, $node];
    }

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    /**
     * Chaudière gaz condensation après 2015, Pn=40kW (tv_id=13) — vérifié sur verif post2026.
     */
    public function testCondensationGaz40kw(): void
    {
        [$doc, $node] = $this->buildGenerator(97, 13, 1, ['pn' => 40000]);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $pn    = (float)$doc->getElementsByTagName('pn')->item(0)->textContent;
        $rpn   = (float)$doc->getElementsByTagName('rpn')->item(0)->textContent;
        $rpint = (float)$doc->getElementsByTagName('rpint')->item(0)->textContent;
        $qp0   = (float)$doc->getElementsByTagName('qp0')->item(0)->textContent;

        $this->assertEqualsWithDelta(40000.0, $pn,   1.0);
        $this->assertEqualsWithDelta(0.958062, $rpn, self::TOL);
        $this->assertEqualsWithDelta(1.070051, $rpint, self::TOL);
        $this->assertEqualsWithDelta(200.0, $qp0, 0.1);
    }

    /**
     * Générateurs PAC ou électrique (IDs hors 20-97) → ignorés.
     */
    public function testPacGeneratorIgnored(): void
    {
        [$doc, $node] = $this->buildGenerator(7, 13); // PAC air/eau → pas de combustion
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $pn = $doc->getElementsByTagName('pn')->item(0);
        $this->assertNull($pn); // rien écrit
    }

    /**
     * TV non digitalisé → rien n'est écrit (graceful degradation).
     */
    public function testUnknownTvIdDoesNothing(): void
    {
        [$doc, $node] = $this->buildGenerator(85, 999); // table entry non existante
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $pn = $doc->getElementsByTagName('pn')->item(0);
        $this->assertNull($pn);
    }

    /**
     * Methode=2 (XSD : « pn, autres données forfaitaires ») → pn saisi est repris,
     * rpn/rpint/qp0 sont calculés par les formules forfaitaires de la table.
     * Cas réel 2662E2147774H : chaudière gaz standard 2001-2015 (tv_id=5), pn=25 kW
     *   rpn = (84 + 2·log10(25))/100 = 0.867959 ; qp0 = 1 % × Pn = 250 W.
     */
    public function testMethode2PnSaisiAutresForfaitaires(): void
    {
        [$doc, $node] = $this->buildGenerator(89, 5, 2, ['pn' => 25000]);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $get = fn(string $tag): float => (float)$di->getElementsByTagName($tag)->item(0)->textContent;
        $this->assertEqualsWithDelta(25000.0, $get('pn'), 1.0);
        $this->assertEqualsWithDelta((84.0 + 2.0 * log10(25.0)) / 100.0, $get('rpn'), self::TOL);
        $this->assertEqualsWithDelta((80.0 + 3.0 * log10(25.0)) / 100.0, $get('rpint'), self::TOL);
        $this->assertEqualsWithDelta(250.0, $get('qp0'), self::TOL);
    }

    /**
     * Methode=2 avec pn stocké en donnee_intermediaire (convention LICIEL,
     * préservé par OutputPurger) → même résultat que pn en donnee_entree.
     */
    public function testMethode2PnFromDonneeIntermediaire(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_chauffage>
        <generateur_chauffage_collection>
            <generateur_chauffage>
                <donnee_entree>
                    <enum_type_generateur_ch_id>89</enum_type_generateur_ch_id>
                    <tv_generateur_combustion_id>5</tv_generateur_combustion_id>
                    <enum_methode_saisie_carac_sys_id>2</enum_methode_saisie_carac_sys_id>
                </donnee_entree>
                <donnee_intermediaire>
                    <pn>25000</pn>
                </donnee_intermediaire>
            </generateur_chauffage>
        </generateur_chauffage_collection>
    </installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('generateur_chauffage')->item(0);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $get = fn(string $tag): float => (float)$di->getElementsByTagName($tag)->item(0)->textContent;
        $this->assertEqualsWithDelta(25000.0, $get('pn'), 1.0);
        $this->assertEqualsWithDelta((84.0 + 2.0 * log10(25.0)) / 100.0, $get('rpn'), self::TOL);
        $this->assertEqualsWithDelta(250.0, $get('qp0'), self::TOL);
    }

    /**
     * Methode=2 sans table digitalisée → seuls les champs saisis sont recopiés.
     */
    public function testMethode2SansTableRecopieSaisie(): void
    {
        [$doc, $node] = $this->buildGenerator(97, 999, 2, ['pn' => 35000]);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $this->assertNotNull($di);
        $pn = $di->getElementsByTagName('pn')->item(0);
        $this->assertNotNull($pn);
        $this->assertEqualsWithDelta(35000.0, (float)$pn->textContent, 1.0);
        $this->assertNull($di->getElementsByTagName('rpn')->item(0));
    }

    public function testDeclaredPilotLightPowerIsPreserved(): void
    {
        [$doc, $node] = $this->buildGenerator(88, 4, 4, [
            'pn' => 25000,
            'rpn' => 0.87,
            'rpint' => 0.84,
            'qp0' => 300,
            'pveilleuse' => 90,
        ]);

        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));

        $this->assertEqualsWithDelta(
            90.0,
            (float)$doc->getElementsByTagName('pveilleuse')->item(0)->textContent,
            self::TOL,
        );
    }

    /** A sampled installation in a method-10 DPE is sized as one average apartment. */
    public function testGeneratedApartmentBuildingUsesAverageApartmentPower(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <enum_methode_application_dpe_log_id>10</enum_methode_application_dpe_log_id>
        <surface_habitable_immeuble>2088</surface_habitable_immeuble>
        <nombre_appartement>32</nombre_appartement>
    </caracteristique_generale>
    <meteo><enum_zone_climatique_id>6</enum_zone_climatique_id><enum_classe_altitude_id>1</enum_classe_altitude_id></meteo>
    <installation_chauffage>
        <donnee_entree><surface_chauffee>522</surface_chauffee></donnee_entree>
        <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
            <enum_type_generateur_ch_id>92</enum_type_generateur_ch_id>
            <tv_generateur_combustion_id>8</tv_generateur_combustion_id>
            <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id>
            <presence_ventouse>1</presence_ventouse>
            <data_complementaires data-annee-installation="2015" data-chaudiere-murale="1"/>
        </donnee_entree></generateur_chauffage></generateur_chauffage_collection>
    </installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $context = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '6',
            classeAltitude: '1',
        );
        $context->set('enveloppe.dp_parois', 3675.8);
        $context->set('enveloppe.dp_pont_thermique', 0.0);
        $context->set('ventilation.hvent', 0.0);
        $context->set('ventilation.hperm', 0.0);
        (new ChaudiereDefautCalculator())->calculate($doc->getElementsByTagName('generateur_chauffage')->item(0), $context);

        $this->assertEqualsWithDelta(5000.0, (float)$doc->getElementsByTagName('pn')->item(0)->textContent, 0.1);
    }

    /** Le mode mixte 33 ne transforme pas sa chaudière individuelle en chaudière collective. */
    public function testGeneratedMixedApartmentSizesIndividualMixedBoilerAtApartmentScale(): void
    {
        $xml = <<<'XML'
<logement>
  <caracteristique_generale>
    <enum_methode_application_dpe_log_id>33</enum_methode_application_dpe_log_id>
    <surface_habitable_immeuble>1203</surface_habitable_immeuble>
    <nombre_appartement>20</nombre_appartement>
  </caracteristique_generale>
  <meteo><enum_zone_climatique_id>1</enum_zone_climatique_id><enum_classe_altitude_id>1</enum_classe_altitude_id></meteo>
  <installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_entree>
    <reference_generateur_mixte>mixte-1</reference_generateur_mixte><volume_stockage>0</volume_stockage>
  </donnee_entree></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>
  <installation_chauffage><donnee_entree><enum_type_installation_id>1</enum_type_installation_id></donnee_entree>
    <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
      <reference>chaudiere-1</reference><reference_generateur_mixte>mixte-1</reference_generateur_mixte>
      <enum_type_generateur_ch_id>97</enum_type_generateur_ch_id><tv_generateur_combustion_id>13</tv_generateur_combustion_id>
      <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id><presence_ventouse>1</presence_ventouse>
    </donnee_entree></generateur_chauffage></generateur_chauffage_collection>
  </installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $context = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '1',
            classeAltitude: '1',
        );
        $context->set('enveloppe.dp_parois', 2008.613);
        $context->set('enveloppe.dp_pont_thermique', 0.0);
        $context->set('ventilation.hvent', 0.0);
        $context->set('ventilation.hperm', 0.0);

        (new ChaudiereDefautCalculator())->calculate($doc->getElementsByTagName('generateur_chauffage')->item(0), $context);

        self::assertEqualsWithDelta(24000.0, (float) $doc->getElementsByTagName('pn')->item(0)?->textContent, 0.1);
    }

    public function testCollectiveVirtualizedGasBoilerUsesFourHundredKwBuildingPower(): void
    {
        $xml = <<<'XML'
<logement>
  <caracteristique_generale><enum_methode_application_dpe_log_id>5</enum_methode_application_dpe_log_id></caracteristique_generale>
  <meteo><enum_zone_climatique_id>1</enum_zone_climatique_id><enum_classe_altitude_id>1</enum_classe_altitude_id></meteo>
  <installation_chauffage><donnee_entree>
    <enum_type_installation_id>2</enum_type_installation_id><ratio_virtualisation>0.02933</ratio_virtualisation>
  </donnee_entree><generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>88</enum_type_generateur_ch_id><tv_generateur_combustion_id>4</tv_generateur_combustion_id>
    <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id><presence_ventouse>0</presence_ventouse>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $context = $this->makeContext($doc);
        $context->set('enveloppe.dp_parois', 100.0);
        $context->set('enveloppe.dp_pont_thermique', 20.0);
        $context->set('ventilation.hvent', 30.0);
        $context->set('ventilation.hperm', 2.0);

        (new ChaudiereDefautCalculator())->calculate($doc->getElementsByTagName('generateur_chauffage')->item(0), $context);

        self::assertEqualsWithDelta(400000.0 * 0.02933, (float)$doc->getElementsByTagName('pn')->item(0)->textContent, 1e-6);
        self::assertEqualsWithDelta((84.0 + 2.0 * log10(400.0)) / 100.0, (float)$doc->getElementsByTagName('rpn')->item(0)->textContent, 1e-9);
        self::assertEqualsWithDelta(0.012 * 400000.0 * 0.02933, (float)$doc->getElementsByTagName('qp0')->item(0)->textContent, 1e-6);
        self::assertNull($doc->getElementsByTagName('pveilleuse')->item(0));
    }

    public function testMixedHeatingKeepsApartmentPowerAndEvaluatesCharacteristicsAtBuildingScale(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement><caracteristique_generale><enum_methode_application_dpe_log_id>31</enum_methode_application_dpe_log_id></caracteristique_generale>
<installation_chauffage><donnee_entree><enum_type_installation_id>2</enum_type_installation_id><ratio_virtualisation>0.0489907</ratio_virtualisation></donnee_entree>
<generateur_chauffage_collection><generateur_chauffage><donnee_entree>
<enum_type_generateur_ch_id>85</enum_type_generateur_ch_id><tv_generateur_combustion_id>1</tv_generateur_combustion_id>
<enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id><presence_ventouse>0</presence_ventouse>
</donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage></logement>
XML);
        $context = $this->makeContext($document);
        $context->set('enveloppe.dp_parois', 109.634);
        $context->set('enveloppe.dp_pont_thermique', 30.036);
        $context->set('ventilation.hvent', 70.55);
        $context->set('ventilation.hperm', 8.379);

        (new ChaudiereDefautCalculator())->calculate($document->getElementsByTagName('generateur_chauffage')->item(0), $context);

        $pn = (float)$document->getElementsByTagName('pn')->item(0)?->textContent;
        self::assertEqualsWithDelta(8719.7, $pn, 1.0);
        self::assertEqualsWithDelta((84 + 2 * log10($pn / 0.0489907 / 1000)) / 100, (float)$document->getElementsByTagName('rpn')->item(0)?->textContent, 1e-9);
        self::assertEqualsWithDelta(0.04 * $pn, (float)$document->getElementsByTagName('qp0')->item(0)?->textContent, 1e-6);
    }
}
