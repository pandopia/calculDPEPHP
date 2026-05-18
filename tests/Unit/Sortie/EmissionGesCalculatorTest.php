<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Common\Period;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\EmissionGesCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour EmissionGesCalculator.
 *
 * Facteurs GES (kgCO2eq/kWh EF) :
 *   Électricité CH  : 0.079   Électricité ECS : 0.065
 *   Électricité ECL : 0.069   Auxiliaires     : 0.064
 *   Gaz naturel     : 0.227
 */
final class EmissionGesCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function makeContext(DOMDocument $doc, Period $period = Period::PRE_2026): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            period: $period,
        );
    }

    /**
     * XML minimal : ef_conso dans le DOM, sans installations.
     */
    private function buildDocWithEfConso(array $efValues, ?float $shLogement, float $shImmeuble): array
    {
        $shLogTag = $shLogement !== null
            ? "<surface_habitable_logement>$shLogement</surface_habitable_logement>"
            : '';
        $efXml = '';
        foreach ($efValues as $tag => $val) {
            $efXml .= "<$tag>$val</$tag>\n";
        }
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        $shLogTag
        <surface_habitable_immeuble>$shImmeuble</surface_habitable_immeuble>
        <nombre_appartement>1</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection/>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            $efXml
        </ef_conso>
        <ep_conso>
            <classe_bilan_dpe>B</classe_bilan_dpe>
        </ep_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc);
        return [$doc, $node, $ctx];
    }

    /**
     * Éclairage : ef=100 × 0.069 = 6.9.
     */
    public function testEclairageGes(): void
    {
        [$doc, $node, $ctx] = $this->buildDocWithEfConso(
            ['conso_eclairage' => 100.0],
            shLogement: 100.0,
            shImmeuble: 1000.0,
        );

        (new EmissionGesCalculator())->calculate($node, $ctx);

        $emGes = $doc->getElementsByTagName('emission_ges')->item(0);
        $ges   = (float)$emGes->getElementsByTagName('emission_ges_eclairage')->item(0)->textContent;

        $this->assertEqualsWithDelta(100.0 * 0.069, $ges, 0.001, 'GES éclairage');
    }

    /**
     * Auxiliaire ventilation : ef=500 × 0.064 = 32.
     */
    public function testAuxVentilationGes(): void
    {
        [$doc, $node, $ctx] = $this->buildDocWithEfConso(
            ['conso_auxiliaire_ventilation' => 500.0, 'conso_eclairage' => 0.0],
            shLogement: null,
            shImmeuble: 1000.0,
        );

        (new EmissionGesCalculator())->calculate($node, $ctx);

        $emGes = $doc->getElementsByTagName('emission_ges')->item(0);
        $gesV  = (float)$emGes->getElementsByTagName('emission_ges_auxiliaire_ventilation')->item(0)->textContent;
        $gesT  = (float)$emGes->getElementsByTagName('emission_ges_totale_auxiliaire')->item(0)->textContent;

        $this->assertEqualsWithDelta(500.0 * 0.064, $gesV, 0.001, 'GES aux_vent');
        $this->assertEqualsWithDelta(500.0 * 0.064, $gesT, 0.001, 'GES aux_total');
    }

    /**
     * Installation CH gaz (id=2) : ef_conso_ch × 0.227.
     */
    public function testChGazGes(): void
    {
        $cle = 62.86 / 1034.74;
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>62.86</surface_habitable_logement>
        <surface_habitable_immeuble>1034.74</surface_habitable_immeuble>
        <nombre_appartement>19</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                <enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
                <enum_type_installation_id>2</enum_type_installation_id>
                <rdim>1</rdim>
                <ratio_virtualisation>1</ratio_virtualisation>
                <cle_repartition_ch>$cle</cle_repartition_ch>
            </donnee_entree>
            <donnee_intermediaire>
                <conso_ch>27615.0475180499</conso_ch>
                <conso_ch_depensier>36927.1997513452</conso_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>2</enum_type_energie_id>
                    </donnee_entree>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            <conso_ch>1677.6019937226922</conso_ch>
            <conso_eclairage>0</conso_eclairage>
            <conso_fr>0</conso_fr>
            <conso_fr_depensier>0</conso_fr_depensier>
        </ef_conso>
        <ep_conso>
            <classe_bilan_dpe>B</classe_bilan_dpe>
        </ep_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc);

        (new EmissionGesCalculator())->calculate($node, $ctx);

        $emGes = $doc->getElementsByTagName('emission_ges')->item(0);
        $gesCh = (float)$emGes->getElementsByTagName('emission_ges_ch')->item(0)->textContent;

        // ZONE: 27615.05 × 1 (rdim collectif=1) × 0.227 × cle = 1677.60 × 0.227 = 380.82
        $this->assertEqualsWithDelta(1677.60 * 0.227, $gesCh, 1.0, 'GES CH gaz ZONE');
    }

    /**
     * Installation CH électricité (id=1) : ef × 0.079.
     */
    public function testChElecGes(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>102</surface_habitable_logement>
        <surface_habitable_immeuble>9543</surface_habitable_immeuble>
        <nombre_appartement>127</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                <enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
                <enum_type_installation_id>1</enum_type_installation_id>
                <rdim>1</rdim>
                <ratio_virtualisation>1</ratio_virtualisation>
                <nombre_logement_echantillon>1</nombre_logement_echantillon>
                <cle_repartition_ch>0.011169</cle_repartition_ch>
            </donnee_entree>
            <donnee_intermediaire>
                <conso_ch>3478.52</conso_ch>
                <conso_ch_depensier>4535.69</conso_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>1</enum_type_energie_id>
                    </donnee_entree>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            <conso_ch>4934.16</conso_ch>
            <conso_eclairage>0</conso_eclairage>
            <conso_fr>0</conso_fr>
            <conso_fr_depensier>0</conso_fr_depensier>
        </ef_conso>
        <ep_conso>
            <classe_bilan_dpe>C</classe_bilan_dpe>
        </ep_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc);

        (new EmissionGesCalculator())->calculate($node, $ctx);

        $emGes = $doc->getElementsByTagName('emission_ges')->item(0);
        $gesCh = (float)$emGes->getElementsByTagName('emission_ges_ch')->item(0)->textContent;

        // rdim_eff = 127/1=127, building_total = 3478.52×127×0.011169 = ~4934.16 × 0.079
        $this->assertEqualsWithDelta(4934.16 * 0.079, $gesCh, 1.0, 'GES CH élec ZONE');
    }

    /**
     * ECS électricité (id=1) : ef × 0.065.
     */
    public function testEcsElecGes(): void
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>102</surface_habitable_logement>
        <surface_habitable_immeuble>9543</surface_habitable_immeuble>
        <nombre_appartement>127</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection/>
    <installation_ecs_collection>
        <installation_ecs>
            <donnee_entree>
                <enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
                <enum_type_installation_id>1</enum_type_installation_id>
                <rdim>1</rdim>
                <ratio_virtualisation>1</ratio_virtualisation>
                <nombre_logement>1</nombre_logement>
                <cle_repartition_ecs>0.011169</cle_repartition_ecs>
            </donnee_entree>
            <generateur_ecs_collection>
                <generateur_ecs>
                    <donnee_entree>
                        <enum_type_energie_id>1</enum_type_energie_id>
                    </donnee_entree>
                    <donnee_intermediaire>
                        <conso_ecs>2000.0</conso_ecs>
                        <conso_ecs_depensier>2600.0</conso_ecs_depensier>
                    </donnee_intermediaire>
                </generateur_ecs>
            </generateur_ecs_collection>
        </installation_ecs>
    </installation_ecs_collection>
    <sortie>
        <ef_conso>
            <conso_eclairage>0</conso_eclairage>
            <conso_fr>0</conso_fr>
            <conso_fr_depensier>0</conso_fr_depensier>
        </ef_conso>
        <ep_conso>
            <classe_bilan_dpe>B</classe_bilan_dpe>
        </ep_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc);

        (new EmissionGesCalculator())->calculate($node, $ctx);

        $emGes  = $doc->getElementsByTagName('emission_ges')->item(0);
        $gesEcs = (float)$emGes->getElementsByTagName('emission_ges_ecs')->item(0)->textContent;

        // rdim_eff = 127/1=127, ZONE: building_total × 0.011169, then × 0.065
        $buildingTotal = 2000.0 * 127.0;
        $expected      = $buildingTotal * 0.011169 * 0.065;
        $this->assertEqualsWithDelta($expected, $gesEcs, 1.0, 'GES ECS élec ZONE');
    }

    /**
     * Classe GES avec valeurs clairement au milieu de chaque plage (évite problèmes FP aux seuils).
     * Thresholds : A≤6, B≤11, C≤30, D≤50, E≤70, F≤100, G>100.
     * ef_eclairage × 0.069 / surface=100 = ges5m2.
     */
    public function testClasseGesThresholds(): void
    {
        // ef × 0.069 / 100 = ges5m2 approximatif — on choisit des valeurs loin des seuils
        $cases = [
            [100.0 / 0.069 * 3,  'A'],  // ges5m2≈3 → A
            [100.0 / 0.069 * 9,  'B'],  // ges5m2≈9 → B
            [100.0 / 0.069 * 20, 'C'],  // ges5m2≈20 → C
            [100.0 / 0.069 * 40, 'D'],  // ges5m2≈40 → D
            [100.0 / 0.069 * 60, 'E'],  // ges5m2≈60 → E
            [100.0 / 0.069 * 85, 'F'],  // ges5m2≈85 → F
            [100.0 / 0.069 * 150, 'G'], // ges5m2≈150 → G
        ];

        foreach ($cases as [$efEcl, $expectedClasse]) {
            [$doc, $node, $ctx] = $this->buildDocWithEfConso(
                ['conso_eclairage' => $efEcl],
                shLogement: 100.0,
                shImmeuble: 10000.0,
            );

            (new EmissionGesCalculator())->calculate($node, $ctx);

            $emGes  = $doc->getElementsByTagName('emission_ges')->item(0);
            $classe = $emGes->getElementsByTagName('classe_emission_ges')->item(0)->textContent;

            $this->assertSame($expectedClasse, $classe, "efEcl={$efEcl} → classe {$expectedClasse}");
        }
    }

    /**
     * classe_bilan_dpe = WORST(classe_energie, classe_ges).
     * Si classe_energie=B et classe_ges=C → classe_bilan=C.
     */
    public function testClasseBilanDpeWorstOfTwo(): void
    {
        // GES = 15 → C (15 > 11, ≤ 30). classe_energie = B → WORST = C.
        $efEcl = 15.0 / 0.069; // → ges ≈ 15 kgCO2/m² avec surface=1
        [$doc, $node, $ctx] = $this->buildDocWithEfConso(
            ['conso_eclairage' => $efEcl],
            shLogement: 1.0,
            shImmeuble: 100.0,
        );

        // La classe_bilan_dpe initiale dans le DOM est 'B' (par buildDocWithEfConso)
        (new EmissionGesCalculator())->calculate($node, $ctx);

        $epConso = $doc->getElementsByTagName('ep_conso')->item(0);
        $classeBilan = $epConso->getElementsByTagName('classe_bilan_dpe')->item(0)->textContent;

        $this->assertSame('C', $classeBilan, 'WORST(B, C) = C');
    }

    /**
     * classe_bilan_dpe = WORST : si GES est mieux que énergie, énergie gagne.
     */
    public function testClasseBilanDpeEnergiePrevails(): void
    {
        // GES très bas → A. classe_energie = C. WORST = C.
        $efEcl = 1.0; // → ges = 0.069 kgCO2/m² → A
        [$doc, $node, $ctx] = $this->buildDocWithEfConso(
            ['conso_eclairage' => $efEcl],
            shLogement: 1.0,
            shImmeuble: 100.0,
        );

        // Overwrite ep_conso.classe_bilan_dpe = C
        $epConso = $doc->getElementsByTagName('ep_conso')->item(0);
        foreach ($epConso->childNodes as $c) {
            if ($c instanceof \DOMElement && $c->nodeName === 'classe_bilan_dpe') {
                $c->textContent = 'C';
            }
        }

        (new EmissionGesCalculator())->calculate($node, $ctx);

        $classeBilan = $epConso->getElementsByTagName('classe_bilan_dpe')->item(0)->textContent;

        $this->assertSame('C', $classeBilan, 'WORST(C, A) = C');
    }

    /**
     * Chauffage sur réseau de chaleur urbain → facteur GES = contenu_co2_acv
     * spécifique au réseau (lookup tv_reseau_chaleur), pas la valeur générique 0.110.
     *
     * Test sur 9120C (Réseau Viry-Châtillon, contenu_co2_acv table 2024 = 0.314).
     * date_arrete_reseau_chaleur=2025-04-11 → year-1 = 2024.
     */
    public function testGesReseauChaleurLookupByIdentifiant(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<dpe>
    <administratif>
        <date_etablissement_dpe>2026-04-03</date_etablissement_dpe>
    </administratif>
    <logement>
        <caracteristique_generale>
            <surface_habitable_immeuble>1000</surface_habitable_immeuble>
            <nombre_appartement>10</nombre_appartement>
        </caracteristique_generale>
        <installation_chauffage_collection>
            <installation_chauffage>
                <donnee_entree>
                    <rdim>1</rdim>
                </donnee_entree>
                <donnee_intermediaire>
                    <conso_ch>10000</conso_ch>
                    <conso_ch_depensier>12000</conso_ch_depensier>
                </donnee_intermediaire>
                <generateur_chauffage_collection>
                    <generateur_chauffage>
                        <donnee_entree>
                            <enum_type_energie_id>8</enum_type_energie_id>
                            <identifiant_reseau_chaleur>9120C</identifiant_reseau_chaleur>
                            <date_arrete_reseau_chaleur>2025-04-11</date_arrete_reseau_chaleur>
                        </donnee_entree>
                    </generateur_chauffage>
                </generateur_chauffage_collection>
            </installation_chauffage>
        </installation_chauffage_collection>
        <installation_ecs_collection/>
        <sortie>
            <ef_conso>
                <conso_ch>10000</conso_ch>
                <conso_eclairage>0</conso_eclairage>
                <conso_fr>0</conso_fr>
                <conso_fr_depensier>0</conso_fr_depensier>
            </ef_conso>
            <ep_conso><classe_bilan_dpe>D</classe_bilan_dpe></ep_conso>
        </sortie>
    </logement>
</dpe>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc);

        (new EmissionGesCalculator())->calculate($node, $ctx);

        $emGes = $doc->getElementsByTagName('emission_ges')->item(0);
        $gesCh = (float)$emGes->getElementsByTagName('emission_ges_ch')->item(0)->textContent;

        // 10000 kWh × 0.314 (contenu_co2_acv 9120C 2023) = 3140 kgCO2
        $this->assertEqualsWithDelta(10000 * 0.314, $gesCh, 1.0,
            'GES réseau de chaleur urbain doit utiliser le facteur spécifique au réseau');
    }

    /**
     * Réseau de chaleur sans identifiant → fallback 0.385 (« autres réseaux »).
     */
    public function testGesReseauChaleurFallbackSansIdentifiant(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>1000</surface_habitable_immeuble>
        <nombre_appartement>10</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                <rdim>1</rdim>
            </donnee_entree>
            <donnee_intermediaire>
                <conso_ch>10000</conso_ch>
                <conso_ch_depensier>12000</conso_ch_depensier>
            </donnee_intermediaire>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_entree>
                        <enum_type_energie_id>8</enum_type_energie_id>
                    </donnee_entree>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
    <installation_ecs_collection/>
    <sortie>
        <ef_conso>
            <conso_ch>10000</conso_ch>
            <conso_eclairage>0</conso_eclairage>
            <conso_fr>0</conso_fr>
            <conso_fr_depensier>0</conso_fr_depensier>
        </ef_conso>
        <ep_conso><classe_bilan_dpe>D</classe_bilan_dpe></ep_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc);

        (new EmissionGesCalculator())->calculate($node, $ctx);

        $emGes = $doc->getElementsByTagName('emission_ges')->item(0);
        $gesCh = (float)$emGes->getElementsByTagName('emission_ges_ch')->item(0)->textContent;

        $this->assertEqualsWithDelta(10000 * 0.385, $gesCh, 1.0,
            'Sans identifiant → fallback 0.385 (autres réseaux de chaleur)');
    }

    public function testAppliesToLogement(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement><meteo/></logement>');

        $calc = new EmissionGesCalculator();
        $this->assertTrue($calc->appliesTo($doc->getElementsByTagName('logement')->item(0)));
        $this->assertFalse($calc->appliesTo($doc->getElementsByTagName('meteo')->item(0)));
    }
}
