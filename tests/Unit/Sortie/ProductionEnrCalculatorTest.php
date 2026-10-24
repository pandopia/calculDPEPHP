<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\ProductionEnrCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Bloc `production_enr` et mention « A0 » — arrêté du 11 juin 2026, annexe 2.
 *
 * Les valeurs attendues viennent des formules de l'arrêté, pas d'un fichier
 * de référence : le modèle 2.7 n'entre en vigueur qu'au 1er janvier 2027 et
 * aucun DPE publié ne les porte encore.
 */
final class ProductionEnrCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    /**
     * @param array<string, string> $options version, classe, et les blocs
     */
    private function sortie(string $version, string $generateurs, string $classe = 'A', string $sortieXml = ''): ?DOMElement
    {
        $document = new DOMDocument();
        $document->loadXML(<<<XML
            <?xml version="1.0"?>
            <dpe>
              <administratif><enum_version_id>{$version}</enum_version_id></administratif>
              <logement>
                <caracteristique_generale><surface_habitable_logement>100</surface_habitable_logement></caracteristique_generale>
                {$generateurs}
                <sortie>{$sortieXml}<ep_conso><classe_bilan_dpe>{$classe}</classe_bilan_dpe></ep_conso></sortie>
              </logement>
            </dpe>
            XML);

        $logement = $document->getElementsByTagName('logement')->item(0);
        self::assertInstanceOf(DOMElement::class, $logement);

        (new ProductionEnrCalculator())->calculate($logement, new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        ));

        $sortie = $logement->getElementsByTagName('sortie')->item(0);

        return $sortie instanceof DOMElement ? $sortie : null;
    }

    private function valeur(?DOMElement $sortie, string $balise): ?string
    {
        return $sortie?->getElementsByTagName($balise)->item(0)?->textContent;
    }

    public function testLaFractionEnrReintegreChaleurCapteeEtAutoconsommationSansPlafond(): void
    {
        $sortie = $this->sortie('2.7', <<<'XML'
            <production_elec_enr><donnee_intermediaire><production_pv>2000</production_pv><conso_elec_ac>300</conso_elec_ac></donnee_intermediaire></production_elec_enr>
            <installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection><generateur_chauffage>
              <donnee_entree><enum_type_generateur_ch_id>120</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
              <donnee_intermediaire><conso_ch>1000</conso_ch><scop>3</scop></donnee_intermediaire>
            </generateur_chauffage></generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
            <installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs>
              <donnee_intermediaire><conso_ecs>200</conso_ecs><cop>3</cop></donnee_intermediaire>
            </generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>
            XML, sortieXml: '<ef_conso><conso_5_usages>1000</conso_5_usages></ef_conso>');
        self::assertEqualsWithDelta(4400.0, (float)$this->valeur($sortie, 'prod_enr_totale'), 1e-9);
        self::assertEqualsWithDelta(4400 / 3700, (float)$this->valeur($sortie, 'prod_enr_pourcentage'), 1e-9);
        self::assertGreaterThan(1.0, (float)$this->valeur($sortie, 'prod_enr_pourcentage'));
    }

    public function testLeBlocEnrRespecteLesNeufBalisesObligatoiresDuSchema27(): void
    {
        $sortie = $this->sortie('2.7', '');
        $bloc = $sortie->getElementsByTagName('production_enr')->item(0);
        self::assertSame(9, $bloc->childNodes->length);
        self::assertSame('0', $this->valeur($sortie, 'prod_enr_pourcentage'));
        $schema = new DOMDocument();
        $schema->load(self::PROJECT_ROOT . '/editeur/public/schemas/DPEv2.7.xsd');
        $xp = new \DOMXPath($schema);
        $declaration = $xp->query('//xs:element[@name="logement"]//xs:element[@name="production_enr"]')->item(0);
        $schemaIsole = new DOMDocument();
        $racine = $schemaIsole->createElementNS('http://www.w3.org/2001/XMLSchema', 'xs:schema');
        $schemaIsole->appendChild($racine);
        $racine->appendChild($schemaIsole->importNode($declaration, true));
        $resultat = new DOMDocument();
        $resultat->appendChild($resultat->importNode($bloc, true));
        self::assertTrue($resultat->schemaValidateSource($schemaIsole->saveXML()));
    }

    public static function casSolaireThermique(): iterable
    {
        yield 'immeuble, export historique' => ['2', 7, 3000.0];
        yield 'immeuble, XML ADEME natif' => ['0.1.0', 7, 3000.0];
        yield 'appartement généré, export historique' => ['2', 10, 1000.0];
        yield 'appartement généré, XML ADEME natif' => ['0.1.0', 10, 1000.0];
    }

    #[DataProvider('casSolaireThermique')]
    public function testSolaireChauffageEtEcsTransitEnKwhAvecUnSeulProrata(string $format, int $mode, float $attendu): void
    {
        $besoinEcs = $format === '0.1.0' ? 1000000 : 1000;
        $doc = new DOMDocument();
        $doc->loadXML(<<<XML
            <dpe version="$format"><administratif><enum_version_id>2.7</enum_version_id></administratif><logement>
              <caracteristique_generale><enum_methode_application_dpe_log_id>$mode</enum_methode_application_dpe_log_id>
                <surface_habitable_immeuble>300</surface_habitable_immeuble><surface_habitable_logement>100</surface_habitable_logement>
                <nombre_appartement>3</nombre_appartement><hsp>2.5</hsp></caracteristique_generale>
              <installation_chauffage_collection><installation_chauffage>
                <donnee_entree><enum_cfg_installation_ch_id>2</enum_cfg_installation_ch_id><surface_chauffee>300</surface_chauffee><rdim>3</rdim><fch_saisi>0.4</fch_saisi></donnee_entree>
                <emetteur_chauffage_collection><emetteur_chauffage><donnee_entree><surface_chauffee>300</surface_chauffee></donnee_entree>
                  <donnee_intermediaire><i0>1</i0><rendement_emission>1</rendement_emission><rendement_distribution>1</rendement_distribution><rendement_regulation>1</rendement_regulation></donnee_intermediaire>
                </emetteur_chauffage></emetteur_chauffage_collection>
                <generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_energie_id>2</enum_type_energie_id></donnee_entree>
                  <donnee_intermediaire><rendement_generation>0.8</rendement_generation></donnee_intermediaire>
                </generateur_chauffage></generateur_chauffage_collection>
              </installation_chauffage></installation_chauffage_collection>
              <installation_ecs_collection><installation_ecs>
                <donnee_entree><rdim>3</rdim></donnee_entree>
                <donnee_intermediaire><besoin_ecs>$besoinEcs</besoin_ecs><besoin_ecs_depensier>$besoinEcs</besoin_ecs_depensier><rendement_distribution>1</rendement_distribution><fecs>0.4</fecs></donnee_intermediaire>
                <generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_energie_id>2</enum_type_energie_id></donnee_entree>
                  <donnee_intermediaire><rendement_generation>0.8</rendement_generation><rendement_stockage>1</rendement_stockage></donnee_intermediaire>
                </generateur_ecs></generateur_ecs_collection>
              </installation_ecs></installation_ecs_collection>
              <sortie><ef_conso><conso_5_usages>$attendu</conso_5_usages></ef_conso></sortie>
            </logement></dpe>
            XML);
        $ctx = new CalculationContext($doc, new TableRepository(self::PROJECT_ROOT . '/resources/tables'));
        $ctx->set('chauffage.besoin_ch', 3000.0);
        $ctx->set('chauffage.besoin_ch_depensier', 4500.0);
        $ctx->set('chauffage.gv', 750.0);
        $ch = $doc->getElementsByTagName('installation_chauffage')->item(0);
        $ecs = $doc->getElementsByTagName('installation_ecs')->item(0);
        foreach ([1, 2] as $passage) {
            (new \CalculDpePHP\Chauffage\Strategy\ChauffageSolaire())->calculate($ch, $ctx);
            (new \CalculDpePHP\Ecs\ConsoEcsCalculator())->calculate($ecs, $ctx);
            (new ProductionEnrCalculator())->calculate($doc->getElementsByTagName('logement')->item(0), $ctx);
            self::assertEqualsWithDelta(3000.0, \CalculDpePHP\Common\ProductionSolaireThermique::total($ctx), 1e-8);
            self::assertEqualsWithDelta($attendu, (float)$doc->getElementsByTagName('prod_enr_solaire_thermique')->item(0)->textContent, 1e-8);
            self::assertEqualsWithDelta(0.5, (float)$doc->getElementsByTagName('prod_enr_pourcentage')->item(0)->textContent, 1e-8);
        }
    }

    /** Une PAC de SCOP 3 qui consomme 1 000 kWh capte 2 000 kWh à l'environnement. */
    public function testChaleurAmbianteCapteeParUnePompeAChaleur(): void
    {
        $sortie = $this->sortie('2.7', <<<'XML'
            <installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection>
              <generateur_chauffage>
                <donnee_entree><enum_type_generateur_ch_id>120</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
                <donnee_intermediaire><conso_ch>1000</conso_ch><scop>3</scop></donnee_intermediaire>
              </generateur_chauffage>
            </generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
            XML);

        self::assertEqualsWithDelta(2000.0, (float) $this->valeur($sortie, 'prod_enr_chaleur_ambiante_pac'), 1e-9);
        self::assertEqualsWithDelta(0.0, (float) $this->valeur($sortie, 'prod_enr_geothermie_pac'), 1e-9);
        self::assertEqualsWithDelta(2000.0, (float) $this->valeur($sortie, 'prod_enr_totale'), 1e-9);
    }

    /** §1.2.7 compte la géothermie à part, sur la même formule. */
    public function testLaGeothermieEstCompteeSeparement(): void
    {
        $sortie = $this->sortie('2.7', <<<'XML'
            <installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection>
              <generateur_chauffage>
                <donnee_entree><enum_type_generateur_ch_id>44</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
                <donnee_intermediaire><conso_ch>1000</conso_ch><scop>4</scop></donnee_intermediaire>
              </generateur_chauffage>
            </generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
            XML);

        self::assertEqualsWithDelta(3000.0, (float) $this->valeur($sortie, 'prod_enr_geothermie_pac'), 1e-9);
        self::assertEqualsWithDelta(0.0, (float) $this->valeur($sortie, 'prod_enr_chaleur_ambiante_pac'), 1e-9);
    }

    /** §1.2.5 : la production bois est la consommation de bois elle-même. */
    public function testLaProductionBoisEstLaConsommationDeBois(): void
    {
        $sortie = $this->sortie('2.7', <<<'XML'
            <installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection>
              <generateur_chauffage>
                <donnee_entree><enum_type_generateur_ch_id>97</enum_type_generateur_ch_id><enum_type_energie_id>5</enum_type_energie_id></donnee_entree>
                <donnee_intermediaire><conso_ch>4200</conso_ch></donnee_intermediaire>
              </generateur_chauffage>
            </generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
            XML);

        self::assertEqualsWithDelta(4200.0, (float) $this->valeur($sortie, 'prod_enr_bois'), 1e-9);
    }

    /** Un SCOP qui ne dépasse pas 1 ne capte rien, et ne doit pas rendre de production négative. */
    public function testUnScopInferieurAUnNeCapteRien(): void
    {
        $sortie = $this->sortie('2.7', <<<'XML'
            <installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection>
              <generateur_chauffage>
                <donnee_entree><enum_type_generateur_ch_id>120</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
                <donnee_intermediaire><conso_ch>1000</conso_ch><scop>0.9</scop></donnee_intermediaire>
              </generateur_chauffage>
            </generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
            XML);

        self::assertEqualsWithDelta(0.0, (float) $this->valeur($sortie, 'prod_enr_chaleur_ambiante_pac'), 1e-9);
    }

    /** Le bloc n'existe qu'à partir du modèle 2.7 : l'écrire avant serait hors schéma. */
    public function testLeBlocNEstPasEcritAvantLeModele27(): void
    {
        $sortie = $this->sortie('2.6', '<installation_chauffage_collection/>');

        self::assertNull($this->valeur($sortie, 'production_enr'));
        self::assertNull($this->valeur($sortie, 'mention_a0'));
    }

    /**
     * Annexe 1 point 3 : étiquette A ou B **et** aucune énergie fossile.
     *
     * @return iterable<string, array{string, int, string}>
     */
    public static function casMentionA0(): iterable
    {
        yield 'classe A tout electrique' => ['A', 1, '1'];
        yield 'classe B au bois' => ['B', 5, '1'];
        yield 'classe C, meme sans fossile' => ['C', 1, '0'];
        yield 'classe A mais au gaz' => ['A', 2, '0'];
        yield 'classe A mais au fioul' => ['A', 3, '0'];
        yield 'classe A mais reseau de chaleur non qualifie' => ['A', 8, '0'];
    }

    #[DataProvider('casMentionA0')]
    public function testMentionA0(string $classe, int $energie, string $attendu): void
    {
        $sortie = $this->sortie('2.7', <<<XML
            <installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection>
              <generateur_chauffage>
                <donnee_entree><enum_type_generateur_ch_id>97</enum_type_generateur_ch_id><enum_type_energie_id>{$energie}</enum_type_energie_id></donnee_entree>
                <donnee_intermediaire><conso_ch>1000</conso_ch></donnee_intermediaire>
              </generateur_chauffage>
            </generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
            XML, $classe);

        self::assertSame($attendu, $this->valeur($sortie, 'mention_a0'));
    }
}
