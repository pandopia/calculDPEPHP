<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Chauffage\BesoinChauffageCalculator;
use CalculDpePHP\Chauffage\Rendement\Combustion\InsertsPoelesCalculator;
use CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator;
use CalculDpePHP\Chauffage\Rendement\DistributionCalculator;
use CalculDpePHP\Chauffage\Rendement\EmissionCalculator;
use CalculDpePHP\Chauffage\Rendement\GenerationNonCombustionCalculator;
use CalculDpePHP\Chauffage\Rendement\RegulationCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Installation avec chauffage solaire (§9.2 p.62).
 *
 * enum_cfg_installation_ch_id = 2 → 1 installation :
 *   Cch = Bch × INT × (1 - Fch) × Ich
 *
 * Fch est lue depuis <fch_saisi> si renseignée, sinon déduite de la zone
 * climatique (table §18.4 p.143).
 *
 * @spec-section 9.2
 * @spec-pages   62
 * @spec-source  resources/specsplitted/09-conso-chauffage/02-solaire.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim, fch_saisi}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator
 * @tables       tv_facteur_couverture_solaire_id (§18.4 — fallback zone → Fch)
 */
final class ChauffageSolaire implements CalculatorInterface
{
    use StrategieComputeTrait;

    private const CFG_ID = 2;

    /** §18.4 p.143 — Fch (chauffage seul ou combiné) par zone climatique id */
    private const FCH_BY_ZONE = [
        '1' => 0.25,  // H1a
        '2' => 0.22,  // H1b
        '3' => 0.28,  // H1c
        '4' => 0.34,  // H2a
        '5' => 0.33,  // H2b
        '6' => 0.38,  // H2c
        '7' => 0.39,  // H2d
        '8' => 0.52,  // H3
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            BesoinChauffageCalculator::class,
            EmissionCalculator::class,
            DistributionCalculator::class,
            RegulationCalculator::class,
            GenerationNonCombustionCalculator::class,
            InsertsPoelesCalculator::class,
            RendementAnnuelMoyenCalculator::class,
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'installation_chauffage'
            && $this->cfgId($node) === self::CFG_ID;
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $bch    = (float)$context->get('chauffage.besoin_ch',           0.0);
        $bchDep = (float)$context->get('chauffage.besoin_ch_depensier', 0.0);

        $accessor = new NodeAccessor($context->document);
        $fch      = $this->resolveFch($accessor, $node, $context);

        $rdim          = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $rdimEffective = max(1e-9, $rdim);

        $besoin    = $bch    * (1.0 - $fch) / $rdimEffective;
        $besoinDep = $bchDep * (1.0 - $fch) / $rdimEffective;

        $this->computeAndWrite($besoin, $besoinDep, $node, $context);
    }

    public function resolveFch(NodeAccessor $accessor, DOMElement $node, CalculationContext $context): float
    {
        $fchSaisi = $accessor->getFloatOrNull('./donnee_entree/fch_saisi', $node);
        if ($fchSaisi !== null && $fchSaisi > 0.0) {
            return min(1.0, $fchSaisi);
        }
        return self::FCH_BY_ZONE[$context->zoneClimatique ?? ''] ?? 0.25;
    }

    private function cfgId(DOMElement $node): ?int
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                foreach ($child->childNodes as $c) {
                    if ($c instanceof DOMElement && $c->nodeName === 'enum_cfg_installation_ch_id') {
                        return (int)trim($c->textContent);
                    }
                }
            }
        }
        return null;
    }
}
