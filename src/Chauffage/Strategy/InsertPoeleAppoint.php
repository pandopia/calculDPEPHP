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
 * Installation avec insert ou poêle bois en appoint (§9.3 p.62-63).
 *
 * enum_cfg_installation_ch_id = 3 → 1 installation, 2 générateurs :
 *   installation/besoin_ch   = Bch (full)
 *   générateur 1 (principal): Cch1 = 0.75 × Bch × INT / (Rg1 × Re × Rd × Rr)
 *   générateur 2 (insert)   : Cch2 = 0.25 × Bch × INT / (Rg2 × Re × Rd × Rr)
 *   installation/conso_ch   = Cch1 + Cch2
 *
 * @spec-section 9.3
 * @spec-pages   62-63
 * @spec-source  resources/specsplitted/09-conso-chauffage/03-insert-poele-appoint.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator
 * @tables       (aucune)
 */
final class InsertPoeleAppoint implements CalculatorInterface
{
    use StrategieComputeTrait;

    private const CFG_ID  = 3;
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

        $gv         = (float)$context->get('chauffage.gv', 1.0);
        $shImmeuble = $this->getShImmeuble($accessor, $node);
        $hsp        = $this->getHsp($accessor, $node);
        $g          = ($hsp * $shImmeuble) > 0.0 ? $gv / ($hsp * $shImmeuble) : 1.0;
        $i0         = $this->weightedEmetteurFloat($accessor, $node, 'i0') ?? 1.0;
        $int        = $i0 / (1.0 + 0.1 * ($g - 1.0));

        $re = $this->weightedEmetteurFloat($accessor, $node, 'rendement_emission')    ?? 1.0;
        $rd = $this->weightedEmetteurFloat($accessor, $node, 'rendement_distribution') ?? 1.0;
        $rr = $this->weightedEmetteurFloat($accessor, $node, 'rendement_regulation')  ?? 1.0;

        // Each generator gets its own fraction of bch and its own rg
        $genCollection = $this->getChildByTag($node, 'generateur_chauffage_collection');
        $genPos        = 0;
        $totalConso    = 0.0;
        $totalConsoDep = 0.0;

        if ($genCollection !== null) {
            foreach ($genCollection->childNodes as $gen) {
                if (!($gen instanceof DOMElement) || $gen->nodeName !== 'generateur_chauffage') {
                    continue;
                }
                $genPos++;
                $factor = self::FACTORS[$genPos] ?? (1.0 / max(1, $genPos));
                $rg     = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_generation', $gen) ?? 1.0;
                $denom  = max(1e-9, $rg * $re * $rd * $rr);

                $genConso    = $factor * $bch    * $int / $denom;
                $genConsoDep = $factor * $bchDep * $int / $denom;

                $totalConso    += $genConso;
                $totalConsoDep += $genConsoDep;

                $genDi = $accessor->ensureDonneeIntermediaire($gen);
                $accessor->setChildValue($genDi, 'conso_ch',           $genConso);
                $accessor->setChildValue($genDi, 'conso_ch_depensier', $genConsoDep);
            }
        }

        // Installation stores the full besoin and sum of generator consos
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch',           $bch);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $bchDep);
        $accessor->setChildValue($di, 'conso_ch',            $totalConso);
        $accessor->setChildValue($di, 'conso_ch_depensier',  $totalConsoDep);
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
