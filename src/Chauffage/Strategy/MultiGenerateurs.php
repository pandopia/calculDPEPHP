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
 * Plusieurs générateurs pour une même émission (§9.1.4 p.61-62).
 *
 * cfg_id=6 (§9.1.4.1 — chaudière bois + PAC/chaudière en relève) :
 *   1ère installation (chaudière bois) : Cch1 = 0.75 × Bch × INT1 × Ich1
 *   2ème installation (PAC/chaudière relève) : Cch2 = 0.25 × Bch × INT2 × Ich2
 *
 * cfg_id=8 (§9.1.4.2 — PAC + chaudière en relève) :
 *   1ère installation (PAC) : Cch1 = 0.80 × Bch × INT1 × Ich1
 *   2ème installation (chaudière) : Cch2 = 0.20 × Bch × INT2 × Ich2
 *
 * Note : les PAC hybrides (§9.1.4.3) ont des facteurs zone-dépendants (H1=80/20, H2=83/17, H3=88/12)
 * mais ne disposent pas d'un cfg_id distinct — traitement partiel ici avec facteurs de base.
 *
 * @spec-section 9.1.4
 * @spec-pages   61-62
 * @spec-source  resources/specsplitted/09-conso-chauffage/01-installation-seule/04-multi-generateurs.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator
 * @tables       (aucune)
 */
final class MultiGenerateurs implements CalculatorInterface
{
    use StrategieComputeTrait;

    // cfg_id → [position → factor]
    private const FACTORS = [
        6 => [1 => 0.75, 2 => 0.25],  // §9.1.4.1 — chaudière bois + PAC relève
        8 => [1 => 0.80, 2 => 0.20],  // §9.1.4.2 — PAC + chaudière relève
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
        if ($node->nodeName !== 'installation_chauffage') {
            return false;
        }
        $cfgId = $this->cfgId($node);
        return $cfgId !== null && isset(self::FACTORS[$cfgId]);
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $bch    = (float)$context->get('chauffage.besoin_ch',           0.0);
        $bchDep = (float)$context->get('chauffage.besoin_ch_depensier', 0.0);

        $cfgId   = $this->cfgId($node) ?? 6;
        $pos     = $this->positionInCollection($node);
        $factors = self::FACTORS[$cfgId];
        $factor  = $factors[$pos] ?? (1.0 / max(1, $pos));

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
