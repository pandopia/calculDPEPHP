<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Strategy;

use CalculDpePHP\Chauffage\Strategy\ChauffageSolaire;
use CalculDpePHP\Chauffage\Strategy\SolaireInsertPoele;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
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

    private function di(DOMElement $node, string $tag): float
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->nodeName === 'donnee_intermediaire') {
                foreach ($child->childNodes as $c) {
                    if ($c instanceof \DOMElement && $c->nodeName === $tag) {
                        return (float)$c->textContent;
                    }
                }
            }
        }
        return 0.0;
    }

    private function besoinCh(DOMElement $node): float
    {
        return $this->di($node, 'besoin_ch');
    }

    /**
     * INT × Ich du gabarit : i0 = 1, tous les rendements à 1, et
     * G = GV / (hsp × Sh) = 100 / (2,5 × 200) = 0,2, d'où
     * INT = 1 / (1 + 0,1 × (0,2 − 1)).
     */
    private const INT_ICH = 1.0 / 0.92;

    /** Le gabarit chauffe 100 m² d'un logement de 200 : §9.1 lui en donne la moitié. */
    private const BESOIN_INSTALLATION = 500.0;

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

    /**
     * §9.2 p.62 retranche la couverture solaire de la consommation, en amont
     * de l'émission : `besoin_ch` reste le besoin de l'installation.
     */
    public function testChauffageSolaire_besoinPublieEstBrutDuSolaire(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1, fchSaisi: 0.30);
        (new ChauffageSolaire())->calculate($node, $this->makeContext($doc, 1000.0, '1'));

        $this->assertEqualsWithDelta(self::BESOIN_INSTALLATION, $this->besoinCh($node), self::TOL);
        $this->assertEqualsWithDelta(
            self::BESOIN_INSTALLATION * 0.70 * self::INT_ICH,
            $this->di($node, 'conso_ch'),
            self::TOL,
        );
    }

    /** Zone H1a (id=1) → Fch = 0,25 : Cch = Bch × INT × (1 − Fch) × Ich. */
    public function testChauffageSolaire_zoneH1a(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1);
        (new ChauffageSolaire())->calculate($node, $this->makeContext($doc, 1000.0, '1'));

        $this->assertEqualsWithDelta(0.25, $this->di($node, 'fch'), self::TOL);
        $this->assertEqualsWithDelta(
            self::BESOIN_INSTALLATION * 0.75 * self::INT_ICH,
            $this->di($node, 'conso_ch'),
            self::TOL,
        );
    }

    /** Zone H3 (id=8) → Fch = 0,52. */
    public function testChauffageSolaire_zoneH3(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1);
        (new ChauffageSolaire())->calculate($node, $this->makeContext($doc, 1000.0, '8'));

        $this->assertEqualsWithDelta(0.52, $this->di($node, 'fch'), self::TOL);
        $this->assertEqualsWithDelta(
            self::BESOIN_INSTALLATION * 0.48 * self::INT_ICH,
            $this->di($node, 'conso_ch'),
            self::TOL,
        );
    }

    /** `fch_saisi` prime sur la table de zone de §18.4. */
    public function testChauffageSolaire_fchSaisiOverridesZone(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1, fchSaisi: 0.10);
        (new ChauffageSolaire())->calculate($node, $this->makeContext($doc, 1000.0, '8'));

        $this->assertEqualsWithDelta(0.10, $this->di($node, 'fch'), self::TOL);
    }

    /**
     * §9.6 p.65 : Prod = Bch × Fch × (0,75 INT1 Ich1 + 0,25 INT2 Ich2). Sur
     * l'installation unique de §9.2 la parenthèse se réduit à INT × Ich.
     */
    public function testChauffageSolaire_productionSolaire(): void
    {
        [$doc, $node] = $this->buildInstallation(2, 1);
        (new ChauffageSolaire())->calculate($node, $this->makeContext($doc, 1000.0, '1'));

        $this->assertEqualsWithDelta(
            self::BESOIN_INSTALLATION * 0.25 * self::INT_ICH,
            $this->di($node, 'production_ch_solaire'),
            self::TOL,
        );
    }

    // §9.6 — SolaireInsertPoele (cfg_id=7)

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

    /**
     * §9.6 p.65 répartit 0,75 et 0,25 entre le système principal et l'appoint
     * **d'une même installation**, pas entre deux installations : le besoin
     * publié reste entier, seule la consommation porte le facteur.
     */
    public function testSolaireInsertPoele_leFacteurPorteSurLaConsoPasLeBesoin(): void
    {
        [$doc, $node] = $this->buildInstallation(7, 1);
        (new SolaireInsertPoele())->calculate($node, $this->makeContext($doc, 1000.0, '1'));

        $this->assertEqualsWithDelta(self::BESOIN_INSTALLATION, $this->besoinCh($node), self::TOL);
        $this->assertEqualsWithDelta(
            0.75 * self::BESOIN_INSTALLATION * 0.75 * self::INT_ICH,
            $this->di($node, 'conso_ch'),
            self::TOL,
        );
    }

    /**
     * La deuxième installation d'un même logement n'hérite plus du facteur
     * d'appoint : elle porte son propre besoin, et son unique générateur est
     * le système principal.
     */
    public function testSolaireInsertPoele_secondeInstallationGardeSonBesoin(): void
    {
        [$doc, $node] = $this->buildInstallation(7, 2);
        (new SolaireInsertPoele())->calculate($node, $this->makeContext($doc, 1000.0, '1'));

        $this->assertEqualsWithDelta(self::BESOIN_INSTALLATION, $this->besoinCh($node), self::TOL);
    }

    /** §9.6 p.65 : Prod = Bch × Fch × Σ facteur_i × INT_i × Ich_i. */
    public function testSolaireInsertPoele_productionSolaire(): void
    {
        [$doc, $node] = $this->buildInstallation(7, 1, fchSaisi: 0.40);
        $ctx = $this->makeContext($doc, 1000.0, '1');
        (new SolaireInsertPoele())->calculate($node, $ctx);
        self::assertEqualsWithDelta($this->di($node, 'production_ch_solaire'), \CalculDpePHP\Common\ProductionSolaireThermique::total($ctx), self::TOL);

        $this->assertEqualsWithDelta(
            self::BESOIN_INSTALLATION * 0.40 * 0.75 * self::INT_ICH,
            $this->di($node, 'production_ch_solaire'),
            self::TOL,
        );
    }
}
