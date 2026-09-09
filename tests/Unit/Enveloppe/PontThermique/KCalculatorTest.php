<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\PontThermique;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\PontThermique\KCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class KCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';

    /** @return iterable<string, array{int, int, string}> */
    public static function adjacencyCases(): iterable
    {
        yield 'circulation commune' => [14, 1, '0'];
        yield 'local chauffé non déperditif' => [22, 1, '0'];
        yield 'extérieur' => [1, 1, '0.71'];
        yield 'plancher intermédiaire vers local chauffé' => [22, 2, '0.71'];
        yield 'refend vers local chauffé' => [22, 4, '0.71'];
    }

    #[DataProvider('adjacencyCases')]
    public function testNeglectsOnlyNonEnvelopeAdjacencies(int $adjacency, int $liaison, string $expected): void
    {
        $document = new DOMDocument();
        $document->loadXML(sprintf(<<<'XML'
<logement>
  <enveloppe>
    <mur_collection>
      <mur>
        <donnee_entree>
          <reference>mur-1</reference>
          <enum_type_adjacence_id>%d</enum_type_adjacence_id>
          <enum_type_isolation_id>3</enum_type_isolation_id>
        </donnee_entree>
      </mur>
    </mur_collection>
    <plancher_bas_collection>
      <plancher_bas>
        <donnee_entree>
          <reference>pb-1</reference>
          <enum_type_adjacence_id>1</enum_type_adjacence_id>
          <enum_type_isolation_id>2</enum_type_isolation_id>
        </donnee_entree>
      </plancher_bas>
    </plancher_bas_collection>
    <pont_thermique_collection>
      <pont_thermique>
        <donnee_entree>
          <reference_1>pb-1</reference_1>
          <reference_2>mur-1</reference_2>
          <tv_pont_thermique_id>7</tv_pont_thermique_id>
          <enum_methode_saisie_pont_thermique_id>1</enum_methode_saisie_pont_thermique_id>
          <enum_type_liaison_id>%d</enum_type_liaison_id>
        </donnee_entree>
      </pont_thermique>
    </pont_thermique_collection>
  </enveloppe>
</logement>
XML, $adjacency, $liaison));

        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $pont = $document->getElementsByTagName('pont_thermique')->item(0);
        self::assertInstanceOf(DOMElement::class, $pont);

        (new KCalculator())->calculate($pont, $context);

        self::assertSame($expected, $document->getElementsByTagName('k')->item(0)?->textContent);
    }

    public function testKeepsMenuiserieJunctionOnCirculationWall(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement>
  <enveloppe>
    <mur><donnee_entree><reference>mur-1</reference><enum_type_adjacence_id>15</enum_type_adjacence_id></donnee_entree></mur>
    <porte><donnee_entree><reference>porte-1</reference><enum_type_adjacence_id>15</enum_type_adjacence_id></donnee_entree></porte>
    <pont_thermique>
      <donnee_entree>
        <reference_1>porte-1</reference_1><reference_2>mur-1</reference_2>
        <tv_pont_thermique_id>76</tv_pont_thermique_id>
        <enum_methode_saisie_pont_thermique_id>1</enum_methode_saisie_pont_thermique_id>
        <enum_type_liaison_id>5</enum_type_liaison_id>
      </donnee_entree>
    </pont_thermique>
  </enveloppe>
</logement>
XML);

        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $pont = $document->getElementsByTagName('pont_thermique')->item(0);
        self::assertInstanceOf(DOMElement::class, $pont);

        (new KCalculator())->calculate($pont, $context);

        self::assertSame('0.25', $document->getElementsByTagName('k')->item(0)?->textContent);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function paroiWeightCases(): iterable
    {
        yield 'deux parois légères' => ['0', '0', '0'];
        yield 'plancher léger et mur lourd' => ['0', '1', '0.31'];
        yield 'plancher lourd et mur léger' => ['1', '0', '0.31'];
        yield 'nature du mur inconnue conservée' => ['1', '', '0.31'];
    }

    #[DataProvider('paroiWeightCases')]
    public function testNeglectsJunctionOnlyWhenBothOpaqueParoisAreExplicitlyLightweight(
        string $plancherLourd,
        string $murLourd,
        string $expected,
    ): void {
        $document = new DOMDocument();
        $document->loadXML(sprintf(<<<'XML'
<dpe version="0.1.0"><logement><enveloppe>
  <plancher_bas><donnee_entree><reference>pb</reference><paroi_lourde>%s</paroi_lourde></donnee_entree></plancher_bas>
  <mur><donnee_entree><reference>mur</reference><paroi_lourde>%s</paroi_lourde></donnee_entree></mur>
  <pont_thermique><donnee_entree>
    <reference_1>pb</reference_1><reference_2>mur</reference_2>
    <tv_pont_thermique_id>5</tv_pont_thermique_id>
    <enum_methode_saisie_pont_thermique_id>1</enum_methode_saisie_pont_thermique_id>
    <enum_type_liaison_id>1</enum_type_liaison_id>
  </donnee_entree></pont_thermique>
</enveloppe></logement></dpe>
XML, $plancherLourd, $murLourd));

        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $pont = $document->getElementsByTagName('pont_thermique')->item(0);
        self::assertInstanceOf(DOMElement::class, $pont);

        (new KCalculator())->calculate($pont, $context);

        self::assertSame($expected, $document->getElementsByTagName('k')->item(0)?->textContent);
    }

    public function testKeepsLowFloorJunctionBetweenLightweightParoisInVersion2(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<dpe version="2"><logement><enveloppe>
  <plancher_bas><donnee_entree><reference>pb</reference><paroi_lourde>0</paroi_lourde></donnee_entree></plancher_bas>
  <mur><donnee_entree><reference>mur</reference><paroi_lourde>0</paroi_lourde></donnee_entree></mur>
  <pont_thermique><donnee_entree>
    <reference_1>pb</reference_1><reference_2>mur</reference_2>
    <tv_pont_thermique_id>7</tv_pont_thermique_id>
    <enum_methode_saisie_pont_thermique_id>1</enum_methode_saisie_pont_thermique_id>
    <enum_type_liaison_id>1</enum_type_liaison_id>
  </donnee_entree></pont_thermique>
</enveloppe></logement></dpe>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $pont = $document->getElementsByTagName('pont_thermique')->item(0);
        self::assertInstanceOf(DOMElement::class, $pont);

        (new KCalculator())->calculate($pont, $context);

        self::assertSame('0.71', $document->getElementsByTagName('k')->item(0)?->textContent);
    }

    public function testKeepsLowFloorJunctionForStructurallyHeavyFullBrickWall(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<dpe version="0.1.0"><logement><enveloppe>
  <plancher_bas><donnee_entree><reference>pb</reference><paroi_lourde>0</paroi_lourde><enum_type_plancher_bas_id>9</enum_type_plancher_bas_id></donnee_entree></plancher_bas>
  <mur><donnee_entree><reference>mur</reference><paroi_lourde>0</paroi_lourde><enum_materiaux_structure_mur_id>8</enum_materiaux_structure_mur_id><epaisseur_structure>34</epaisseur_structure></donnee_entree></mur>
  <pont_thermique><donnee_entree>
    <reference_1>pb</reference_1><reference_2>mur</reference_2>
    <tv_pont_thermique_id>5</tv_pont_thermique_id>
    <enum_methode_saisie_pont_thermique_id>1</enum_methode_saisie_pont_thermique_id>
    <enum_type_liaison_id>1</enum_type_liaison_id>
  </donnee_entree></pont_thermique>
</enveloppe></logement></dpe>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $pont = $document->getElementsByTagName('pont_thermique')->item(0);
        self::assertInstanceOf(DOMElement::class, $pont);

        (new KCalculator())->calculate($pont, $context);

        self::assertSame('0.31', $document->getElementsByTagName('k')->item(0)?->textContent);
    }

    public function testStillNeglectsFullBrickJunctionWithLightweightFloor(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<dpe version="0.1.0"><logement><enveloppe>
  <plancher_bas><donnee_entree><reference>pb</reference><paroi_lourde>0</paroi_lourde><enum_type_plancher_bas_id>10</enum_type_plancher_bas_id></donnee_entree></plancher_bas>
  <mur><donnee_entree><reference>mur</reference><paroi_lourde>0</paroi_lourde><enum_materiaux_structure_mur_id>8</enum_materiaux_structure_mur_id><epaisseur_structure>34</epaisseur_structure></donnee_entree></mur>
  <pont_thermique><donnee_entree>
    <reference_1>pb</reference_1><reference_2>mur</reference_2>
    <tv_pont_thermique_id>5</tv_pont_thermique_id>
    <enum_methode_saisie_pont_thermique_id>1</enum_methode_saisie_pont_thermique_id>
    <enum_type_liaison_id>1</enum_type_liaison_id>
  </donnee_entree></pont_thermique>
</enveloppe></logement></dpe>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $pont = $document->getElementsByTagName('pont_thermique')->item(0);
        self::assertInstanceOf(DOMElement::class, $pont);

        (new KCalculator())->calculate($pont, $context);

        self::assertSame('0', $document->getElementsByTagName('k')->item(0)?->textContent);
    }

    public function testKeepsHighFloorJunctionBetweenLightweightParois(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement><enveloppe>
  <plancher_haut><donnee_entree><reference>ph</reference><paroi_lourde>0</paroi_lourde></donnee_entree></plancher_haut>
  <mur><donnee_entree><reference>mur</reference><paroi_lourde>0</paroi_lourde></donnee_entree></mur>
  <pont_thermique><donnee_entree><reference_1>ph</reference_1><reference_2>mur</reference_2>
    <tv_pont_thermique_id>36</tv_pont_thermique_id><enum_methode_saisie_pont_thermique_id>1</enum_methode_saisie_pont_thermique_id>
    <enum_type_liaison_id>3</enum_type_liaison_id>
  </donnee_entree></pont_thermique>
</enveloppe></logement>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $pont = $document->getElementsByTagName('pont_thermique')->item(0);
        self::assertInstanceOf(DOMElement::class, $pont);

        (new KCalculator())->calculate($pont, $context);

        self::assertSame('0.3', $document->getElementsByTagName('k')->item(0)?->textContent);
    }
}
