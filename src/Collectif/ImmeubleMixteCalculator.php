<?php

declare(strict_types=1);

namespace CalculDpe\Collectif;

use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Engine\CalculationContext;
use DOMElement;

/**
 * Immeuble collectif mixte (logements + locaux tertiaires) — §17.4.
 *
 * §17.4 : "Dans le cas où des locaux tertiaires sont présents au sein de l'immeuble
 * à usage principal d'habitation, et que ces locaux sont chauffés par l'installation
 * collective de chauffage de l'immeuble, le calcul du besoin de chauffage de
 * l'immeuble sera fait pour l'ensemble de la surface des logements et des locaux
 * tertiaires, afin que les caractéristiques de l'installation collective soient les bons.
 * Une fois les caractéristiques de l'installation calculées, le besoin de chauffage
 * de l'immeuble sera ramené aux seuls logements."
 *
 * Algorithme §17.4 :
 *   1. GV_total = GV(logements + tertiaires)  → sert à dimensionner l'installation
 *   2. BV_total = GV_total × Σ DH × (1-F_total) / 1000
 *   3. Bch_total = BV_total - pertes_recup
 *   4. Cch_installation = f(Bch_total, caractéristiques générateur)
 *   5. Bch_logements_seulement = ajustement final (retrait de la part tertiaire)
 *
 * Prérequis : le format XML ADEME actuel ne distingue pas explicitement les locaux
 * tertiaires des logements au sein d'un immeuble mixte. L'implémentation complète
 * nécessiterait un tag XML dédié (ex. surface_tertiaire ou plancher_bas/mur avec
 * un flag usage_tertiaire). Ce Calculator est donc actuellement un no-op documenté.
 *
 * @spec-section 17.4
 * @spec-pages   119
 * @spec-source  resources/specsplitted/17-collectif/04-immeuble-mixte.md
 * @xml-input    (locaux tertiaires non identifiables dans le format XML ADEME standard)
 * @xml-output   (aucun)
 * @depends-on   \CalculDpe\Collectif\DpeImmeubleCalculator
 * @tables       (aucune)
 */
final class ImmeubleMixteCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return ['\CalculDpe\Collectif\DpeImmeubleCalculator'];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return false; // §17.4 non implémentable sans distinction XML logement/tertiaire.
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        // No-op : l'immeuble mixte nécessite un flag de surface tertiaire dans le XML,
        // absent du format ADEME standard actuel.
    }
}
