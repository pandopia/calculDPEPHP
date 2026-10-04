<?php
declare(strict_types=1);
namespace Tests\Unit\Enveloppe\PlancherBas;
final class UpbCalculatorTest extends \PHPUnit\Framework\TestCase
{

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
