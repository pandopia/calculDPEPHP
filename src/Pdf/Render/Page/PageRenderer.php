<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render\Page;

use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\Render\Canvas;

/**
 * Remplit une ou plusieurs pages du rapport à partir d'une page du modèle.
 */
interface PageRenderer
{
    public function render(Canvas $pdf, DpeData $data, DonneesRapportPdf $extra): void;
}
