<?php

declare(strict_types=1);

namespace Tests\Unit\Ventilation;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Ventilation\HpermCalculator;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** §4 p.38-40 : infiltrations, débits conventionnels et cas Sdep = 0. */
final class HpermCalculatorTest extends TestCase
{
    private function document(
        string $entree = '<enum_type_ventilation_id>25</enum_type_ventilation_id><surface_ventile>100</surface_ventile><plusieurs_facade_exposee>1</plusieurs_facade_exposee>',
        ?string $enveloppe = '',
        string $general = '<hsp>2.5</hsp>',
        string $intermediaire = '<q4pa_conv>2</q4pa_conv>',
    ): DOMDocument {
        $document = new DOMDocument();
        $document->loadXML('<dpe><logement><caracteristique_generale>' . $general . '</caracteristique_generale>'
            . ($enveloppe !== null ? '<enveloppe>' . $enveloppe . '</enveloppe>' : '')
            . '<ventilation_collection><ventilation><donnee_entree>' . $entree . '</donnee_entree>'
            . '<donnee_intermediaire>' . $intermediaire . '</donnee_intermediaire></ventilation></ventilation_collection></logement></dpe>');
        return $document;
    }

    private function calculate(DOMDocument $document): ?float
    {
        $node = $document->getElementsByTagName('ventilation')->item(0);
        self::assertInstanceOf(DOMElement::class, $node);
        (new HpermCalculator())->calculate($node, new CalculationContext(
            document: $document,
            tables: new TableRepository(__DIR__ . '/../../../resources/tables'),
        ));
        $value = $node->getElementsByTagName('hperm')->item(0);
        return $value !== null ? (float)$value->textContent : null;
    }

    private static function formule(float $sdep, float $sh, float $qvarep, float $qvasouf, float $smea, bool $plusieurs): float
    {
        [$e, $f] = $plusieurs ? [0.07, 15] : [0.02, 20];
        $q4pa = 2 * $sdep + 0.45 * $smea * $sh;
        $n50 = $q4pa / ((4 / 50) ** (2 / 3) * 2.5 * $sh);
        if ($n50 === 0.0) {
            return 0.0;
        }
        return 0.34 * (2.5 * $sh * $n50 * $e)
            / (1 + ($f / $e) * (($qvasouf - $qvarep) / (2.5 * $n50)) ** 2);
    }

    public static function debits(): iterable
    {
        // Table p.40 : paramètres indépendants du fichier PHP de production.
        foreach ([1 => [1.2, 1.2, 0], 8 => [1.44, 0, 2], 19 => [0.6, 0.6, 0], 25 => [2.23, 0, 4]] as $type => $row) {
            foreach ([false, true] as $plusieurs) {
                yield "$type, façades " . (int)$plusieurs => [$type, ...$row, $plusieurs];
            }
        }
    }

    private static function parois(): string
    {
        return '<mur_collection><mur><donnee_entree><surface_paroi_opaque>20</surface_paroi_opaque></donnee_entree></mur>'
            . '<mur><donnee_entree><surface_paroi_opaque>1000</surface_paroi_opaque><enum_type_adjacence_id>22</enum_type_adjacence_id></donnee_entree></mur>'
            . '<mur><donnee_entree><surface_paroi_opaque>1000</surface_paroi_opaque></donnee_entree><donnee_intermediaire><b>0</b></donnee_intermediaire></mur></mur_collection>'
            . '<plancher_haut_collection><plancher_haut><donnee_entree><surface_paroi_opaque>30</surface_paroi_opaque></donnee_entree></plancher_haut>'
            . '<plancher_haut><donnee_entree><surface_paroi_opaque>1000</surface_paroi_opaque></donnee_entree><donnee_intermediaire><b>0</b></donnee_intermediaire></plancher_haut></plancher_haut_collection>'
            . '<plancher_bas_collection><plancher_bas><donnee_entree><surface_paroi_opaque>1000</surface_paroi_opaque></donnee_entree></plancher_bas></plancher_bas_collection>'
            . '<baie_vitree_collection><baie_vitree><donnee_entree><surface_totale_baie>5</surface_totale_baie></donnee_entree></baie_vitree>'
            . '<baie_vitree><donnee_entree><surface_totale_baie>1000</surface_totale_baie></donnee_entree><donnee_intermediaire><b>0</b></donnee_intermediaire></baie_vitree></baie_vitree_collection>'
            . '<porte_collection><porte><donnee_entree><surface_porte>2</surface_porte></donnee_entree></porte>'
            . '<porte><donnee_entree><surface_porte>1000</surface_porte></donnee_entree><donnee_intermediaire><b>0</b></donnee_intermediaire></porte></porte_collection>';
    }

    #[DataProvider('debits')]
    public function testFormuleEtSurfacesDeperditivesHorsPlancherBas(
        int $type, float $qvarep, float $qvasouf, float $smea, bool $plusieurs,
    ): void {
        $entry = "<enum_type_ventilation_id>$type</enum_type_ventilation_id><surface_ventile>100</surface_ventile>"
            . '<plusieurs_facade_exposee>' . (int)$plusieurs . '</plusieurs_facade_exposee>';
        self::assertEqualsWithDelta(self::formule(57, 100, $qvarep, $qvasouf, $smea, $plusieurs),
            $this->calculate($this->document($entry, self::parois())), 1e-10);
    }

    public function testSurfaceDeperditiveNulleNometPasHpermNul(): void
    {
        $entry = '<enum_type_ventilation_id>1</enum_type_ventilation_id><surface_ventile>100</surface_ventile>';
        self::assertSame(0.0, $this->calculate($this->document($entry)));
    }

    public function testLesEntreesAirSubsistentQuandLaSurfaceDeperditiveEstNulle(): void
    {
        // Q4Paenv = 0 ne signifie pas Q4Pa = 0 : le terme 0,45 Smea Sh
        // reste présent. Supprimer le calcul à Sdep=0 perd cette infiltration.
        self::assertEqualsWithDelta(self::formule(0, 100, 2.23, 0, 4, true), $this->calculate($this->document()), 1e-10);
    }

    public function testSansDescriptionEnveloppeOnNeSupposePasUneSurfaceNulle(): void
    {
        self::assertNull($this->calculate($this->document(enveloppe: null)));
    }

    public function testAppartementGenerePrendLaSurfaceDeCalculImmeuble(): void
    {
        $general = '<hsp>2.5</hsp><enum_methode_application_dpe_log_id>10</enum_methode_application_dpe_log_id>'
            . '<surface_habitable_logement>50</surface_habitable_logement><surface_habitable_immeuble>200</surface_habitable_immeuble>';
        self::assertEqualsWithDelta(self::formule(57, 200, 2.23, 0, 4, true),
            $this->calculate($this->document(enveloppe: self::parois(), general: $general)), 1e-10);
    }

    public function testHspHistoriqueDirectSousLogementResteLu(): void
    {
        $doc = $this->document(enveloppe: self::parois(), general: '');
        $doc->getElementsByTagName('logement')->item(0)->appendChild($doc->createElement('hsp', '2.5'));
        self::assertEqualsWithDelta(self::formule(57, 100, 2.23, 0, 4, true), $this->calculate($doc), 1e-10);
    }

    public function testDonneesEssentiellesManquantesOuNonPositivesNeDivisentPasParZero(): void
    {
        foreach (['', '<enum_type_ventilation_id>999</enum_type_ventilation_id><surface_ventile>100</surface_ventile>',
            '<enum_type_ventilation_id>25</enum_type_ventilation_id>',
            '<enum_type_ventilation_id>25</enum_type_ventilation_id><surface_ventile>0</surface_ventile>',
            '<enum_type_ventilation_id>25</enum_type_ventilation_id><surface_ventile>-1</surface_ventile>'] as $entry) {
            self::assertNull($this->calculate($this->document($entry, self::parois())));
        }
        self::assertNull($this->calculate($this->document(enveloppe: self::parois(), intermediaire: '')));
        self::assertNull($this->calculate($this->document(enveloppe: self::parois(), general: '')));
        self::assertNull($this->calculate($this->document(enveloppe: self::parois(), general: '<hsp>0</hsp>')));
    }

    public function testPasDeLogementAncetreNeFabriquePasDeHauteur(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<ventilation><donnee_entree><enum_type_ventilation_id>1</enum_type_ventilation_id>'
            . '<surface_ventile>100</surface_ventile></donnee_entree><donnee_intermediaire><q4pa_conv>2</q4pa_conv></donnee_intermediaire></ventilation>');
        self::assertNull($this->calculate($doc));
    }

    public function testSansDonneeEntreeLaStructureInvalideEstSignalee(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<ventilation/>');
        $this->expectException(RuntimeException::class);
        $this->calculate($doc);
    }
}
