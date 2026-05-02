<?php

declare(strict_types=1);

namespace Tests\Unit\Ecs;

use CalculDpe\Ecs\BesoinEcsCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour BesoinEcsCalculator (§11.1 p.70-72).
 */
final class BesoinEcsCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 1.0; // kWh

    /**
     * XML minimal collectif (enum_type_installation_id=2) :
     * Nadeq = apport.nadeq → besoin bâtiment entier.
     */
    private function buildCollectifLogement(): array
    {
        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>1034.74</surface_habitable_logement>
        <surface_habitable_immeuble>1034.74</surface_habitable_immeuble>
        <nombre_appartement>19</nombre_appartement>
    </caracteristique_generale>
    <installation_ecs_collection>
        <installation_ecs>
            <donnee_entree>
                <enum_type_installation_id>2</enum_type_installation_id>
            </donnee_entree>
        </installation_ecs>
    </installation_ecs_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return [$doc, $doc->getElementsByTagName('logement')->item(0)];
    }

    /**
     * XML minimal individuel (enum_type_installation_id=1) :
     * Nadeq calculé pour 1 logement selon §11.1 p.70-71.
     */
    private function buildIndividuelLogement(float $shLogement = 75.14, ?float $shImmeuble = null, ?int $nAppart = null): array
    {
        $immeubleTags = '';
        if ($shImmeuble !== null) {
            $immeubleTags .= "<surface_habitable_immeuble>$shImmeuble</surface_habitable_immeuble>";
        }
        if ($nAppart !== null) {
            $immeubleTags .= "<nombre_appartement>$nAppart</nombre_appartement>";
        }

        $xml = <<<XML
<?xml version="1.0"?>
<logement>
    <caracteristique_generale>
        <surface_habitable_logement>$shLogement</surface_habitable_logement>
        $immeubleTags
    </caracteristique_generale>
    <installation_ecs_collection>
        <installation_ecs>
            <donnee_entree>
                <enum_type_installation_id>1</enum_type_installation_id>
            </donnee_entree>
        </installation_ecs>
    </installation_ecs_collection>
</logement>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return [$doc, $doc->getElementsByTagName('logement')->item(0)];
    }

    private function makeContext(DOMDocument $doc, array $extra = [], ?string $zone = '1', ?string $alt = '1'): CalculationContext
    {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            zoneClimatique: $zone,
            classeAltitude: $alt,
        );
        foreach ($extra as $k => $v) {
            $ctx->set($k, $v);
        }
        return $ctx;
    }

    /**
     * Installation COLLECTIVE → Becs calculé sur Nadeq total (bâtiment).
     * zone H1a, alt 400m, Nadeq=34.13977 ≈ verif 22349 kWh.
     */
    public function testCollectifBesoinEcsMatchesVerif(): void
    {
        [$doc, $node] = $this->buildCollectifLogement();
        $ctx = $this->makeContext($doc, ['apport.nadeq' => 34.13977]);

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $becs = (float)$doc->getElementsByTagName('besoin_ecs')->item(0)->textContent;
        $this->assertEqualsWithDelta(22349.057, $becs, self::TOL);
    }

    /**
     * Installation INDIVIDUELLE dans immeuble collectif →
     * Nadeq pour 1 logement : Shmoy = Sh_immeuble/Nblgt, formule §11.1 p.70-71.
     *
     * Shmoy = 9543 / 127 = 75.14 → Nmax = 0.035 × 75.14 = 2.630
     * Nadeq_lgt = 1.75 + 0.3 × (2.630 − 1.75) = 2.014
     * Becs ≈ 1.163 × 2.014 × 56 × Σ(40−Tefs)×njj / 1000
     */
    public function testIndividuelImmeubleBesoinEcsPerLogement(): void
    {
        // Shmoy = 9543 / 127 = 75.14
        [$doc, $node] = $this->buildIndividuelLogement(75.14, 9543.0, 127);
        $ctx = $this->makeContext($doc, ['apport.nadeq' => 255.776]); // total bâtiment (ignoré)

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $becs = (float)$doc->getElementsByTagName('besoin_ecs')->item(0)->textContent;
        // Nadeq_lgt = 2.014, doit être << apport.nadeq basé
        $this->assertGreaterThan(1000.0, $becs);
        $this->assertLessThan(5000.0, $becs);
        // Besoin doit être ~79/56 fois plus grand pour dépensier
        $becsDep = (float)$doc->getElementsByTagName('besoin_ecs_depensier')->item(0)->textContent;
        $this->assertEqualsWithDelta(79.0 / 56.0, $becsDep / $becs, 0.001);
    }

    /**
     * Installation INDIVIDUELLE logement isolé (pas d'immeuble) →
     * Nadeq depuis nmaxIndividuel(Sh).
     * Sh=80m² → Nmax = 0.025 × 80 = 2.0 → Nadeq = 1.75 + 0.3×0.25 = 1.825
     */
    public function testIndividuelLogementIsoleBesoin(): void
    {
        [$doc, $node] = $this->buildIndividuelLogement(80.0);
        $ctx = $this->makeContext($doc, ['apport.nadeq' => 1.825]);

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $becs = (float)$doc->getElementsByTagName('besoin_ecs')->item(0)->textContent;
        $this->assertGreaterThan(0.0, $becs);

        // Ratio dépensier/conventionnel = 79/56
        $becsDep = (float)$doc->getElementsByTagName('besoin_ecs_depensier')->item(0)->textContent;
        $this->assertEqualsWithDelta(79.0 / 56.0, $becsDep / $becs, 0.001);
    }

    /**
     * V40 journalier = Nadeq_résumé × 56.
     */
    public function testV40JournalierEqualsNadeqTimes56(): void
    {
        [$doc, $node] = $this->buildCollectifLogement();
        $nadeq = 34.13977;
        $ctx   = $this->makeContext($doc, ['apport.nadeq' => $nadeq]);

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $v40 = (float)$doc->getElementsByTagName('v40_ecs_journalier')->item(0)->textContent;
        $this->assertEqualsWithDelta($nadeq * 56.0, $v40, 0.001);
    }

    /**
     * V40 journalier dépensier = Nadeq_résumé × 79.
     */
    public function testV40JournalierDepensierEqualsNadeqTimes79(): void
    {
        [$doc, $node] = $this->buildCollectifLogement();
        $nadeq = 34.13977;
        $ctx   = $this->makeContext($doc, ['apport.nadeq' => $nadeq]);

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $v40Dep = (float)$doc->getElementsByTagName('v40_ecs_journalier_depensier')->item(0)->textContent;
        $this->assertEqualsWithDelta($nadeq * 79.0, $v40Dep, 0.001);
    }

    /**
     * Résultats stockés dans le contexte.
     */
    public function testContextStoredCorrectly(): void
    {
        [$doc, $node] = $this->buildCollectifLogement();
        $ctx = $this->makeContext($doc, ['apport.nadeq' => 34.13977]);

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $becs = (float)$doc->getElementsByTagName('besoin_ecs')->item(0)->textContent;
        $this->assertEqualsWithDelta($becs, (float)$ctx->get('ecs.besoin_ecs', 0.0), 0.001);
        $this->assertArrayHasKey(1, (array)$ctx->get('ecs.besoin_ecs_mensuel', []));
    }

    /**
     * besoin_ecs écrit dans donnee_intermediaire de chaque installation_ecs.
     */
    public function testBesoinEcsWrittenToInstallationEcs(): void
    {
        [$doc, $node] = $this->buildCollectifLogement();
        $ctx = $this->makeContext($doc, ['apport.nadeq' => 34.13977]);

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $this->assertNotNull($di);
        $becsInDi = (float)$di->getElementsByTagName('besoin_ecs')->item(0)->textContent;
        $this->assertEqualsWithDelta(22349.057, $becsInDi, self::TOL);
    }

    /**
     * Décembre = 24 jours (pas 31) — occupation conventionnelle §11.1 p.71.
     */
    public function testDecemberIs24Days(): void
    {
        [$doc, $node] = $this->buildCollectifLogement();
        $ctx = $this->makeContext($doc, ['apport.nadeq' => 1.0]);

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $mensuel = (array)$ctx->get('ecs.besoin_ecs_mensuel', []);
        $this->assertArrayHasKey(12, $mensuel);
        // Décembre: njj=24. Février: njj=28.
        // Ratio dec/feb = 24 × (40-Tefs12) / (28 × (40-Tefs2)) pour zone 1 alt 1
        $feb = $mensuel[2] ?? 0.0;
        $dec = $mensuel[12] ?? 0.0;
        $expectedRatio = (24.0 * (40.0 - 9.2)) / (28.0 * (40.0 - 5.5));
        if ($feb > 0.0) {
            $this->assertEqualsWithDelta($expectedRatio, $dec / $feb, 0.01);
        }
    }

    /**
     * Sans données climatiques (zone=null) → Tefs=0 pour tous les mois.
     */
    public function testNoClimateDataUsesZeroTefs(): void
    {
        [$doc, $node] = $this->buildCollectifLogement();
        $ctx = $this->makeContext($doc, ['apport.nadeq' => 1.0], null, null);

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        // Tefs = 0 → Becsj = 1.163 × 1 × 56 × 40 × njj
        $njjTotal = 31+28+31+30+31+30+31+31+30+31+30+24;
        $expected = 1.163 * 56.0 * 40.0 * $njjTotal / 1000.0;
        $becs = (float)$doc->getElementsByTagName('besoin_ecs')->item(0)->textContent;
        $this->assertEqualsWithDelta($expected, $becs, 1.0);
    }

    /**
     * §11.5 — pertes_distribution_ecs_recup pour bat_post2026 (collectif, H1a).
     * Valeurs de référence extraites du fichier verif ADEME.
     * Tau=0.212 (collectif), sumNref19=5792 h, besoin_ecs=22349.06 kWh.
     */
    public function testPertesDistributionEcsRecupCollectifH1a(): void
    {
        [$doc, $node] = $this->buildCollectifLogement();
        // nadeq must produce besoin_ecs ≈ 22349 kWh (nadeq=34.14 for H1a)
        $ctx = $this->makeContext($doc, ['apport.nadeq' => 34.13977], '1', '1');

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $pertesRecup    = (float)$ctx->get('ecs.pertes_distribution_recup');
        $pertesRecupDep = (float)$ctx->get('ecs.pertes_distribution_recup_dep');

        // Expected from verif: 1503.70 and 2173.30 kWh
        $this->assertEqualsWithDelta(1503.70, $pertesRecup,    2.0, 'pertes_distribution_ecs_recup');
        $this->assertEqualsWithDelta(2173.30, $pertesRecupDep, 3.0, 'pertes_distribution_ecs_recup_depensier');
    }

    /**
     * §11.5 — Tau=0.1 pour installation individuelle → pertes moins importantes.
     */
    public function testPertesDistributionEcsRecupIndividuelTau(): void
    {
        [$doc, $node] = $this->buildIndividuelLogement(75.14);
        $ctx = $this->makeContext($doc, ['apport.nadeq' => 2.0], '1', '1');

        (new BesoinEcsCalculator())->calculate($node, $ctx);

        $pertesRecup    = (float)$ctx->get('ecs.pertes_distribution_recup');
        $pertesRecupDep = (float)$ctx->get('ecs.pertes_distribution_recup_dep');

        // Collectif gives 1503.70 for nadeq=34.14 → individuel (nadeq=2, Tau=0.1/0.212 ratio)
        // At nadeq=2: becs ≈ 22349*(2/34.14) ≈ 1310 kWh, Tau=0.1 → pertes≈1503.7*(2/34.14)*(0.1/0.212)
        $this->assertGreaterThan(0.0, $pertesRecup);
        $this->assertLessThan(1503.70, $pertesRecup); // must be less than collective
        $this->assertGreaterThan(0.0, $pertesRecupDep);
    }
}
