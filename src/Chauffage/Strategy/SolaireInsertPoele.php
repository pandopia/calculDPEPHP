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
 * Chauffage solaire + insert ou poêle bois en appoint (§9.6 p.64).
 *
 * enum_cfg_installation_ch_id = 7 → 2 installations :
 *   1ère (principal) : Cch1 = 0.75 × Bch × INT1 × (1 - Fch) × Ich1
 *   2ème (insert)    : Cch2 = 0.25 × Bch × INT2 × (1 - Fch) × Ich2
 *
 * Fch identique à §9.2 (lue depuis fch_saisi ou zone climatique §18.4).
 *
 * @spec-section 9.6
 * @spec-pages   64
 * @spec-source  resources/specsplitted/09-conso-chauffage/06-solaire-insert-poele.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim, fch_saisi}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpePHP\Chauffage\Strategy\ChauffageSolaire
 * @tables       tv_facteur_couverture_solaire_id (§18.4 — via ChauffageSolaire::resolveFch)
 */
final class SolaireInsertPoele implements CalculatorInterface
{
    use StrategieComputeTrait;

    private const CFG_ID  = 7;
    private const FACTORS = [1 => 0.75, 2 => 0.25];

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
        $fch      = (new ChauffageSolaire())->resolveFch($accessor, $node, $context);

        $pos    = $this->positionInCollection($node);
        $factor = self::FACTORS[$pos] ?? (1.0 / max(1, $pos));

        $rdim          = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $rdimEffective = max(1e-9, $rdim);

        $besoin    = $factor * $bch    * (1.0 - $fch) / $rdimEffective;
        $besoinDep = $factor * $bchDep * (1.0 - $fch) / $rdimEffective;

        $this->computeAndWrite($besoin, $besoinDep, $node, $context);
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
