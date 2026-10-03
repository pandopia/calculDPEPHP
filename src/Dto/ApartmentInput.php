<?php
declare(strict_types=1);
namespace CalculDpePHP\Dto;

/** Entrée indépendante du format d'export de l'application appelante. */
final readonly class ApartmentInput
{
    /** @param array<string, list<string>> $associations */
    public function __construct(
        public string $reference,
        public float $surface,
        public ?string $error = null,
        public array $associations = [],
        public ?int $position = null,
        public ?int $typology = null,
        public bool $visited = false,
    ) {}
}
