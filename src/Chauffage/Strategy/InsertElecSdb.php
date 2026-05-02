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
 * Installation par insert/poêle bois avec chauffage électrique SdB (§9.4 p.63).
 *
 * enum_cfg_installation_ch_id = 4 → 2 installations :
 *   1ère (insert/poêle) : Cch1 = 0.90 × Bch × INT1 × Ich1
 *   2ème (élec SdB)     : Cch2 = 0.10 × Bch × INT2 × Ich2
 *
 * @spec-section 9.4
 * @spec-pages   63
 * @spec-source  resources/specsplitted/09-conso-chauffage/04-insert-elec-sdb.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpe\Chauffage\BesoinChauffageCalculator
 * @tables       (aucune)
 */
final class InsertElecSdb implements CalculatorInterface
{
    use StrategieComputeTrait;

    private const CFG_ID  = 4;
    private const FACTORS = [1 => 0.90, 2 => 0.10];

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
