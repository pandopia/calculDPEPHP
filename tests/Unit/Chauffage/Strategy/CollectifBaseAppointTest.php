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

    public function testDh14EtTextDesParoisAnciennesExigentUneInertieLourde(): void
    {
        foreach ([0, 1] as $anciens) {
            foreach ([1, 2, 3, 4] as $inertie) {
                [$doc, $ctx, $node] = $this->buildXml(
                    bch: 10000, gv: 200, surface: 80, sh: 80, hsp: 2.5,
                    rdim: 1, pnW: 1000, rgBase: 1, reBase: 1, rdBase: 1, rrBase: 1, i0Base: 1,
                    rgAppoint: 1, reAppoint: 1, rdAppoint: 1, rrAppoint: 1, i0Appoint: 1,
                    matAnciensId: $anciens, inertieId: $inertie,
                );
                $mensuels = array_fill(1, 12, 0.0);
                $mensuels[1] = 10000.0;
                $ctx->set('chauffage.besoin_ch_mensuel', $mensuels);

                $ilpa = $anciens === 1 && $inertie <= 2 ? 1 : 0;
                $dh = $ctx->tables->load('chauffage/dh14_base_appoint')[$ilpa][1];
                $text = $ctx->tables->load('chauffage/text_mensuel_base_appoint')[$ilpa][1][1][1];
                $nref = \CalculDpePHP\Common\ClimaticSolicitations::heating($ctx, 1, 1)[1]['Nref19'];
                $t = 14 - array_sum(array_column($dh, 1)) / 10000;
                $x = 0.5 * ($t + 9.5) / ($text + 9.5);
                $dht = max(0.0, $nref * ($text + 9.5) * $x ** 5 * (14 - 28 * $x + 20 * $x ** 2 - 5 * $x ** 3));
                $attendu = min(10000.0, 10000 * max(0.0, 1 - $dht / $dh[1][1]));

                // Vérifie aussi le repli XML lors d'un appel isolé, sans
                // InertieCalculator : la classe est portée par l'enveloppe.
                if ($inertie % 2 === 0) {
                    $ctx->set('inertie.classe_id', $inertie);
                }
                (new CollectifBaseAppoint())->calculate($node, $ctx);
                $xp = new \DOMXPath($doc);
                self::assertEqualsWithDelta(
                    $attendu,
                    $xp->evaluate('number(//generateur_chauffage[1]/donnee_intermediaire/conso_ch)'),
                    1e-7,
                    "matériaux anciens=$anciens, classe ADEME=$inertie",
                );
            }
        }
    }

    public function testLeVolumeDIntermittenceSuitLePerimetreDuBesoin(): void
    {
        foreach ([32 => 80.0, 7 => 800.0] as $mode => $surfaceReference) {
            [$doc, $ctx, $node] = $this->buildXml(
                bch: 10000, gv: 200, surface: $surfaceReference, sh: 800, hsp: 2.5,
                rdim: 1, pnW: 0, rgBase: 1, reBase: 1, rdBase: 1, rrBase: 1, i0Base: 1,
                rgAppoint: 1, reAppoint: 1, rdAppoint: 1, rrAppoint: 1, i0Appoint: 1,
            );
            $carac = $doc->getElementsByTagName('caracteristique_generale')->item(0);
            $carac->appendChild($doc->createElement('enum_methode_application_dpe_log_id', (string)$mode));
            $carac->appendChild($doc->createElement('surface_habitable_logement', '80'));
            (new CollectifBaseAppoint())->calculate($node, $ctx);
            $xp = new \DOMXPath($doc);
            $attendu = 10000 / (1 + 0.1 * (200 / (2.5 * $surfaceReference) - 1));
            self::assertEqualsWithDelta($attendu, $xp->evaluate('number(//installation_chauffage/donnee_intermediaire/conso_ch)'), 1e-7);
        }
    }

    public function testLeDepensierUtiliseSonBesoinEtSesRendements(): void
    {
        [$doc, $ctx, $node] = $this->buildXml(
            bch: 10000, gv: 200, surface: 80, sh: 80, hsp: 2.5,
            rdim: 1, pnW: 0, rgBase: 0.8, reBase: 1, rdBase: 1, rrBase: 1, i0Base: 1,
            rgAppoint: 0.9, reAppoint: 1, rdAppoint: 1, rrAppoint: 1, i0Appoint: 1,
        );
        $ctx->set('chauffage.besoin_ch_depensier', 15000.0);
        $ctx->set('chauffage.rendement_generation_depensier', ['base' => 0.9, 'appoint' => 0.95]);
        foreach ($doc->getElementsByTagName('generateur_chauffage') as $i => $gen) {
            $de = $doc->createElement('donnee_entree');
            $de->appendChild($doc->createElement('reference', $i === 0 ? 'base' : 'appoint'));
            $gen->appendChild($de);
        }
        (new CollectifBaseAppoint())->calculate($node, $ctx);

        $xp = new \DOMXPath($doc);
        self::assertEqualsWithDelta(10000.0, $xp->evaluate('number(//installation_chauffage/donnee_intermediaire/besoin_ch)'), 1e-8);
        self::assertEqualsWithDelta(15000.0, $xp->evaluate('number(//installation_chauffage/donnee_intermediaire/besoin_ch_depensier)'), 1e-8);
        foreach ([['', 5000 / 0.8, 5000 / 0.9], ['_depensier', 7500 / 0.9, 7500 / 0.95]] as [$suffixe, $base, $appoint]) {
            self::assertEqualsWithDelta($base, $xp->evaluate('number(//generateur_chauffage[1]/donnee_intermediaire/conso_ch' . $suffixe . ')'), 1e-7);
            self::assertEqualsWithDelta($appoint, $xp->evaluate('number(//generateur_chauffage[2]/donnee_intermediaire/conso_ch' . $suffixe . ')'), 1e-7);
            self::assertEqualsWithDelta($base + $appoint, $xp->evaluate('number(//installation_chauffage/donnee_intermediaire/conso_ch' . $suffixe . ')'), 1e-7);
        }
    }

    public function testLeProfilMensuelReelPrimeSurLeProrataDesDegresHeures(): void
    {
        $bases = [];
        foreach ([1, 4] as $mois) {
            [$doc, $ctx, $node] = $this->buildXml(
                bch: 20000, gv: 400, surface: 80, sh: 160, hsp: 2.5,
                rdim: 1, pnW: 1000, rgBase: 1, reBase: 1, rdBase: 1, rrBase: 1, i0Base: 1,
                rgAppoint: 1, reAppoint: 1, rdAppoint: 1, rrAppoint: 1, i0Appoint: 1,
            );
            $mensuels = array_fill(1, 12, 0.0);
            $mensuels[$mois] = 20000.0;
            $ctx->set('chauffage.besoin_ch_mensuel', $mensuels);
            $ctx->set('chauffage.besoin_ch_mensuel_depensier', $mensuels);
            (new CollectifBaseAppoint())->calculate($node, $ctx);

            $tables = $ctx->tables;
            $dh = $tables->load('chauffage/dh14_base_appoint')[0][1];
            $text = $tables->load('chauffage/text_mensuel_base_appoint')[0][1][$mois][1];
            $nref = \CalculDpePHP\Common\ClimaticSolicitations::heating($ctx, 1, 1)[$mois]['Nref19'];
            $t = 14 - array_sum(array_column($dh, 1)) / 10000;
            $x = 0.5 * ($t + 9.5) / ($text + 9.5);
            $dht = max(0.0, $nref * ($text + 9.5) * $x ** 5 * (14 - 28 * $x + 20 * $x ** 2 - 5 * $x ** 3));
            $attendu = 10000 * max(0.0, 1 - $dht / $dh[$mois][1]);
            $xp = new \DOMXPath($doc);
            $base = $xp->evaluate('number(//generateur_chauffage[1]/donnee_intermediaire/conso_ch)');
            self::assertEqualsWithDelta($attendu, $base, 1e-7, 'besoin mensuel proratisé une seule fois à la surface desservie');
            self::assertEqualsWithDelta(10000.0, $xp->evaluate('number(//installation_chauffage/donnee_intermediaire/conso_ch)'), 1e-7);
            $bases[] = $base;
        }
        self::assertNotEqualsWithDelta($bases[0], $bases[1], 1e-3, 'un même besoin annuel ne définit pas le partage mensuel');
    }

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
        <batiment_materiaux_anciens>$matAnciensId</batiment_materiaux_anciens>
    </meteo>
    <caracteristique_generale>
        <hsp>$hsp</hsp>
        <surface_habitable_immeuble>$sh</surface_habitable_immeuble>
        <nombre_appartement>1</nombre_appartement>
    </caracteristique_generale>
    <enveloppe><inertie><enum_classe_inertie_id>$inertieId</enum_classe_inertie_id></inertie></enveloppe>
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

    /**
     * §18.3 tabule des températures extérieures moyennes **négatives** dès la
     * classe d'altitude 3 : H1c y donne −0,2 °C en janvier, −0,1 en février et
     * −0,8 en décembre. Un garde `Textj <= 0` les prenait pour des données
     * manquantes et vidait la base de ces trois mois — ceux où elle travaille
     * le plus. La base doit donc couvrir davantage en H1c altitude 3 qu'en
     * altitude 1 à puissance et besoin égaux, le froid y étant plus vif.
     */
    public function testLesMoisATemperatureNegativeComptentPourLaBase(): void
    {
        $parts = [];
        foreach ([1, 3] as $altId) {
            [$doc, $ctx, $node] = $this->buildXml(
                bch: 10000, gv: 200, surface: 80, sh: 80, hsp: 2.5,
                rdim: 1, pnW: 4000, rgBase: 1.0, reBase: 1.0, rdBase: 1.0, rrBase: 1.0, i0Base: 1.0,
                rgAppoint: 1.0, reAppoint: 1.0, rdAppoint: 1.0, rrAppoint: 1.0, i0Appoint: 1.0,
                zoneId: 3, altId: $altId,
            );
            (new CollectifBaseAppoint())->calculate($node, $ctx);

            $xp = new \DOMXPath($doc);
            $generateurs = $xp->query('//generateur_chauffage/donnee_intermediaire/conso_ch');
            $base = (float) $generateurs->item(0)->textContent;
            $appoint = (float) $generateurs->item(1)->textContent;
            self::assertGreaterThan(0.0, $base + $appoint, "altitude $altId : aucune consommation");
            $parts[$altId] = $base / ($base + $appoint);
        }

        self::assertGreaterThan(0.0, $parts[3], 'la base est vide en altitude 3');
        self::assertGreaterThan(
            $parts[1],
            $parts[3],
            'la base devrait couvrir davantage en altitude 3 : ses mois les plus froids y sont comptés',
        );
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
