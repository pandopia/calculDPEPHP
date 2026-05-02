<?php

declare(strict_types=1);

namespace Tests\Unit\ProductionElec;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Common\Period;
use CalculDpePHP\ProductionElec\ProductionPvCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ProductionPvCalculator.
 *
 * §16.2 p.103-105 :
 *   Ppv = Σ ki × Scapteur × r × Σ_mois(Epv_j) × C
 *   Tap = 1/(1/Tcv + 1/Tapl)   avec Tcv = Ppv/Celec_tot, Tapl = Σ(taplpi × Celec_i)/Celec_tot
 *   Celec_ac = Celec_tot × Tap
 */
final class ProductionPvCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 1e-3;

    private function makeContext(DOMDocument $doc, array $vars = [], ?Period $period = null): CalculationContext
    {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '1',  // H1a
            classeAltitude: '1',
            period: $period ?? Period::PRE_2026,
        );
        foreach ($vars as $k => $v) {
            $ctx->set($k, $v);
        }
        return $ctx;
    }

    private function runOnLogement(string $xml, array $contextVars = [], ?Period $period = null): DOMDocument
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $calc = new ProductionPvCalculator();
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, $contextVars, $period);
        $calc->calculate($logement, $ctx);
        return $doc;
    }

    /**
     * Sans PV (presence_production_pv=0) : aucun donnee_intermediaire écrit.
     */
    public function testNoPvPanels(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <production_elec_enr>
        <donnee_entree>
            <presence_production_pv>0</presence_production_pv>
        </donnee_entree>
        <panneaux_pv_collection/>
    </production_elec_enr>
    <sortie/>
</logement>
XML;
        $doc = $this->runOnLogement($xml);
        $diNodes = $doc->getElementsByTagName('production_elec_enr')->item(0)
            ->getElementsByTagName('donnee_intermediaire');
        $this->assertSame(0, $diNodes->length, 'Aucun donnee_intermediaire attendu sans PV');
    }

    /**
     * Absence de nœud production_elec_enr : aucune erreur.
     */
    public function testNoPvNode(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <sortie/>
</logement>
XML;
        $doc = $this->runOnLogement($xml);
        $this->assertEmpty($doc->getElementsByTagName('donnee_intermediaire'));
    }

    /**
     * PV présent avec 1 panneau orientation Sud inclinaison 15-45° (ki=1.07).
     * Surface 10 m², zone H1a.
     * Ppv_annuel = 1.07 × 10 × 0.17 × Σ(Epv_j) × 0.86
     * Σ(Epv_H1a) = 50.1+54.7+123.4+169.3+208.2+217.8+221.9+173.3+163.6+88.9+59.1+43.2 = 1573.5
     * Ppv = 1.07 × 10 × 0.17 × 1573.5 × 0.86 = 2465.52 kWh
     */
    public function testSimplePvComputation(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <production_elec_enr>
        <donnee_entree>
            <presence_production_pv>1</presence_production_pv>
        </donnee_entree>
        <panneaux_pv_collection>
            <panneaux_pv>
                <tv_coef_orientation_pv_id>10</tv_coef_orientation_pv_id>
                <surface_totale_capteurs>10</surface_totale_capteurs>
            </panneaux_pv>
        </panneaux_pv_collection>
    </production_elec_enr>
    <installation_chauffage_collection/>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            <conso_ch>2000</conso_ch>
            <conso_ecs>500</conso_ecs>
            <conso_fr>0</conso_fr>
            <conso_eclairage>300</conso_eclairage>
            <conso_totale_auxiliaire>100</conso_totale_auxiliaire>
            <conso_auxiliaire_generation_ch>0</conso_auxiliaire_generation_ch>
            <conso_auxiliaire_generation_ecs>0</conso_auxiliaire_generation_ecs>
            <conso_auxiliaire_distribution_ch>50</conso_auxiliaire_distribution_ch>
            <conso_auxiliaire_distribution_ecs>30</conso_auxiliaire_distribution_ecs>
            <conso_auxiliaire_ventilation>20</conso_auxiliaire_ventilation>
            <conso_5_usages>2900</conso_5_usages>
            <conso_5_usages_m2>29</conso_5_usages_m2>
        </ef_conso>
        <ep_conso>
            <ep_conso_ch>3800</ep_conso_ch>
            <ep_conso_ecs>950</ep_conso_ecs>
            <ep_conso_fr>0</ep_conso_fr>
            <ep_conso_eclairage>570</ep_conso_eclairage>
            <ep_conso_totale_auxiliaire>190</ep_conso_totale_auxiliaire>
            <ep_conso_5_usages>5510</ep_conso_5_usages>
            <ep_conso_5_usages_m2>55</ep_conso_5_usages_m2>
        </ep_conso>
    </sortie>
</logement>
XML;
        $doc = $this->runOnLogement($xml);

        // Vérifier production_pv > 0
        $pvNode = $doc->getElementsByTagName('production_elec_enr')->item(0);
        $diNode = $pvNode->getElementsByTagName('donnee_intermediaire')->item(0);
        $this->assertNotNull($diNode, 'donnee_intermediaire doit exister');

        $productionPv = (float)$diNode->getElementsByTagName('production_pv')->item(0)->textContent;
        $sumEpvH1a = 50.1 + 54.7 + 123.4 + 169.3 + 208.2 + 217.8 + 221.9 + 173.3 + 163.6 + 88.9 + 59.1 + 43.2;
        $expectedPpv = 1.07 * 10.0 * 0.17 * $sumEpvH1a * 0.86;
        $this->assertEqualsWithDelta($expectedPpv, $productionPv, $expectedPpv * self::TOL, 'production_pv');

        $tap = (float)$diNode->getElementsByTagName('taux_autoproduction')->item(0)->textContent;
        $this->assertGreaterThan(0.0, $tap, 'taux_autoproduction > 0');
        $this->assertLessThanOrEqual(1.0, $tap, 'taux_autoproduction ≤ 1');

        $celecAc = (float)$diNode->getElementsByTagName('conso_elec_ac')->item(0)->textContent;
        $this->assertGreaterThan(0.0, $celecAc, 'conso_elec_ac > 0');
    }

    /**
     * Avec nombre_module à la place de surface_totale_capteurs.
     * 5 modules × 1.6 m²/module = 8 m², ki=1.0 (est, ≤15°, id=1)
     */
    public function testNombreModuleFallback(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <production_elec_enr>
        <donnee_entree>
            <presence_production_pv>1</presence_production_pv>
        </donnee_entree>
        <panneaux_pv_collection>
            <panneaux_pv>
                <tv_coef_orientation_pv_id>1</tv_coef_orientation_pv_id>
                <nombre_module>5</nombre_module>
            </panneaux_pv>
        </panneaux_pv_collection>
    </production_elec_enr>
    <installation_chauffage_collection/>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            <conso_ch>1000</conso_ch>
            <conso_ecs>300</conso_ecs>
            <conso_fr>0</conso_fr>
            <conso_eclairage>200</conso_eclairage>
            <conso_totale_auxiliaire>50</conso_totale_auxiliaire>
            <conso_auxiliaire_generation_ch>0</conso_auxiliaire_generation_ch>
            <conso_auxiliaire_generation_ecs>0</conso_auxiliaire_generation_ecs>
            <conso_auxiliaire_distribution_ch>20</conso_auxiliaire_distribution_ch>
            <conso_auxiliaire_distribution_ecs>15</conso_auxiliaire_distribution_ecs>
            <conso_auxiliaire_ventilation>15</conso_auxiliaire_ventilation>
            <conso_5_usages>1550</conso_5_usages>
            <conso_5_usages_m2>15</conso_5_usages_m2>
        </ef_conso>
        <ep_conso>
            <ep_conso_ch>1900</ep_conso_ch>
            <ep_conso_ecs>570</ep_conso_ecs>
            <ep_conso_fr>0</ep_conso_fr>
            <ep_conso_eclairage>380</ep_conso_eclairage>
            <ep_conso_totale_auxiliaire>95</ep_conso_totale_auxiliaire>
            <ep_conso_5_usages>2945</ep_conso_5_usages>
            <ep_conso_5_usages_m2>29</ep_conso_5_usages_m2>
        </ep_conso>
    </sortie>
</logement>
XML;
        $doc = $this->runOnLogement($xml);

        $pvNode = $doc->getElementsByTagName('production_elec_enr')->item(0);
        $diNode = $pvNode->getElementsByTagName('donnee_intermediaire')->item(0);
        $this->assertNotNull($diNode);

        $productionPv = (float)$diNode->getElementsByTagName('production_pv')->item(0)->textContent;
        $sumEpvH1a = 50.1 + 54.7 + 123.4 + 169.3 + 208.2 + 217.8 + 221.9 + 173.3 + 163.6 + 88.9 + 59.1 + 43.2;
        $expectedPpv = 1.00 * (5 * 1.6) * 0.17 * $sumEpvH1a * 0.86;
        $this->assertEqualsWithDelta($expectedPpv, $productionPv, $expectedPpv * self::TOL, 'production_pv avec nombre_module');
    }

    /**
     * EF conso réduite par autoconsommation (conso_5_usages doit baisser).
     */
    public function testEfConsoReducedByAutoconsumption(): void
    {
        // PV important : 50 m² panneaux Sud 15-45° (ki=1.07)
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>200</surface_habitable_logement>
    </caracteristique_generale>
    <production_elec_enr>
        <donnee_entree>
            <presence_production_pv>1</presence_production_pv>
        </donnee_entree>
        <panneaux_pv_collection>
            <panneaux_pv>
                <tv_coef_orientation_pv_id>10</tv_coef_orientation_pv_id>
                <surface_totale_capteurs>50</surface_totale_capteurs>
            </panneaux_pv>
        </panneaux_pv_collection>
    </production_elec_enr>
    <installation_chauffage_collection>
        <installation_chauffage>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>1</enum_type_energie_id>
                    </donnee_entree>
                    <donnee_intermediaire>
                        <conso_ch>3000</conso_ch>
                    </donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            <conso_ch>3000</conso_ch>
            <conso_ecs>0</conso_ecs>
            <conso_fr>0</conso_fr>
            <conso_eclairage>400</conso_eclairage>
            <conso_totale_auxiliaire>100</conso_totale_auxiliaire>
            <conso_auxiliaire_generation_ch>0</conso_auxiliaire_generation_ch>
            <conso_auxiliaire_generation_ecs>0</conso_auxiliaire_generation_ecs>
            <conso_auxiliaire_distribution_ch>60</conso_auxiliaire_distribution_ch>
            <conso_auxiliaire_distribution_ecs>0</conso_auxiliaire_distribution_ecs>
            <conso_auxiliaire_ventilation>40</conso_auxiliaire_ventilation>
            <conso_5_usages>3500</conso_5_usages>
            <conso_5_usages_m2>17</conso_5_usages_m2>
        </ef_conso>
        <ep_conso>
            <ep_conso_ch>5700</ep_conso_ch>
            <ep_conso_ecs>0</ep_conso_ecs>
            <ep_conso_fr>0</ep_conso_fr>
            <ep_conso_eclairage>760</ep_conso_eclairage>
            <ep_conso_totale_auxiliaire>190</ep_conso_totale_auxiliaire>
            <ep_conso_5_usages>6650</ep_conso_5_usages>
            <ep_conso_5_usages_m2>33</ep_conso_5_usages_m2>
        </ep_conso>
    </sortie>
</logement>
XML;
        $doc = $this->runOnLogement($xml);

        $sortie = $doc->getElementsByTagName('sortie')->item(0);
        $efNode = null;
        foreach ($sortie->childNodes as $c) {
            if ($c instanceof \DOMElement && $c->nodeName === 'ef_conso') {
                $efNode = $c;
                break;
            }
        }
        $this->assertNotNull($efNode);

        $conso5 = (float)$efNode->getElementsByTagName('conso_5_usages')->item(0)->textContent;
        $this->assertLessThan(3500.0, $conso5, 'conso_5_usages doit être réduit par PV');
    }

    /**
     * Vérification ki par tv_coef_orientation_pv_id=10 → ki=1.07 (Sud, 15-45°).
     * Comparé avec id=1 → ki=1.00 (Est, ≤15°) : Sud doit produire plus.
     */
    public function testSudProducesMoreThanEst(): void
    {
        $baseXml = fn(int $tvId, float $surface) => <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <production_elec_enr>
        <donnee_entree>
            <presence_production_pv>1</presence_production_pv>
        </donnee_entree>
        <panneaux_pv_collection>
            <panneaux_pv>
                <tv_coef_orientation_pv_id>$tvId</tv_coef_orientation_pv_id>
                <surface_totale_capteurs>$surface</surface_totale_capteurs>
            </panneaux_pv>
        </panneaux_pv_collection>
    </production_elec_enr>
    <installation_chauffage_collection/>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            <conso_ch>1000</conso_ch>
            <conso_ecs>300</conso_ecs>
            <conso_fr>0</conso_fr>
            <conso_eclairage>200</conso_eclairage>
            <conso_totale_auxiliaire>50</conso_totale_auxiliaire>
            <conso_auxiliaire_generation_ch>0</conso_auxiliaire_generation_ch>
            <conso_auxiliaire_generation_ecs>0</conso_auxiliaire_generation_ecs>
            <conso_auxiliaire_distribution_ch>20</conso_auxiliaire_distribution_ch>
            <conso_auxiliaire_distribution_ecs>15</conso_auxiliaire_distribution_ecs>
            <conso_auxiliaire_ventilation>15</conso_auxiliaire_ventilation>
            <conso_5_usages>1550</conso_5_usages>
            <conso_5_usages_m2>15</conso_5_usages_m2>
        </ef_conso>
        <ep_conso>
            <ep_conso_ch>1900</ep_conso_ch>
            <ep_conso_ecs>570</ep_conso_ecs>
            <ep_conso_fr>0</ep_conso_fr>
            <ep_conso_eclairage>380</ep_conso_eclairage>
            <ep_conso_totale_auxiliaire>95</ep_conso_totale_auxiliaire>
            <ep_conso_5_usages>2945</ep_conso_5_usages>
            <ep_conso_5_usages_m2>29</ep_conso_5_usages_m2>
        </ep_conso>
    </sortie>
</logement>
XML;

        $docSud = $this->runOnLogement($baseXml(10, 10.0)); // Sud 15-45°, ki=1.07
        $docEst = $this->runOnLogement($baseXml(1, 10.0));  // Est ≤15°, ki=1.00

        $getPpv = static function (DOMDocument $doc): float {
            $pvNode = $doc->getElementsByTagName('production_elec_enr')->item(0);
            return (float)$pvNode->getElementsByTagName('production_pv')->item(0)->textContent;
        };

        $ppvSud = $getPpv($docSud);
        $ppvEst = $getPpv($docEst);

        $this->assertGreaterThan($ppvEst, $ppvSud, 'Sud (ki=1.07) doit produire plus que Est (ki=1.00)');
        $this->assertEqualsWithDelta(1.07 / 1.00, $ppvSud / $ppvEst, 1e-4, 'Ratio ki=1.07/1.00');
    }

    /**
     * Post-2026 : coef_ep=2.3 → réduction EP plus grande qu'en pré-2026 (coef=1.9).
     */
    public function testPost2026UsesCoefEp23(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <production_elec_enr>
        <donnee_entree>
            <presence_production_pv>1</presence_production_pv>
        </donnee_entree>
        <panneaux_pv_collection>
            <panneaux_pv>
                <tv_coef_orientation_pv_id>10</tv_coef_orientation_pv_id>
                <surface_totale_capteurs>20</surface_totale_capteurs>
            </panneaux_pv>
        </panneaux_pv_collection>
    </production_elec_enr>
    <installation_chauffage_collection/>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            <conso_ch>2000</conso_ch>
            <conso_ecs>0</conso_ecs>
            <conso_fr>0</conso_fr>
            <conso_eclairage>300</conso_eclairage>
            <conso_totale_auxiliaire>100</conso_totale_auxiliaire>
            <conso_auxiliaire_generation_ch>0</conso_auxiliaire_generation_ch>
            <conso_auxiliaire_generation_ecs>0</conso_auxiliaire_generation_ecs>
            <conso_auxiliaire_distribution_ch>50</conso_auxiliaire_distribution_ch>
            <conso_auxiliaire_distribution_ecs>0</conso_auxiliaire_distribution_ecs>
            <conso_auxiliaire_ventilation>50</conso_auxiliaire_ventilation>
            <conso_5_usages>2400</conso_5_usages>
            <conso_5_usages_m2>24</conso_5_usages_m2>
        </ef_conso>
        <ep_conso>
            <ep_conso_ch>4600</ep_conso_ch>
            <ep_conso_ecs>0</ep_conso_ecs>
            <ep_conso_fr>0</ep_conso_fr>
            <ep_conso_eclairage>690</ep_conso_eclairage>
            <ep_conso_totale_auxiliaire>230</ep_conso_totale_auxiliaire>
            <ep_conso_5_usages>5520</ep_conso_5_usages>
            <ep_conso_5_usages_m2>55</ep_conso_5_usages_m2>
        </ep_conso>
    </sortie>
</logement>
XML;
        $docPre  = $this->runOnLogement($xml, [], Period::PRE_2026);
        $docPost = $this->runOnLogement($xml, [], Period::POST_2026);

        $getEp5 = static function (DOMDocument $doc): float {
            $sortie = $doc->getElementsByTagName('sortie')->item(0);
            foreach ($sortie->childNodes as $c) {
                if ($c instanceof \DOMElement && $c->nodeName === 'ep_conso') {
                    return (float)$c->getElementsByTagName('ep_conso_5_usages')->item(0)->textContent;
                }
            }
            return 0.0;
        };

        $ep5Pre  = $getEp5($docPre);
        $ep5Post = $getEp5($docPost);

        $this->assertLessThan($ep5Pre, $ep5Post, 'Post-2026 (coef=2.3) a une réduction EP plus grande → ep_5 plus bas');
    }
}
