<?php

declare(strict_types=1);

namespace Tests\Unit\Engine;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Chauffage\GenerateurChAlias;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CombustionSansTablePipelineTest extends TestCase
{
    #[DataProvider('types')]
    public function testLeTypeEtLaPuissanceSuffisentAuParcoursCompletSansIdentifiantExterne(int $type, int $energy, int $emitter): void
    {
        $root = dirname(__DIR__, 3);
        $document = new DOMDocument();
        $document->load($root . '/tests/Fixtures/comparatif-collectif.xml');
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//reference_generateur_mixte | //data_complementaires | //identifiant_reseau_chaleur[normalize-space(.)=""]') as $node) { $node->parentNode->removeChild($node); }
        $accessor = new NodeAccessor($document);
        $accessor->setChildValue($document->getElementsByTagName('administratif')->item(0), 'enum_version_id', '2.6');
        $gen = $document->getElementsByTagName('generateur_chauffage')->item(0);
        $entry = $xpath->query('./donnee_entree', $gen)->item(0);
        foreach ($xpath->query('./tv_generateur_combustion_id', $entry) as $id) { $entry->removeChild($id); }
        $accessor->setChildValue($entry, 'enum_type_generateur_ch_id', $type);
        $accessor->setChildValue($entry, 'enum_type_energie_id', $energy);
        $accessor->setChildValue($entry, 'enum_usage_generateur_id', 1);
        $accessor->setChildValue($entry, 'enum_methode_saisie_carac_sys_id', 2);
        $accessor->setChildValue($accessor->ensureDonneeIntermediaire($gen), 'pn', 18000);
        foreach ($xpath->query('//emetteur_chauffage/donnee_entree') as $entry) { $accessor->setChildValue($entry, 'enum_type_emission_distribution_id', $emitter); }
        $result = new DOMDocument();
        $result->loadXML(CalculDpePHP::calculate($document->saveXML()));
        $a = new NodeAccessor($result);
        $output = $result->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertSame(18000.0, $a->getFloatOrNull('./donnee_intermediaire/pn', $output));
        foreach (['rpn', 'rendement_generation', 'conso_ch', 'conso_ch_depensier'] as $field) {
            $value = $a->getFloatOrNull('./donnee_intermediaire/' . $field, $output);
            self::assertNotNull($value, "type $type : $field absent");
            self::assertTrue(is_finite($value) && $value > 0, "type $type : $field invalide");
        }
        $normalised = GenerateurChAlias::normalizeNode($type, $gen);
        if (!in_array($normalised, [53, 54], true)) {
            self::assertGreaterThan(0, $a->getFloatOrNull('./donnee_intermediaire/rpint', $output));
            self::assertGreaterThan(0, $a->getFloatOrNull('./donnee_intermediaire/qp0', $output));
        }
        self::assertSame(0, (new DOMXPath($result))->query('//generateur_chauffage/donnee_entree/tv_generateur_combustion_id')->length);
    }

    public static function types(): iterable
    {
        foreach (range(50, 74) as $type) {
            $energy = $type <= 54 ? 2 : ($type >= 69 ? 5 : ($type >= 62 ? 6 : 4));
            yield (string)$type => [$type, $energy, $type <= 52 ? 5 : ($type <= 54 ? 23 : 14)];
        }
        yield 'autre combustion bois' => [115, 4, 14];
        foreach (range(120, 126) as $type) { yield (string)$type => [$type, 11, 14]; }
        foreach ([140, 141, 152, 153, 154, 155, 156, 157, 158, 159] as $type) { yield (string)$type => [$type, in_array($type, [140, 141, 152, 153], true) ? 5 : 4, 14]; }
    }
}
