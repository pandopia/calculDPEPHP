<?php
declare(strict_types=1);
namespace CalculDpePHP\Dto;

final readonly class BuildingInput
{
    /** @param list<ApartmentInput> $apartments */
    public function __construct(
        public string $xml,
        public array $apartments,
        public int $heatingDistribution,
        public int $hotWaterDistribution,
        public ?string $apartmentReference = null,
    ) {}
}
