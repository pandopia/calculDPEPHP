<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render;

use CalculDpePHP\Pdf\Template\Box;
use CalculDpePHP\Pdf\Template\Template;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Surface de dessin : une page du modèle officiel en fond, puis les données
 * du DPE par-dessus.
 *
 * Unités en points, origine en haut à gauche, comme les ancres du modèle.
 * Les polices sont celles du modèle (IBM Plex Sans et Sans Condensed, licence
 * SIL OFL), converties pour TCPDF dans resources/pdf/fonts/tcpdf.
 */
final class Canvas extends Fpdi
{
    public const SANS = 'plex';
    public const SANS_MEDIUM = 'plexmedium';
    public const SANS_SEMIBOLD = 'plexsemibold';
    public const COND = 'plexcond';
    public const COND_MEDIUM = 'plexcondmedium';
    public const COND_SEMIBOLD = 'plexcondsemibold';

    public const BLACK = [0, 0, 0];
    public const WHITE = [255, 255, 255];
    public const GREY = [157, 157, 156];
    public const GREEN = [0, 164, 122];
    public const PINK = [232, 48, 138];
    public const ORANGE = [245, 158, 36];
    public const NAVY = [61, 66, 140];

    /** Hauteur de ligne pdftotext d'IBM Plex : (1119 + 245) / 1000 em (FontBBox). */
    private const LINE_BOX_EM = 1.364;
    /** Distance haut de boîte pdftotext → ligne de base : 1119 / 1000 em. */
    private const BASELINE_EM = 1.119;

    private Template $template;
    private int $templatePage = 1;

    /**
     * Zones recouvertes, relevées par bin/build-pdf-templates pour retirer du
     * modèle le texte d'exemple qu'elles cachent (null : pas de relevé).
     *
     * @var array<string, array<int, list<array{0: float, 1: float, 2: float, 3: float}>>>|null
     */
    public static ?array $recordedZones = null;

    /** Faux le temps d'un recouvrement propre à une page de suite (non relevé). */
    public bool $recording = true;

    /** @var array<int, string> pages importées du modèle */
    private array $imported = [];

    public function __construct(Template $template)
    {
        parent::__construct('P', 'pt', 'A4', true, 'UTF-8', false);
        $this->template = $template;
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetAutoPageBreak(false);
        $this->SetMargins(0, 0, 0);
        $this->setCellPaddings(0, 0, 0, 0);
        $this->setCellMargins(0, 0, 0, 0);
        $this->setFontSubsetting(true);
        $this->SetCreator('calculDPE PHP');
        $this->registerFonts();
        $this->setSourceFile($template->pdfPath());
    }

    public function template(): Template
    {
        return $this->template;
    }

    public function templatePage(): int
    {
        return $this->templatePage;
    }

    /**
     * Nouvelle page du rapport, avec la page $templatePage du modèle en fond.
     * Le bandeau « Exemple de DPE, données fictives » du modèle est effacé.
     */
    public function addTemplatePage(int $templatePage): void
    {
        $this->templatePage = $templatePage;
        $this->recording = true;
        $this->imported[$templatePage] ??= $this->importPage($templatePage);
        $this->AddPage('P', 'A4');
        $this->useTemplate($this->imported[$templatePage], 0, 0, 595.276, 841.89);
        $this->eraseSampleBanner();
        $this->renumber();
    }

    /**
     * Le modèle numérote ses pages « p.N » ; une section qui déborde décale
     * les suivantes.
     */
    private function renumber(): void
    {
        $label = $this->template->find($this->templatePage, '/^p\.\d+$/');
        if ($label === null) {
            return;
        }
        $this->fillRect($label->x0 - 8, $label->y0 + 3, $label->width() + 8.5, $label->height() - 5, Canvas::WHITE);
        $this->font(self::SANS, 12.5, 'B', [0, 0, 0]);
        $this->textAt($label->x1, $label->y0 + 1.1, 'p.' . $this->PageNo(), 'R');
    }

    private function eraseSampleBanner(): void
    {
        $banner = $this->template->find($this->templatePage, '/^Exemple de DPE, données fictives/');
        if ($banner === null) {
            return;
        }
        // Bandeau pleine largeur de 5,6 à 28,3 pt ; le fond de page commence à 7,1 pt.
        $this->fillRect(0, 5.0, 595.3, 24.0, [255, 255, 255]);
        $this->fillRect(7.1, 7.1, 581.1, 22.0, $this->template->colorAt($this->templatePage, 300, 32));
    }

    // ── Masquage ────────────────────────────────────────────────────────────

    /**
     * Recouvre une zone avec la couleur du fond lue au point d'échantillonnage
     * (par défaut, juste au-dessus à gauche de la zone).
     */
    public function mask(Box $box, ?float $sampleX = null, ?float $sampleY = null): void
    {
        $color = $this->template->colorAt(
            $this->templatePage,
            $sampleX ?? $box->x0 - 1.5,
            $sampleY ?? $box->y0 - 1.5,
        );
        $this->fillRect($box->x0, $box->y0, $box->width(), $box->height(), $color);
    }

    /**
     * @param array{0: int, 1: int, 2: int} $rgb
     */
    public function fillRect(float $x, float $y, float $w, float $h, array $rgb): void
    {
        if (self::$recordedZones !== null && $this->recording) {
            self::$recordedZones[$this->template->name()][$this->templatePage][] = [$x, $y, $x + $w, $y + $h];
        }
        $this->SetFillColor(...$rgb);
        $this->Rect($x, $y, $w, $h, 'F');
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public function backgroundAt(float $x, float $y): array
    {
        return $this->template->colorAt($this->templatePage, $x, $y);
    }

    // ── Texte ───────────────────────────────────────────────────────────────

    /**
     * @param array{0: int, 1: int, 2: int} $rgb
     */
    public function font(string $family, float $size, string $style = '', array $rgb = [0, 0, 0]): void
    {
        $this->SetFont($family, $style, $size);
        $this->SetTextColor(...$rgb);
    }

    /** Taille de police qui reproduit une ligne du modèle de hauteur $height. */
    public static function sizeForLineHeight(float $height): float
    {
        return round($height / self::LINE_BOX_EM * 2) / 2;
    }

    /**
     * Écrit une ligne dont la boîte (au sens pdftotext) commence en $top.
     *
     * @param 'L'|'C'|'R' $align alignement par rapport à $x (C et R : $x est le centre ou le bord droit)
     */
    public function textAt(float $x, float $top, string $text, string $align = 'L'): float
    {
        $width = $this->GetStringWidth($text);
        $left = match ($align) {
            'C' => $x - $width / 2,
            'R' => $x - $width,
            default => $x,
        };
        $baseline = $top + self::BASELINE_EM * $this->FontSizePt;
        $this->SetXY($left, $baseline - $this->getFontAscent($this->FontFamily, $this->FontStyle, $this->FontSizePt));
        $this->Cell($width + 0.01, 0, $text, 0, 0, 'L', false, '', 0, true, 'T', 'T');

        return $left + $width;
    }

    /**
     * Écrit une suite de segments de styles différents sur une même ligne.
     *
     * @param list<array{0: string, 1: string, 2?: float, 3?: string, 4?: array{0:int,1:int,2:int}}> $runs [famille, texte, taille, style, couleur]
     */
    public function runs(float $x, float $top, array $runs): float
    {
        // Lignes de base alignées sur celle du premier segment.
        $baseline = $top + self::BASELINE_EM * ($runs[0][2] ?? $this->FontSizePt);
        foreach ($runs as $run) {
            $size = $run[2] ?? $this->FontSizePt;
            $this->font($run[0], $size, $run[3] ?? '', $run[4] ?? [0, 0, 0]);
            $x = $this->textAt($x, $baseline - self::BASELINE_EM * $size, $run[1]);
        }

        return $x;
    }

    /**
     * Paragraphe justifié à gauche, coupé à la largeur $width.
     *
     * @return float ordonnée du haut de la ligne suivante
     */
    public function paragraph(float $x, float $top, float $width, string $text, float $leading, int $maxLines = PHP_INT_MAX): float
    {
        foreach ($this->wrap($text, $width) as $i => $line) {
            if ($i >= $maxLines) {
                break;
            }
            $this->textAt($x, $top, $line);
            $top += $leading;
        }

        return $top;
    }

    /**
     * Découpe un texte en lignes tenant dans $width avec la police courante.
     *
     * @return list<string>
     */
    public function wrap(string $text, float $width): array
    {
        $lines = [];
        foreach (preg_split('/\R/u', trim($text)) ?: [] as $paragraph) {
            $current = '';
            foreach (preg_split('/\s+/u', trim($paragraph)) ?: [] as $word) {
                $candidate = $current === '' ? $word : "$current $word";
                if ($current !== '' && $this->GetStringWidth($candidate) > $width) {
                    $lines[] = $current;
                    $current = $word;
                } else {
                    $current = $candidate;
                }
            }
            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * Réduit la police courante jusqu'à ce que le texte tienne dans $width.
     */
    public function fitWidth(string $text, float $width, float $minSize = 5.0): void
    {
        while ($this->FontSizePt > $minSize && $this->GetStringWidth($text) > $width) {
            $this->SetFontSize($this->FontSizePt - 0.25);
        }
    }

    public function fontSize(): float
    {
        return $this->FontSizePt;
    }

    // ── Images ──────────────────────────────────────────────────────────────

    /**
     * Image ajustée dans un cadre en conservant ses proportions, centrée.
     */
    public function imageFit(string $data, float $x, float $y, float $w, float $h): void
    {
        $size = @getimagesizefromstring($data);
        if ($size === false || $size[0] === 0 || $size[1] === 0) {
            return;
        }
        $scale = min($w / $size[0], $h / $size[1]);
        $iw = $size[0] * $scale;
        $ih = $size[1] * $scale;
        $this->Image('@' . $data, $x + ($w - $iw) / 2, $y + ($h - $ih) / 2, $iw, $ih);
    }

    public function picto(string $name, float $x, float $y, float $w = 0, float $h = 0): void
    {
        $this->Image(Assets::picto($name), $x, $y, $w, $h, 'PNG');
    }

    private function registerFonts(): void
    {
        $dir = Assets::fontDirectory();
        $this->AddFont(self::SANS, '', "$dir/ibmplexsans.php");
        $this->AddFont(self::SANS, 'B', "$dir/ibmplexsansb.php");
        $this->AddFont(self::SANS, 'I', "$dir/ibmplexsansi.php");
        $this->AddFont(self::SANS, 'BI', "$dir/ibmplexsansbi.php");
        $this->AddFont(self::SANS_MEDIUM, '', "$dir/ibmplexsansmedium.php");
        $this->AddFont(self::SANS_SEMIBOLD, '', "$dir/ibmplexsanssemib.php");
        $this->AddFont(self::COND, '', "$dir/ibmplexsanscondensed.php");
        $this->AddFont(self::COND, 'B', "$dir/ibmplexsanscondensedb.php");
        $this->AddFont(self::COND, 'I', "$dir/ibmplexsanscondensedi.php");
        $this->AddFont(self::COND_MEDIUM, '', "$dir/ibmplexsanscondensedmedium.php");
        $this->AddFont(self::COND_SEMIBOLD, '', "$dir/ibmplexsanscondensedsemib.php");
    }
}
