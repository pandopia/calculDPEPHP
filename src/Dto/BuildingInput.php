<?php
declare(strict_types=1);
namespace CalculDpePHP\Dto;

final readonly class BuildingInput
{
    /** @param list<ApartmentInput> $apartments
     *  @param list<string> $assumptions Hypothèses de l’adaptateur, conservées dans le résultat. */
    public function __construct(
        public string $xml,
        public array $apartments,
        public int $heatingDistribution,
        public int $hotWaterDistribution,
        public ?string $apartmentReference = null,
        public ?float $individualizationCoefficient = null,
        public bool $approximateMissingAssociations = false,
        public array $assumptions = [],
    ) {}
}
