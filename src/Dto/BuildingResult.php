<?php
declare(strict_types=1);
namespace CalculDpePHP\Dto;

final readonly class BuildingResult
{
    /** @param array<string, CalculationDocument> $apartments
     *  @param list<string> $assumptions */
    public function __construct(public CalculationDocument $building, public array $apartments, public array $assumptions = []) {}
}
