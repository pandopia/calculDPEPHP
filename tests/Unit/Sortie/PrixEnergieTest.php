<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\PrixEnergie;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PrixEnergieTest extends TestCase
{
    /** Une déclaration collective explicite ne se déduit pas d'une surface ou d'une clé. */
    #[DataProvider('descriptionsDeVentilation')]
    public function testCollectiviteDeclaree(array $descriptions, bool $collective): void
    {
        $prix = $this->prix($descriptions);
        self::assertSame($collective, $prix->ventilationCollective);
    }

    public static function descriptionsDeVentilation(): iterable
    {
        yield 'absence de ventilation' => [[], false];
        yield 'description absente' => [[''], false];
        yield 'cle immeuble sans precision' => [['VMC SF Hygro B après 2012'], false];
        yield 'individuelle' => [['VMC (individuelle)'], false];
        yield 'non collective' => [['VMC (non collective)'], false];
        yield 'collective' => [['VMC SF Auto réglable de 2001 à 2012 (collective)'], true];
        yield 'casse et espaces' => [['VMC ( COLLECTIVE )'], true];
        yield 'collectif' => [['Système (collectif)'], true];
        yield 'deux collectives' => [['VMC (collective)', 'VMC (collective)'], true];
        yield 'ensemble mixte' => [['VMC (collective)', 'VMC (individuelle)'], false];
        yield 'ensemble partiellement documente' => [['VMC (collective)', 'VMC'], false];
    }

    /** Annexe 7 : l'abonnement de ventilation ne grossit pas l'assiette des usages individuels. */
    public function testVentilationCollectiveUtiliseLaTrancheDeLImmeuble(): void
    {
        $prix = $this->prix(['VMC (collective)']);
        $couts = $prix->tarifer([
            'chauffage' => [1, 1500.0, false],
            'ecs' => [1, 1000.0, false],
            'ventilation' => [1, 200.0, $prix->ventilationCollective],
        ]);

        // Usages individuels : 2 500 kWh, tranche 2 500-5 000.
        self::assertEqualsWithDelta(158.0 + 0.18949 * 2500.0, $couts['chauffage'] + $couts['ecs'], 1e-9);
        // Ventilation : 200 × (1 000/50) = 4 000 kWh pour le caisson commun.
        self::assertEqualsWithDelta((158.0 + 0.18949 * 4000.0) / 20.0, $couts['ventilation'], 1e-9);
    }

    private function prix(array $descriptions): PrixEnergie
    {
        $document = new DOMDocument();
        $document->loadXML('<dpe><administratif><date_etablissement_dpe>2026-01-01</date_etablissement_dpe></administratif><logement>'
            . '<caracteristique_generale><enum_methode_application_dpe_log_id>10</enum_methode_application_dpe_log_id><surface_habitable_logement>50</surface_habitable_logement><surface_habitable_immeuble>1000</surface_habitable_immeuble></caracteristique_generale>'
            . '<ventilation_collection/></logement></dpe>');
        $collection = $document->getElementsByTagName('ventilation_collection')->item(0);
        self::assertInstanceOf(DOMElement::class, $collection);
        foreach ($descriptions as $description) {
            $ventilation = $document->createElement('ventilation');
            $entree = $document->createElement('donnee_entree');
            $entree->appendChild($document->createElement('description', $description));
            $entree->appendChild($document->createElement('surface_ventile', '1000'));
            $entree->appendChild($document->createElement('cle_repartition_ventilation', '0.05'));
            $ventilation->appendChild($entree);
            $collection->appendChild($ventilation);
        }
        $logement = $document->getElementsByTagName('logement')->item(0);
        self::assertInstanceOf(DOMElement::class, $logement);

        return PrixEnergie::pour($logement, new NodeAccessor($document), new CalculationContext(
            $document, new TableRepository(__DIR__ . '/../../../resources/tables'),
        ));
    }
}
