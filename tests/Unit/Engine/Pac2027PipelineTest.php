<?php

declare(strict_types=1);

namespace Tests\Unit\Engine;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Conformite\ValueExtractor;
use CalculDpePHP\Conformite\XsdVocabulary;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Pac2027PipelineTest extends TestCase
{
    #[DataProvider('typesRecents')]
    public function testLeParcoursCompletConsommeLesPerformancesRecentes(int $type, float $expectedScop): void
    {
        $root = dirname(__DIR__, 3);
        $doc = new DOMDocument();
        $doc->load($root . '/tests/Fixtures/comparatif-collectif.xml');
        $xp = new DOMXPath($doc);
        // Ce donneur legacy contient des extensions éditeur hors schéma.
        // Le scénario synthétique utilise uniquement les données ADEME.
        foreach ($xp->query('//data_complementaires') as $extension) { $extension->parentNode->removeChild($extension); }
        foreach ($xp->query('//identifiant_reseau_chaleur[normalize-space(.)=""]') as $emptyId) { $emptyId->parentNode->removeChild($emptyId); }
        $this->set($doc->getElementsByTagName('administratif')->item(0), 'enum_version_id', '2.7');
        $this->set($doc->getElementsByTagName('administratif')->item(0), 'date_etablissement_dpe', '2027-01-01');
        foreach ($xp->query('//reference_generateur_mixte') as $reference) { $reference->parentNode->removeChild($reference); }
        $gen = $doc->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $gen);
        if ($type >= 178) {
            $boiler = $gen->cloneNode(true);
            $entry = $xp->query('./donnee_entree', $boiler)->item(0);
            $this->set($entry, 'enum_type_generateur_ch_id', '149');
            $this->set($entry, 'reference', 'chaudiere-hybride-2027');
            $gen->parentNode->appendChild($boiler);
        }
        $entry = $xp->query('./donnee_entree', $gen)->item(0);
        $this->set($entry, 'enum_type_generateur_ch_id', (string)$type);
        $this->set($entry, 'enum_type_energie_id', '1');
        foreach ($xp->query('//emetteur_chauffage/donnee_entree') as $entry) {
            $this->set($entry, 'enum_type_emission_distribution_id', $type === 172 ? '42' : '14');
        }
        foreach ($xp->query('//generateur_ecs/donnee_entree') as $entry) {
            $this->set($entry, 'enum_type_generateur_ecs_id', '136');
            $this->set($entry, 'enum_type_energie_id', '1');
            $this->set($entry, 'volume_stockage', '200');
            $this->set($entry, 'enum_type_stockage_ecs_id', '3');
        }
        foreach ($xp->query('//generateur_chauffage/donnee_entree') as $entry) {
            $this->set($entry, 'chauffage_duree_vie_restante', '20'); // saisie fictive du scénario
        }
        $result = new DOMDocument();
        $result->loadXML(CalculDpePHP::calculate($doc->saveXML()));
        $xr = new DOMXPath($result);
        self::assertEqualsWithDelta($expectedScop, $xr->evaluate('number((//generateur_chauffage)[1]/donnee_intermediaire/scop)'), 1e-12);
        self::assertGreaterThan(0, $xr->evaluate('number((//generateur_chauffage)[1]/donnee_intermediaire/conso_ch)'));
        self::assertSame(0.0, $xr->evaluate('count((//generateur_chauffage)[1]/donnee_intermediaire/rendement_generation)'));
        self::assertEqualsWithDelta(2.7, $xr->evaluate('number((//generateur_ecs)[1]/donnee_intermediaire/cop)'), 1e-12);
        self::assertSame(0.0, $xr->evaluate('count(//generateur_ecs/donnee_intermediaire/rendement_stockage)'));
        self::assertGreaterThan(0, $xr->evaluate('number(//prod_enr_totale)'));
        self::assertSame([], XsdVocabulary::unknownElements((new ValueExtractor())->extract($result), $root . '/editeur/public/schemas/DPEv2.7.xsd'));
        $this->validateTechnologyBlocks($result, $root . '/editeur/public/schemas/DPEv2.7.xsd');
        self::assertSame(CalculDpePHP::calculate($result->saveXML()), CalculDpePHP::calculate($doc->saveXML()));
    }

    public static function typesRecents(): iterable
    {
        foreach ([172 => 3.2, 173 => 3.4, 174 => 3.5, 175 => 3.5, 176 => 3.5, 178 => 3.4, 179 => 3.5, 180 => 3.5, 181 => 3.5] as $type => $scop) {
            yield (string)$type => [$type, $scop];
        }
    }

    private function set(DOMElement $entry, string $name, string $value): void
    {
        foreach ($entry->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $name) { $child->textContent = $value; return; }
        }
        $entry->appendChild($entry->ownerDocument->createElement($name, $value));
    }

    private function validateTechnologyBlocks(DOMDocument $result, string $path): void
    {
        // Valider les sous-blocs techniques avec leurs déclarations EXACTES
        // du XSD, pas un schéma de test simplifié. L'identité administrative
        // volontairement incomplète du donneur n'est pas la cible du test.
        $schema = new DOMDocument();
        $schema->load($path);
        $xs = new DOMXPath($schema);
        foreach (['generateur_chauffage', 'generateur_ecs', 'production_enr'] as $name) {
            $declaration = $xs->query('(//xs:element[@name="' . $name . '"])[1]')->item(0)->cloneNode(true);
            $declaration->removeAttribute('minOccurs');
            $declaration->removeAttribute('maxOccurs');
            $schema->documentElement->appendChild($declaration);
        }
        foreach (['generateur_chauffage', 'generateur_ecs', 'production_enr'] as $name) {
            foreach ($result->getElementsByTagName($name) as $node) {
                $single = new DOMDocument();
                $single->appendChild($single->importNode($node, true));
                $internal = libxml_use_internal_errors(true);
                libxml_clear_errors();
                $valid = $single->schemaValidateSource($schema->saveXML());
                $errors = implode("\n", array_unique(array_map(static fn($error) => trim($error->message), libxml_get_errors())));
                libxml_clear_errors();
                libxml_use_internal_errors($internal);
                self::assertTrue($valid, "$name : $errors");
            }
        }
    }
}
