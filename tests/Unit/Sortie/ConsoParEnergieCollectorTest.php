<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\ConsoParEnergieCollector;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

/**
 * Collecte partagée par `sortie_par_energie` et `<cout>`.
 *
 * Ce qu'elle garantit, et que les deux blocs perdaient en la dupliquant : une
 * `donnee_intermediaire` d'installation est à l'échelle du bâtiment, la sortie
 * à celle du logement, et le facteur qui va de l'une à l'autre est **propre à
 * chaque installation**. Deux installations d'énergies différentes se
 * répartissent donc la consommation autrement que leurs consos brutes ne le
 * suggèrent.
 */
final class ConsoParEnergieCollectorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    /**
     * Deux installations individuelles d'un DPE appartement, chacune avec sa
     * clé de répartition : la chaudière gaz dessert 10 % du bâtiment, les
     * panneaux électriques 20 %.
     */
    private function document(float $cleGaz, float $cleElec): DOMDocument
    {
        $xml = <<<XML
        <?xml version="1.0"?>
        <dpe>
          <logement>
            <caracteristique_generale>
              <surface_habitable_logement>50</surface_habitable_logement>
              <nombre_appartement>10</nombre_appartement>
            </caracteristique_generale>
            <installation_chauffage_collection>
              <installation_chauffage>
                <donnee_entree>
                  <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id>
                  <enum_type_installation_id>2</enum_type_installation_id>
                  <rdim>1</rdim>
                  <cle_repartition_ch>{$cleGaz}</cle_repartition_ch>
                </donnee_entree>
                <donnee_intermediaire><conso_ch>1000</conso_ch><conso_ch_depensier>1200</conso_ch_depensier></donnee_intermediaire>
                <generateur_chauffage_collection>
                  <generateur_chauffage>
                    <donnee_entree><enum_type_energie_id>2</enum_type_energie_id></donnee_entree>
                    <donnee_intermediaire><conso_ch>1000</conso_ch><conso_ch_depensier>1200</conso_ch_depensier></donnee_intermediaire>
                  </generateur_chauffage>
                </generateur_chauffage_collection>
              </installation_chauffage>
              <installation_chauffage>
                <donnee_entree>
                  <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id>
                  <enum_type_installation_id>2</enum_type_installation_id>
                  <rdim>1</rdim>
                  <cle_repartition_ch>{$cleElec}</cle_repartition_ch>
                </donnee_entree>
                <donnee_intermediaire><conso_ch>1000</conso_ch><conso_ch_depensier>1200</conso_ch_depensier></donnee_intermediaire>
                <generateur_chauffage_collection>
                  <generateur_chauffage>
                    <donnee_entree><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
                    <donnee_intermediaire><conso_ch>1000</conso_ch><conso_ch_depensier>1200</conso_ch_depensier></donnee_intermediaire>
                  </generateur_chauffage>
                </generateur_chauffage_collection>
              </installation_chauffage>
            </installation_chauffage_collection>
          </logement>
        </dpe>
        XML;

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }

    /** @return array{byEnergie: array<int, array{0: float, 1: float}>, reseauGes: array<int, float>} */
    private function collecter(DOMDocument $doc): array
    {
        $logement = $doc->getElementsByTagName('logement')->item(0);
        self::assertInstanceOf(DOMElement::class, $logement);

        return ConsoParEnergieCollector::collecter(
            new NodeAccessor($doc),
            $logement,
            'installation_chauffage',
            'generateur_chauffage',
            'conso_ch',
            new CalculationContext(
                document: $doc,
                tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            ),
        );
    }

    /**
     * Les deux installations publient la même conso brute ; seules leurs clés
     * les séparent. La collecte doit donc rendre 100 et 200, pas 1 000 et
     * 1 000 — et les parts qu'on en tire, un tiers / deux tiers.
     */
    public function testChaqueInstallationEstRamenéeParSaPropreCle(): void
    {
        $collecte = $this->collecter($this->document(0.1, 0.2));

        self::assertEqualsWithDelta(100.0, $collecte['byEnergie'][2][0], 1e-9);
        self::assertEqualsWithDelta(200.0, $collecte['byEnergie'][1][0], 1e-9);
        self::assertEqualsWithDelta(120.0, $collecte['byEnergie'][2][1], 1e-9);
        self::assertEqualsWithDelta(240.0, $collecte['byEnergie'][1][1], 1e-9);

        $total = $collecte['byEnergie'][1][0] + $collecte['byEnergie'][2][0];
        self::assertEqualsWithDelta(1 / 3, $collecte['byEnergie'][2][0] / $total, 1e-12);
    }

    /**
     * À clés égales, la mise à l'échelle ne départage plus rien : les parts
     * redeviennent celles des consommations brutes. C'est le cas que
     * l'ancienne lecture traitait correctement — et le seul.
     */
    public function testAClesEgalesLesPartsSontCellesDesConsosBrutes(): void
    {
        $collecte = $this->collecter($this->document(0.15, 0.15));

        self::assertEqualsWithDelta(
            $collecte['byEnergie'][1][0],
            $collecte['byEnergie'][2][0],
            1e-9,
        );
    }

    /**
     * §9.9 : le générateur unique d'un convecteur bi-jonction peut publier
     * le seul circuit de base Cch1 ; son énergie porte néanmoins le total
     * Cch1+Cch2 publié par l'installation.
     */
    public function testGenerateurUniquePorteLeTotalDeLInstallation(): void
    {
        $document = $this->document(0.1, 0.2);
        $xpath = new \DOMXPath($document);
        $generateur = $xpath->query('//installation_chauffage[1]//generateur_chauffage/donnee_intermediaire/conso_ch')->item(0);
        self::assertNotNull($generateur);
        $generateur->textContent = '600';

        $collecte = $this->collecter($document);

        self::assertEqualsWithDelta(100.0, $collecte['byEnergie'][2][0], 1e-9);
    }
}
