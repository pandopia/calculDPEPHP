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

    /** §13.2.2 p.86–88 : la technologie et la période suffisent sans identifiant TV. */
    #[\PHPUnit\Framework\Attributes\DataProvider('chaudieresSansIdentifiantTable')]
    public function testCalculeLesCaracteristiquesDepuisLeTypeSansIdentifiantTable(
        int $type, float $pn, float $rpn, float $rpint, float $qp0,
    ): void {
        [$doc, $node] = $this->buildGenerator($type, 5, 2, ['pn' => $pn]);
        $tv = $doc->getElementsByTagName('tv_generateur_combustion_id')->item(0);
        $tv->parentNode->removeChild($tv);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));
        $di = $node->getElementsByTagName('donnee_intermediaire')->item(0);
        foreach (['pn' => $pn, 'rpn' => $rpn, 'rpint' => $rpint, 'qp0' => $qp0] as $tag => $expected) {
            $actual = $di->getElementsByTagName($tag)->item(0);
            self::assertNotNull($actual, $tag . ' doit être calculé sans table explicite');
            self::assertEqualsWithDelta($expected, (float)$actual->textContent, self::TOL);
        }
    }

    public static function chaudieresSansIdentifiantTable(): iterable
    {
        $rpn = (84 + 2 * log10(24)) / 100;
        $rpint = (80 + 3 * log10(24)) / 100;
        yield 'gaz standard 2001-2015' => [89, 24000.0, $rpn, $rpint, 240.0];
        yield 'gaz standard 1991-2000' => [88, 24000.0, $rpn, $rpint, 288.0];
        yield 'fioul standard 1991-2015' => [79, 24000.0, $rpn, $rpint, 240.0];
        yield 'alias GPL standard' => [131, 24000.0, $rpn, $rpint, 240.0];
        yield 'condensation gaz au-dessus de 70 kW' => [97, 100000.0, 0.96, 1.06, 300.0];
        yield 'condensation fioul au-dessus de 70 kW' => [84, 100000.0, 0.96, 1.02, 600.0];
        yield 'partie chaudière hybride gaz' => [149, 100000.0, 0.96, 1.06, 300.0];
    }

    public function testConserveLaTableExpliciteQuandLeTypeSuggereUneAutrePeriode(): void
    {
        [$doc, $node] = $this->buildGenerator(89, 4, 2, ['pn' => 24000]);
        (new ChaudiereDefautCalculator())->calculate($node, $this->makeContext($doc));
        self::assertEqualsWithDelta(288.0, (float)$node->getElementsByTagName('qp0')->item(0)->textContent, self::TOL);
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

    /**
     * §15.1 p.97 plafonne Pn, pas Pch : un Pch de 500 kW traverse la table
     * Pdim → Pn de §13.2.2.4 puis est ramené à 400 kW exactement. Plafonner Pch
     * en amont ferait lire la ligne « 40 < » à Pdim = 400 et rendrait
     * (partie entière(400/5) + 1) × 5 = 405 kW.
     */
    public function testGasBoilerCapIsAppliedToPnNotToPch(): void
    {
        $xml = <<<'XML'
<logement>
  <caracteristique_generale><enum_methode_application_dpe_log_id>1</enum_methode_application_dpe_log_id></caracteristique_generale>
  <meteo><enum_zone_climatique_id>1</enum_zone_climatique_id><enum_classe_altitude_id>1</enum_classe_altitude_id></meteo>
  <installation_chauffage><donnee_entree>
    <enum_type_installation_id>1</enum_type_installation_id>
  </donnee_entree><generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>88</enum_type_generateur_ch_id><tv_generateur_combustion_id>4</tv_generateur_combustion_id>
    <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id><presence_ventouse>0</presence_ventouse>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $context = $this->makeContext($doc);
        // Tbase = -9,5 (H1, altitude < 400 m) : Pch = 1,2 × 12535 × 28,5 / 0,95³ ≈ 500 kW.
        $context->set('enveloppe.dp_parois', 12535.0);
        $context->set('enveloppe.dp_pont_thermique', 0.0);
        $context->set('ventilation.hvent', 0.0);
        $context->set('ventilation.hperm', 0.0);

        (new ChaudiereDefautCalculator())->calculate($doc->getElementsByTagName('generateur_chauffage')->item(0), $context);

        self::assertEqualsWithDelta(400000.0, (float)$doc->getElementsByTagName('pn')->item(0)->textContent, 1e-6);
    }

    /**
     * Sous le plafond, la table §13.2.2.4 p.92 garde sa règle « 40 < Pdim :
     * (partie entière(Pdim/5) + 1) × 5 ». Pch ≈ 52,1 kW → Pn = 55 kW.
     */
    public function testGasBoilerBelowCapStillRoundsUpOnTheNominalPowerTable(): void
    {
        $xml = <<<'XML'
<logement>
  <caracteristique_generale><enum_methode_application_dpe_log_id>1</enum_methode_application_dpe_log_id></caracteristique_generale>
  <meteo><enum_zone_climatique_id>1</enum_zone_climatique_id><enum_classe_altitude_id>1</enum_classe_altitude_id></meteo>
  <installation_chauffage><donnee_entree>
    <enum_type_installation_id>1</enum_type_installation_id>
  </donnee_entree><generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>88</enum_type_generateur_ch_id><tv_generateur_combustion_id>4</tv_generateur_combustion_id>
    <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id><presence_ventouse>0</presence_ventouse>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $context = $this->makeContext($doc);
        $context->set('enveloppe.dp_parois', 1306.0);
        $context->set('enveloppe.dp_pont_thermique', 0.0);
        $context->set('ventilation.hvent', 0.0);
        $context->set('ventilation.hperm', 0.0);

        (new ChaudiereDefautCalculator())->calculate($doc->getElementsByTagName('generateur_chauffage')->item(0), $context);

        self::assertEqualsWithDelta(55000.0, (float)$doc->getElementsByTagName('pn')->item(0)->textContent, 1e-6);
    }

    /**
     * §13.2.2.4 p.92 : une chaudière collective virtualisée se dimensionne comme
     * les autres — Pdim, puis la table Pn —, le plafond de §15.1 ne servant que
     * de maximum. Nous lui assignions 400 kW en dur, quel que soit son Pdim.
     *
     * Ici GV = 152 W/K pour un ratio de 0,02933, soit 5 182 W/K au bâtiment, et
     * Tbase = −9,5 : Pch = 1,2 × 5 182 × 28,5 / 0,95³ = 206,7 kW, que la table
     * rend à 210 kW. Le pn publié est la part du logement, 210 000 × 0,02933.
     */
    public function testCollectiveVirtualizedGasBoilerIsSizedOnItsOwnPdim(): void
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

        self::assertEqualsWithDelta(210000.0 * 0.02933, (float)$doc->getElementsByTagName('pn')->item(0)->textContent, 1e-6);
        // Les caractéristiques restent évaluées à la puissance du bâtiment.
        self::assertEqualsWithDelta((84.0 + 2.0 * log10(210.0)) / 100.0, (float)$doc->getElementsByTagName('rpn')->item(0)->textContent, 1e-9);
        self::assertEqualsWithDelta(0.012 * 210000.0 * 0.02933, (float)$doc->getElementsByTagName('qp0')->item(0)->textContent, 1e-6);
        self::assertNull($doc->getElementsByTagName('pveilleuse')->item(0));
    }

    public static function casChaudiereCollectiveMixte(): iterable
    {
        // §13.2.2.4 p.91-92 : Pch = 1,2 × GV_immeuble × 28,5 / 0,95³ ;
        // Pecs dépend seulement du stockage, puis Pn = table(max(Pch, Pecs)).
        $cas = [
            'instantanee dimensionnee par ecs' => [0.0, 200.0, 24000.0],
            'instantanee dimensionnee par chauffage' => [0.0, 1600.0, 65000.0],
            'semi instantanee' => [10.0, 200.0, 18000.0],
            'semi accumulation' => [100.0, 200.0, 18000.0],
            'accumulation collective' => [10000.0, 200.0, 75000.0],
            'plafond gaz fioul' => [0.0, 12535.0, 400000.0],
        ];
        foreach ($cas as $nom => [$volume, $gvImmeuble, $pnImmeuble]) {
            foreach ([0.05, 0.2] as $ratio) {
                foreach ([false, true] as $cleDistincte) {
                    yield $nom . ' ratio ' . $ratio . ($cleDistincte ? ' cle commune distincte' : ' cle commune egale a reference chauffage')
                        => [$volume, $gvImmeuble, $pnImmeuble, $ratio, $cleDistincte];
                }
            }
        }
    }

    /**
     * §13.2.2.4 p.92 : le forfait Pecs caractérise le générateur collectif ;
     * il ne se multiplie pas par l'inverse de la part du logement.
     * §17.2.1.1 : seule la puissance nominale obtenue est ensuite virtualisée.
     * Le même immeuble doit conserver la même Pn quel que soit le logement.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('casChaudiereCollectiveMixte')]
    public function test_chaudiere_collective_mixte_dimensionnee_avant_virtualisation(
        float $volume,
        float $gvImmeuble,
        float $pnImmeuble,
        float $ratio,
        bool $cleDistincte,
    ): void {
        $cleMixte = $cleDistincte ? 'chaudiere-commune' : 'chauffage';
        $lienChauffage = '<reference_generateur_mixte>' . $cleMixte . '</reference_generateur_mixte>';
        $lienEcs = $lienChauffage;
        $xml = <<<XML
<logement>
  <caracteristique_generale><enum_methode_application_dpe_log_id>5</enum_methode_application_dpe_log_id></caracteristique_generale>
  <meteo><enum_zone_climatique_id>1</enum_zone_climatique_id><enum_classe_altitude_id>1</enum_classe_altitude_id></meteo>
  <installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_entree>
    <reference>ecs</reference>{$lienEcs}
    <enum_usage_generateur_id>3</enum_usage_generateur_id><volume_stockage>{$volume}</volume_stockage>
  </donnee_entree></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>
  <installation_chauffage><donnee_entree>
    <enum_type_installation_id>2</enum_type_installation_id><ratio_virtualisation>{$ratio}</ratio_virtualisation>
  </donnee_entree><generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <reference>chauffage</reference>{$lienChauffage}
    <enum_type_generateur_ch_id>88</enum_type_generateur_ch_id><tv_generateur_combustion_id>4</tv_generateur_combustion_id>
    <enum_usage_generateur_id>3</enum_usage_generateur_id>
    <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id><presence_ventouse>0</presence_ventouse>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $context = $this->makeContext($doc);
        $context->set('enveloppe.dp_parois', $gvImmeuble * $ratio);

        (new ChaudiereDefautCalculator())->calculate($doc->getElementsByTagName('generateur_chauffage')->item(0), $context);

        self::assertEqualsWithDelta($pnImmeuble * $ratio, (float)$doc->getElementsByTagName('pn')->item(0)->textContent, 1e-6);
        self::assertEqualsWithDelta((84.0 + 2.0 * log10($pnImmeuble / 1000.0)) / 100.0, (float)$doc->getElementsByTagName('rpn')->item(0)->textContent, 1e-9);
        self::assertEqualsWithDelta(0.012 * $pnImmeuble * $ratio, (float)$doc->getElementsByTagName('qp0')->item(0)->textContent, 1e-6);
    }

    /**
     * Compatibilité des exports historiques sans clé mixte commune :
     * l'ECS y est dimensionnée sur le logement virtuel (21 / ratio).
     * Cette convention ne doit pas contaminer un lien commun conforme au XSD.
     */
    public function test_conserve_la_convention_historique_sans_cle_mixte_commune(): void
    {
        $xml = <<<'XML'
<logement>
  <caracteristique_generale><enum_methode_application_dpe_log_id>5</enum_methode_application_dpe_log_id></caracteristique_generale>
  <meteo><enum_zone_climatique_id>1</enum_zone_climatique_id><enum_classe_altitude_id>1</enum_classe_altitude_id></meteo>
  <installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_entree>
    <enum_usage_generateur_id>3</enum_usage_generateur_id><volume_stockage>0</volume_stockage>
  </donnee_entree></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>
  <installation_chauffage><donnee_entree>
    <enum_type_installation_id>2</enum_type_installation_id><ratio_virtualisation>0.13554</ratio_virtualisation>
  </donnee_entree><generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>88</enum_type_generateur_ch_id><tv_generateur_combustion_id>4</tv_generateur_combustion_id>
    <enum_usage_generateur_id>3</enum_usage_generateur_id>
    <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id><presence_ventouse>0</presence_ventouse>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $context = $this->makeContext($doc);
        // GV volontairement faible : c'est Pecs qui doit dimensionner.
        $context->set('enveloppe.dp_parois', 50.0);
        $context->set('enveloppe.dp_pont_thermique', 0.0);
        $context->set('ventilation.hvent', 0.0);
        $context->set('ventilation.hperm', 0.0);

        (new ChaudiereDefautCalculator())->calculate($doc->getElementsByTagName('generateur_chauffage')->item(0), $context);

        self::assertEqualsWithDelta(
            155000.0 * 0.13554,
            (float)$doc->getElementsByTagName('pn')->item(0)->textContent,
            1e-6,
        );
    }

    /**
     * Sans virtualisation, `enum_usage_generateur_id = 3` seul ne prouve pas que
     * les deux générateurs décrivent un même appareil : le corpus montre alors
     * deux puissances distinctes, chaque usage étant dimensionné pour lui-même.
     * Pdim reste donc Pch.
     */
    public function testNonVirtualizedInstallationIgnoresTheEcsPowerWithoutCrossReference(): void
    {
        $xml = <<<'XML'
<logement>
  <caracteristique_generale><enum_methode_application_dpe_log_id>1</enum_methode_application_dpe_log_id></caracteristique_generale>
  <meteo><enum_zone_climatique_id>1</enum_zone_climatique_id><enum_classe_altitude_id>1</enum_classe_altitude_id></meteo>
  <installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_entree>
    <enum_usage_generateur_id>3</enum_usage_generateur_id><volume_stockage>0</volume_stockage>
  </donnee_entree></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>
  <installation_chauffage><donnee_entree>
    <enum_type_installation_id>1</enum_type_installation_id>
  </donnee_entree><generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>88</enum_type_generateur_ch_id><tv_generateur_combustion_id>4</tv_generateur_combustion_id>
    <enum_usage_generateur_id>3</enum_usage_generateur_id>
    <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id><presence_ventouse>0</presence_ventouse>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $context = $this->makeContext($doc);
        // Pch = 1,2 × 250 × 28,5 / 0,95³ ≈ 9,97 kW → table : 13 kW sur sol.
        $context->set('enveloppe.dp_parois', 250.0);
        $context->set('enveloppe.dp_pont_thermique', 0.0);
        $context->set('ventilation.hvent', 0.0);
        $context->set('ventilation.hperm', 0.0);

        (new ChaudiereDefautCalculator())->calculate($doc->getElementsByTagName('generateur_chauffage')->item(0), $context);

        // Sans le garde, Pecs = 21 kW aurait donné 24 kW.
        self::assertEqualsWithDelta(18000.0, (float)$doc->getElementsByTagName('pn')->item(0)->textContent, 1e-6);
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
    public function testLeLienMixteDirectDimensionneChaqueChaudiereDeLaCascade(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<logement><caracteristique_generale><enum_methode_application_dpe_log_id>1</enum_methode_application_dpe_log_id></caracteristique_generale>
<installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_entree>
<reference>ecs-&quot;commun&quot;</reference><reference_generateur_mixte>chauffage-1</reference_generateur_mixte>
<enum_usage_generateur_id>3</enum_usage_generateur_id><volume_stockage>0</volume_stockage>
</donnee_entree></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>
<installation_chauffage><donnee_entree><enum_type_installation_id>1</enum_type_installation_id></donnee_entree>
<generateur_chauffage_collection><generateur_chauffage><donnee_entree>
<reference>chauffage-1</reference><reference_generateur_mixte>ecs-&quot;commun&quot;</reference_generateur_mixte>
<enum_type_generateur_ch_id>97</enum_type_generateur_ch_id><tv_generateur_combustion_id>13</tv_generateur_combustion_id>
<enum_usage_generateur_id>3</enum_usage_generateur_id><enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id>
</donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage></logement>
XML);
        $first = $doc->getElementsByTagName('generateur_chauffage')->item(0);
        $second = $first->cloneNode(true);
        $second->getElementsByTagName('reference')->item(0)->textContent = 'chauffage-2';
        $first->parentNode->appendChild($second);
        $context = $this->makeContext($doc);
        foreach (['enveloppe.dp_pont_thermique', 'ventilation.hvent', 'ventilation.hperm'] as $key) {
            $context->set($key, 0.0);
        }
        $context->set('enveloppe.dp_parois', 50.0);
        $calc = new ChaudiereDefautCalculator();
        foreach ([$first, $second] as $generator) {
            $calc->calculate($generator, $context);
            // §13.2.2.4 p.91-92 : ECS instantanée, Pecs=21 kW donc Pn=24 kW.
            self::assertEqualsWithDelta(24000.0, (float)$generator->getElementsByTagName('pn')->item(0)->textContent, 1e-6);
        }
        // Un lien explicite absent du XML ne désigne pas automatiquement le seul ECS.
        $second->getElementsByTagName('reference_generateur_mixte')->item(0)->textContent = 'ecs-inconnu';
        $calc->calculate($second, $context);
        self::assertLessThan(24000.0, (float)$second->getElementsByTagName('pn')->item(0)->textContent);
    }

}
