<?php

declare(strict_types=1);

namespace Tests\Unit;

use CalculDpePHP\Collectif\EcsInstallationMultiplicity;
use CalculDpePHP\Ecs\BesoinEcsCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Enveloppe\PlancherBas\UpbFinalCalculator;
use CalculDpePHP\Enveloppe\PontThermique\KCalculator;
use CalculDpePHP\Intermittence\IntermittenceCalculator;
use CalculDpePHP\Sortie\EpConsoCalculator;
use CalculDpePHP\Sortie\EmissionGesCalculator;
use CalculDpePHP\Sortie\SeuilsClasses;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConvergenceHistoriqueTest extends TestCase
{
    private function context(string $xml): CalculationContext
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return new CalculationContext($doc, new TableRepository(__DIR__ . '/../../resources/tables'), zoneClimatique: '1', classeAltitude: '1');
    }

    public static function comptages(): iterable
    {
        yield 'ligne avec comptage' => [183, 1, 0.95];
        yield 'ligne sans comptage' => [179, 1, 1.03];
        yield 'identifiant incohérent avec la régulation' => [183, 2, 1.01];
        yield 'identifiant inconnu' => [999, 1, 1.03];
    }

    #[DataProvider('comptages')]
    public function testLeComptageEstDecodeSeulementSiLesCriteresConcordent(int $tv, int $regulation, float $expected): void
    {
        $ctx = $this->context("<dpe><emetteur_chauffage><donnee_entree>
            <tv_intermittence_id>$tv</tv_intermittence_id><enum_type_chauffage_id>2</enum_type_chauffage_id>
            <enum_type_regulation_id>$regulation</enum_type_regulation_id><enum_equipement_intermittence_id>6</enum_equipement_intermittence_id>
            <enum_type_emission_distribution_id>37</enum_type_emission_distribution_id>
            </donnee_entree></emetteur_chauffage></dpe>");
        (new IntermittenceCalculator())->calculate($ctx->document->getElementsByTagName('emetteur_chauffage')->item(0), $ctx);
        self::assertEqualsWithDelta($expected, (float)$ctx->document->getElementsByTagName('i0')->item(0)->textContent, 1e-9);
    }

    public function testLeComptageDuPremierEmetteurNeContaminePasLeSuivant(): void
    {
        $entry = '<enum_type_chauffage_id>2</enum_type_chauffage_id><enum_type_regulation_id>1</enum_type_regulation_id><enum_equipement_intermittence_id>6</enum_equipement_intermittence_id><enum_type_emission_distribution_id>37</enum_type_emission_distribution_id>';
        $ctx = $this->context('<dpe><emetteur_chauffage><donnee_entree>' . $entry . '<tv_intermittence_id>183</tv_intermittence_id></donnee_entree></emetteur_chauffage><emetteur_chauffage><donnee_entree>' . $entry . '</donnee_entree></emetteur_chauffage></dpe>');
        foreach ($ctx->document->getElementsByTagName('emetteur_chauffage') as $node) { (new IntermittenceCalculator())->calculate($node, $ctx); }
        self::assertSame('0.95', $ctx->document->getElementsByTagName('i0')->item(0)->textContent);
        self::assertSame('1.03', $ctx->document->getElementsByTagName('i0')->item(1)->textContent);
    }

    public static function liaisons(): iterable
    {
        yield 'fraction explicite du format 7' => ['7.0.0', '<reference_1>mur</reference_1>', false, 'AUTRE', '<pourcentage_valeur_pont_thermique>0.5</pourcentage_valeur_pont_thermique>', 0.46];
        yield 'limite basse du lot' => ['6.3.0', '', false, 'PLANCHER_INTERMEDIAIRE_BAS', '', 0.43];
        yield 'limite haute du lot' => ['6.4.1', '', false, 'PLANCHER_INTERMEDIAIRE_HAUT', '', 0.43];
        yield 'fraction explicitement renseignée' => ['6.3.0', '', false, 'PLANCHER_INTERMEDIAIRE_BAS', '<pourcentage_valeur_pont_thermique>1</pourcentage_valeur_pont_thermique>', 0.86];
        yield 'description ambiguë conservée' => ['6.3.0', '', true, 'PLANCHER_INTERMEDIAIRE_BAS', '', 0.92];
        yield 'référence explicite prioritaire' => ['6.3.0', '<reference_1>mur</reference_1>', false, 'PLANCHER_INTERMEDIAIRE_BAS', '', 0.92];
        yield 'format moderne inchangé' => ['0.1.0', '', false, 'PLANCHER_INTERMEDIAIRE_BAS', '', 0.92];
        yield 'liaison non reconnue inchangée' => ['6.3.0', '', false, 'AUTRE', '', 0.92];
    }

    #[DataProvider('liaisons')]
    public function testLesLimitesDeLotHistoriquesSontAssocieesSansAmbiguite(string $version, string $ref, bool $duplicate, string $name, string $fraction, float $expected): void
    {
        $wall = '<mur><donnee_entree><description>Mur test</description><enum_type_isolation_id>1</enum_type_isolation_id></donnee_entree></mur>';
        $ctx = $this->context('<dpe version="' . $version . '"><logement><enveloppe>' . $wall . ($duplicate ? $wall : '')
            . '<pont_thermique><donnee_entree><description>Mur test - ' . $name . '</description>' . $ref . $fraction
            . '<enum_type_liaison_id>2</enum_type_liaison_id><enum_methode_saisie_pont_thermique_id>1</enum_methode_saisie_pont_thermique_id><tv_pont_thermique_id>30</tv_pont_thermique_id></donnee_entree></pont_thermique></enveloppe></logement></dpe>');
        (new KCalculator())->calculate($ctx->document->getElementsByTagName('pont_thermique')->item(0), $ctx);
        self::assertEqualsWithDelta($expected, (float)$ctx->document->getElementsByTagName('k')->item(0)->textContent, 1e-9);
    }

    public static function geometries(): iterable
    {
        yield 'ancien plancher au périmètre équivalent' => ['6.3.0', '', 0.339659442724458];
        yield 'Ue explicite prioritaire' => ['6.3.0', '<ue>0.25</ue>', 0.25];
        yield 'surface Ue explicite prioritaire' => ['6.3.0', '<surface_ue>150</surface_ue>', 0.219829721362229];
        yield 'format récent sans géométrie complète' => ['2', '', 0.823529411764706];
    }

    #[DataProvider('geometries')]
    public function testLePlancherHistoriqueUtiliseSaGeometrieEquivalente(string $version, string $extra, float $expected): void
    {
        $ctx = $this->context('<dpe version="' . $version . '"><plancher_bas><donnee_entree><enum_type_adjacence_id>3</enum_type_adjacence_id><calcul_ue>1</calcul_ue><surface_paroi_opaque>42</surface_paroi_opaque><perimetre_ue>15.38</perimetre_ue>' . $extra . '</donnee_entree><donnee_intermediaire><upb>0.823529411764706</upb></donnee_intermediaire></plancher_bas></dpe>');
        (new UpbFinalCalculator())->calculate($ctx->document->getElementsByTagName('plancher_bas')->item(0), $ctx);
        self::assertEqualsWithDelta($expected, (float)$ctx->document->getElementsByTagName('upb_final')->item(0)->textContent, 1e-9);
    }

    public static function visites(): iterable
    {
        yield 'relevé exhaustif' => [50.0, 50.0, false, 1.0, 2];
        yield 'surfaces incohérentes' => [50.0, 40.0, false, 2.0, 2];
        yield 'visite partielle' => [50.0, null, false, 2.0, 2];
        yield 'immeuble exhaustif de trois logements' => [25.0, 25.0, false, 3.0, 3];
        yield 'plusieurs installations' => [50.0, 50.0, true, 2.0, 2];
    }

    #[DataProvider('visites')]
    public function testLaMultipliciteEcsRespecteLeReleveExhaustif(float $s1, ?float $s2, bool $duplicate, float $expected, int $count): void
    {
        $install = '<installation_ecs><donnee_entree><enum_type_installation_id>1</enum_type_installation_id><enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id><surface_habitable>100</surface_habitable><rdim>1</rdim></donnee_entree></installation_ecs>';
        $visited = '<logement_visite><surface_habitable_logement>' . $s1 . '</surface_habitable_logement></logement_visite>';
        if ($s2 !== null) { $visited .= '<logement_visite><surface_habitable_logement>' . $s2 . '</surface_habitable_logement></logement_visite>'; }
        if ($count === 3) { $visited .= '<logement_visite><surface_habitable_logement>50</surface_habitable_logement></logement_visite>'; }
        $ctx = $this->context('<dpe><logement><caracteristique_generale><enum_methode_application_dpe_log_id>10</enum_methode_application_dpe_log_id><surface_habitable_logement>50</surface_habitable_logement><surface_habitable_immeuble>100</surface_habitable_immeuble><nombre_appartement>' . $count . '</nombre_appartement></caracteristique_generale><installation_ecs_collection>' . $install . ($duplicate ? $install : '') . '</installation_ecs_collection></logement><dpe_immeuble><logement_visite_collection>' . $visited . '</logement_visite_collection></dpe_immeuble></dpe>');
        $node = $ctx->document->getElementsByTagName('installation_ecs')->item(0);
        self::assertSame($expected, EcsInstallationMultiplicity::sampledOrNull($node, new NodeAccessor($ctx->document)));
        $ctx->set('apport.nadeq', 4.0);
        (new BesoinEcsCalculator())->calculate($ctx->document->getElementsByTagName('logement')->item(0), $ctx);
        $xp = new DOMXPath($ctx->document);
        $total = (float)$xp->evaluate('string(//sortie/apport_et_besoin/besoin_ecs)');
        $installNeed = (float)$xp->evaluate('string(//installation_ecs/donnee_intermediaire/besoin_ecs)');
        self::assertGreaterThan(0.0, $total);
        self::assertEqualsWithDelta($total / $expected, $installNeed, 1e-6);
    }

    public function testLesSeuilsPetitesSurfacesRespectentLaDateHistorique(): void
    {
        foreach (['2022-04-16' => 'G', '2024-06-30' => 'G', '2024-07-01' => 'F'] as $date => $expected) {
            $ctx = $this->context('<dpe><administratif><date_etablissement_dpe>' . $date . '</date_etablissement_dpe></administratif></dpe>');
            self::assertSame($expected, SeuilsClasses::energie(428, 30, 1, 1, $ctx));
        }
    }

    public function testLeFormatHistoriqueConserveLesDecimalesSansChangerLeClassement(): void
    {
        foreach (['6.3.0' => 4.43394, '7.0.0' => 4.43394, '2' => 4.0] as $version => $expected) {
            $ctx = $this->context('<dpe version="' . $version . '"><logement><caracteristique_generale><surface_habitable_logement>100</surface_habitable_logement></caracteristique_generale><sortie><ef_conso><conso_eclairage>192.78</conso_eclairage></ef_conso></sortie></logement></dpe>');
            $logement = $ctx->document->getElementsByTagName('logement')->item(0);
            (new EpConsoCalculator())->calculate($logement, $ctx);
            (new EmissionGesCalculator())->calculate($logement, $ctx);
            self::assertEqualsWithDelta($expected, (float)$ctx->document->getElementsByTagName('ep_conso_5_usages_m2')->item(0)->textContent, 1e-8);
            self::assertSame('A', $ctx->document->getElementsByTagName('classe_bilan_dpe')->item(0)->textContent);
            self::assertSame((string)$version === '2' ? '0' : '0.13', $ctx->document->getElementsByTagName('emission_ges_5_usages_m2')->item(0)->textContent);
        }
    }

    public function testUneProductionInstantaneeNeSubitPasLesPertesDuVolumeResiduel(): void
    {
        foreach ([1, 2] as $stockage) {
            $ctx = $this->context('<dpe version="6.3.0"><logement><installation_ecs><donnee_entree><enum_type_installation_id>2</enum_type_installation_id></donnee_entree><donnee_intermediaire><besoin_ecs>10000</besoin_ecs><rendement_distribution>0.52</rendement_distribution></donnee_intermediaire><generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_stockage_ecs_id>' . $stockage . '</enum_type_stockage_ecs_id><volume_stockage>1200</volume_stockage><enum_type_energie_id>2</enum_type_energie_id><enum_type_generateur_ecs_id>49</enum_type_generateur_ecs_id><enum_usage_generateur_id>2</enum_usage_generateur_id></donnee_entree></generateur_ecs></generateur_ecs_collection></installation_ecs></logement></dpe>');
            $node = $ctx->document->getElementsByTagName('generateur_ecs')->item(0);
            (new \CalculDpePHP\Ecs\Rendement\StockageCalculator())->calculate($node, $ctx);
            (new \CalculDpePHP\Ecs\Rendement\CombustionCalculator())->calculate($node, $ctx);
            $qgw = $ctx->get(\CalculDpePHP\Ecs\Rendement\StockageCalculator::qgwKey($node));
            if ($stockage === 1) {
                self::assertSame(0.0, $qgw);
                self::assertSame(0, $node->getElementsByTagName('rendement_stockage')->length);
                self::assertSame('24000', $node->getElementsByTagName('pn')->item(0)->textContent);
            } else {
                self::assertGreaterThan(0, $qgw);
                self::assertSame(1, $node->getElementsByTagName('rendement_stockage')->length);
                self::assertSame('18000', $node->getElementsByTagName('pn')->item(0)->textContent);
            }
            // La saisie source reste disponible pour diagnostiquer la contradiction.
            self::assertSame('1200', $node->getElementsByTagName('volume_stockage')->item(0)->textContent);
        }
    }
    public function testLaFractionDuPontNestAppliqueeQuUneFoisDansLeBilan(): void
    {
        foreach (['7.0.0', '2'] as $version) {
            $ctx = $this->context('<dpe version="' . $version . '"><logement><enveloppe>
                <pont_thermique_collection><pont_thermique><donnee_entree>
                <enum_type_liaison_id>2</enum_type_liaison_id><tv_pont_thermique_id>30</tv_pont_thermique_id>
                <enum_methode_saisie_pont_thermique_id>1</enum_methode_saisie_pont_thermique_id>
                <l>10</l><pourcentage_valeur_pont_thermique>0.5</pourcentage_valeur_pont_thermique>
                </donnee_entree></pont_thermique></pont_thermique_collection></enveloppe></logement></dpe>');
            (new KCalculator())->calculate($ctx->document->getElementsByTagName('pont_thermique')->item(0), $ctx);
            (new \CalculDpePHP\Enveloppe\EnveloppeAggregator())->calculate($ctx->document->getElementsByTagName('logement')->item(0), $ctx);
            self::assertEqualsWithDelta(4.6, $ctx->get('enveloppe.dp_pont_thermique'), 1e-9);
            self::assertEqualsWithDelta($version === '2' ? 0.92 : 0.46, (float)$ctx->document->getElementsByTagName('k')->item(0)->textContent, 1e-9);
        }
    }

    public function testLaPuissanceDirecteSuitLaMultipliciteDeLInstallation(): void
    {
        foreach ([[1, 1, 55000.0], [1, 8, 18000.0], [4, 1, 18000.0]] as [$method, $rdim, $expected]) {
            $ctx = $this->context('<dpe><logement><caracteristique_generale>
                <enum_methode_application_dpe_log_id>6</enum_methode_application_dpe_log_id>
                <nombre_appartement>8</nombre_appartement><surface_habitable_immeuble>504.29</surface_habitable_immeuble>
                </caracteristique_generale><installation_chauffage_collection><installation_chauffage><donnee_entree>
                <enum_type_installation_id>1</enum_type_installation_id><surface_chauffee>504.29</surface_chauffee>
                <enum_methode_calcul_conso_id>' . $method . '</enum_methode_calcul_conso_id><rdim>' . $rdim . '</rdim>
                </donnee_entree><generateur_chauffage_collection><generateur_chauffage><donnee_entree>
                <enum_type_generateur_ch_id>92</enum_type_generateur_ch_id><enum_type_energie_id>2</enum_type_energie_id>
                <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id><tv_generateur_combustion_id>8</tv_generateur_combustion_id>
                <enum_usage_generateur_id>1</enum_usage_generateur_id><presence_ventouse>1</presence_ventouse>
                </donnee_entree></generateur_chauffage></generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection></logement></dpe>');
            $ctx->set('enveloppe.dp_parois', 1324.2445);
            (new \CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator())->calculate($ctx->document->getElementsByTagName('generateur_chauffage')->item(0), $ctx);
            self::assertSame($expected, (float)$ctx->document->getElementsByTagName('pn')->item(0)->textContent);
        }
    }

    public function testLeBesoinEcsGenereConserveLaMultipliciteDirecteDuBatiment(): void
    {
        foreach ([1, 8] as $rdim) {
            $ctx = $this->context('<dpe><logement><caracteristique_generale>
                <enum_methode_application_dpe_log_id>10</enum_methode_application_dpe_log_id>
                <nombre_appartement>8</nombre_appartement><surface_habitable_immeuble>504</surface_habitable_immeuble>
                <surface_habitable_logement>60</surface_habitable_logement></caracteristique_generale>
                <installation_ecs_collection><installation_ecs><donnee_entree><enum_type_installation_id>1</enum_type_installation_id>
                <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id><surface_habitable>504</surface_habitable>
                <rdim>' . $rdim . '</rdim></donnee_entree></installation_ecs></installation_ecs_collection></logement></dpe>');
            $ctx->set('apport.nadeq', 12.0);
            (new BesoinEcsCalculator())->calculate($ctx->document->getElementsByTagName('logement')->item(0), $ctx);
            $xp = new DOMXPath($ctx->document);
            $total = (float)$xp->evaluate('string(//sortie/apport_et_besoin/besoin_ecs)');
            self::assertGreaterThan(0.0, $total);
            self::assertEqualsWithDelta($total / $rdim, (float)$xp->evaluate('string(//installation_ecs/donnee_intermediaire/besoin_ecs)'), 1e-6);
        }
    }

}
