<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpe\Common\Period;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Sortie\QualiteIsolationCalculator;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour QualiteIsolationCalculator.
 *
 * Seuils qualite_isol (open3cl, strict <) :
 *   Murs       : <0.30→1, <0.45→2, <0.65→3, sinon 4
 *   Plancher bas: <0.25→1, <0.45→2, <0.65→3, sinon 4
 *   Toit terrasse: <0.25→1, <0.30→2, <0.35→3, sinon 4
 *   Comble perdu: <0.15→1, <0.20→2, <0.30→3, sinon 4
 *   Menuiseries: <1.60→1, <2.20→2, <3.00→3, sinon 4
 *   Enveloppe  : <0.45→1, <0.65→2, <0.85→3, sinon 4
 */
final class QualiteIsolationCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            period: Period::PRE_2026,
        );
    }

    /**
     * Builds a logement DOM with one mur, one plancher_bas, one plancher_haut,
     * one baie_vitree, and no ponts thermiques.
     *
     * @param float $surface  surface_habitable_logement
     * @param float $umur     computed umur
     * @param float $upb      computed upb_final
     * @param float $uph      computed uph
     * @param float $ubv      computed u_menuiserie
     * @param float $s        common surface for all parois
     * @param float $b        common b coefficient
     */
    private function buildDoc(float $surface, float $umur, float $upb, float $uph, float $ubv, float $s = 100.0, float $b = 1.0, float $dpPT = 0.0): array
    {
        // Pre-compute deperditions for the sortie/deperdition block (as EnveloppeAggregator would)
        $dMur = round($b * $s * $umur, 9);
        $dPb  = round($b * $s * $upb,  9);
        $dPh  = round($b * $s * $uph,  9);
        $dBv  = round($b * $s * $ubv,  9);

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>$surface</surface_habitable_logement>
    </caracteristique_generale>
    <enveloppe>
        <mur_collection>
            <mur>
                <donnee_entree><surface_paroi_opaque>$s</surface_paroi_opaque></donnee_entree>
                <donnee_intermediaire><b>$b</b><umur>$umur</umur></donnee_intermediaire>
            </mur>
        </mur_collection>
        <plancher_bas_collection>
            <plancher_bas>
                <donnee_entree><surface_paroi_opaque>$s</surface_paroi_opaque></donnee_entree>
                <donnee_intermediaire><b>$b</b><upb_final>$upb</upb_final></donnee_intermediaire>
            </plancher_bas>
        </plancher_bas_collection>
        <plancher_haut_collection>
            <plancher_haut>
                <donnee_entree><surface_paroi_opaque>$s</surface_paroi_opaque></donnee_entree>
                <donnee_intermediaire><b>$b</b><uph>$uph</uph></donnee_intermediaire>
            </plancher_haut>
        </plancher_haut_collection>
        <baie_vitree_collection>
            <baie_vitree>
                <donnee_entree><surface_totale_baie>$s</surface_totale_baie></donnee_entree>
                <donnee_intermediaire><b>$b</b><u_menuiserie>$ubv</u_menuiserie></donnee_intermediaire>
            </baie_vitree>
        </baie_vitree_collection>
    </enveloppe>
    <sortie>
        <deperdition>
            <deperdition_mur>$dMur</deperdition_mur>
            <deperdition_plancher_bas>$dPb</deperdition_plancher_bas>
            <deperdition_plancher_haut>$dPh</deperdition_plancher_haut>
            <deperdition_baie_vitree>$dBv</deperdition_baie_vitree>
            <deperdition_porte>0</deperdition_porte>
            <deperdition_pont_thermique>$dpPT</deperdition_pont_thermique>
        </deperdition>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $logement = $doc->documentElement;
        return [$doc, $logement];
    }

    private function runAndGetQi(float $surface, float $umur, float $upb, float $uph, float $ubv, float $s = 100.0, float $b = 1.0, float $dpPT = 0.0): \DOMElement
    {
        [$doc, $logement] = $this->buildDoc($surface, $umur, $upb, $uph, $ubv, $s, $b, $dpPT);
        $ctx = $this->makeContext($doc);
        $ctx->set('enveloppe.dp_pont_thermique', $dpPT);

        (new QualiteIsolationCalculator())->calculate($logement, $ctx);

        // Locate qualite_isolation node
        $xpath = new \DOMXPath($doc);
        $qiNodes = $xpath->query('.//qualite_isolation', $logement);
        $this->assertNotNull($qiNodes);
        $this->assertGreaterThan(0, $qiNodes->length);
        return $qiNodes->item(0);
    }

    private function childText(\DOMElement $parent, string $tag): ?string
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof \DOMElement && $c->nodeName === $tag) return $c->textContent;
        }
        return null;
    }

    public function testAppliesToLogement(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<logement/>');
        $calc = new QualiteIsolationCalculator();
        $this->assertTrue($calc->appliesTo($doc->documentElement));

        $doc2 = new DOMDocument();
        $doc2->loadXML('<installation_chauffage/>');
        $this->assertFalse($calc->appliesTo($doc2->documentElement));
    }

    public function testUbatFormula(): void
    {
        // ubat = (b×S×umur + b×S×upb + b×S×uph + b×S×ubv) / Σ(S_parois)
        // With b=1, S=100 per paroi (4 parois), umur=0.345, upb=0.167, uph=0.14, ubv=2.2
        // Numerator = 100*(0.345 + 0.167 + 0.14 + 2.2) = 285.2 W/K
        // Denominator = 4 × 100 = 400 m² (total envelope surface)
        // ubat = 285.2 / 400 = 0.713
        $qi = $this->runAndGetQi(200.0, 0.345, 0.167, 0.14, 2.2, 100.0, 1.0, 0.0);
        $ubat = (float)$this->childText($qi, 'ubat');
        $this->assertEqualsWithDelta(0.713, $ubat, 0.001);
    }

    public function testUbatIncludesPontThermique(): void
    {
        // Add dpPT = 50.0 to numerator → (285.2+50) / 400 = 335.2 / 400 = 0.838
        $qi = $this->runAndGetQi(200.0, 0.345, 0.167, 0.14, 2.2, 100.0, 1.0, 50.0);
        $ubat = (float)$this->childText($qi, 'ubat');
        $this->assertEqualsWithDelta(0.838, $ubat, 0.001);
    }

    /** @dataProvider murQualiteProvider */
    public function testQualiteIsolMur(float $umur, int $expectedClasse): void
    {
        $qi = $this->runAndGetQi(100.0, $umur, 0.15, 0.10, 0.80, 100.0, 1.0);
        $this->assertEquals($expectedClasse, (int)$this->childText($qi, 'qualite_isol_mur'));
    }

    public static function murQualiteProvider(): array
    {
        // MUR_THRESHOLDS = [0.30, 0.45, 0.65] (strict <)
        return [
            'class 1 (U=0.20)'  => [0.20, 1],
            'class 2 (U=0.35)'  => [0.35, 2],
            'class 3 (U=0.55)'  => [0.55, 3],
            'class 4 (U=2.50)'  => [2.50, 4],
        ];
    }

    /** @dataProvider phQualiteProvider */
    public function testQualiteIsolPlancherHaut(float $uph, int $expectedClasse): void
    {
        // buildDoc creates plancher_haut without adjacence_id → classified as comble_perdu
        $qi = $this->runAndGetQi(100.0, 0.15, 0.15, $uph, 0.80, 100.0, 1.0);
        $this->assertEquals($expectedClasse, (int)$this->childText($qi, 'qualite_isol_plancher_haut_comble_perdu'));
    }

    public static function phQualiteProvider(): array
    {
        // CP_THRESHOLDS = [0.15, 0.20, 0.30] (strict <)
        return [
            'class 1 (U=0.10)' => [0.10, 1],
            'class 2 (U=0.16)' => [0.16, 2],
            'class 3 (U=0.25)' => [0.25, 3],
            'class 4 (U=0.50)' => [0.50, 4],
        ];
    }

    /** @dataProvider pbQualiteProvider */
    public function testQualiteIsolPlancherBas(float $upb, int $expectedClasse): void
    {
        $qi = $this->runAndGetQi(100.0, 0.15, $upb, 0.10, 0.80, 100.0, 1.0);
        $this->assertEquals($expectedClasse, (int)$this->childText($qi, 'qualite_isol_plancher_bas'));
    }

    public static function pbQualiteProvider(): array
    {
        return [
            'class 1 (U=0.167)' => [0.167, 1],
            'class 2 (U=0.40)'  => [0.40,  2],
            'class 3 (U=0.55)'  => [0.55,  3],
            'class 4 (U=1.50)'  => [1.50,  4],
        ];
    }

    /** @dataProvider menuiserieQualiteProvider */
    public function testQualiteIsolMenuiserie(float $ubv, int $expectedClasse): void
    {
        $qi = $this->runAndGetQi(100.0, 0.15, 0.15, 0.10, $ubv, 100.0, 1.0);
        $this->assertEquals($expectedClasse, (int)$this->childText($qi, 'qualite_isol_menuiserie'));
    }

    public static function menuiserieQualiteProvider(): array
    {
        // MENU_THRESHOLDS = [1.60, 2.20, 3.00] (strict <)
        return [
            'class 1 (U=0.80)' => [0.80, 1],
            'class 1 (U=1.37)' => [1.37, 1],
            'class 2 (U=2.00)' => [2.00, 2],
            'class 3 (U=2.50)' => [2.50, 3],
            'class 4 (U=4.00)' => [4.00, 4],
        ];
    }

    /** @dataProvider ubatQualiteProvider */
    public function testQualiteIsolEnveloppe(float $ubat, int $expectedClasse): void
    {
        // Set ubat to target by adjusting surface; use identical U values = ubat_target
        // ubat = (4 × S × U) / surface → set U=ubat_target, S=100, surface=400 → ubat=ubat_target
        $qi = $this->runAndGetQi(400.0, $ubat, $ubat, $ubat, $ubat, 100.0, 1.0, 0.0);
        $this->assertEquals($expectedClasse, (int)$this->childText($qi, 'qualite_isol_enveloppe'));
    }

    public static function ubatQualiteProvider(): array
    {
        // ENV_THRESHOLDS = [0.45, 0.65, 0.85] (strict <)
        return [
            'class 1 (ubat=0.30)' => [0.30, 1],
            'class 2 (ubat=0.55)' => [0.55, 2],
            'class 3 (ubat=0.75)' => [0.75, 3],
            'class 4 (ubat=2.00)' => [2.00, 4],
        ];
    }

    public function testVerifLikePreBuildingValues(): void
    {
        // Rough verification matching bat_pre2026 profile:
        // murs with ~80% non-insulated (U=2.5) and 20% insulated (U=0.303)
        // → u_mur_moyen ≈ (80×2.5 + 20×0.303)/(100) = 2.0606 → qualite 4
        // upb=0.55 → PB_THRESHOLDS [0.25, 0.45, 0.65]: 0.55 < 0.65 → qualite 3
        // uph=0.14 → CP_THRESHOLDS [0.15, 0.20, 0.30]: 0.14 < 0.15 → qualite 1 (comble_perdu)
        // u_menuiserie=1.37 → MENU_THRESHOLDS [1.60, 2.20, 3.00]: 1.37 < 1.60 → qualite 1
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale><surface_habitable_logement>100.0</surface_habitable_logement></caracteristique_generale>
    <enveloppe>
        <mur_collection>
            <mur>
                <donnee_entree><surface_paroi_opaque>80</surface_paroi_opaque></donnee_entree>
                <donnee_intermediaire><b>1.0</b><umur>2.5</umur></donnee_intermediaire>
            </mur>
            <mur>
                <donnee_entree><surface_paroi_opaque>20</surface_paroi_opaque></donnee_entree>
                <donnee_intermediaire><b>1.0</b><umur>0.303</umur></donnee_intermediaire>
            </mur>
        </mur_collection>
        <plancher_bas_collection>
            <plancher_bas>
                <donnee_entree><surface_paroi_opaque>50</surface_paroi_opaque></donnee_entree>
                <donnee_intermediaire><b>1.0</b><upb_final>0.55</upb_final></donnee_intermediaire>
            </plancher_bas>
        </plancher_bas_collection>
        <plancher_haut_collection>
            <plancher_haut>
                <donnee_entree><surface_paroi_opaque>50</surface_paroi_opaque></donnee_entree>
                <donnee_intermediaire><b>1.0</b><uph>0.14</uph></donnee_intermediaire>
            </plancher_haut>
        </plancher_haut_collection>
        <baie_vitree_collection>
            <baie_vitree>
                <donnee_entree><surface_totale_baie>10</surface_totale_baie></donnee_entree>
                <donnee_intermediaire><b>1.0</b><u_menuiserie>1.37</u_menuiserie></donnee_intermediaire>
            </baie_vitree>
        </baie_vitree_collection>
    </enveloppe>
    <sortie>
        <deperdition>
            <deperdition_mur>206.06</deperdition_mur>
            <deperdition_plancher_bas>27.5</deperdition_plancher_bas>
            <deperdition_plancher_haut>7.0</deperdition_plancher_haut>
            <deperdition_baie_vitree>13.7</deperdition_baie_vitree>
            <deperdition_porte>0</deperdition_porte>
            <deperdition_pont_thermique>0</deperdition_pont_thermique>
        </deperdition>
    </sortie>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $ctx = $this->makeContext($doc);
        $ctx->set('enveloppe.dp_pont_thermique', 0.0);

        (new QualiteIsolationCalculator())->calculate($doc->documentElement, $ctx);

        $xpath = new \DOMXPath($doc);
        $qi = $xpath->query('.//qualite_isolation', $doc->documentElement)->item(0);

        $this->assertEquals(4, (int)$this->childText($qi, 'qualite_isol_mur'), 'mur qualite');
        $this->assertEquals(1, (int)$this->childText($qi, 'qualite_isol_plancher_haut_comble_perdu'), 'ph comble_perdu qualite');
        $this->assertEquals(3, (int)$this->childText($qi, 'qualite_isol_plancher_bas'), 'pb qualite');
        $this->assertEquals(1, (int)$this->childText($qi, 'qualite_isol_menuiserie'), 'menuiserie qualite');
    }
}
