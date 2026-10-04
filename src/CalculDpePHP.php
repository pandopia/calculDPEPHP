<?php

declare(strict_types=1);

namespace CalculDpePHP;

use CalculDpePHP\Dto\EnergyPerformanceResult;
use CalculDpePHP\Engine\DefaultDpeEngineFactory;
use CalculDpePHP\Xml\NodeAccessor;
use CalculDpePHP\Xml\XmlWriter;
use DOMDocument;
use InvalidArgumentException;
use RuntimeException;

final class CalculDpePHP
{
    public const VERSION = '0.1.20-alpha.12';

    public static function calculateBuilding(Dto\BuildingInput $input): Dto\BuildingResult
    {
        return Collectif\BuildingCalculation::run($input);
    }

    /**
     * @param array{energieOnly?: bool} $options
     */
    public static function calculate(string $xml, array $options = []): string|EnergyPerformanceResult
    {
        self::assertSupportedOptions($options);

        $document = DefaultDpeEngineFactory::create()->calculate($xml);

        if (($options['energieOnly'] ?? false) === true) {
            return self::buildEnergyResult($document);
        }

        return (new XmlWriter())->toString($document);
    }

    /**
     * Rapport PDF d'un DPE, au format des modèles officiels, à partir de son
     * XML ADEME validé (avec ses résultats `<sortie>`). Les éléments absents
     * du XML (photo, logo, signature…) se passent dans $complements.
     *
     * @return string contenu binaire du PDF
     */
    public static function genererPdf(string $xml, ?Dto\DonneesRapportPdf $complements = null): string
    {
        return (new Pdf\DpePdfGenerator())->generate($xml, $complements ?? new Dto\DonneesRapportPdf());
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function assertSupportedOptions(array $options): void
    {
        $supported = ['energieOnly'];
        $unknown = array_diff(array_keys($options), $supported);

        if ($unknown !== []) {
            throw new InvalidArgumentException(sprintf(
                'Options non supportées : %s',
                implode(', ', $unknown),
            ));
        }
    }

    private static function buildEnergyResult(DOMDocument $document): EnergyPerformanceResult
    {
        $accessor = new NodeAccessor($document);

        $epConso5UsagesM2 = $accessor->getFloatOrNull('//sortie/ep_conso/ep_conso_5_usages_m2');
        $classeBilanDpe = $accessor->getStringOrNull('//sortie/ep_conso/classe_bilan_dpe');
        $emissionGes5UsagesM2 = $accessor->getFloatOrNull('//sortie/emission_ges/emission_ges_5_usages_m2');
        $classeEmissionGes = $accessor->getStringOrNull('//sortie/emission_ges/classe_emission_ges');

        if (
            $epConso5UsagesM2 === null
            || $classeBilanDpe === null
            || $emissionGes5UsagesM2 === null
            || $classeEmissionGes === null
        ) {
            throw new RuntimeException('Les sorties énergie attendues sont absentes du XML calculé.');
        }

        return new EnergyPerformanceResult(
            epConso5UsagesM2: $epConso5UsagesM2,
            classeBilanDpe: $classeBilanDpe,
            emissionGes5UsagesM2: $emissionGes5UsagesM2,
            classeEmissionGes: $classeEmissionGes,
        );
    }
}
