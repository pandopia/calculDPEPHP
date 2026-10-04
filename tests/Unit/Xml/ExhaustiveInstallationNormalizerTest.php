<?php

declare(strict_types=1);

namespace Tests\Unit\Xml;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Engine\{CalculationContext, CalculatorInterface, CalculatorPipeline, DpeEngine};
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\{ExhaustiveInstallationNormalizer, NodeAccessor};
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExhaustiveInstallationNormalizerTest extends TestCase
{
    public static function immeubles(): iterable
    {
        yield '117 logements sans échantillonnage' => ['exhaustif-117', 400000.0, 123.0, 25.0, 140635.04198436];
        yield '8 logements sans échantillonnage' => ['exhaustif-8', 55000.0, 143.0, 28.0, 9881.73107727414];
    }

    #[DataProvider('immeubles')]
    public function testLeCalculRetrouvePuissanceBesoinEtEtiquettesSansSortiesDeReference(string $name, float $power, float $ep, float $ges, float $ecs): void
    {
        $xml = file_get_contents(__DIR__ . '/Fixtures/' . $name . '.xml');
        $document = new DOMDocument();
        $document->loadXML($xml);
        self::assertSame(0, $document->getElementsByTagName('sortie')->length);
        self::assertSame(0, $document->getElementsByTagName('donnee_intermediaire')->length);
        $out = CalculDpePHP::calculate($xml);
        $document->loadXML($out);
        $a = new NodeAccessor($document);
        self::assertEqualsWithDelta($power, $a->getFloatOrNull('//generateur_chauffage/donnee_intermediaire/pn'), 0.001);
        self::assertEqualsWithDelta($ecs, $a->getFloatOrNull('//installation_ecs/donnee_intermediaire/besoin_ecs'), 0.01);
        self::assertSame($ep, $a->getFloatOrNull('//sortie/ep_conso/ep_conso_5_usages_m2'));
        self::assertSame($ges, $a->getFloatOrNull('//sortie/emission_ges/emission_ges_5_usages_m2'));
        self::assertSame('C', $a->getStringOrNull('//classe_bilan_dpe'));
        self::assertSame('C', $a->getStringOrNull('//classe_emission_ges'));
        foreach ($document->getElementsByTagName('enum_methode_calcul_conso_id') as $method) {
            self::assertSame('4', $method->textContent, 'Le code saisi reste intact dans le XML retourné.');
        }
        self::assertSame($out, CalculDpePHP::calculate($out), 'Le rejeu reste idempotent.');
    }

    public static function casNonApplicables(): iterable
    {
        yield 'ancien schéma' => ['//administratif/enum_version_id', '2.5'];
        yield 'ancien export Liciel' => ['//version_logiciel', 'LICIEL Diagnostics v4 [Version XML:318]'];
        yield 'autre logiciel' => ['//version_logiciel', 'Autre logiciel'];
        yield 'ancien moteur' => ['//version_moteur_calcul', 'BBS_Slama_2024.6.1.0'];
        yield 'moteur différent' => ['//version_moteur_calcul', '3cl_tribu_2024.6.1.0'];
        yield 'échantillonnage explicite' => ['//enum_calcul_echantillonnage_id', '2'];
        yield 'exhaustivité non déclarée' => ['//enum_calcul_echantillonnage_id', null];
        yield 'visite manquante' => ['(//logement_visite)[1]', null];
        yield 'surface visitée manquante' => ['(//logement_visite/surface_habitable_logement)[1]', null];
        yield 'surface visitée incohérente' => ['(//logement_visite/surface_habitable_logement)[1]', '10'];
        yield 'méthode mixte' => ['//enum_methode_application_dpe_log_id', '33'];
        yield 'appartement autonome' => ['//enum_methode_application_dpe_log_id', '2'];
        yield 'bâtiment original' => ['//enum_methode_application_dpe_log_id', '6'];
        yield 'collectif' => ['//installation_chauffage/donnee_entree/enum_type_installation_id | //installation_ecs/donnee_entree/enum_type_installation_id', '2'];
        yield 'surface de groupe incomplète' => ['//installation_chauffage/donnee_entree/surface_chauffee | //installation_ecs/donnee_entree/surface_habitable', '100'];
        yield 'multiplicité déjà complète' => ['//rdim', '8'];
        yield 'multiplicité invalide' => ['//rdim', '0'];
        yield 'virtualisation' => ['//ratio_virtualisation', '0.5'];
    }

    #[DataProvider('casNonApplicables')]
    public function testLesAutresRepresentationsConserventLeurMethode(string $path, ?string $value): void
    {
        $document = $this->fixture();
        $nodes = iterator_to_array((new DOMXPath($document))->query($path));
        foreach ($nodes as $node) {
            if ($value === null) { $node->parentNode->removeChild($node); }
            else { $node->textContent = $value; }
        }
        $before = $document->saveXML();
        self::assertCount(0, ExhaustiveInstallationNormalizer::apply($document));
        self::assertSame($before, $document->saveXML());
    }

    public function testChaqueUsageEstControleIndependamment(): void
    {
        $document = $this->fixture();
        (new DOMXPath($document))->query('//installation_ecs/donnee_entree/surface_habitable')->item(0)->textContent = '100';
        self::assertCount(1, ExhaustiveInstallationNormalizer::apply($document));
        $a = new NodeAccessor($document);
        self::assertSame(1, $a->getIntOrNull('//installation_chauffage/donnee_entree/enum_methode_calcul_conso_id'));
        self::assertSame(4, $a->getIntOrNull('//installation_ecs/donnee_entree/enum_methode_calcul_conso_id'));
    }

    public function testLesDonneesOriginalesSontRestaureesMemeApresUneErreur(): void
    {
        $document = $this->fixture();
        $before = $document->saveXML();
        $pipeline = new CalculatorPipeline();
        $pipeline->add(new class implements CalculatorInterface {
            public function id(): string { return 'echec_test'; }
            public function dependencies(): array { return []; }
            public function appliesTo(DOMElement $node): bool { return $node->nodeName === 'logement'; }
            public function calculate(DOMElement $node, CalculationContext $context): void
            {
                TestCase::assertSame(1, (new NodeAccessor($context->document))->getIntOrNull('//installation_chauffage/donnee_entree/enum_methode_calcul_conso_id'));
                throw new RuntimeException('échec volontaire');
            }
        });
        $tables = dirname((new \ReflectionClass(TableRepository::class))->getFileName(), 3) . '/resources/tables';
        try {
            (new DpeEngine($pipeline, new TableRepository($tables)))->calculateDocument($document);
            self::fail('Une erreur du calculateur doit être propagée.');
        } catch (RuntimeException $e) {
            self::assertSame('échec volontaire', $e->getMessage());
        }
        self::assertSame($before, $document->saveXML());
    }

    public function testLeBallonElectriqueConserveSonVolumeParLogement(): void
    {
        $document = new DOMDocument();
        $document->load(__DIR__ . '/Fixtures/collectif-ballon-electrique.xml');
        self::assertCount(0, ExhaustiveInstallationNormalizer::apply($document));
        $document->loadXML(CalculDpePHP::calculate($document->saveXML()));
        $a = new NodeAccessor($document);
        self::assertSame(221.0, $a->getFloatOrNull('//sortie/ep_conso/ep_conso_5_usages_m2'));
        self::assertSame(36.0, $a->getFloatOrNull('//sortie/emission_ges/emission_ges_5_usages_m2'));
        self::assertSame('D', $a->getStringOrNull('//classe_bilan_dpe'));
        self::assertSame('D', $a->getStringOrNull('//classe_emission_ges'));
    }

    private function fixture(): DOMDocument
    {
        $document = new DOMDocument();
        $document->load(__DIR__ . '/Fixtures/exhaustif-8.xml');
        return $document;
    }
}
