<?php
declare(strict_types=1);
namespace CalculDpePHP\Dto;

final readonly class BuildingResult
{
    /** @param array<string, CalculationDocument> $apartments */
    public function __construct(public CalculationDocument $building, public array $apartments) {}
}
