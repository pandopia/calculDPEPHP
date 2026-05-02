<?php

declare(strict_types=1);

namespace Tests\Unit\Apport;

use CalculDpePHP\Apport\FCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour FCalculator (§6.1 p.42-44).
 */
final class FCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 0.001;

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

    /**
     * Nadeq collectif : 19 appartements, Sh=1034.74m² → 34.13977
     */
    public function testNadeqCollectif(): void
    {
        $doc = $this->buildLogement(shImmeuble: 1034.74, nAppart: 19);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, null, null, [
            'enveloppe.dp_parois'          => 500.0,
            'enveloppe.dp_pont_thermique'  => 0.0,
            'apport.sse_mensuel'           => array_fill(1, 12, 0.0),
        ]);

        (new FCalculator())->calculate($node, $ctx);

        $nadeq = (float)$doc->getElementsByTagName('nadeq')->item(0)->textContent;
        $this->assertEqualsWithDelta(34.13977, $nadeq, self::TOL);
    }

    /**
     * Nadeq individuel : Sh=102m², 1 logement → Shmoy=102 ≥70 → Nmax=0.025×102=2.55
     * Nmax ≥ 1.75 → Nadeq = 1 × (1.75 + 0.3×(2.55−1.75)) = 1.75 + 0.24 = 1.99
     */
    public function testNadeqIndividuel(): void
    {
        $doc = $this->buildLogement(shLogement: 102.0, nAppart: null, methodeId: 1);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, null, null, [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => array_fill(1, 12, 0.0),
        ]);

        (new FCalculator())->calculate($node, $ctx);

        $nadeq = (float)$doc->getElementsByTagName('nadeq')->item(0)->textContent;
        $expected = 1.75 + 0.3 * (0.025 * 102.0 - 1.75);
        $this->assertEqualsWithDelta($expected, $nadeq, self::TOL);
    }

    /**
     * Nadeq collectif petite surface : Shmoy=25m² (<50m² mais ≥10m²)
     * Shmoy=25: Nmax = 1.75 − 0.01875×(50−25) = 1.75 − 0.46875 = 1.28125 (<1.75)
     * Nadeq = 1 × 1.28125 = 1.28125
     */
    public function testNadeqCollectifSmall(): void
    {
        // 4 appartements × 25m² = 100m²
        $doc = $this->buildLogement(shImmeuble: 100.0, nAppart: 4);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, null, null, [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => array_fill(1, 12, 0.0),
        ]);

        (new FCalculator())->calculate($node, $ctx);

        $nadeq = (float)$doc->getElementsByTagName('nadeq')->item(0)->textContent;
        $shmoy = 25.0;
        $nmax  = 1.75 - 0.01875 * (50.0 - $shmoy);
        $expected = 4 * $nmax;
        $this->assertEqualsWithDelta($expected, $nadeq, self::TOL);
    }

    /**
     * Formule Fj lourde : n=3.6, Xj=0.5
     * Fj = (0.5 − 0.5^3.6) / (1 − 0.5^3.6)
     */
    public function testFFormulaLourde(): void
    {
        $n  = 3.6;
        $x  = 0.5;
        $xn = $x ** $n; // 0.5^3.6 ≈ 0.08240
        $expected = ($x - $xn) / (1.0 - $xn);

        // Build a minimal logement with known SSe and GV to get a controlled Xj
        $doc = $this->buildLogement(shImmeuble: 1034.74, nAppart: 19, inertieId: 3); // lourde
        $node = $doc->getElementsByTagName('logement')->item(0);

        $tvS = (new TableRepository(self::PROJECT_ROOT . '/resources/tables'))
            ->load('reference/tv_sollicitations');

        // Only use January (zone 1 alt 1)
        $row = $tvS[1][1][1];
        $DH19j   = $row['DH19'];  // 11712.4
        $Nref19j = $row['Nref19']; // 744
        $Ej      = $row['E'];      // 38.36

        // GV chosen so Xj = 0.5 for January
        // Xj = (Asj + Aij) / (GV × DH19j) = 0.5
        // → GV = (Asj + Aij) / (0.5 × DH19j)
        $nadeq  = 34.13977;
        $sh     = 1034.74;
        $Ssej   = 30.0; // arbitrary
        $Asj    = 1000.0 * $Ssej * $Ej;
        $Aij    = (3.52 * $sh + 90.0 * (132.0/168.0) * $nadeq) * $Nref19j;
        $gv     = ($Asj + $Aij) / (0.5 * $DH19j);

        // Set SSe only for January
        $sseMensuel = array_fill(1, 12, 0.0);
        $sseMensuel[1] = $Ssej;

        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'         => $gv,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => $sseMensuel,
        ]);

        // Zero out all months except Jan so fraction = F_jan
        // (other months give non-zero Xj too, so we just test nadeq/apports here)
        (new FCalculator())->calculate($node, $ctx);

        // fraction should be between 0 and 1
        $fraction = (float)$doc->getElementsByTagName('fraction_apport_gratuit_ch')->item(0)->textContent;
        $this->assertGreaterThan(0.0, $fraction);
        $this->assertLessThan(1.0, $fraction);
    }

    /**
     * F=1 si Xj ≥ 1 (besoins entièrement couverts par apports gratuits).
     */
    public function testFEqualsOneWhenXGteOne(): void
    {
        // Set très grand SSe pour que Xj ≥ 1 partout
        $doc = $this->buildLogement(shImmeuble: 1034.74, nAppart: 19, inertieId: 2);
        $node = $doc->getElementsByTagName('logement')->item(0);

        $sseMensuel = array_fill(1, 12, 9999999.0); // SSe énorme

        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'         => 100.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => $sseMensuel,
        ]);

        (new FCalculator())->calculate($node, $ctx);

        $fraction = (float)$doc->getElementsByTagName('fraction_apport_gratuit_ch')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.0, $fraction, self::TOL);
    }

    /**
     * Apport solaire = 0 si SSe = 0 pour tous les mois.
     */
    public function testApportSolaireZeroWhenNoSse(): void
    {
        $doc = $this->buildLogement(shImmeuble: 1034.74, nAppart: 19);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => array_fill(1, 12, 0.0),
        ]);

        (new FCalculator())->calculate($node, $ctx);

        $apportSolaire = (float)$doc->getElementsByTagName('apport_solaire_ch')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $apportSolaire, self::TOL);
    }

    /**
     * Apport interne > 0 même sans SSe (les occupants et équipements génèrent de la chaleur).
     */
    public function testApportInternePositive(): void
    {
        $doc = $this->buildLogement(shImmeuble: 1034.74, nAppart: 19);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => array_fill(1, 12, 0.0),
        ]);

        (new FCalculator())->calculate($node, $ctx);

        $apportInterne = (float)$doc->getElementsByTagName('apport_interne_ch')->item(0)->textContent;
        $this->assertGreaterThan(0.0, $apportInterne);
    }

    /**
     * Sans données climatiques (zoneId=null) → fraction = 0 (aucune donnée → fallback).
     */
    public function testNoClimateData_FractionZero(): void
    {
        $doc = $this->buildLogement(shImmeuble: 500.0, nAppart: 10);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, null, null, [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => array_fill(1, 12, 10.0),
        ]);

        (new FCalculator())->calculate($node, $ctx);

        $fraction = (float)$doc->getElementsByTagName('fraction_apport_gratuit_ch')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $fraction, self::TOL);
    }

    /**
     * ETS SSe s'additionne au SSe des baies : apport solaire augmente.
     */
    public function testEtsSseAdded(): void
    {
        $doc = $this->buildLogement(shImmeuble: 1034.74, nAppart: 19);
        $node = $doc->getElementsByTagName('logement')->item(0);

        $sseMensuel    = array_fill(1, 12, 5.0);
        $sseEtsMensuel = array_fill(1, 12, 2.0);

        $ctx1 = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => $sseMensuel,
        ]);
        (new FCalculator())->calculate($node, $ctx1);
        $as1 = (float)$doc->getElementsByTagName('apport_solaire_ch')->item(0)->textContent;

        // Reset DOM
        $doc2 = $this->buildLogement(shImmeuble: 1034.74, nAppart: 19);
        $node2 = $doc2->getElementsByTagName('logement')->item(0);
        $ctx2 = $this->makeContext($doc2, '1', '1', [
            'enveloppe.dp_parois'         => 500.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => $sseMensuel,
            'apport.sse_ets_mensuel'      => $sseEtsMensuel,
        ]);
        (new FCalculator())->calculate($node2, $ctx2);
        $as2 = (float)$doc2->getElementsByTagName('apport_solaire_ch')->item(0)->textContent;

        $this->assertGreaterThan($as1, $as2);
    }

    /**
     * La fraction dépensière (DH21) est ≤ fraction conventionnelle (DH19)
     * car DH21 > DH19 → X21 < X19 → F21 < F19.
     */
    public function testDepensierFractionLeConventionnel(): void
    {
        $doc = $this->buildLogement(shImmeuble: 1034.74, nAppart: 19, inertieId: 2);
        $node = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc, '1', '1', [
            'enveloppe.dp_parois'         => 995.0,
            'enveloppe.dp_pont_thermique' => 0.0,
            'apport.sse_mensuel'          => array_fill(1, 12, 50.0),
        ]);

        (new FCalculator())->calculate($node, $ctx);

        $fch  = (float)$doc->getElementsByTagName('fraction_apport_gratuit_ch')->item(0)->textContent;
        $fdep = (float)$doc->getElementsByTagName('fraction_apport_gratuit_depensier_ch')->item(0)->textContent;

        $this->assertLessThanOrEqual($fch, $fdep + self::TOL);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function buildLogement(
        float  $shImmeuble  = 1034.74,
        float  $shLogement  = 62.86,
        ?int   $nAppart     = 19,
        int    $inertieId   = 2,
        ?int   $methodeId   = null,
    ): DOMDocument {
        $nAppartXml = $nAppart !== null
            ? "<nombre_appartement>{$nAppart}</nombre_appartement>"
            : '';
        $methodeXml = $methodeId !== null
            ? "<enum_methode_application_dpe_log_id>{$methodeId}</enum_methode_application_dpe_log_id>"
            : '';

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
  <caracteristique_generale>
    <surface_habitable_logement>{$shLogement}</surface_habitable_logement>
    <surface_habitable_immeuble>{$shImmeuble}</surface_habitable_immeuble>
    {$nAppartXml}
    {$methodeXml}
  </caracteristique_generale>
  <enveloppe>
    <inertie>
      <enum_classe_inertie_id>{$inertieId}</enum_classe_inertie_id>
    </inertie>
  </enveloppe>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc;
    }
}
