<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render\Page;

use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\Render\Assets;
use CalculDpePHP\Pdf\Render\Canvas;
use CalculDpePHP\Pdf\Render\Format;
use CalculDpePHP\Pdf\Template\Box;
use CalculDpePHP\Pdf\Template\TemplateVariant;

/**
 * Page 2 : déperditions, qualité de l'isolation, ventilation, confort d'été,
 * énergies renouvelables.
 */
final class Page2Enveloppe implements PageRenderer
{
    /** Couleurs [fond, bandeau] des niveaux, relevées sur les pictogrammes officiels. */
    private const NIVEAUX = [
        'insuffisant' => [[250, 209, 194], [229, 26, 34]],
        'moyen' => [[254, 231, 208], [244, 152, 55]],
        'bon' => [[235, 243, 223], [164, 204, 116]],
        'tres_bon' => [[219, 237, 226], [41, 176, 133]],
    ];
    private const NEUTRE = [[237, 237, 237], [198, 198, 198]];

    /** enum_categorie_enr_descriptif_id ⇒ [libellé, picto actif, picto « solution »]. */
    private const ENR = [
        1 => ['pompe à chaleur', 'picto_p2_er_pompe_a_chaleur_actif', 'picto_p2_er_small_pompe_a_chaleur'],
        2 => ['chauffe-eau thermodynamique', 'picto_p2_er_thermodynamique_actif', 'picto_p2_er_small_chauffe_eau_thermodynamique'],
        3 => ['panneaux solaires photovoltaïques', 'picto_p2_er_solaire_photovoltaique_actif', 'picto_p2_er_small_solaire_photovoltaique'],
        4 => ['panneaux solaires thermiques', 'picto_p2_er_solaire_thermique_actif', 'picto_p2_er_small_solaire_thermique'],
        5 => ['géothermie', 'picto_p2_er_geothermie_actif', 'picto_p2_er_small_geothermie'],
        6 => ['réseau de chaleur ou de froid vertueux', 'picto_p2_er_reseau_vertueux_actif', 'picto_p2_er_small_reseau_chaleur'],
        7 => ['chauffage au bois', 'picto_p2_er_chauffage_bois_actif', 'picto_p2_er_small_chauffage_bois'],
        8 => ['éolienne', 'picto_p2_er_eolien_actif', null],
        9 => ['cogénération', 'picto_p2_er_cogeneration_actif', null],
    ];

    public function render(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $pdf->addTemplatePage(2);
        $this->deperditions($pdf, $data);
        $this->isolation($pdf, $data);
        $this->ventilation($pdf, $data);
        $this->confortEte($pdf, $data);
        $this->renouvelables($pdf, $data);
    }

    private function deperditions(Canvas $pdf, DpeData $data): void
    {
        $parts = $data->partsDeperditions();
        $boxes = [];
        $page = $pdf->template()->linesIn(2, 30, 95, 300, 310);
        foreach ($page as $box) {
            if (preg_match('/^\d+\s?%$/u', $box->text) !== 1) {
                continue;
            }
            $key = match (true) {
                $box->y0 < 160 && $box->x0 < 150 => 'ventilation',
                $box->y0 < 160 => 'toiture',
                $box->y0 < 240 && $box->x0 > 200 => 'murs',
                $box->y0 < 240 => 'menuiseries',
                $box->x0 < 140 => 'ponts',
                default => 'plancher',
            };
            $boxes[$key] = $box;
        }

        $values = self::pourcentagesArrondis($parts);
        foreach ($boxes as $key => $box) {
            $rightAligned = in_array($key, ['ventilation', 'menuiseries', 'ponts'], true);
            $mask = $rightAligned
                ? new Box($box->x1 - 42, $box->y0 + 7.5, $box->x1 + 0.6, $box->y1 - 3)
                : new Box($box->x0 - 0.6, $box->y0 + 7.5, $box->x0 + 42, $box->y1 - 3);
            $pdf->fillRect($mask->x0, $mask->y0, $mask->width(), $mask->height(), Canvas::WHITE);
            $pdf->font(Canvas::SANS, 20.5, 'B', Canvas::BLACK);
            $pdf->textAt($rightAligned ? $box->x1 : $box->x0, $box->y0 + 1.2, $values[$key] . '%', $rightAligned ? 'R' : 'L');
        }
    }

    /**
     * Parts entières arrondies chacune au plus proche, comme sur les rapports
     * d'exemple (la somme peut différer de 100 d'une unité).
     *
     * @param array<string, float> $parts
     * @return array<string, int>
     */
    public static function pourcentagesArrondis(array $parts): array
    {
        return array_map(static fn (float $v): int => (int) round($v), $parts);
    }

    private function isolation(Canvas $pdf, DpeData $data): void
    {
        $label = $pdf->template()->anchor(2, '/^INSUFFISANTE$/');
        $barTop = $label->y0 - 1.0;
        $niveau = match ($data->qualiteIsolation()) {
            1 => 'tres_bon',
            2 => 'bon',
            3 => 'moyen',
            4 => 'insuffisant',
            default => null,
        };
        $this->strip($pdf, 309.0, $barTop - 66.6, 58.7, 62.9, 66.6, [
            'insuffisant' => 'INSUFFISANTE',
            'moyen' => 'MOYENNE',
            'bon' => 'BONNE',
            'tres_bon' => 'TRÈS BONNE',
        ], $niveau, 'icone_isolation_', [
            'insuffisant' => 'insuffisante',
            'moyen' => 'moyenne',
            'bon' => 'bonne',
            'tres_bon' => 'tres_bonne',
        ], 43.6, 49.3);
    }

    /**
     * Bandeau de niveaux (isolation ou confort d'été) : une case par niveau,
     * celle du niveau atteint en couleur avec son pictogramme.
     *
     * @param array<string, string> $labels
     * @param array<string, string> $icons
     */
    private function strip(Canvas $pdf, float $x, float $top, float $w, float $pitch, float $h, array $labels, ?string $selected, string $iconPrefix, array $icons, float $iconW, float $iconH): void
    {
        $i = 0;
        foreach ($labels as $key => $text) {
            [$bg, $bar] = $key === $selected ? self::NIVEAUX[$key] : self::NEUTRE;
            $left = $x + $i * $pitch;
            $pdf->fillRect($left, $top, $w, $h, $bg);
            $pdf->fillRect($left, $top + $h, $w, 14.2, $bar);
            if ($key === $selected) {
                $pdf->Image(Assets::picto($iconPrefix . $icons[$key]), $left + ($w - $iconW) / 2, $top + ($h - $iconH) / 2, $iconW, $iconH, 'PNG');
            }
            $pdf->font(Canvas::COND_MEDIUM, 6.5, '', Canvas::WHITE);
            $pdf->textAt($left + $w / 2, $top + $h + 2.4, $text, 'C');
            $i++;
        }
    }

    private function ventilation(Canvas $pdf, DpeData $data): void
    {
        $titre = $pdf->template()->anchor(2, '/^Système de ventilation en place/');
        $pdf->fillRect(381.0, $titre->y1 + 6.0, 178.0, 84.0, Canvas::WHITE);
        $descriptifs = $data->descriptifs(8);
        $pdf->font(Canvas::SANS, 8.5, '', Canvas::BLACK);
        $rows = [];
        foreach ($descriptifs === [] ? ['Non renseigné'] : $descriptifs as $d) {
            array_push($rows, ...$pdf->wrap($d, 172));
        }
        $rows = array_slice($rows, 0, 7);
        $leading = 10.6;
        $top = $titre->y1 + 44.5 - count($rows) * $leading / 2;
        foreach ($rows as $i => $row) {
            $pdf->textAt(382.5, $top + $i * $leading, $row);
        }
    }

    private function confortEte(Canvas $pdf, DpeData $data): void
    {
        $t = $pdf->template();
        $confort = $data->confortEte();
        $batiment = $data->variant() === TemplateVariant::IMMEUBLE;
        $mot = $batiment ? 'bâtiment' : 'logement';

        if (!$batiment) {
            $label = $t->anchor(2, '/^INSUFFISANT$/');
            $niveau = match ($confort['indicateur'] ?? null) {
                1 => 'insuffisant',
                2 => 'moyen',
                3 => 'bon',
                default => null,
            };
            $this->strip($pdf, 38.2, $label->y0 - 1.3 - 68.4, 79.9, 84.1, 68.4, [
                'insuffisant' => 'INSUFFISANT',
                'moyen' => 'MOYEN',
                'bon' => 'BON',
            ], $niveau, 'icone_confort_', [
                'insuffisant' => 'insuffisant',
                'moyen' => 'moyen',
                'bon' => 'bon',
            ], 40.0, 40.0);
        }

        $titre = $t->anchor(2, '/^Les caractéristiques de votre (logement|bâtiment) améliorant/');
        $note = $t->find(2, '/^\*Le niveau de confort d’été présenté/');
        $bottom = $note !== null ? $note->y0 - 4.0 : $t->whiteUntil(2, 36.0, $titre->y1) - 4.0;
        $pdf->fillRect(35.0, $titre->y0 - 1.0, 254.0, $bottom - $titre->y0, Canvas::WHITE);
        if ($confort === null) {
            $pdf->font(Canvas::SANS, 8, '', Canvas::GREY);
            $pdf->textAt($titre->x0, $titre->y0, 'Caractéristiques de confort d’été non renseignées.');

            return;
        }

        $atouts = [];
        if ($confort['inertie_lourde']) {
            $atouts[] = ['picto_p2_ete_actif_isolation_murs', "bonne inertie du $mot"];
        }
        if ($confort['aspect_traversant']) {
            $atouts[] = ['picto_p2_ete_actif_traversant', "$mot traversant"];
        }
        if ($confort['protection_solaire_exterieure']) {
            $atouts[] = ['picto_p2_ete_actif_volets', 'fenêtres équipées de volets extérieurs'];
        }
        if ($confort['isolation_toiture']) {
            $atouts[] = ['picto_p2_ete_actif_isolation_toit', 'toiture isolée'];
        }
        if ($confort['brasseur_air']) {
            $atouts[] = ['picto_p2_ete_ventilateur', 'présence de brasseurs d’air'];
        }

        $conseils = [];
        if (!$confort['protection_solaire_exterieure']) {
            $conseils[] = ['picto_p2_ete_inactif_volets', "Équipez les fenêtres de votre $mot de volets extérieurs ou brise-soleil."];
        }
        if (!$confort['isolation_toiture']) {
            $conseils[] = ['picto_p2_ete_inactif_isolation_toit', $batiment
                ? 'Faites isoler la toiture du bâtiment.'
                : ($data->variant()->isAppartement()
                    ? "Faites isoler la toiture de votre logement.\n(rapprochez-vous de votre copropriété)."
                    : 'Faites isoler la toiture de votre logement.')];
        }

        $y = $titre->y0;
        if ($atouts !== []) {
            $pdf->font(Canvas::SANS_SEMIBOLD, 8.5, '', Canvas::BLACK);
            $y = $pdf->paragraph($titre->x0, $y, 240, "Les caractéristiques de votre $mot améliorant le confort d’été :", 10.8);
            $y += 8;
            foreach (array_chunk($atouts, 2) as $pair) {
                $rowHeight = 0;
                foreach ($pair as $col => [$picto, $text]) {
                    $cx = $col === 0 ? 92.0 : 220.0;
                    $pdf->picto($picto, $cx - 22.5, $y, 45.0, 45.0);
                    $pdf->font(Canvas::SANS, 8.5, '', Canvas::BLACK);
                    $rows = $pdf->wrap($text, 120);
                    foreach ($rows as $i => $row) {
                        $pdf->textAt($cx, $y + 48 + $i * 10.8, $row, 'C');
                    }
                    $rowHeight = max($rowHeight, 52 + count($rows) * 10.8);
                }
                $y += $rowHeight + 4;
            }
        }

        if ($conseils !== []) {
            $y += 4;
            $pdf->SetDrawColor(...Canvas::GREY);
            $pdf->SetLineStyle(['width' => 0.6, 'dash' => '1.5,2', 'color' => Canvas::GREY]);
            $pdf->Line(38.0, $y - 6, 286.0, $y - 6);
            $pdf->SetLineStyle(['width' => 0.6, 'dash' => 0]);
            $pdf->font(Canvas::SANS_SEMIBOLD, 8.5, '', Canvas::BLACK);
            $pdf->textAt($titre->x0 - 0.4, $y, 'Pour améliorer le confort d’été :');
            $y += 22;
            foreach ($conseils as [$picto, $text]) {
                $pdf->picto($picto, 41.0, $y - 4, 30.0, 30.0);
                $pdf->font(Canvas::SANS, 8.5, '', Canvas::BLACK);
                $end = $pdf->paragraph(81.7, $y, 200, $text, 10.8);
                $y = max($end, $y + 30) + 8;
            }
        }
    }

    private function renouvelables(Canvas $pdf, DpeData $data): void
    {
        $presentes = array_values(array_filter($data->enrPresentes(), static fn (int $id): bool => isset(self::ENR[$id])));
        $titre = $pdf->template()->anchor(2, '/^Production d’énergies renouvelables/');
        $pdf->fillRect(306.0, $titre->y1 + 5.0, 253.5, 285.0, Canvas::WHITE);
        $mot = $data->variant()->isBatiment() ? 'ce bâtiment' : 'ce logement';
        $y = $titre->y1 + 12.6;

        if ($presentes === []) {
            $pdf->font(Canvas::SANS_SEMIBOLD, 8.5, '', Canvas::BLACK);
            $y = $pdf->paragraph(311.3, $y, 240, Format::ucfirst($mot) . ' n’est pas encore équipé de systèmes de production d’énergies renouvelables.', 10.8);
            $y += 24.5;
            $pdf->font(Canvas::SANS_SEMIBOLD, 8.5, '', Canvas::BLACK);
            $pdf->textAt(311.3, $y, 'Diverses solutions existent :');
            $y += 26.0;
        } else {
            $pdf->font(Canvas::SANS_SEMIBOLD, 8.5, '', Canvas::BLACK);
            $pdf->textAt(311.3, $y, "Équipement(s) présent(s) dans $mot :");
            $y += 18;
            foreach (array_chunk($presentes, 2) as $pair) {
                foreach ($pair as $col => $id) {
                    [$label, $actif] = self::ENR[$id];
                    $x = $col === 0 ? 314.0 : 438.0;
                    $pdf->picto($actif, $x, $y, 40.0, 40.0);
                    $pdf->font(Canvas::SANS, 8, '', Canvas::BLACK);
                    $pdf->paragraph($x + 43.0, $y + 8, 74, $label, 9.8, 3);
                }
                $y += 48;
            }
            $y += 6;
            $pdf->font(Canvas::SANS_SEMIBOLD, 8.5, '', Canvas::BLACK);
            $pdf->textAt(311.3, $y, 'D’autres solutions d’énergies renouvelables existent :');
            $y += 20;
        }

        $autres = array_values(array_filter(array_keys(self::ENR), static fn (int $id): bool => !in_array($id, $presentes, true) && self::ENR[$id][2] !== null));
        foreach (array_chunk($autres, 2) as $pair) {
            foreach ($pair as $col => $id) {
                [$label, , $small] = self::ENR[$id];
                $x = $col === 0 ? 318.0 : 442.0;
                $pdf->picto((string) $small, $x, $y, 22.0, 22.0);
                $pdf->font(Canvas::SANS, 8, '', Canvas::BLACK);
                $pdf->paragraph($x + 28.0, $y + 1, 86, $label, 9.8, 3);
            }
            $y += 34;
        }
    }
}
