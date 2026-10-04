<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReferenceDefectsTest extends TestCase
{
    private const COUT = 'dpe/logement/sortie/cout/';
    private const EF = 'dpe/logement/sortie/ef_conso/';

    /** @param array<string, string> $extra */
    private function reference(array $extra = []): array
    {
        return array_merge([
            self::EF . 'conso_ch' => '10000',
            self::EF . 'conso_ch_depensier' => '13000',
            self::COUT . 'cout_ch' => '900',
            self::COUT . 'cout_ch_depensier' => '900',
        ], $extra);
    }

    /**
     * Un total d'auxiliaires qui contredit ses propres addendes est un défaut
     * de la référence, et le contrôle vaut pour les quatre blocs qui publient
     * ces totaux — pas seulement la consommation finale.
     *
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function blocsAuxiliairesProvider(): iterable
    {
        yield 'energie finale' => [
            'dpe/logement/sortie/ef_conso/', 'conso_', 'conso_totale_auxiliaire',
        ];
        yield 'cout' => [
            'dpe/logement/sortie/cout/', 'cout_', 'cout_total_auxiliaire',
        ];
        yield 'emissions de GES' => [
            'dpe/logement/sortie/emission_ges/', 'emission_ges_', 'emission_ges_totale_auxiliaire',
        ];
        yield 'energie primaire' => [
            'dpe/logement/sortie/ep_conso/', 'ep_conso_', 'ep_conso_totale_auxiliaire',
        ];
    }

    #[DataProvider('blocsAuxiliairesProvider')]
    public function testTotalAuxiliaireContredisantSesPostesEstSignale(
        string $prefixe,
        string $prefixePoste,
        string $nomTotal,
    ): void {
        // Tous les postes nuls sauf la ventilation, et un total inférieur à
        // cette seule ventilation : le total contredit sa propre somme.
        $suspects = ReferenceDefects::detect($this->reference([
            $prefixe . $prefixePoste . 'auxiliaire_generation_ch' => '0',
            $prefixe . $prefixePoste . 'auxiliaire_distribution_ch' => '0',
            $prefixe . $prefixePoste . 'auxiliaire_ventilation' => '3016.2',
            $prefixe . $nomTotal => '1169.6',
        ]));

        self::assertArrayHasKey($nomTotal, $suspects);
        self::assertStringContainsString('3016', $suspects[$nomTotal]);
    }

    #[DataProvider('blocsAuxiliairesProvider')]
    public function testTotalAuxiliaireCoherentNestPasSignale(
        string $prefixe,
        string $prefixePoste,
        string $nomTotal,
    ): void {
        $suspects = ReferenceDefects::detect($this->reference([
            $prefixe . $prefixePoste . 'auxiliaire_generation_ch' => '150',
            $prefixe . $prefixePoste . 'auxiliaire_distribution_ch' => '1000',
            $prefixe . $prefixePoste . 'auxiliaire_ventilation' => '3016.2',
            $prefixe . $nomTotal => '4166.2',
        ]));

        self::assertArrayNotHasKey($nomTotal, $suspects);
    }

    public function testCoutDepensierRecopieEstSignale(): void
    {
        // Le schéma décrit cout_ch_depensier comme le coût du scénario
        // dépensier : deux consommations différentes ne peuvent pas coûter
        // exactement la même chose.
        $suspects = ReferenceDefects::detect($this->reference());

        self::assertArrayHasKey('cout_ch_depensier', $suspects);
        self::assertStringContainsString('10000', $suspects['cout_ch_depensier']);
        self::assertStringContainsString('13000', $suspects['cout_ch_depensier']);
    }

    public function testCoutDepensierDistinctNestPasSignale(): void
    {
        $suspects = ReferenceDefects::detect($this->reference([self::COUT . 'cout_ch_depensier' => '1150']));

        self::assertSame([], $suspects);
    }

    public function testConsommationsIdentiquesNeSontPasUnDefaut(): void
    {
        // Même consommation, même coût : c'est cohérent, pas suspect.
        $suspects = ReferenceDefects::detect($this->reference([self::EF . 'conso_ch_depensier' => '10000']));

        self::assertSame([], $suspects);
    }

    public function testCoutNulNestPasSignale(): void
    {
        $suspects = ReferenceDefects::detect($this->reference([
            self::COUT . 'cout_ch' => '0',
            self::COUT . 'cout_ch_depensier' => '0',
        ]));

        self::assertSame([], $suspects);
    }

    public function testBalisesAbsentesNeDeclenchentRien(): void
    {
        self::assertSame([], ReferenceDefects::detect([]));
    }

    /**
     * Le schéma rend `confort_ete` facultatif mais impose
     * `protection_solaire_exterieure` dès que le bloc est là : un bloc vide le
     * viole, et le contenu que nous produisons n'est pas de trop.
     */
    public function testBlocConfortEteVideEstSignale(): void
    {
        $suspects = ReferenceDefects::detect(['dpe/logement/sortie/confort_ete' => '']);

        self::assertArrayHasKey('protection_solaire_exterieure', $suspects);
        self::assertArrayHasKey('enum_indicateur_confort_ete_id', $suspects);
        self::assertArrayHasKey('inertie_lourde', $suspects);
    }

    public function testBlocConfortEteRenseigneNestPasSignale(): void
    {
        // Le bloc est un conteneur : renseigné, il n'apparaît pas comme feuille
        // vide dans les valeurs extraites.
        $suspects = ReferenceDefects::detect([
            'dpe/logement/sortie/confort_ete/protection_solaire_exterieure' => '1',
        ]);

        self::assertSame([], $suspects);
    }

    public function testBlocConfortEteAbsentNestPasSignale(): void
    {
        self::assertSame([], ReferenceDefects::detect(['dpe/logement/sortie/ep_conso/classe_bilan_dpe' => 'D']));
    }

    /**
     * Échantillonnage §17 : la référence publie plusieurs installations ECS
     * aux entrées identiques et leur donne des rendements de stockage
     * différents. Rien dans le XML ne les distingue — le rendement et ses
     * consommations ECS dépendantes ne sont pas reproductibles, et une règle
     * qui y parviendrait devinerait.
     */
    public function testInstallationsEcsIdentiquesAuxRendementsDifferentsSontSignalees(): void
    {
        $suspects = ReferenceDefects::detect([], $this->docEcs(['0.79', '0.62', '0.78']));

        self::assertSame(
            [
                'rendement_stockage@1', 'conso_ecs@1', 'conso_ecs_depensier@1',
                'rendement_stockage@2', 'conso_ecs@2', 'conso_ecs_depensier@2',
                'rendement_stockage@3', 'conso_ecs@3', 'conso_ecs_depensier@3',
            ],
            array_keys($suspects),
        );
        self::assertStringContainsString('3 rendements de stockage différents', $suspects['rendement_stockage@1']);
        self::assertSame($suspects['rendement_stockage@1'], $suspects['conso_ecs_depensier@1']);
    }

    public function testInstallationsEcsIdentiquesAuMemeRendementNeSontPasSignalees(): void
    {
        self::assertSame([], ReferenceDefects::detect([], $this->docEcs(['0.79', '0.79'])));
    }

    public function testInstallationsEcsDifferentesNeSontPasSignalees(): void
    {
        // Volumes de stockage distincts : deux rendements différents sont
        // parfaitement explicables.
        self::assertSame([], ReferenceDefects::detect([], $this->docEcs(['0.79', '0.62'], ['100', '200'])));
    }

    public function testSansDocumentDeReferenceLaRegleNeSApplique(): void
    {
        self::assertSame([], ReferenceDefects::detect([]));
    }

    public function testBesoinsDepensiersReglementairementImpossiblesSontSignales(): void
    {
        $apport = 'dpe/logement/sortie/apport_et_besoin/';
        $suspects = ReferenceDefects::detect([
            $apport . 'besoin_ch' => '50050.58',
            $apport . 'besoin_ch_depensier' => '7116',
            $apport . 'besoin_ecs' => '17714.68',
            $apport . 'besoin_ecs_depensier' => '8800',
        ]);

        self::assertArrayHasKey('besoin_ch_depensier', $suspects);
        self::assertArrayHasKey('besoin_ecs_depensier', $suspects);
        self::assertStringContainsString('79/56', $suspects['besoin_ecs_depensier']);
    }

    public function testBesoinsDepensiersCoherentsNeSontPasSignales(): void
    {
        $apport = 'dpe/logement/sortie/apport_et_besoin/';

        self::assertSame([], ReferenceDefects::detect([
            $apport . 'besoin_ch' => '10000',
            $apport . 'besoin_ch_depensier' => '13000',
            $apport . 'besoin_ecs' => '5600',
            $apport . 'besoin_ecs_depensier' => '7900',
        ]));
    }

    public function testTotalAuxiliaireDifferentDeSesPostesEstSignale(): void
    {
        $suspects = ReferenceDefects::detect([
            self::EF . 'conso_auxiliaire_generation_ch' => '0',
            self::EF . 'conso_auxiliaire_ventilation' => '316.8',
            self::EF . 'conso_totale_auxiliaire' => '103.1',
        ]);

        self::assertArrayHasKey('conso_totale_auxiliaire', $suspects);
        self::assertStringContainsString('316.8', $suspects['conso_totale_auxiliaire']);
    }

    public function testRendementStockageDepensierSerialiseEstSignale(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement><installation_ecs_collection><installation_ecs>
  <donnee_intermediaire>
    <rendement_distribution>0.93</rendement_distribution>
    <besoin_ecs>1265.34</besoin_ecs><besoin_ecs_depensier>1785.03</besoin_ecs_depensier>
    <conso_ecs>2069.42</conso_ecs><conso_ecs_depensier>2628.23</conso_ecs_depensier>
  </donnee_intermediaire>
  <generateur_ecs_collection><generateur_ecs>
    <donnee_entree><enum_type_generateur_ecs_id>70</enum_type_generateur_ecs_id></donnee_entree>
    <donnee_intermediaire><rendement_stockage>0.7303</rendement_stockage></donnee_intermediaire>
  </generateur_ecs></generateur_ecs_collection>
</installation_ecs></installation_ecs_collection></logement></dpe>
XML);

        $suspects = ReferenceDefects::detect([], $doc);

        self::assertArrayHasKey('rendement_stockage', $suspects);
        self::assertStringContainsString('dépensier', $suspects['rendement_stockage']);
        self::assertStringContainsString('0.6575', $suspects['rendement_stockage']);
    }

    public function testQp0EnKilowattsMalgreLeXsdEnWattsEstSignale(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement><installation_chauffage><generateur_chauffage_collection><generateur_chauffage>
  <donnee_intermediaire><pn>55000</pn><qp0>0.55</qp0></donnee_intermediaire>
</generateur_chauffage></generateur_chauffage_collection></installation_chauffage></logement></dpe>
XML);

        $suspects = ReferenceDefects::detect([], $doc);

        self::assertArrayHasKey('qp0', $suspects);
        self::assertStringContainsString('550 W', $suspects['qp0']);
    }

    public function testQp0DejaEnWattsNestPasSignale(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement><installation_chauffage><generateur_chauffage_collection><generateur_chauffage>
  <donnee_intermediaire><pn>55000</pn><qp0>550</qp0></donnee_intermediaire>
</generateur_chauffage></generateur_chauffage_collection></installation_chauffage></logement></dpe>
XML);

        self::assertSame([], ReferenceDefects::detect([], $doc));
    }

    /**
     * §5 p.41 lie Caux_vent à Pventmoy : une puissance nulle ne peut pas
     * consommer. Le constat se fait sur le fichier de référence seul.
     */
    public function testVentilationSansPuissanceMaisConsommatriceEstSignalee(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(
            '<dpe><logement><ventilation_collection><ventilation><donnee_intermediaire>'
            . '<pvent_moy>0</pvent_moy><conso_auxiliaire_ventilation>1</conso_auxiliaire_ventilation>'
            . '</donnee_intermediaire></ventilation></ventilation_collection></logement></dpe>'
        );

        $suspects = ReferenceDefects::detect(
            [self::EF . 'conso_auxiliaire_ventilation' => '131.4'],
            $doc,
        );

        self::assertArrayHasKey('pvent_moy', $suspects);
        self::assertArrayHasKey('conso_auxiliaire_ventilation', $suspects);
        self::assertStringContainsString('8760', $suspects['pvent_moy']);
    }

    public function testVentilationSansPuissanceEtSansConsommationNestPasSignalee(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(
            '<dpe><logement><ventilation_collection><ventilation><donnee_intermediaire>'
            . '<pvent_moy>0</pvent_moy><conso_auxiliaire_ventilation>0</conso_auxiliaire_ventilation>'
            . '</donnee_intermediaire></ventilation></ventilation_collection></logement></dpe>'
        );

        $suspects = ReferenceDefects::detect(
            [self::EF . 'conso_auxiliaire_ventilation' => '0'],
            $doc,
        );

        self::assertArrayNotHasKey('pvent_moy', $suspects);
    }

    public function testVentilationDePuissanceNonNulleNestPasSignalee(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(
            '<dpe><logement><ventilation_collection><ventilation><donnee_intermediaire>'
            . '<pvent_moy>65</pvent_moy><conso_auxiliaire_ventilation>569.4</conso_auxiliaire_ventilation>'
            . '</donnee_intermediaire></ventilation></ventilation_collection></logement></dpe>'
        );

        $suspects = ReferenceDefects::detect(
            [self::EF . 'conso_auxiliaire_ventilation' => '569.4'],
            $doc,
        );

        self::assertArrayNotHasKey('pvent_moy', $suspects);
    }

    public function testStockageIntegreSerialiseCommeSepareEstSignale(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement><installation_ecs><generateur_ecs_collection><generateur_ecs>
  <donnee_entree><enum_type_stockage_ecs_id>3</enum_type_stockage_ecs_id></donnee_entree>
  <donnee_intermediaire><rendement_generation>0.83</rendement_generation><rendement_stockage>1</rendement_stockage></donnee_intermediaire>
</generateur_ecs></generateur_ecs_collection></installation_ecs></logement></dpe>
XML);

        $suspects = ReferenceDefects::detect([], $doc);

        self::assertArrayHasKey('rendement_generation', $suspects);
        self::assertArrayHasKey('rendement_stockage', $suspects);
        self::assertArrayHasKey('rendement_generation_stockage', $suspects);
    }

    public function testRepartitionEcsAppartementGenereNonReproductibleEstSignalee(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement>
  <caracteristique_generale>
    <enum_methode_application_dpe_log_id>33</enum_methode_application_dpe_log_id>
    <surface_habitable_immeuble>1203</surface_habitable_immeuble>
  </caracteristique_generale>
  <installation_ecs_collection><installation_ecs>
    <donnee_entree>
      <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id>
      <enum_type_installation_id>1</enum_type_installation_id>
      <surface_habitable>2.5</surface_habitable><rdim>20</rdim>
      <cle_repartition_ecs>0.0415628</cle_repartition_ecs>
    </donnee_entree>
    <donnee_intermediaire><conso_ecs>1604.57</conso_ecs><conso_ecs_depensier>2168.73</conso_ecs_depensier></donnee_intermediaire>
  </installation_ecs></installation_ecs_collection>
</logement></dpe>
XML);

        $suspects = ReferenceDefects::detect([
            self::EF . 'conso_ecs' => '1520.9',
            self::EF . 'conso_ecs_depensier' => '2144.5',
        ], $doc);

        self::assertArrayHasKey(self::EF . 'conso_ecs', $suspects);
        self::assertArrayHasKey(self::EF . 'conso_ecs_depensier', $suspects);
        self::assertStringContainsString('1333.8', $suspects[self::EF . 'conso_ecs']);
    }

    public function testRepartitionEcsCoherenteNestPasSignalee(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement>
  <caracteristique_generale>
    <enum_methode_application_dpe_log_id>33</enum_methode_application_dpe_log_id>
    <surface_habitable_immeuble>1203</surface_habitable_immeuble>
  </caracteristique_generale>
  <installation_ecs_collection><installation_ecs>
    <donnee_entree>
      <enum_methode_calcul_conso_id>1</enum_methode_calcul_conso_id>
      <enum_type_installation_id>1</enum_type_installation_id>
      <surface_habitable>1203</surface_habitable><rdim>20</rdim>
      <cle_repartition_ecs>0.0415628</cle_repartition_ecs>
    </donnee_entree>
    <donnee_intermediaire><conso_ecs>1604.57</conso_ecs><conso_ecs_depensier>2168.73</conso_ecs_depensier></donnee_intermediaire>
  </installation_ecs></installation_ecs_collection>
</logement></dpe>
XML);

        self::assertSame([], ReferenceDefects::detect([
            self::EF . 'conso_ecs' => '1520.9',
            self::EF . 'conso_ecs_depensier' => '2144.5',
        ], $doc));
    }

    /**
     * @param list<string> $rendements
     * @param list<string>|null $volumes
     */
    private function docEcs(array $rendements, ?array $volumes = null): DOMDocument
    {
        $installations = '';
        foreach ($rendements as $i => $rs) {
            $volume = $volumes[$i] ?? '150';
            $installations .= <<<XML
            <installation_ecs>
              <donnee_entree><reference>ref-$i</reference><surface_habitable>108</surface_habitable></donnee_entree>
              <generateur_ecs_collection><generateur_ecs>
                <donnee_entree><enum_type_generateur_ecs_id>70</enum_type_generateur_ecs_id><volume_stockage>$volume</volume_stockage></donnee_entree>
                <donnee_intermediaire><rendement_stockage>$rs</rendement_stockage></donnee_intermediaire>
              </generateur_ecs></generateur_ecs_collection>
            </installation_ecs>
            XML;
        }

        $doc = new DOMDocument();
        $doc->loadXML("<dpe><logement><installation_ecs_collection>$installations</installation_ecs_collection></logement></dpe>");

        return $doc;
    }

    public function testTousLesPostesConcernesSontCouverts(): void
    {
        $suspects = ReferenceDefects::detect([
            self::EF . 'conso_ecs' => '5000', self::EF . 'conso_ecs_depensier' => '7000',
            self::COUT . 'cout_ecs' => '400', self::COUT . 'cout_ecs_depensier' => '400',
            self::EF . 'conso_auxiliaire_generation_ch' => '100',
            self::EF . 'conso_auxiliaire_generation_ch_depensier' => '150',
            self::COUT . 'cout_auxiliaire_generation_ch' => '30',
            self::COUT . 'cout_auxiliaire_generation_ch_depensier' => '30',
        ]);

        self::assertSame(
            ['cout_ecs_depensier', 'cout_auxiliaire_generation_ch_depensier'],
            array_keys($suspects),
        );
    }

    /**
     * Le XSD définit `k` comme la valeur du pont thermique en W/(m·K) : la
     * déperdition totale vaut Σ k × l. Quand c'est la somme des `k` eux-mêmes
     * qui reproduit `deperdition_pont_thermique`, les valeurs publiées portent
     * déjà la longueur.
     */
    public function testPontThermiqueKDejaMultiplieParLaLongueurEstSignale(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement>
  <deperdition><deperdition_pont_thermique>3.0</deperdition_pont_thermique></deperdition>
  <enveloppe><pont_thermique_collection>
    <pont_thermique><donnee_entree><l>2</l></donnee_entree>
      <donnee_intermediaire><k>2.0</k></donnee_intermediaire></pont_thermique>
    <pont_thermique><donnee_entree><l>5</l></donnee_entree>
      <donnee_intermediaire><k>1.0</k></donnee_intermediaire></pont_thermique>
  </pont_thermique_collection></enveloppe>
</logement></dpe>
XML);

        $suspects = ReferenceDefects::detect($this->reference(), $doc);

        self::assertArrayHasKey('k', $suspects);
        self::assertStringContainsString('W/(m·K)', $suspects['k']);
    }

    /** Un fichier où Σ k × l reproduit le total n'est pas signalé. */
    public function testPontThermiqueKCoefficientNestPasSignale(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<dpe><logement>
  <deperdition><deperdition_pont_thermique>9.0</deperdition_pont_thermique></deperdition>
  <enveloppe><pont_thermique_collection>
    <pont_thermique><donnee_entree><l>2</l></donnee_entree>
      <donnee_intermediaire><k>2.0</k></donnee_intermediaire></pont_thermique>
    <pont_thermique><donnee_entree><l>5</l></donnee_entree>
      <donnee_intermediaire><k>1.0</k></donnee_intermediaire></pont_thermique>
  </pont_thermique_collection></enveloppe>
</logement></dpe>
XML);

        self::assertArrayNotHasKey('k', ReferenceDefects::detect($this->reference(), $doc));
    }
    public function test_signale_le_bouclage_collectif_sans_auxiliaire_depuis_la_reference_seule(): void
    {
        $xml = '<dpe><logement><caracteristique_generale><enum_methode_application_dpe_log_id>34</enum_methode_application_dpe_log_id></caracteristique_generale><installation_ecs><donnee_entree><enum_type_installation_id>2</enum_type_installation_id><enum_bouclage_reseau_ecs_id>2</enum_bouclage_reseau_ecs_id><cle_repartition_ecs>0.02</cle_repartition_ecs><surface_habitable>30</surface_habitable></donnee_entree><donnee_intermediaire><besoin_ecs>1000</besoin_ecs></donnee_intermediaire></installation_ecs></logement></dpe>';
        $path = self::EF . 'conso_auxiliaire_distribution_ecs';
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        self::assertArrayHasKey($path, ReferenceDefects::detect([$path => '0.0'], $doc));
        self::assertArrayNotHasKey($path, ReferenceDefects::detect([$path => '4.2'], $doc));
        self::assertArrayNotHasKey($path, ReferenceDefects::detect([], $doc));
        foreach ([
            ['<enum_bouclage_reseau_ecs_id>2', '<enum_bouclage_reseau_ecs_id>1'],
            ['<enum_type_installation_id>2', '<enum_type_installation_id>1'],
            ['<cle_repartition_ecs>0.02', '<cle_repartition_ecs>0'],
            ['<besoin_ecs>1000', '<besoin_ecs>0'],
            ['<surface_habitable>30', '<surface_habitable>0'],
            ['<enum_methode_application_dpe_log_id>34', '<enum_methode_application_dpe_log_id>2'],
            ['</donnee_entree>', '<enum_type_installation_solaire_id>2</enum_type_installation_solaire_id></donnee_entree>'],
        ] as [$from, $to]) {
            $doc->loadXML(str_replace($from, $to, $xml));
            self::assertArrayNotHasKey($path, ReferenceDefects::detect([$path => '0'], $doc));
        }
    }
    private function referenceAvecPertesBallon(float $diviseur = 18, string $version = '2', int $type = 70): DOMDocument
    {
        // Entrées fictives : le ballon de 100 L appartient à chaque appartement.
        $cr = [68 => 0.39, 69 => 0.32, 70 => 0.27, 71 => 0.25][$type];
        $k = $type === 71 ? 1.08 : 1.0;
        $qgw = 8592 * 45 / 24 * 100 * $cr / 1000;
        $conso = (1000 / 0.93 + $qgw / $diviseur) / $k;
        $consoDep = (1400 / 0.93 + $qgw / $diviseur) / $k;
        $becs = $version === '0.1.0' ? 1000000 : 1000;
        $becsDep = $version === '0.1.0' ? 1400000 : 1400;
        $doc = new DOMDocument();
        $doc->loadXML(<<<XML
<dpe version="$version"><logement>
<caracteristique_generale><enum_methode_application_dpe_log_id>10</enum_methode_application_dpe_log_id><nombre_appartement>18</nombre_appartement><surface_habitable_immeuble>540</surface_habitable_immeuble></caracteristique_generale>
<installation_ecs_collection><installation_ecs>
<donnee_entree><enum_type_installation_id>1</enum_type_installation_id><enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id><ratio_virtualisation>1</ratio_virtualisation><rdim>1</rdim><nombre_logement>1</nombre_logement><surface_habitable>540</surface_habitable></donnee_entree>
<donnee_intermediaire><rendement_distribution>0.93</rendement_distribution><besoin_ecs>$becs</besoin_ecs><besoin_ecs_depensier>$becsDep</besoin_ecs_depensier><conso_ecs>$conso</conso_ecs><conso_ecs_depensier>$consoDep</conso_ecs_depensier></donnee_intermediaire>
<generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_energie_id>1</enum_type_energie_id><enum_type_generateur_ecs_id>$type</enum_type_generateur_ecs_id><volume_stockage>100</volume_stockage></donnee_entree><donnee_intermediaire><rendement_generation>1</rendement_generation><ratio_besoin_ecs>1</ratio_besoin_ecs></donnee_intermediaire></generateur_ecs></generateur_ecs_collection>
</installation_ecs></installation_ecs_collection></logement></dpe>
XML);
        return $doc;
    }

    public static function formatsEtBallonsPourPertesDivisees(): iterable
    {
        foreach (['2', '0.1.0'] as $format) {
            foreach ([68, 69, 70, 71] as $type) {
                yield $format . '/' . $type => [$format, $type];
            }
        }
    }

    #[DataProvider('formatsEtBallonsPourPertesDivisees')]
    public function test_les_pertes_individuelles_divisees_par_le_nombre_de_logements_sont_signalees(string $format, int $type): void
    {
        $doc = $this->referenceAvecPertesBallon(18, $format, $type);
        $suspects = ReferenceDefects::detect([], $doc);
        self::assertArrayHasKey('conso_ecs', $suspects);
        self::assertArrayHasKey('conso_ecs_depensier', $suspects);
        self::assertArrayHasKey('classe_bilan_dpe', $suspects);
        self::assertStringContainsString('par 18 logements', $suspects['conso_ecs']);
        self::assertArrayNotHasKey('conso_ch', $suspects);
        self::assertArrayNotHasKey('deperdition_enveloppe', $suspects);
    }

    public function test_des_pertes_normales_ou_un_simple_desaccord_ne_sont_pas_signales(): void
    {
        foreach ([1, 2, 3, 17, 19] as $diviseur) {
            $doc = $this->referenceAvecPertesBallon($diviseur);
            self::assertArrayNotHasKey('classe_bilan_dpe', ReferenceDefects::detect([], $doc));
        }
    }

    public function test_le_defaut_de_pertes_exige_les_deux_scenarios_et_un_seul_groupe_individuel(): void
    {
        $xml = $this->referenceAvecPertesBallon()->saveXML();
        foreach ([
            ['enum_methode_application_dpe_log_id', '2'],
            ['nombre_appartement', '1'],
            ['enum_type_installation_id', '2'],
            ['enum_methode_calcul_conso_id', '1'],
            ['ratio_virtualisation', '0.5'],
            ['rdim', '18'],
            ['nombre_logement', '18'],
            ['surface_habitable', '100'],
            ['enum_type_generateur_ecs_id', '6'],
            ['volume_stockage', '0'],
            ['enum_type_energie_id', '2'],
            ['rendement_distribution', '0'],
            ['rendement_generation', '2'],
            ['ratio_besoin_ecs', '0.5'],
            ['besoin_ecs_depensier', '0'],
            ['conso_ecs_depensier', '9999'],
        ] as [$tag, $value]) {
            $doc = new DOMDocument();
            $doc->loadXML($xml);
            $doc->getElementsByTagName($tag)->item(0)->textContent = $value;
            self::assertArrayNotHasKey('classe_bilan_dpe', ReferenceDefects::detect([], $doc), $tag);
        }
        foreach (['installation_ecs', 'generateur_ecs'] as $tag) {
            $doc = new DOMDocument();
            $doc->loadXML($xml);
            $node = $doc->getElementsByTagName($tag)->item(0);
            $node->parentNode->appendChild($node->cloneNode(true));
            self::assertArrayNotHasKey('classe_bilan_dpe', ReferenceDefects::detect([], $doc));
        }
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $doc->getElementsByTagName('installation_ecs')->item(0)->appendChild($doc->createElement('tv_fecs_id', '1'));
        self::assertArrayNotHasKey('classe_bilan_dpe', ReferenceDefects::detect([], $doc));
    }

    public function test_un_ecart_imputable_aux_pertes_de_reference_reste_dans_le_bilan_brut(): void
    {
        $reference = $this->referenceAvecPertesBallon();
        $logement = $reference->getElementsByTagName('logement')->item(0);
        $sortie = $reference->createElement('sortie');
        $ep = $reference->createElement('ep_conso');
        $ep->appendChild($reference->createElement('classe_bilan_dpe', 'C'));
        $sortie->appendChild($ep);
        $logement->appendChild($sortie);
        $actual = clone $reference;
        $actual->getElementsByTagName('classe_bilan_dpe')->item(0)->textContent = 'D';
        $comparator = new \CalculDpePHP\Conformite\CaseComparator(\CalculDpePHP\Conformite\ToleranceProfile::strict());
        $rows = $comparator->compareDocuments($reference, $actual);
        $classes = array_values(array_filter($rows, fn($row) => $row['tag'] === 'classe_bilan_dpe'));
        self::assertCount(1, $classes);
        self::assertSame('string_mismatch', $classes[0]['status']);
        self::assertStringContainsString('par 18 logements', $classes[0]['reference_suspect']);
    }


}
