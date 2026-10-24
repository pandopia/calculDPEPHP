<?php

declare(strict_types=1);

namespace Tests\Unit\Engine;

use CalculDpePHP\CalculDpePHP;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Puissance saisie du modèle 2.7 : purge → puissance → consommation → agrégats. */
final class VentilationSaisiePipelineTest extends TestCase
{
    #[DataProvider('powers')]
    public function testLaPuissanceDocumenteeSurvitAuPipelineEtALaRelance(string $format, int $method, float $power): void
    {
        $root = dirname(__DIR__, 3);
        $document = new DOMDocument();
        $document->load($root . '/tests/Fixtures/comparatif-collectif.xml');
        $document->documentElement->setAttribute('version', $format);
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//data_complementaires') as $extension) { $extension->parentNode->removeChild($extension); }
        $this->set($document->getElementsByTagName('administratif')->item(0), 'enum_version_id', '2.7');
        $this->set($document->getElementsByTagName('administratif')->item(0), 'date_etablissement_dpe', '2027-01-01');
        $general = $document->getElementsByTagName('caracteristique_generale')->item(0);
        $this->set($general, 'enum_methode_application_dpe_log_id', '5');
        foreach (iterator_to_array($general->childNodes) as $node) {
            if ($node instanceof DOMElement && $node->tagName === 'surface_habitable_logement') { $general->removeChild($node); }
        }
        foreach ($document->getElementsByTagName('ventilation') as $ventilation) {
            $entry = $xpath->query('./donnee_entree', $ventilation)->item(0);
            $this->set($entry, 'enum_type_ventilation_id', '6');
            $this->set($entry, 'enum_methode_saisie_pvent_moy_id', (string)$method);
            $di = $xpath->query('./donnee_intermediaire', $ventilation)->item(0);
            if (!$di instanceof DOMElement) { $di = $ventilation->appendChild($document->createElement('donnee_intermediaire')); }
            $this->set($di, 'pvent_moy', (string)$power);
        }
        $first = CalculDpePHP::calculate($document->saveXML());
        $second = CalculDpePHP::calculate($first);
        self::assertSame($first, $second);
        $result = new DOMDocument();
        $result->loadXML($first);
        $xr = new DOMXPath($result);
        $count = $xr->evaluate('count(//ventilation)');
        self::assertGreaterThan(0, $count);
        self::assertEqualsWithDelta($power * $count, $xr->evaluate('sum(//ventilation/donnee_intermediaire/pvent_moy)'), 1e-9);
        self::assertEqualsWithDelta(8.76 * $power * $count, $xr->evaluate('number(//sortie/ef_conso/conso_auxiliaire_ventilation)'), 1e-9);

        // Déclaration exacte du bloc ventilation 2.7, y compris ses entrées,
        // pour ne pas valider uniquement la présence d'un nombre hors schéma.
        $schema = new DOMDocument();
        $schema->load($root . '/editeur/public/schemas/DPEv2.7.xsd');
        $xs = new DOMXPath($schema);
        $declaration = $xs->query('(//xs:element[@name="ventilation"])[1]')->item(0)->cloneNode(true);
        $declaration->removeAttribute('minOccurs');
        $declaration->removeAttribute('maxOccurs');
        $schema->documentElement->appendChild($declaration);
        foreach ($result->getElementsByTagName('ventilation') as $ventilation) {
            $single = new DOMDocument();
            $single->appendChild($single->importNode($ventilation, true));
            $previous = libxml_use_internal_errors(true);
            libxml_clear_errors();
            $valid = $single->schemaValidateSource($schema->saveXML());
            $errors = implode("\n", array_map(static fn($error) => trim($error->message), libxml_get_errors()));
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            self::assertTrue($valid, $errors);
        }
    }

    public static function powers(): iterable
    {
        foreach (['2', '0.1.0'] as $format) {
            foreach ([2, 3] as $method) {
                foreach ([0.0, 39.5, 125.75] as $power) { yield "$format/$method/$power" => [$format, $method, $power]; }
            }
        }
    }

    private function set(DOMElement $parent, string $tag, string $value): void
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $tag) { $child->textContent = $value; return; }
        }
        $parent->appendChild($parent->ownerDocument->createElement($tag, $value));
    }
}
