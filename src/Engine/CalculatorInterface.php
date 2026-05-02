<?php

declare(strict_types=1);

namespace CalculDpe\Engine;

use DOMElement;

/**
 * Contrat des Calculators.
 *
 * Un Calculator est responsable d'une grandeur (ou d'un petit groupe cohérent
 * de grandeurs) calculée selon une sous-section de la spec DPE 3CL-2021.
 *
 * Chaque Calculator déclare :
 * - le tag XML auquel il s'applique (`appliesTo`),
 * - les Calculators dont il dépend (pour le tri topologique),
 * - et exécute le calcul en injectant le résultat dans le DOM (`calculate`).
 *
 * Voir CLAUDE.md > "Convention de traçabilité" pour les doc-blocs obligatoires
 * (@spec-section, @spec-pages, @xml-input, @xml-output, …) sur les implémentations.
 */
interface CalculatorInterface
{
    /**
     * Liste des classes Calculator (FQN) dont ce Calculator dépend.
     * Utilisé par CalculatorPipeline pour le tri topologique.
     *
     * @return list<class-string<CalculatorInterface>>
     */
    public function dependencies(): array;

    /**
     * Vrai si ce Calculator doit s'exécuter sur le nœud donné.
     *
     * Exemple : un UmurCalculator retourne true uniquement pour les
     * éléments `<mur>` (donnee_entree d'un mur).
     */
    public function appliesTo(DOMElement $node): bool;

    /**
     * Exécute le calcul et injecte le résultat dans le DOM.
     *
     * Les résultats sont écrits dans une balise `<donnee_intermediaire>` ou
     * `<sortie>` créée si elle n'existe pas, à l'intérieur du nœud parent.
     *
     * @param DOMElement $node Nœud sur lequel s'applique le calcul.
     * @param CalculationContext $context Contexte global (zone, période, etc.).
     */
    public function calculate(DOMElement $node, CalculationContext $context): void;

    /**
     * Identifiant unique du Calculator (par convention : FQN de la classe).
     */
    public function id(): string;
}
