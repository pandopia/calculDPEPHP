<?php

declare(strict_types=1);

namespace Tests\Unit\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Sortie\ReseauChaleurFactorResolver;
use CalculDpePHP\Tables\TableRepository;
use CalculDpePHP\Xml\NodeAccessor;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Choix du millésime d'arrêté « réseaux de chaleur et de froid ».
 *
 * Les arrêtés ne paraissent pas à date fixe : le millésime se lit sur les
 * plages d'application publiées, jamais sur l'année du DPE.
 */
final class ReseauChaleurFactorResolverTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    /**
     * Un DPE tombe dans la plage de son arrêté, et dans aucune autre.
     *
     * `4211C` (Andrézieux-Bouthéon) change de valeur à chaque millésime, ce
     * qui en fait un témoin commode de la plage réellement appliquée.
     */
    #[DataProvider('datesEtMillesimes')]
    public function testLeMillesimeSuitLaPlageDApplication(string $date, float $attendu): void
    {
        self::assertEqualsWithDelta($attendu, $this->resolve($date, '4211C'), 1e-9);
    }

    /** @return iterable<string, array{string, float}> */
    public static function datesEtMillesimes(): iterable
    {
        // Millésime 2021, arrêté du 21 octobre 2021 : 2022-01-18 → 2023-04-23.
        // Avant cette borne, `identifiant_reseau_chaleur` n'existe pas encore
        // au schéma : un fichier qui en porterait un malgré tout ne peut pas
        // être résolu sur le millésime 2020, indexé par numéro de ligne, et
        // retombe donc sur le forfait (cf. testLeMillesime2020...).
        yield 'veille de la bascule, identifiant hors de son temps' => ['2022-01-17', 0.385];
        yield 'jour de la bascule vers 2021' => ['2022-01-18', 0.109];
        yield '2242E2979513I' => ['2022-04-06', 0.109];
        // Millésime 2022, arrêté du 16 mars 2023 : 2023-03-16 → 2024-09-20.
        yield 'jour de la bascule vers 2022' => ['2023-03-16', 0.075];
        yield 'ete 2024, toujours le millesime 2022' => ['2024-07-04', 0.075];
        // Millésime 2023, arrêté du 5 juillet 2024.
        yield 'jour de la bascule vers 2023' => ['2024-07-05', 0.075];
        // Millésime 2024, arrêté du 11 avril 2025.
        yield 'jour de la bascule vers 2024' => ['2025-04-11', 0.069];
        // Millésime 2025, arrêté du 25 avril 2026, applicable au 10 mai.
        yield 'jour de la bascule vers 2025' => ['2026-05-10', 0.048];
    }

    /**
     * Un DPE d'avril 2022 relève de l'arrêté du 21 octobre 2021. L'ancienne
     * règle `max(2022, année − 1)` lui appliquait celui du 16 mars 2023,
     * publié onze mois après lui.
     */
    public function testUnArreteNEstJamaisAppliqueAvantSaPublication(): void
    {
        self::assertEqualsWithDelta(0.109, $this->resolve('2022-04-06', '4211C'), 1e-9);
        self::assertNotEqualsWithDelta(0.075, $this->resolve('2022-04-06', '4211C'), 1e-9);
    }

    /**
     * `date_arrete_reseau_chaleur` désigne le millésime sans ambiguïté, même
     * quand la date du DPE pointerait ailleurs : le XSD la définit comme « la
     * date de l'arrêté qui modifie l'annexe 7 ».
     */
    public function testLaDateDArreteDeclareePrimeSurCelleDuDpe(): void
    {
        self::assertEqualsWithDelta(
            0.109,
            $this->resolve('2025-06-01', '4211C', dateArrete: '2021-10-21'),
            1e-9,
        );
    }

    /**
     * Avant le 18 janvier 2022, le XSD déclare `identifiant_reseau_chaleur`
     * inexistant et le réseau est désigné par `tv_reseau_chaleur_id`.
     */
    public function testLeMillesime2020EstIndexeParLeNumeroDeLigne(): void
    {
        self::assertEqualsWithDelta(
            0.155,
            $this->resolve('2021-09-15', null, ligne: 1),
            1e-9,
        );
    }

    /** Un identifiant absent de tous les millésimes retombe sur le forfait. */
    public function testUnReseauInconnuRetombeSurLeForfait(): void
    {
        self::assertEqualsWithDelta(
            ReseauChaleurFactorResolver::AUTRE_RESEAU_CHALEUR,
            $this->resolve('2024-01-01', '9999C'),
            1e-9,
        );
        self::assertEqualsWithDelta(
            ReseauChaleurFactorResolver::AUTRE_RESEAU_FROID,
            $this->resolve('2024-01-01', '9999F'),
            1e-9,
        );
    }

    private function resolve(
        string $dateDpe,
        ?string $identifiant,
        ?string $dateArrete = null,
        ?int $ligne = null,
    ): float {
        $entree = '';
        if ($identifiant !== null) {
            $entree .= "<identifiant_reseau_chaleur>$identifiant</identifiant_reseau_chaleur>";
        }
        if ($dateArrete !== null) {
            $entree .= "<date_arrete_reseau_chaleur>$dateArrete</date_arrete_reseau_chaleur>";
        }
        if ($ligne !== null) {
            $entree .= "<tv_reseau_chaleur_id>$ligne</tv_reseau_chaleur_id>";
        }

        $document = new DOMDocument();
        $document->loadXML(<<<XML
            <dpe><administratif><date_etablissement_dpe>$dateDpe</date_etablissement_dpe></administratif>
            <logement><installation_chauffage_collection><installation_chauffage>
            <generateur_chauffage_collection><generateur_chauffage>
            <donnee_entree>$entree</donnee_entree>
            </generateur_chauffage></generateur_chauffage_collection>
            </installation_chauffage></installation_chauffage_collection></logement></dpe>
        XML);

        $generateur = $document->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $generateur);

        $context = new CalculationContext(
            $document,
            new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );

        return ReseauChaleurFactorResolver::resolve($generateur, new NodeAccessor($document), $context);
    }
}
