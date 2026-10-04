<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Template;

/**
 * Rectangle en points PDF, origine en haut à gauche de la page (convention
 * de pdftotext et de TCPDF).
 */
final readonly class Box
{
    public function __construct(
        public float $x0,
        public float $y0,
        public float $x1,
        public float $y1,
        public string $text = '',
    ) {
    }

    public function width(): float
    {
        return $this->x1 - $this->x0;
    }

    public function height(): float
    {
        return $this->y1 - $this->y0;
    }

    public function grow(float $left, ?float $top = null, ?float $right = null, ?float $bottom = null): self
    {
        $top ??= $left;
        $right ??= $left;
        $bottom ??= $top;

        return new self($this->x0 - $left, $this->y0 - $top, $this->x1 + $right, $this->y1 + $bottom, $this->text);
    }

    public function union(self $other): self
    {
        return new self(
            min($this->x0, $other->x0),
            min($this->y0, $other->y0),
            max($this->x1, $other->x1),
            max($this->y1, $other->y1),
            trim($this->text . ' ' . $other->text),
        );
    }
}
