<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Strategy;

use CalculDpe\Chauffage\Strategy\ChauffageSolaire;
use CalculDpe\Chauffage\Strategy\SolaireInsertPoele;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour les stratégies de chauffage solaire (§9.2 et §9.6).
 */
final class SolaireStrategiesTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 1e-6;

    private function buildInstallation(int $cfgId, int $position, float $fchSaisi = 0.0): array
    {
        $installationsXml = '';
        for ($i = 1; $i <= $position; $i++) {
            $fchNode = $fchSaisi > 0.0 ? "<fch_saisi>$fchSaisi</fch_saisi>" : '';
            $installationsXml .= <<<XML
            <installation_chauffage>
                <donnee_entree>
                    <enum_cfg_installation_ch_id>$cfgId</enum_cfg_installation_ch_id>
                    <surface_chauffee>100</surface_chauffee>
                    <rdim>1</rdim>
                    $fchNode
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
                        <donnee_intermediaire>
                            <rendement_generation>1.0</rendement_generation>
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

    private function makeContext(DOMDocument $doc, float $bch = 1000.0, string $zone = '1'): CalculationContext
    {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: $zone,
        );
        $ctx->set('chauffage.besoin_ch',           $bch);
        $ctx->set('chauffage.besoin_ch_depensier', $bch * 1.2);
        $ctx->set('chauffage.gv', 100.0);
        return $ctx;
    }

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

    // §9.2 — ChauffageSolaire (cfg_id=2)

    /** cfg_id=2 → appliesTo returns true */
    public function testChauffageSolaire_appliesTo(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1);
        $this->assertTrue((new ChauffageSolaire())->appliesTo($node));
    }

    /** cfg_id=1 → appliesTo returns false */
    public function testChauffageSolaire_doesNotApplyToCfg1(): void
    {
        [$doc, $node] = $this->buildInstallation(1, 1);
        $this->assertFalse((new ChauffageSolaire())->appliesTo($node));
    }

    /** fch_saisi=0.30, Bch=1000 → besoin = 1000 × (1 - 0.30) = 700 */
    public function testChauffageSolaire_fchSaisi(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1, fchSaisi: 0.30);
        (new ChauffageSolaire())->calculate($node, $this->makeContext($doc, 1000.0, '1'));
        $this->assertEqualsWithDelta(700.0, $this->besoinCh($node), self::TOL);
    }

    /** Zone H1a (id=1) → Fch=0.25, besoin = 1000 × (1 - 0.25) = 750 */
    public function testChauffageSolaire_zoneH1a(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1);
        (new ChauffageSolaire())->calculate($node, $this->makeContext($doc, 1000.0, '1'));
        $this->assertEqualsWithDelta(750.0, $this->besoinCh($node), self::TOL);
    }

    /** Zone H3 (id=8) → Fch=0.52, besoin = 1000 × (1 - 0.52) = 480 */
    public function testChauffageSolaire_zoneH3(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1);
        (new ChauffageSolaire())->calculate($node, $this->makeContext($doc, 1000.0, '8'));
        $this->assertEqualsWithDelta(480.0, $this->besoinCh($node), self::TOL);
    }

    /** fch_saisi takes precedence over zone lookup */
    public function testChauffageSolaire_fchSaisiOverridesZone(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1, fchSaisi: 0.10);
        (new ChauffageSolaire())->calculate($node, $this->makeContext($doc, 1000.0, '8'));
        // fch=0.10 (saisi), not 0.52 (zone H3)
        $this->assertEqualsWithDelta(900.0, $this->besoinCh($node), self::TOL);
    }

    // §9.6 — SolaireInsertPoele (cfg_id=7)

    /** cfg_id=7, position=1 → factor=0.75, fch=0.25 → besoin = 0.75 × 750 = 562.5 */
    public function testSolaireInsertPoele_principal(): void
    {
        [$doc, $node] = $this->buildInstallation(7, 1);
        (new SolaireInsertPoele())->calculate($node, $this->makeContext($doc, 1000.0, '1'));
        $this->assertEqualsWithDelta(562.5, $this->besoinCh($node), self::TOL);
    }

    /** cfg_id=7, position=2 → factor=0.25, fch=0.25 → besoin = 0.25 × 750 = 187.5 */
    public function testSolaireInsertPoele_insert(): void
    {
        [$doc, $node] = $this->buildInstallation(7, 2);
        (new SolaireInsertPoele())->calculate($node, $this->makeContext($doc, 1000.0, '1'));
        $this->assertEqualsWithDelta(187.5, $this->besoinCh($node), self::TOL);
    }

    /** cfg_id=7 → appliesTo returns true */
    public function testSolaireInsertPoele_appliesTo(): void
    {
        [$doc, $node] = $this->buildInstallation(7, 1);
        $this->assertTrue((new SolaireInsertPoele())->appliesTo($node));
    }

    /** cfg_id=1 → SolaireInsertPoele does NOT apply */
    public function testSolaireInsertPoele_doesNotApplyToCfg1(): void
    {
        [$doc, $node] = $this->buildInstallation(1, 1);
        $this->assertFalse((new SolaireInsertPoele())->appliesTo($node));
    }

    /** fch_saisi=0.40, position=1, Bch=1000 → besoin = 0.75 × 1000 × 0.60 = 450 */
    public function testSolaireInsertPoele_fchSaisi(): void
    {
        [$doc, $node] = $this->buildInstallation(7, 1, fchSaisi: 0.40);
        (new SolaireInsertPoele())->calculate($node, $this->makeContext($doc, 1000.0, '1'));
        $this->assertEqualsWithDelta(450.0, $this->besoinCh($node), self::TOL);
    }
}
