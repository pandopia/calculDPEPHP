<?php

declare(strict_types=1);

namespace CalculDpePHP\Ecs\Rendement;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * COP des chauffe-eau thermodynamiques à accumulation (§14.2 p.95).
 *
 * Le COP dépend du type d'installation (air ambiant/extérieur, air extrait, PAC double service)
 * et de la zone climatique (H1/H2 vs H3). Le COP inclut déjà le rendement de
 * stockage (XSD), il tient donc lieu de rendement de génération et de stockage.
 *
 * Formula spec : Iecs = 1 / (Rd × COP)
 *
 * @spec-section 14.2
 * @spec-pages   95
 * @spec-source  resources/specsplitted/14-rendement-ecs-generateurs/02-cet-accumulation.md
 * @xml-input    generateur_ecs.donnee_entree.{enum_type_generateur_ecs_id, enum_periode_installation_ecs_thermo_id, enum_methode_saisie_carac_sys_id, cop}
 * @xml-input    generateur_ecs.donnee_intermediaire.cop (saisie méthode 6)
 * @xml-output   generateur_ecs.donnee_intermediaire.cop
 * @depends-on   aucun
 * @tables       ecs/tv_cop_2027 (extension ADEME du modèle 2.7)
 */
final class CetAccumulationCalculator implements CalculatorInterface
{
    /**
     * §14.2 p.95 — COP[zone_groupe][type_id]
     * type: 1-6 = air ambiant/extérieur, 7-9 = air extrait, 10-12 = PAC double service
     * zone H1/H2 et zone H3 ont des valeurs différentes.
     */
    private const COP_H1H2 = [
        // CET sur air extérieur ou ambiant (IDs 1-6)
        1 => 2.0,  2 => 2.2,  3 => 2.5,
        4 => 2.0,  5 => 2.2,  6 => 2.5,
        // CET sur air extrait (IDs 7-9)
        7 => 2.3,  8 => 2.5,  9 => 2.8,
        // PAC double service (IDs 10-12)
        10 => 2.0, 11 => 2.1, 12 => 2.3,
    ];

    private const COP_H3 = [
        // CET sur air extérieur ou ambiant (IDs 1-6)
        1 => 2.3,  2 => 2.5,  3 => 2.8,
        4 => 2.3,  5 => 2.5,  6 => 2.8,
        // CET sur air extrait (IDs 7-9)
        7 => 2.3,  8 => 2.5,  9 => 2.9,
        // PAC double service (IDs 10-12)
        10 => 2.3, 11 => 2.4, 12 => 2.6,
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

        // « Autre système thermodynamique électrique » (82) → PAC double service
        // selon la période d'installation (open3cl 13.2_generateur_pac.js).
        if ($typeId === 82) {
            $periode = $accessor->getIntOrNull('./donnee_entree/enum_periode_installation_ecs_thermo_id', $node);
            if ($periode === 4) {
                // Le classeur 2.7 ne rattache pas le type générique 82 à une
                // des quatre familles 135–138. Ne pas inventer cette famille.
                $cop = self::justifiedCop($node, $accessor);
                if ($cop === null) {
                    throw new \CalculDpePHP\Engine\UnsupportedDpeModelException('CET générique 82 depuis 2027 : COP propre connu et justifié requis, aucun forfait ADEME publié pour ce type.');
                }
                $accessor->setChildValue($accessor->ensureDonneeIntermediaire($node), 'cop', $cop);
                return;
            }
            $typeId  = match ($periode) {
                1       => 10,
                2       => 11,
                3       => 12,
                default => 10, // période inconnue → la plus pénalisante
            };
        }

        $recent = $typeId !== null && $typeId >= 135 && $typeId <= 138;
        if (!$recent && ($typeId === null || $typeId < 1 || $typeId > 12)) {
            return;
        }

        $zone  = CalculationContext::zoneGroupeFromId($context->zoneClimatique);
        if ($recent) {
            // ADEME 05aabba7, feuille scop lignes 108-113 : les nouvelles
            // générations ont leur propre COP, pas celui des CET 2015-2026.
            $table = $context->tables->load('ecs/tv_cop_2027');
            $cop = $table[$typeId][$zone === 'H3' ? 'h3' : 'h1h2'];
        } else {
            $table = ($zone === 'H3') ? self::COP_H3 : self::COP_H1H2;
            $cop = $table[$typeId];
        }

        // §14.2 et méthode de saisie 6 du XSD : une performance réelle
        // justifiée n'est pas un résultat forfaitaire à recalculer. Le champ
        // cop propre à ce CET est préservé par OutputPurger.
        $cop = self::justifiedCop($node, $accessor) ?? $cop;

        // Le XSD définit `rendement_generation` comme « le rendement de
        // génération dans le cas où le rendement de stockage est séparé du
        // rendement de génération », et `cop` comme « le COP du chauffe-eau
        // thermodynamique (inclus le rendement de stockage) » : un CET a son
        // ballon intégré, donc un COP et pas de Rg séparé.
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'cop', $cop);
    }

    /** §14.2 p.95 : performance réelle propre, méthode de saisie 6 du XSD. */
    public static function justifiedCop(DOMElement $node, NodeAccessor $accessor): ?float
    {
        if ($accessor->getIntOrNull('./donnee_entree/enum_methode_saisie_carac_sys_id', $node) !== 6) { return null; }
        $cop = $accessor->getFloatOrNull('./donnee_entree/cop', $node)
            ?? $accessor->getFloatOrNull('./donnee_intermediaire/cop', $node);
        return $cop !== null && is_finite($cop) && $cop > 0.0 ? $cop : null;
    }
}
