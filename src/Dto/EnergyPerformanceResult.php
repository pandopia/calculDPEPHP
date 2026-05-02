<?php

declare(strict_types=1);

namespace CalculDpePHP\Dto;

final readonly class EnergyPerformanceResult
{
    public function __construct(
        public float $epConso5UsagesM2,
        public string $classeBilanDpe,
        public float $emissionGes5UsagesM2,
        public string $classeEmissionGes,
    ) {}
}
