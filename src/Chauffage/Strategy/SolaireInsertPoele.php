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
use CalculDpePHP\Chauffage\Rendement\RendementGenerateur;
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
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch, fch, production_ch_solaire}
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

        $rdim          = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $rdimEffective = max(1e-9, $rdim);

        $besoin    = $this->partSurfacique($accessor, $node) * $bch    / $rdimEffective;
        $besoinDep = $this->partSurfacique($accessor, $node) * $bchDep / $rdimEffective;

        $gv         = (float)$context->get('chauffage.gv', 1.0);
        $shImmeuble = $this->getShImmeuble($accessor, $node);
        $hsp        = $this->getHsp($accessor, $node);
        $g          = ($hsp * $shImmeuble) > 0.0 ? $gv / ($hsp * $shImmeuble) : 1.0;

        $genCollection = $this->getChildByTag($node, 'generateur_chauffage_collection');
        $genPos        = 0;
        $totalConso    = 0.0;
        $totalConsoDep = 0.0;
        // Σ facteur_i × INT_i × Ich_i, la parenthèse de la production solaire.
        $sommePonderee = 0.0;

        if ($genCollection !== null) {
            foreach ($genCollection->childNodes as $gen) {
                if (!($gen instanceof DOMElement) || $gen->nodeName !== 'generateur_chauffage') {
                    continue;
                }
                $genPos++;
                // §9.6 p.65 affecte 0,75 au système principal et 0,25 à
                // l'insert ou au poêle d'appoint, comme §9.3 pour la même
                // paire d'émetteurs. Le XSD les distingue par
                // `enum_lien_generateur_emetteur_id` — 1 « génération
                // principale », 2 « génération d'appoint » — et non par leur
                // ordre d'écriture, qui ne sert que de repli.
                $lien   = $accessor->getIntOrNull('./donnee_entree/enum_lien_generateur_emetteur_id', $gen);
                $factor = ($lien !== null ? (self::FACTORS[$lien] ?? null) : null)
                    ?? self::FACTORS[$genPos]
                    ?? (1.0 / max(1, $genPos));

                $rg  = RendementGenerateur::lire($accessor, $gen) ?? 1.0;
                $i0  = $this->linkedEmetteurFloat($accessor, $node, $gen, 'i0')
                    ?? $this->weightedEmetteurFloat($accessor, $node, 'i0') ?? 1.0;
                $re  = $this->linkedEmetteurFloat($accessor, $node, $gen, 'rendement_emission')
                    ?? $this->weightedEmetteurFloat($accessor, $node, 'rendement_emission') ?? 1.0;
                $rd  = $this->linkedEmetteurFloat($accessor, $node, $gen, 'rendement_distribution')
                    ?? $this->weightedEmetteurFloat($accessor, $node, 'rendement_distribution') ?? 1.0;
                $rr  = $this->linkedEmetteurFloat($accessor, $node, $gen, 'rendement_regulation')
                    ?? $this->weightedEmetteurFloat($accessor, $node, 'rendement_regulation') ?? 1.0;

                $int   = $i0 / (1.0 + 0.1 * ($g - 1.0));
                $denom = max(1e-9, $rg * $re * $rd * $rr);

                // §9.6 p.65 : Cch_i = facteur_i × Bch × INT_i × (1 − Fch) × Ich_i
                $genConso    = $factor * $besoin    * $int * (1.0 - $fch) / $denom;
                $genConsoDep = $factor * $besoinDep * $int * (1.0 - $fch) / $denom;

                $totalConso    += $genConso;
                $totalConsoDep += $genConsoDep;
                $sommePonderee += $factor * $int / $denom;

                $genDi = $accessor->ensureDonneeIntermediaire($gen);
                $accessor->setChildValue($genDi, 'conso_ch',           $genConso);
                $accessor->setChildValue($genDi, 'conso_ch_depensier', $genConsoDep);
            }
        }

        // §9.6 p.65 : le besoin publié reste celui de l'installation, la
        // couverture solaire ne retranchant que la consommation.
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch',           $besoin);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $besoinDep);
        $accessor->setChildValue($di, 'conso_ch',            $totalConso);
        $accessor->setChildValue($di, 'conso_ch_depensier',  $totalConsoDep);
        $accessor->setChildValue($di, 'fch',                 $fch);
        // §9.6 p.65 : Prod = Bch × Fch × (0,75 INT1 Ich1 + 0,25 INT2 Ich2)
        $accessor->setChildValue($di, 'production_ch_solaire', $besoin * $fch * $sommePonderee);
    }

    /**
     * §9.6 p.65 : INTi et Ichi sont ceux de l'installation alimentée par
     * l'équipement i — l'émetteur de base pour le système principal, celui
     * d'appoint pour le poêle ou l'insert.
     *
     * @spec-formula F-9.6-Cchi
     */
    private function linkedEmetteurFloat(
        NodeAccessor $accessor,
        DOMElement $installNode,
        DOMElement $generator,
        string $field,
    ): ?float {
        $linkId = $accessor->getIntOrNull('./donnee_entree/enum_lien_generateur_emetteur_id', $generator);
        $collection = $this->getChildByTag($installNode, 'emetteur_chauffage_collection');
        if ($linkId === null || $collection === null) {
            return null;
        }

        foreach ($collection->childNodes as $emitter) {
            if (!($emitter instanceof DOMElement) || $emitter->nodeName !== 'emetteur_chauffage') {
                continue;
            }
            if ($accessor->getIntOrNull('./donnee_entree/enum_lien_generateur_emetteur_id', $emitter) === $linkId) {
                return $accessor->getFloatOrNull("./donnee_intermediaire/{$field}", $emitter);
            }
        }

        return null;
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
