<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * §3.2.2 p.20 : un plancher bas dont la résistance d'isolant est saisie et
 * justifiée vaut `upb = 1 / (1/upb0 + R)`. Publier `upb = upb0` tout en
 * déclarant cet isolant est une contradiction interne de la référence.
 */
final class IsolantPlancherBasDeclareTest extends TestCase
{
    private function document(string $upb, string $methode, string $isolation, string $resistance): DOMDocument
    {
        $doc = new DOMDocument();
        $doc->loadXML(sprintf(<<<'XML'
<dpe><logement><enveloppe><plancher_bas_collection><plancher_bas>
  <donnee_entree>
    <enum_methode_saisie_u_id>%s</enum_methode_saisie_u_id>
    <enum_type_isolation_id>%s</enum_type_isolation_id>
    <resistance_isolation>%s</resistance_isolation>
  </donnee_entree>
  <donnee_intermediaire><upb0>2</upb0><upb>%s</upb></donnee_intermediaire>
</plancher_bas></plancher_bas_collection></enveloppe></logement></dpe>
XML, $methode, $isolation, $resistance, $upb));

        return $doc;
    }

    public function testUpbEgalUpb0MalgreUnIsolantDeclareEstImputeALaReference(): void
    {
        $suspects = ReferenceDefects::detect([], $this->document('2', '5', '4', '3.65'));

        self::assertArrayHasKey('upb', $suspects);
        self::assertStringContainsString('3.65', $suspects['upb']);
    }

    /** La valeur isolée attendue par §3.2.2 ne déclenche rien. */
    public function testUpbIsoleNEstPasSuspect(): void
    {
        $suspects = ReferenceDefects::detect([], $this->document('0.241', '5', '4', '3.65'));

        self::assertArrayNotHasKey('upb', $suspects);
    }

    /** Un plancher déclaré non isolé publie légitimement upb = upb0. */
    public function testPlancherNonIsoleNEstPasSuspect(): void
    {
        $suspects = ReferenceDefects::detect([], $this->document('2', '5', '2', '3.65'));

        self::assertArrayNotHasKey('upb', $suspects);
    }

    /**
     * §3.2.2 p.20 ne rend `upb = upb0` que sur la branche « Isolation ? Non ».
     * La contradiction tient donc au seul type d'isolation déclaré, quelle que
     * soit la méthode de saisie — 2459E4183923N la montre en saisie
     * forfaitaire par année d'isolation (méthode 7).
     */
    public function testUnPlancherIsoleEnSaisieForfaitaireEstAussiSuspect(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement><enveloppe><plancher_bas_collection><plancher_bas>
  <donnee_entree>
    <enum_methode_saisie_u_id>7</enum_methode_saisie_u_id>
    <enum_type_isolation_id>3</enum_type_isolation_id>
  </donnee_entree>
  <donnee_intermediaire><upb0>2</upb0><upb>2</upb></donnee_intermediaire>
</plancher_bas></plancher_bas_collection></enveloppe></logement></dpe>
XML);

        $suspects = ReferenceDefects::detect([], $doc);

        self::assertArrayHasKey('upb', $suspects);
        self::assertStringContainsString('Isolation ? Non', $suspects['upb']);
    }

    /** Un plancher déclaré isolé et publié sous son upb0 reste conforme. */
    public function testUnPlancherIsoleSousSonUpb0NEstPasSuspect(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement><enveloppe><plancher_bas_collection><plancher_bas>
  <donnee_entree>
    <enum_methode_saisie_u_id>7</enum_methode_saisie_u_id>
    <enum_type_isolation_id>3</enum_type_isolation_id>
  </donnee_entree>
  <donnee_intermediaire><upb0>2</upb0><upb>0.5</upb></donnee_intermediaire>
</plancher_bas></plancher_bas_collection></enveloppe></logement></dpe>
XML);

        self::assertArrayNotHasKey('upb', ReferenceDefects::detect([], $doc));
    }
}
