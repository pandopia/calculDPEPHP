<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\OutputSchemaValidator;
use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OutputSchemaValidatorTest extends TestCase
{
    private function validator(): OutputSchemaValidator
    {
        return new OutputSchemaValidator(dirname(__DIR__, 3) . '/editeur/public/schemas');
    }

    private function document(string $body, string $version = '2.6'): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML('<dpe xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><administratif><enum_version_id>' . $version . '</enum_version_id></administratif><logement>' . $body . '</logement></dpe>');
        return $document;
    }

    private function climatisation(string $output): string
    {
        $output .= '<besoin_fr>0</besoin_fr><conso_fr_depensier>0</conso_fr_depensier>';
        if (!str_contains($output, '<conso_fr>')) { $output .= '<conso_fr>0</conso_fr>'; }
        return '<climatisation_collection><climatisation><donnee_intermediaire>' . $output . '</donnee_intermediaire></climatisation></climatisation_collection>';
    }

    public static function versions(): iterable
    {
        foreach (['1', '1.1'] as $v) { yield $v => [$v, 'DPEv1-obsolete.xsd']; }
        foreach (['2', '2.1', '2.2'] as $v) { yield $v => [$v, 'DPEv2.xsd']; }
        foreach (['2.3' => '2.2', '2.4' => '2.3', '2.5' => '2.4', '2.6' => '2.6', '2.7' => '2.7'] as $v => $f) { yield $v => [$v, 'DPEv' . $f . '.xsd']; }
    }

    #[DataProvider('versions')]
    public function testLeNumeroDeModeleNestPasLeNomDuFichier(string $version, string $filename): void
    {
        self::assertSame($filename, OutputSchemaValidator::schemaFilename($version));
        $result = $this->validator()->validate($this->document('', $version));
        self::assertSame($version, $result['version']);
        self::assertSame($filename, $result['schema']);
        self::assertSame([], $result['issues']);
    }

    public function testLesBalisesObligatoiresManquentMemeSiLaReferenceNeLesPubliePas(): void
    {
        $result = $this->validator()->validate($this->document($this->climatisation('<conso_fr>0</conso_fr>')));
        self::assertSame(1, $result['validated_blocks']);
        self::assertCount(1, $result['issues']);
        self::assertStringContainsString('eer', implode(' ', $result['issues'][0]['errors']));
    }

    public function testUneConsommationNulleNeSupprimePasLaPerformancePositive(): void
    {
        $validator = $this->validator();
        $document = $this->document($this->climatisation('<eer>3.5</eer><conso_fr>0</conso_fr>'));
        $before = $document->saveXML();
        foreach ([1, 2] as $_) {
            $result = $validator->validate($document);
            self::assertSame(1, $result['validated_blocks']);
            self::assertSame([], $result['issues']);
            self::assertSame($before, $document->saveXML());
        }
    }

    public static function performancesInvalides(): iterable
    {
        yield 'zero' => ['<eer>0</eer>'];
        yield 'negative' => ['<eer>-1</eer>'];
        yield 'texte' => ['<eer>inconnue</eer>'];
        yield 'nil avec contenu' => ['<eer xsi:nil="true">3</eer>'];
        yield 'nil numerique avec contenu' => ['<eer xsi:nil="1">3</eer>'];
        yield 'enfant invente' => ['<eer>3</eer><Qgw>1</Qgw>'];
        yield 'vide' => [''];
    }

    #[DataProvider('performancesInvalides')]
    public function testLesBornesTypesEtNilSontControles(string $output): void
    {
        self::assertCount(1, $this->validator()->validate($this->document($this->climatisation($output)))['issues']);
    }

    public function testLesFratriesHomonymesRestentDistinctes(): void
    {
        $body = '<climatisation_collection><climatisation><donnee_intermediaire><eer>3</eer><besoin_fr>0</besoin_fr><conso_fr>0</conso_fr><conso_fr_depensier>0</conso_fr_depensier></donnee_intermediaire></climatisation><climatisation><donnee_intermediaire><eer>0</eer><besoin_fr>0</besoin_fr><conso_fr>0</conso_fr><conso_fr_depensier>0</conso_fr_depensier></donnee_intermediaire></climatisation></climatisation_collection>';
        $result = $this->validator()->validate($this->document($body));
        self::assertSame(2, $result['validated_blocks']);
        self::assertCount(1, $result['issues']);
        self::assertStringContainsString('/climatisation[2]/', $result['issues'][0]['path']);
    }

    public function testUnBlocAuMauvaisEndroitNestPasLegitimeParSonNom(): void
    {
        $result = $this->validator()->validate($this->document('<donnee_intermediaire><eer>3</eer></donnee_intermediaire>'));
        self::assertSame(0, $result['validated_blocks']);
        self::assertCount(1, $result['issues']);
        self::assertStringContainsString('absent du schéma', $result['issues'][0]['errors'][0]);
    }

    public function testLeControleIgnoreLesEntreesEtNePretendPasValiderLeDocumentEntier(): void
    {
        $result = $this->validator()->validate($this->document('<donnee_entree><non_valide/></donnee_entree>'));
        self::assertSame(0, $result['validated_blocks']);
        self::assertSame([], $result['issues']);
    }

    public function testLesAutresPerimetresNeSontPasValidesCommeDesLogementsExistants(): void
    {
        $doc = $this->document('');
        $neuf = $doc->createElement('logement_neuf');
        $neuf->appendChild($doc->createElement('sortie'));
        $doc->documentElement->appendChild($neuf);
        self::assertSame(0, $this->validator()->validate($doc)['validated_blocks']);
    }

    public function testUnBlocSortiePresentMaisVideEstControle(): void
    {
        $result = $this->validator()->validate($this->document('<sortie/>'));
        self::assertSame(1, $result['validated_blocks']);
        self::assertCount(1, $result['issues']);
    }

    public function testLesNouvellesBalises27NeSontPasAutoriseesDansUnAncienModele(): void
    {
        $body = '<installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_intermediaire><scf>0.07</scf><ratio_besoin_ecs>1</ratio_besoin_ecs><conso_ecs>100</conso_ecs><conso_ecs_depensier>120</conso_ecs_depensier></donnee_intermediaire></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>';
        self::assertNotEmpty($this->validator()->validate($this->document($body, '2.6'))['issues']);
        self::assertSame([], $this->validator()->validate($this->document($body, '2.7'))['issues']);
    }

    public function testUnSchemaAbsentNeDonnePasUnFauxVert(): void
    {
        $this->expectException(RuntimeException::class);
        (new OutputSchemaValidator('/introuvable'))->validate($this->document(''));
    }

    public static function schemasInexploitables(): iterable
    {
        yield 'XML mal forme' => ['<xs:schema'];
        yield 'autre document' => ['<document/>'];
        yield 'aucun bloc' => ['<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"/>'];
        foreach (['import', 'include', 'redefine'] as $type) {
            yield $type => ['<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"><xs:' . $type . ' schemaLocation="https://example.invalid/interdit.xsd"/></xs:schema>'];
        }
    }

    #[DataProvider('schemasInexploitables')]
    public function testUnSchemaNonAutonomeOuInexploitableEstRefuse(string $source): void
    {
        $directory = sys_get_temp_dir() . '/calculdpe-schema-test-' . bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $file = $directory . '/DPEv2.6.xsd';
        file_put_contents($file, $source);
        try {
            $this->expectException(RuntimeException::class);
            (new OutputSchemaValidator($directory))->validate($this->document(''));
        } finally {
            unlink($file);
            rmdir($directory);
        }
    }

    public function testUneVersionInconnueNeChoisitPasUnSchemaParDefaut(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator()->validate($this->document('', '2.8'));
    }

    public function testLaCollecteLibxmlEstRetablie(): void
    {
        $original = libxml_use_internal_errors(false);
        try {
            $this->validator()->validate($this->document($this->climatisation('<eer>0</eer>')));
            self::assertFalse(libxml_use_internal_errors());
            self::assertSame([], libxml_get_errors());
        } finally {
            libxml_use_internal_errors($original);
        }
    }
}
