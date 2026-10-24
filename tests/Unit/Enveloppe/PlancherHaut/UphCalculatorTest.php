<?php

declare(strict_types=1);

namespace Tests\Unit\Enveloppe\PlancherHaut;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\PlancherHaut\UphCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class UphCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';

    public static function anneesConventionnellesIsolation(): iterable
    {
        foreach ([1, 2] as $construction) {
            foreach (['H1' => [0.50, 0.75], 'H2' => [0.53, 0.79], 'H3' => [0.56, 0.83]] as $zone => [$comble, $terrasse]) {
                foreach (['joule', 'autres'] as $energie) {
                    yield "$construction $zone $energie combles" => [$construction, $zone, $energie, 11, $comble];
                    yield "$construction $zone $energie terrasse" => [$construction, $zone, $energie, 7, $terrasse];
                }
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('anneesConventionnellesIsolation')]
    public function testIsolationSansAnneeAvant1975Utilise1975A1977(int $construction, string $zone, string $energie, int $adjacence, float $attendu): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<plancher_haut><donnee_entree><enum_methode_saisie_u_id>8</enum_methode_saisie_u_id><enum_type_isolation_id>9</enum_type_isolation_id><enum_type_adjacence_id>' . $adjacence . '</enum_type_adjacence_id></donnee_entree><donnee_intermediaire><uph0>2.5</uph0></donnee_intermediaire></plancher_haut>');
        $ctx = new CalculationContext($doc, new TableRepository(self::PROJECT_ROOT . '/resources/tables'), zoneGroupe: $zone, energieChauffagePrincipale: $energie, periodeConstructionId: $construction);
        (new UphCalculator())->calculate($doc->documentElement, $ctx);
        self::assertEqualsWithDelta($attendu, (float)$doc->getElementsByTagName('uph')->item(0)->textContent, 1e-9);
    }

    public function testLeRepliNeRemplaceNiUneAnneeExpliciteNiUneIsolationInconnue(): void
    {
        foreach ([[8, 1, 1, 9, 2.5], [8, 1, 7, 9, 0.23], [2, 1, null, 1, 2.5], [8, null, null, 9, 2.5], [8, 1, null, 2, 2.5], [8, 7, null, 9, 0.23]] as [$methode, $construction, $isolation, $type, $attendu]) {
            $doc = new DOMDocument();
            $periode = $isolation === null ? '' : '<enum_periode_isolation_id>' . $isolation . '</enum_periode_isolation_id>';
            $doc->loadXML('<plancher_haut><donnee_entree><enum_methode_saisie_u_id>' . $methode . '</enum_methode_saisie_u_id><enum_type_isolation_id>' . $type . '</enum_type_isolation_id><enum_type_adjacence_id>11</enum_type_adjacence_id>' . $periode . '</donnee_entree><donnee_intermediaire><uph0>2.5</uph0></donnee_intermediaire></plancher_haut>');
            $ctx = new CalculationContext($doc, new TableRepository(self::PROJECT_ROOT . '/resources/tables'), zoneGroupe: 'H1', energieChauffagePrincipale: 'joule', periodeConstructionId: $construction);
            (new UphCalculator())->calculate($doc->documentElement, $ctx);
            self::assertEqualsWithDelta($attendu, (float)$doc->getElementsByTagName('uph')->item(0)->textContent, 1e-9);
        }
    }

    public function testTypeEightAboveHeatedNonLossyRoomUsesTerraceTable(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<plancher_haut><donnee_entree><enum_type_adjacence_id>22</enum_type_adjacence_id><enum_type_plancher_haut_id>8</enum_type_plancher_haut_id><enum_type_isolation_id>9</enum_type_isolation_id><enum_methode_saisie_u_id>2</enum_methode_saisie_u_id></donnee_entree><donnee_intermediaire><uph0>2.5</uph0></donnee_intermediaire></plancher_haut>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneGroupe: 'H2',
            energieChauffagePrincipale: 'autres',
            periodeConstructionId: 6,
        );

        (new UphCalculator())->calculate($document->documentElement, $context);

        self::assertSame('0.42', $document->getElementsByTagName('uph')->item(0)->textContent);
    }

    public static function locauxHorsCombles(): iterable
    {
        foreach ([4, 6, 7, 8, 9, 10, 14, 15, 16, 17, 18, 19, 20, 21] as $adjacence) {
            foreach (['H1' => [0.35, 0.40], 'H2' => [0.37, 0.42], 'H3' => [0.39, 0.44]] as $zone => [$joule, $autres]) {
                foreach (['joule' => $joule, 'autres' => $autres] as $energie => $attendu) {
                    yield "$adjacence $zone $energie" => [$adjacence, $zone, $energie, $attendu];
                }
            }
        }
    }

    /** §3.2.3.1 p.21 : un local non chauffé ou autre qu'habitation utilise Terrasse. */
    #[\PHPUnit\Framework\Attributes\DataProvider('locauxHorsCombles')]
    public function testLocalHorsComblesUtiliseLaCategorieTerrasse(int $adjacence, string $zone, string $energie, float $attendu): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<plancher_haut><donnee_entree><enum_methode_saisie_u_id>7</enum_methode_saisie_u_id><enum_periode_isolation_id>6</enum_periode_isolation_id><enum_type_isolation_id>4</enum_type_isolation_id><enum_type_adjacence_id>' . $adjacence . '</enum_type_adjacence_id><enum_type_plancher_haut_id>8</enum_type_plancher_haut_id></donnee_entree><donnee_intermediaire><uph0>2.5</uph0></donnee_intermediaire></plancher_haut>');
        $ctx = new CalculationContext($doc, new TableRepository(self::PROJECT_ROOT . '/resources/tables'), zoneGroupe: $zone, energieChauffagePrincipale: $energie);
        (new UphCalculator())->calculate($doc->documentElement, $ctx);
        self::assertEqualsWithDelta($attendu, (float)$doc->getElementsByTagName('uph')->item(0)->textContent, 1e-9);
    }

    public function test_la_saisie_justifiee_prime_sur_le_forfait_non_isole(): void
    {
        foreach ([9, 10] as $method) {
            $document = new \DOMDocument();
            $document->loadXML('<plancher_haut><donnee_entree><enum_methode_saisie_u_id>' . $method . '</enum_methode_saisie_u_id><enum_type_isolation_id>2</enum_type_isolation_id><uph_saisi>0.18</uph_saisi><u_saisi>0.99</u_saisi></donnee_entree></plancher_haut>');
            $context = new \CalculDpePHP\Engine\CalculationContext(document: $document, tables: new \CalculDpePHP\Tables\TableRepository(dirname(__DIR__, 4) . '/resources/tables'));
            (new \CalculDpePHP\Enveloppe\PlancherHaut\UphCalculator())->calculate($document->documentElement, $context);
            self::assertSame('0.18', $document->getElementsByTagName('uph')->item(0)->textContent);
        }
    }

}
