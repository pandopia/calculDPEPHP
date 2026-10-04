<?php

declare(strict_types=1);

namespace Tests\Unit\Pdf;

use CalculDpePHP\Pdf\Data\DpeData;
use CalculDpePHP\Pdf\Template\TemplateVariant;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DpeDataTest extends TestCase
{
    public const FIXTURE = __DIR__ . '/../../Fixtures/pdf/dpe_maison_fictif.xml';

    private function data(?callable $edit = null): DpeData
    {
        $xml = (string) file_get_contents(self::FIXTURE);
        if ($edit !== null) {
            $xml = $edit($xml);
        }

        return DpeData::fromXml($xml);
    }

    /**
     * Fourchette des rapports d'exemple : −15 % à la dizaine inférieure,
     * +15 % à la dizaine supérieure (1 152,91 € ⇒ 970 à 1 330 €).
     */
    public function testFourchetteDesCouts(): void
    {
        self::assertSame([970, 1330], DpeData::fourchette(1152.9071626289215));
        self::assertSame([220, 310], DpeData::fourchette(265.95665884330742));
        self::assertSame([0, 20], DpeData::fourchette(11.27));
        self::assertSame([0, 0], DpeData::fourchette(0.0));
    }

    public function testFourchetteTotaleEstLaSommeDesUsages(): void
    {
        // 1800 ⇒ 1530-2070 ; 300 ⇒ 250-350 ; 60 ⇒ 50-70 ; 40 ⇒ 30-50.
        self::assertSame([1860, 2540], $this->data()->fourchetteTotale());
    }

    public function testGainComportementSurLesConsommationsDepensieres(): void
    {
        $data = $this->data();

        // (25000 − 20000) / 25000 = 20 % ; 1800 × 5000 / 20000 = 450 €.
        self::assertSame(['pourcentage' => 20, 'euros' => 450], $data->gainComportement('chauffage'));
        self::assertSame(['pourcentage' => 25, 'euros' => 100], $data->gainComportement('ecs'));
    }

    public function testGainNulSansConsommation(): void
    {
        $data = $this->data(static fn (string $x): string => str_replace('<conso_ch>20000</conso_ch>', '<conso_ch>0</conso_ch>', $x));

        self::assertSame(['pourcentage' => 0, 'euros' => 0], $data->gainComportement('chauffage'));
    }

    public function testVolumeEcsParAdulteEquivalent(): void
    {
        $data = $this->data();

        self::assertEqualsWithDelta(112.0, $data->volumeEcsJournalier(), 1e-9);
        self::assertEqualsWithDelta(158.0, $data->volumeEcsJournalierDepensier(), 1e-9);
    }

    public function testNadeqImmeubleRameneAuLogementMoyen(): void
    {
        $data = $this->data(static fn (string $x): string => str_replace(
            ['<enum_methode_application_dpe_log_id>1<', '</caracteristique_generale>'],
            ['<enum_methode_application_dpe_log_id>6<', '<nombre_appartement>4</nombre_appartement></caracteristique_generale>'],
            $x,
        ));

        self::assertSame(TemplateVariant::IMMEUBLE, $data->variant());
        self::assertEqualsWithDelta(0.5, $data->nadeqLogement(), 1e-9);
    }

    public function testNadeqAppartementIssuImmeubleRecalculeSurSaSurface(): void
    {
        $data = $this->data(static fn (string $x): string => str_replace(
            ['<enum_methode_application_dpe_log_id>1<', '<surface_habitable_logement>100<'],
            ['<enum_methode_application_dpe_log_id>10<', '<surface_habitable_logement>70.6<'],
            $x,
        ));

        // Nmax = 0,035 × 70,6 ; Nadeq = 1,75 + 0,3 × (Nmax − 1,75) ⇒ 110 ℓ/j (rapport d'exemple).
        self::assertSame(110, (int) round($data->volumeEcsJournalier()));
        self::assertEqualsWithDelta(1.0, DpeData::nadeqCollectif(5.0), 1e-9);
        self::assertEqualsWithDelta(1.3375, DpeData::nadeqCollectif(28.0), 1e-9);
    }

    public function testDatesEtValidite(): void
    {
        $data = $this->data();

        self::assertSame('2026-03-12', $data->dateEtablissement()->format('Y-m-d'));
        self::assertSame('2036-03-11', $data->dateFinValidite()->format('Y-m-d'));
        self::assertSame('2026-03-10', $data->dateVisite()?->format('Y-m-d'));
    }

    public function testPartsDeperditions(): void
    {
        $parts = $this->data()->partsDeperditions();

        self::assertEqualsWithDelta(40.0, $parts['murs'], 1e-9);
        self::assertEqualsWithDelta(30.0, $parts['ventilation'], 1e-9);
        self::assertEqualsWithDelta(100.0, array_sum($parts), 1e-9);
    }

    public function testEnergiesTrieesParConsommation(): void
    {
        $data = $this->data();

        self::assertSame([3], $data->energiesUsage('chauffage'));
        self::assertSame([1], $data->energiesUsage('ecs'));
        self::assertSame([1], $data->energiesUsage('eclairage'));
        self::assertSame([], $data->energiesUsage('refroidissement'));
    }

    public function testPacksEtClassesApresTravaux(): void
    {
        $data = $this->data();
        $packs = $data->packsTravaux();

        self::assertSame([1, 3], array_keys($packs));
        self::assertSame("Isolation des murs par l'extérieur.\nVérifier l'absence d'humidité.", $packs[1]['travaux'][0]['description']);
        // 150 kWh/m² et 30 kg/m² : classe C en énergie, D en climat ⇒ étiquette D (double seuil).
        self::assertSame(['dpe' => 'D', 'ges' => 'D'], $data->classesPour(150, 30));
        self::assertSame(['dpe' => 'B', 'ges' => 'A'], $data->classesPour(80, 5));
    }

    public function testDescriptifsGestesEtFiches(): void
    {
        $data = $this->data();

        self::assertSame(["Mur en blocs de béton non isolé donnant sur l'extérieur"], $data->descriptifs(1));
        self::assertSame([], $data->descriptifs(2));
        self::assertSame('Chaudière', $data->gestesEntretien()[0]['categorie']);
        self::assertCount(2, $data->fichesTechniques());
        self::assertSame(['Plans du logement'], $data->justificatifs());
        self::assertSame([1], $data->enrPresentes());
        self::assertSame(4, $data->qualiteIsolation());
        self::assertSame(3, $data->qualiteIsolation('plancher_haut'));
    }

    public function testConfortEte(): void
    {
        $confort = $this->data()->confortEte();

        self::assertNotNull($confort);
        self::assertSame(1, $confort['indicateur']);
        self::assertTrue($confort['inertie_lourde']);
        self::assertFalse($confort['isolation_toiture']);
    }

    public function testAdresseAvecComplement(): void
    {
        $data = $this->data(static fn (string $x): string => str_replace(
            "<label_brut>1 rue de l'Exemple 75000 Paris</label_brut>",
            "<label_brut>1 rue de l'Exemple 75000 Paris</label_brut><compl_ref_logement>Étage 2</compl_ref_logement>",
            $x,
        ));

        self::assertSame("1 rue de l'Exemple 75000 Paris / Étage 2", $data->adresseBien());
        self::assertSame('maison individuelle', $data->typeBien());
        self::assertSame(100.0, $data->surfaceReference());
    }

    public function testRejetteUnXmlQuiNestPasUnDpe(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DpeData::fromXml('<autre/>');
    }

    public function testRejetteUnXmlIllisible(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DpeData::fromXml('<dpe>');
    }
}
