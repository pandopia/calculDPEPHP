<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render\Page;

use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Pdf\Data\EnumLabels;
use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\Render\Canvas;
use CalculDpePHP\Pdf\Render\Format;

/**
 * Annexes : références du DPE, justificatifs, explications personnalisées,
 * puis la fiche technique complète (fiche_technique_collection du XML) sur
 * autant de pages que nécessaire, au fond des pages 7 et 8 du modèle.
 */
final class Page7Annexes implements PageRenderer
{
    private const NAVY = [61, 66, 140];
    private const LIGHT_BLUE = [43, 170, 226];
    private const BOTTOM = 802.0;
    private const ROW = 17.0;

    /** Colonnes du tableau de la fiche technique. */
    private const COL_ELEMENT = 88.2;
    private const COL_DONNEE = 168.0;
    private const COL_ORIGINE = 300.0;
    private const COL_VALEUR = 392.0;
    private const RIGHT = 557.0;

    /** enum_origine_donnee_id ⇒ [libellé, pictogramme, couleur]. */
    private const ORIGINES = [
        1 => ['valeur par défaut', 'picto_p7_manquant', [229, 35, 34]],
        2 => ['mesurée ou observée', 'picto_p7_mesure_ou_observe', self::NAVY],
        3 => ['document fourni', 'picto_p7_fourni', self::LIGHT_BLUE],
        4 => ['données en ligne', 'picto_p7_api', self::LIGHT_BLUE],
        5 => ['estimée', 'picto_p7_estime', self::NAVY],
        6 => ['valeur par défaut pénalisante', 'picto_p7_manquant', [229, 35, 34]],
    ];

    /** enum_categorie_fiche_technique_id ⇒ [groupe, nom d'élément]. */
    private const CATEGORIES = [
        11 => ['généralités', null],
        1 => ['enveloppe', 'Mur'],
        2 => ['enveloppe', 'Plancher bas'],
        3 => ['enveloppe', 'Toiture / plafond'],
        4 => ['enveloppe', 'Fenêtre'],
        5 => ['enveloppe', 'Porte'],
        6 => ['enveloppe', 'Pont thermique'],
        7 => ['équipements', 'Chauffage'],
        8 => ['équipements', 'Eau chaude sanitaire'],
        9 => ['équipements', 'Climatisation'],
        10 => ['équipements', 'Ventilation'],
        12 => ['logements visités', 'Logement'],
    ];

    private float $y = 0.0;

    public function render(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $pdf->addTemplatePage(7);
        $this->references($pdf, $data, $extra);
        $this->explications($pdf, $extra);

        $header = $pdf->template()->anchor(7, '/^donnée d’entrée$/');
        $top = $header->y0 - 13.0;
        $pdf->fillRect(30.0, $top - 2.0, 535.0, 812.0 - $top, $pdf->backgroundAt(20.0, 300.0));
        $this->y = $top;
        $this->fiche($pdf, $data);
    }

    private function references(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $t = $pdf->template();
        $d = $extra->diagnostiqueur->completer($data->diagnostiqueur());

        $certif = $t->anchor(7, '/^Le présent rapport est établi par une personne/');
        $pdf->fillRect($certif->x0 - 1, $certif->y0 + 0.5, 518.0, 22.5, Canvas::WHITE);
        $phrase = 'Le présent rapport est établi par une personne dont les compétences sont certifiées par '
            . ($d['organisme'] ?? '') . ($extra->diagnostiqueur->adresseOrganismeCertification !== null ? ', ' . $extra->diagnostiqueur->adresseOrganismeCertification : '') . '.';
        $pdf->font(Canvas::SANS, 9, 'B', Canvas::BLACK);
        $pdf->paragraph($certif->x0, $certif->y0, 512.0, $phrase, 10.8, 2);

        // Bloc de références (gauche) et justificatifs (droite).
        $first = $t->anchor(7, '/^référence du logiciel validé/');
        $last = $t->anchor(7, '/^référence de la parcelle cadastrale/');
        $surface = $t->anchor(7, '/^La surface de référence d/');
        $proprio = $t->find(7, '/^Propriétaire des installations communes/');
        $bottom = ($proprio ?? $surface)->y0 - 4.0;
        $pdf->fillRect(40.0, $first->y0 - 0.5, 519.0, $bottom - $first->y0, Canvas::WHITE);

        $lignes = [
            ['référence du logiciel validé : ', $d['logiciel'] ?? ''],
            ['référence du DPE : ', $extra->numeroDpe ?? $data->numeroDpe() ?? 'non attribué (DPE non enregistré auprès de l’ADEME)'],
            ['méthode de calcul : ', '3CL-DPE 2021'],
            ['date de visite du bien : ', $data->dateVisite() === null ? '' : Format::date($data->dateVisite())],
            ['invariant fiscal du logement : ', $data->invariantFiscal() ?? ''],
        ];
        if ($data->variant()->isAppartement()) {
            $lignes[] = ['Numéro d’immatriculation de la copropriété : ', $data->immatriculationCopropriete() ?? ''];
        }
        $lignes[] = ['référence de la parcelle cadastrale : ', $data->parcelleCadastrale() ?? ''];
        $leading = 8.4;
        foreach ($lignes as $i => [$label, $value]) {
            $pdf->runs($first->x0, $first->y0 + $i * $leading, [
                [Canvas::SANS, $label, 7.3, '', Canvas::BLACK],
                [Canvas::SANS, $value, 7.3, 'B', Canvas::BLACK],
            ]);
        }

        $justifs = $data->justificatifs();
        $pdf->font(Canvas::SANS, 7.3, '', Canvas::BLACK);
        $pdf->textAt(304.6, $first->y0 + 0.2, 'Justificatifs fournis pour établir le DPE :');
        $y = $first->y0 + $leading;
        $max = (int) floor(($bottom - $y) / $leading);
        $rows = [];
        foreach ($justifs as $j) {
            array_push($rows, ...$pdf->wrap('→ ' . $j, 250.0));
        }
        foreach (array_slice($rows, 0, max(0, $max)) as $row) {
            $pdf->textAt(304.6, $y, $row);
            $y += $leading;
        }

        if ($proprio !== null) {
            $pdf->fillRect(40.0, $proprio->y0 - 0.5, 519.0, $surface->y0 - $proprio->y0 - 3.0, Canvas::WHITE);
            $nom = $data->nomProprietaireInstallationCommune();
            $pdf->font(Canvas::SANS, 7.3, '', Canvas::BLACK);
            $pdf->textAt($proprio->x0, $proprio->y0, 'Propriétaire des installations communes :');
            $pdf->font(Canvas::SANS, 7.3, 'B', Canvas::BLACK);
            $pdf->textAt($proprio->x0, $proprio->y0 + 8.2, $nom ?? 'Non renseigné');
            $adresse = $data->adresseProprietaireInstallationCommune();
            if ($adresse !== null) {
                $pdf->textAt($proprio->x0, $proprio->y0 + 16.6, $adresse);
            }
        }
    }

    private function explications(Canvas $pdf, DonneesRapportPdf $extra): void
    {
        $t = $pdf->template();
        $titre = $t->anchor(7, '/^estimées et les consommations réelles/');
        $top = $titre->y1 + 4.0;
        $bottom = $t->whiteUntil(7, 556.0, $top) - 3.0;
        $pdf->fillRect(80.0, $top, 478.0, $bottom - $top, Canvas::WHITE);
        if ($extra->explicationsPersonnalisees === null) {
            return;
        }
        $pdf->font(Canvas::SANS, 9, 'B', Canvas::BLACK);
        $lines = $pdf->wrap($extra->explicationsPersonnalisees, 460.0);
        $max = (int) floor(($bottom - $top - 8) / 10.8);
        foreach (array_slice($lines, 0, $max) as $i => $line) {
            $pdf->textAt($titre->x0, $top + 6 + $i * 10.8, $line);
        }
    }

    private function fiche(Canvas $pdf, DpeData $data): void
    {
        $groupes = [];
        $compteurs = [];
        $totaux = [];
        foreach ($data->fichesTechniques() as $fiche) {
            $totaux[$fiche['categorie']] = ($totaux[$fiche['categorie']] ?? 0) + 1;
        }
        foreach ($data->fichesTechniques() as $fiche) {
            [$groupe, $element] = self::CATEGORIES[$fiche['categorie']]
                ?? [EnumLabels::label('enum_categorie_fiche_technique_id', $fiche['categorie']) ?? 'autres', null];
            $n = $compteurs[$fiche['categorie']] = ($compteurs[$fiche['categorie']] ?? 0) + 1;
            if ($element !== null && $totaux[$fiche['categorie']] > 1) {
                $element .= " $n";
            }
            foreach ($fiche['lignes'] as $i => $ligne) {
                [$donnee, $valeur] = self::split($ligne['description'], $ligne['valeur']);
                $groupes[$groupe][] = [
                    'element' => $i === 0 ? $element : null,
                    'donnee' => $donnee,
                    'origine' => $ligne['origine'],
                    'valeur' => $valeur,
                ];
            }
        }

        $ordre = ['généralités', 'enveloppe', 'équipements', 'logements visités'];
        uksort($groupes, static fn (string $a, string $b): int => (array_search($a, $ordre, true) ?: 99) <=> (array_search($b, $ordre, true) ?: 99));

        foreach ($groupes as $groupe => $rows) {
            $this->groupe($pdf, $groupe, $rows);
            $this->y += 12.0;
        }
    }

    /**
     * « Surface du mur: 19,18 m² » ⇒ [« Surface du mur », « 19,18 m² »].
     *
     * @return array{0: string, 1: string}
     */
    public static function split(string $description, string $valeur): array
    {
        $parts = explode(':', $description, 2);
        $donnee = trim($parts[0]);
        if ($valeur === '' && isset($parts[1])) {
            $valeur = trim($parts[1]);
        }

        return [$donnee, $valeur];
    }

    /**
     * @param list<array{element: ?string, donnee: string, origine: ?int, valeur: string}> $rows
     */
    private function groupe(Canvas $pdf, string $titre, array $rows): void
    {
        $this->ensureSpace($pdf, 30.0 + 2 * self::ROW);
        $blockTop = $this->y;
        $this->header($pdf);

        foreach ($rows as $i => $row) {
            if ($this->y + self::ROW > self::BOTTOM) {
                $this->closePanel($pdf, $titre, $blockTop);
                $this->continuation($pdf);
                $blockTop = $this->y;
                $this->header($pdf);
            }
            $this->row($pdf, $row, $i === count($rows) - 1);
        }
        $this->closePanel($pdf, $titre, $blockTop);
    }

    private function header(Canvas $pdf): void
    {
        $pdf->fillRect(34.0, $this->y, 527.3, 26.0, Canvas::WHITE);
        $pdf->font(Canvas::COND, 7.5, '', self::NAVY);
        $top = $this->y + 9.5;
        $pdf->textAt(self::COL_ELEMENT, $top, 'élément');
        $pdf->textAt(self::COL_DONNEE, $top, 'donnée d’entrée');
        $pdf->textAt(self::COL_ORIGINE, $top, 'origine de la donnée');
        $pdf->textAt(self::COL_VALEUR, $top, 'valeur renseignée');
        $pdf->SetLineStyle(['width' => 0.9, 'color' => self::NAVY, 'dash' => 0]);
        $pdf->Line(85.0, $this->y + 21.0, self::RIGHT, $this->y + 21.0);
        $this->y += 22.0;
    }

    /**
     * @param array{element: ?string, donnee: string, origine: ?int, valeur: string} $row
     */
    private function row(Canvas $pdf, array $row, bool $last): void
    {
        $pdf->fillRect(34.0, $this->y, 527.3, self::ROW, Canvas::WHITE);
        if ($row['element'] !== null && $this->y > 0) {
            $pdf->SetLineStyle(['width' => 0.9, 'color' => self::NAVY]);
            $pdf->Line(85.0, $this->y, self::RIGHT, $this->y);
        }
        $top = $this->y + 3.6;
        if ($row['element'] !== null) {
            $pdf->font(Canvas::COND_SEMIBOLD, 7.5, '', Canvas::BLACK);
            $pdf->fitWidth($row['element'], self::COL_DONNEE - self::COL_ELEMENT - 4);
            $pdf->textAt(self::COL_ELEMENT, $top, $row['element']);
        }
        $pdf->font(Canvas::COND_SEMIBOLD, 7.5, '', Canvas::BLACK);
        $pdf->fitWidth($row['donnee'], self::COL_ORIGINE - self::COL_DONNEE - 4, 5.5);
        $pdf->textAt(self::COL_DONNEE, $top, $row['donnee']);

        if ($row['origine'] !== null && isset(self::ORIGINES[$row['origine']])) {
            [$label, $picto, $color] = self::ORIGINES[$row['origine']];
            $pdf->picto($picto, self::COL_ORIGINE, $top + 1.0, 8.0, 8.0);
            $pdf->font(Canvas::COND, 7.5, '', $color);
            $pdf->fitWidth($label, self::COL_VALEUR - self::COL_ORIGINE - 14, 5.5);
            $pdf->textAt(self::COL_ORIGINE + 10.5, $top, $label);
        }

        $pdf->font(Canvas::COND, 7.5, '', Canvas::BLACK);
        $pdf->fitWidth($row['valeur'], self::RIGHT - self::COL_VALEUR - 2, 5.5);
        $pdf->textAt(self::COL_VALEUR, $top, $row['valeur']);

        $this->y += self::ROW;
        $pdf->SetLineStyle(['width' => $last ? 0.9 : 0.5, 'color' => self::NAVY]);
        $pdf->Line(85.0, $this->y, self::RIGHT, $this->y);
    }

    /** Bas du panneau et libellé vertical du groupe. */
    private function closePanel(Canvas $pdf, string $titre, float $blockTop): void
    {
        $pdf->fillRect(34.0, $this->y, 527.3, 8.0, Canvas::WHITE);
        $this->y += 8.0;
        $center = ($blockTop + $this->y) / 2;
        $pdf->font(Canvas::SANS, 13, 'B', self::NAVY);
        $width = $pdf->GetStringWidth($titre);
        if ($width > $this->y - $blockTop - 6) {
            $pdf->SetFontSize(max(7.0, 13 * ($this->y - $blockTop - 6) / $width));
            $width = $pdf->GetStringWidth($titre);
        }
        $pdf->StartTransform();
        $pdf->Rotate(90, 60.5, $center);
        $pdf->textAt(60.5 - $width / 2, $center - 8.5, $titre);
        $pdf->StopTransform();
    }

    private function ensureSpace(Canvas $pdf, float $height): void
    {
        if ($this->y + $height > self::BOTTOM) {
            $this->continuation($pdf);
        }
    }

    /** Page de suite au fond de la page 8 du modèle, corps vidé. */
    private function continuation(Canvas $pdf): void
    {
        $pdf->addTemplatePage(8);
        $titre = $pdf->template()->anchor(8, '/^Fiche technique du (logement|bâtiment) \(suite\)/');
        $top = $titre->y1 + 8.0;
        $pdf->fillRect(30.0, $top, 535.0, 812.0 - $top, $pdf->backgroundAt(20.0, 300.0));
        $this->y = $top + 6.0;
    }
}
