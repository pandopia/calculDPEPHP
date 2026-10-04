<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render;

/**
 * Tableau à lignes de hauteur variable dans le style des modèles (pages 4 et
 * 5) : bandes alternées, pictogramme et libellé à gauche, description au
 * centre, valeur ou pastille à droite.
 *
 * Une ligne est une liste de « paragraphes » ; chaque paragraphe porte son
 * style et, optionnellement, une valeur alignée à droite (performance
 * recommandée en regard du travail correspondant).
 */
final class RowTable
{
    public float $fontSize = 9.0;
    public float $leading = 10.8;
    public float $minRowHeight = 28.3;
    public float $padding = 4.0;

    /**
     * @param array{0:int,1:int,2:int} $stripe
     */
    public function __construct(
        private readonly Canvas $pdf,
        public float $left,
        public float $right,
        public float $labelX,
        public float $descriptionX,
        public float $descriptionRight,
        public float $valueX,
        public array $stripe,
    ) {
    }

    /**
     * @param array{picto?: ?string, label: string, paragraphs: list<array{text: string, style?: string, value?: ?string, warning?: bool}>, badge?: ?array{0: string, 1: array{0:int,1:int,2:int}}} $row
     * @return list<array{text: string, style: string, value: ?string, warning: bool, first: bool}> lignes physiques
     */
    private function layout(array $row): array
    {
        $lines = [];
        foreach ($row['paragraphs'] as $paragraph) {
            $style = $paragraph['style'] ?? '';
            $warning = $paragraph['warning'] ?? false;
            $this->pdf->font(Canvas::COND, $this->fontSize, $style === 'I' ? 'I' : '');
            $width = $this->descriptionRight - $this->descriptionX - ($warning ? 11 : 0);
            foreach ($this->pdf->wrap($paragraph['text'], $width) as $i => $text) {
                $lines[] = [
                    'text' => $text,
                    'style' => $style,
                    'value' => $i === 0 ? ($paragraph['value'] ?? null) : null,
                    'warning' => $warning,
                    'first' => $i === 0,
                ];
            }
        }

        return $lines;
    }

    /**
     * @param array{picto?: ?string, label: string, paragraphs: list<array{text: string, style?: string, value?: ?string, warning?: bool}>, badge?: ?array{0: string, 1: array{0:int,1:int,2:int}}} $row
     */
    public function height(array $row): float
    {
        return max($this->minRowHeight, count($this->layout($row)) * $this->leading + 2 * $this->padding);
    }

    /**
     * @param list<array{picto?: ?string, label: string, paragraphs: list<array{text: string, style?: string, value?: ?string, warning?: bool}>, badge?: ?array{0: string, 1: array{0:int,1:int,2:int}}}> $rows
     */
    public function totalHeight(array $rows): float
    {
        return array_sum(array_map(fn (array $row): float => $this->height($row), $rows));
    }

    /**
     * Réduit le corps du texte jusqu'à ce que les lignes tiennent en $available.
     *
     * @param list<array{picto?: ?string, label: string, paragraphs: list<array{text: string, style?: string, value?: ?string, warning?: bool}>, badge?: ?array{0: string, 1: array{0:int,1:int,2:int}}}> $rows
     */
    public function fit(array $rows, float $available, float $minSize = 7.0): bool
    {
        while ($this->totalHeight($rows) > $available && $this->fontSize > $minSize) {
            $this->fontSize -= 0.25;
            $this->leading = $this->fontSize * 1.2;
            $this->minRowHeight = max(20.0, $this->minRowHeight - 0.8);
        }

        return $this->totalHeight($rows) <= $available;
    }

    /**
     * @param array{picto?: ?string, label: string, paragraphs: list<array{text: string, style?: string, value?: ?string, warning?: bool}>, badge?: ?array{0: string, 1: array{0:int,1:int,2:int}}} $row
     * @return float haut de la ligne suivante
     */
    public function draw(array $row, float $top, bool $striped): float
    {
        $h = $this->height($row);
        if ($striped) {
            $this->pdf->fillRect($this->left, $top, $this->right - $this->left, $h, $this->stripe);
        }
        $center = $top + $h / 2;

        if (($row['picto'] ?? null) !== null) {
            $this->pdf->picto($row['picto'], $this->left + 4.5, $center - 9.5, 19.0, 19.0);
        }
        $this->pdf->font(Canvas::COND_SEMIBOLD, $this->fontSize, '', Canvas::BLACK);
        $labelRows = $this->pdf->wrap($row['label'], $this->descriptionX - $this->labelX - 6);
        foreach ($labelRows as $i => $text) {
            $this->pdf->textAt($this->labelX, $center - count($labelRows) * $this->leading / 2 + $i * $this->leading - 1.0, $text);
        }

        $lines = $this->layout($row);
        $y = $center - count($lines) * $this->leading / 2 - 1.0;
        foreach ($lines as $line) {
            $x = $this->descriptionX;
            if ($line['warning'] && $line['first']) {
                $this->pdf->picto('global_picto_warning_orange', $x, $y + 2.2, 8.0, 7.0);
            }
            if ($line['warning']) {
                $x += 11;
            }
            $this->pdf->font(Canvas::COND, $this->fontSize, $line['style'] === 'I' ? 'I' : '', Canvas::BLACK);
            if ($line['style'] === 'B') {
                $this->pdf->font(Canvas::COND_SEMIBOLD, $this->fontSize);
            }
            $this->pdf->textAt($x, $y, $line['text']);
            if ($line['value'] !== null) {
                $this->pdf->font(Canvas::COND, $this->fontSize, '', Canvas::BLACK);
                $this->pdf->fitWidth($line['value'], $this->right - $this->valueX - 4);
                $this->pdf->textAt($this->valueX, $y, $line['value']);
            }
            $y += $this->leading;
        }

        if (($row['badge'] ?? null) !== null) {
            [$text, $color] = $row['badge'];
            $this->pdf->font(Canvas::COND_SEMIBOLD, 9, '', Canvas::WHITE);
            $w = $this->pdf->GetStringWidth($text) + 9.0;
            $this->pdf->SetFillColor(...$color);
            $this->pdf->RoundedRect($this->valueX, $center - 6.4, $w, 12.9, 2.5, '1111', 'F');
            $this->pdf->textAt($this->valueX + 4.5, $center - 7.0, $text);
        }

        return $top + $h;
    }
}
