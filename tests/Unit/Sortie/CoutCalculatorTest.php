<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\CoutCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class CoutCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 1e-6;

    /** @param array<string, float|int|string> $ef */
    private function build(array $ef, int $energieCh = 1, int $energieEcs = 1, ?string $date = null, ?int $nbLogements = null, int $methode = 1, bool $chCollectif = false, ?float $shLogement = null, ?float $shImmeuble = null): DOMDocument
    {
        $defaults = [
            'conso_ch' => 0, 'conso_ch_depensier' => 0,
            'conso_ecs' => 0, 'conso_ecs_depensier' => 0,
            'conso_eclairage' => 0, 'conso_fr' => 0, 'conso_fr_depensier' => 0,
            'conso_auxiliaire_generation_ch' => 0, 'conso_auxiliaire_generation_ch_depensier' => 0,
            'conso_auxiliaire_distribution_ch' => 0,
            'conso_auxiliaire_generation_ecs' => 0, 'conso_auxiliaire_generation_ecs_depensier' => 0,
            'conso_auxiliaire_distribution_ecs' => 0, 'conso_auxiliaire_ventilation' => 0,
        ];
        $ef = array_merge($defaults, $ef);

        $efXml = '';
        foreach ($ef as $tag => $value) {
            $efXml .= "<$tag>$value</$tag>";
        }
        $admin = $date === null ? '' : "<administratif><date_etablissement_dpe>$date</date_etablissement_dpe></administratif>";
        $napp = $nbLogements === null ? '' : "<nombre_appartement>$nbLogements</nombre_appartement>";
        $typeInstallCh = $chCollectif
            ? '<donnee_entree><enum_type_installation_id>2</enum_type_installation_id></donnee_entree>'
            : '';
        $surfaces = ($shLogement === null ? '' : "<surface_habitable_logement>$shLogement</surface_habitable_logement>")
            . ($shImmeuble === null ? '' : "<surface_habitable_immeuble>$shImmeuble</surface_habitable_immeuble>");
        $carac = "<caracteristique_generale><enum_methode_application_dpe_log_id>$methode</enum_methode_application_dpe_log_id>$napp$surfaces</caracteristique_generale>";

        $xml = <<<XML
        <?xml version="1.0"?>
        <dpe>$admin<logement>$carac
          <installation_chauffage_collection><installation_chauffage>$typeInstallCh<generateur_chauffage_collection>
            <generateur_chauffage><donnee_entree><enum_type_energie_id>$energieCh</enum_type_energie_id></donnee_entree></generateur_chauffage>
          </generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
          <installation_ecs_collection><installation_ecs><generateur_ecs_collection>
            <generateur_ecs><donnee_entree><enum_type_energie_id>$energieEcs</enum_type_energie_id></donnee_entree></generateur_ecs>
          </generateur_ecs_collection></installation_ecs></installation_ecs_collection>
          <sortie><ef_conso>$efXml</ef_conso></sortie>
        </logement></dpe>
        XML;

        $doc = new DOMDocument();
        $doc->loadXML($xml);

        return $doc;
    }

    private function compute(DOMDocument $doc): void
    {
        $logement = $doc->getElementsByTagName('logement')->item(0);
        self::assertInstanceOf(DOMElement::class, $logement);

        (new CoutCalculator())->calculate($logement, new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        ));
    }

    private function cout(DOMDocument $doc, string $tag): float
    {
        $nodes = (new DOMXPath($doc))->query('//sortie/cout/' . $tag);
        if ($nodes === false || $nodes->length === 0) {
            self::fail("Balise absente : $tag");
        }

        return (float) $nodes->item(0)?->textContent;
    }

    // ── Barème selon la date d'établissement ──────────────────────────────

    public function testBaremeAvant2024EstCeluiDeLArrete2021(): void
    {
        // Fioul, annexe 7 de 2021 : 0,09142 €/kWh.
        $doc = $this->build(['conso_ch' => 5000], energieCh: 3, date: '2023-05-16');
        $this->compute($doc);

        self::assertEqualsWithDelta(0.09142 * 5000, $this->cout($doc, 'cout_ch'), self::TOL);
    }

    public function testBaremeDepuisJuillet2024EstCeluiDeLArrete2024(): void
    {
        // Fioul, arrêté du 25 mars 2024 : 0,14821 €/kWh.
        $doc = $this->build(['conso_ch' => 5000], energieCh: 3, date: '2026-01-05');
        $this->compute($doc);

        self::assertEqualsWithDelta(0.14821 * 5000, $this->cout($doc, 'cout_ch'), self::TOL);
    }

    public function testBasculeExactementAu1erJuillet2024(): void
    {
        $veille = $this->build(['conso_ch' => 1000], energieCh: 3, date: '2024-06-30');
        $jour   = $this->build(['conso_ch' => 1000], energieCh: 3, date: '2024-07-01');
        $this->compute($veille);
        $this->compute($jour);

        self::assertEqualsWithDelta(91.42, $this->cout($veille, 'cout_ch'), 1e-4);
        self::assertEqualsWithDelta(148.21, $this->cout($jour, 'cout_ch'), 1e-4);
    }

    public function testSansDateLeBaremeLePlusRecentSApplique(): void
    {
        $doc = $this->build(['conso_ch' => 1000], energieCh: 3);
        $this->compute($doc);

        self::assertEqualsWithDelta(148.21, $this->cout($doc, 'cout_ch'), 1e-4);
    }

    // ── La tranche porte sur le total de l'énergie, pas sur chaque usage ──

    public function testLaTrancheElectriquePorteSurLeTotalPasSurChaqueUsage(): void
    {
        // 4 000 kWh de chauffage + 4 000 kWh d'ECS = 8 000 kWh au total, donc
        // tranche 5 000-15 000 : 119 + 0,19726 × 8 000 = 1 697,08 €.
        // Tarifer chaque usage séparément placerait les deux dans la tranche
        // 2 500-5 000 et facturerait deux fois le terme fixe.
        $doc = $this->build(
            ['conso_ch' => 4000, 'conso_ecs' => 4000],
            energieCh: 1,
            energieEcs: 1,
            date: '2026-01-05',
        );
        $this->compute($doc);

        $total = 119.0 + 0.19726 * 8000.0;
        self::assertEqualsWithDelta($total / 2, $this->cout($doc, 'cout_ch'), 1e-6);
        self::assertEqualsWithDelta($total / 2, $this->cout($doc, 'cout_ecs'), 1e-6);
        self::assertEqualsWithDelta($total, $this->cout($doc, 'cout_5_usages'), 1e-6);
    }

    public function testChaqueEnergieEstTarifeeSurSonPropreTotal(): void
    {
        // Chauffage au gaz, ECS électrique : deux paniers distincts.
        $doc = $this->build(
            ['conso_ch' => 20000, 'conso_ecs' => 3000],
            energieCh: 2,
            energieEcs: 1,
            date: '2026-01-05',
        );
        $this->compute($doc);

        self::assertEqualsWithDelta(182.0 + 0.09488 * 20000.0, $this->cout($doc, 'cout_ch'), 1e-6);
        self::assertEqualsWithDelta(158.0 + 0.18949 * 3000.0, $this->cout($doc, 'cout_ecs'), 1e-6);
    }

    // ── La tranche s'apprécie par logement ────────────────────────────────

    public function testSurUnDpeImmeubleLaTrancheSAppricieParLogement(): void
    {
        // Méthode 6 = DPE immeuble collectif. 20 000 kWh sur 40 logements =
        // 500 kWh par ménage : première tranche (0,34721 €/kWh), et non la
        // tranche ≥ 15 000 du total du bâtiment.
        $doc = $this->build(['conso_eclairage' => 20000], date: '2026-01-05', nbLogements: 40, methode: 6);
        $this->compute($doc);

        self::assertEqualsWithDelta(0.34721 * 20000.0, $this->cout($doc, 'cout_eclairage'), 1e-6);
    }

    public function testHorsDpeImmeubleNombreAppartementEstIgnore(): void
    {
        // Méthode 10 = appartement généré à partir des données de l'immeuble :
        // la sortie ne décrit qu'un logement. `nombre_appartement` y renseigne
        // la taille du bâtiment et ne doit pas diviser la consommation.
        $doc = $this->build(['conso_eclairage' => 20000], date: '2026-01-05', nbLogements: 40, methode: 10);
        $this->compute($doc);

        self::assertEqualsWithDelta(78.0 + 0.20001 * 20000.0, $this->cout($doc, 'cout_eclairage'), 1e-6);
    }

    public function testSansNombreDeLogementsLaTrancheSAppliqueAuTotal(): void
    {
        $doc = $this->build(['conso_eclairage' => 20000], date: '2026-01-05', methode: 6);
        $this->compute($doc);

        // 78 + 0,20001 × 20 000, ramené au prix unitaire puis réappliqué.
        self::assertEqualsWithDelta(78.0 + 0.20001 * 20000.0, $this->cout($doc, 'cout_eclairage'), 1e-6);
    }

    public function testNombreDeLogementsAberrantEstRameneAUn(): void
    {
        $doc = $this->build(['conso_eclairage' => 20000], date: '2026-01-05', nbLogements: 0, methode: 6);
        $this->compute($doc);

        self::assertEqualsWithDelta(78.0 + 0.20001 * 20000.0, $this->cout($doc, 'cout_eclairage'), 1e-6);
    }

    /**
     * Une installation collective d'immeuble est desservie par un abonnement
     * unique : sa tranche s'apprécie sur le total du bâtiment, pas sur une
     * part par logement. Les usages individuels restent divisés.
     */
    public function testInstallationCollectiveEstUnAbonnementUnique(): void
    {
        $doc = $this->buildAvecInstallationCollective();
        $this->compute($doc);

        // Chauffage collectif électrique : 40 000 kWh sur un seul abonnement
        // → tranche ≥ 15 000.
        self::assertEqualsWithDelta(78.0 + 0.20001 * 40000.0, $this->cout($doc, 'cout_ch'), 1e-6);
        // Éclairage individuel : 20 000 kWh sur 40 logements → première tranche.
        self::assertEqualsWithDelta(0.34721 * 20000.0, $this->cout($doc, 'cout_eclairage'), 1e-6);
    }

    /**
     * Annexe tarifaire : « Abonnement collectif : la consommation […] à prendre
     * en compte pour la détermination du prix du kWh est celle de l'ensemble de
     * l'immeuble », estimée sur un DPE d'appartement par le rapport des
     * surfaces habitables.
     */
    public function testAppartementSurInstallationCollectiveEstTarifeSurLImmeuble(): void
    {
        // Appartement de 50 m² dans un immeuble de 1 000 m² : facteur 20.
        // 500 kWh d'ECS collective ⇒ 10 000 kWh à l'échelle de l'immeuble,
        // donc tranche 5 000-15 000 et non la première.
        $doc = $this->build(
            ['conso_ecs' => 500],
            energieEcs: 1,
            date: '2026-01-05',
            methode: 5,
            shLogement: 50.0,
            shImmeuble: 1000.0,
        );
        $this->marquerEcsCollective($doc);
        $this->compute($doc);

        $prixMoyen = (119.0 + 0.19726 * 10000.0) / 10000.0;
        self::assertEqualsWithDelta($prixMoyen * 500.0, $this->cout($doc, 'cout_ecs'), 1e-6);
    }

    public function testSansSurfaceImmeubleLAppartementResteTarifeSurLuiMeme(): void
    {
        $doc = $this->build(['conso_ecs' => 500], energieEcs: 1, date: '2026-01-05', methode: 5);
        $this->marquerEcsCollective($doc);
        $this->compute($doc);

        self::assertEqualsWithDelta(0.34721 * 500.0, $this->cout($doc, 'cout_ecs'), 1e-6);
    }

    private function marquerEcsCollective(DOMDocument $doc): void
    {
        $inst = $doc->getElementsByTagName('installation_ecs')->item(0);
        self::assertInstanceOf(DOMElement::class, $inst);
        $de = $doc->createElement('donnee_entree');
        $de->appendChild($doc->createElement('enum_type_installation_id', '2'));
        $inst->insertBefore($de, $inst->firstChild);
    }

    private function buildAvecInstallationCollective(): DOMDocument
    {
        return $this->build(
            ['conso_ch' => 40000, 'conso_eclairage' => 20000],
            energieCh: 1,
            date: '2026-01-05',
            nbLogements: 40,
            methode: 6,
            chCollectif: true,
        );
    }

    // ── Panier dépensier ──────────────────────────────────────────────────

    public function testLeScenarioDepensierAToutesSesPropresTranches(): void
    {
        $doc = $this->build(
            ['conso_ch' => 800, 'conso_ch_depensier' => 8000],
            energieCh: 1,
            date: '2026-01-05',
        );
        $this->compute($doc);

        self::assertEqualsWithDelta(0.34721 * 800.0, $this->cout($doc, 'cout_ch'), 1e-6);
        self::assertEqualsWithDelta(119.0 + 0.19726 * 8000.0, $this->cout($doc, 'cout_ch_depensier'), 1e-6);
    }

    // ── Agrégats ──────────────────────────────────────────────────────────

    public function testTotalAuxiliaireEtCout5Usages(): void
    {
        $doc = $this->build([
            'conso_ch' => 1000, 'conso_ecs' => 500, 'conso_eclairage' => 200,
            'conso_auxiliaire_generation_ch' => 50,
            'conso_auxiliaire_distribution_ch' => 60,
            'conso_auxiliaire_generation_ecs' => 30,
            'conso_auxiliaire_distribution_ecs' => 20,
            'conso_auxiliaire_ventilation' => 100,
        ], date: '2026-01-05');
        $this->compute($doc);

        $aux = $this->cout($doc, 'cout_auxiliaire_generation_ch')
            + $this->cout($doc, 'cout_auxiliaire_distribution_ch')
            + $this->cout($doc, 'cout_auxiliaire_generation_ecs')
            + $this->cout($doc, 'cout_auxiliaire_distribution_ecs')
            + $this->cout($doc, 'cout_auxiliaire_ventilation');

        self::assertEqualsWithDelta($aux, $this->cout($doc, 'cout_total_auxiliaire'), 1e-9);

        $attendu = $this->cout($doc, 'cout_ch') + $this->cout($doc, 'cout_ecs')
            + $this->cout($doc, 'cout_fr') + $aux + $this->cout($doc, 'cout_eclairage');
        self::assertEqualsWithDelta($attendu, $this->cout($doc, 'cout_5_usages'), 1e-9);
    }

    public function testConsommationNulleDonneUnCoutNul(): void
    {
        $doc = $this->build([], date: '2026-01-05');
        $this->compute($doc);

        self::assertSame(0.0, $this->cout($doc, 'cout_5_usages'));
        self::assertSame(0.0, $this->cout($doc, 'cout_ch'));
    }

    public function testElectriciteRenouvelableEstTarifeeCommeLElectricite(): void
    {
        // enum_type_energie_id 12 partage le panier de l'électricité : les
        // deux consommations doivent se cumuler dans la même tranche.
        $doc = $this->build(
            ['conso_ch' => 4000, 'conso_eclairage' => 4000],
            energieCh: 12,
            date: '2026-01-05',
        );
        $this->compute($doc);

        $total = 119.0 + 0.19726 * 8000.0;
        self::assertEqualsWithDelta($total / 2, $this->cout($doc, 'cout_ch'), 1e-6);
    }
}
