<?php

declare(strict_types=1);

namespace Tests\Unit\Engine;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Common\IntermediateEnergyUnit;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CetGazPipelineTest extends TestCase
{
    #[DataProvider('performances')]
    public function testLeCopGazJustifieAlimenteLesConsommationsEtLeBilanEnr(string $format, string $model, float $cop): void
    {
        $root = dirname(__DIR__, 3);
        $document = new DOMDocument();
        $document->load($root . '/tests/Fixtures/comparatif-collectif.xml');
        $document->documentElement->setAttribute('version', $format);
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//reference_generateur_mixte | //data_complementaires | //identifiant_reseau_chaleur[normalize-space(.)=""]') as $node) { $node->parentNode->removeChild($node); }
        $accessor = new NodeAccessor($document);
        $admin = $document->getElementsByTagName('administratif')->item(0);
        $accessor->setChildValue($admin, 'enum_version_id', $model);
        $accessor->setChildValue($admin, 'date_etablissement_dpe', $model === '2.7' ? '2027-01-01' : '2026-10-24');
        foreach ($document->getElementsByTagName('generateur_ecs') as $gen) {
            $entry = $xpath->query('./donnee_entree', $gen)->item(0);
            $accessor->setChildValue($entry, 'enum_type_generateur_ecs_id', 83);
            $accessor->setChildValue($entry, 'enum_type_energie_id', 2);
            $accessor->setChildValue($entry, 'enum_usage_generateur_id', 2);
            $accessor->setChildValue($entry, 'enum_methode_saisie_carac_sys_id', 6);
            $accessor->setChildValue($entry, 'enum_type_stockage_ecs_id', 3);
            $accessor->setChildValue($entry, 'volume_stockage', 200);
            if ($model === '2.7') { $accessor->setChildValue($entry, 'enum_periode_installation_ecs_thermo_id', 4); }
            $accessor->setChildValue($accessor->ensureDonneeIntermediaire($gen), 'cop', $cop);
        }
        $first = CalculDpePHP::calculate($document->saveXML());
        self::assertSame($first, CalculDpePHP::calculate($first));
        $result = new DOMDocument();
        $result->loadXML($first);
        $a = new NodeAccessor($result);
        $xr = new DOMXPath($result);
        $installation = $result->getElementsByTagName('installation_ecs')->item(0);
        $rd = $a->getFloatOrNull('./donnee_intermediaire/rendement_distribution', $installation);
        $becs = $a->getFloatOrNull('./donnee_intermediaire/besoin_ecs', $installation) / IntermediateEnergyUnit::xmlPerKwh($result);
        $becsDep = $a->getFloatOrNull('./donnee_intermediaire/besoin_ecs_depensier', $installation) / IntermediateEnergyUnit::xmlPerKwh($result);
        $conso = $becs / ($rd * $cop);
        self::assertEqualsWithDelta($conso, $xr->evaluate('number(//generateur_ecs/donnee_intermediaire/conso_ecs)'), 1e-8);
        self::assertEqualsWithDelta($becsDep / ($rd * $cop), $xr->evaluate('number(//generateur_ecs/donnee_intermediaire/conso_ecs_depensier)'), 1e-8);
        self::assertEqualsWithDelta($cop, $xr->evaluate('number(//generateur_ecs/donnee_intermediaire/cop)'), 1e-12);
        foreach (['rendement_generation', 'rendement_generation_stockage', 'rendement_stockage', 'pn', 'qp0'] as $tag) { self::assertSame(0.0, $xr->evaluate('count(//generateur_ecs/donnee_intermediaire/' . $tag . ')')); }
        if ($model === '2.7') {
            self::assertEqualsWithDelta($conso * max(0, $cop - 1), $xr->evaluate('number(//prod_enr_chaleur_ambiante_cet)'), 1e-8);
            self::assertSame('0', $a->getStringOrNull('//mention_a0'), 'Un COP positif ne rend pas le gaz non fossile.');
        } else {
            self::assertSame(0.0, $xr->evaluate('count(//production_enr)'));
        }

        $schema = new DOMDocument();
        $schema->load($root . '/editeur/public/schemas/' . ($model === '2.7' ? 'DPEv2.7.xsd' : 'DPEv2.6.xsd'));
        $xs = new DOMXPath($schema);
        $declaration = $xs->query('(//xs:element[@name="generateur_ecs"])[1]')->item(0)->cloneNode(true);
        $declaration->removeAttribute('minOccurs');
        $declaration->removeAttribute('maxOccurs');
        $schema->documentElement->appendChild($declaration);
        foreach ($result->getElementsByTagName('generateur_ecs') as $gen) {
            $single = new DOMDocument();
            $single->appendChild($single->importNode($gen, true));
            $previous = libxml_use_internal_errors(true);
            libxml_clear_errors();
            $valid = $single->schemaValidateSource($schema->saveXML());
            $errors = implode("\n", array_map(static fn($error) => trim($error->message), libxml_get_errors()));
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            self::assertTrue($valid, $errors);
        }
    }

    public static function performances(): iterable
    {
        foreach (['2', '0.1.0'] as $format) {
            foreach (['2.6', '2.7'] as $model) {
                foreach ([1.001, 1.5, 8.0] as $cop) { yield "$format/$model/$cop" => [$format, $model, $cop]; }
            }
        }
    }
}
