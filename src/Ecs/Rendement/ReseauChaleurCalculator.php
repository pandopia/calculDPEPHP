<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs\Rendement;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement ECS via réseau de chaleur (§14.3 p.95).
 *
 * Les rendements de stockage et de génération sont remplacés par le rendement
 * d'échange de la sous-station :
 *   - installation isolée  : Rs × Rg = 0.9
 *   - sinon (non isolée)   : Rs × Rg = 0.75
 *
 * On stocke directement le produit dans `rendement_generation_stockage`,
 * conformément au vocabulaire XML ADEME.
 *
 * @spec-section 14.3
 * @spec-pages   95
 * @spec-source  resources/specsplitted/14-rendement-ecs-generateurs/03-reseau-chaleur.md
 * @xml-input    generateur_ecs.donnee_entree.enum_type_generateur_ecs_id
 * @xml-output   generateur_ecs.donnee_intermediaire.rendement_generation_stockage
 * @depends-on   aucun
 * @tables       (aucune — deux valeurs directement de la spec p.95)
 */
final class ReseauChaleurCalculator implements CalculatorInterface
{
    /**
     * enum_type_generateur_ecs_id → Rs × Rg (§14.3 p.95).
     *
     * Le § distingue l'installation isolée (0,9) de celle qui ne l'est pas
     * (0,75), et le XSD ne porte ce qualificatif que sur les réseaux urbains
     * 72/73 (et leurs équivalents « logement neuf » 107/108). Les types
     * 74-77 et 134, « chaudière(s) … multi bâtiment modélisée comme un réseau
     * de chaleur », désignent un générateur d'un bâtiment voisin : il n'y a pas
     * de réseau primaire non isolé à pénaliser, donc l'échange isolé s'applique.
     * Le type 119, « non répertorié ou inconnu », garde la valeur pénalisante.
     */
    private const RG_BY_TYPE = [
        72  => 0.75,  // réseau de chaleur non isolé (logement existant)
        73  => 0.90,  // réseau de chaleur isolé (logement existant)
        74  => 0.90,  // chaudière(s) bois multi bâtiment en réseau de chaleur
        75  => 0.90,  // chaudière(s) fioul multi bâtiment en réseau de chaleur
        76  => 0.90,  // chaudière(s) gaz multi bâtiment en réseau de chaleur
        77  => 0.90,  // pompe(s) à chaleur multi bâtiment en réseau de chaleur
        107 => 0.75,  // réseau de chaleur non isolé (logement neuf)
        108 => 0.90,  // réseau de chaleur isolé (logement neuf)
        119 => 0.75,  // réseau de chaleur non répertorié ou inconnu (XSD 2.6)
        134 => 0.90,  // chaudière(s) charbon multi bâtiment en réseau de chaleur
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'generateur_ecs';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $typeId   = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $node);

        if ($typeId === null || !isset(self::RG_BY_TYPE[$typeId])) {
            return;
        }

        $rg = self::RG_BY_TYPE[$typeId];
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_generation_stockage', $rg);
    }
}
