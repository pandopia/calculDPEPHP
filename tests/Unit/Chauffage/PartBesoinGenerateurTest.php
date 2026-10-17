<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage;

use CalculDpePHP\Chauffage\PartBesoinGenerateur;
use CalculDpePHP\Chauffage\Strategy\InsertPoeleAppoint;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

/**
 * §15.1.1 p.97 : « dans les cas où le générateur n'assure pas 100 % du besoin,
 * seule la part du besoin qu'il couvre est prise en compte ». Les stratégies
 * de §9 publient donc leur répartition pour `AuxGenerationCalculator`.
 */
final class PartBesoinGenerateurTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../..';

    private function installation(): array
    {
        $doc = new DOMDocument();
        $doc->loadXML(<<<'XML'
<logement>
  <caracteristique_generale>
    <hsp>2.5</hsp><surface_habitable_immeuble>100</surface_habitable_immeuble>
  </caracteristique_generale>
  <installation_chauffage_collection><installation_chauffage>
    <donnee_entree>
      <enum_cfg_installation_ch_id>3</enum_cfg_installation_ch_id>
      <surface_chauffee>100</surface_chauffee><rdim>1</rdim>
    </donnee_entree>
    <emetteur_chauffage_collection>
      <emetteur_chauffage>
        <donnee_entree><surface_chauffee>100</surface_chauffee>
          <enum_lien_generateur_emetteur_id>1</enum_lien_generateur_emetteur_id></donnee_entree>
        <donnee_intermediaire><i0>1</i0><rendement_emission>1</rendement_emission>
          <rendement_distribution>1</rendement_distribution><rendement_regulation>1</rendement_regulation></donnee_intermediaire>
      </emetteur_chauffage>
      <emetteur_chauffage>
        <donnee_entree><surface_chauffee>100</surface_chauffee>
          <enum_lien_generateur_emetteur_id>2</enum_lien_generateur_emetteur_id></donnee_entree>
        <donnee_intermediaire><i0>1</i0><rendement_emission>1</rendement_emission>
          <rendement_distribution>1</rendement_distribution><rendement_regulation>1</rendement_regulation></donnee_intermediaire>
      </emetteur_chauffage>
    </emetteur_chauffage_collection>
    <generateur_chauffage_collection>
      <generateur_chauffage>
        <donnee_entree><enum_lien_generateur_emetteur_id>1</enum_lien_generateur_emetteur_id></donnee_entree>
        <donnee_intermediaire><rendement_generation>0.9</rendement_generation></donnee_intermediaire>
      </generateur_chauffage>
      <generateur_chauffage>
        <donnee_entree><enum_lien_generateur_emetteur_id>2</enum_lien_generateur_emetteur_id></donnee_entree>
        <donnee_intermediaire><rendement_generation>0.5</rendement_generation></donnee_intermediaire>
      </generateur_chauffage>
    </generateur_chauffage_collection>
  </installation_chauffage></installation_chauffage_collection>
</logement>
XML);
        $context = new CalculationContext(
            document: $doc,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $context->set('chauffage.besoin_ch', 1000.0);
        $context->set('chauffage.besoin_ch_depensier', 1200.0);
        $context->set('chauffage.gv', 100.0);

        $node = $doc->getElementsByTagName('installation_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $node);

        return [$doc, $node, $context];
    }

    /**
     * §9.3 p.62 affecte 0,75 au système principal et 0,25 à l'appoint : ce
     * sont les parts que §15.1.1 demande, et leur somme fait exactement 1 —
     * le besoin n'est plus compté une fois par générateur.
     */
    public function testLInsertEtSonPrincipalSePartagentLeBesoin(): void
    {
        [, $node, $context] = $this->installation();

        (new InsertPoeleAppoint())->calculate($node, $context);

        $parts = (array) $context->get(PartBesoinGenerateur::CLE_CONTEXTE, []);
        self::assertCount(2, $parts);
        self::assertEqualsWithDelta(1.0, array_sum($parts), 1e-9);
        self::assertContains(0.75, $parts);
        self::assertContains(0.25, $parts);
    }
}
