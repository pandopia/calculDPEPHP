<?php
declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AutoconsommationPvNulleTest extends TestCase
{
    private function document(?float $pv = 2000.0, ?float $surface = 100.0, ?float $conso = 10000.0, float $ac = 0.0, int $methode = 1, int $present = 1): DOMDocument
    {
        $tag = static fn(string $nom, ?float $v): string => $v === null ? '' : '<' . $nom . '>' . $v . '</' . $nom . '>';
        $surfaceTag = $methode === 26 ? 'surface_habitable_immeuble' : 'surface_habitable_logement';
        $doc = new DOMDocument();
        $doc->loadXML('<dpe><logement><caracteristique_generale><enum_methode_application_dpe_log_id>' . $methode . '</enum_methode_application_dpe_log_id>' . $tag($surfaceTag, $surface) . '</caracteristique_generale>'
            . '<production_elec_enr><donnee_entree><presence_production_pv>' . $present . '</presence_production_pv></donnee_entree><donnee_intermediaire>' . $tag('production_pv', $pv) . $tag('conso_elec_ac', $ac) . $tag('taux_autoproduction', $ac) . '</donnee_intermediaire></production_elec_enr>'
            . '<sortie><ef_conso>' . $tag('conso_5_usages', $conso) . '</ef_conso><production_electricite>' . $tag('conso_elec_ac', $ac) . $tag('conso_elec_ac_autre_usage', $ac)
            . '<conso_elec_ac_ch>0</conso_elec_ac_ch></production_electricite></sortie></logement></dpe>');
        return $doc;
    }

    public function testLaPreuveNeDependQueDeLaReferenceEtNeConcernePasLeChauffage(): void
    {
        $resultat = ReferenceDefects::detect([], $this->document());
        self::assertCount(4, $resultat);
        self::assertArrayHasKey('dpe/logement/sortie/production_electricite/conso_elec_ac_autre_usage', $resultat);
        self::assertArrayNotHasKey('dpe/logement/sortie/production_electricite/conso_elec_ac_ch', $resultat);
        self::assertStringContainsString('sans notre moteur', reset($resultat));
    }

    public function testLaPreuveVautAussiPourUnImmeuble(): void
    {
        self::assertCount(4, ReferenceDefects::detect([], $this->document(methode: 26)));
    }

    public function testLaBorneEstConservatriceSurDesBilansElectriquesDifferents(): void
    {
        $taux = [0.02, 0.05, 0.25, 0.05, 0.50, 0.10];
        foreach (range(1, 100) as $cas) {
            $sh = 10.0 + $cas * 2.0;
            $pv = 0.1 + $cas * 17.0;
            $autres = [27.0, 28.1, 29.0][$cas % 3] * $sh;
            $w = 0.45 * $autres;
            $brut = 0.0;
            foreach ($taux as $i => $coefficient) {
                $conso = (float)(($cas * ($i + 11) * 137) % 30000);
                $w += $coefficient * $conso;
                $brut += $conso;
            }
            $ac = $pv * $w / ($pv + $w);
            $acAutres = $pv * 0.45 * $autres / ($pv + $w);
            $net = $brut - ($ac - $acAutres) + $cas * 100.0; // combustible non électrique en sus
            $borne = $pv * 0.45 * 27.0 * $sh / ($pv + 0.5 * ($net + $pv + 29.0 * $sh));
            self::assertLessThanOrEqual($acAutres + 1e-9, $borne);
        }
    }

    public static function preuvesInsuffisantes(): iterable
    {
        yield 'sans production' => [[0.0]];
        yield 'production absente' => [[null]];
        yield 'surface absente' => [[2000.0, null]];
        yield 'surface nulle' => [[2000.0, 0.0]];
        yield 'consommation absente' => [[2000.0, 100.0, null]];
        yield 'petite production arrondissable' => [[0.001]];
        yield 'demande énorme, borne insuffisante' => [[2000.0, 100.0, 1e12]];
        yield 'autoconsommation positive' => [[2000.0, 100.0, 10000.0, 100.0]];
        yield 'appartement généré, périmètre DI indéterminé' => [[2000.0, 100.0, 10000.0, 0.0, 13]];
        yield 'pas de PV déclaré' => [[2000.0, 100.0, 10000.0, 0.0, 1, 0]];
    }

    #[DataProvider('preuvesInsuffisantes')]
    public function testNeSignalePasSansPreuveOuPourUnZeroDArrondi(array $arguments): void
    {
        self::assertSame([], ReferenceDefects::detect([], $this->document(...$arguments)));
    }
}
