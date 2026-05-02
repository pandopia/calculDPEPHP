<?php

declare(strict_types=1);

namespace CalculDpePHP\Eclairage;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Consommation d'éclairage conventionnelle (§16.1 p.102-103).
 *
 * Cecl = C × Pecl × Σ(Nhj) / 1000 × Sh
 *
 * avec :
 *   C    = 0,9 (taux d'utilisation avec commande par interrupteur)
 *   Pecl = 1,4 W/m²
 *   Nhj  = heures de fonctionnement mensuelles (table par zone climatique)
 *
 * Surface Sh :
 *   - ZONE (caracteristique_generale contient surface_habitable_logement) → surface_habitable_logement
 *   - BAT (pas de surface_habitable_logement au niveau logement) → surface_habitable_immeuble
 *
 * @spec-section 16.1
 * @spec-pages   102-103
 * @spec-source  resources/specsplitted/16-eclairage-prod-elec/01-eclairage.md
 * @xml-input    caracteristique_generale.{surface_habitable_logement, surface_habitable_immeuble}
 *               meteo.enum_zone_climatique_id
 * @xml-output   logement.donnee_intermediaire.conso_eclairage (stockée en contexte)
 * @depends-on   (aucun)
 * @tables       (aucune — table Nhj directement dans le code)
 */
final class ConsoEclairageCalculator implements CalculatorInterface
{
    /**
     * Nombre d'heures annuelles d'éclairage par zone (§16.1 p.102-103).
     * Indexé par enum_zone_climatique_id (1→h1a … 8→h3).
     *
     * @spec-formula F-16.1-Nhj
     */
    private const NHJ_ANNUEL = [
        1 => 1500, // h1a
        2 => 1445, // h1b
        3 => 1476, // h1c
        4 => 1500, // h2a
        5 => 1531, // h2b
        6 => 1566, // h2c
        7 => 1566, // h2d
        8 => 1506, // h3
    ];

    private const C    = 0.9;  // interrupteur
    private const PECL = 1.4;  // W/m²

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
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $zoneId = $accessor->getIntOrNull('./meteo/enum_zone_climatique_id', $node) ?? 1;
        $nhj    = self::NHJ_ANNUEL[$zoneId] ?? 1500;

        // ZONE : utilise la surface du logement évalué (présente en caracteristique_generale)
        // BAT  : utilise la surface totale de l'immeuble
        $sh = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node)
           ?? $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node)
           ?? 0.0;

        $cecl = self::C * self::PECL * ($nhj / 1000.0) * $sh;

        $context->set('eclairage.conso_eclairage', $cecl);
    }
}
