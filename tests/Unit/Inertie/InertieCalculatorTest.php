<?php

declare(strict_types=1);

namespace Tests\Unit\Inertie;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Inertie\InertieCalculator;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class InertieCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function makeContext(DOMDocument $doc): CalculationContext
    {
        return new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
    }

    private function buildDoc(string $pbLourd, string $phLourd, string $pvLourd): DOMDocument
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
  <enveloppe>
    <plancher_bas_collection>
      <plancher_bas>
        <donnee_entree>
          <surface_paroi_totale>100</surface_paroi_totale>
          <paroi_lourde>{$pbLourd}</paroi_lourde>
        </donnee_entree>
      </plancher_bas>
    </plancher_bas_collection>
    <plancher_haut_collection>
      <plancher_haut>
        <donnee_entree>
          <surface_paroi_totale>100</surface_paroi_totale>
          <paroi_lourde>{$phLourd}</paroi_lourde>
        </donnee_entree>
      </plancher_haut>
    </plancher_haut_collection>
    <mur_collection>
      <mur>
        <donnee_entree>
          <surface_paroi_totale>200</surface_paroi_totale>
          <paroi_lourde>{$pvLourd}</paroi_lourde>
        </donnee_entree>
      </mur>
    </mur_collection>
  </enveloppe>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc;
    }

    public function testTresLourd(): void
    {
        $doc = $this->buildDoc('1', '1', '1');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        $this->assertSame(4, $ctx->get('inertie.classe_id'));
    }

    public function testLourd_PbPh(): void
    {
        $doc = $this->buildDoc('1', '1', '0');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        $this->assertSame(3, $ctx->get('inertie.classe_id'));
    }

    public function testLourd_PhPv(): void
    {
        $doc = $this->buildDoc('0', '1', '1');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        $this->assertSame(3, $ctx->get('inertie.classe_id'));
    }

    public function testMoyenne_SeulementPv(): void
    {
        $doc = $this->buildDoc('0', '0', '1');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        $this->assertSame(2, $ctx->get('inertie.classe_id'));
    }

    public function testMoyenne_SeulementPh(): void
    {
        $doc = $this->buildDoc('0', '1', '0');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        $this->assertSame(2, $ctx->get('inertie.classe_id'));
    }

    public function testMoyenne_SeulementPb(): void
    {
        $doc = $this->buildDoc('1', '0', '0');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        $this->assertSame(2, $ctx->get('inertie.classe_id'));
    }

    public function testLegere(): void
    {
        $doc = $this->buildDoc('0', '0', '0');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        $this->assertSame(1, $ctx->get('inertie.classe_id'));
    }

    public function testWritesXmlTag(): void
    {
        $doc = $this->buildDoc('1', '1', '1');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $this->assertNotNull($di);
        $tag = $di->getElementsByTagName('enum_classe_inertie_id')->item(0);
        $this->assertNotNull($tag);
        $this->assertSame('4', $tag->textContent);
    }

    public function testDefaultPbLourd_WhenAbsent(): void
    {
        // PB sans paroi_lourde → défaut = lourd (§7.2)
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
  <enveloppe>
    <plancher_bas_collection>
      <plancher_bas>
        <donnee_entree>
          <surface_paroi_totale>100</surface_paroi_totale>
        </donnee_entree>
      </plancher_bas>
    </plancher_bas_collection>
    <plancher_haut_collection/>
    <mur_collection/>
  </enveloppe>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        // PB lourd, PH absent (défaut légère), PV absent (défaut légère) → classe 2 (moyenne: seul PB lourd)
        $this->assertSame(2, $ctx->get('inertie.classe_id'));
    }

    public function testSurfaceMajoritaire(): void
    {
        // 2 murs: 60m² non-lourd, 40m² lourd → majorité non-lourd → PV = false
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
  <enveloppe>
    <mur_collection>
      <mur><donnee_entree><surface_paroi_totale>60</surface_paroi_totale><paroi_lourde>0</paroi_lourde></donnee_entree></mur>
      <mur><donnee_entree><surface_paroi_totale>40</surface_paroi_totale><paroi_lourde>1</paroi_lourde></donnee_entree></mur>
    </mur_collection>
    <plancher_bas_collection/>
    <plancher_haut_collection/>
  </enveloppe>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = $this->makeContext($doc);
        (new InertieCalculator())->calculate($logement, $ctx);
        // PV non lourd (60>50%), PH absent=léger, PB absent=lourd → seul PB lourd → classe 2
        $this->assertSame(2, $ctx->get('inertie.classe_id'));
    }
}
