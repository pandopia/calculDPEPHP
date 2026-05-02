<?php

declare(strict_types=1);

namespace CalculDpe\Ecs\Rendement;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement ECS via réseau de chaleur (§14.3 p.95).
 *
 * Les rendements de stockage et de génération sont remplacés par le rendement
 * d'échange de la sous-station :
 *   - installation isolée  : Rs × Rg = 0.9
 *   - sinon (non isolée)   : Rs × Rg = 0.75
 *
 * On stocke le produit Rs × Rg dans `rendement_generation` et on laisse
 * `rendement_stockage` à 1.0 (défaut de ConsoEcsCalculator).
 *
 * @spec-section 14.3
 * @spec-pages   95
 * @spec-source  resources/specsplitted/14-rendement-ecs-generateurs/03-reseau-chaleur.md
 * @xml-input    generateur_ecs.donnee_entree.enum_type_generateur_ecs_id
 * @xml-output   generateur_ecs.donnee_intermediaire.rendement_generation
 * @depends-on   aucun
 * @tables       (aucune — deux valeurs directement de la spec p.95)
 */
final class ReseauChaleurCalculator implements CalculatorInterface
{
    // enum_type_generateur_ecs_id → réseau de chaleur isolé (=0.9) ou non (=0.75)
    private const RG_BY_TYPE = [
        72  => 0.75,  // réseau de chaleur non isolé (logement existant)
        73  => 0.90,  // réseau de chaleur isolé (logement existant)
        107 => 0.75,  // réseau de chaleur non isolé (logement neuf)
        108 => 0.90,  // réseau de chaleur isolé (logement neuf)
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
        $accessor->setChildValue($di, 'rendement_generation', $rg);
    }
}
