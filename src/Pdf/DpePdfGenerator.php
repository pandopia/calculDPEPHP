<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf;

use CalculDpePHP\Dto\DonneesRapportPdf;
use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Pdf\Render\Canvas;
use CalculDpePHP\Pdf\Render\Page\PageRenderer;
use CalculDpePHP\Pdf\Render\Page\Page1Synthese;
use CalculDpePHP\Pdf\Render\Page\Page2Enveloppe;
use CalculDpePHP\Pdf\Render\Page\Page3Consommations;
use CalculDpePHP\Pdf\Render\Page\Page4VueEnsemble;
use CalculDpePHP\Pdf\Render\Page\Page5Travaux;
use CalculDpePHP\Pdf\Render\Page\Page6Evolution;
use CalculDpePHP\Pdf\Render\Page\Page7Annexes;
use CalculDpePHP\Pdf\Template\TemplateCatalog;

/**
 * Génère le rapport PDF d'un DPE à partir de son XML ADEME.
 *
 * Chaque page part de la page correspondante du modèle officiel du ministère
 * (resources/pdf/templates) : ses données d'exemple sont masquées puis
 * remplacées par celles du XML. Les éléments absents du XML (photo, logo,
 * signature…) se passent dans DonneesRapportPdf.
 *
 * Le XML doit porter ses résultats (`<sortie>`) : c'est un DPE validé, tel
 * que déposé à l'observatoire ADEME.
 */
final class DpePdfGenerator
{
    public function __construct(private readonly TemplateCatalog $catalog = new TemplateCatalog(__DIR__ . '/../../resources/pdf/templates'))
    {
    }

    public function generate(string $xml, DonneesRapportPdf $extra = new DonneesRapportPdf()): string
    {
        $data = DpeData::fromXml($xml);
        $template = $this->catalog->resolve($data->variant(), $data->dateEtablissement());

        $pdf = new Canvas($template);
        $numero = $extra->numeroDpe ?? $data->numeroDpe();
        $pdf->SetTitle('Diagnostic de performance énergétique' . ($numero !== null ? " n° $numero" : ' – document non officiel, non enregistré auprès de l’ADEME'));
        $pdf->SetSubject($data->adresseBien());

        foreach ($this->pages() as $page) {
            $page->render($pdf, $data, $extra);
        }

        return $pdf->Output('dpe.pdf', 'S');
    }

    /**
     * @return list<PageRenderer>
     */
    private function pages(): array
    {
        return [
            new Page1Synthese(),
            new Page2Enveloppe(),
            new Page3Consommations(),
            new Page4VueEnsemble(),
            new Page5Travaux(),
            new Page6Evolution(),
            new Page7Annexes(),
        ];
    }
}
