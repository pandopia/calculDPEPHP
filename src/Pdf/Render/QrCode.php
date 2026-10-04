<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode as QrEncoder;
use chillerlan\QRCode\QROptions;

/**
 * QR code de vérification du DPE, dessiné en modules vectoriels (net à toute
 * résolution d'impression).
 *
 * Il pointe vers la fiche du DPE sur l'observatoire ADEME, comme sur les
 * rapports déposés : https://observatoire-dpe-audit.ademe.fr/afficher-dpe/{numéro}.
 */
final class QrCode
{
    public const URL_OBSERVATOIRE = 'https://observatoire-dpe-audit.ademe.fr/afficher-dpe/';

    public static function urlFor(string $numeroDpe): string
    {
        return self::URL_OBSERVATOIRE . rawurlencode($numeroDpe);
    }

    /**
     * @return list<list<bool>> modules sombres, ligne par ligne, zone de silence exclue
     */
    public static function matrix(string $data): array
    {
        $options = new QROptions([
            'eccLevel' => EccLevel::M,
            'addQuietzone' => false,
        ]);
        $qr = new QrEncoder($options);
        $qr->addByteSegment($data);

        /** @var list<list<bool>> $rows */
        $rows = array_map('array_values', array_values($qr->getQRMatrix()->getMatrix(true)));

        return $rows;
    }

    public static function draw(Canvas $pdf, string $data, float $x, float $y, float $size): void
    {
        $matrix = self::matrix($data);
        $n = count($matrix);
        $cell = $size / $n;
        $pdf->SetFillColor(0, 0, 0);
        foreach ($matrix as $row => $modules) {
            $start = null;
            foreach ($modules as $col => $dark) {
                // Modules contigus d'une ligne fusionnés en un seul rectangle.
                if ($dark && $start === null) {
                    $start = $col;
                }
                if ((!$dark || $col === $n - 1) && $start !== null) {
                    $end = $dark ? $col + 1 : $col;
                    $pdf->Rect($x + $start * $cell, $y + $row * $cell, ($end - $start) * $cell + 0.02, $cell + 0.02, 'F');
                    $start = null;
                }
            }
        }
    }
}
