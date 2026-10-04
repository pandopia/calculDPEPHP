<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render\Page;

use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\Render\Canvas;
use CalculDpePHP\Pdf\Render\Format;
use CalculDpePHP\Pdf\Template\Box;

/**
 * Page 3 : consommations et frais annuels par usage, recommandations d'usage.
 */
final class Page3Consommations implements PageRenderer
{
    /** Ancre (libellé d'usage du modèle) de chaque ligne du tableau. */
    private const LIGNES = [
        'chauffage' => '/^chauffage$/',
        'ecs' => '/^eau chaude$/',
        'refroidissement' => '/^refroidissement$/',
        'eclairage' => '/^éclairage$/',
        'auxiliaires' => '/^auxiliaires$/',
    ];

    /** enum_type_energie_id ⇒ [libellé court, pictogramme]. */
    private const ENERGIES = [
        1 => ['électrique', 'picto_p3_energie_electricite'],
        2 => ['gaz naturel', 'picto_p3_energie_gaz_naturel'],
        3 => ['fioul', 'picto_p3_energie_fioul'],
        4 => ['bois', 'picto_p3_energie_bois'],
        5 => ['bois', 'picto_p3_energie_bois'],
        6 => ['bois', 'picto_p3_energie_bois'],
        7 => ['bois', 'picto_p3_energie_bois'],
        8 => ['réseau de chaleur', 'picto_p3_reseau_de_chaleur_v2'],
        9 => ['propane', 'picto_p3_energie_butane'],
        10 => ['butane', 'picto_p3_energie_butane'],
        11 => ['charbon', 'picto_p3_energie_charbon'],
        12 => ['électrique', 'picto_p3_energie_electricite'],
        13 => ['GPL', 'picto_p3_energie_butane'],
        14 => ['combustible fossile', 'picto_p3_energie_fioul'],
        15 => ['réseau de froid', 'picto_p3_reseau_de_chaleur_v2'],
    ];

    private const BAR_LEFT = 412.3;
    /** Largeur de barre pour 100 % (98,2 pt pour 67 % dans le modèle). */
    private const BAR_FULL = 146.6;

    public function render(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $pdf->addTemplatePage(3);
        $this->tableau($pdf, $data);
        $this->notes($pdf, $data);
        $this->recommandations($pdf, $data);
    }

    private function tableau(Canvas $pdf, DpeData $data): void
    {
        $t = $pdf->template();
        $couts = [];
        foreach (array_keys(self::LIGNES) as $usage) {
            $couts[$usage] = $data->usage($usage)['cout'];
        }
        $total = array_sum($couts);
        $parts = Page2Enveloppe::pourcentagesArrondis(array_map(
            static fn (float $c): float => $total > 0 ? $c / $total * 100 : 0.0,
            $couts,
        ));

        foreach (self::LIGNES as $usage => $pattern) {
            $label = $t->anchor(3, $pattern);
            $center = $usage === 'ecs' ? $label->y0 + 11.8 : ($label->y0 + $label->y1) / 2;
            $top = $center - 14.0;
            $rowColor = $pdf->backgroundAt(404.0, $center);
            $pdf->fillRect(137.0, $top, 268.0, 28.0, $rowColor);
            $pdf->fillRect(self::BAR_LEFT - 1.0, $top - 0.3, 148.0, 28.6, Canvas::WHITE);

            $u = $data->usage($usage);
            $textTop = $center - 6.2;
            $energies = $data->energiesUsage($usage);
            if ($energies !== [] && $u['ep'] > 0) {
                $this->energies($pdf, $energies, $center);
            }

            $this->conso($pdf, 245.7, $textTop, Format::int($u['ep']), '(' . Format::int($u['ef']) . ' é.f.)');

            [$min, $max] = DpeData::fourchette($u['cout']);
            if ($max === 0) {
                $pdf->runs(305.0, $textTop, [[Canvas::COND_SEMIBOLD, '0€', 9.5, '', Canvas::BLACK]]);
            } else {
                $this->fourchette($pdf, 305.0, $textTop, $min, $max);
            }

            $pct = $parts[$usage];
            $w = max(1.2, self::BAR_FULL * $pct / 100);
            $pdf->fillRect(self::BAR_LEFT, $top + 0.8, $w, 26.6, Canvas::PINK);
            $pdf->font(Canvas::SANS, 20, '', Canvas::BLACK);
            $pdf->textAt(self::BAR_LEFT + $w + 4.2, $center - 13.8, $pct . '%');
        }

        // Ligne de total.
        $totalLabel = $t->anchor(3, '/^énergie totale pour les/');
        $pdf->fillRect(195.0, $totalLabel->y0 - 2.5, 210.0, 27.5, $pdf->backgroundAt(300.0, $totalLabel->y0 - 1.5));
        $pdf->runs(200.9, $totalLabel->y0 - 1.6, [
            [Canvas::COND_SEMIBOLD, Format::int($data->epConsoTotale()), 11.5, '', Canvas::BLACK],
            [Canvas::COND, "\u{00A0}kWh", 11.5, '', Canvas::BLACK],
        ]);
        $pdf->font(Canvas::COND, 9, '', Canvas::GREY);
        $pdf->textAt(200.2, $totalLabel->y0 + 11.8, '(' . Format::int($data->efConsoTotale()) . ' kWh é.f.)');
        [$min, $max] = $data->fourchetteTotale();
        $this->fourchette($pdf, 305.0, $totalLabel->y0 - 1.6, $min, $max, 11.5);
        $pdf->font(Canvas::COND, 9.5, '', Canvas::BLACK);
        $pdf->textAt(305.0, $totalLabel->y0 + 11.8, 'par an');

        // Note propre à l'exemple du modèle appartement (chaudière collective).
        $note = $t->find(3, '/^Absence d’information sur la chaudière collective/');
        if ($note !== null) {
            $pdf->fillRect(34.5, $note->y0 - 5.0, 268.0, 23.0, $pdf->backgroundAt(36.0, $note->y0 - 6.0));
        }
    }

    /**
     * @param list<int> $energies
     */
    private function energies(Canvas $pdf, array $energies, float $center): void
    {
        $labels = [];
        $pictos = [];
        foreach ($energies as $id) {
            [$label, $picto] = self::ENERGIES[$id] ?? ['autre', 'picto_p3_energie_fioul'];
            if (!in_array($label, $labels, true)) {
                $labels[] = $label;
                $pictos[] = $picto;
            }
        }
        $labels = array_slice($labels, 0, 2);
        $lineHeight = 10.0;
        $top = $center - count($labels) * $lineHeight / 2;
        foreach ($labels as $i => $label) {
            $pdf->picto($pictos[$i], 140.2, $top + $i * $lineHeight + 1.2, 8.0, 8.0);
            $pdf->font(Canvas::COND, count($labels) > 1 ? 8.5 : 9.5, '', Canvas::BLACK);
            $pdf->fitWidth($label, 52.0);
            $pdf->textAt(152.5, $top + $i * $lineHeight - 0.4, $label);
        }
    }

    private function conso(Canvas $pdf, float $center, float $top, string $ep, string $ef): void
    {
        $pdf->font(Canvas::COND_SEMIBOLD, 9.5);
        $w1 = $pdf->GetStringWidth($ep . ' ');
        $pdf->font(Canvas::COND, 9.5);
        $w2 = $pdf->GetStringWidth($ef);
        $left = $center - ($w1 + $w2) / 2;
        $pdf->runs($left, $top, [
            [Canvas::COND_SEMIBOLD, $ep . ' ', 9.5, '', Canvas::BLACK],
            [Canvas::COND, $ef, 9.5, '', Canvas::GREY],
        ]);
    }

    private function fourchette(Canvas $pdf, float $x, float $top, int $min, int $max, float $size = 9.5): void
    {
        $pdf->runs($x, $top, [
            [Canvas::COND, 'entre ', $size, '', Canvas::BLACK],
            [Canvas::COND_SEMIBOLD, Format::int($min) . '€', $size, '', Canvas::BLACK],
            [Canvas::COND, ' et ', $size, '', Canvas::BLACK],
            [Canvas::COND_SEMIBOLD, Format::int($max) . '€', $size, '', Canvas::BLACK],
        ]);
    }

    /** Volume d'eau chaude du paragraphe « Conventionnellement… ». */
    private function notes(Canvas $pdf, DpeData $data): void
    {
        $line = $pdf->template()->find(3, '/consommation d’eau chaude de \d+ℓ par jour/');
        if ($line === null) {
            return;
        }
        $pdf->mask($line->grow(0.5, 0, 6, 0), $line->x0 - 1.5, $line->y0 + 2);
        $text = sprintf(
            'climatisation), et une consommation d’eau chaude de %dℓ par jour.',
            (int) round($data->volumeEcsJournalier()),
        );
        $pdf->font(Canvas::SANS, 9, '', Canvas::BLACK);
        $pdf->fitWidth($text, $line->width() + 1.0);
        $pdf->textAt($line->x0, $line->y0, $text);
    }

    private function recommandations(Canvas $pdf, DpeData $data): void
    {
        $t = $pdf->template();
        $orange = Canvas::ORANGE;

        // Chauffage : 19 °C plutôt que 21 °C. Le modèle immeuble n'affiche pas d'euros.
        $euros = !$data->variant()->isBatiment();
        $gain = $data->gainComportement('chauffage');
        $line = $t->anchor(3, '/^c’est -\d+% sur votre facture/');
        $this->gainLine($pdf, $line, $gain['pourcentage'], $euros ? $gain['euros'] : null, $orange);

        // Eau chaude : volume conventionnel, économie par rapport au scénario dépensier.
        $v40 = (int) round($data->volumeEcsJournalier());
        $titre = $t->anchor(3, '/ℓ\/jour/');
        $pdf->mask(new Box($titre->x0 - 0.5, $titre->y0 + 0.5, 375.0, $titre->y1), $titre->x0 - 2, $titre->y0 + 3);
        $texte = str_starts_with($titre->text, '→')
            ? "→ {$v40}ℓ/jour d’eau chaude à 40°C"
            : "Consommation recommandée → {$v40}ℓ/jour";
        $pdf->font(Canvas::SANS_SEMIBOLD, 13, '', Canvas::BLACK);
        $pdf->fitWidth($texte, 375.0 - $titre->x0);
        $pdf->textAt($titre->x0, $titre->y0, $texte);

        $personnes = $t->find(3, '/^\(\d+-\d+ personnes\)/');
        if ($personnes !== null) {
            $bas = max(1, (int) floor($data->nadeqLogement()));
            $pdf->mask(new Box($personnes->x0 - 0.5, $personnes->y0 + 0.5, $personnes->x0 + 58, $personnes->y1 - 0.5), $personnes->x0 - 2, $personnes->y0 + 3);
            $pdf->font(Canvas::SANS, 9, '', Canvas::BLACK);
            $pdf->textAt($personnes->x0, $personnes->y0, sprintf('(%d-%d personnes).', $bas, $bas + 1));
        }

        $moins = $t->anchor(3, '/^\d+ℓ consommés en moins par jour/');
        $pdf->mask(new Box($moins->x0 - 0.5, $moins->y0 + 0.5, $moins->x1 + 20, $moins->y1 - 0.5), $moins->x0 - 2, $moins->y0 + 3);
        $ecart = (int) round($data->volumeEcsJournalierDepensier() - $data->volumeEcsJournalier());
        $pdf->font(Canvas::SANS, 13, '', Canvas::BLACK);
        $pdf->textAt($moins->x0, $moins->y0, "{$ecart}ℓ consommés en moins par jour,");

        $gain = $data->gainComportement('ecs');
        $line = $t->anchor(3, '/^c’est -\d+% sur votre facture/', 1);
        $this->gainLine($pdf, $line, $gain['pourcentage'], $euros ? $gain['euros'] : null, $orange);
    }

    /**
     * @param array{0:int,1:int,2:int} $orange
     */
    private function gainLine(Canvas $pdf, Box $line, int $pourcentage, ?int $euros, array $orange): void
    {
        $pdf->mask(new Box($line->x0 - 0.5, $line->y0 + 0.5, 378.0, $line->y1 - 0.5), $line->x0 - 2, $line->y0 + 3);
        if ($euros === null) {
            $pdf->runs($line->x0, $line->y0, [[Canvas::SANS, "c’est -$pourcentage% sur votre facture", 13, 'B', $orange]]);

            return;
        }
        $pdf->runs($line->x0, $line->y0, [
            [Canvas::SANS, "c’est -$pourcentage% sur votre facture ", 13, '', Canvas::BLACK],
            [Canvas::SANS, 'soit -' . Format::int($euros) . '€ par an', 13, 'B', $orange],
        ]);
    }
}
