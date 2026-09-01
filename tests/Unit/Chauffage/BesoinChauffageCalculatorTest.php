<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage;

use CalculDpePHP\Chauffage\BesoinChauffageCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour BesoinChauffageCalculator (§9.1.1 p.57-59).
 */
final class BesoinChauffageCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 0.5; // kWh tolerance

    private function makeContext(
        DOMDocument $doc,
        ?string $zoneId = '1',
        ?string $altId = '1',
        array $extra = [],
    ): CalculationContext {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: $zoneId,
            classeAltitude: $altId,
        );
        foreach ($extra as $k => $v) {
            $ctx->set($k, $v);
        }
        return $ctx;
    }

    private function buildLogement(): DOMDocument
    {
        $xml = '<?xml version="1.0"?><logement></logement>';
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc;
    }

    /**
     * Sans fraction (F=0), besoin_ch = GV × Σ(DH19) / 1000.
     */
    public function testBesoinWithZeroFraction(): void
    {
        $doc  = $this->buildLogement();
        $node = $doc->getElementsByTagName('logement')->item(0);

        // Zone 1 alt 1: Σ(DH19j) = 60585.1
        $gv = 1000.0;
        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'          => $gv,
            'enveloppe.dp_pont_thermique'  => 0.0,
            'ventilation.hvent'            => 0.0,
            'ventilation.hperm'            => 0.0,
            'apport.fraction_ch'           => 0.0,
            'apport.fraction_ch_depensier' => 0.0,
        ]);

        (new BesoinChauffageCalculator())->calculate($node, $ctx);

        $bch = (float)$doc->getElementsByTagName('besoin_ch')->item(0)->textContent;
        // GV × Σ(DH19) / 1000 = 1000 × 60585.1 / 1000 = 60585.1 kWh
        $this->assertEqualsWithDelta(60585.1, $bch, self::TOL);
    }

    /**
     * Avec F=1 (tous les besoins couverts par apports gratuits), besoin_ch = 0.
     */
    public function testBesoinWithFullFraction(): void
    {
        $doc  = $this->buildLogement();
        $node = $doc->getElementsByTagName('logement')->item(0);

        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'          => 500.0,
            'enveloppe.dp_pont_thermique'  => 100.0,
            'ventilation.hvent'            => 200.0,
            'ventilation.hperm'            => 50.0,
            'apport.fraction_ch'           => 1.0,
            'apport.fraction_ch_depensier' => 1.0,
        ]);

        (new BesoinChauffageCalculator())->calculate($node, $ctx);

        $bch = (float)$doc->getElementsByTagName('besoin_ch')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $bch, self::TOL);
    }

    /**
     * Besoin ne peut pas être négatif (max(0, ...) appliqué).
     */
    public function testBesoinNotNegative(): void
    {
        $doc  = $this->buildLogement();
        $node = $doc->getElementsByTagName('logement')->item(0);

        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'          => 100.0,
            'enveloppe.dp_pont_thermique'  => 0.0,
            'ventilation.hvent'            => 0.0,
            'ventilation.hperm'            => 0.0,
            'apport.fraction_ch'           => 0.5,
            'apport.fraction_ch_depensier' => 0.5,
            'ecs.pertes_distribution_recup' => 1e9, // grande valeur → besoin = 0, pas négatif
        ]);

        (new BesoinChauffageCalculator())->calculate($node, $ctx);

        $bch = (float)$doc->getElementsByTagName('besoin_ch')->item(0)->textContent;
        $this->assertGreaterThanOrEqual(0.0, $bch);
    }

    /**
     * Pertes récupérées ECS réduisent le besoin.
     */
    public function testPertesDegradentBesoin(): void
    {
        $doc1  = $this->buildLogement();
        $node1 = $doc1->getElementsByTagName('logement')->item(0);
        $ctx1  = $this->makeContext($doc1, '1', '1', [
            'enveloppe.dp_parois'          => 995.0,
            'enveloppe.dp_pont_thermique'  => 0.0,
            'ventilation.hvent'            => 0.0,
            'ventilation.hperm'            => 0.0,
            'apport.fraction_ch'           => 0.616,
            'apport.fraction_ch_depensier' => 0.569,
        ]);
        (new BesoinChauffageCalculator())->calculate($node1, $ctx1);
        $bch1 = (float)$doc1->getElementsByTagName('besoin_ch')->item(0)->textContent;

        $doc2  = $this->buildLogement();
        $node2 = $doc2->getElementsByTagName('logement')->item(0);
        $ctx2  = $this->makeContext($doc2, '1', '1', [
            'enveloppe.dp_parois'           => 995.0,
            'enveloppe.dp_pont_thermique'   => 0.0,
            'ventilation.hvent'             => 0.0,
            'ventilation.hperm'             => 0.0,
            'apport.fraction_ch'            => 0.616,
            'apport.fraction_ch_depensier'  => 0.569,
            'ecs.pertes_distribution_recup' => 1500.0,
        ]);
        (new BesoinChauffageCalculator())->calculate($node2, $ctx2);
        $bch2 = (float)$doc2->getElementsByTagName('besoin_ch')->item(0)->textContent;

        $this->assertLessThan($bch1, $bch2);
        $this->assertEqualsWithDelta($bch1 - 1500.0, $bch2, self::TOL);
    }

    /**
     * Besoin dépensier > besoin conventionnel (DH21 > DH19, fraction21 < fraction19).
     */
    public function testDepensierGreaterThanConventionnel(): void
    {
        $doc  = $this->buildLogement();
        $node = $doc->getElementsByTagName('logement')->item(0);

        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'          => 500.0,
            'enveloppe.dp_pont_thermique'  => 100.0,
            'ventilation.hvent'            => 300.0,
            'ventilation.hperm'            => 30.0,
            'apport.fraction_ch'           => 0.616,
            'apport.fraction_ch_depensier' => 0.569,
        ]);

        (new BesoinChauffageCalculator())->calculate($node, $ctx);

        $bch    = (float)$doc->getElementsByTagName('besoin_ch')->item(0)->textContent;
        $bchDep = (float)$doc->getElementsByTagName('besoin_ch_depensier')->item(0)->textContent;

        $this->assertGreaterThan($bch, $bchDep);
    }

    /**
     * Résultat stocké dans le contexte pour les calculators §9.x.
     */
    public function testResultStoredInContext(): void
    {
        $doc  = $this->buildLogement();
        $node = $doc->getElementsByTagName('logement')->item(0);

        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'          => 500.0,
            'enveloppe.dp_pont_thermique'  => 0.0,
            'ventilation.hvent'            => 0.0,
            'ventilation.hperm'            => 0.0,
            'apport.fraction_ch'           => 0.5,
            'apport.fraction_ch_depensier' => 0.5,
        ]);

        (new BesoinChauffageCalculator())->calculate($node, $ctx);

        $bch    = (float)$doc->getElementsByTagName('besoin_ch')->item(0)->textContent;
        $bchCtx = (float)$ctx->get('chauffage.besoin_ch', 0.0);
        $this->assertEqualsWithDelta($bch, $bchCtx, self::TOL);
    }

    /**
     * GV inclut tous les composants (parois + pont_thermique + hvent + hperm).
     */
    public function testGvIncludesAllComponents(): void
    {
        $doc1 = $this->buildLogement();
        $node1 = $doc1->getElementsByTagName('logement')->item(0);
        $ctx1 = $this->makeContext($doc1, '1', '1', [
            'enveloppe.dp_parois'         => 800.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'ventilation.hvent'           => 0.0,
            'ventilation.hperm'           => 0.0,
            'apport.fraction_ch'          => 0.0,
            'apport.fraction_ch_depensier' => 0.0,
        ]);
        (new BesoinChauffageCalculator())->calculate($node1, $ctx1);
        $bch800 = (float)$doc1->getElementsByTagName('besoin_ch')->item(0)->textContent;

        $doc2 = $this->buildLogement();
        $node2 = $doc2->getElementsByTagName('logement')->item(0);
        $ctx2 = $this->makeContext($doc2, '1', '1', [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 100.0,
            'ventilation.hvent'           => 150.0,
            'ventilation.hperm'           => 50.0,
            'apport.fraction_ch'          => 0.0,
            'apport.fraction_ch_depensier' => 0.0,
        ]);
        (new BesoinChauffageCalculator())->calculate($node2, $ctx2);
        $bch_same = (float)$doc2->getElementsByTagName('besoin_ch')->item(0)->textContent;

        $this->assertEqualsWithDelta($bch800, $bch_same, self::TOL);
    }

    /**
     * Sans données climatiques (zone=null) → besoin = 0.
     */
    public function testNoClimate_BesoinZero(): void
    {
        $doc  = $this->buildLogement();
        $node = $doc->getElementsByTagName('logement')->item(0);

        $ctx = $this->makeContext($doc, null, null, [
            'enveloppe.dp_parois'          => 500.0,
            'enveloppe.dp_pont_thermique'  => 0.0,
            'ventilation.hvent'            => 0.0,
            'ventilation.hperm'            => 0.0,
            'apport.fraction_ch'           => 0.5,
            'apport.fraction_ch_depensier' => 0.5,
        ]);

        (new BesoinChauffageCalculator())->calculate($node, $ctx);

        $bch = (float)$doc->getElementsByTagName('besoin_ch')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $bch, self::TOL);
    }

    public function testRecoveredGeneratorLossesUseSampleMultiplicity(): void
    {
        $xml = <<<'XML'
<logement><caracteristique_generale><nombre_appartement>21</nombre_appartement></caracteristique_generale>
<installation_chauffage_collection><installation_chauffage><donnee_entree>
<enum_type_installation_id>1</enum_type_installation_id><enum_methode_calcul_conso_id>METHOD</enum_methode_calcul_conso_id>
<nombre_logement_echantillon>1</nombre_logement_echantillon><ratio_virtualisation>1</ratio_virtualisation><rdim>1</rdim>
</donnee_entree><generateur_chauffage_collection><generateur_chauffage><donnee_entree>
<position_volume_chauffe>1</position_volume_chauffe><presence_ventouse>1</presence_ventouse><enum_usage_generateur_id>3</enum_usage_generateur_id>
</donnee_entree><donnee_intermediaire><pn>24000</pn><qp0>240</qp0></donnee_intermediaire>
</generateur_chauffage></generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection></logement>
XML;
        $values = [];
        foreach ([1, 4] as $method) {
            $doc = new DOMDocument();
            $doc->loadXML(str_replace('METHOD', (string)$method, $xml));
            $ctx = $this->makeContext($doc, '1', '1', [
                'enveloppe.dp_parois' => 1500.0,
                'apport.fraction_ch' => 0.4,
                'apport.fraction_ch_depensier' => 0.35,
            ]);
            (new BesoinChauffageCalculator())->calculate($doc->documentElement, $ctx);
            $values[$method] = (float)$ctx->get('ch.pertes_generateur_recup', 0.0);
        }

        $this->assertEqualsWithDelta(21.0 * $values[1], $values[4], 1e-9);
    }

    public function testCollectiveStorageLossInsideHeatedVolumeIsRecovered(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<logement><installation_ecs_collection><installation_ecs><donnee_entree>
<enum_type_installation_id>2</enum_type_installation_id><rdim>1</rdim></donnee_entree>
<generateur_ecs_collection><generateur_ecs><donnee_entree>
<position_volume_chauffe>1</position_volume_chauffe><position_volume_chauffe_stockage>1</position_volume_chauffe_stockage>
</donnee_entree><donnee_intermediaire><Qgw>1064620.987191</Qgw></donnee_intermediaire>
</generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection></logement>
XML);
        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois' => 1000.0,
            'apport.fraction_ch' => 0.4,
            'apport.fraction_ch_depensier' => 0.35,
        ]);

        (new BesoinChauffageCalculator())->calculate($doc->documentElement, $ctx);

        $this->assertGreaterThan(0.0, (float)$ctx->get('ecs.pertes_stockage_recup', 0.0));
    }
}
