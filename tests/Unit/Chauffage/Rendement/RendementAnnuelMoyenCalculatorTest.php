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

    /** §17.1.2 p.108-109 : pondération par la population des sous-ensembles. */
    #[\PHPUnit\Framework\Attributes\DataProvider('sousEnsemblesPourPuissanceMoyenne')]
    public function testLaPuissanceMoyenneInclutLesLogementsSansCombustion(array $groupes, float $puissanceMoyenne): void
    {
        $controle = $this->rendementAvecSousEnsembles([[97, 24000, 400]], 400 * 24000 / $puissanceMoyenne);
        foreach ([$groupes, array_reverse($groupes)] as $ordre) {
            $actuel = $this->rendementAvecSousEnsembles($ordre, 400.0);
            self::assertEqualsWithDelta($controle[0], $actuel[0], 1e-12);
            self::assertEqualsWithDelta($controle[1], $actuel[1], 1e-12);
        }
    }

    public static function sousEnsemblesPourPuissanceMoyenne(): iterable
    {
        yield 'deux chaudieres et logements electriques' => [[[97, 24000, 200], [97, 12000, 100], [100, 8000, 100]], 15000.0];
        yield 'une chaudiere et logements electriques' => [[[97, 24000, 240], [100, 8000, 160]], 14400.0];
        yield 'deux tailles de sous ensembles combustion' => [[[97, 24000, 300], [97, 12000, 100]], 21000.0];
        yield 'surfaces absentes repli arithmetique' => [[[97, 24000, null], [97, 12000, null]], 18000.0];
        yield 'une surface absente repli integral' => [[[97, 24000, 300], [97, 12000, null]], 18000.0];
        yield 'surface nulle repli integral' => [[[97, 24000, 0], [97, 12000, 100]], 18000.0];
        yield 'surface negative repli integral' => [[[97, 24000, -1], [97, 12000, 100]], 18000.0];
    }

    /** @return array{float, float} */
    private function rendementAvecSousEnsembles(array $groupes, float $gv): array
    {
        $installations = '';
        $premier24 = false;
        foreach ($groupes as [$type, $puissance, $surface]) {
            $reference = $puissance === 24000 && !$premier24 ? 'cible' : 'autre-' . strlen($installations);
            $premier24 = $premier24 || $puissance === 24000;
            $installations .= '<installation_chauffage><donnee_entree><enum_type_installation_id>1</enum_type_installation_id>'
                . '<enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id><rdim>1</rdim>'
                . ($surface === null ? '' : '<surface_chauffee>' . $surface . '</surface_chauffee>')
                . '</donnee_entree><generateur_chauffage_collection><generateur_chauffage><donnee_entree>'
                . '<reference>' . $reference . '</reference><enum_type_generateur_ch_id>' . $type . '</enum_type_generateur_ch_id><enum_type_energie_id>'
                . ($type === 100 ? '1' : '2') . '</enum_type_energie_id></donnee_entree><donnee_intermediaire><pn>' . $puissance . '</pn>'
                . '<rpn>0.95</rpn><rpint>0.98</rpint><qp0>240</qp0><temp_fonc_30>35</temp_fonc_30><temp_fonc_100>55</temp_fonc_100>'
                . '</donnee_intermediaire></generateur_chauffage></generateur_chauffage_collection></installation_chauffage>';
        }
        $document = new DOMDocument();
        $document->loadXML('<logement><caracteristique_generale><enum_methode_application_dpe_log_id>10</enum_methode_application_dpe_log_id>'
            . '<nombre_appartement>4</nombre_appartement><surface_habitable_immeuble>400</surface_habitable_immeuble>'
            . '</caracteristique_generale><installation_chauffage_collection>' . $installations . '</installation_chauffage_collection></logement>');
        $context = new CalculationContext($document, new TableRepository(self::PROJECT_ROOT . '/resources/tables'), zoneClimatique: '1', classeAltitude: '1');
        $context->set('chauffage.gv', $gv);
        $generator = (new \DOMXPath($document))->query('//generateur_chauffage[donnee_entree/reference="cible"]')->item(0);
        (new RendementAnnuelMoyenCalculator())->calculate($generator, $context);

        return [
            (float)$generator->getElementsByTagName('rendement_generation')->item(0)?->textContent,
            $context->get('chauffage.rendement_generation_depensier')['cible'],
        ];
    }

    /**
     * §13.2.2.2 p.88 puis §13.2.4 p.92 : sans pertes fixes, le rapport
     * QPx/Px est constant. Le résultat ne dépend ni de Pn ni de GV et ne
     * requiert pas Rpint, absent de la table des radiateurs à gaz.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('radiateursGaz')]
    public function testRadiateurGazUtiliseSaLoiDePertes(int $type, float $rpn, float $pn, float $gv): void
    {
        $document = new DOMDocument();
        $document->loadXML(<<<XML
<logement><installation_chauffage_collection><installation_chauffage>
  <generateur_chauffage_collection><generateur_chauffage>
    <donnee_entree><enum_type_generateur_ch_id>$type</enum_type_generateur_ch_id>
      <enum_type_energie_id>2</enum_type_energie_id><reference>radiateur</reference></donnee_entree>
    <donnee_intermediaire><pn>$pn</pn><rpn>$rpn</rpn></donnee_intermediaire>
  </generateur_chauffage></generateur_chauffage_collection>
</installation_chauffage></installation_chauffage_collection></logement>
XML);
        $context = new CalculationContext($document, new TableRepository(self::PROJECT_ROOT . '/resources/tables'));
        $context->set('chauffage.gv', $gv);

        (new RendementAnnuelMoyenCalculator())->calculate(
            $document->getElementsByTagName('generateur_chauffage')->item(0), $context,
        );

        $attendu = 1.11 / (1.0 + 1.04 * (1.11 / $rpn - 1.0));
        $rendement = (float)$document->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        self::assertEqualsWithDelta($attendu, $rendement, 1e-12);
        self::assertEqualsWithDelta($attendu, $context->get('chauffage.rendement_generation_depensier')['radiateur'], 1e-12);
        self::assertLessThan($rpn, $rendement);
    }

    public static function radiateursGaz(): array
    {
        return [
            'ancien moins de 5 kW' => [53, 0.70, 4000.0, 100.0],
            'ancien au moins 5 kW' => [53, 0.73, 12595.7, 1000.0],
            'ancien autre charge' => [53, 0.73, 19144.8, 50.0],
            'recent moins de 5 kW' => [54, 0.80, 4000.0, 100.0],
            'recent au moins 5 kW' => [54, 0.82, 6000.0, 200.0],
        ];
    }

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

    /**
     * §17.1.4.2 p.110 : dans un DPE d'immeuble à chauffage individuel, tout
     * se calcule à l'échelle de l'appartement « moyen ». Le profil de charge
     * du générateur voit donc GV / Nblgt, y compris lorsque l'installation
     * est décrite une seule fois pour tout l'immeuble.
     *
     * Valeurs de 2659E2595696Y : deux logements, GV 104 W/K, chaudière gaz à
     * condensation de 24 kW. Seul `nombre_appartement` change d'une branche
     * à l'autre.
     */
    public function testLeProfilDeChargeVoitLeGvDeLAppartementMoyen(): void
    {
        $rendements = [];
        foreach ([1, 2] as $nblgt) {
            $document = new DOMDocument();
            $document->loadXML(sprintf(<<<'XML'
<logement>
  <caracteristique_generale>
    <enum_methode_application_dpe_log_id>10</enum_methode_application_dpe_log_id>
    <nombre_appartement>%d</nombre_appartement>
    <surface_habitable_immeuble>104.53</surface_habitable_immeuble>
    <enum_zone_climatique_id>5</enum_zone_climatique_id>
    <enum_classe_altitude_id>1</enum_classe_altitude_id>
  </caracteristique_generale>
  <installation_chauffage_collection><installation_chauffage>
    <donnee_entree>
      <enum_type_installation_id>1</enum_type_installation_id>
      <enum_methode_calcul_conso_id>4</enum_methode_calcul_conso_id>
      <nombre_logement_echantillon>1</nombre_logement_echantillon>
      <rdim>1</rdim><surface_chauffee>104.53</surface_chauffee>
    </donnee_entree>
    <generateur_chauffage_collection><generateur_chauffage>
      <donnee_entree>
        <enum_type_generateur_ch_id>96</enum_type_generateur_ch_id>
        <reference>gen</reference><enum_type_energie_id>2</enum_type_energie_id>
      </donnee_entree>
      <donnee_intermediaire>
        <pn>24000</pn><rpn>0.95</rpn><rpint>0.98</rpint><qp0>240</qp0><pveil>0</pveil>
        <temp_fonc_30>35</temp_fonc_30><temp_fonc_100>55</temp_fonc_100>
      </donnee_intermediaire>
    </generateur_chauffage></generateur_chauffage_collection>
  </installation_chauffage></installation_chauffage_collection>
</logement>
XML, $nblgt));
            $context = new CalculationContext(
                document: $document,
                tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
            );
            $context->set('chauffage.gv', 104.0);

            (new RendementAnnuelMoyenCalculator())->calculate(
                $document->getElementsByTagName('generateur_chauffage')->item(0),
                $context,
            );

            $di = $document->getElementsByTagName('donnee_intermediaire')->item(0);
            $rendements[] = (float)$di->getElementsByTagName('rendement_generation')->item(0)->textContent;
        }

        // Face au GV d'un seul logement sur deux, la chaudière est deux fois
        // moins chargée : son rendement annuel baisse.
        self::assertLessThan($rendements[0], $rendements[1]);
    }
}
