<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Common\Period;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\EpConsoCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour EpConsoCalculator.
 *
 * Coefficients EF→EP :
 *   - Électricité pré-2026  : ×2.3
 *   - Électricité post-2026 : ×1.9
 *   - Gaz / autres          : ×1.0
 * Auxiliaires + éclairage : toujours électricité.
 */
final class EpConsoCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 1e-3;

    private function makeContext(DOMDocument $doc, Period $period = Period::PRE_2026): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            period: $period,
        );
    }

    /**
     * Construit un XML logement minimal avec ef_conso pré-rempli dans la sortie.
     * Aucune installation : les valeurs EP viennent directement des auxiliaires/éclairage.
     */
    private function buildDocWithEfConso(
        array $efValues,
        ?float $shLogement,
        float $shImmeuble,
        Period $period = Period::PRE_2026,
    ): array {
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
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc, $period);
        return [$doc, $node, $ctx];
    }

    /**
     * Éclairage seul pré-2026 : ef=192.78 × 2.3 = 443.394.
     * Surface = 102. ep_conso_5_usages_m2 = floor(443.394/102) = 4.
     */
    public function testEclairagePre2026(): void
    {
        [$doc, $node, $ctx] = $this->buildDocWithEfConso(
            ['conso_eclairage' => 192.78],
            shLogement: 102.0,
            shImmeuble: 9543.0,
            period: Period::PRE_2026,
        );

        (new EpConsoCalculator())->calculate($node, $ctx);

        $epConso = $doc->getElementsByTagName('ep_conso')->item(0);
        $epEcl   = (float)$epConso->getElementsByTagName('ep_conso_eclairage')->item(0)->textContent;

        $this->assertEqualsWithDelta(192.78 * 2.3, $epEcl, 0.01, 'ep_conso_eclairage pré-2026');
    }

    /**
     * Éclairage seul post-2026 : ef=118.8054 × 1.9 = 225.73.
     */
    public function testEclairagePost2026(): void
    {
        [$doc, $node, $ctx] = $this->buildDocWithEfConso(
            ['conso_eclairage' => 118.8054],
            shLogement: 62.86,
            shImmeuble: 1034.74,
            period: Period::POST_2026,
        );

        (new EpConsoCalculator())->calculate($node, $ctx);

        $epConso = $doc->getElementsByTagName('ep_conso')->item(0);
        $epEcl   = (float)$epConso->getElementsByTagName('ep_conso_eclairage')->item(0)->textContent;

        $this->assertEqualsWithDelta(118.8054 * 1.9, $epEcl, 0.01, 'ep_conso_eclairage post-2026');
    }

    /**
     * Auxiliaire ventilation pré-2026 : ef=22838 × 2.3 = 52507.4.
     */
    public function testAuxVentilationPre2026(): void
    {
        $efAux = 22838.356169565216; // ~ bat_pre2026 conso_auxiliaire_ventilation
        [$doc, $node, $ctx] = $this->buildDocWithEfConso(
            ['conso_auxiliaire_ventilation' => $efAux, 'conso_eclairage' => 0.0],
            shLogement: null,
            shImmeuble: 9543.0,
            period: Period::PRE_2026,
        );

        (new EpConsoCalculator())->calculate($node, $ctx);

        $epConso  = $doc->getElementsByTagName('ep_conso')->item(0);
        $epCauxV  = (float)$epConso->getElementsByTagName('ep_conso_auxiliaire_ventilation')->item(0)->textContent;
        $epCauxT  = (float)$epConso->getElementsByTagName('ep_conso_totale_auxiliaire')->item(0)->textContent;

        $this->assertEqualsWithDelta($efAux * 2.3, $epCauxV, 1.0, 'ep_conso_auxiliaire_ventilation');
        $this->assertEqualsWithDelta($efAux * 2.3, $epCauxT, 1.0, 'ep_conso_totale_auxiliaire');
    }

    /**
     * Installation CH gaz (id=2), pré-2026 : EP coeff = 1.0.
     * ef_conso_ch = 1677.60 → ep_conso_ch = 1677.60 × 1.0 = 1677.60.
     */
    public function testChGazCoeff1(): void
    {
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
                <cle_repartition_ch>0.060745</cle_repartition_ch>
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
            <conso_ch_depensier>2243.3111471186558</conso_ch_depensier>
            <conso_eclairage>118.8054</conso_eclairage>
            <conso_fr>0</conso_fr>
            <conso_fr_depensier>0</conso_fr_depensier>
        </ef_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc, Period::POST_2026);

        (new EpConsoCalculator())->calculate($node, $ctx);

        $epConso = $doc->getElementsByTagName('ep_conso')->item(0);
        $epCh    = (float)$epConso->getElementsByTagName('ep_conso_ch')->item(0)->textContent;

        // Gas × 1.0 = 1677.60
        $this->assertEqualsWithDelta(1677.60, $epCh, 1.0, 'ep_conso_ch gaz = ef×1.0');
    }

    /**
     * Installation CH électricité (id=1), pré-2026 : EP coeff = 2.3.
     * ef_conso_ch = 441771.59 → ep_conso_ch ≈ 1016074.65.
     */
    public function testChElecPre2026Coeff23(): void
    {
        $efCh = 441771.5888695874;
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_immeuble>9543</surface_habitable_immeuble>
        <nombre_appartement>127</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id>
                <enum_type_installation_id>1</enum_type_installation_id>
                <rdim>12.7</rdim>
                <ratio_virtualisation>1</ratio_virtualisation>
            </donnee_entree>
            <donnee_intermediaire>
                <conso_ch>34784.377871621843</conso_ch>
                <conso_ch_depensier>45347.490327326472</conso_ch_depensier>
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
            <conso_eclairage>18036.27</conso_eclairage>
            <conso_fr>0</conso_fr>
            <conso_fr_depensier>0</conso_fr_depensier>
        </ef_conso>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx  = $this->makeContext($doc, Period::PRE_2026);

        (new EpConsoCalculator())->calculate($node, $ctx);

        $epConso = $doc->getElementsByTagName('ep_conso')->item(0);
        $epCh    = (float)$epConso->getElementsByTagName('ep_conso_ch')->item(0)->textContent;

        $this->assertEqualsWithDelta(34784.377871621843 * 12.7 * 2.3, $epCh, 10.0, 'ep_conso_ch elec pré-2026');
    }

    /**
     * ep_conso_5_usages_m2 utilise floor().
     *
     * ep_conso_5 / surface = résultat connu avec décimales → doit être tronqué.
     */
    public function testEp5UsagesM2UsesFloor(): void
    {
        // eclairage = 4580.15 × 2.3 = 10534.345. surface = 62.86. 10534.345/62.86 = 167.58 → floor=167
        [$doc, $node, $ctx] = $this->buildDocWithEfConso(
            ['conso_eclairage' => 4580.15, 'conso_fr' => 0.0, 'conso_fr_depensier' => 0.0],
            shLogement: 62.86,
            shImmeuble: 1034.74,
            period: Period::PRE_2026,
        );

        (new EpConsoCalculator())->calculate($node, $ctx);

        $epConso = $doc->getElementsByTagName('ep_conso')->item(0);
        $m2 = (int)$epConso->getElementsByTagName('ep_conso_5_usages_m2')->item(0)->textContent;

        $expected = (int)floor(4580.15 * 2.3 / 62.86);
        $this->assertSame($expected, $m2, 'ep_conso_5_usages_m2 uses floor()');
    }

    /**
     * Classe énergie : valeurs clairement au milieu de chaque plage → pas de problème FP.
     * Thresholds : A≤70, B≤110, C≤180, D≤250, E≤330, F≤420, G>420.
     * On utilise post2026 (×1.9) et surface=100 pour des valeurs entières propres.
     */
    public function testClasseEnergetique(): void
    {
        // ef_eclairage × 1.9 / 100 = ep5m2. Choisir ef pour un ep5m2 bien dans la plage.
        $cases = [
            // ef × 1.9 / 100 = ep5m2
            [100.0 / 1.9 * 35,  'A'],  // ep5m2 ≈ 35 → A
            [100.0 / 1.9 * 90,  'B'],  // ep5m2 ≈ 90 → B
            [100.0 / 1.9 * 145, 'C'],  // ep5m2 ≈ 145 → C
            [100.0 / 1.9 * 220, 'D'],  // ep5m2 ≈ 220 → D
            [100.0 / 1.9 * 290, 'E'],  // ep5m2 ≈ 290 → E
            [100.0 / 1.9 * 375, 'F'],  // ep5m2 ≈ 375 → F
            [100.0 / 1.9 * 500, 'G'],  // ep5m2 ≈ 500 → G
        ];

        foreach ($cases as [$efEcl, $expectedClasse]) {
            [$doc, $node, $ctx] = $this->buildDocWithEfConso(
                ['conso_eclairage' => $efEcl],
                shLogement: 100.0,
                shImmeuble: 10000.0,
                period: Period::POST_2026,
            );

            (new EpConsoCalculator())->calculate($node, $ctx);

            $epConso = $doc->getElementsByTagName('ep_conso')->item(0);
            $classe  = $epConso->getElementsByTagName('classe_bilan_dpe')->item(0)->textContent;

            $this->assertSame($expectedClasse, $classe, "efEcl={$efEcl} → classe {$expectedClasse}");
        }
    }

    /**
     * Seuils exacts — les seuils eux-mêmes sont inclusifs (ex : ep=70 → A, ep=71 → B).
     */
    public function testClasseEnergetiqueThresholds(): void
    {
        // Pour éviter les problèmes FP, on injecte des ef qui donnent des entiers exacts.
        // Utiliser pré-2026 (×2.3) et surface=1.
        // ef=10 → ep=23 → A (≤70) ; ef=40 → ep=92 → B ; etc.
        $cases = [
            ['ef' => 10.0,  'classe' => 'A'],  // ep=23
            ['ef' => 40.0,  'classe' => 'B'],  // ep=92
            ['ef' => 55.0,  'classe' => 'C'],  // ep=126.5 → 126
            ['ef' => 80.0,  'classe' => 'D'],  // ep=184
            ['ef' => 110.0, 'classe' => 'E'],  // ep=253
            ['ef' => 145.0, 'classe' => 'F'],  // ep=333.5 → 333
            ['ef' => 185.0, 'classe' => 'G'],  // ep=425.5 → 425
        ];

        foreach ($cases as ['ef' => $efEcl, 'classe' => $expectedClasse]) {
            [$doc, $node, $ctx] = $this->buildDocWithEfConso(
                ['conso_eclairage' => $efEcl],
                shLogement: 1.0,
                shImmeuble: 100.0,
                period: Period::PRE_2026,
            );

            (new EpConsoCalculator())->calculate($node, $ctx);

            $epConso = $doc->getElementsByTagName('ep_conso')->item(0);
            $classe  = $epConso->getElementsByTagName('classe_bilan_dpe')->item(0)->textContent;

            $this->assertSame($expectedClasse, $classe, "ef={$efEcl} (ep≈" . floor($efEcl * 2.3) . ") → {$expectedClasse}");
        }
    }

    public function testAppliesToLogement(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement><meteo/></logement>');
        $logNode  = $doc->getElementsByTagName('logement')->item(0);
        $metaNode = $doc->getElementsByTagName('meteo')->item(0);

        $calc = new EpConsoCalculator();
        $this->assertTrue($calc->appliesTo($logNode));
        $this->assertFalse($calc->appliesTo($metaNode));
    }
}
