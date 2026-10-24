<?php
declare(strict_types=1);
namespace Tests\Unit\Enveloppe\PlancherBas;
final class UpbCalculatorTest extends \PHPUnit\Framework\TestCase
{
    public static function anneesConventionnellesIsolation(): iterable
    {
        foreach ([1, 2] as $construction) {
            foreach (['H1' => 0.90, 'H2' => 0.95, 'H3' => 1.0] as $zone => $attendu) {
                foreach (['joule', 'autres'] as $energie) {
                    yield "$construction $zone $energie" => [$construction, $zone, $energie, $attendu];
                }
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('anneesConventionnellesIsolation')]
    public function testIsolationSansAnneeAvant1975Utilise1975A1977(int $construction, string $zone, string $energie, float $attendu): void
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<plancher_bas><donnee_entree><enum_methode_saisie_u_id>8</enum_methode_saisie_u_id><enum_type_isolation_id>9</enum_type_isolation_id></donnee_entree><donnee_intermediaire><upb0>2</upb0></donnee_intermediaire></plancher_bas>');
        $ctx = new \CalculDpePHP\Engine\CalculationContext($doc, new \CalculDpePHP\Tables\TableRepository(dirname(__DIR__, 4) . '/resources/tables'), zoneGroupe: $zone, energieChauffagePrincipale: $energie, periodeConstructionId: $construction);
        (new \CalculDpePHP\Enveloppe\PlancherBas\UpbCalculator())->calculate($doc->documentElement, $ctx);
        self::assertEqualsWithDelta($attendu, (float)$doc->getElementsByTagName('upb')->item(0)->textContent, 1e-9);
    }

    public function testLeRepliNeRemplaceNiUneAnneeExpliciteNiUneIsolationInconnue(): void
    {
        foreach ([[8, 1, 1, 9, 2.0], [8, 1, 7, 9, 0.3], [2, 1, null, 1, 2.0], [8, null, null, 9, 2.0], [8, 1, null, 2, 2.0], [8, 7, null, 9, 0.3]] as [$methode, $construction, $isolation, $type, $attendu]) {
            $doc = new \DOMDocument();
            $periode = $isolation === null ? '' : '<enum_periode_isolation_id>' . $isolation . '</enum_periode_isolation_id>';
            $doc->loadXML('<plancher_bas><donnee_entree><enum_methode_saisie_u_id>' . $methode . '</enum_methode_saisie_u_id><enum_type_isolation_id>' . $type . '</enum_type_isolation_id>' . $periode . '</donnee_entree><donnee_intermediaire><upb0>2</upb0></donnee_intermediaire></plancher_bas>');
            $ctx = new \CalculDpePHP\Engine\CalculationContext($doc, new \CalculDpePHP\Tables\TableRepository(dirname(__DIR__, 4) . '/resources/tables'), zoneGroupe: 'H1', energieChauffagePrincipale: 'joule', periodeConstructionId: $construction);
            (new \CalculDpePHP\Enveloppe\PlancherBas\UpbCalculator())->calculate($doc->documentElement, $ctx);
            self::assertEqualsWithDelta($attendu, (float)$doc->getElementsByTagName('upb')->item(0)->textContent, 1e-9);
        }
    }

    public function test_la_saisie_justifiee_prime_sur_le_forfait_non_isole(): void
    {
        foreach ([9, 10] as $method) {
            $document = new \DOMDocument();
            $document->loadXML('<plancher_bas><donnee_entree><enum_methode_saisie_u_id>' . $method . '</enum_methode_saisie_u_id><enum_type_isolation_id>2</enum_type_isolation_id><upb_saisi>0.18</upb_saisi><u_saisi>0.99</u_saisi></donnee_entree></plancher_bas>');
            $context = new \CalculDpePHP\Engine\CalculationContext(document: $document, tables: new \CalculDpePHP\Tables\TableRepository(dirname(__DIR__, 4) . '/resources/tables'));
            (new \CalculDpePHP\Enveloppe\PlancherBas\UpbCalculator())->calculate($document->documentElement, $context);
            self::assertSame('0.18', $document->getElementsByTagName('upb')->item(0)->textContent);
        }
    }

}
