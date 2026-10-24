<?php

declare(strict_types=1);

namespace Tests\Unit\Auxiliaire;

use CalculDpePHP\Auxiliaire\AuxGenerationCalculator;
use CalculDpePHP\Common\Period;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class AuxGenerationCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function buildDoc(string $xml): array
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            period: Period::POST_2026,
        );
        return [$doc, $doc->documentElement, $ctx];
    }

    private function efValue(DOMDocument $doc, string $tag): float
    {
        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query("//ef_conso/$tag");
        $this->assertNotNull($nodes);
        $this->assertGreaterThan(0, $nodes->length, "Missing tag: $tag");
        return (float)$nodes->item(0)->textContent;
    }

    private function gasCombinedXml(
        float $besoinCh,
        float $besoinChDep,
        float $besoinEcs,
        float $besoinEcsDep,
        float $pn = 40000.0,
        ?float $cleRepCh = null,
        ?float $cleRepEcs = null,
    ): string {
        $cleChAttr  = $cleRepCh  !== null ? "<cle_repartition_ch>$cleRepCh</cle_repartition_ch>"   : '';
        $cleEcsAttr = $cleRepEcs !== null ? "<cle_repartition_ecs>$cleRepEcs</cle_repartition_ecs>" : '';
        $shLogement = $cleRepCh  !== null ? '<surface_habitable_logement>62.86</surface_habitable_logement>' : '';
        return <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>1034.74</surface_habitable_immeuble>
        $shLogement
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                $cleChAttr
            </donnee_entree>
            <donnee_intermediaire>
                <besoin_ch>$besoinCh</besoin_ch>
                <besoin_ch_depensier>$besoinChDep</besoin_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>2</enum_type_energie_id>
                        <enum_type_generateur_ch_id>96</enum_type_generateur_ch_id>
                        <tv_generateur_combustion_id>13</tv_generateur_combustion_id>
                    </donnee_entree>
                    <donnee_intermediaire>
                        <pn>$pn</pn>
                    </donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection>
        <installation_ecs>
            <donnee_entree>
                $cleEcsAttr
            </donnee_entree>
            <donnee_intermediaire>
                <besoin_ecs>$besoinEcs</besoin_ecs>
                <besoin_ecs_depensier>$besoinEcsDep</besoin_ecs_depensier>
            </donnee_intermediaire>
            <generateur_ecs>
                <donnee_entree>
                    <enum_type_energie_id>2</enum_type_energie_id>
                    <enum_type_generateur_ecs_id>52</enum_type_generateur_ecs_id>
                    <tv_generateur_combustion_id>13</tv_generateur_combustion_id>
                </donnee_entree>
                <donnee_intermediaire>
                    <pn>$pn</pn>
                </donnee_intermediaire>
            </generateur_ecs>
        </installation_ecs>
    </installation_ecs_collection>
    <sortie/>
</logement>
XML;
    }

    public function testAppliesToLogement(): void
    {
        $calc = new AuxGenerationCalculator();
        $doc  = new DOMDocument();
        $doc->loadXML('<logement/>');
        $this->assertTrue($calc->appliesTo($doc->documentElement));

        $doc2 = new DOMDocument();
        $doc2->loadXML('<installation_chauffage/>');
        $this->assertFalse($calc->appliesTo($doc2->documentElement));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('perimetresEtOrdres')]
    public function testLesClesPortentSurChaqueInstallation(?int $methode, bool $inverse, bool $cleParDefaut, float $attendu): void
    {
        $installations = [];
        foreach ([[10000, 0.2], [20000, 0.3]] as [$besoin, $cle]) {
            $cle = $cleParDefaut ? 1.0 : $cle;
            $installations[] = '<installation_chauffage><donnee_entree><cle_repartition_ch>' . $cle . '</cle_repartition_ch></donnee_entree>'
                . '<donnee_intermediaire><besoin_ch>' . $besoin . '</besoin_ch><besoin_ch_depensier>' . (2 * $besoin) . '</besoin_ch_depensier></donnee_intermediaire>'
                . '<generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>96</enum_type_generateur_ch_id><enum_type_energie_id>2</enum_type_energie_id></donnee_entree>'
                . '<donnee_intermediaire><pn>40000</pn></donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection></installation_chauffage>';
        }
        $mode = $methode === null ? '' : '<enum_methode_application_dpe_log_id>' . $methode . '</enum_methode_application_dpe_log_id>';
        [$document, $logement, $context] = $this->buildDoc('<logement><caracteristique_generale>' . $mode
            . '<surface_habitable_logement>50</surface_habitable_logement><surface_habitable_immeuble>1000</surface_habitable_immeuble></caracteristique_generale>'
            . '<installation_chauffage_collection>' . implode('', $inverse ? array_reverse($installations) : $installations) . '</installation_chauffage_collection></logement>');

        (new AuxGenerationCalculator())->calculate($logement, $context);

        self::assertEqualsWithDelta($attendu, $this->efValue($document, 'conso_auxiliaire_generation_ch'), 1e-10);
        self::assertEqualsWithDelta(3 * $attendu, $this->efValue($document, 'conso_auxiliaire_generation_ch_depensier'), 1e-10);
    }

    public static function perimetresEtOrdres(): iterable
    {
        foreach ([false, true] as $inverse) {
            yield 'genere ' . (int)$inverse => [10, $inverse, false, 16.8];
            yield 'direct ' . (int)$inverse => [2, $inverse, false, 63.0];
            yield 'minimal ' . (int)$inverse => [null, $inverse, false, 16.8];
            yield 'cle unitaire conserve le besoin publie ' . (int)$inverse => [10, $inverse, true, 63.0];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ecsClesEtOrdres')]
    public function testChaqueInstallationEcsConserveSaCle(bool $inverse, bool $zone, ?int $mode = null): void
    {
        $installations = [];
        foreach ([[1000, 0.2], [2000, 0.3]] as [$besoin, $cle]) {
            $installations[] = '<installation_ecs><donnee_entree><cle_repartition_ecs>' . $cle . '</cle_repartition_ecs><rdim>1</rdim></donnee_entree>'
                . '<donnee_intermediaire><besoin_ecs>' . $besoin . '</besoin_ecs><besoin_ecs_depensier>' . ($besoin * 2) . '</besoin_ecs_depensier></donnee_intermediaire>'
                . '<generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_generateur_ecs_id>57</enum_type_generateur_ecs_id></donnee_entree>'
                . '<donnee_intermediaire><pn>20000</pn></donnee_intermediaire></generateur_ecs></generateur_ecs_collection></installation_ecs>';
        }
        $surfaceLogement = $zone ? '<surface_habitable_logement>50</surface_habitable_logement>' : '';
        $modeXml = $mode === null ? '' : '<enum_methode_application_dpe_log_id>' . $mode . '</enum_methode_application_dpe_log_id>';
        [$doc, $logement, $context] = $this->buildDoc('<logement><caracteristique_generale>' . $modeXml . $surfaceLogement . '<surface_habitable_immeuble>1000</surface_habitable_immeuble></caracteristique_generale><installation_ecs_collection>'
            . implode('', $inverse ? array_reverse($installations) : $installations) . '</installation_ecs_collection></logement>');

        (new AuxGenerationCalculator())->calculate($logement, $context);

        // Paux = 20 + 1,6 × 20 = 52 W. Les clés ne sont jamais globales.
        $expected = 52 / 20000 * ($zone && $mode !== 2 ? 1000 * 0.2 + 2000 * 0.3 : 3000);
        self::assertEqualsWithDelta($expected, $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 1e-12);
        self::assertEqualsWithDelta(2 * $expected, $this->efValue($doc, 'conso_auxiliaire_generation_ecs_depensier'), 1e-12);
    }

    public static function ecsClesEtOrdres(): iterable
    {
        yield 'appartement direct, pas de seconde cle' => [false, true, 2];
        yield 'appartement genere, cle par installation' => [false, true, 10];
        foreach ([false, true] as $inverse) {
            foreach ([false, true] as $zone) {
                yield 'ordre ' . (int)$inverse . ' zone ' . (int)$zone => [$inverse, $zone];
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('chaudieresBoisHybridesEcs')]
    public function testLaPartieChaudiereBoisHybrideGardeSaLigneDAuxiliaires(int $type, int $ventouse): void
    {
        [$doc, $logement, $context] = $this->buildDoc('<logement><installation_ecs_collection><installation_ecs><donnee_intermediaire><besoin_ecs>1000</besoin_ecs><besoin_ecs_depensier>2000</besoin_ecs_depensier></donnee_intermediaire><generateur_ecs><donnee_entree><enum_type_generateur_ecs_id>' . $type . '</enum_type_generateur_ecs_id><presence_ventouse>' . $ventouse . '</presence_ventouse></donnee_entree><donnee_intermediaire><pn>20000</pn></donnee_intermediaire></generateur_ecs></installation_ecs></installation_ecs_collection></logement>');
        (new AuxGenerationCalculator())->calculate($logement, $context);
        // §15.1 p.97 : chaudière bois, pas la partie PAC (auxiliaires nuls).
        $expected = $ventouse === 1 ? (73.3 + 10.5 * 20) * 1000 / 20000 : 0.0;
        self::assertEqualsWithDelta($expected, $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 1e-12);
        self::assertEqualsWithDelta(2 * $expected, $this->efValue($doc, 'conso_auxiliaire_generation_ecs_depensier'), 1e-12);
    }

    public static function chaudieresBoisHybridesEcs(): iterable
    {
        foreach (range(124, 131) as $type) {
            foreach ([0, 1] as $ventouse) {
                yield "$type ventouse $ventouse" => [$type, $ventouse];
            }
        }
    }

    public function testGasBoilerBatMode(): void
    {
        // bat_post2026 profile: Paux=84W, Pn=40kW, besoin_ch=21632.74, besoin_ecs=22349.06
        [$doc, $logement, $ctx] = $this->buildDoc(
            $this->gasCombinedXml(21632.7364635101, 28838.2832215158, 22349.0573350388, 31528.1344547869)
        );
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(45.4287, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
        $this->assertEqualsWithDelta(105.989, $this->efValue($doc, 'conso_auxiliaire_generation_ch_depensier'), 0.01);
        $this->assertEqualsWithDelta(46.933,  $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 0.001);
        $this->assertEqualsWithDelta(66.209,  $this->efValue($doc, 'conso_auxiliaire_generation_ecs_depensier'), 0.01);
    }

    public function testGasBoilerZoneMode(): void
    {
        // zone_post2026 profile: same Pn/Paux, cle_repartition scaling
        $cleRepCh  = 0.060749560276011361;
        $cleRepEcs = 0.056226656335245144;
        [$doc, $logement, $ctx] = $this->buildDoc(
            $this->gasCombinedXml(
                21632.7364635101, 28838.2832215158,
                22349.0573350388, 31528.1344547869,
                40000.0,
                $cleRepCh, $cleRepEcs
            )
        );
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(2.7598, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
        $this->assertEqualsWithDelta(6.4388, $this->efValue($doc, 'conso_auxiliaire_generation_ch_depensier'), 0.001);
        $this->assertEqualsWithDelta(2.6389, $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 0.001);
        $this->assertEqualsWithDelta(3.7227, $this->efValue($doc, 'conso_auxiliaire_generation_ecs_depensier'), 0.001);
    }

    /**
     * §15.1.1 : Bch_g est le besoin couvert par le générateur. Quand la
     * stratégie a déjà proratisé le besoin publié sur l'installation — ici
     * celui de l'appartement, qui chauffe toute sa surface — l'auxiliaire le
     * reprend tel quel, sans seconde part surfacique.
     */
    public function testLAuxiliaireReprendLeBesoinDejaProratise(): void
    {
        [$doc, $logement, $ctx] = $this->buildDoc($this->fioulAvecSurfaces(2));
        $ctx->set('chauffage.besoin_ch', 4549.0289651514);
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        // Paux = 20 + 1,6 x 18 = 48,8 W ; Q = 48,8 x 4549,03 / 18000.
        $this->assertEqualsWithDelta(12.3329, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
    }

    /**
     * Une installation qui porte encore le besoin entier du DPE tout en ne
     * chauffant qu'une fraction de la surface desservie n'en couvre que cette
     * fraction : §15.1.1 ramène alors Bch_g à `surface_chauffee / Sh`.
     */
    public function testUneInstallationPortantToutLeBesoinNenCouvreQueSaSurface(): void
    {
        [$doc, $logement, $ctx] = $this->buildDoc($this->fioulAvecSurfaces(9));
        $ctx->set('chauffage.besoin_ch', 4549.0289651514);
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(
            12.3329 * 64.96 / 4687.0,
            $this->efValue($doc, 'conso_auxiliaire_generation_ch'),
            0.0001
        );
    }

    /**
     * Le même immeuble, mais dont les stratégies ont réparti le besoin entre
     * plusieurs installations : chacune ne porte plus que sa part, et la
     * reproratiser la compterait deux fois (2400E0669882V, mode 6).
     */
    public function testUnBesoinDejaRepartiNestPasReproratise(): void
    {
        [$doc, $logement, $ctx] = $this->buildDoc($this->fioulAvecSurfaces(9));
        $ctx->set('chauffage.besoin_ch', 44877.590295596);
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(12.3329, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
    }

    /**
     * §15.1 p.97 distingue « chaudière bois atmosphérique » (0 / 0) de
     * « chaudière bois assistée par ventilateur » (73,3 / 10,5). La ventouse,
     * terminal à tirage forcé, départage les deux.
     */
    public function testUneChaudiereBoisSansVentouseEstAtmospherique(): void
    {
        [$doc, $logement, $ctx] = $this->buildDoc($this->chaudiereSeule(73, 0, 15000.0, 11287.3));
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertSame(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ch'));
    }

    public function testUneChaudiereBoisAVentouseEstAssisteeParVentilateur(): void
    {
        [$doc, $logement, $ctx] = $this->buildDoc($this->chaudiereSeule(73, 1, 15000.0, 11287.3));
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        // Paux = 73,3 + 10,5 x 15 = 230,8 W ; Q = 230,8 x 11287,3 / 15000.
        $this->assertEqualsWithDelta(173.6733, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
    }

    /**
     * §15.1 ne tabule pas le charbon et n'énonce que deux cas nuls (PAC, réseau
     * de chaleur). Le renvoi de §13.2.2.3 vers le bois bûche vaut pour le
     * rendement de combustion, pas pour les auxiliaires : la ligne retenue est
     * « chaudière au gaz ou au fioul », quelle que soit la ventouse.
     */
    public function testUneChaudiereCharbonSuitLaLigneGazFioul(): void
    {
        [$doc, $logement, $ctx] = $this->buildDoc($this->chaudiereSeule(121, 0, 18000.0, 9594.106));
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        // Paux = 20 + 1,6 x 18 = 48,8 W ; Q = 48,8 x 9594,106 / 18000.
        $this->assertEqualsWithDelta(26.0107, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
    }

    private function chaudiereSeule(int $genId, int $ventouse, float $pn, float $besoin): string
    {
        return <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <enum_methode_application_dpe_log_id>1</enum_methode_application_dpe_log_id>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree/>
            <donnee_intermediaire>
                <besoin_ch>$besoin</besoin_ch>
                <besoin_ch_depensier>$besoin</besoin_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_generateur_ch_id>$genId</enum_type_generateur_ch_id>
                        <presence_ventouse>$ventouse</presence_ventouse>
                    </donnee_entree>
                    <donnee_intermediaire>
                        <pn>$pn</pn>
                    </donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <sortie/>
</logement>
XML;
    }

    private function fioulAvecSurfaces(int $methode): string
    {
        return <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <enum_methode_application_dpe_log_id>$methode</enum_methode_application_dpe_log_id>
        <surface_habitable_logement>64.96</surface_habitable_logement>
        <surface_habitable_immeuble>4687</surface_habitable_immeuble>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                <surface_chauffee>64.96</surface_chauffee>
            </donnee_entree>
            <donnee_intermediaire>
                <besoin_ch>4549.0289651514</besoin_ch>
                <besoin_ch_depensier>6162.5758788342</besoin_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>3</enum_type_energie_id>
                        <enum_type_generateur_ch_id>75</enum_type_generateur_ch_id>
                        <tv_generateur_combustion_id>16</tv_generateur_combustion_id>
                    </donnee_entree>
                    <donnee_intermediaire>
                        <pn>18000</pn>
                    </donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <sortie/>
</logement>
XML;
    }

    public function testElectricGeneratorIsZero(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>500.0</surface_habitable_immeuble>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree/>
            <donnee_intermediaire>
                <besoin_ch>10000.0</besoin_ch>
                <besoin_ch_depensier>15000.0</besoin_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>1</enum_type_energie_id>
                    </donnee_entree>
                    <donnee_intermediaire>
                        <pn>10000</pn>
                    </donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
    <sortie/>
</logement>
XML;
        [$doc, $logement, $ctx] = $this->buildDoc($xml);
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ch_depensier'), 1e-9);
    }

    public function testPnCapAt400kWForGas(): void
    {
        // Pn = 600kW → capped at 400kW for gas boiler
        // Paux = 20 + 1.6 × 400 = 660 W
        // Q_aux_ch = 660 × 10000 / 600000 = 11.0 kWh
        [$doc, $logement, $ctx] = $this->buildDoc(
            $this->gasCombinedXml(10000.0, 15000.0, 8000.0, 12000.0, 600000.0)
        );
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $expectedPaux = 20.0 + 1.6 * 400.0; // 660 W (capped at 400kW)
        $expectedQch  = $expectedPaux * 10000.0 / 600000.0;
        $this->assertEqualsWithDelta($expectedQch, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 0.001);
    }

    public function testCollectiveVirtualizedBoilerUsesBuildingPowerWithoutDoubleScaling(): void
    {
        $xml = <<<'XML'
<logement>
  <caracteristique_generale><surface_habitable_logement>44</surface_habitable_logement><surface_habitable_immeuble>1500</surface_habitable_immeuble></caracteristique_generale>
  <installation_chauffage_collection><installation_chauffage>
    <donnee_entree><surface_chauffee>44</surface_chauffee><ratio_virtualisation>0.02933</ratio_virtualisation></donnee_entree>
    <donnee_intermediaire><besoin_ch>4480.47366624454</besoin_ch><besoin_ch_depensier>5732.53837310582</besoin_ch_depensier></donnee_intermediaire>
    <generateur_chauffage_collection><generateur_chauffage>
      <donnee_entree><enum_type_generateur_ch_id>88</enum_type_generateur_ch_id></donnee_entree>
      <donnee_intermediaire><pn>11733.33</pn></donnee_intermediaire>
    </generateur_chauffage></generateur_chauffage_collection>
  </installation_chauffage></installation_chauffage_collection>
  <installation_ecs_collection><installation_ecs>
    <donnee_entree><ratio_virtualisation>0.02933</ratio_virtualisation></donnee_entree>
    <donnee_intermediaire><besoin_ecs>1071.96332565</besoin_ecs><besoin_ecs_depensier>1512.23397725625</besoin_ecs_depensier></donnee_intermediaire>
    <generateur_ecs><donnee_entree><enum_type_generateur_ecs_id>48</enum_type_generateur_ecs_id></donnee_entree><donnee_intermediaire><pn>11733.33</pn></donnee_intermediaire></generateur_ecs>
  </installation_ecs></installation_ecs_collection>
  <sortie/>
</logement>
XML;
        [$doc, $logement, $ctx] = $this->buildDoc($xml);

        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        self::assertEqualsWithDelta(7.3927816, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 1e-6);
        self::assertEqualsWithDelta(16.85147, $this->efValue($doc, 'conso_auxiliaire_generation_ch_depensier'), 1e-6);
        self::assertEqualsWithDelta(1.7687395, $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 1e-6);
        self::assertEqualsWithDelta(2.4951861, $this->efValue($doc, 'conso_auxiliaire_generation_ecs_depensier'), 1e-6);
    }

    public function testNoPnSkipsGenerator(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>1000.0</surface_habitable_immeuble>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree/>
            <donnee_intermediaire>
                <besoin_ch>20000.0</besoin_ch>
                <besoin_ch_depensier>25000.0</besoin_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>2</enum_type_energie_id>
                        <tv_generateur_combustion_id>13</tv_generateur_combustion_id>
                    </donnee_entree>
                    <donnee_intermediaire/>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
    <sortie/>
</logement>
XML;
        [$doc, $logement, $ctx] = $this->buildDoc($xml);
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 1e-9);
    }

    public function testNoInstallationReturnsZero(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>500.0</surface_habitable_immeuble>
    </caracteristique_generale>
    <installation_chauffage_collection/>
    <installation_ecs_collection/>
    <sortie/>
</logement>
XML;
        [$doc, $logement, $ctx] = $this->buildDoc($xml);
        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ch'), 1e-9);
        $this->assertEqualsWithDelta(0.0, $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 1e-9);
    }

    /**
     * §17.1.3.2 p.109 : l'installation individuelle échantillonnée couvre ici
     * tout l'immeuble — 1 545 m² pour vingt et un logements — et sa
     * multiplicité vaut donc 21 appartements moyens. La surface du groupe est
     * ce qui la porte : sans elle, l'installation ne vaut que pour elle-même.
     */
    public function testSampledIndividualEcsAuxiliaryUsesEffectiveMultiplicity(): void
    {
        $xml = <<<'XML'
<logement><caracteristique_generale><surface_habitable_logement>54</surface_habitable_logement>
<surface_habitable_immeuble>1545</surface_habitable_immeuble><nombre_appartement>21</nombre_appartement></caracteristique_generale>
<installation_chauffage_collection/><installation_ecs_collection><installation_ecs><donnee_entree>
<enum_type_installation_id>1</enum_type_installation_id><enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
<nombre_logement>1</nombre_logement><ratio_virtualisation>1</ratio_virtualisation><rdim>1</rdim>
<surface_habitable>1545</surface_habitable>
<cle_repartition_ecs>0.042753700986532119</cle_repartition_ecs></donnee_entree>
<donnee_intermediaire><besoin_ecs>1307.63159877</besoin_ecs><besoin_ecs_depensier>1844.69457683625</besoin_ecs_depensier></donnee_intermediaire>
<generateur_ecs><donnee_entree><enum_type_generateur_ecs_id>56</enum_type_generateur_ecs_id></donnee_entree>
<donnee_intermediaire><pn>24000</pn></donnee_intermediaire></generateur_ecs>
</installation_ecs></installation_ecs_collection><sortie/></logement>
XML;
        [$doc, $logement, $ctx] = $this->buildDoc($xml);

        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(2.8568012, $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 1e-6);
    }

    /**
     * Un fichier qui déclare « absence de méthode d'échantillonnage »
     * (`enum_calcul_echantillonnage_id` = 1) garde la multiplicité explicite
     * de son installation unique : §17.1.3.2 p.109 ne multiplie par Nblgt que
     * l'appartement « moyen » d'un échantillon.
     *
     * 2659E0496026K — 119,59 m² pour deux logements tous deux visités, une
     * chaudière gaz à condensation de 24 kW, besoin ECS 2 425,874 kWh. La
     * référence publie 2,797184 : 58,4 W × 2 425,874 / 24 000 × 0,473861,
     * sans facteur 2.
     */
    public function testUnReleveExhaustifNeMultipliePasLAuxiliaireEcs(): void
    {
        $xml = <<<'XML'
<logement><caracteristique_generale><surface_habitable_logement>50.57</surface_habitable_logement>
<surface_habitable_immeuble>119.59</surface_habitable_immeuble><nombre_appartement>2</nombre_appartement>
<enum_calcul_echantillonnage_id>1</enum_calcul_echantillonnage_id></caracteristique_generale>
<installation_chauffage_collection/><installation_ecs_collection><installation_ecs><donnee_entree>
<enum_type_installation_id>1</enum_type_installation_id><enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
<nombre_logement>1</nombre_logement><ratio_virtualisation>1</ratio_virtualisation><rdim>1</rdim>
<surface_habitable>119.59</surface_habitable>
<cle_repartition_ecs>0.47386117853735943</cle_repartition_ecs></donnee_entree>
<donnee_intermediaire><besoin_ecs>2425.87428155394</besoin_ecs><besoin_ecs_depensier>3422.2155043350203</besoin_ecs_depensier></donnee_intermediaire>
<generateur_ecs><donnee_entree><enum_type_generateur_ecs_id>57</enum_type_generateur_ecs_id></donnee_entree>
<donnee_intermediaire><pn>24000</pn></donnee_intermediaire></generateur_ecs>
</installation_ecs></installation_ecs_collection><sortie/></logement>
XML;
        [$doc, $logement, $ctx] = $this->buildDoc($xml);
        $visite = $doc->createElement('dpe_immeuble');
        $collection = $doc->createElement('logement_visite_collection');
        foreach ([50.57, 69.02] as $surface) {
            $node = $doc->createElement('logement_visite');
            $node->appendChild($doc->createElement('surface_habitable_logement', (string)$surface));
            $collection->appendChild($node);
        }
        $visite->appendChild($collection);
        $doc->documentElement->appendChild($visite);

        (new AuxGenerationCalculator())->calculate($logement, $ctx);

        $this->assertEqualsWithDelta(2.7971839, $this->efValue($doc, 'conso_auxiliaire_generation_ecs'), 1e-6);
    }
}
