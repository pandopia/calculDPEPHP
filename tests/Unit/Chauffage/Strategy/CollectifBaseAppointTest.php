<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Strategy;

use CalculDpePHP\Chauffage\Strategy\CollectifBaseAppoint;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour CollectifBaseAppoint (§9.8 p.65-66).
 *
 * cfg_id=10 : collectif base (générateur collectif) + appoint (générateur individuel).
 *
 * Formule T = 14 - Pe × DH14 / Bch, puis répartition mensuelle par DHTj/DH14j.
 */
final class CollectifBaseAppointTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';
    private const TOL = 1e-2;

    private function buildXml(
        float $bch,
        float $gv,
        float $surface,
        float $sh,
        float $hsp,
        float $rdim,
        float $pnW,       // puissance nominale base (W)
        float $rgBase,
        float $reBase,
        float $rdBase,
        float $rrBase,
        float $i0Base,
        float $rgAppoint,
        float $reAppoint,
        float $rdAppoint,
        float $rrAppoint,
        float $i0Appoint,
        int   $zoneId = 1, // H1a
        int   $altId  = 1, // <400m
        int   $matAnciensId = 0,
        int   $inertieId = 1,
    ): array {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <meteo>
        <enum_zone_climatique_id>$zoneId</enum_zone_climatique_id>
        <enum_classe_altitude_id>$altId</enum_classe_altitude_id>
    </meteo>
    <caracteristique_generale>
        <hsp>$hsp</hsp>
        <surface_habitable_immeuble>$sh</surface_habitable_immeuble>
        <nombre_appartement>1</nombre_appartement>
        <batiment_materiaux_anciens>$matAnciensId</batiment_materiaux_anciens>
    </caracteristique_generale>
    <donnee_intermediaire>
        <enum_classe_inertie_id>$inertieId</enum_classe_inertie_id>
    </donnee_intermediaire>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree>
                <enum_cfg_installation_ch_id>10</enum_cfg_installation_ch_id>
                <surface_chauffee>$surface</surface_chauffee>
                <rdim>$rdim</rdim>
                <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id>
            </donnee_entree>
            <generateur_chauffage_collection>
                <generateur_chauffage>
                    <donnee_intermediaire>
                        <pn>$pnW</pn>
                        <rendement_generation>$rgBase</rendement_generation>
                    </donnee_intermediaire>
                </generateur_chauffage>
                <generateur_chauffage>
                    <donnee_intermediaire>
                        <pn>5000</pn>
                        <rendement_generation>$rgAppoint</rendement_generation>
                    </donnee_intermediaire>
                </generateur_chauffage>
            </generateur_chauffage_collection>
            <emetteur_chauffage_collection>
                <emetteur_chauffage>
                    <donnee_entree><surface_chauffee>$surface</surface_chauffee></donnee_entree>
                    <donnee_intermediaire>
                        <i0>$i0Base</i0>
                        <rendement_emission>$reBase</rendement_emission>
                        <rendement_distribution>$rdBase</rendement_distribution>
                        <rendement_regulation>$rrBase</rendement_regulation>
                    </donnee_intermediaire>
                </emetteur_chauffage>
                <emetteur_chauffage>
                    <donnee_entree><surface_chauffee>$surface</surface_chauffee></donnee_entree>
                    <donnee_intermediaire>
                        <i0>$i0Appoint</i0>
                        <rendement_emission>$reAppoint</rendement_emission>
                        <rendement_distribution>$rdAppoint</rendement_distribution>
                        <rendement_regulation>$rrAppoint</rendement_regulation>
                    </donnee_intermediaire>
                </emetteur_chauffage>
            </emetteur_chauffage_collection>
        </installation_chauffage>
    </installation_chauffage_collection>
</logement>
XML;

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $tables = new TableRepository(self::PROJECT_ROOT . '/resources/tables');
        $ctx    = new CalculationContext(
            document:       $doc,
            tables:         $tables,
            zoneClimatique: (string)$zoneId,
            classeAltitude: (string)$altId,
        );
        $ctx->set('chauffage.besoin_ch',          $bch);
        $ctx->set('chauffage.besoin_ch_depensier', $bch);
        $ctx->set('chauffage.gv',                 $gv);

        $xp      = new \DOMXPath($doc);
        $install = $xp->query('//installation_chauffage')->item(0);
        return [$doc, $ctx, $install];
    }

    public function testAppliesToCfg10(): void
    {
        $calc = new CollectifBaseAppoint();
        [, , $node] = $this->buildXml(
            bch: 10000, gv: 200, surface: 80, sh: 80, hsp: 2.5,
            rdim: 1, pnW: 5000, rgBase: 0.9, reBase: 0.9, rdBase: 0.85, rrBase: 0.9, i0Base: 1.0,
            rgAppoint: 0.9, reAppoint: 0.9, rdAppoint: 0.85, rrAppoint: 0.9, i0Appoint: 1.0,
        );
        $this->assertTrue($calc->appliesTo($node));
    }

    public function testNotAppliesToCfg1(): void
    {
        $calc = new CollectifBaseAppoint();
        $xml  = <<<XML
<?xml version="1.0"?>
<logement>
    <installation_chauffage_collection>
        <installation_chauffage>
            <donnee_entree><enum_cfg_installation_ch_id>1</enum_cfg_installation_ch_id></donnee_entree>
        </installation_chauffage>
    </installation_chauffage_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $node = $doc->getElementsByTagName('installation_chauffage')->item(0);
        $this->assertFalse($calc->appliesTo($node));
    }

    public function testConsoPositiveWhenBchPositive(): void
    {
        $calc = new CollectifBaseAppoint();
        [$doc, $ctx, $node] = $this->buildXml(
            bch: 15000, gv: 300, surface: 100, sh: 100, hsp: 2.5,
            rdim: 1, pnW: 5000, rgBase: 0.87, reBase: 0.85, rdBase: 0.85, rrBase: 0.9, i0Base: 1.0,
            rgAppoint: 0.90, reAppoint: 0.85, rdAppoint: 0.85, rrAppoint: 0.9, i0Appoint: 1.0,
        );
        $calc->calculate($node, $ctx);

        $xp    = new \DOMXPath($doc);
        $conso = $xp->evaluate('number(//installation_chauffage/donnee_intermediaire/conso_ch)');
        $this->assertGreaterThan(0.0, $conso, 'conso_ch should be positive');
        // Consumption must exceed raw besoin (losses) and be bounded from above
        $this->assertGreaterThan(15000, $conso, 'conso_ch must exceed raw besoin (distribution losses)');
        $this->assertLessThan(60000, $conso, 'conso_ch should not be unreasonably large');
    }

    public function testZeroBchProducesNoOutput(): void
    {
        $calc = new CollectifBaseAppoint();
        [$doc, $ctx, $node] = $this->buildXml(
            bch: 0, gv: 300, surface: 100, sh: 100, hsp: 2.5,
            rdim: 1, pnW: 5000, rgBase: 0.87, reBase: 0.85, rdBase: 0.85, rrBase: 0.9, i0Base: 1.0,
            rgAppoint: 0.9, reAppoint: 0.85, rdAppoint: 0.85, rrAppoint: 0.9, i0Appoint: 1.0,
        );
        $calc->calculate($node, $ctx);
        $xp    = new \DOMXPath($doc);
        $conso = $xp->evaluate('number(//installation_chauffage/donnee_intermediaire/conso_ch)');
        // When bch=0, no calculation is done (early return)
        $this->assertTrue(is_nan($conso) || $conso === 0.0, 'No conso when bch=0');
    }

    public function testBaseAndAppointGeneratorsGetConso(): void
    {
        $calc = new CollectifBaseAppoint();
        [$doc, $ctx, $node] = $this->buildXml(
            bch: 12000, gv: 250, surface: 80, sh: 80, hsp: 2.5,
            rdim: 1, pnW: 4000, rgBase: 0.87, reBase: 0.85, rdBase: 0.85, rrBase: 0.9, i0Base: 1.0,
            rgAppoint: 0.90, reAppoint: 0.85, rdAppoint: 0.85, rrAppoint: 0.9, i0Appoint: 1.0,
        );
        $calc->calculate($node, $ctx);

        $xp   = new \DOMXPath($doc);
        $gens = $xp->query('//generateur_chauffage');
        $this->assertCount(2, $gens);

        $conso0 = $xp->evaluate('number(donnee_intermediaire/conso_ch)', $gens->item(0));
        $conso1 = $xp->evaluate('number(donnee_intermediaire/conso_ch)', $gens->item(1));
        $this->assertGreaterThanOrEqual(0.0, $conso0, 'Base generator conso >= 0');
        $this->assertGreaterThanOrEqual(0.0, $conso1, 'Appoint generator conso >= 0');
        $this->assertGreaterThan(0.0, $conso0 + $conso1, 'Sum of conso > 0');
    }

    public function testNoPowerBaseUsesConvention50percent(): void
    {
        // When pn=0, no base power → convention 50% split
        $calc = new CollectifBaseAppoint();
        [$doc, $ctx, $node] = $this->buildXml(
            bch: 10000, gv: 200, surface: 80, sh: 80, hsp: 2.5,
            rdim: 1, pnW: 0, rgBase: 0.9, reBase: 0.9, rdBase: 0.9, rrBase: 0.9, i0Base: 1.0,
            rgAppoint: 0.9, reAppoint: 0.9, rdAppoint: 0.9, rrAppoint: 0.9, i0Appoint: 1.0,
        );
        $calc->calculate($node, $ctx);

        $xp   = new \DOMXPath($doc);
        $gens = $xp->query('//generateur_chauffage');
        $c0 = $xp->evaluate('number(donnee_intermediaire/conso_ch)', $gens->item(0));
        $c1 = $xp->evaluate('number(donnee_intermediaire/conso_ch)', $gens->item(1));
        // Both should be equal (50/50 split, same rendements)
        $this->assertEqualsWithDelta($c0, $c1, self::TOL * max($c0, $c1) + 1.0, '50% split with equal rendements');
    }

    public function testHigherPowerBaseCoversMoreNeed(): void
    {
        // Larger base power → base covers more → appoint gets less
        $calc = new CollectifBaseAppoint();

        [$doc1, $ctx1, $node1] = $this->buildXml(
            bch: 15000, gv: 300, surface: 100, sh: 100, hsp: 2.5,
            rdim: 1, pnW: 3000, rgBase: 0.87, reBase: 0.85, rdBase: 0.85, rrBase: 0.9, i0Base: 1.0,
            rgAppoint: 0.9, reAppoint: 0.85, rdAppoint: 0.85, rrAppoint: 0.9, i0Appoint: 1.0,
        );
        $calc->calculate($node1, $ctx1);
        $xp1 = new \DOMXPath($doc1);
        $gens1 = $xp1->query('//generateur_chauffage');
        $app1 = $xp1->evaluate('number(donnee_intermediaire/conso_ch)', $gens1->item(1));

        [$doc2, $ctx2, $node2] = $this->buildXml(
            bch: 15000, gv: 300, surface: 100, sh: 100, hsp: 2.5,
            rdim: 1, pnW: 20000, rgBase: 0.87, reBase: 0.85, rdBase: 0.85, rrBase: 0.9, i0Base: 1.0,
            rgAppoint: 0.9, reAppoint: 0.85, rdAppoint: 0.85, rrAppoint: 0.9, i0Appoint: 1.0,
        );
        $calc->calculate($node2, $ctx2);
        $xp2 = new \DOMXPath($doc2);
        $gens2 = $xp2->query('//generateur_chauffage');
        $app2 = $xp2->evaluate('number(donnee_intermediaire/conso_ch)', $gens2->item(1));

        $this->assertLessThan($app1, $app2 + 0.01, 'Higher base power → less appoint conso');
    }
}
