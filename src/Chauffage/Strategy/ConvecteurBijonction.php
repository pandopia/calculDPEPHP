<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Chauffage\BesoinChauffageCalculator;
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
 * Convecteur bi-jonction (§9.9 p.66).
 *
 * enum_cfg_installation_ch_id = 11 → 2 installations :
 *   1ère (circuit collectif base)        : Cch1 = 0.60 × Bch × INT1 × Ich1
 *   2ème (circuit individuel appoint)    : Cch2 = 0.40 × Bch × INT2 × Ich2
 *
 * @spec-section 9.9
 * @spec-pages   66
 * @spec-source  resources/specsplitted/09-conso-chauffage/09-convecteur-bijonction.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator
 * @tables       (aucune)
 */
final class ConvecteurBijonction implements CalculatorInterface
{
    use StrategieComputeTrait;

    private const CFG_ID  = 11;
    private const FACTORS = [1 => 0.60, 2 => 0.40];

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

        $accessor      = new NodeAccessor($context->document);
        $rdim          = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $rdimEffective = max(1e-9, $rdim);
        $besoin        = $bch / $rdimEffective;
        $besoinDep     = $bchDep / $rdimEffective;

        // §9.9 p.67 : « La base et l'appoint sont assurés par un **même**
        // convecteur disposant d'un circuit collectif alimentant la base et un
        // circuit individuel pour l'appoint » — Cch1 = 0,6 Bch, Cch2 = 0,4 Bch.
        // La coupure est entre deux circuits d'un seul appareil, pas entre
        // deux installations : leurs parts somment à 1.
        //
        // Quand le fichier distingue les deux circuits par
        // `enum_lien_generateur_emetteur_id`, chacun garde son propre
        // rendement ; les quatre cas du corpus n'en décrivent qu'un, et
        // l'installation reçoit alors le besoin entier.
        if ($this->computeAndWriteParLien(self::FACTORS, $besoin, $besoinDep, $node, $context)) {
            return;
        }

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
