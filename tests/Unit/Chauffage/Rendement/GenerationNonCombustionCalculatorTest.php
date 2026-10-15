<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpePHP\Chauffage\Rendement\GenerationNonCombustionCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * §12.4.2 p.80 : le SCOP par défaut d'une PAC se lit sur deux colonnes selon le
 * type d'émetteur — « Autres » et « Planchers / Plafonds » — et sur la zone
 * climatique.
 */
final class GenerationNonCombustionCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';

    private function calcule(int $genId, int $typeEmetteur, string $zone): float
    {
        $document = new DOMDocument();
        $document->loadXML(sprintf(<<<'XML'
<logement>
  <installation_chauffage_collection><installation_chauffage>
    <emetteur_chauffage_collection><emetteur_chauffage><donnee_entree>
      <enum_type_emission_distribution_id>%d</enum_type_emission_distribution_id>
    </donnee_entree></emetteur_chauffage></emetteur_chauffage_collection>
    <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
      <enum_type_generateur_ch_id>%d</enum_type_generateur_ch_id>
    </donnee_entree></generateur_chauffage></generateur_chauffage_collection>
  </installation_chauffage></installation_chauffage_collection>
</logement>
XML, $typeEmetteur, $genId));

        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: $zone,
        );
        $generateur = $document->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $generateur);

        (new GenerationNonCombustionCalculator())->calculate($generateur, $context);

        return (float) $document->getElementsByTagName('scop')->item(0)?->textContent;
    }

    /** @return iterable<string, array{int, int, string, float}> */
    public static function scopParEmetteur(): iterable
    {
        // PAC air/eau à partir de 2017, zone H1/H2 : 2,8 ou 3,2.
        yield 'air/eau, radiateur bitube' => [7, 39, '2', 2.8];
        yield 'air/eau, plancher chauffant' => [7, 14, '2', 3.2];
        yield 'air/eau, plafond chauffant' => [7, 18, '2', 3.2];
        // Même PAC en zone H3 : 3,2 ou 3,8.
        yield 'air/eau en H3, radiateur' => [7, 39, '8', 3.2];
        yield 'air/eau en H3, plancher' => [7, 14, '8', 3.8];
        // PAC air/air : colonne unique, l'émetteur ne change rien.
        yield 'air/air, plancher' => [3, 14, '2', 3.0];
        yield 'air/air, radiateur' => [3, 39, '2', 3.0];
    }

    #[DataProvider('scopParEmetteur')]
    public function testScopSuitLeTypeDEmetteur(int $genId, int $emetteur, string $zone, float $attendu): void
    {
        self::assertEqualsWithDelta($attendu, $this->calcule($genId, $emetteur, $zone), 1e-9);
    }

    /**
     * Une installation mixte — un plancher chauffant et des radiateurs — relève
     * de la colonne « Planchers / Plafonds ».
     */
    public function testUnSeulPlancherSuffitPourLaColonnePlancher(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement>
  <installation_chauffage_collection><installation_chauffage>
    <emetteur_chauffage_collection>
      <emetteur_chauffage><donnee_entree>
        <enum_type_emission_distribution_id>39</enum_type_emission_distribution_id>
      </donnee_entree></emetteur_chauffage>
      <emetteur_chauffage><donnee_entree>
        <enum_type_emission_distribution_id>14</enum_type_emission_distribution_id>
      </donnee_entree></emetteur_chauffage>
    </emetteur_chauffage_collection>
    <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
      <enum_type_generateur_ch_id>7</enum_type_generateur_ch_id>
    </donnee_entree></generateur_chauffage></generateur_chauffage_collection>
  </installation_chauffage></installation_chauffage_collection>
</logement>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '2',
        );
        $generateur = $document->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $generateur);

        (new GenerationNonCombustionCalculator())->calculate($generateur, $context);

        self::assertEqualsWithDelta(3.2, (float) $document->getElementsByTagName('scop')->item(0)?->textContent, 1e-9);
    }

    /** Un SCOP saisi en donnee_entree prime sur la table. */
    public function testLeScopSaisiPrimeSurLaTable(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement><installation_chauffage_collection><installation_chauffage>
  <emetteur_chauffage_collection><emetteur_chauffage><donnee_entree>
    <enum_type_emission_distribution_id>14</enum_type_emission_distribution_id>
  </donnee_entree></emetteur_chauffage></emetteur_chauffage_collection>
  <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
    <enum_type_generateur_ch_id>7</enum_type_generateur_ch_id><scop>4.1</scop>
  </donnee_entree></generateur_chauffage></generateur_chauffage_collection>
</installation_chauffage></installation_chauffage_collection></logement>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '2',
        );
        $generateur = $document->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $generateur);

        (new GenerationNonCombustionCalculator())->calculate($generateur, $context);

        self::assertEqualsWithDelta(4.1, (float) $document->getElementsByTagName('scop')->item(1)?->textContent, 1e-9);
    }
}
