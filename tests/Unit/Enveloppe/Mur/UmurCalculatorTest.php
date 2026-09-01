<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\Mur;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\Mur\Umur0Calculator;
use CalculDpePHP\Enveloppe\Mur\UmurCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UmurCalculatorTest extends TestCase
{
    /** @return iterable<string, array{int, float}> */
    public static function methods(): iterable
    {
        yield 'présence isolation inconnue conserve période avant 1975' => [2, 2.5];
        yield 'isolation avérée sans année applique convention 1975-1977' => [8, 1.0];
    }

    #[DataProvider('methods')]
    public function testConstructionBefore1975DependsOnIsolationKnowledge(int $method, float $expected): void
    {
        $document = new DOMDocument();
        $document->loadXML(sprintf(<<<'XML'
<logement><mur><donnee_entree>
  <enum_methode_saisie_u0_id>1</enum_methode_saisie_u0_id>
  <enum_materiaux_structure_mur_id>1</enum_materiaux_structure_mur_id>
  <enum_type_doublage_id>2</enum_type_doublage_id>
  <enum_type_isolation_id>1</enum_type_isolation_id>
  <enum_methode_saisie_u_id>%d</enum_methode_saisie_u_id>
</donnee_entree></mur></logement>
XML, $method));
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(__DIR__ . '/../../../../resources/tables'),
            zoneGroupe: 'H1',
            energieChauffagePrincipale: 'autres',
            periodeConstructionId: 1,
        );
        $mur = $document->getElementsByTagName('mur')->item(0);

        (new Umur0Calculator())->calculate($mur, $context);
        (new UmurCalculator())->calculate($mur, $context);

        self::assertEqualsWithDelta($expected, (float)$document->getElementsByTagName('umur')->item(0)?->textContent, 1e-9);
    }
}
