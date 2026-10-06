<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render\Page;

use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\Render\Canvas;
use CalculDpePHP\Pdf\Render\EnergyLabel;
use CalculDpePHP\Pdf\Render\Format;
use CalculDpePHP\Pdf\Render\QrCode;
use CalculDpePHP\Pdf\Template\Box;
use CalculDpePHP\Pdf\Template\TemplateVariant;

/**
 * Page 1 : identification, étiquettes énergie et climat, estimation des coûts,
 * diagnostiqueur.
 */
final class Page1Synthese implements PageRenderer
{
    public function render(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $pdf->addTemplatePage(1);
        $this->header($pdf, $data, $extra);
        $this->bien($pdf, $data, $extra);
        $this->etiquettes($pdf, $data);
        $this->couts($pdf, $data);
        $this->diagnostiqueur($pdf, $data, $extra);

        if (($extra->numeroDpe ?? $data->numeroDpe()) === null) {
            self::filigraneNonEnregistre($pdf);
        }
    }

    public const FILIGRANE = 'DOCUMENT NON OFFICIEL';
    public const FILIGRANE_DETAIL = 'DPE non enregistré auprès de l’ADEME – sans valeur réglementaire';

    /**
     * Sans numéro ADEME, le DPE n'a pas été déposé à l'observatoire (calcul
     * préalable, simulation) : le rapport ne doit pas pouvoir passer pour un
     * DPE opposable. Filigrane en diagonale de la page, au-dessus du contenu.
     */
    public static function filigraneNonEnregistre(Canvas $pdf): void
    {
        $w = 595.276;
        $h = 841.89;
        $angle = rad2deg(atan2($h, $w));
        $red = [215, 34, 31];

        $pdf->StartTransform();
        $pdf->setAlpha(0.22);
        $pdf->Rotate($angle, $w / 2, $h / 2);
        // TCPDF lit une abscisse négative depuis le bord droit : le texte, plus
        // large que la demi-page, est écrit dans un repère décalé.
        $shift = 600.0;
        $pdf->Translate(-$shift, 0);
        $pdf->font(Canvas::SANS, 60, 'B', $red);
        $pdf->fitWidth(self::FILIGRANE, 900.0);
        $pdf->textAt($w / 2 + $shift, $h / 2 - 50.0, self::FILIGRANE, 'C');
        $pdf->font(Canvas::SANS, 19, 'B', $red);
        $pdf->fitWidth(self::FILIGRANE_DETAIL, 900.0);
        $pdf->textAt($w / 2 + $shift, $h / 2 + 32.0, self::FILIGRANE_DETAIL, 'C');
        $pdf->setAlpha(1.0);
        $pdf->StopTransform();
    }

    private function header(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $t = $pdf->template();
        $numero = $extra->numeroDpe ?? $data->numeroDpe() ?? '';
        $libelleNumero = 'n° : ' . ($numero !== '' ? $numero : 'non attribué');
        $etabli = 'établi le : ' . Format::date($data->dateEtablissement());
        $valable = 'valable jusqu’au : ' . Format::date($data->dateFinValidite());

        if ($t->hasQrCode()) {
            // Cartouche vert « n° » puis zone blanche des dates (image du modèle).
            $pdf->fillRect(368, 44.2, 113, 10.6, $pdf->backgroundAt(350, 50));
            $pdf->fillRect(340, 55.0, 141, 24.0, Canvas::WHITE);
            $pdf->font(Canvas::SANS, 7.5, 'B', Canvas::WHITE);
            $pdf->textAt(479.5, 44.9, $libelleNumero, 'R');
            $pdf->font(Canvas::SANS, 9, '', Canvas::GREEN);
            $pdf->textAt(479.6, 55.0, $etabli, 'R');
            $pdf->font(Canvas::SANS, 9.5, 'B', Canvas::GREEN);
            $pdf->textAt(479.6, 65.6, $valable, 'R');

            // QR code ADEME (l'image du modèle porte un filigrane « QR code Ademe »).
            $pdf->fillRect(488.6, 44.3, 63.3, 63.0, Canvas::WHITE);
            if ($numero !== '') {
                QrCode::draw($pdf, QrCode::urlFor($numero), 491.0, 46.7, 58.2);
            } else {
                $pdf->font(Canvas::SANS, 6.5, 'B', Canvas::GREY);
                foreach (['QR code', 'disponible après', 'enregistrement', 'à l’ADEME'] as $i => $ligne) {
                    $pdf->textAt(520.2, 57.0 + $i * 9.0, $ligne, 'C');
                }
            }
        } else {
            // Éditions 2023 et 2024 : numéro et dates en vert, alignés à droite, sans QR code.
            $n = $t->anchor(1, '/^n° :/');
            $e = $t->anchor(1, '/^établi le/');
            $v = $t->anchor(1, '/^valable jusqu/');
            $pdf->fillRect(min($n->x0, $e->x0, $v->x0) - 40.0, $n->y0 + 0.5, max($n->x1, $e->x1, $v->x1) + 41.0 - min($n->x0, $e->x0, $v->x0), $v->y1 - $n->y0 - 0.5, Canvas::WHITE);
            $pdf->font(Canvas::SANS, 9, '', Canvas::GREEN);
            $pdf->textAt($n->x1, $n->y0, $libelleNumero, 'R');
            $pdf->textAt($e->x1, $e->y0, $etabli, 'R');
            $pdf->font(Canvas::SANS, 9.5, 'B', Canvas::GREEN);
            $pdf->textAt($v->x1, $v->y0, $valable, 'R');
        }

        // Lien du guide pédagogique : le texte du modèle jusqu'au lien, puis le lien.
        $url = $t->find(1, '/<url_gouv_guide_p/');
        if ($url !== null) {
            $right = $t->hasQrCode() ? 478.0 : 556.0;
            $debut = trim(explode('Pour en savoir plus', $url->text, 2)[0]) . ' ';
            $lien = 'Pour en savoir plus : ' . $extra->urlGuidePedagogique;
            $pdf->mask(new Box($url->x0, $url->y0 + 0.5, $right, $url->y1));
            $pdf->font(Canvas::SANS, 8, '', Canvas::GREEN);
            $pdf->fitWidth($debut . $lien, $right - 2.0 - $url->x0, 6.0);
            $size = $pdf->fontSize();
            $pdf->runs($url->x0, $url->y0, [
                [Canvas::SANS, $debut, $size, '', Canvas::GREEN],
                [Canvas::SANS, $lien, $size, 'I', Canvas::GREEN],
            ]);
        }
    }

    private function bien(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $t = $pdf->template();

        // Photo du bien : cadre gris du modèle.
        $placeholder = $t->anchor(1, '/^<photo du bien>/');
        $pdf->mask($placeholder->grow(1), 40, 125);
        if ($extra->photo !== null) {
            $this->photo($pdf, $extra->photo, new Box(34.0, 115.5, 214.6, 221.6));
        }

        $first = $t->anchor(1, '/^adresse :/');
        $last = $t->anchor(1, '/^adresse :/', 1);
        $pdf->mask(new Box($first->x0 - 1, $first->y0, 558, $last->y1 + 1), $first->x0 - 4, $first->y0 + 4);

        $lines = [
            ['adresse : ', $data->adresseBien(), Canvas::SANS_SEMIBOLD, ''],
            ['type de bien : ', $data->typeBien(), Canvas::SANS, ''],
            ['année de construction : ', $data->anneeConstruction() ?? '', Canvas::SANS, ''],
            // « surface de référence » depuis l'édition 2024, « surface habitable » avant.
            [self::libelle($pdf, '/^surface (de référence|habitable)\s?:/u', 'surface de référence : '), Format::surface($data->surfaceReference()), Canvas::SANS, 'B'],
        ];
        if ($data->variant() === TemplateVariant::IMMEUBLE) {
            $lines[] = ['nombre de logements : ', (string) ($data->nombreLogements() ?? ''), Canvas::SANS, ''];
        }
        $lines[] = null;
        $lines[] = ['propriétaire : ', $extra->nomProprietaire ?? $data->nomProprietaire() ?? '', Canvas::SANS, ''];
        $lines[] = ['adresse : ', $extra->adresseProprietaire ?? $data->adresseProprietaire(), Canvas::SANS, ''];

        $top = $first->y0;
        $width = 555 - $first->x0;
        foreach ($lines as $line) {
            if ($line === null) {
                $top += 11.0;
                continue;
            }
            [$label, $value, $family, $style] = $line;
            $pdf->font(Canvas::SANS, 9, '', Canvas::GREY);
            $labelWidth = $pdf->GetStringWidth($label);
            $pdf->textAt($first->x0, $top, $label);
            $pdf->font($family, 9, $style, Canvas::BLACK);
            $rows = $pdf->wrap($value, $width - $labelWidth);
            foreach (array_slice($rows, 0, 2) as $i => $row) {
                $pdf->textAt($first->x0 + $labelWidth, $top, $row);
                $top += 10.75;
            }
        }
    }

    /** Libellé du modèle (« surface habitable : »…) repris tel qu'il est écrit. */
    private static function libelle(Canvas $pdf, string $pattern, string $defaut): string
    {
        $line = $pdf->template()->find(1, $pattern);
        if ($line === null || preg_match($pattern, $line->text, $m) !== 1) {
            return $defaut;
        }

        return rtrim($m[0], ' :') . ' : ';
    }

    private function photo(Canvas $pdf, string $photo, Box $frame): void
    {
        $size = @getimagesizefromstring($photo);
        if ($size === false || $size[0] === 0 || $size[1] === 0) {
            return;
        }
        // Remplit le cadre (recadrage centré), comme sur les rapports déposés.
        $scale = max($frame->width() / $size[0], $frame->height() / $size[1]);
        $w = $size[0] * $scale;
        $h = $size[1] * $scale;
        $pdf->StartTransform();
        $pdf->Rect($frame->x0, $frame->y0, $frame->width(), $frame->height(), 'CNZ');
        $pdf->Image('@' . $photo, $frame->x0 + ($frame->width() - $w) / 2, $frame->y0 + ($frame->height() - $h) / 2, $w, $h);
        $pdf->StopTransform();
    }

    private function etiquettes(Canvas $pdf, DpeData $data): void
    {
        $t = $pdf->template();
        $label = new EnergyLabel($pdf);
        $batiment = $data->variant()->isBatiment();

        $top = $t->anchor(1, '/extrêmement performant$/');
        $x = $top->x0 + 0.4;
        $y = $top->y1 + 2.6;
        $pdf->mask(new Box($x - EnergyLabel::BOX - 13, $y - 0.8, 380.5, $y + EnergyLabel::height() + 0.2), $x - 30, $y - 4);
        $label->energie($x, $y, $data->classeDpe(), [
            'ep' => Format::int($data->epConsoM2()),
            'ges' => Format::int($data->gesM2()),
            'ef' => Format::int($data->efConsoM2()),
            'unite' => 'kWh/m²/an',
        ]);

        $ges = $t->anchor(1, '/^peu d’émissions de CO/');
        $gx = $ges->x0 + 0.2;
        $gy = $ges->y1 + 2.1;
        $pdf->mask(new Box($gx - 1, $gy - 0.5, $gx + 145, $gy + 93.2), $gx + 100, $gy - 3);
        $label->ges($gx, $gy, $data->classeGes(), Format::int($data->gesM2()));

        // Bulle verte : équivalent kilométrique des émissions.
        $emet = $t->anchor(1, '/^Ce (logement|bâtiment) émet/');
        $next = $t->anchor(1, '/^Le niveau d’émissions dépend/');
        $pdf->mask(new Box($emet->x0 - 0.5, $emet->y0 + 0.3, $emet->x0 + 150, $next->y0 + 0.5), $emet->x0 - 2, $emet->y0 + 5);
        $pdf->font(Canvas::SANS, 7.5, 'B', Canvas::WHITE);
        $phrase = sprintf(
            'Ce %s émet %s kg de CO₂ par an, soit l’équivalent de %s km parcourus en voiture.',
            $batiment ? 'bâtiment' : 'logement',
            Format::int($data->emissionGesAnnuelle()),
            Format::int($data->kmEquivalents()),
        );
        $rows = $pdf->wrap($phrase, 148);
        if (count($rows) > 3) {
            $pdf->SetFontSize(6.8);
            $rows = $pdf->wrap($phrase, 148);
        }
        $leading = ($next->y0 - $emet->y0) / 3;
        foreach (array_slice($rows, 0, 3) as $i => $row) {
            $pdf->textAt($emet->x0, $emet->y0 + $i * $leading, $row);
        }
    }

    private function couts(Canvas $pdf, DpeData $data): void
    {
        $t = $pdf->template();
        $entre = $t->anchor(1, '/^entre$/');
        $et = $t->anchor(1, '/^et$/');
        $parAn = $t->anchor(1, '/^par an$/');
        [$min, $max] = $data->fourchetteTotale();

        $top = $entre->y0 - 17.5;
        // Le masque s'arrête juste sous les chiffres d'exemple (sans jambage) :
        // plus bas, il couperait le haut des deux ronds de la réglette rose.
        $bas = $entre->y1 + 2;
        $slot1 = new Box($entre->x1 + 3, $top, $et->x0 - 3, $bas);
        $slot2 = new Box($et->x1 + 3, $top, $parAn->x0 - 3, $bas);
        $pdf->mask($slot1, $slot1->x0 + 5, $slot1->y0 - 1);
        $pdf->mask($slot2, $slot2->x0 + 5, $slot2->y0 - 1);

        foreach ([[$slot1, $min], [$slot2, $max]] as [$slot, $value]) {
            $text = Format::euros($value);
            $pdf->font(Canvas::SANS, 23, 'B', Canvas::BLACK);
            $pdf->fitWidth($text, $slot->width() - 2);
            $baselineTop = $entre->y1 - 1.119 * $pdf->fontSize() - 2.3;
            $pdf->textAt(($slot->x0 + $slot->x1) / 2, $baselineTop, $text, 'C');
        }
    }

    private function diagnostiqueur(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void
    {
        $t = $pdf->template();
        $titre = $t->anchor(1, '/^Informations diagnostiqueur/');
        $top = $titre->y1 + 3.5;
        $pdf->fillRect(35.5, $titre->y1 + 0.5, 524.0, $top + 56.0 - $titre->y1, Canvas::WHITE);

        $d = $extra->diagnostiqueur->completer($data->diagnostiqueur());
        $leading = 10.8;
        $x = $titre->x0 + 0.1;

        $pdf->font(Canvas::SANS_SEMIBOLD, 9, '', Canvas::BLACK);
        $pdf->fitWidth($d['entreprise'] ?? '', 172);
        $pdf->textAt($x, $top, $d['entreprise'] ?? '');
        $y = $top + $leading;
        $pdf->font(Canvas::SANS, 9, '', Canvas::BLACK);
        foreach (array_slice(self::adresseLignes($d['adresse'] ?? ''), 0, 2) as $row) {
            $pdf->fitWidth($row, 172);
            $pdf->textAt($x, $y, $row);
            $pdf->SetFontSize(9);
            $y += $leading;
        }
        $pdf->runs($x, $top + 3 * $leading, [
            [Canvas::SANS, 'diagnostiqueur : ', 9, '', Canvas::GREY],
            [Canvas::SANS, $d['nom'] ?? '', 9, '', Canvas::BLACK],
        ]);

        $x2 = $x + 178.1;
        $rows = [
            ['tel : ', $d['telephone'] ?? ''],
            ['email : ', $d['email'] ?? ''],
            ['n° de certification : ', $d['certification'] ?? ''],
            ['organisme de certification : ', $d['organisme'] ?? ''],
        ];
        foreach ($rows as $i => [$label, $value]) {
            $pdf->font(Canvas::SANS, 9, '', Canvas::GREY);
            $w = $pdf->GetStringWidth($label);
            $pdf->font(Canvas::SANS, 9, '', Canvas::BLACK);
            $pdf->fitWidth($value, 178 - $w);
            $size = $pdf->fontSize();
            $pdf->runs($x2, $top + $i * $leading, [
                [Canvas::SANS, $label, 9, '', Canvas::GREY],
                [Canvas::SANS, $value, $size, '', Canvas::BLACK],
            ]);
        }

        if ($extra->logo !== null) {
            $pdf->imageFit($extra->logo, 398.0, $top - 3.0, 74.0, 52.0);
        }
        if ($extra->signature !== null) {
            $pdf->imageFit($extra->signature, 480.0, $top - 3.0, 76.0, 52.0);
        }
    }

    /**
     * « 37 Rue des Mathurins 75008 PARIS » → rue, puis code postal et ville.
     *
     * @return list<string>
     */
    public static function adresseLignes(string $adresse): array
    {
        $adresse = trim(preg_replace('/\s+/u', ' ', $adresse) ?? '');
        if (preg_match('/^(.*?),?\s+(\d{5}\s+\S.*)$/u', $adresse, $m) === 1 && $m[1] !== '') {
            return [rtrim($m[1], ',') . ',', $m[2]];
        }

        return $adresse === '' ? [] : [$adresse];
    }
}
