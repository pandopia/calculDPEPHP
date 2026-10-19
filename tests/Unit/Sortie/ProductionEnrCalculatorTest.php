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
    private function sortie(string $version, string $generateurs, string $classe = 'A'): ?DOMElement
    {
        $document = new DOMDocument();
        $document->loadXML(<<<XML
            <?xml version="1.0"?>
            <dpe>
              <administratif><enum_version_id>{$version}</enum_version_id></administratif>
              <logement>
                <caracteristique_generale><surface_habitable_logement>100</surface_habitable_logement></caracteristique_generale>
                {$generateurs}
                <sortie><ep_conso><classe_bilan_dpe>{$classe}</classe_bilan_dpe></ep_conso></sortie>
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
