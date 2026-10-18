<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement\Combustion;

use CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

/**
 * §17.1.4.2 — GV de l'appartement « moyen » servant de base à Cdimref.
 *
 * Un immeuble à chauffage individuel peut décrire **plus de systèmes que
 * d'appartements** : `2400E0575636Z` porte sept chaudières pour six
 * logements. Diviser le GV par le seul `nombre_appartement` attribue alors à
 * chaque chaudière plus de déperditions qu'elle n'en dessert, ce qui abaisse
 * Cdimref, relève le taux de charge et gonfle le rendement — de 0,775 % sur ce
 * fichier, qui se propage ensuite à toute la chaîne de consommation.
 */
final class GvParSystemeTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../../..';

    /** Rendement obtenu avec `$systemes` installations individuelles pour `$appartements` logements. */
    private function rendement(int $systemes, int $appartements): float
    {
        $installations = str_repeat(
            <<<'XML'
            <installation_chauffage>
              <donnee_entree>
                <enum_type_installation_id>1</enum_type_installation_id>
                <enum_methode_calcul_conso_id>2</enum_methode_calcul_conso_id>
                <rdim>1</rdim>
              </donnee_entree>
              <generateur_chauffage_collection>
                <generateur_chauffage>
                  <donnee_entree>
                    <enum_type_generateur_ch_id>97</enum_type_generateur_ch_id>
                    <enum_type_energie_id>2</enum_type_energie_id>
                  </donnee_entree>
                  <donnee_intermediaire>
                    <pn>24000</pn><rpn>0.93</rpn><rpint>0.98</rpint><qp0>120</qp0>
                    <temp_fonc_100>70</temp_fonc_100><temp_fonc_30>40</temp_fonc_30>
                  </donnee_intermediaire>
                </generateur_chauffage>
              </generateur_chauffage_collection>
            </installation_chauffage>
            XML,
            $systemes,
        );

        $document = new DOMDocument();
        $document->loadXML(<<<XML
            <?xml version="1.0"?>
            <dpe><logement>
              <caracteristique_generale>
                <enum_methode_application_dpe_log_id>6</enum_methode_application_dpe_log_id>
                <nombre_appartement>{$appartements}</nombre_appartement>
              </caracteristique_generale>
              <meteo><enum_zone_climatique_id>2</enum_zone_climatique_id><enum_classe_altitude_id>1</enum_classe_altitude_id></meteo>
              <installation_chauffage_collection>{$installations}</installation_chauffage_collection>
            </logement></dpe>
            XML);

        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $context->set('chauffage.gv', 1200.0);

        $generateur = $document->getElementsByTagName('generateur_chauffage')->item(0);
        self::assertInstanceOf(DOMElement::class, $generateur);
        (new RendementAnnuelMoyenCalculator())->calculate($generateur, $context);

        return (float) $generateur->getElementsByTagName('rendement_generation')->item(0)?->textContent;
    }

    /**
     * Sept chaudières pour six logements : chacune dessert un sixième de
     * moins, donc fonctionne plus chargée, donc mieux — le rendement doit
     * différer de celui qu'on obtient en comptant six systèmes.
     */
    public function testPlusDeSystemesQueDAppartementsResserreLeProfil(): void
    {
        self::assertNotEqualsWithDelta($this->rendement(6, 6), $this->rendement(7, 6), 1e-9);
    }

    /**
     * Le diviseur est borné par le nombre de logements : moins de systèmes
     * que d'appartements ne doit **rien** changer, un GV par système
     * supérieur au GV par logement n'ayant pas de sens ici. La règle non
     * bornée coûtait 537 écarts sur 23 cas du corpus.
     */
    public function testMoinsDeSystemesQueDAppartementsNeChangeRien(): void
    {
        self::assertEqualsWithDelta($this->rendement(6, 6), $this->rendement(3, 6), 1e-12);
    }
}
