<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render\Page;

use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\Render\Canvas;
use CalculDpePHP\Pdf\Render\RowTable;

/**
 * Page 4 : vue d'ensemble de l'enveloppe et des équipements, gestes
 * d'entretien (descriptif_simplifie et descriptif_geste_entretien du XML).
 */
final class Page4VueEnsemble implements PageRenderer
{
    private const STRIPE_BLEU = [244, 243, 250];
    private const STRIPE_ORANGE = [255, 246, 237];

    /** Pastilles « isolation » : qualite_isol_* ⇒ [libellé, couleur]. */
    private const QUALITE = [
        1 => ['très bonne', [0, 160, 109]],
        2 => ['bonne', [120, 189, 118]],
        3 => ['moyenne', [254, 203, 4]],
        4 => ['insuffisante', [229, 35, 34]],
    ];

    /** enum_picto_geste_entretien_id ⇒ pictogramme. */
    public const PICTOS_ENTRETIEN = [
        1 => 'picto_p3_conso_ventilation',
        2 => 'picto_p4_chaudiere',
        3 => 'picto_p4_radiateur',
        4 => 'picto_p4_eclairage',
        5 => 'picto_p4_fenetres',
        6 => 'picto_p4_circuit_chauffage',
        7 => 'picto_p4_pompe_a_chaleur',
        8 => 'picto_p4_insert_-_poele_bois',
        9 => 'picto_p4_chauffe_eau',
        10 => 'picto_p4_chauffe_eau_thermodynamique',
        11 => 'picto_p4_panneaux_solaires',
        12 => 'picto_p4_generique',
        13 => 'picto_p4_isolation',
        14 => 'picto_p4_climatisation',
        15 => 'picto_p3_conso_chauffage',
        16 => 'picto_p3_conso_eau_chaude',
        17 => 'picto_p4_logement_huisserie',
    ];

    public function render(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $pdf->addTemplatePage(4);
        $t = $pdf->template();

        // Enveloppe.
        $header = $t->anchor(4, '/^description$/');
        $rows = [];
        foreach ([
            [1, 'murs', 'picto_p4_logement_murs', 'mur'],
            [2, 'plancher bas', 'picto_p4_logement_sol', 'plancher_bas'],
            [3, 'toiture/plafond', 'picto_p4_logement_toit', 'plancher_haut'],
            [4, 'portes et fenêtres', 'picto_p4_logement_huisserie', 'menuiserie'],
        ] as [$categorie, $label, $picto, $poste]) {
            $descriptifs = $data->descriptifs($categorie);
            $qualite = $descriptifs === [] ? null : $data->qualiteIsolation($poste);
            $rows[] = [
                'picto' => $picto,
                'label' => $label,
                'paragraphs' => self::paragraphs($descriptifs),
                'badge' => $qualite === null ? null : self::QUALITE[$qualite] ?? null,
            ];
        }
        $table = new RowTable($pdf, 38.3, 557.3, 64.9, 149.7, 472.0, 482.6, self::STRIPE_BLEU);
        $this->section($pdf, $table, $rows, $header->y1 + 1.4);

        // Équipements.
        $header = $t->anchor(4, '/^description$/', 1);
        $rows = [];
        foreach ([
            [5, 'chauffage', 'picto_p3_conso_chauffage'],
            [6, 'eau chaude sanitaire', 'picto_p3_conso_eau_chaude'],
            [7, 'climatisation', 'picto_p3_conso_climatisation'],
            [8, 'ventilation', 'picto_p3_conso_ventilation'],
            [9, 'pilotage', 'picto_p4_pilotage'],
        ] as [$categorie, $label, $picto]) {
            $rows[] = [
                'picto' => $picto,
                'label' => $label,
                'paragraphs' => self::paragraphs($data->descriptifs($categorie), $categorie === 7 ? 'Sans objet' : 'Néant'),
            ];
        }
        $table = new RowTable($pdf, 38.3, 557.9, 67.8, 162.2, 552.0, 552.0, self::STRIPE_BLEU);
        $this->section($pdf, $table, $rows, $header->y1 + 1.5);

        // Gestes d'entretien.
        $header = $t->anchor(4, '/^type d’entretien$/');
        $rows = [];
        foreach ($data->gestesEntretien() as $geste) {
            $rows[] = [
                'picto' => self::PICTOS_ENTRETIEN[$geste['picto']] ?? 'picto_p4_generique',
                'label' => mb_strtolower(mb_substr($geste['categorie'], 0, 1)) . mb_substr($geste['categorie'], 1),
                'paragraphs' => self::paragraphs($geste['descriptions']),
            ];
        }
        $table = new RowTable($pdf, 38.3, 557.0, 67.8, 162.2, 552.0, 552.0, self::STRIPE_ORANGE);
        $this->section($pdf, $table, $rows, $header->y1 + 1.5);
    }

    /**
     * @param list<array{picto?: ?string, label: string, paragraphs: list<array{text: string, style?: string, value?: ?string, warning?: bool}>, badge?: ?array{0: string, 1: array{0:int,1:int,2:int}}}> $rows
     */
    private function section(Canvas $pdf, RowTable $table, array $rows, float $top): void
    {
        $bottom = $pdf->template()->whiteUntil(4, 36.0, $top) - 2.5;
        $pdf->fillRect(36.5, $top, 523.0, $bottom - $top, Canvas::WHITE);
        $table->fit($rows, $bottom - $top, 6.5);
        foreach ($rows as $i => $row) {
            if ($top + $table->height($row) > $bottom + 0.5) {
                break;
            }
            $top = $table->draw($row, $top, $i % 2 === 0);
        }
    }

    /**
     * @param list<string> $descriptions
     * @return list<array{text: string}>
     */
    private static function paragraphs(array $descriptions, string $empty = 'Néant'): array
    {
        if ($descriptions === []) {
            return [['text' => $empty]];
        }

        return array_map(static fn (string $d): array => ['text' => $d], $descriptions);
    }
}
