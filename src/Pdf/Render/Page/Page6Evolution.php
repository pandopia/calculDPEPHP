<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render\Page;

use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\Render\Canvas;
use CalculDpePHP\Pdf\Render\EnergyLabel;
use CalculDpePHP\Pdf\Render\Format;

/**
 * Page 6 : évolution de la performance après chaque pack de travaux.
 *
 * Les échelles sont redessinées (les repères du modèle chevauchent les
 * flèches) ; chaque état — actuel, après pack 1, après packs 1+2 — est un
 * encadré relié à sa classe. Les classes après travaux sont recalculées avec
 * les seuils réglementaires à partir des valeurs du pack.
 */
final class Page6Evolution implements PageRenderer
{
    private const PITCH = 26.65;
    private const ROW = 23.5;
    private const WIDTHS = [54.5, 73.8, 93.8, 115.3, 134.5, 153.9, 174.1];

    private const GES_PITCH = 19.9;
    private const GES_ROW = 17.6;
    private const GES_WIDTHS = [44.6, 59.5, 74.3, 87.1, 103.6, 118.6, 134.2];

    public function render(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $pdf->addTemplatePage(6);
        $t = $pdf->template();
        $etats = $this->etats($data);

        $haut = $t->anchor(6, '/extrêmement performant$/');
        $x = $haut->x0 + 0.5;
        $y = $haut->y1 + 2.0;
        $pdf->fillRect(42.0, $y - 1.0, 336.0, 7 * self::PITCH - 2.0, Canvas::WHITE);
        $rows = $this->energyScale($pdf, $x, $y);
        $this->boxes($pdf, $etats, $rows, $x, true, $y);

        $ges = $t->anchor(6, '/^peu d’émissions de CO/');
        $gx = $ges->x0 - 0.5;
        $gy = $ges->y1 + 1.9;
        $pdf->fillRect(58.0, $gy - 1.0, 300.0, 7 * self::GES_PITCH - 1.5, Canvas::WHITE);
        $gesRows = $this->gesScale($pdf, $gx, $gy);
        $this->boxes($pdf, $etats, $gesRows, $gx, false, $gy);
    }

    /**
     * États affichés de haut en bas : après 1+2, après 1, actuel.
     *
     * @return list<array{titre: string, packs: list<int>, ep: float, ges: float, dpe: string, classeGes: string}>
     */
    private function etats(DpeData $data): array
    {
        $packs = $data->packsTravaux();
        $etats = [];
        $final = $packs[3] ?? $packs[2] ?? null;
        if ($final !== null && $final['conso'] !== null && $final['ges'] !== null && isset($packs[1])) {
            $classes = $data->classesPour($final['conso'], $final['ges']);
            $etats[] = ['titre' => 'avec travaux', 'packs' => [1, 2], 'ep' => $final['conso'], 'ges' => $final['ges'], 'dpe' => $classes['dpe'], 'classeGes' => $classes['ges']];
        }
        $premier = $packs[1] ?? ($final !== null && !isset($packs[1]) ? $final : null);
        if ($premier !== null && $premier['conso'] !== null && $premier['ges'] !== null) {
            $classes = $data->classesPour($premier['conso'], $premier['ges']);
            $etats[] = ['titre' => 'avec travaux', 'packs' => isset($packs[1]) ? [1] : [2], 'ep' => $premier['conso'], 'ges' => $premier['ges'], 'dpe' => $classes['dpe'], 'classeGes' => $classes['ges']];
        }
        $etats[] = ['titre' => 'état actuel', 'packs' => [], 'ep' => $data->epConsoM2(), 'ges' => $data->gesM2(), 'dpe' => $data->classeDpe(), 'classeGes' => $data->classeGes()];

        return $etats;
    }

    /**
     * @return array<string, float> classe ⇒ ordonnée du milieu de la flèche
     */
    private function energyScale(Canvas $pdf, float $x, float $y): array
    {
        $centers = [];
        foreach (EnergyLabel::CLASSES as $k => $letter) {
            $top = $y + $k * self::PITCH;
            $right = $x + self::WIDTHS[$k];
            $pdf->SetFillColor(...EnergyLabel::DPE_COLORS[$letter]);
            $pdf->Polygon([$x, $top, $right - 7.9, $top, $right, $top + self::ROW / 2, $right - 7.9, $top + self::ROW, $x, $top + self::ROW], 'F');
            $pdf->font(Canvas::SANS, 15.5, 'B', Canvas::WHITE);
            $pdf->textAt($x + 5.7, $top + 0.9, $letter);
            $centers[$letter] = $top + self::ROW / 2;
        }

        return $centers;
    }

    /**
     * @return array<string, float>
     */
    private function gesScale(Canvas $pdf, float $x, float $y): array
    {
        $centers = [];
        foreach (EnergyLabel::CLASSES as $k => $letter) {
            $top = $y + $k * self::GES_PITCH;
            $pdf->SetFillColor(...EnergyLabel::GES_COLORS[$letter]);
            $pdf->RoundedRect($x, $top, self::GES_WIDTHS[$k], self::GES_ROW, self::GES_ROW / 2, '1100', 'F');
            $pdf->font(Canvas::SANS, 12, 'B', Canvas::WHITE);
            $pdf->textAt($x + 4.5, $top + 0.2, $letter);
            $centers[$letter] = $top + self::GES_ROW / 2;
        }

        return $centers;
    }

    /**
     * @param list<array{titre: string, packs: list<int>, ep: float, ges: float, dpe: string, classeGes: string}> $etats
     * @param array<string, float> $rows
     */
    private function boxes(Canvas $pdf, array $etats, array $rows, float $scaleX, bool $energie, float $scaleTop): void
    {
        $left = $energie ? 53.3 : 78.7;
        $width = $energie ? 110.1 : 86.4;
        $height = $energie ? 33.4 : 23.8;
        $gap = $energie ? 13.4 : 12.4;
        $first = $energie ? $scaleTop + 15.4 : $scaleTop + 27.6;

        foreach ($etats as $i => $etat) {
            $top = $first + $i * ($height + $gap);
            $pdf->SetLineStyle(['width' => 0.75, 'color' => Canvas::BLACK, 'dash' => 0]);
            $pdf->SetFillColor(...Canvas::WHITE);
            $pdf->RoundedRect($left, $top, $width, $height, 3.0, '1111', 'DF');

            $pdf->font(Canvas::SANS, $energie ? 8.5 : 8, 'B', Canvas::BLACK);
            $end = $pdf->textAt($left + 4.8, $top + 0.6, $etat['titre']);
            foreach ($etat['packs'] as $j => $pack) {
                if ($j > 0) {
                    $pdf->font(Canvas::SANS, 8.5, 'B', Canvas::BLACK);
                    $end = $pdf->textAt($end + 1.0, $top + 0.6, '+');
                }
                $this->pastille($pdf, $end + 6.2, $top + 8.1, $pack);
                $end += 11.4;
            }

            $pdf->font(Canvas::SANS, 6.5, '', Canvas::BLACK);
            if ($energie) {
                $pdf->textAt($left + 4.8, $top + 11.7, 'consommation : ' . Format::int($etat['ep']) . ' kWh/m²/an');
                $pdf->textAt($left + 4.8, $top + 20.1, 'émissions : ' . Format::int($etat['ges']) . ' kg CO₂/m²/an');
            } else {
                $pdf->textAt($left + 4.8, $top + 11.4, Format::int($etat['ges']) . ' kg CO₂/m²/an');
            }

            // Flèche « ↑ » vers l'état suivant (plus performant) au-dessus.
            if ($i > 0) {
                $ax = $left + ($energie ? 31.3 : 42.0);
                $pdf->SetLineStyle(['width' => 0.75, 'color' => Canvas::BLACK]);
                $pdf->Line($ax, $top - 2.0, $ax, $top - $gap + 3.0);
                $pdf->Polygon([$ax - 2.4, $top - $gap + 5.0, $ax, $top - $gap + 1.6, $ax + 2.4, $top - $gap + 5.0], 'F', [], [0, 0, 0]);
            }

            // Lien vers la classe atteinte.
            $classe = $energie ? $etat['dpe'] : $etat['classeGes'];
            $targetY = $rows[$classe] ?? $rows['G'];
            $midY = $top + $height / 2;
            $pdf->SetLineStyle(['width' => 0.75, 'color' => Canvas::BLACK]);
            $pdf->Line($left + $width, $midY, $left + $width + 6.0, $midY);
            $pdf->Line($left + $width + 6.0, $midY, $scaleX - 1.0, $targetY);
            $pdf->SetFillColor(0, 0, 0);
            $pdf->Circle($scaleX + 0.2, $targetY, 2.6, 0, 360, 'F');
        }
    }

    private function pastille(Canvas $pdf, float $cx, float $cy, int $numero): void
    {
        $pdf->SetFillColor(244, 152, 56);
        $pdf->Circle($cx, $cy, 4.85, 0, 360, 'F');
        $pdf->font(Canvas::SANS, 6.5, 'B', Canvas::WHITE);
        $pdf->textAt($cx + 0.1, $cy - 4.7, (string) $numero, 'C');
    }
}
