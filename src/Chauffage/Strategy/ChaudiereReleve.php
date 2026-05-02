<?php

declare(strict_types=1);

namespace CalculDpe\Chauffage\Strategy;

use CalculDpe\Chauffage\BesoinChauffageCalculator;
use CalculDpe\Chauffage\Rendement\Combustion\InsertsPoelesCalculator;
use CalculDpe\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator;
use CalculDpe\Chauffage\Rendement\DistributionCalculator;
use CalculDpe\Chauffage\Rendement\EmissionCalculator;
use CalculDpe\Chauffage\Rendement\GenerationNonCombustionCalculator;
use CalculDpe\Chauffage\Rendement\RegulationCalculator;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Chaudière en relève de PAC avec insert ou poêle bois en appoint (§9.7 p.64-65).
 *
 * enum_cfg_installation_ch_id = 9 → 3 installations :
 *   1ère (PAC)               : Cch1 = 0.80 × 0.75 × Bch × INT1 × Ich1 = 0.60 × Bch …
 *   2ème (chaudière relève)  : Cch2 = 0.20 × 0.75 × Bch × INT2 × Ich2 = 0.15 × Bch …
 *   3ème (insert/poêle)      : Cch3 = 0.25 × Bch × INT3 × Ich3
 *
 * @spec-section 9.7
 * @spec-pages   64-65
 * @spec-source  resources/specsplitted/09-conso-chauffage/07-chaudiere-releve-pac-insert.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpe\Chauffage\BesoinChauffageCalculator
 * @tables       (aucune)
 */
final class ChaudiereReleve implements CalculatorInterface
{
    use StrategieComputeTrait;

    private const CFG_ID  = 9;
    private const FACTORS = [1 => 0.60, 2 => 0.15, 3 => 0.25];

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

        $pos    = $this->positionInCollection($node);
        $factor = self::FACTORS[$pos] ?? (1.0 / max(1, $pos));

        $accessor      = new NodeAccessor($context->document);
        $rdim          = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $rdimEffective = max(1e-9, $rdim);

        $this->computeAndWrite($factor * $bch / $rdimEffective, $factor * $bchDep / $rdimEffective, $node, $context);
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
