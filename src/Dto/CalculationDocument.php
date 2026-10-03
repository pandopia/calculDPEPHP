<?php
declare(strict_types=1);
namespace CalculDpePHP\Dto;

final readonly class CalculationDocument
{
    public function __construct(public ?string $xml, public ?string $error = null) {}
}
