<?php

declare(strict_types=1);

namespace CalculDpePHP\Collectif;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use DOMElement;

/**
 * Chauffage collectif alimentant plusieurs immeubles (§17.3).
 *
 * §17.3 : "Pour un groupe d'immeubles alimenté par une installation collective unique,
 * l'installation de chauffage est traitée comme un réseau de chaleur local."
 *
 * Conséquence de calcul : le calcul des émissions GES se fait à partir des énergies
 * réellement consommées par les générateurs (pas de facteur d'émission réseau national).
 *
 * Dans notre moteur, ce cas est géré en amont par le diagnostiqueur qui renseigne
 * l'installation comme "réseau de chaleur" dans l'XML. Les Calculators existants
 * (ConsoEcsCalculator, ConsoChCalculator via Strategy) gèrent déjà les réseaux de chaleur.
 * Ce Calculator est donc un no-op : il documente l'intention §17.3 mais ne modifie pas le DOM.
 *
 * Pour un traitement complet, il faudrait :
 *   1. Détecter que l'installation est collective multi-immeubles (pas de flag XML standardisé)
 *   2. Forcer enum_type_energie_id = réseau de chaleur local pour le calcul GES
 *
 * @spec-section 17.3
 * @spec-pages   119
 * @spec-source  resources/specsplitted/17-collectif/03-chauffage-multi-immeuble.md
 * @xml-input    (aucun — détection multi-immeuble non disponible dans le format XML ADEME)
 * @xml-output   (aucun)
 * @depends-on   \CalculDpePHP\Collectif\DpeImmeubleCalculator
 * @tables       (aucune)
 */
final class ChauffageMultiImmeubleCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return ['\CalculDpePHP\Collectif\DpeImmeubleCalculator'];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return false; // §17.3 non détectable depuis le XML seul — no-op.
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        // No-op : le calcul multi-immeuble se fait en traitant l'installation comme
        // un réseau de chaleur local, ce qui est déjà le cas si le diagnostiqueur
        // renseigne correctement le type d'énergie dans l'XML d'entrée.
    }
}
