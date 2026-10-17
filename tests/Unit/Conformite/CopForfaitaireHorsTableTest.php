<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * §14.2 p.95 tabule les COP par défaut des chauffe-eau thermodynamiques « si
 * les caractéristiques exactes des équipements ne peuvent pas être saisies ».
 * Le tableau est fini : trois familles × trois millésimes × deux groupes de
 * zones, soit neuf valeurs distinctes entre 2 et 2,9.
 */
final class CopForfaitaireHorsTableTest extends TestCase
{
    private function generateur(int $saisie, float $cop): DOMDocument
    {
        $document = new DOMDocument();
        $document->loadXML(<<<XML
<dpe><logement><installation_ecs_collection><installation_ecs><generateur_ecs_collection><generateur_ecs>
  <donnee_entree>
    <enum_type_generateur_ecs_id>6</enum_type_generateur_ecs_id>
    <enum_methode_saisie_carac_sys_id>$saisie</enum_methode_saisie_carac_sys_id>
  </donnee_entree>
  <donnee_intermediaire><cop>$cop</cop></donnee_intermediaire>
</generateur_ecs></generateur_ecs_collection></installation_ecs></installation_ecs_collection></logement></dpe>
XML);

        return $document;
    }

    /** @return iterable<string, array{float}> */
    public static function copHorsTable(): iterable
    {
        yield '2600E0099076V' => [3.8];
        yield '2400E4097217K' => [3.2];
        yield '2593E3079342Z' => [3.07];
    }

    #[DataProvider('copHorsTable')]
    public function testUnCopForfaitaireAbsentDeLaTableEstSignale(float $cop): void
    {
        $suspects = ReferenceDefects::detect([], $this->generateur(1, $cop));

        self::assertArrayHasKey('cop', $suspects);
        self::assertStringContainsString('14.2', $suspects['cop']);
    }

    /** @return iterable<string, array{float}> */
    public static function copTabules(): iterable
    {
        foreach ([2.0, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.8, 2.9] as $cop) {
            yield (string) $cop => [$cop];
        }
    }

    /**
     * Une valeur du tableau ne déclenche rien, même lue sur la mauvaise
     * ligne : la départager demanderait de refaire le calcul, pas de lire le
     * fichier.
     */
    #[DataProvider('copTabules')]
    public function testUneValeurTabuleeNeDeclenchePas(float $cop): void
    {
        self::assertArrayNotHasKey('cop', ReferenceDefects::detect([], $this->generateur(1, $cop)));
    }

    /**
     * `enum_methode_saisie_carac_sys_id` = 6 déclare un COP lu « à partir de
     * la plaque signalétique » : il échappe au tableau par construction.
     */
    public function testUnCopSaisiEchappeALaRegle(): void
    {
        self::assertArrayNotHasKey('cop', ReferenceDefects::detect([], $this->generateur(6, 3.14)));
    }
}
