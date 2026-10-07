<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Strategy;

use CalculDpePHP\Chauffage\Strategy\MultiGenerateurs;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MultiGenerateursTest extends TestCase
{
    /** §9.1.4 p.61-62 : chaque générateur reçoit sa part avec son propre rendement. */
    #[DataProvider('couplesPacChaudiere')]
    public function testRepartitLeBesoinEntrePacEtChaudiere(
        int $typePac, int $typeChaudiere, string $zone, float $partPac, bool $inverse, float $rdim,
    ): void {
        [$document, $installation, $context] = $this->installation($typePac, $typeChaudiere, $zone, $inverse, $rdim);
        (new MultiGenerateurs())->calculate($installation, $context);
        $xpath = new DOMXPath($document);
        $pac = $xpath->query('//generateur_chauffage[donnee_entree/reference="pac"]')->item(0);
        $chaudiere = $xpath->query('//generateur_chauffage[donnee_entree/reference="chaudiere"]')->item(0);
        self::assertInstanceOf(DOMElement::class, $pac);
        self::assertInstanceOf(DOMElement::class, $chaudiere);
        // G=1, INT=0.9, Re=0.95, Rd=0.9 et Rr=0.8 ; Rg PAC=3, chaudière=0.8.
        $base = 1000 * 0.9 / (0.95 * 0.9 * 0.8 * $rdim);
        $cPac = $base * $partPac / 3;
        $cChaudiere = $base * (1 - $partPac) / 0.8;
        self::assertEqualsWithDelta($cPac, (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch)', $pac), 1e-8);
        self::assertEqualsWithDelta($cChaudiere, (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch)', $chaudiere), 1e-8);
        self::assertEqualsWithDelta($cPac + $cChaudiere, (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch)', $installation), 1e-8);
        // Le scénario dépensier possède son rendement de chaudière propre (0.9).
        $cDep = 1.2 * $base * ($partPac / 3 + (1 - $partPac) / 0.9);
        self::assertEqualsWithDelta($cDep, (float)$xpath->evaluate('string(donnee_intermediaire/conso_ch_depensier)', $installation), 1e-8);
        self::assertSame(1000.0, (float)$xpath->evaluate('string(donnee_intermediaire/besoin_ch)', $installation));
        $parts = $context->get('chauffage.part_besoin_generateur', []);
        self::assertEqualsWithDelta($partPac, $parts[$pac->getNodePath()] ?? -1, 1e-10);
        self::assertEqualsWithDelta(1 - $partPac, $parts[$chaudiere->getNodePath()] ?? -1, 1e-10);
    }

    public static function couplesPacChaudiere(): iterable
    {
        yield 'PAC classique en H1' => [7, 97, '1', 0.8, false, 1.0];
        yield 'PAC classique en H3' => [7, 97, '8', 0.8, false, 1.0];
        yield 'hybride en H1' => [147, 149, '1', 0.8, false, 1.0];
        yield 'hybride en H2' => [147, 149, '4', 0.83, false, 1.0];
        yield 'hybride en H3 inversée' => [147, 149, '8', 0.88, true, 1.0];
        yield 'cinq logements avec systèmes individuels' => [147, 149, '1', 0.8, true, 5.0];
        // §9.1.4.2 ne restreint pas l'énergie de la chaudière en relève : une
        // chaudière électrique (106) en est une au même titre qu'une chaudière
        // à combustion.
        yield 'relève par chaudière électrique' => [7, 106, '1', 0.8, false, 1.0];
    }

    public function testConserveLeCalculHistoriqueSiLesGenerateursSontIndistincts(): void
    {
        [$document, $installation, $context] = $this->installation(7, 7, '1', false, 1.0);
        (new MultiGenerateurs())->calculate($installation, $context);
        $xpath = new DOMXPath($document);
        self::assertSame(800.0, (float)$xpath->evaluate('string(donnee_intermediaire/besoin_ch)', $installation));
        self::assertSame([], $context->get('chauffage.part_besoin_generateur', []));
    }

    public function testNeDoublePasLeBesoinSiPlusieursInstallationsSontPresentes(): void
    {
        [$document, $installation, $context] = $this->installation(7, 97, '1', false, 1.0);
        $installation->parentNode->appendChild($installation->cloneNode(true));
        (new MultiGenerateurs())->calculate($installation, $context);
        $xpath = new DOMXPath($document);
        self::assertSame(800.0, (float)$xpath->evaluate('string(donnee_intermediaire/besoin_ch)', $installation));
        self::assertSame([], $context->get('chauffage.part_besoin_generateur', []));
    }

    /** @return array{DOMDocument, DOMElement, CalculationContext} */
    private function installation(int $pac, int $chaudiere, string $zone, bool $inverse, float $rdim): array
    {
        $generators = [];
        foreach ([['pac', $pac, 3], ['chaudiere', $chaudiere, 0.8]] as [$ref, $type, $rg]) {
            $generators[] = "<generateur_chauffage><donnee_entree><reference>$ref</reference>"
                . "<enum_type_generateur_ch_id>$type</enum_type_generateur_ch_id>"
                . '<enum_lien_generateur_emetteur_id>1</enum_lien_generateur_emetteur_id></donnee_entree>'
                . "<donnee_intermediaire><rendement_generation>$rg</rendement_generation></donnee_intermediaire></generateur_chauffage>";
        }
        $generators = implode('', $inverse ? array_reverse($generators) : $generators);
        $document = new DOMDocument();
        $document->loadXML(<<<XML
            <logement><caracteristique_generale><hsp>2.5</hsp><surface_habitable_immeuble>100</surface_habitable_immeuble></caracteristique_generale>
            <installation_chauffage_collection><installation_chauffage>
            <donnee_entree><enum_cfg_installation_ch_id>8</enum_cfg_installation_ch_id><surface_chauffee>100</surface_chauffee><rdim>$rdim</rdim></donnee_entree>
            <emetteur_chauffage_collection><emetteur_chauffage>
            <donnee_entree><surface_chauffee>100</surface_chauffee><enum_lien_generateur_emetteur_id>1</enum_lien_generateur_emetteur_id></donnee_entree>
            <donnee_intermediaire><i0>0.9</i0><rendement_emission>0.95</rendement_emission><rendement_distribution>0.9</rendement_distribution><rendement_regulation>0.8</rendement_regulation></donnee_intermediaire>
            </emetteur_chauffage></emetteur_chauffage_collection><generateur_chauffage_collection>$generators</generateur_chauffage_collection>
            </installation_chauffage></installation_chauffage_collection></logement>
        XML);
        $installation = $document->getElementsByTagName('installation_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $installation);
        $context = new CalculationContext($document, new TableRepository(__DIR__ . '/../../../../resources/tables'), zoneClimatique: $zone);
        $context->set('chauffage.besoin_ch', 1000.0);
        $context->set('chauffage.besoin_ch_depensier', 1200.0);
        $context->set('chauffage.gv', 250.0);
        $context->set('chauffage.rendement_generation_depensier', ['chaudiere' => 0.9]);
        return [$document, $installation, $context];
    }
}
