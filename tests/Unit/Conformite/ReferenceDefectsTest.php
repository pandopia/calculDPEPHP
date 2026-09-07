<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
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
}
