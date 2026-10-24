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
    public function testLeParcoursCompletConsommeLesPerformancesRecentes(int $type, float $expectedScop, int $boilerType = 149, bool $predictive = false, bool $solar = false, int $ecsType = 136): void
    {
        $root = dirname(__DIR__, 3);
        $wood = in_array($boilerType, [182, 184], true);
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
            $this->set($entry, 'enum_type_generateur_ch_id', (string)$boilerType);
            if ($wood) {
                $this->set($entry, 'enum_type_energie_id', '5');
            }
            $this->set($entry, 'reference', 'chaudiere-hybride-2027');
            $gen->parentNode->appendChild($boiler);
        }
        $entry = $xp->query('./donnee_entree', $gen)->item(0);
        $this->set($entry, 'enum_type_generateur_ch_id', (string)$type);
        $this->set($entry, 'enum_type_energie_id', $type === 118 ? '2' : '1');
        if (in_array($type, [117, 118], true)) {
            $this->set($entry, 'enum_methode_saisie_carac_sys_id', '6');
            $di = $xp->query('./donnee_intermediaire', $gen)->item(0);
            if (!$di instanceof DOMElement) { $di = $gen->appendChild($doc->createElement('donnee_intermediaire')); }
            $this->set($di, 'scop', (string)$expectedScop);
        }
        foreach ($xp->query('//emetteur_chauffage/donnee_entree') as $entry) {
            $this->set($entry, 'enum_type_emission_distribution_id', $type === 172 ? '42' : '14');
            $this->set($entry, 'basse_temperature', $type === 172 ? '0' : '1');
            if ($predictive) {
                $this->set($entry, 'enum_equipement_intermittence_id', '8');
                $this->set($entry, 'enum_type_regulation_id', '2');
            }
        }
        foreach ($xp->query('//generateur_ecs/donnee_entree') as $entry) {
            $this->set($entry, 'enum_type_generateur_ecs_id', $wood ? ($boilerType === 182 ? '144' : '146') : (string)$ecsType);
            $this->set($entry, 'enum_type_energie_id', $wood ? '5' : '1');
            $this->set($entry, 'volume_stockage', '200');
            $this->set($entry, 'enum_type_stockage_ecs_id', '3');
            if ($ecsType === 82) {
                $this->set($entry, 'enum_periode_installation_ecs_thermo_id', '4');
                $this->set($entry, 'enum_methode_saisie_carac_sys_id', '6');
                $di = $xp->query('../donnee_intermediaire', $entry)->item(0);
                if (!$di instanceof DOMElement) { $di = $entry->parentNode->appendChild($doc->createElement('donnee_intermediaire')); }
                $this->set($di, 'cop', '4.7');
            }
        }
        if ($solar) {
            foreach ($xp->query('//installation_ecs/donnee_entree') as $entry) {
                $this->set($entry, 'enum_type_installation_solaire_id', '5');
            }
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
        if ($wood) {
            $rpint = $xr->evaluate('number((//generateur_chauffage)[2]/donnee_intermediaire/rpint)');
            if ($boilerType === 182) { self::assertGreaterThan(1, $rpint); }
            else { self::assertGreaterThan(0, $rpint); self::assertLessThan(1, $rpint); }
            self::assertGreaterThan(0, $xr->evaluate('number((//generateur_chauffage)[2]/donnee_intermediaire/qp0)'));
            self::assertGreaterThan(0, $xr->evaluate('number((//generateur_ecs)[1]/donnee_intermediaire/rendement_generation)'));
            self::assertGreaterThan(0, $xr->evaluate('number((//generateur_ecs)[1]/donnee_intermediaire/qp0)'));
        } else {
            self::assertEqualsWithDelta($ecsType === 82 ? 4.7 : 2.7, $xr->evaluate('number((//generateur_ecs)[1]/donnee_intermediaire/cop)'), 1e-12);
        }
        if ($wood) {
            self::assertGreaterThan(0, $xr->evaluate('number((//generateur_ecs)[1]/donnee_intermediaire/rendement_stockage)'));
        } else {
            self::assertSame(0.0, $xr->evaluate('count(//generateur_ecs/donnee_intermediaire/rendement_stockage)'));
        }
        self::assertGreaterThan(0, $xr->evaluate('number(//prod_enr_totale)'));
        if ($predictive) {
            self::assertEqualsWithDelta(0.98, $xr->evaluate('number((//emetteur_chauffage)[1]/donnee_intermediaire/i0)'), 1e-12);
        }
        if ($solar) {
            self::assertEqualsWithDelta(0.38, $xr->evaluate('number((//installation_ecs)[1]/donnee_intermediaire/fecs)'), 1e-12);
            self::assertGreaterThan(0, $xr->evaluate('number(//prod_enr_solaire_thermique)'));
        }
        self::assertSame([], XsdVocabulary::unknownElements((new ValueExtractor())->extract($result), $root . '/editeur/public/schemas/DPEv2.7.xsd'));
        $this->validateTechnologyBlocks($result, $root . '/editeur/public/schemas/DPEv2.7.xsd');
        self::assertSame(CalculDpePHP::calculate($result->saveXML()), CalculDpePHP::calculate($doc->saveXML()));
    }

    public static function typesRecents(): iterable
    {
        foreach ([172 => 3.2, 173 => 3.4, 174 => 3.5, 175 => 3.5, 176 => 3.5, 178 => 3.4, 179 => 3.5, 180 => 3.5, 181 => 3.5] as $type => $scop) {
            yield (string)$type => [$type, $scop];
        }
        yield 'PAC et chaudière granulés condensation récentes' => [178, 3.4, 182];
        yield 'PAC récente avec régulation prédictive' => [173, 3.4, 149, true];
        yield 'PAC récente et ECS solaire 2.7' => [173, 3.4, 149, false, true];
        yield 'autre thermodynamique électrique justifié' => [117, 3.15];
        yield 'autre thermodynamique gaz justifié' => [118, 1.5];
        yield 'CET générique 2027 avec COP propre' => [173, 3.4, 149, false, false, 82];
        yield 'PAC et granulés récents sans condensation' => [178, 3.4, 184];
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
        foreach (['generateur_chauffage', 'generateur_ecs', 'emetteur_chauffage', 'installation_ecs', 'production_enr'] as $name) {
            $declaration = $xs->query('(//xs:element[@name="' . $name . '"])[1]')->item(0)->cloneNode(true);
            $declaration->removeAttribute('minOccurs');
            $declaration->removeAttribute('maxOccurs');
            $schema->documentElement->appendChild($declaration);
        }
        foreach (['generateur_chauffage', 'generateur_ecs', 'emetteur_chauffage', 'installation_ecs', 'production_enr'] as $name) {
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
