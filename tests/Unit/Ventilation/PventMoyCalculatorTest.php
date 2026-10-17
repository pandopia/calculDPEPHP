<?php

declare(strict_types=1);

namespace Tests\Unit\Ventilation;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Ventilation\PventMoyCalculator;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

/**
 * §5 p.50 — puissance moyenne des auxiliaires de ventilation.
 */
final class PventMoyCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function pventMoy(int $typeId, int $mode, float $surface, int $post2012): float
    {
        $doc = new DOMDocument();
        $doc->loadXML(sprintf(<<<'XML'
<logement>
  <caracteristique_generale>
    <enum_methode_application_dpe_log_id>%d</enum_methode_application_dpe_log_id>
  </caracteristique_generale>
  <ventilation_collection><ventilation><donnee_entree>
    <enum_type_ventilation_id>%d</enum_type_ventilation_id>
    <surface_ventile>%s</surface_ventile>
    <ventilation_post_2012>%d</ventilation_post_2012>
  </donnee_entree></ventilation></ventilation_collection>
</logement>
XML, $mode, $typeId, $surface, $post2012));

        $node = $doc->getElementsByTagName('ventilation')->item(0);
        self::assertInstanceOf(DOMElement::class, $node);

        (new PventMoyCalculator())->calculate($node, new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        ));

        return (float) $doc->getElementsByTagName('pvent_moy')->item(0)->textContent;
    }

    /**
     * §5 p.50 : « Les puissances d'auxiliaires des VMC basse pression sont les
     * mêmes que pour les VMC classiques », et le tableau en tabule deux
     * colonnes. Le libellé XSD de ces types ne portant pas l'époque, c'est
     * `ventilation_post_2012` qui la désigne — 2400E0855774N publie 15 W.
     */
    public function testVmcBassePressionApres2012PrendLaColonneRecente(): void
    {
        self::assertEqualsWithDelta(15.0, $this->pventMoy(18, 1, 53.96, 1), 1e-9);
    }

    /** Le même type avant 2012 garde 50 W — 2400E1561131Q. */
    public function testVmcBassePressionAvant2012GardeLaColonneAncienne(): void
    {
        self::assertEqualsWithDelta(50.0, $this->pventMoy(18, 1, 53.96, 0), 1e-9);
    }

    /** En immeuble, la colonne joue sur Pvent : 0,25 au lieu de 0,46. */
    public function testVmcBassePressionEnImmeubleSuitLaMemeColonne(): void
    {
        $recent = $this->pventMoy(18, 5, 70.46, 1);
        $ancien = $this->pventMoy(18, 5, 70.46, 0);

        self::assertEqualsWithDelta(0.25 * 1.24 * 70.46, $recent, 1e-9);
        self::assertEqualsWithDelta(0.46 * 1.24 * 70.46, $ancien, 1e-9);
    }

    /**
     * Un puits climatique **sans** échangeur n'extrait que l'air vicié : c'est
     * un simple flux, 65 W jusqu'à 2012 — 2401E1474278E.
     */
    public function testPuitsClimatiqueSansEchangeurEstUnSimpleFlux(): void
    {
        self::assertEqualsWithDelta(65.0, $this->pventMoy(35, 1, 156.0, 0), 1e-9);
    }

    /** Celui **avec** échangeur souffle et extrait : double flux, 80 W. */
    public function testPuitsClimatiqueAvecEchangeurEstUnDoubleFlux(): void
    {
        self::assertEqualsWithDelta(80.0, $this->pventMoy(37, 1, 142.0, 0), 1e-9);
    }

    /** Le drapeau n'a pas d'effet sur un type dont le libellé porte l'époque. */
    public function testLeDrapeauNeToucheQueLesBassePression(): void
    {
        self::assertEqualsWithDelta(
            $this->pventMoy(20, 1, 100.0, 0),
            $this->pventMoy(20, 1, 100.0, 1),
            1e-9,
        );
    }
}
