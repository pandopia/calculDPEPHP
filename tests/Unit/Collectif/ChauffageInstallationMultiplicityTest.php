<?php

declare(strict_types=1);

namespace Tests\Unit\Collectif;

use CalculDpePHP\Collectif\ChauffageInstallationMultiplicity;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class ChauffageInstallationMultiplicityTest extends TestCase
{
    /** @param list<array{float, int, int}> $installations [surface_chauffee, methode, type] */
    private function document(float $shImmeuble, float $nbAppt, array $installations): DOMDocument
    {
        $xml = '<logement><caracteristique_generale>'
            . '<surface_habitable_immeuble>' . $shImmeuble . '</surface_habitable_immeuble>'
            . '<nombre_appartement>' . $nbAppt . '</nombre_appartement>'
            . '</caracteristique_generale><installation_chauffage_collection>';
        foreach ($installations as [$surface, $methode, $type]) {
            $xml .= '<installation_chauffage><donnee_entree>'
                . '<surface_chauffee>' . $surface . '</surface_chauffee>'
                . '<enum_methode_calcul_conso_id>' . $methode . '</enum_methode_calcul_conso_id>'
                . '<enum_type_installation_id>' . $type . '</enum_type_installation_id>'
                . '</donnee_entree></installation_chauffage>';
        }
        $xml .= '</installation_chauffage_collection></logement>';

        $document = new DOMDocument();
        $document->loadXML($xml);

        return $document;
    }

    /**
     * §17.1.4.2 p.110 : Nblgt_syst_i suit la surface desservie, pas un partage
     * uniforme — sinon le besoin de l'appartement « moyen » varierait d'une
     * installation à l'autre, ce que §17.1.2 exclut.
     *
     * Cas de référence 2675E2152874Y : 955,44 m², 15 appartements, deux
     * installations de 318,48 et 636,96 m² → 5 et 10 logements.
     */
    public function testMultipliciteSuitLaSurfaceDesservie(): void
    {
        $document = $this->document(955.44, 15, [[318.48, 4, 1], [636.96, 4, 1]]);
        $accessor = new NodeAccessor($document);
        $installations = $document->getElementsByTagName('installation_chauffage');

        self::assertEqualsWithDelta(
            5.0,
            ChauffageInstallationMultiplicity::sampledOrNull($installations->item(0), $accessor),
            1e-9,
        );
        self::assertEqualsWithDelta(
            10.0,
            ChauffageInstallationMultiplicity::sampledOrNull($installations->item(1), $accessor),
            1e-9,
        );
    }

    /** La somme des multiplicités reste le nombre d'appartements de l'immeuble. */
    public function testLaSommeRedonneLeNombreDAppartements(): void
    {
        $document = $this->document(955.44, 15, [[318.48, 4, 1], [636.96, 4, 1]]);
        $accessor = new NodeAccessor($document);

        $somme = 0.0;
        foreach ($document->getElementsByTagName('installation_chauffage') as $installation) {
            $somme += ChauffageInstallationMultiplicity::sampledOrNull($installation, $accessor) ?? 0.0;
        }

        self::assertEqualsWithDelta(15.0, $somme, 1e-9);
    }

    /** Installation collective ou calcul bâtiment : la règle ne s'applique pas. */
    public function testCasHorsPerimetre(): void
    {
        $accessor = new NodeAccessor($doc = $this->document(955.44, 15, [[318.48, 1, 1]]));
        self::assertNull(ChauffageInstallationMultiplicity::sampledOrNull(
            $doc->getElementsByTagName('installation_chauffage')->item(0),
            $accessor,
        ));

        $accessor = new NodeAccessor($doc = $this->document(955.44, 15, [[318.48, 4, 2]]));
        self::assertNull(ChauffageInstallationMultiplicity::sampledOrNull(
            $doc->getElementsByTagName('installation_chauffage')->item(0),
            $accessor,
        ));
    }

    /** Sans immeuble décrit, la multiplicité n'est pas calculable. */
    public function testSansImmeubleDecrit(): void
    {
        $accessor = new NodeAccessor($doc = $this->document(0.0, 15, [[318.48, 4, 1]]));
        self::assertNull(ChauffageInstallationMultiplicity::sampledOrNull(
            $doc->getElementsByTagName('installation_chauffage')->item(0),
            $accessor,
        ));
    }
}
