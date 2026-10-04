<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render;

/**
 * Étiquettes énergie (A→G, flèches) et climat (barres arrondies) du rapport,
 * dessinées en vectoriel selon la géométrie des modèles officiels du
 * 1er septembre 2025 (relevée sur les modèles 02 à 04, identique sur le 01 à
 * l'échelle près).
 *
 * Le modèle n'illustre qu'un logement classé E : la classe obtenue est mise en
 * avant (flèche agrandie cerclée de noir, chiffres à gauche), les autres
 * classes restent fines. Pour F et G (« passoire énergétique »), le
 * pictogramme officiel est relié à la flèche, comme sur les étiquettes de
 * l'archive graphique du ministère.
 */
final class EnergyLabel
{
    public const CLASSES = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

    /** Couleurs des classes énergie relevées dans le modèle (RVB 0-255). */
    public const DPE_COLORS = [
        'A' => [0, 160, 109],
        'B' => [82, 177, 83],
        'C' => [165, 204, 116],
        'D' => [244, 231, 15],
        'E' => [240, 181, 15],
        'F' => [235, 130, 53],
        'G' => [215, 34, 31],
    ];

    /** Couleurs des classes climat relevées dans le modèle. */
    public const GES_COLORS = [
        'A' => [164, 219, 248],
        'B' => [140, 180, 211],
        'C' => [119, 146, 177],
        'D' => [96, 111, 143],
        'E' => [77, 82, 113],
        'F' => [57, 53, 81],
        'G' => [40, 27, 53],
    ];

    public const ROW = 23.47;
    public const PITCH = 26.7;
    public const BIG = 45.4;
    public const BAR_BASE = 46.0;
    public const BAR_STEP = 18.27;
    public const TIP = 7.94;
    /** Largeur de l'encadré chiffré à gauche de la classe obtenue. */
    public const BOX = 98.7;

    public const GES_ROW = 10.6;
    public const GES_PITCH = 12.03;
    public const GES_BIG = 20.4;
    public const GES_WIDTHS = ['A' => 23.2, 'B' => 31.6, 'C' => 39.7, 'D' => 48.1, 'E' => 59.8, 'F' => 71.4, 'G' => 79.7];

    public function __construct(private readonly Canvas $pdf)
    {
    }

    /** Hauteur totale de l'échelle énergie. */
    public static function height(): float
    {
        return 5 * self::PITCH + self::BIG + 3.2 + self::ROW;
    }

    /**
     * Échelle énergie avec la classe obtenue mise en avant.
     *
     * @param float $x bord gauche des flèches
     * @param float $y haut de la flèche A
     * @param array{ep: string, ges: string, ef: ?string, unite: string} $valeurs
     */
    public function energie(float $x, float $y, string $classe, array $valeurs, string $passoireLibelle = 'passoire énergétique'): void
    {
        $index = array_search($classe, self::CLASSES, true);
        $index = $index === false ? 6 : $index;
        $top = $y;
        $bigTop = $y;
        $tipBig = 0.0;
        $rowTops = [];

        foreach (self::CLASSES as $k => $letter) {
            $rowTops[$letter] = $top;
            $normalTip = $x + self::BAR_BASE + self::BAR_STEP * $k + self::TIP;
            if ($k === $index) {
                $bigTop = $top;
                $tipBig = $normalTip + 7.8;
                $this->bigArrow($x - self::BOX - 0.0, $x, $top, $tipBig, self::DPE_COLORS[$letter], $letter);
                $top += self::BIG + 3.2;
                continue;
            }
            $this->arrow($x, $top, $normalTip, self::ROW, self::TIP, self::DPE_COLORS[$letter]);
            $this->pdf->font(Canvas::SANS, 15.5, 'B', [255, 255, 255]);
            $this->pdf->textAt($x + 6.4, $top + 0.9, $letter);
            $top += self::PITCH;
        }

        $this->valueBox($x - self::BOX, $bigTop, $x, $valeurs);

        if ($index >= 5) {
            $this->passoireIcon($tipBig, $bigTop, $index === 6 ? $rowTops['E'] : $rowTops['D'], $passoireLibelle);
        } else {
            // Repère gris « passoire énergétique » à gauche des classes F et G.
            $grey = [157, 157, 156];
            $this->pdf->SetDrawColor(...$grey);
            $this->pdf->SetLineWidth(1.5);
            $this->pdf->Line($x - 2.0, $rowTops['F'] - 0.2, $x - 2.0, $rowTops['G'] + self::ROW);
            $this->pdf->font(Canvas::SANS, 7, '', $grey);
            $mid = ($rowTops['F'] + $rowTops['G'] + self::ROW) / 2;
            $words = explode(' ', $passoireLibelle, 2);
            $this->pdf->textAt($x - 10.0, $mid - 9.6, $words[0], 'R');
            $this->pdf->textAt($x - 10.0, $mid - 1.2, $words[1] ?? '', 'R');
        }
    }

    /**
     * @param array{0:int,1:int,2:int} $rgb
     */
    private function arrow(float $x, float $top, float $tipX, float $h, float $tip, array $rgb): void
    {
        $this->pdf->SetFillColor(...$rgb);
        $this->pdf->Polygon([
            $x, $top,
            $tipX - $tip, $top,
            $tipX, $top + $h / 2,
            $tipX - $tip, $top + $h,
            $x, $top + $h,
        ], 'F');
    }

    /**
     * Flèche agrandie de la classe obtenue, cerclée de noir, solidaire de
     * l'encadré chiffré.
     *
     * @param array{0:int,1:int,2:int} $rgb
     */
    private function bigArrow(float $boxLeft, float $x, float $top, float $tipX, array $rgb, string $letter): void
    {
        $h = self::BIG;
        $tip = $h / 2;
        $this->pdf->SetLineStyle(['width' => 1.4, 'color' => [0, 0, 0], 'join' => 'miter', 'cap' => 'butt']);
        $this->pdf->SetFillColor(...$rgb);
        $this->pdf->Polygon([
            $x - 0.7, $top + 0.7,
            $tipX - $tip, $top + 0.7,
            $tipX - 0.9, $top + $h / 2,
            $tipX - $tip, $top + $h - 0.7,
            $x - 0.7, $top + $h - 0.7,
        ], 'DF');

        // Lettre blanche cernée de noir.
        $this->pdf->font(Canvas::SANS, 28, 'B', [255, 255, 255]);
        $this->pdf->setTextRenderingMode(1.1, true, false);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->textAt($x + 7.0, $top + 3.6, $letter);
        $this->pdf->setTextRenderingMode(0, true, false);
    }

    /**
     * @param array{ep: string, ges: string, ef: ?string, unite: string} $v
     */
    private function valueBox(float $left, float $top, float $right, array $v): void
    {
        $h = self::BIG;
        $this->pdf->SetLineStyle(['width' => 1.4, 'color' => [0, 0, 0]]);
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->RoundedRect($left + 0.7, $top + 0.7, $right - $left, $h - 1.4, 2.5, '0011', 'DF');
        $mid = ($left + $right) / 2 + 0.3;
        $this->pdf->SetLineWidth(1.4);
        $this->pdf->Line($mid, $top + 4.6, $mid, $top + $h - 4.6);

        $c1 = ($left + $mid) / 2 + 0.6;
        $c2 = ($mid + $right) / 2;

        $this->pdf->font(Canvas::SANS, 6.5, '', [0, 0, 0]);
        $this->pdf->textAt($c1, $top - 21.0, 'consommation', 'C');
        $this->pdf->textAt($c1, $top - 12.6, '(énergie primaire)', 'C');
        $this->pdf->textAt($c2, $top - 12.6, 'émissions', 'C');

        $this->pdf->font(Canvas::SANS, 20.5, 'B');
        $this->pdf->fitWidth($v['ep'], $mid - $left - 6);
        $this->pdf->textAt($c1, $top + 3.0, $v['ep'], 'C');
        $this->pdf->font(Canvas::SANS, 20.5, 'B');
        $this->pdf->fitWidth($v['ges'] . '*', $right - $mid - 6);
        $size = $this->pdf->fontSize();
        $end = $this->pdf->textAt($c2 - 2.2, $top + 3.0, $v['ges'], 'C');
        $this->pdf->font(Canvas::SANS, $size * 0.45, 'B');
        $this->pdf->textAt($end + 0.3, $top + 5.6, '*');

        $this->pdf->font(Canvas::SANS, 6.5);
        $this->pdf->textAt($c1, $top + 27.6, $v['unite'], 'C');
        $this->pdf->textAt($c2, $top + 27.6, 'kg CO₂/m²/an', 'C');

        if ($v['ef'] !== null) {
            $this->pdf->font(Canvas::SANS, 6.5, '', [157, 157, 156]);
            $this->pdf->textAt($c1, $top + $h + 1.6, $v['ef'] . ' ' . $v['unite'], 'C');
            $this->pdf->textAt($c1, $top + $h + 10.0, 'd’énergie finale', 'C');
        }
    }

    /**
     * Pictogramme « passoire énergétique » relié à la pointe de la flèche.
     */
    private function passoireIcon(float $tipX, float $bigTop, float $circleRowTop, string $libelle): void
    {
        $r = 21.0;
        $cx = $tipX + 13.0;
        $cy = $circleRowTop + self::ROW / 2 + 4.0;
        $tipY = $bigTop + self::BIG / 2;

        $this->pdf->SetLineStyle(['width' => 1.4, 'color' => [0, 0, 0]]);
        // Lien : descend du cercle puis revient en arc vers la pointe.
        $this->pdf->Line($cx, $cy + $r, $cx, $tipY - 6.0);
        $this->pdf->Curve($cx, $tipY - 6.0, $cx, $tipY - 1.0, $cx - 3.0, $tipY, $tipX - 0.5, $tipY, 'D');
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Circle($cx, $cy, $r, 0, 360, 'DF');
        $this->pdf->Image(Assets::picto('passoire_maison'), $cx - $r * 0.72, $cy - $r * 0.72, $r * 1.44, $r * 1.44, 'PNG');

        $this->pdf->font(Canvas::SANS, 7, 'B', [228, 30, 37]);
        $words = explode(' ', $libelle, 2);
        $this->pdf->textAt($cx, $cy - $r - 18.0, $words[0], 'C');
        $this->pdf->textAt($cx, $cy - $r - 9.6, $words[1] ?? '', 'C');
    }

    /**
     * Échelle climat (émissions de gaz à effet de serre).
     *
     * @param float $x bord gauche des barres
     * @param float $y haut de la barre A
     */
    public function ges(float $x, float $y, string $classe, string $valeur): void
    {
        $top = $y;
        foreach (self::CLASSES as $letter) {
            $w = self::GES_WIDTHS[$letter];
            if ($letter === $classe) {
                $h = self::GES_BIG;
                $top += 0.1;
                $this->pdf->SetLineStyle(['width' => 1.0, 'color' => [0, 0, 0]]);
                $this->pdf->SetFillColor(...self::GES_COLORS[$letter]);
                $this->pdf->RoundedRect($x + 0.5, $top + 0.5, $w - 1.0, $h - 1.0, ($h - 1.0) / 2, '1100', 'DF');
                $this->pdf->font(Canvas::SANS, 14.5, 'B', [255, 255, 255]);
                $this->pdf->setTextRenderingMode(0.8, true, false);
                $this->pdf->textAt($x + 3.6, $top + 0.9, $letter);
                $this->pdf->setTextRenderingMode(0, true, false);

                $lineY = $top + $h / 2;
                $this->pdf->SetLineWidth(0.9);
                $this->pdf->Line($x + 60.8, $lineY, $x + 82.0, $lineY);
                $this->pdf->font(Canvas::SANS, 13.5, 'B', [0, 0, 0]);
                $end = $this->pdf->textAt($x + 84.1, $lineY - 9.3, $valeur);
                $this->pdf->font(Canvas::SANS, 5.5);
                $this->pdf->textAt($end + 1.5, $lineY - 1.8, 'kg CO₂/m²/an');
                $top += $h + 1.5;
                continue;
            }
            $this->pdf->SetFillColor(...self::GES_COLORS[$letter]);
            $this->pdf->RoundedRect($x, $top, $w, self::GES_ROW, self::GES_ROW / 2, '1100', 'F');
            $this->pdf->font(Canvas::SANS, 7.5, 'B', [255, 255, 255]);
            $this->pdf->textAt($x + 3.0, $top - 0.6, $letter);
            $top += self::GES_PITCH;
        }
    }
}
