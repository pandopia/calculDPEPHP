<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpePHP\Chauffage\Rendement\GenerationNonCombustionCalculator;
use CalculDpePHP\Chauffage\Rendement\RendementGenerateur;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
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

    #[DataProvider('scopSaisis')]
    public function testLeScopPreserveDuGenerateurPrimeSurUneFicheGlobale(int $methode, ?float $scop, ?float $scopEntree, float $attendu): void
    {
        $document = new DOMDocument();
        $document->loadXML('<dpe><logement><installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection><generateur_chauffage><donnee_entree>'
            . '<enum_type_generateur_ch_id>7</enum_type_generateur_ch_id><enum_methode_saisie_carac_sys_id>' . $methode . '</enum_methode_saisie_carac_sys_id>'
            . ($scopEntree === null ? '' : '<scop>' . $scopEntree . '</scop>') . '</donnee_entree><donnee_intermediaire>'
            . ($scop === null ? '' : '<scop>' . $scop . '</scop>') . '</donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection></logement>'
            . '<fiche_technique_collection><fiche_technique><enum_categorie_fiche_technique_id>7</enum_categorie_fiche_technique_id><sous_fiche_technique_collection>'
            . '<sous_fiche_technique><description>SCOP / COP: 4,3</description><valeur>4,3</valeur></sous_fiche_technique>'
            . '</sous_fiche_technique_collection></fiche_technique></fiche_technique_collection></dpe>');
        \CalculDpePHP\Xml\OutputPurger::purge($document);
        $generator = $document->getElementsByTagName('generateur_chauffage')->item(0);
        $context = new CalculationContext($document, new TableRepository(self::PROJECT_ROOT . '/resources/tables'), zoneClimatique: '2');

        (new GenerationNonCombustionCalculator())->calculate($generator, $context);

        self::assertEqualsWithDelta($attendu, (new \DOMXPath($document))->evaluate('number(//generateur_chauffage/donnee_intermediaire/scop)'), 1e-9);
    }

    public static function scopSaisis(): iterable
    {
        yield 'saisie propre a cette PAC' => [6, 4.1, null, 4.1];
        yield 'saisie en entree prioritaire' => [6, 4.1, 4.9, 4.9];
        yield 'saisie absente repli fiche' => [6, null, null, 4.3];
        yield 'saisie nulle repli fiche' => [6, 0.0, null, 4.3];
        yield 'saisie negative repli fiche' => [6, -1.0, null, 4.3];
        yield 'ancienne sortie forfaitaire purgee' => [1, 4.1, null, 2.8];
    }

    /**
     * XSD : `scop` est publié « en remplacement du Rg pour les générateurs
     * thermodynamique », et `rendement_generation` vaut « pour tous les
     * générateurs sauf thermodynamique ». Une PAC ne publie donc que son
     * SCOP, et `RendementGenerateur::lire()` le relit à sa place.
     */
    public function testUnePacNePubliePasDeRendementDeGeneration(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement>
  <installation_chauffage_collection><installation_chauffage>
    <emetteur_chauffage_collection><emetteur_chauffage><donnee_entree>
      <enum_type_emission_distribution_id>1</enum_type_emission_distribution_id>
    </donnee_entree></emetteur_chauffage></emetteur_chauffage_collection>
    <generateur_chauffage_collection><generateur_chauffage><donnee_entree>
      <enum_type_generateur_ch_id>7</enum_type_generateur_ch_id>
    </donnee_entree></generateur_chauffage></generateur_chauffage_collection>
  </installation_chauffage></installation_chauffage_collection>
</logement>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '1',
        );
        $generateur = $document->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $generateur);

        (new GenerationNonCombustionCalculator())->calculate($generateur, $context);

        $di = $generateur->getElementsByTagName('donnee_intermediaire')->item(0);
        self::assertSame(0, $di->getElementsByTagName('rendement_generation')->length);
        self::assertSame(1, $di->getElementsByTagName('scop')->length);
        self::assertEqualsWithDelta(
            (float) $di->getElementsByTagName('scop')->item(0)->textContent,
            RendementGenerateur::lire(new NodeAccessor($document), $generateur) ?? 0.0,
            1e-9,
        );
    }
}
