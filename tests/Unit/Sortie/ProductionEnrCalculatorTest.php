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

    #[DataProvider('perimetresReseaux')]
    public function testLaProductionReseauSommeLesUsagesAvantLeProrataUnique(string $format, int $mode): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<XML
            <dpe version="$format"><administratif><enum_version_id>2.7</enum_version_id><date_etablissement_dpe>2027-01-01</date_etablissement_dpe></administratif><logement>
              <caracteristique_generale><enum_methode_application_dpe_log_id>$mode</enum_methode_application_dpe_log_id><surface_habitable_immeuble>1000</surface_habitable_immeuble><surface_habitable_logement>100</surface_habitable_logement></caracteristique_generale>
              <installation_chauffage_collection><installation_chauffage><donnee_entree><rdim>2</rdim><cle_repartition_ch>0.7</cle_repartition_ch></donnee_entree>
                <generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>107</enum_type_generateur_ch_id><enum_type_energie_id>8</enum_type_energie_id><identifiant_reseau_chaleur>0103C</identifiant_reseau_chaleur></donnee_entree><donnee_intermediaire><conso_ch>1000</conso_ch><conso_ch_depensier>9000</conso_ch_depensier></donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection>
              </installation_chauffage></installation_chauffage_collection>
              <installation_ecs_collection><installation_ecs><donnee_entree><rdim>3</rdim><cle_repartition_ecs>0.8</cle_repartition_ecs></donnee_entree>
                <generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_generateur_ecs_id>72</enum_type_generateur_ecs_id><enum_type_energie_id>8</enum_type_energie_id><identifiant_reseau_chaleur>0103C</identifiant_reseau_chaleur></donnee_entree><donnee_intermediaire><conso_ecs>200</conso_ecs></donnee_intermediaire></generateur_ecs></generateur_ecs_collection>
              </installation_ecs></installation_ecs_collection>
              <sortie><ef_conso><conso_5_usages>400</conso_5_usages></ef_conso><ep_conso><classe_bilan_dpe>A</classe_bilan_dpe></ep_conso></sortie>
            </logement></dpe>
            XML);
        $ctx = new CalculationContext($doc, new TableRepository(self::PROJECT_ROOT . '/resources/tables'));
        // L'ancien canal non alimenté ne doit ni écraser ni ajouter le calcul.
        $ctx->set('enr.reseau_chaleur', 999999);
        (new ProductionEnrCalculator())->calculate($doc->getElementsByTagName('logement')->item(0), $ctx);
        $part = in_array($mode, [10, 11, 12, 13, 33, 34, 38, 39, 40], true) ? 0.1 : 1.0;
        $enr = (1000 * 2 + 200 * 3) * 0.857 * $part;
        $xp = new \DOMXPath($doc);
        self::assertEqualsWithDelta($enr, $xp->evaluate('number(//prod_enr_rcfu)'), 1e-9);
        self::assertEqualsWithDelta($enr, $xp->evaluate('number(//prod_enr_totale)'), 1e-9);
        self::assertEqualsWithDelta($enr / 400, $xp->evaluate('number(//prod_enr_pourcentage)'), 1e-9);
        self::assertSame('0', $xp->evaluate('string(//mention_a0)'));
    }

    public static function perimetresReseaux(): iterable
    {
        foreach (['2', '0.1.0'] as $format) {
            foreach ([1, 8, 9, 10, 11, 12, 13, 33, 34, 38, 39, 40] as $mode) { yield "$format, mode $mode" => [$format, $mode]; }
        }
    }

    #[DataProvider('tauxReseauxDisponibles')]
    public function testLaProductionReseauSuitLaDateEtLeVraiZero(string $date, string $id, float $expected): void
    {
        $sortie = $this->sortie('2.7', '<installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_energie_id>8</enum_type_energie_id><identifiant_reseau_chaleur>' . $id . '</identifiant_reseau_chaleur><date_arrete_reseau_chaleur>' . $date . '</date_arrete_reseau_chaleur></donnee_entree><donnee_intermediaire><conso_ch>1000</conso_ch></donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>');
        self::assertEqualsWithDelta($expected, (float)$this->valeur($sortie, 'prod_enr_rcfu'), 1e-9);
    }

    public static function tauxReseauxDisponibles(): iterable
    {
        yield 'taux 2021 explicite' => ['2021-10-21', '0103C', 715.0];
        yield 'taux 2026 explicite' => ['2026-03-30', '0103C', 857.0];
        yield 'zéro officiel' => ['2026-03-30', '0611C', 0.0];
        yield 'autre réseau chaleur sans taux historique' => ['2026-03-30', '9999C', 0.0];
    }

    #[DataProvider('tauxReseauxIndisponibles')]
    public function testUnTauxIndisponibleNEstPasUnZero(string $id, string $date, string $type): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('taux ENR');
        $this->sortie('2.7', '<installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_energie_id>8</enum_type_energie_id><identifiant_reseau_chaleur>' . $id . '</identifiant_reseau_chaleur><date_arrete_reseau_chaleur>' . $date . '</date_arrete_reseau_chaleur>' . $type . '</donnee_entree><donnee_intermediaire><conso_ecs>100</conso_ecs></donnee_intermediaire></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>');
    }

    public static function tauxReseauxIndisponibles(): iterable
    {
        yield 'identifiant absent' => ['', '2026-03-30', ''];
        yield 'identifiant mal formé' => ['103C', '2026-03-30', ''];
        yield 'ancienne édition sans taux' => ['0103C', '2020-10-12', ''];
        yield 'anomalie officielle non plafonnée' => ['5415C', '2023-03-16', ''];
        yield 'froid sans taux' => ['0606F', '2026-03-30', ''];
        yield 'chaufferie multi bâtiment non qualifiée' => ['0103C', '2026-03-30', '<enum_type_generateur_ecs_id>74</enum_type_generateur_ecs_id>'];
    }

    public function testLeReseauDeFroidNonCouvertNEstPasSilencieusementIgnore(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('réseau de froid');
        $this->sortie('2.7', '<climatisation_collection><climatisation><donnee_entree><enum_type_energie_id>15</enum_type_energie_id><identifiant_reseau_chaleur>0606F</identifiant_reseau_chaleur></donnee_entree><donnee_intermediaire><conso_fr>1000</conso_fr></donnee_intermediaire></climatisation></climatisation_collection>');
    }

    public function testUnReseauSansConsommationNeNecessitePasUnTauxInvente(): void
    {
        $sortie = $this->sortie('2.7', '<installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_energie_id>8</enum_type_energie_id></donnee_entree><donnee_intermediaire><conso_ecs>0</conso_ecs></donnee_intermediaire></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>');
        self::assertSame('0', $this->valeur($sortie, 'prod_enr_rcfu'));
    }

    public function testUnGenerateurGazAvecUnIdentifiantParasiteNEstPasUnReseau(): void
    {
        $sortie = $this->sortie('2.7', '<installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_energie_id>2</enum_type_energie_id><identifiant_reseau_chaleur>0103C</identifiant_reseau_chaleur></donnee_entree><donnee_intermediaire><conso_ecs>1000</conso_ecs></donnee_intermediaire></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>');
        self::assertSame('0', $this->valeur($sortie, 'prod_enr_rcfu'));
    }

    public function testLesAnciensModelesNeDemandentPasDeTauxEnr(): void
    {
        foreach (['2.4', '2.5', '2.6'] as $version) {
            $sortie = $this->sortie($version, '<installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs><donnee_entree><enum_type_energie_id>8</enum_type_energie_id></donnee_entree><donnee_intermediaire><conso_ecs>1000</conso_ecs></donnee_intermediaire></generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>');
            self::assertNull($this->valeur($sortie, 'production_enr'));
        }
    }

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

    /** Tous les IDs CH du XSD, y compris ceux qui ne sont PAS des PAC. */
    public static function typesGenerateurChDuSchema(): iterable
    {
        $schema = new DOMDocument();
        $schema->load(self::PROJECT_ROOT . '/editeur/public/schemas/DPEv2.7.xsd');
        $xp = new \DOMXPath($schema);
        $element = $xp->query('(//xs:element[@name="enum_type_generateur_ch_id"])[1]')->item(0);
        $libelles = json_decode($xp->evaluate('string(xs:annotation/xs:appinfo)', $element), true, flags: JSON_THROW_ON_ERROR);
        foreach ($xp->query('.//xs:enumeration', $element) as $enumeration) {
            $type = (int)$enumeration->getAttribute('value');
            $libelle = $libelles[$type];
            $pac = str_starts_with($libelle, 'pac ') || str_contains($libelle, 'partie pompe à chaleur')
                || $libelle === 'autre système thermodynamique électrique';
            $geothermique = $pac && preg_match('/eau\/eau|eau glycolée\/eau|géothermique/u', $libelle) === 1;
            yield "$type : $libelle" => [$type, $pac, $geothermique];
        }
    }

    #[DataProvider('typesGenerateurChDuSchema')]
    public function testLaClassificationEnrCouvreLesLibellesChDuXsd(
        int $type,
        bool $pac,
        bool $geothermique,
    ): void {
        $sortie = $this->sortie('2.7', <<<XML
            <installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection><generateur_chauffage>
              <donnee_entree><enum_type_generateur_ch_id>$type</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
              <donnee_intermediaire><conso_ch>1000</conso_ch><scop>4</scop></donnee_intermediaire>
            </generateur_chauffage></generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
            XML);
        self::assertEqualsWithDelta($pac && !$geothermique ? 3000.0 : 0.0, (float)$this->valeur($sortie, 'prod_enr_chaleur_ambiante_pac'), 1e-9);
        self::assertEqualsWithDelta($geothermique ? 3000.0 : 0.0, (float)$this->valeur($sortie, 'prod_enr_geothermie_pac'), 1e-9);
    }

    public static function typesGenerateurEcsDuSchema(): iterable
    {
        $schema = new DOMDocument();
        $schema->load(self::PROJECT_ROOT . '/editeur/public/schemas/DPEv2.7.xsd');
        $xp = new \DOMXPath($schema);
        $element = $xp->query('(//xs:element[@name="enum_type_generateur_ecs_id"])[1]')->item(0);
        $libelles = json_decode($xp->evaluate('string(xs:annotation/xs:appinfo)', $element), true, flags: JSON_THROW_ON_ERROR);
        $minimum = (int)$xp->evaluate('string(.//xs:minInclusive/@value)', $element);
        $maximum = (int)$xp->evaluate('string(.//xs:maxInclusive/@value)', $element);
        foreach (range($minimum, $maximum) as $type) {
            $libelle = $libelles[$type];
            yield "$type : $libelle" => [$type, str_starts_with($libelle, 'cet ') || str_starts_with($libelle, 'pac double service')
                || $libelle === 'autre système thermodynamique électrique'];
        }
    }

    #[DataProvider('typesGenerateurEcsDuSchema')]
    public function testUnCopResiduelSurUneChaudiereEcsNeCreePasDeChaleurCaptee(int $type, bool $thermodynamique): void
    {
        $sortie = $this->sortie('2.7', <<<XML
            <installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs>
              <donnee_entree><enum_type_generateur_ecs_id>$type</enum_type_generateur_ecs_id></donnee_entree>
              <donnee_intermediaire><conso_ecs>200</conso_ecs><cop>3</cop></donnee_intermediaire>
            </generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>
            XML);
        self::assertEqualsWithDelta($thermodynamique ? 400.0 : 0.0, (float)$this->valeur($sortie, 'prod_enr_chaleur_ambiante_cet'), 1e-9);
    }

    public static function perimetresProductionEnr(): iterable
    {
        foreach (['2', '0.1.0'] as $format) {
            foreach ([7, 10] as $mode) {
                foreach ([false, true] as $echantillonne) {
                    yield "$format, mode $mode, échantillonnage=" . (int)$echantillonne => [$format, $mode, $echantillonne];
                }
            }
        }
    }

    public static function hybridesEtZones(): iterable
    {
        foreach ([147 => false, 168 => true, 178 => false, 181 => true] as $type => $geothermique) {
            foreach (['1' => 0.8, '3' => 0.8, '4' => 0.83, '7' => 0.83, '8' => 0.88] as $zone => $part) {
                yield "hybride $type, zone $zone" => [$type, $geothermique, (string)$zone, $part];
            }
        }
    }

    #[DataProvider('hybridesEtZones')]
    public function testLeForfaitHybrideEnrPorteSurLeTotalDuSysteme(
        int $type,
        bool $geothermique,
        string $zone,
        float $part,
    ): void {
        $doc = new DOMDocument();
        $doc->loadXML(<<<XML
            <dpe><administratif><enum_version_id>2.7</enum_version_id></administratif><logement>
              <installation_chauffage_collection><installation_chauffage>
                <donnee_intermediaire><conso_ch>1000</conso_ch></donnee_intermediaire>
                <generateur_chauffage_collection>
                  <generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>$type</enum_type_generateur_ch_id></donnee_entree>
                    <donnee_intermediaire><conso_ch>100</conso_ch><scop>4</scop></donnee_intermediaire>
                  </generateur_chauffage>
                  <generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>149</enum_type_generateur_ch_id></donnee_entree>
                    <donnee_intermediaire><conso_ch>900</conso_ch><scop>8</scop></donnee_intermediaire>
                  </generateur_chauffage>
                </generateur_chauffage_collection>
              </installation_chauffage></installation_chauffage_collection>
            </logement></dpe>
            XML);
        $ctx = new CalculationContext($doc, new TableRepository(self::PROJECT_ROOT . '/resources/tables'), zoneClimatique: $zone);
        (new ProductionEnrCalculator())->calculate($doc->getElementsByTagName('logement')->item(0), $ctx);
        $attendu = 1000 * $part * (4 - 1);
        self::assertEqualsWithDelta($attendu, (float)$doc->getElementsByTagName('prod_enr_totale')->item(0)->textContent, 1e-9);
        self::assertEqualsWithDelta($geothermique ? $attendu : 0.0, (float)$doc->getElementsByTagName('prod_enr_geothermie_pac')->item(0)->textContent, 1e-9);
        self::assertEqualsWithDelta($geothermique ? 0.0 : $attendu, (float)$doc->getElementsByTagName('prod_enr_chaleur_ambiante_pac')->item(0)->textContent, 1e-9);
    }

    public function testPlusieursHybridesSansTotalIndividuelNeRecomptentPasLeBloc(): void
    {
        $sortie = $this->sortie('2.7', <<<'XML'
            <installation_chauffage_collection><installation_chauffage>
              <donnee_intermediaire><conso_ch>1000</conso_ch></donnee_intermediaire>
              <generateur_chauffage_collection>
                <generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>147</enum_type_generateur_ch_id></donnee_entree>
                  <donnee_intermediaire><conso_ch>100</conso_ch><scop>3</scop></donnee_intermediaire></generateur_chauffage>
                <generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>178</enum_type_generateur_ch_id></donnee_entree>
                  <donnee_intermediaire><conso_ch>200</conso_ch><scop>4</scop></donnee_intermediaire></generateur_chauffage>
              </generateur_chauffage_collection>
            </installation_chauffage></installation_chauffage_collection>
            XML);
        self::assertEqualsWithDelta(800.0, (float)$this->valeur($sortie, 'prod_enr_totale'), 1e-9);
    }

    public function testLaMultipliciteDeRepliEtLesInstallationsCollectivesRestentDistinctes(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
            <dpe><administratif><enum_version_id>2.7</enum_version_id></administratif><logement>
              <caracteristique_generale><nombre_appartement>12</nombre_appartement></caracteristique_generale>
              <installation_chauffage_collection>
                <installation_chauffage><donnee_entree><enum_methode_calcul_conso_id>2</enum_methode_calcul_conso_id>
                  <enum_type_installation_id>1</enum_type_installation_id><nombre_logement_echantillon>2</nombre_logement_echantillon>
                  <ratio_virtualisation>0.5</ratio_virtualisation><rdim>9</rdim></donnee_entree>
                  <generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>3</enum_type_generateur_ch_id></donnee_entree>
                    <donnee_intermediaire><conso_ch>100</conso_ch><scop>3</scop></donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection>
                </installation_chauffage>
                <installation_chauffage><donnee_entree><enum_methode_calcul_conso_id>2</enum_methode_calcul_conso_id>
                  <enum_type_installation_id>2</enum_type_installation_id><nombre_logement_echantillon>1</nombre_logement_echantillon><rdim>2</rdim></donnee_entree>
                  <generateur_chauffage_collection><generateur_chauffage><donnee_entree><enum_type_generateur_ch_id>19</enum_type_generateur_ch_id></donnee_entree>
                    <donnee_intermediaire><conso_ch>100</conso_ch><scop>4</scop></donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection>
                </installation_chauffage>
              </installation_chauffage_collection>
            </logement></dpe>
            XML);
        $ctx = new CalculationContext($doc, new TableRepository(self::PROJECT_ROOT . '/resources/tables'));
        (new ProductionEnrCalculator())->calculate($doc->getElementsByTagName('logement')->item(0), $ctx);
        // Extrapolation 12×0,5/(2+1)=2 pour l'individuel ; rdim=2 pour le collectif.
        self::assertEqualsWithDelta(400.0, (float)$doc->getElementsByTagName('prod_enr_chaleur_ambiante_pac')->item(0)->textContent, 1e-9);
        self::assertEqualsWithDelta(600.0, (float)$doc->getElementsByTagName('prod_enr_geothermie_pac')->item(0)->textContent, 1e-9);
    }

    #[DataProvider('perimetresProductionEnr')]
    public function testLesMultiplicitesPrecedentLeProrataSurfaciqueEnr(
        string $format,
        int $mode,
        bool $echantillonne,
    ): void {
        $methodeCh = $echantillonne ? 2 : 1;
        $methodeEcs = $echantillonne ? 4 : 1;
        $doc = new DOMDocument();
        $doc->loadXML(<<<XML
            <dpe version="$format"><administratif><enum_version_id>2.7</enum_version_id></administratif><logement>
              <caracteristique_generale><enum_methode_application_dpe_log_id>$mode</enum_methode_application_dpe_log_id>
                <surface_habitable_immeuble>1000</surface_habitable_immeuble><surface_habitable_logement>100</surface_habitable_logement>
                <nombre_appartement>10</nombre_appartement></caracteristique_generale>
              <production_elec_enr><donnee_intermediaire><production_pv>1000</production_pv></donnee_intermediaire></production_elec_enr>
              <installation_chauffage_collection>
                <installation_chauffage><donnee_entree><enum_methode_calcul_conso_id>$methodeCh</enum_methode_calcul_conso_id>
                  <enum_type_installation_id>1</enum_type_installation_id><surface_chauffee>400</surface_chauffee>
                  <rdim>2</rdim><cle_repartition_ch>0.8</cle_repartition_ch></donnee_entree>
                  <generateur_chauffage_collection><generateur_chauffage>
                    <donnee_entree><enum_type_generateur_ch_id>3</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
                    <donnee_intermediaire><conso_ch>100</conso_ch><scop>3</scop></donnee_intermediaire>
                  </generateur_chauffage></generateur_chauffage_collection>
                </installation_chauffage>
                <installation_chauffage><donnee_entree><enum_methode_calcul_conso_id>$methodeCh</enum_methode_calcul_conso_id>
                  <enum_type_installation_id>1</enum_type_installation_id><surface_chauffee>600</surface_chauffee>
                  <rdim>3</rdim><cle_repartition_ch>0.8</cle_repartition_ch></donnee_entree>
                  <generateur_chauffage_collection><generateur_chauffage>
                    <donnee_entree><enum_type_generateur_ch_id>97</enum_type_generateur_ch_id><enum_type_energie_id>5</enum_type_energie_id></donnee_entree>
                    <donnee_intermediaire><conso_ch>500</conso_ch></donnee_intermediaire>
                  </generateur_chauffage></generateur_chauffage_collection>
                </installation_chauffage>
              </installation_chauffage_collection>
              <installation_ecs_collection><installation_ecs><donnee_entree>
                <enum_methode_calcul_conso_id>$methodeEcs</enum_methode_calcul_conso_id><enum_type_installation_id>1</enum_type_installation_id>
                <surface_habitable>600</surface_habitable><rdim>4</rdim><cle_repartition_ecs>0.6</cle_repartition_ecs>
                </donnee_entree><generateur_ecs_collection><generateur_ecs>
                  <donnee_entree><enum_type_generateur_ecs_id>3</enum_type_generateur_ecs_id></donnee_entree>
                  <donnee_intermediaire><conso_ecs>50</conso_ecs><cop>3</cop></donnee_intermediaire>
                </generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>
              <sortie><ef_conso><conso_5_usages>1000</conso_5_usages></ef_conso></sortie>
            </logement></dpe>
            XML);
        $logement = $doc->getElementsByTagName('logement')->item(0);
        $ctx = new CalculationContext($doc, new TableRepository(self::PROJECT_ROOT . '/resources/tables'));
        $part = $mode === 10 ? 0.1 : 1.0;
        foreach ([1, 2] as $passage) {
            (new ProductionEnrCalculator())->calculate($logement, $ctx);
            $sortie = $doc->getElementsByTagName('sortie')->item(0);
            self::assertEqualsWithDelta(1000 * $part, (float)$this->valeur($sortie, 'prod_enr_pv'), 1e-9);
            self::assertEqualsWithDelta(($echantillonne ? 800 : 400) * $part, (float)$this->valeur($sortie, 'prod_enr_chaleur_ambiante_pac'), 1e-9);
            self::assertEqualsWithDelta(($echantillonne ? 3000 : 1500) * $part, (float)$this->valeur($sortie, 'prod_enr_bois'), 1e-9);
            self::assertEqualsWithDelta(($echantillonne ? 600 : 400) * $part, (float)$this->valeur($sortie, 'prod_enr_chaleur_ambiante_cet'), 1e-9);
            self::assertEqualsWithDelta(($echantillonne ? 5400 : 3300) * $part, (float)$this->valeur($sortie, 'prod_enr_totale'), 1e-9);
        }
    }

    public function testLaFractionEnrReintegreChaleurCapteeEtAutoconsommationSansPlafond(): void
    {
        $sortie = $this->sortie('2.7', <<<'XML'
            <production_elec_enr><donnee_intermediaire><production_pv>2000</production_pv><conso_elec_ac>300</conso_elec_ac></donnee_intermediaire></production_elec_enr>
            <installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection><generateur_chauffage>
              <donnee_entree><enum_type_generateur_ch_id>3</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
              <donnee_intermediaire><conso_ch>1000</conso_ch><scop>3</scop></donnee_intermediaire>
            </generateur_chauffage></generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
            <installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs>
              <donnee_entree><enum_type_generateur_ecs_id>3</enum_type_generateur_ecs_id></donnee_entree>
              <donnee_intermediaire><conso_ecs>200</conso_ecs><cop>3</cop></donnee_intermediaire>
            </generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection>
            XML, sortieXml: '<ef_conso><conso_5_usages>1000</conso_5_usages></ef_conso>');
        self::assertEqualsWithDelta(4400.0, (float)$this->valeur($sortie, 'prod_enr_totale'), 1e-9);
        self::assertEqualsWithDelta(4400 / 3700, (float)$this->valeur($sortie, 'prod_enr_pourcentage'), 1e-9);
        self::assertGreaterThan(1.0, (float)$this->valeur($sortie, 'prod_enr_pourcentage'));
    }

    public function testPvCalculeLocalementNestPasProratiseDeuxFoisDansLeBilanEnr(): void
    {
        $doc = new DOMDocument();
        $doc->loadXML('<dpe><administratif><enum_version_id>2.7</enum_version_id></administratif><logement>'
            . '<caracteristique_generale><enum_methode_application_dpe_log_id>10</enum_methode_application_dpe_log_id><surface_habitable_logement>100</surface_habitable_logement><surface_habitable_immeuble>1000</surface_habitable_immeuble><nombre_appartement>10</nombre_appartement></caracteristique_generale>'
            . '<production_elec_enr><donnee_entree><presence_production_pv>1</presence_production_pv></donnee_entree><panneaux_pv_collection><panneaux_pv><surface_totale_capteurs>20</surface_totale_capteurs><enum_orientation_pv_id>3</enum_orientation_pv_id><enum_inclinaison_pv_id>2</enum_inclinaison_pv_id></panneaux_pv></panneaux_pv_collection></production_elec_enr>'
            . '<sortie><ef_conso><conso_eclairage>200</conso_eclairage><conso_totale_auxiliaire>100</conso_totale_auxiliaire><conso_auxiliaire_ventilation>100</conso_auxiliaire_ventilation><conso_5_usages>300</conso_5_usages></ef_conso></sortie></logement></dpe>');
        $ctx = new CalculationContext($doc, new TableRepository(self::PROJECT_ROOT . '/resources/tables'), zoneClimatique: '1');
        $logement = $doc->getElementsByTagName('logement')->item(0);
        (new \CalculDpePHP\ProductionElec\ProductionPvCalculator())->calculate($logement, $ctx);
        $local = (float)$ctx->get('pv.production_pv');
        self::assertGreaterThan(0.0, $local);
        self::assertEqualsWithDelta($local * 10, $ctx->get('pv.production_pv_immeuble'), 1e-8);
        foreach ([1, 2] as $passage) {
            (new ProductionEnrCalculator())->calculate($logement, $ctx);
            self::assertEqualsWithDelta($local, (float)$doc->getElementsByTagName('prod_enr_pv')->item(0)->textContent, 1e-8);
        }
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
                <donnee_entree><enum_type_generateur_ch_id>3</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
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
                <donnee_entree><enum_type_generateur_ch_id>19</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
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
                <donnee_entree><enum_type_generateur_ch_id>3</enum_type_generateur_ch_id><enum_type_energie_id>1</enum_type_energie_id></donnee_entree>
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
        $reseau = $energie === 8 ? '<identifiant_reseau_chaleur>0103C</identifiant_reseau_chaleur><date_arrete_reseau_chaleur>2026-03-30</date_arrete_reseau_chaleur>' : '';
        $sortie = $this->sortie('2.7', <<<XML
            <installation_chauffage_collection><installation_chauffage><generateur_chauffage_collection>
              <generateur_chauffage>
                <donnee_entree><enum_type_generateur_ch_id>97</enum_type_generateur_ch_id><enum_type_energie_id>{$energie}</enum_type_energie_id>{$reseau}</donnee_entree>
                <donnee_intermediaire><conso_ch>1000</conso_ch></donnee_intermediaire>
              </generateur_chauffage>
            </generateur_chauffage_collection></installation_chauffage></installation_chauffage_collection>
            XML, $classe);

        self::assertSame($attendu, $this->valeur($sortie, 'mention_a0'));
    }
}
