<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render\Page;

use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\Render\Canvas;
use CalculDpePHP\Pdf\Render\Format;
use CalculDpePHP\Pdf\Render\RowTable;

/**
 * Page 5 : packs de travaux (descriptif_travaux) et commentaires.
 *
 * Le modèle prévoit deux packs : « 1 » les travaux essentiels, « 2 » les
 * travaux à envisager. Dans le XML, enum_num_pack_travaux_id vaut 1, 2 ou
 * 3 (« 1+2 ») ; à défaut de pack 2, c'est le pack 3 qui porte les travaux à
 * envisager. Si les lots ne tiennent pas sur la page, la section continue sur
 * une page de suite au même fond.
 */
final class Page5Travaux implements PageRenderer
{
    private const STRIPE = [255, 244, 233];
    private const PACK_ORANGE = [244, 152, 56];
    private const BOTTOM = 800.0;

    /** enum_lot_travaux_id ⇒ [libellé, pictogramme]. */
    public const LOTS = [
        1 => ['murs', 'picto_p4_logement_murs'],
        2 => ['plancher bas', 'picto_p4_logement_sol'],
        3 => ['toiture et combles', 'picto_p4_logement_toit'],
        4 => ['portes et fenêtres', 'picto_p4_logement_huisserie'],
        5 => ['chauffage', 'picto_p3_conso_chauffage'],
        6 => ['eau chaude sanitaire', 'picto_p3_conso_eau_chaude'],
        7 => ['climatisation', 'picto_p3_conso_climatisation'],
        8 => ['ventilation', 'picto_p3_conso_ventilation'],
        9 => ['énergies renouvelables', 'picto_p4_panneaux_solaires'],
    ];

    private float $y = 0.0;

    public function render(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $pdf->addTemplatePage(5);
        $t = $pdf->template();
        $premier = $t->anchor(5, '/^Les travaux essentiels/');
        $pdf->fillRect(30.0, $premier->y0 - 12.0, 535.0, 812.0 - $premier->y0, $pdf->backgroundAt(20.0, 300.0));
        $this->y = $premier->y0 - 2.0;

        $packs = $data->packsTravaux();
        $affiches = array_filter([
            1 => $packs[1] ?? null,
            2 => $packs[2] ?? $packs[3] ?? null,
        ]);
        foreach ($affiches as $numero => $pack) {
            $this->pack($pdf, $numero, $pack);
            $this->y += 14.0;
        }
        if ($affiches === []) {
            $this->ensureSpace($pdf, 40);
            $pdf->font(Canvas::SANS, 10, '', Canvas::BLACK);
            $pdf->textAt(40.0, $this->y, 'Aucun pack de travaux n’est proposé pour ce logement.');
            $this->y += 30;
        }

        $this->commentaires($pdf, $extra->commentaires ?? $data->commentaireTravaux());
    }

    /**
     * @param array{conso: ?float, ges: ?float, cout_min: ?float, cout_max: ?float, travaux: list<array{lot: int, description: string, avertissement: ?string, performance: ?string}>} $pack
     */
    private function pack(Canvas $pdf, int $numero, array $pack): void
    {
        $table = new RowTable($pdf, 38.0, 556.6, 69.2, 186.3, 415.0, 424.1, self::STRIPE);
        $rows = self::rows($pack['travaux']);
        $first = $rows === [] ? 0.0 : $table->height($rows[0]);
        $this->ensureSpace($pdf, 70.0 + $first);

        // Pastille numérotée et titre.
        $cy = $this->y + 9.5;
        $pdf->SetFillColor(...self::PACK_ORANGE);
        $pdf->Circle(59.35, $cy, 17.15, 0, 360, 'F');
        $pdf->font(Canvas::SANS, 20, 'B', Canvas::WHITE);
        $pdf->textAt(59.6, $cy - 14.0, (string) $numero, 'C');
        $montant = $pack['cout_min'] !== null && $pack['cout_max'] !== null
            ? sprintf(' montant estimé : %s à %s€', Format::int($pack['cout_min']), Format::int($pack['cout_max']))
            : '';
        $pdf->runs(88.2, $this->y, [
            [Canvas::SANS, $numero === 1 ? 'Les travaux essentiels' : 'Les travaux à envisager', 13, 'B', Canvas::BLACK],
            [Canvas::SANS, $montant, 9, '', Canvas::BLACK],
        ]);
        $this->y += 23.2;

        // En-tête du tableau sur panneau blanc.
        $pdf->fillRect(34.0, $this->y, 527.3, 32.1, Canvas::WHITE);
        $pdf->font(Canvas::COND_SEMIBOLD, 9, '', Canvas::BLACK);
        $pdf->textAt(70.3, $this->y + 11.1, 'lot');
        $pdf->textAt(186.3, $this->y + 11.1, 'description');
        $pdf->textAt(424.1, $this->y + 11.1, 'performance recommandée');
        $this->y += 32.1;

        foreach ($rows as $i => $row) {
            $h = $table->height($row);
            if ($this->y + $h + 4.0 > self::BOTTOM) {
                $this->continuation($pdf);
                $pdf->fillRect(34.0, $this->y, 527.3, 4.0, Canvas::WHITE);
                $this->y += 4.0;
            }
            $pdf->fillRect(34.0, $this->y, 527.3, $h, Canvas::WHITE);
            $this->y = $table->draw($row, $this->y, $i % 2 === 0);
        }
        $pdf->fillRect(34.0, $this->y, 527.3, 4.5, Canvas::WHITE);
        $this->y += 4.5;
    }

    /**
     * Regroupe les travaux consécutifs d'un même lot en une ligne.
     *
     * @param list<array{lot: int, description: string, avertissement: ?string, performance: ?string}> $travaux
     * @return list<array{picto: ?string, label: string, paragraphs: list<array{text: string, style?: string, value?: ?string, warning?: bool}>}>
     */
    public static function rows(array $travaux): array
    {
        $rows = [];
        $lastLot = null;
        foreach ($travaux as $t) {
            [$label, $picto] = self::LOTS[$t['lot']] ?? ['autre', 'picto_p4_generique'];
            // Un lot très détaillé est réparti sur plusieurs lignes pour qu'aucune
            // ne dépasse une page.
            $taille = $lastLot === $t['lot'] ? mb_strlen(implode(' ', array_column($rows[count($rows) - 1]['paragraphs'], 'text'))) : 0;
            if ($t['lot'] !== $lastLot || $taille > 700) {
                $rows[] = ['picto' => $picto, 'label' => $label, 'paragraphs' => []];
                $lastLot = $t['lot'];
            }
            $current = &$rows[count($rows) - 1];
            $lines = preg_split('/\R/u', trim($t['description'])) ?: [];
            foreach ($lines as $i => $line) {
                if (trim($line) === '') {
                    continue;
                }
                $current['paragraphs'][] = [
                    'text' => trim($line),
                    'style' => $i === 0 ? '' : 'I',
                    'value' => $i === 0 ? $t['performance'] : null,
                ];
            }
            if ($t['avertissement'] !== null) {
                $current['paragraphs'][] = ['text' => $t['avertissement'], 'style' => 'I', 'warning' => true];
            }
            unset($current);
        }

        return $rows;
    }

    private function commentaires(Canvas $pdf, ?string $commentaire): void
    {
        $pdf->font(Canvas::SANS, 9, '', Canvas::BLACK);
        $lines = $commentaire === null ? [] : $pdf->wrap($commentaire, 510.0);
        $height = max(88.6, 34.0 + count($lines) * 10.8);
        $this->ensureSpace($pdf, min($height, 200.0));
        $pdf->fillRect(34.0, $this->y, 527.3, min($height, self::BOTTOM + 5 - $this->y), Canvas::WHITE);
        $pdf->font(Canvas::SANS, 13, 'B', Canvas::BLACK);
        $pdf->textAt(38.3, $this->y + 1.0, 'Commentaires :');
        $pdf->font(Canvas::SANS, 9, '', Canvas::BLACK);
        $y = $this->y + 22.0;
        foreach ($lines as $line) {
            if ($y > self::BOTTOM - 8) {
                break;
            }
            $pdf->textAt(38.3, $y, $line);
            $y += 10.8;
        }
    }

    private function ensureSpace(Canvas $pdf, float $height): void
    {
        if ($this->y + $height > self::BOTTOM) {
            $this->continuation($pdf);
        }
    }

    /** Page de suite : même fond, corps vidé sous le bandeau de titre. */
    private function continuation(Canvas $pdf): void
    {
        $pdf->addTemplatePage(5);
        $titre = $pdf->template()->anchor(5, '/^Recommandations d’amélioration de la performance/');
        $top = $titre->y1 + 8.0;
        // Page de suite : ce qu'on y recouvre (l'introduction fixe) reste dans
        // le modèle, les zones de cette page ne sont pas relevées.
        $pdf->recording = false;
        $pdf->fillRect(30.0, $top, 535.0, 812.0 - $top, $pdf->backgroundAt(20.0, 300.0));
        $this->y = $top + 6.0;
    }
}
