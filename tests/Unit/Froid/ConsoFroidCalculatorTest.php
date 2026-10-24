<?php

declare(strict_types=1);

namespace Tests\Unit\Froid;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Froid\ConsoFroidCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ConsoFroidCalculator (§10.3 p.69-70).
 */
final class ConsoFroidCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 0.01;

    #[\PHPUnit\Framework\Attributes\DataProvider('forfaitsParVersion')]
    public function testForfaitSeerVersionne(?string $version, int $zone, ?int $periode, ?int $tableId, float $eer): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation($tableId);
        if ($version !== null) {
            $administratif = $doc->createElement('administratif');
            $administratif->appendChild($doc->createElement('enum_version_id', $version));
            $node->appendChild($administratif);
        }
        if ($periode !== null) {
            $doc->getElementsByTagName('donnee_entree')->item(0)->appendChild($doc->createElement('enum_periode_installation_fr_id', (string)$periode));
        }
        $context = new CalculationContext(document: $doc, tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'), zoneClimatique: (string)$zone);
        $context->set('froid.besoin_fr', 1000.0);
        $context->set('froid.besoin_fr_depensier', 1200.0);
        (new ConsoFroidCalculator())->calculate($node, $context);
        $xp = new \DOMXPath($doc);
        self::assertEqualsWithDelta($eer, (float)$xp->evaluate('string(//climatisation/donnee_intermediaire/eer)'), 1e-9);
        self::assertEqualsWithDelta(900 / $eer, (float)$xp->evaluate('string(//sortie/ef_conso/conso_fr)'), 1e-9);
    }

    public static function forfaitsParVersion(): iterable
    {
        // Feuille ADEME « seer », édition 05aabba7 (16/10/2026) :
        // lignes historiques 1–6 conservées, nouvelles lignes 7–8 pour 2.7.
        foreach ([null, '2.6', '2.7'] as $version) {
            foreach (range(1, 8) as $zone) {
                foreach ([1, 2, 3] as $periode) {
                    $recent = $version === '2.7' ? ($zone === 8 ? 6.365 : 7.125) : ($zone === 8 ? 7.125 : 6.365);
                    $eer = $periode === 3 ? $recent : ($zone === 8 ? [1 => 3.25, 2 => 5.415][$periode] : [1 => 3.6, 2 => 6.175][$periode]);
                    yield ($version ?? 'absent') . "/$zone/$periode" => [$version, $zone, $periode, null, $eer];
                }
            }
        }
        foreach ([1 => 3.6, 2 => 6.175, 3 => 6.365, 4 => 3.25, 5 => 5.415, 6 => 7.125, 7 => 7.125, 8 => 6.365] as $id => $eer) {
            yield "table explicite $id" => ['2.7', 1, null, $id, $eer];
        }
    }

    public function testToutesLesLignesSeerDuXsd27SontCalculablesEtLeurEerEstValide(): void
    {
        $schema = new DOMDocument();
        $schema->load(self::PROJECT_ROOT . '/editeur/public/schemas/DPEv2.7.xsd');
        $xp = new \DOMXPath($schema);
        $declaration = $xp->query('(//xs:element[@name="tv_seer_id"])[1]')->item(0);
        $eerDeclaration = $xp->query('(//xs:element[@name="climatisation"]//xs:element[@name="donnee_intermediaire"]//xs:element[@name="eer"])[1]')->item(0);
        foreach ($xp->query('.//xs:enumeration', $declaration) as $enumeration) {
            $id = (int)$enumeration->getAttribute('value');
            [$doc, $node] = $this->buildLogementWithClimatisation($id);
            $context = $this->makeContext($doc, ['froid.besoin_fr' => 1000.0]);
            (new ConsoFroidCalculator())->calculate($node, $context);
            foreach ([$doc->getElementsByTagName('tv_seer_id')->item(0), $doc->getElementsByTagName('eer')->item(0)] as $valeur) {
                $isole = new DOMDocument();
                $racine = $isole->createElementNS('http://www.w3.org/2001/XMLSchema', 'xs:schema');
                $isole->appendChild($racine);
                $element = $isole->importNode($valeur->nodeName === 'eer' ? $eerDeclaration : $declaration, true);
                $element->removeAttribute('minOccurs');
                $racine->appendChild($element);
                $resultat = new DOMDocument();
                $resultat->appendChild($resultat->importNode($valeur, true));
                self::assertTrue($resultat->schemaValidateSource($isole->saveXML()), "Ligne SEER $id : {$valeur->nodeName}");
            }
        }
    }

    private function buildLogementWithClimatisation(?int $seerIdent = null, ?float $eerDirect = null, ?float $surfaceRefroidie = null): array
    {
        $deXml = '<donnee_entree>';
        if ($seerIdent !== null) {
            $deXml .= "<tv_seer_id>$seerIdent</tv_seer_id>";
        }
        if ($eerDirect !== null) {
            $deXml .= "<eer>$eerDirect</eer>";
        }
        if ($surfaceRefroidie !== null) {
            $deXml .= "<surface_refroidie>$surfaceRefroidie</surface_refroidie>";
        }
        $deXml .= '</donnee_entree>';

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <climatisation_collection>
        <climatisation>$deXml</climatisation>
    </climatisation_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return [$doc, $doc->getElementsByTagName('logement')->item(0)];
    }

    private function buildLogementNoCooling(): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>100</surface_habitable_logement>
    </caracteristique_generale>
    <climatisation_collection/>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return [$doc, $doc->getElementsByTagName('logement')->item(0)];
    }

    private function makeContext(DOMDocument $doc, array $extra = []): CalculationContext
    {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: '1',
            classeAltitude: '1',
        );
        foreach ($extra as $k => $v) {
            $ctx->set($k, $v);
        }
        return $ctx;
    }

    /**
     * Besoin = 0 → conso = 0.
     */
    public function testZeroBesoinOutputsZeroConso(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(3);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 0.0,
            'froid.besoin_fr_depensier' => 0.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $conso = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.0, $conso, self::TOL);
    }

    /**
     * EER direct saisi dans donnee_entree est utilisé en priorité.
     */
    public function testEerDirectPriorityOverSeer(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 4.0);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1200.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        // Cfr = 0.9 × 1000 / 4.0 = 225 kWh
        $conso = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta(225.0, $conso, self::TOL);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('performancesSaisies')]
    public function testLesPerformancesReellesPreserveesPrimentSurLeForfait(int $methode, ?float $eer, ?float $seer, float $attendu): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(3);
        $clim = $doc->getElementsByTagName('climatisation')->item(0);
        $clim->getElementsByTagName('donnee_entree')->item(0)->appendChild($doc->createElement('enum_methode_saisie_carac_sys_id', (string)$methode));
        $di = $doc->createElement('donnee_intermediaire');
        foreach (['eer' => $eer, 'seer' => $seer] as $tag => $value) {
            if ($value !== null) {
                $di->appendChild($doc->createElement($tag, (string)$value));
            }
        }
        $clim->appendChild($di);
        \CalculDpePHP\Xml\OutputPurger::purge($doc);
        $context = $this->makeContext($doc, ['froid.besoin_fr' => 1000.0, 'froid.besoin_fr_depensier' => 1200.0]);

        (new ConsoFroidCalculator())->calculate($node, $context);

        $xp = new \DOMXPath($doc);
        self::assertEqualsWithDelta($attendu, (float)$xp->evaluate('string(//climatisation/donnee_intermediaire/eer)'), 1e-9);
        self::assertEqualsWithDelta(900 / $attendu, (float)$xp->evaluate('string(//sortie/ef_conso/conso_fr)'), 1e-9);
    }

    public static function performancesSaisies(): iterable
    {
        yield 'EER saisi' => [8, 4.7, null, 4.7];
        yield 'EER methode thermodynamique' => [6, 4.7, null, 4.7];
        yield 'SEER converti une fois' => [8, null, 8.0, 7.6];
        yield 'EER deja converti prioritaire' => [8, 7.6, 8.0, 7.6];
        yield 'valeurs invalides repli' => [8, 0.0, -1.0, 6.365];
        yield 'forfait purge ancien EER' => [1, 4.7, 8.0, 6.365];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('installationsDistinctes')]
    public function testChaqueClimatiseurConserveSaPerformanceEtSaCle(float $surface, int $methode, float $totalAttendu): void
    {
        foreach ([false, true] as $inverse) {
            $climatiseurs = [];
            foreach ([['a', 6, 0.1], ['b', 3, 0.2]] as [$reference, $eer, $cle]) {
                $climatiseurs[] = '<climatisation><donnee_entree><reference>' . $reference . '</reference><surface_clim>' . $surface . '</surface_clim>'
                    . '<enum_methode_saisie_carac_sys_id>8</enum_methode_saisie_carac_sys_id><tv_seer_id>3</tv_seer_id><cle_repartition_clim>' . $cle . '</cle_repartition_clim></donnee_entree>'
                    . '<donnee_intermediaire><eer>' . $eer . '</eer></donnee_intermediaire></climatisation>';
            }
            $doc = new DOMDocument();
            $doc->loadXML('<logement><caracteristique_generale><surface_habitable_logement>100</surface_habitable_logement><surface_habitable_immeuble>1000</surface_habitable_immeuble>'
                . '<enum_methode_application_dpe_log_id>' . $methode . '</enum_methode_application_dpe_log_id></caracteristique_generale><climatisation_collection>'
                . implode('', $inverse ? array_reverse($climatiseurs) : $climatiseurs) . '</climatisation_collection></logement>');
            $context = $this->makeContext($doc, ['froid.besoin_fr' => 1000.0, 'froid.besoin_fr_depensier' => 1200.0]);
            foreach ([1, 2] as $execution) {
                \CalculDpePHP\Xml\OutputPurger::purge($doc);
                (new ConsoFroidCalculator())->calculate($doc->documentElement, $context);
                $xp = new \DOMXPath($doc);
                self::assertEqualsWithDelta(6.0, (float)$xp->evaluate('string(//climatisation[donnee_entree/reference="a"]/donnee_intermediaire/eer)'), 1e-9);
                self::assertEqualsWithDelta(3.0, (float)$xp->evaluate('string(//climatisation[donnee_entree/reference="b"]/donnee_intermediaire/eer)'), 1e-9);
                self::assertEqualsWithDelta($totalAttendu, (float)$xp->evaluate('string(//sortie/ef_conso/conso_fr)'), 1e-9);
                self::assertEqualsWithDelta(1.2 * $totalAttendu, $context->get('froid.conso_fr_depensier'), 1e-9);
                $a = (float)$xp->evaluate('string(//climatisation[donnee_entree/reference="a"]/donnee_intermediaire/conso_fr)');
                $b = (float)$xp->evaluate('string(//climatisation[donnee_entree/reference="b"]/donnee_intermediaire/conso_fr)');
                self::assertEqualsWithDelta(2 * $a, $b, 1e-9);
            }
        }
    }

    public static function installationsDistinctes(): iterable
    {
        yield 'deux surfaces partielles' => [25.0, 2, 112.5];
        yield 'logement entier' => [50.0, 2, 225.0];
        yield 'surface totale bornee au logement' => [100.0, 2, 225.0];
        yield 'surfaces absentes repli egalitaire' => [0.0, 2, 225.0];
        yield 'appartement genere cle propre a chaque clim' => [500.0, 13, 37.5];
    }

    /**
     * tv_seer_id=3 (H1/H2, à partir 2015) : EER = 0.95 × 6.7 = 6.365.
     * Cfr = 0.9 × 1000 / 6.365 ≈ 141.4 kWh
     */
    public function testSeerTableId3(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(3);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1000.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $eer      = 0.95 * 6.7;
        $expected = 0.9 * 1000.0 / $eer;
        $conso    = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta($expected, $conso, 0.1);
    }

    /**
     * tv_seer_id=1 (H1/H2, avant 2008) : EER = 3.6 (direct, non × 0.95).
     */
    public function testSeerTableId1Avant2008(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(1);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1000.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $expected = 0.9 * 1000.0 / 3.6;  // EER direct = 3.6 p.69
        $conso    = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta($expected, $conso, 0.1);
    }

    /**
     * Seule une partie du logement est refroidie → rapport surfaceRefroidie/surfaceHabitable.
     */
    public function testPartialCoolingRatio(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 4.0, 50.0); // surface_refroidie = 50 sur 100
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1000.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        // Cfr_logement = 0.9 × 1000 / 4.0 × (50/100) = 225 × 0.5 = 112.5
        $conso = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta(112.5, $conso, self::TOL);
    }

    /**
     * Conso dépensier est ≥ conso conventionnel quand besoin dépensier ≥ besoin conventionnel.
     */
    public function testDepensierConsoGeqConventionnel(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(3);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1500.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $conso    = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $consoDep = (float)$doc->getElementsByTagName('conso_fr_depensier')->item(0)->textContent;
        $this->assertGreaterThanOrEqual($conso, $consoDep);
    }

    /**
     * Résultat stocké dans le contexte.
     */
    public function testContextStoredCorrectly(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 4.0);
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 1000.0,
            'froid.besoin_fr_depensier' => 1000.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $conso = (float)$doc->getElementsByTagName('conso_fr')->item(0)->textContent;
        $this->assertEqualsWithDelta($conso, (float)$ctx->get('froid.conso_fr', 0.0), self::TOL);
    }

    /**
     * Pas de climatisation → conso = 0.
     */
    public function testNoCoolingSystemOutputsZero(): void
    {
        [$doc, $node] = $this->buildLogementNoCooling();
        $ctx = $this->makeContext($doc, [
            'froid.besoin_fr'          => 0.0,
            'froid.besoin_fr_depensier' => 0.0,
        ]);

        (new ConsoFroidCalculator())->calculate($node, $ctx);

        $conso = $doc->getElementsByTagName('conso_fr')->item(0);
        if ($conso !== null) {
            $this->assertEqualsWithDelta(0.0, (float)$conso->textContent, self::TOL);
        } else {
            $this->assertTrue(true); // no output element = handled by caller
        }
    }
    public function test_la_cle_appartement_pondere_les_sorties_et_preserve_les_intermediaires(): void
    {
        foreach ([2 => 1.0, 10 => 0.1, 11 => 0.1, 12 => 0.1, 13 => 0.1, 33 => 0.1, 34 => 0.1, 38 => 0.1, 39 => 0.1, 40 => 0.1] as $method => $share) {
            [$doc, $node] = $this->buildLogementWithClimatisation(null, 1.0, 1000.0);
            $general = $doc->getElementsByTagName('caracteristique_generale')->item(0);
            $general->appendChild($doc->createElement('enum_methode_application_dpe_log_id', (string) $method));
            $general->appendChild($doc->createElement('surface_habitable_immeuble', '1000'));
            $doc->getElementsByTagName('donnee_entree')->item(0)->appendChild($doc->createElement('cle_repartition_clim', '0.1'));
            $ctx = $this->makeContext($doc, ['froid.besoin_fr' => 1000.0, 'froid.besoin_fr_depensier' => 1200.0]);
            (new ConsoFroidCalculator())->calculate($node, $ctx);
            $xpath = new \DOMXPath($doc);
            $this->assertEqualsWithDelta(900.0, (float) $xpath->evaluate('string(//climatisation/donnee_intermediaire/conso_fr)'), 1e-9);
            $this->assertEqualsWithDelta(900.0 * $share, (float) $xpath->evaluate('string(//sortie/ef_conso/conso_fr)'), 1e-9);
            $this->assertEqualsWithDelta(1080.0 * $share, $ctx->get('froid.conso_fr_depensier'), 1e-9);
        }
    }

    public function test_une_cle_absente_ne_repartit_pas_arbitrairement_la_climatisation(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 1.0, 1000.0);
        $doc->getElementsByTagName('caracteristique_generale')->item(0)->appendChild($doc->createElement('enum_methode_application_dpe_log_id', '10'));
        $ctx = $this->makeContext($doc, ['froid.besoin_fr' => 1000.0]);
        $this->expectException(\RuntimeException::class);
        (new ConsoFroidCalculator())->calculate($node, $ctx);
    }

    public function test_le_refroidissement_partiel_garde_la_surface_immeuble_avant_repartition(): void
    {
        [$doc, $node] = $this->buildLogementWithClimatisation(null, 4.0, 500.0);
        $general = $doc->getElementsByTagName('caracteristique_generale')->item(0);
        $general->appendChild($doc->createElement('enum_methode_application_dpe_log_id', '13'));
        $general->appendChild($doc->createElement('surface_habitable_immeuble', '1000'));
        $doc->getElementsByTagName('donnee_entree')->item(0)->appendChild($doc->createElement('cle_repartition_clim', '0.1'));
        $ctx = $this->makeContext($doc, ['froid.besoin_fr' => 1000.0, 'froid.besoin_fr_depensier' => 1200.0]);
        (new ConsoFroidCalculator())->calculate($node, $ctx);
        $xp = new \DOMXPath($doc);
        // 0,9 × 1000 / 4 × 500/1000 = 112,5 kWh immeuble, puis clé 0,1.
        self::assertEqualsWithDelta(112.5, (float) $xp->evaluate('string(//climatisation/donnee_intermediaire/conso_fr)'), 1e-9);
        self::assertEqualsWithDelta(11.25, (float) $xp->evaluate('string(//sortie/ef_conso/conso_fr)'), 1e-9);
        self::assertEqualsWithDelta(13.5, $ctx->get('froid.conso_fr_depensier'), 1e-9);
    }

}
