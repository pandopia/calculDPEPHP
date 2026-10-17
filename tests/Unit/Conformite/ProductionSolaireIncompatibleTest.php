<?php

declare(strict_types=1);

namespace Tests\Unit\Conformite;

use CalculDpePHP\Conformite\ReferenceDefects;
use DOMDocument;
use PHPUnit\Framework\TestCase;

/**
 * §9.2 p.62 et §9.6 p.65 écrivent la production solaire et la consommation
 * de chauffage sur le même produit :
 *
 *   Cch  = Bch × (1 − Fch) × Σ_i (part_i × INT_i × Ich_i)
 *   Prod = Bch ×      Fch  × Σ_i (part_i × INT_i × Ich_i)
 *
 * Tout s'élimine sauf `Prod / Cch = Fch / (1 − Fch)`. Les trois valeurs étant
 * publiées côte à côte, la contradiction se démontre sans notre moteur.
 */
final class ProductionSolaireIncompatibleTest extends TestCase
{
    private function reference(float $production, float $conso, ?float $fch): DOMDocument
    {
        $balise = $fch === null ? '' : "<fch>$fch</fch>";
        $document = new DOMDocument();
        $document->loadXML(<<<XML
<dpe><logement><installation_chauffage_collection><installation_chauffage>
  <donnee_intermediaire>
    <besoin_ch>10514.6394583436</besoin_ch>
    <conso_ch>$conso</conso_ch>
    $balise
    <production_ch_solaire>$production</production_ch_solaire>
  </donnee_intermediaire>
</installation_chauffage></installation_chauffage_collection></logement></dpe>
XML);

        return $document;
    }

    /** 2401E0569876M : 12 293,027 publiés pour 9 513,946 et fch = 0,28. */
    public function testUneProductionTroisFoisTropGrandeEstSignalee(): void
    {
        $suspects = ReferenceDefects::detect([], $this->reference(12293.027067, 9513.9460914488, 0.28));

        self::assertArrayHasKey('production_ch_solaire', $suspects);
        self::assertStringContainsString('0,3889', str_replace('.', ',', $suspects['production_ch_solaire']));
    }

    /** 2601E0370414Y publie au contraire une production trop petite. */
    public function testUneProductionTropPetiteEstSignaleeAussi(): void
    {
        $suspects = ReferenceDefects::detect([], $this->reference(5037.758, 15331.0, 0.28));
        self::assertArrayHasKey('production_ch_solaire', $suspects);
    }

    /** Le rapport imposé par la méthode ne déclenche rien. */
    public function testLeRapportImposeParLaMethodeNeDeclencheRien(): void
    {
        // Fch / (1 − Fch) = 0,28 / 0,72 = 0,388889.
        $conso = 9513.9460914488;
        $suspects = ReferenceDefects::detect([], $this->reference($conso * 0.28 / 0.72, $conso, 0.28));
        self::assertArrayNotHasKey('production_ch_solaire', $suspects);
    }

    /** Une tolérance de 1 % absorbe les arrondis de sérialisation. */
    public function testUnEcartDeArrondiNeDeclenchePas(): void
    {
        $conso = 9513.9460914488;
        $suspects = ReferenceDefects::detect([], $this->reference($conso * 0.28 / 0.72 * 1.005, $conso, 0.28));
        self::assertArrayNotHasKey('production_ch_solaire', $suspects);
    }

    /** Sans `fch` publié, la preuve n'existe pas : on ne signale rien. */
    public function testSansFchAucuneImputation(): void
    {
        $suspects = ReferenceDefects::detect([], $this->reference(12293.027067, 9513.9460914488, null));
        self::assertArrayNotHasKey('production_ch_solaire', $suspects);
    }
}
