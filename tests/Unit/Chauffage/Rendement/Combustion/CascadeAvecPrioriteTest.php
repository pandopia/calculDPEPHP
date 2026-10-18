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
 * §13.2.1.3.1 — profil de charge d'une cascade avec priorité.
 *
 * Deux chaudières identiques n'ont pas le même rendement dès lors que l'une
 * est prioritaire : la première prend `min(Prel₁ ; Tchx_dim)` à chaque point de
 * charge et la seconde le reliquat, si bien que la seconde ne fonctionne
 * qu'aux fortes charges — là où une chaudière à condensation est la plus
 * sollicitée mais aussi la mieux placée sur sa courbe de pertes.
 *
 * Les valeurs attendues sont celles que la référence `2406E1688667C` publie :
 * deux chaudières gaz à condensation de 350 kW en cascade 1/2, sur un immeuble
 * de 143 appartements et 33 602 W/K de déperditions.
 */
final class CascadeAvecPrioriteTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/../../../../..';

    /**
     * @param list<int> $priorites
     *
     * @return list<float> rendement_generation de chaque générateur
     */
    private function rendements(array $priorites): array
    {
        $generateurs = '';
        foreach ($priorites as $priorite) {
            $generateurs .= <<<XML
                <generateur_chauffage>
                  <donnee_entree>
                    <enum_type_generateur_ch_id>97</enum_type_generateur_ch_id>
                    <enum_type_energie_id>2</enum_type_energie_id>
                    <presence_regulation_combustion>1</presence_regulation_combustion>
                    <priorite_generateur_cascade>{$priorite}</priorite_generateur_cascade>
                  </donnee_entree>
                  <donnee_intermediaire>
                    <pn>350000</pn>
                    <rpn>0.965441</rpn>
                    <rpint>1.06272</rpint>
                    <qp0>1050</qp0>
                    <temp_fonc_100>80</temp_fonc_100>
                    <temp_fonc_30>38</temp_fonc_30>
                  </donnee_intermediaire>
                </generateur_chauffage>
                XML;
        }

        $document = new DOMDocument();
        $document->loadXML(<<<XML
            <?xml version="1.0"?>
            <dpe>
              <logement>
                <meteo>
                  <enum_zone_climatique_id>8</enum_zone_climatique_id>
                  <enum_classe_altitude_id>1</enum_classe_altitude_id>
                </meteo>
                <installation_chauffage_collection>
                  <installation_chauffage>
                    <donnee_entree><enum_type_installation_id>2</enum_type_installation_id></donnee_entree>
                    <generateur_chauffage_collection>{$generateurs}</generateur_chauffage_collection>
                  </installation_chauffage>
                </installation_chauffage_collection>
              </logement>
            </dpe>
            XML);

        $context = new CalculationContext(
            document: $document,
            tables: new TableRepository(self::PROJECT_ROOT . '/resources/tables'),
        );
        // Cdimref rapporte la puissance installée aux déperditions ; le GV
        // transite par le contexte, pas par le XML (§Conformité structurelle).
        $context->set('chauffage.gv', 33601.995767881);
        $calculateur = new RendementAnnuelMoyenCalculator();

        $rendements = [];
        foreach ($document->getElementsByTagName('generateur_chauffage') as $generateur) {
            self::assertInstanceOf(DOMElement::class, $generateur);
            $calculateur->calculate($generateur, $context);
            $rendements[] = (float) $generateur
                ->getElementsByTagName('rendement_generation')->item(0)?->textContent;
        }

        return $rendements;
    }

    /**
     * Les deux chaudières sont identiques au bit près ; seule la priorité les
     * sépare, et elle suffit à écarter leurs rendements de plus d'un point.
     */
    public function testLaPrioriteSepareLesRendementsDeDeuxChaudieresIdentiques(): void
    {
        [$prioritaire, $subordonne] = $this->rendements([1, 2]);

        self::assertEqualsWithDelta(0.974685, $prioritaire, 5e-7);
        self::assertEqualsWithDelta(0.985541, $subordonne, 5e-7);
    }

    /**
     * Sans hiérarchie, §13.2.1.3.2 s'applique : les deux générateurs
     * contribuent au taux de charge au prorata de leur puissance, donc
     * identiquement ici — et le profil conventionnel reste celui du système.
     */
    public function testSansHierarchieLesDeuxChaudieresPartagentLeMemeProfil(): void
    {
        [$premier, $second] = $this->rendements([1, 1]);

        self::assertEqualsWithDelta($premier, $second, 1e-12);
        self::assertNotEqualsWithDelta(0.974685, $premier, 5e-7);
    }
}
