<?php

declare(strict_types=1);

namespace Tests\Unit\Intermittence;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Intermittence\IntermittenceCalculator;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class IntermittenceCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';
    private const TOL = 0.001;

    private function makeContext(DOMDocument $doc, int $classeInertieId = 1, int $methodeId = 1): CalculationContext
    {
        $ctx = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $ctx->set('inertie.classe_id', $classeInertieId);
        $ctx->set('logement.methode_application_dpe_log_id', $methodeId);
        return $ctx;
    }

    private function buildEmetteur(
        int $chauffageId,
        int $regulationId,
        int $equipementId,
        int $emetteurId,
    ): DOMDocument {
        $xml = <<<XML
<?xml version="1.0"?>
<emetteur_chauffage>
  <donnee_entree>
    <enum_type_chauffage_id>{$chauffageId}</enum_type_chauffage_id>
    <enum_type_regulation_id>{$regulationId}</enum_type_regulation_id>
    <enum_equipement_intermittence_id>{$equipementId}</enum_equipement_intermittence_id>
    <enum_type_emission_distribution_id>{$emetteurId}</enum_type_emission_distribution_id>
  </donnee_entree>
</emetteur_chauffage>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        return $doc;
    }

    /**
     * bat_pre2026 : immeuble collectif chauffage individuel divisé,
     * avec régu ppp, par pièce avec min temp (enum 4), radiateur élec (enum 3),
     * inertie légère → i0 = 0.86
     */
    public function testBatPre2026_i0(): void
    {
        $doc = $this->buildEmetteur(1, 2, 4, 3);
        $emetteur = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        // methodeId=6 = dpe immeuble collectif chauffage individuel
        $ctx = $this->makeContext($doc, classeInertieId: 1, methodeId: 6);
        (new IntermittenceCalculator())->calculate($emetteur, $ctx);
        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $i0 = (float)$di->getElementsByTagName('i0')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.86, $i0, self::TOL);
    }

    /**
     * bat_post2026 : immeuble collectif chauffage collectif,
     * central, avec régu ppp, central collectif (enum 6), radiateur (enum 37),
     * sans comptage → i0 = 1.01
     */
    public function testBatPost2026_i0(): void
    {
        $doc = $this->buildEmetteur(2, 2, 6, 37);
        $emetteur = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        $ctx = $this->makeContext($doc, classeInertieId: 1, methodeId: 6);
        (new IntermittenceCalculator())->calculate($emetteur, $ctx);
        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $i0 = (float)$di->getElementsByTagName('i0')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.01, $i0, self::TOL);
    }

    public function testMaison_DiviseCentral_Absent(): void
    {
        // Maison, divisé, sans régu ppp, absent, radiateur légère → 0.84
        // Mapping XSD : id=4 = légère.
        $doc = $this->buildEmetteur(1, 1, 1, 3);
        $emetteur = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        $ctx = $this->makeContext($doc, classeInertieId: 4, methodeId: 1);
        (new IntermittenceCalculator())->calculate($emetteur, $ctx);
        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $i0 = (float)$di->getElementsByTagName('i0')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.84, $i0, self::TOL);
    }

    public function testMaison_CentralSansRegu_Radiateur_Lourd(): void
    {
        // Maison, central, sans régu ppp, absent, radiateur, inertie lourde → 0.93
        // Mapping XSD : id=2 = lourde.
        $doc = $this->buildEmetteur(2, 1, 1, 3);
        $emetteur = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        $ctx = $this->makeContext($doc, classeInertieId: 2, methodeId: 1);
        (new IntermittenceCalculator())->calculate($emetteur, $ctx);
        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $i0 = (float)$di->getElementsByTagName('i0')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.93, $i0, self::TOL);
    }

    public function testPlancherChauffant_Collectif_PppAvec_AvecMin(): void
    {
        // Immeuble individuel, central, avec ppp, plancher (enum 11), central avec min (enum 3) → 0.93
        $doc = $this->buildEmetteur(2, 2, 3, 11);
        $emetteur = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        $ctx = $this->makeContext($doc, 1, 6);
        (new IntermittenceCalculator())->calculate($emetteur, $ctx);
        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $i0 = (float)$di->getElementsByTagName('i0')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.93, $i0, self::TOL);
    }

    public function testAirSouffle_CollectifCollectif_SansRegu(): void
    {
        // Collectif collectif, central, sans ppp, air soufflé (5), central collectif (6), sans comptage → 1.01
        $doc = $this->buildEmetteur(2, 1, 6, 5);
        $emetteur = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        $ctx = $this->makeContext($doc, 1, 6);
        (new IntermittenceCalculator())->calculate($emetteur, $ctx);
        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $i0 = (float)$di->getElementsByTagName('i0')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.01, $i0, self::TOL);
    }

    public function testPlafond_Emetteur(): void
    {
        // Maison, central, avec ppp, plafond (16), absent (1), légère → 0.88
        // Mapping XSD : id=4 = légère.
        $doc = $this->buildEmetteur(2, 2, 1, 16);
        $emetteur = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        $ctx = $this->makeContext($doc, classeInertieId: 4, methodeId: 1);
        (new IntermittenceCalculator())->calculate($emetteur, $ctx);
        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $i0 = (float)$di->getElementsByTagName('i0')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.88, $i0, self::TOL);
    }

    /**
     * Comptage individuel détecté via fiche_technique[cat=7]/sous_fiche_technique
     * avec description contenant "comptage" et valeur=1.
     *
     * Collectif collectif central sans régulation radiateur, equipement=6 :
     *   comptage absent  → i0=1.03
     *   comptage present → i0=0.95
     */
    public function testComptageIndividuelDetectedViaFicheTechnique(): void
    {
        $xml = <<<'XML'
<?xml version="1.0"?>
<dpe>
    <administratif>
        <fiche_technique_collection>
            <fiche_technique>
                <enum_categorie_fiche_technique_id>7</enum_categorie_fiche_technique_id>
                <sous_fiche_technique_collection>
                    <sous_fiche_technique>
                        <description>Présence comptage: 1</description>
                        <valeur>1</valeur>
                    </sous_fiche_technique>
                </sous_fiche_technique_collection>
            </fiche_technique>
        </fiche_technique_collection>
    </administratif>
    <logement>
        <caracteristique_generale>
            <enum_methode_application_dpe_log_id>7</enum_methode_application_dpe_log_id>
        </caracteristique_generale>
        <installation_chauffage_collection>
            <installation_chauffage>
                <emetteur_chauffage_collection>
                    <emetteur_chauffage>
                        <donnee_entree>
                            <enum_type_chauffage_id>2</enum_type_chauffage_id>
                            <enum_type_regulation_id>1</enum_type_regulation_id>
                            <enum_equipement_intermittence_id>6</enum_equipement_intermittence_id>
                            <enum_type_emission_distribution_id>33</enum_type_emission_distribution_id>
                        </donnee_entree>
                    </emetteur_chauffage>
                </emetteur_chauffage_collection>
            </installation_chauffage>
        </installation_chauffage_collection>
    </logement>
</dpe>
XML;
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $emetteur = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        $ctx = $this->makeContext($doc, classeInertieId: 3, methodeId: 7);

        (new IntermittenceCalculator())->calculate($emetteur, $ctx);

        $di = $emetteur->getElementsByTagName('donnee_intermediaire')->item(0);
        $i0 = (float)$di->getElementsByTagName('i0')->item(0)->textContent;
        $this->assertEqualsWithDelta(0.95, $i0, self::TOL,
            'Comptage individuel présent → i0 réduit (0.95 vs 1.03 absent)');
    }

    /**
     * Sans fiche_technique de comptage → comptage='absent' par défaut.
     * Collectif collectif sans comptage donne i0=1.03 (radiateur sans régu).
     */
    public function testComptageAbsentSansFicheTechnique(): void
    {
        $doc = $this->buildEmetteur(2, 1, 6, 33);
        $emetteur = $doc->getElementsByTagName('emetteur_chauffage')->item(0);
        $ctx = $this->makeContext($doc, classeInertieId: 3, methodeId: 7);

        (new IntermittenceCalculator())->calculate($emetteur, $ctx);

        $di = $doc->getElementsByTagName('donnee_intermediaire')->item(0);
        $i0 = (float)$di->getElementsByTagName('i0')->item(0)->textContent;
        $this->assertEqualsWithDelta(1.03, $i0, self::TOL);
    }
}
