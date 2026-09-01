<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ValueExtractor;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class ValueExtractorTest extends TestCase
{
    private function doc(string $xml): DOMDocument
    {
        $d = new DOMDocument();
        $d->preserveWhiteSpace = false;
        $d->loadXML($xml);

        return $d;
    }

    public function testFratriesHomonymesSontIndexeesSeparement(): void
    {
        $values = (new ValueExtractor())->extract($this->doc(<<<'XML'
        <dpe><logement><enveloppe><mur_collection>
          <mur><donnee_intermediaire><umur>0.5</umur></donnee_intermediaire></mur>
          <mur><donnee_intermediaire><umur>1.5</umur></donnee_intermediaire></mur>
        </mur_collection></enveloppe></logement></dpe>
        XML));

        self::assertSame('0.5', $values['dpe/logement/enveloppe/mur_collection/mur[1]/donnee_intermediaire/umur']);
        self::assertSame('1.5', $values['dpe/logement/enveloppe/mur_collection/mur[2]/donnee_intermediaire/umur']);
    }

    public function testFilsUniqueNestPasIndexe(): void
    {
        $values = (new ValueExtractor())->extract(
            $this->doc('<dpe><logement><sortie><deperdition><deperdition_mur>12</deperdition_mur></deperdition></sortie></logement></dpe>'),
        );

        self::assertArrayHasKey('dpe/logement/sortie/deperdition/deperdition_mur', $values);
    }

    public function testSortieParEnergieEstIndexeeParTypeDEnergie(): void
    {
        // L'ordre de la collection est produit par le moteur : l'indexer par
        // position opposerait l'électricité de la référence au gaz du moteur.
        $values = (new ValueExtractor())->extract($this->doc(<<<'XML'
        <dpe><logement><sortie><sortie_par_energie_collection>
          <sortie_par_energie><enum_type_energie_id>3</enum_type_energie_id><conso_5_usages>10</conso_5_usages></sortie_par_energie>
          <sortie_par_energie><enum_type_energie_id>1</enum_type_energie_id><conso_5_usages>20</conso_5_usages></sortie_par_energie>
        </sortie_par_energie_collection></sortie></logement></dpe>
        XML));

        $prefix = 'dpe/logement/sortie/sortie_par_energie_collection/sortie_par_energie';
        self::assertSame('10', $values[$prefix . '[enum_type_energie_id=3]/conso_5_usages']);
        self::assertSame('20', $values[$prefix . '[enum_type_energie_id=1]/conso_5_usages']);
    }

    public function testNilEstDistingueDeLaValeurVide(): void
    {
        $values = (new ValueExtractor())->extract($this->doc(
            '<dpe xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><logement><sortie><cout><cout_ch xsi:nil="true"/></cout></sortie></logement></dpe>',
        ));

        self::assertSame('#nil', $values['dpe/logement/sortie/cout/cout_ch']);
    }

    public function testMetadataDeduitPerimetreEtRegime(): void
    {
        $meta = (new ValueExtractor())->metadata($this->doc(<<<'XML'
        <dpe>
          <numero_dpe>2650E0036638H</numero_dpe>
          <administratif>
            <date_etablissement_dpe>2026-01-07</date_etablissement_dpe>
            <enum_version_id>2.6</enum_version_id>
            <diagnostiqueur><usr_logiciel_id>42</usr_logiciel_id><version_moteur_calcul>x_1.0</version_moteur_calcul></diagnostiqueur>
          </administratif>
          <logement><caracteristique_generale><enum_methode_application_dpe_log_id>9</enum_methode_application_dpe_log_id></caracteristique_generale></logement>
        </dpe>
        XML));

        self::assertSame('2650E0036638H', $meta['numero_dpe']);
        self::assertSame('immeuble_collectif', $meta['perimetre']);
        self::assertSame('post_2026', $meta['regime_coef_ep_elec']);
        self::assertSame('x_1.0', $meta['version_moteur_calcul']);
    }

    /**
     * Les quatre périmètres du règlement d'évaluation CSTB §1.1.
     *
     * @dataProvider perimetreProvider
     */
    public function testPerimetreParMethodeDApplication(?int $methode, string $attendu): void
    {
        self::assertSame($attendu, ValueExtractor::perimetre($methode));
    }

    public static function perimetreProvider(): array
    {
        return [
            'maison'              => [1, 'maison_individuelle'],
            'appartement ch/ecs'  => [5, 'appartement_individuel'],
            'appartement mixte'   => [31, 'appartement_individuel'],
            'immeuble'            => [9, 'immeuble_collectif'],
            'immeuble mixte'      => [26, 'immeuble_collectif'],
            'issu immeuble'       => [13, 'appartement_issu_immeuble'],
            'issu immeuble mixte' => [34, 'appartement_issu_immeuble'],
            'neuf rt2012'         => [17, 'logement_neuf'],
            'neuf re2020'         => [21, 'logement_neuf'],
            'absent'              => [null, 'inconnu'],
            'hors enum'           => [99, 'inconnu'],
        ];
    }

    public function testRegimeCoefElecBasculeAu1erJanvier2026(): void
    {
        self::assertSame('pre_2026', ValueExtractor::regimeCoefElec('2025-12-31'));
        self::assertSame('post_2026', ValueExtractor::regimeCoefElec('2026-01-01'));
        self::assertSame('inconnu', ValueExtractor::regimeCoefElec(null));
        self::assertSame('inconnu', ValueExtractor::regimeCoefElec(''));
    }
}
