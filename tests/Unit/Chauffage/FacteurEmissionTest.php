<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage;

use CalculDpePHP\Chauffage\FacteurEmission;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

/**
 * §9.1.3 p.60 : `Cch = Σ_i (Sh_i / Sh × INT_i × Ich_i) × Bch`.
 */
final class FacteurEmissionTest extends TestCase
{
    /** @param list<array{float, float, float, float, float}> $emetteurs [Sh, i0, Re, Rd, Rr] */
    private function installation(array $emetteurs): array
    {
        $xml = '';
        foreach ($emetteurs as [$sh, $i0, $re, $rd, $rr]) {
            $xml .= <<<XML
            <emetteur_chauffage>
                <donnee_entree><surface_chauffee>$sh</surface_chauffee></donnee_entree>
                <donnee_intermediaire>
                    <i0>$i0</i0>
                    <rendement_emission>$re</rendement_emission>
                    <rendement_distribution>$rd</rendement_distribution>
                    <rendement_regulation>$rr</rendement_regulation>
                </donnee_intermediaire>
            </emetteur_chauffage>
XML;
        }
        $document = new DOMDocument();
        $document->loadXML(
            '<installation_chauffage><emetteur_chauffage_collection>'
            . $xml . '</emetteur_chauffage_collection></installation_chauffage>',
        );
        $installation = $document->documentElement;
        self::assertInstanceOf(DOMElement::class, $installation);

        return [new NodeAccessor($document), $installation];
    }

    /**
     * L'exemple de la spec : « un générateur alimentant un plancher chauffant
     * au rez-de-chaussée et des radiateurs en étage ». Les deux émissions
     * n'ont ni le même i0 ni les mêmes rendements, et la somme des produits
     * s'écarte du produit des moyennes — ce que faisait le moteur.
     */
    public function testLaSommeDesProduitsDiffereDuProduitDesMoyennes(): void
    {
        // Plancher chauffant 60 m² : i0 0,98 — radiateurs 40 m² : i0 0,90.
        [$accessor, $installation] = $this->installation([
            [60.0, 0.98, 0.95, 0.90, 0.99],
            [40.0, 0.90, 0.88, 0.80, 0.91],
        ]);

        $obtenu = FacteurEmission::parEmetteurOuNull($accessor, $installation);

        $attendu = (60.0 * 0.98 / (0.95 * 0.90 * 0.99) + 40.0 * 0.90 / (0.88 * 0.80 * 0.91)) / 100.0;
        self::assertNotNull($obtenu);
        self::assertEqualsWithDelta($attendu, $obtenu, 1e-12);

        // Le produit des moyennes pondérées, lui, donne autre chose.
        $i0Moy = (60.0 * 0.98 + 40.0 * 0.90) / 100.0;
        $reMoy = (60.0 * 0.95 + 40.0 * 0.88) / 100.0;
        $rdMoy = (60.0 * 0.90 + 40.0 * 0.80) / 100.0;
        $rrMoy = (60.0 * 0.99 + 40.0 * 0.91) / 100.0;
        $parMoyennes = $i0Moy / ($reMoy * $rdMoy * $rrMoy);

        self::assertNotEqualsWithDelta($parMoyennes, $obtenu, 1e-6);
    }

    /** À émetteurs identiques, les deux lectures coïncident. */
    public function testDeuxEmetteursIdentiquesRedonnentLaMoyenne(): void
    {
        [$accessor, $installation] = $this->installation([
            [50.0, 0.95, 0.90, 0.85, 0.97],
            [30.0, 0.95, 0.90, 0.85, 0.97],
        ]);

        self::assertEqualsWithDelta(
            0.95 / (0.90 * 0.85 * 0.97),
            FacteurEmission::parEmetteurOuNull($accessor, $installation) ?? 0.0,
            1e-12,
        );
    }

    /** Un émetteur unique : rien à sommer, la moyenne suffit. */
    public function testUnEmetteurUniqueNeChangePasDeChemin(): void
    {
        [$accessor, $installation] = $this->installation([[80.0, 0.95, 0.90, 0.85, 0.97]]);
        self::assertNull(FacteurEmission::parEmetteurOuNull($accessor, $installation));
    }

    /** Un émetteur incomplet fait retomber sur les moyennes pondérées. */
    public function testUnEmetteurIncompletRendNull(): void
    {
        [$accessor, $installation] = $this->installation([
            [60.0, 0.98, 0.95, 0.90, 0.99],
            [40.0, 0.90, 0.88, 0.80, 0.91],
        ]);
        $second = $installation->getElementsByTagName('emetteur_chauffage')->item(1);
        $i0 = $second->getElementsByTagName('i0')->item(0);
        $i0->parentNode->removeChild($i0);

        self::assertNull(FacteurEmission::parEmetteurOuNull($accessor, $installation));
    }

    /** Une surface d'émetteur absente aussi : la part §9.1.3 serait arbitraire. */
    public function testUneSurfaceManquanteRendNull(): void
    {
        [$accessor, $installation] = $this->installation([
            [60.0, 0.98, 0.95, 0.90, 0.99],
            [40.0, 0.90, 0.88, 0.80, 0.91],
        ]);
        $second = $installation->getElementsByTagName('emetteur_chauffage')->item(1);
        $sh = $second->getElementsByTagName('surface_chauffee')->item(0);
        $sh->parentNode->removeChild($sh);

        self::assertNull(FacteurEmission::parEmetteurOuNull($accessor, $installation));
    }
}
