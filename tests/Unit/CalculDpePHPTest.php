<?php

declare(strict_types=1);

namespace Tests\Unit;

use CalculDpePHP\CalculDpePHP;
use CalculDpePHP\Dto\EnergyPerformanceResult;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CalculDpePHPTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../..';
    private const FIXTURE_BASENAME = 'bat_post2026coefelec_diag2356736.xml';

    public function testCalculateReturnsCalculatedXmlByDefault(): void
    {
        $xml = (string) file_get_contents(self::PROJECT_ROOT . '/resources/XML/input/' . self::FIXTURE_BASENAME);

        $result = CalculDpePHP::calculate($xml);

        $this->assertIsString($result);

        $document = new DOMDocument();
        $this->assertTrue($document->loadXML($result));

        $expected = $this->expectedEnergyValues();
        $accessor = new NodeAccessor($document);

        $this->assertSame($expected['classe_bilan_dpe'], $accessor->getStringOrNull('//sortie/ep_conso/classe_bilan_dpe'));
        $this->assertSame($expected['classe_emission_ges'], $accessor->getStringOrNull('//sortie/emission_ges/classe_emission_ges'));
        $this->assertEqualsWithDelta($expected['ep_conso_5_usages_m2'], $accessor->getFloatOrNull('//sortie/ep_conso/ep_conso_5_usages_m2'), 1e-3);
        $this->assertEqualsWithDelta($expected['emission_ges_5_usages_m2'], $accessor->getFloatOrNull('//sortie/emission_ges/emission_ges_5_usages_m2'), 1e-3);
    }

    public function testCalculateReturnsEnergyDtoWhenRequested(): void
    {
        $xml = (string) file_get_contents(self::PROJECT_ROOT . '/resources/XML/input/' . self::FIXTURE_BASENAME);

        $result = CalculDpePHP::calculate($xml, ['energieOnly' => true]);

        $this->assertInstanceOf(EnergyPerformanceResult::class, $result);

        $expected = $this->expectedEnergyValues();

        $this->assertEqualsWithDelta($expected['ep_conso_5_usages_m2'], $result->epConso5UsagesM2, 1e-3);
        $this->assertSame($expected['classe_bilan_dpe'], $result->classeBilanDpe);
        $this->assertEqualsWithDelta($expected['emission_ges_5_usages_m2'], $result->emissionGes5UsagesM2, 1e-3);
        $this->assertSame($expected['classe_emission_ges'], $result->classeEmissionGes);
    }

    public function testCalculateRejectsUnknownOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options non supportées');

        CalculDpePHP::calculate('<dpe/>', ['unknown' => true]);
    }

    /**
     * @return array{
     *   ep_conso_5_usages_m2: float,
     *   classe_bilan_dpe: string,
     *   emission_ges_5_usages_m2: float,
     *   classe_emission_ges: string
     * }
     */
    private function expectedEnergyValues(): array
    {
        $document = new DOMDocument();
        $document->load(self::PROJECT_ROOT . '/resources/XML/verif/' . self::FIXTURE_BASENAME);

        $accessor = new NodeAccessor($document);

        return [
            'ep_conso_5_usages_m2' => (float) $accessor->getFloatOrNull('//sortie/ep_conso/ep_conso_5_usages_m2'),
            'classe_bilan_dpe' => (string) $accessor->getStringOrNull('//sortie/ep_conso/classe_bilan_dpe'),
            'emission_ges_5_usages_m2' => (float) $accessor->getFloatOrNull('//sortie/emission_ges/emission_ges_5_usages_m2'),
            'classe_emission_ges' => (string) $accessor->getStringOrNull('//sortie/emission_ges/classe_emission_ges'),
        ];
    }
}
