<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\EfConsoCalculator;
use CalculDpePHP\Sortie\EmissionGesCalculator;
use CalculDpePHP\Sortie\EpConsoCalculator;
use CalculDpePHP\Sortie\SortieParEnergieAggregator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HeatingAllocationOutputsTest extends TestCase
{
    /** Le périmètre réglementaire doit rester commun aux quatre sorties. */
    #[DataProvider('perimetres')]
    public function testToutesLesSortiesAppliquentLaMemeCle(int $methode, float $cle, float $attendu): void
    {
        $document = new DOMDocument();
        $document->loadXML('<logement><caracteristique_generale><enum_methode_application_dpe_log_id>' . $methode . '</enum_methode_application_dpe_log_id>'
            . '<surface_habitable_logement>50</surface_habitable_logement><surface_habitable_immeuble>1000</surface_habitable_immeuble></caracteristique_generale>'
            . '<installation_chauffage_collection><installation_chauffage><donnee_entree><rdim>1</rdim><cle_repartition_ch>' . $cle . '</cle_repartition_ch></donnee_entree>'
            . '<donnee_intermediaire><conso_ch>1500</conso_ch><conso_ch_depensier>1800</conso_ch_depensier></donnee_intermediaire>'
            . '<generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_energie_id>2</enum_type_energie_id></donnee_entree>'
            . '<donnee_intermediaire><conso_ch>1500</conso_ch><conso_ch_depensier>1800</conso_ch_depensier></donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection>'
            . '</installation_chauffage></installation_chauffage_collection></logement>');
        $context = new CalculationContext($document, new TableRepository(__DIR__ . '/../../../resources/tables'));
        foreach ([new EfConsoCalculator(), new EpConsoCalculator(), new EmissionGesCalculator(), new SortieParEnergieAggregator()] as $calculator) {
            $calculator->calculate($document->documentElement, $context);
        }
        $xpath = new DOMXPath($document);
        foreach ([
            'ef_conso/conso_ch' => $attendu,
            'ef_conso/conso_ch_depensier' => $attendu * 1.2,
            'ep_conso/ep_conso_ch' => $attendu,
            'emission_ges/emission_ges_ch' => $attendu * 0.227,
            'sortie_par_energie_collection/sortie_par_energie[enum_type_energie_id=2]/conso_ch' => $attendu,
        ] as $path => $valeur) {
            self::assertEqualsWithDelta($valeur, (float)$xpath->evaluate('string(/logement/sortie/' . $path . ')'), 1e-7, $path);
        }
    }

    public static function perimetres(): iterable
    {
        yield 'direct ignore une cle reservee aux generes' => [2, 0.25, 1500.0];
        yield 'genere conserve sa cle explicite' => [10, 0.25, 375.0];
        yield 'genere utilise la part surfacique par defaut' => [10, 1.0, 75.0];
    }
}
