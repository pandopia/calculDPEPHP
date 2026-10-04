<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Template;

use GdImage;
use RuntimeException;

/**
 * Un modèle officiel préparé par `bin/build-pdf-templates` : le PDF de fond,
 * la position de ses lignes de texte et un rendu basse résolution de chaque
 * page.
 *
 * Les lignes de texte servent d'ancres : les données d'exemple du modèle
 * (« 42 avenue de la République », « 216 kWh »…) sont repérées par leur
 * contenu, masquées, puis remplacées. Le rendu sert à masquer avec la couleur
 * exacte du fond, quelle que soit la page ou la variante.
 */
final class Template
{
    /** @var array<int, list<array{0: float, 1: float, 2: float, 3: float, 4: string}>>|null */
    private ?array $lines = null;

    /** @var array<int, GdImage> */
    private array $previews = [];

    public function __construct(
        public readonly TemplateVariant $variant,
        public readonly string $edition,
        private readonly string $directory,
    ) {
    }

    public function pdfPath(): string
    {
        return sprintf('%s/%s_%s.pdf', $this->directory, $this->variant->value, $this->edition);
    }

    public function name(): string
    {
        return sprintf('%s_%s', $this->variant->value, $this->edition);
    }

    /**
     * Ligne de texte du modèle dont le contenu correspond à l'expression.
     *
     * @param int $nth rang de l'occurrence (0 = première, dans l'ordre du flux)
     */
    public function find(int $page, string $pattern, int $nth = 0): ?Box
    {
        foreach ($this->lines()[$page] ?? [] as $line) {
            if (preg_match($pattern, $line[4]) === 1 && $nth-- === 0) {
                return new Box($line[0], $line[1], $line[2], $line[3], $line[4]);
            }
        }

        return null;
    }

    /**
     * Comme find(), mais l'ancre est indispensable à la mise en page.
     */
    public function anchor(int $page, string $pattern, int $nth = 0): Box
    {
        return $this->find($page, $pattern, $nth)
            ?? throw new RuntimeException(sprintf('Ancre %s introuvable page %d du modèle %s.', $pattern, $page, $this->name()));
    }

    /**
     * Toutes les lignes d'une zone, pour masquer un bloc d'exemple entier.
     *
     * @return list<Box>
     */
    public function linesIn(int $page, float $x0, float $y0, float $x1, float $y1): array
    {
        $boxes = [];
        foreach ($this->lines()[$page] ?? [] as $line) {
            if ($line[0] >= $x0 && $line[2] <= $x1 && $line[1] >= $y0 && $line[3] <= $y1) {
                $boxes[] = new Box($line[0], $line[1], $line[2], $line[3], $line[4]);
            }
        }

        return $boxes;
    }

    /**
     * Couleur du fond du modèle au point donné.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public function colorAt(int $page, float $x, float $y): array
    {
        $image = $this->preview($page);
        $px = (int) max(0, min(imagesx($image) - 1, round($x / 2)));
        $py = (int) max(0, min(imagesy($image) - 1, round($y / 2)));
        $rgb = imagecolorat($image, $px, $py);

        return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
    }

    /**
     * Premier point sous $y où le fond n'est plus blanc pur, en descendant à
     * l'abscisse $x : bas d'un panneau blanc du modèle.
     */
    public function whiteUntil(int $page, float $x, float $y, int $minRun = 3): float
    {
        // Un filet ou un pointillé (moins de $minRun pixels) ne ferme pas le panneau.
        $image = $this->preview($page);
        $px = (int) round($x / 2);
        $run = 0;
        for ($py = (int) ceil($y / 2); $py < imagesy($image); $py++) {
            $rgb = imagecolorat($image, $px, $py);
            $run = min(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF) < 252 ? $run + 1 : 0;
            if ($run >= $minRun) {
                return ($py - $minRun + 1) * 2.0;
            }
        }

        return imagesy($image) * 2.0;
    }

    /**
     * @return array<int, list<array{0: float, 1: float, 2: float, 3: float, 4: string}>>
     */
    private function lines(): array
    {
        return $this->lines ??= require sprintf('%s/maps/%s.php', $this->directory, $this->name());
    }

    private function preview(int $page): GdImage
    {
        if (!isset($this->previews[$page])) {
            $path = sprintf('%s/preview/%s-%d.png', $this->directory, $this->name(), $page);
            $image = @imagecreatefrompng($path);
            if ($image === false) {
                throw new RuntimeException("Rendu de modèle illisible : $path (relancer bin/build-pdf-templates).");
            }
            $this->previews[$page] = $image;
        }

        return $this->previews[$page];
    }
}
