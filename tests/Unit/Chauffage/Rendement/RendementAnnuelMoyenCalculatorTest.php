<?php

declare(strict_types=1);

namespace Tests\Unit\Chauffage\Rendement;

use CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;
use PHPUnit\Framework\TestCase;

final class RendementAnnuelMoyenCalculatorTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../..';

    public function testComputesSeparateDepensierLoadProfileAt21Degrees(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement>
  <caracteristique_generale>
    <enum_methode_application_dpe_log_id>2</enum_methode_application_dpe_log_id>
    <enum_zone_climatique_id>5</enum_zone_climatique_id>
    <enum_classe_altitude_id>1</enum_classe_altitude_id>
  </caracteristique_generale>
  <installation_chauffage><generateur_chauffage_collection><generateur_chauffage>
    <donnee_entree>
      <enum_type_generateur_ch_id>93</enum_type_generateur_ch_id>
      <reference>gen-1</reference>
      <enum_type_energie_id>2</enum_type_energie_id>
      <presence_regulation_combustion>1</presence_regulation_combustion>
    </donnee_entree>
    <donnee_intermediaire>
      <pn>23000</pn><rpn>0.895425917540264</rpn><rpint>0.895425917540264</rpint>
      <qp0>324.442078172763</qp0><pveil>0</pveil>
      <temp_fonc_30>45.5</temp_fonc_30><temp_fonc_100>70</temp_fonc_100>
    </donnee_intermediaire>
  </generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $context->set('chauffage.gv', 102.070658177748);

        (new RendementAnnuelMoyenCalculator())->calculate(
            $document->getElementsByTagName('generateur_chauffage')->item(0),
            $context,
        );

        $di = $document->getElementsByTagName('donnee_intermediaire')->item(0);
        $nominal = (float)$di->getElementsByTagName('rendement_generation')->item(0)->textContent;
        $depensier = (float)$context->get('chauffage.rendement_generation_depensier')['gen-1'];
        self::assertEqualsWithDelta(0.74299, $nominal, 1e-4);
        self::assertEqualsWithDelta(0.75257, $depensier, 1e-4);
        self::assertGreaterThan($nominal, $depensier);
    }

    /**
     * §13.2.1.2 : le taux de charge se juge sur la puissance installée. Deux
     * chaudières identiques face au même GV sont chacune moins chargée qu'une
     * seule, et leur rendement annuel s'en ressent — ajouter un second
     * générateur doit donc changer le rendement du premier.
     */
    public function testLeTauxDeChargeDependDeLaPuissanceInstalleeTotale(): void
    {
        $seul = $this->rendement(1);
        $deux = $this->rendement(2);

        self::assertNotEqualsWithDelta($seul, $deux, 1e-6);
        self::assertLessThan($seul, $deux, 'À GV égal, deux chaudières tournent plus bas et rendent moins.');
    }

    private function rendement(int $nombreGenerateurs): float
    {
        $gen = <<<'XML'
        <generateur_chauffage>
          <donnee_entree>
            <enum_type_generateur_ch_id>93</enum_type_generateur_ch_id>
            <reference>gen-%d</reference>
            <enum_type_energie_id>2</enum_type_energie_id>
            <presence_regulation_combustion>1</presence_regulation_combustion>
          </donnee_entree>
          <donnee_intermediaire>
            <pn>23000</pn><rpn>0.895425917540264</rpn><rpint>0.895425917540264</rpint>
            <qp0>324.442078172763</qp0><pveilleuse>0</pveilleuse>
            <temp_fonc_30>45.5</temp_fonc_30><temp_fonc_100>70</temp_fonc_100>
          </donnee_intermediaire>
        </generateur_chauffage>
        XML;

        $gens = '';
        for ($i = 1; $i <= $nombreGenerateurs; $i++) {
            $gens .= sprintf($gen, $i);
        }

        $document = new DOMDocument();
        $document->loadXML(<<<XML
        <logement>
          <caracteristique_generale>
            <enum_methode_application_dpe_log_id>2</enum_methode_application_dpe_log_id>
            <enum_zone_climatique_id>5</enum_zone_climatique_id>
            <enum_classe_altitude_id>1</enum_classe_altitude_id>
          </caracteristique_generale>
          <installation_chauffage><generateur_chauffage_collection>$gens</generateur_chauffage_collection></installation_chauffage>
        </logement>
        XML);

        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $context->set('chauffage.gv', 102.070658177748);

        (new RendementAnnuelMoyenCalculator())->calculate(
            $document->getElementsByTagName('generateur_chauffage')->item(0),
            $context,
        );

        return (float) $document
            ->getElementsByTagName('generateur_chauffage')->item(0)
            ->getElementsByTagName('rendement_generation')->item(0)->textContent;
    }

    /**
     * §13.2.2.1 p.89 : le point de fonctionnement d'un générateur d'air chaud
     * est à 50 % de charge, et QP0 y vaut `Pn × (1,75 − 0,55 logPn) / 100`,
     * un **pourcentage** de la puissance nominale comme pour les chaudières.
     *
     * Le cas reprend 2401E2888143P : Pn = 18 kW, Rpn = 77 %, Rpint = 74 %.
     */
    public function testGenerateurAirChaudSuitLeProfilA50PourCent(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement>
  <caracteristique_generale>
    <enum_methode_application_dpe_log_id>1</enum_methode_application_dpe_log_id>
    <enum_zone_climatique_id>5</enum_zone_climatique_id>
    <enum_classe_altitude_id>1</enum_classe_altitude_id>
  </caracteristique_generale>
  <installation_chauffage><generateur_chauffage_collection><generateur_chauffage>
    <donnee_entree>
      <enum_type_generateur_ch_id>50</enum_type_generateur_ch_id>
      <reference>gen-air-chaud</reference>
      <enum_type_energie_id>3</enum_type_energie_id>
      <tv_generateur_combustion_id>69</tv_generateur_combustion_id>
      <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id>
    </donnee_entree>
    <donnee_intermediaire>
      <pn>18000</pn><rpn>0.77</rpn><rpint>0.74</rpint>
      <qp0>190.728021994773</qp0><pveil>0</pveil>
    </donnee_intermediaire>
  </generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $context->set('chauffage.gv', 150.0);

        (new RendementAnnuelMoyenCalculator())->calculate(
            $document->getElementsByTagName('generateur_chauffage')->item(0),
            $context,
        );

        $di = $document->getElementsByTagName('donnee_intermediaire')->item(0);
        $rg = (float)$di->getElementsByTagName('rendement_generation')->item(0)->textContent;

        // Un générateur d'air chaud ancien tient son rendement près de ses
        // 77 % nominaux ; le profil des chaudières standard, calé sur 30 % de
        // charge et corrigé en température, l'effondrait sous 0,3.
        self::assertGreaterThan(0.60, $rg);
        self::assertLessThan(0.80, $rg);
    }

    /**
     * §13.2.2.1 p.89 : « QP0 = Pn × (1,75 − 0,55 logPn) / 100 ». Pour
     * Pn = 18 kW la table doit rendre 190,7 W, pas 19 073 W.
     */
    public function testQp0DUnGenerateurAirChaudEstUnPourcentage(): void
    {
        $table = (new TableRepository(self::PROJECT_ROOT . '/resources/tables'))
            ->load('chauffage/tv_generateur_combustion');

        $ligne = ($table[69])(18.0, 2.5, -0.8);

        self::assertEqualsWithDelta(190.728021994773, $ligne['qp0'], 1e-6);
        self::assertEqualsWithDelta(0.77, $ligne['rpn'], 1e-9);
        self::assertEqualsWithDelta(0.74, $ligne['rpint'], 1e-9);
    }

    /**
     * §13.2.2.3 p.90 donne aux chaudières bois les formules de §13.2.2.1 :
     * même point de fonctionnement à 50 % de charge, mêmes QP50 et QP100 sans
     * correction de température. Le cas reprend 2400E0180612T.
     */
    public function testChaudiereBoisSuitLeMemeProfilQueLAirChaud(): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<'XML'
<logement>
  <caracteristique_generale>
    <enum_methode_application_dpe_log_id>1</enum_methode_application_dpe_log_id>
    <enum_zone_climatique_id>5</enum_zone_climatique_id>
    <enum_classe_altitude_id>1</enum_classe_altitude_id>
  </caracteristique_generale>
  <installation_chauffage><generateur_chauffage_collection><generateur_chauffage>
    <donnee_entree>
      <enum_type_generateur_ch_id>74</enum_type_generateur_ch_id>
      <reference>gen-bois</reference>
      <enum_type_energie_id>5</enum_type_energie_id>
      <enum_methode_saisie_carac_sys_id>1</enum_methode_saisie_carac_sys_id>
    </donnee_entree>
    <donnee_intermediaire>
      <pn>24000</pn><rpn>0.87</rpn><rpint>0.9</rpint>
      <qp0>120</qp0><pveil>0</pveil>
    </donnee_intermediaire>
  </generateur_chauffage></generateur_chauffage_collection></installation_chauffage>
</logement>
XML);
        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        $context->set('chauffage.gv', 150.0);

        (new RendementAnnuelMoyenCalculator())->calculate(
            $document->getElementsByTagName('generateur_chauffage')->item(0),
            $context,
        );

        $di = $document->getElementsByTagName('donnee_intermediaire')->item(0);
        $rg = (float)$di->getElementsByTagName('rendement_generation')->item(0)->textContent;

        // Une chaudière granulés récente reste proche de ses 87 % nominaux.
        self::assertGreaterThan(0.78, $rg);
        self::assertLessThan(0.90, $rg);
    }
}
