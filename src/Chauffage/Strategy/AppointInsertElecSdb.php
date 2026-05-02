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
 * Installation avec en appoint un insert/poêle bois et un chauffage élec SdB (§9.5 p.63-64).
 *
 * enum_cfg_installation_ch_id = 5 → 3 installations :
 *   1ère (principale) : Cch1 = 0.75 × 0.90 × Bch × INT1 × Ich1  = 0.675 × Bch …
 *   2ème (insert)     : Cch2 = 0.25 × 0.90 × Bch × INT2 × Ich2  = 0.225 × Bch …
 *   3ème (élec SdB)   : Cch3 = 0.10 × Bch × INT3 × Ich3
 *
 * @spec-section 9.5
 * @spec-pages   63-64
 * @spec-source  resources/specsplitted/09-conso-chauffage/05-appoint-insert-elec-sdb.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpe\Chauffage\BesoinChauffageCalculator
 * @tables       (aucune)
 */
final class AppointInsertElecSdb implements CalculatorInterface
{
    use StrategieComputeTrait;

    private const CFG_ID  = 5;
    private const FACTORS = [1 => 0.675, 2 => 0.225, 3 => 0.10];

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
