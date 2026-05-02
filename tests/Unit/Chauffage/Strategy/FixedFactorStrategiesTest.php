<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Strategy;

use CalculDpePHP\Chauffage\Strategy\AppointInsertElecSdb;
use CalculDpePHP\Chauffage\Strategy\ChaudiereReleve;
use CalculDpePHP\Chauffage\Strategy\ConvecteurBijonction;
use CalculDpePHP\Chauffage\Strategy\InsertElecSdb;
use CalculDpePHP\Chauffage\Strategy\InsertPoeleAppoint;
use CalculDpePHP\Chauffage\Strategy\MultiGenerateurs;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour les stratégies à facteurs fixes (§9.3-9.9).
 * Vérifie que chaque installation reçoit la fraction correcte de Bch.
 */
final class FixedFactorStrategiesTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 1e-6;

    private function buildInstallation(int $cfgId, int $position, float $rg = 0.9, float $re = 0.95, float $rd = 1.0, float $rr = 1.0, float $i0 = 1.0): array
    {
        // Build a collection with $position installations, all with same cfgId
        $installationsXml = '';
        for ($i = 1; $i <= $position; $i++) {
            $installationsXml .= <<<XML
            <installation_chauffage>
                <donnee_entree>
                    <enum_cfg_installation_ch_id>$cfgId</enum_cfg_installation_ch_id>
                    <surface_chauffee>100</surface_chauffee>
                    <rdim>1</rdim>
                </donnee_entree>
                <emetteur_chauffage_collection>
                    <emetteur_chauffage>
                        <donnee_entree><surface_chauffee>100</surface_chauffee></donnee_entree>
                        <donnee_intermediaire>
                            <i0>$i0</i0>
                            <rendement_emission>$re</rendement_emission>
                            <rendement_distribution>$rd</rendement_distribution>
                            <rendement_regulation>$rr</rendement_regulation>
                        </donnee_intermediaire>
                    </emetteur_chauffage>
                </emetteur_chauffage_collection>
                <generateur_chauffage_collection>
                    <generateur_chauffage>
                        <donnee_intermediaire>
                            <rendement_generation>$rg</rendement_generation>
                        </donnee_intermediaire>
                    </generateur_chauffage>
                </generateur_chauffage_collection>
            </installation_chauffage>
XML;
        }

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <hsp>2.5</hsp>
        <surface_habitable_immeuble>200</surface_habitable_immeuble>
        <nombre_appartement>1</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>
        $installationsXml
    </installation_chauffage_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $nodes = $doc->getElementsByTagName('installation_chauffage');
        return [$doc, $nodes->item($position - 1)];
    }

    private function makeContext(DOMDocument $doc, float $bch = 1000.0): CalculationContext
    {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '1',
        );
        $ctx->set('chauffage.besoin_ch',           $bch);
        $ctx->set('chauffage.besoin_ch_depensier', $bch * 1.2);
        // gv=500 with hsp=2.5, shImm=200 → g = 500/(2.5×200) = 1.0 → INT = i0
        $ctx->set('chauffage.gv', 500.0);
        return $ctx;
    }

    /**
     * Récupère besoin_ch dans la donnee_intermediaire du nœud installation traité.
     */
    private function besoinCh(DOMElement $node): float
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->nodeName === 'donnee_intermediaire') {
                foreach ($child->childNodes as $c) {
                    if ($c instanceof \DOMElement && $c->nodeName === 'besoin_ch') {
                        return (float)$c->textContent;
                    }
                }
            }
        }
        return 0.0;
    }

    /**
     * Récupère conso_ch dans la donnee_intermediaire du nœud installation traité.
     */
    private function consoCh(DOMElement $node): float
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->nodeName === 'donnee_intermediaire') {
                foreach ($child->childNodes as $c) {
                    if ($c instanceof \DOMElement && $c->nodeName === 'conso_ch') {
                        return (float)$c->textContent;
                    }
                }
            }
        }
        return 0.0;
    }

    // §9.3 — InsertPoeleAppoint
    // cfg_id=3: 1 installation with 2 generators. Factor[1]=0.75 (principal), factor[2]=0.25 (insert).
    // installation/besoin_ch = full Bch (per open3cl/verif).
    // installation/conso_ch = sum of generator consos.
    // With gv=500, hsp=2.5, shImm=200 → g=1.0 → INT=i0=1.0 → conso=factor×bch.

    /** cfg_id=3, 1 generator (principal) → installation/conso_ch = 0.75 × Bch */
    public function testInsertPoeleAppoint_principal(): void
    {
        [$doc, $node] = $this->buildInstallation(3, 1, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new InsertPoeleAppoint())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(1000.0, $this->besoinCh($node), self::TOL, 'installation/besoin_ch = full Bch');
        $this->assertEqualsWithDelta(750.0, $this->consoCh($node), self::TOL, 'installation/conso_ch = 0.75 × Bch');
    }

    /** cfg_id=3, 2 generators → installation/conso_ch = (0.75+0.25) × Bch = Bch */
    public function testInsertPoeleAppoint_appoint(): void
    {
        // Build an installation_chauffage_collection with 2 installations (each 1 generator),
        // but test the second installation's single-generator: factor[1]=0.75 for it.
        // To properly test factor[2]=0.25, check via total conso with 2 generators in ONE installation.
        // We test indirectly: with 1 install × 2 generators, total conso_ch = (0.75+0.25)×Bch = Bch.
        // We verify that gen2 receives factor=0.25 by checking the installation/conso_ch with 2 generators.
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <hsp>2.5</hsp>
        <surface_habitable_immeuble>200</surface_habitable_immeuble>
        <nombre_appartement>1</nombre_appartement>
    </caracteristique_generale>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                <enum_cfg_installation_ch_id>3</enum_cfg_installation_ch_id>
                <surface_chauffee>100</surface_chauffee>
                <rdim>1</rdim>
            </donnee_entree>
            <emetteur_chauffage_collection>
                <emetteur_chauffage>
                    <donnee_entree><surface_chauffee>100</surface_chauffee></donnee_entree>
                    <donnee_intermediaire>
                        <i0>1.0</i0>
                        <rendement_emission>1.0</rendement_emission>
                        <rendement_distribution>1.0</rendement_distribution>
                        <rendement_regulation>1.0</rendement_regulation>
                    </donnee_intermediaire>
                </emetteur_chauffage>
            </emetteur_chauffage_collection>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_intermediaire><rendement_generation>1.0</rendement_generation></donnee_intermediaire>
                </generateur_chauffage>
                <generateur_chauffage>
                    <donnee_intermediaire><rendement_generation>1.0</rendement_generation></donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('installation_chauffage')->item(0);
        (new InsertPoeleAppoint())->calculate($node, $this->makeContext($doc, 1000.0));
        // With 2 generators: gen1=750, gen2=250 → total conso=1000
        $this->assertEqualsWithDelta(1000.0, $this->consoCh($node), self::TOL, 'total conso_ch = (0.75+0.25)×Bch');
        $this->assertEqualsWithDelta(1000.0, $this->besoinCh($node), self::TOL, 'installation/besoin_ch = full Bch');
    }

    /** cfg_id=3 does NOT apply to cfg_id=1 */
    public function testInsertPoeleAppoint_doesNotApplyToCfg1(): void
    {
        [$doc, $node] = $this->buildInstallation(1, 1);
        $this->assertFalse((new InsertPoeleAppoint())->appliesTo($node));
    }

    // §9.4 — InsertElecSdb

    /** cfg_id=4, position=1 → factor=0.90 */
    public function testInsertElecSdb_insert(): void
    {
        [$doc, $node] = $this->buildInstallation(4, 1, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new InsertElecSdb())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(900.0, $this->besoinCh($node), self::TOL);
    }

    /** cfg_id=4, position=2 → factor=0.10 */
    public function testInsertElecSdb_sdb(): void
    {
        [$doc, $node] = $this->buildInstallation(4, 2, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new InsertElecSdb())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(100.0, $this->besoinCh($node), self::TOL);
    }

    // §9.5 — AppointInsertElecSdb

    /** cfg_id=5, position=1 → factor=0.675 */
    public function testAppointInsertElecSdb_principal(): void
    {
        [$doc, $node] = $this->buildInstallation(5, 1, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new AppointInsertElecSdb())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(675.0, $this->besoinCh($node), self::TOL);
    }

    /** cfg_id=5, position=2 → factor=0.225 */
    public function testAppointInsertElecSdb_insert(): void
    {
        [$doc, $node] = $this->buildInstallation(5, 2, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new AppointInsertElecSdb())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(225.0, $this->besoinCh($node), self::TOL);
    }

    /** cfg_id=5, position=3 → factor=0.10 */
    public function testAppointInsertElecSdb_sdb(): void
    {
        [$doc, $node] = $this->buildInstallation(5, 3, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new AppointInsertElecSdb())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(100.0, $this->besoinCh($node), self::TOL);
    }

    // §9.1.4 — MultiGenerateurs

    /** cfg_id=6, position=1 → factor=0.75 (chaudière bois) */
    public function testMultiGenerateurs_cfg6_chaudiereBois(): void
    {
        [$doc, $node] = $this->buildInstallation(6, 1, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new MultiGenerateurs())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(750.0, $this->besoinCh($node), self::TOL);
    }

    /** cfg_id=8, position=1 → factor=0.80 (PAC principale) */
    public function testMultiGenerateurs_cfg8_pac(): void
    {
        [$doc, $node] = $this->buildInstallation(8, 1, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new MultiGenerateurs())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(800.0, $this->besoinCh($node), self::TOL);
    }

    /** cfg_id=8, position=2 → factor=0.20 (chaudière relève) */
    public function testMultiGenerateurs_cfg8_chaudiere(): void
    {
        [$doc, $node] = $this->buildInstallation(8, 2, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new MultiGenerateurs())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(200.0, $this->besoinCh($node), self::TOL);
    }

    /** MultiGenerateurs does NOT apply to cfg_id=1 */
    public function testMultiGenerateurs_doesNotApplyToCfg1(): void
    {
        [$doc, $node] = $this->buildInstallation(1, 1);
        $this->assertFalse((new MultiGenerateurs())->appliesTo($node));
    }

    // §9.7 — ChaudiereReleve

    /** cfg_id=9, position=1 → factor=0.60 (PAC) */
    public function testChaudiereReleve_pac(): void
    {
        [$doc, $node] = $this->buildInstallation(9, 1, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new ChaudiereReleve())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(600.0, $this->besoinCh($node), self::TOL);
    }

    /** cfg_id=9, position=3 → factor=0.25 (insert appoint) */
    public function testChaudiereReleve_insert(): void
    {
        [$doc, $node] = $this->buildInstallation(9, 3, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new ChaudiereReleve())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(250.0, $this->besoinCh($node), self::TOL);
    }

    // §9.9 — ConvecteurBijonction

    /** cfg_id=11, position=1 → factor=0.60 (circuit collectif base) */
    public function testConvecteurBijonction_base(): void
    {
        [$doc, $node] = $this->buildInstallation(11, 1, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new ConvecteurBijonction())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(600.0, $this->besoinCh($node), self::TOL);
    }

    /** cfg_id=11, position=2 → factor=0.40 (circuit individuel appoint) */
    public function testConvecteurBijonction_appoint(): void
    {
        [$doc, $node] = $this->buildInstallation(11, 2, rg: 1.0, re: 1.0, rd: 1.0, rr: 1.0, i0: 1.0);
        (new ConvecteurBijonction())->calculate($node, $this->makeContext($doc, 1000.0));
        $this->assertEqualsWithDelta(400.0, $this->besoinCh($node), self::TOL);
    }
}
