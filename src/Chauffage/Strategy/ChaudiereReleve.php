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
use CalculDpePHP\Chauffage\GenerateurChAlias;
use CalculDpePHP\Chauffage\Rendement\RendementGenerateur;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Chaudière en relève de PAC avec insert ou poêle bois en appoint (§9.7 p.64-65).
 *
 * enum_cfg_installation_ch_id = 9 → 3 installations :
 *   1ère (PAC)               : Cch1 = 0.80 × 0.75 × Bch × INT1 × Ich1 = 0.60 × Bch …
 *   2ème (chaudière relève)  : Cch2 = 0.20 × 0.75 × Bch × INT2 × Ich2 = 0.15 × Bch …
 *   3ème (insert/poêle)      : Cch3 = 0.25 × Bch × INT3 × Ich3
 *
 * @spec-section 9.1.3, 9.7
 * @spec-pages   60, 64-65
 * @spec-source  resources/specsplitted/09-conso-chauffage/07-chaudiere-releve-pac-insert.md
 * @spec-source  resources/specsplitted/09-conso-chauffage/01-installation-seule/03-multi-emissions.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator
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
        $partSurfacique = $this->partSurfacique(new NodeAccessor($context->document), $node);
        // §9.1 p.57 répartit le besoin entre les installations du logement :
        // une installation qui ne chauffe qu'une partie de la surface
        // desservie n'en porte que la part correspondante.
        $bch    = $partSurfacique * (float)$context->get('chauffage.besoin_ch',           0.0);
        $bchDep = $partSurfacique * (float)$context->get('chauffage.besoin_ch_depensier', 0.0);

        $accessor      = new NodeAccessor($context->document);
        $rdim          = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $rdimEffective = max(1e-9, $rdim);
        $besoin        = $bch / $rdimEffective;
        $besoinDep     = $bchDep / $rdimEffective;

        $gv         = (float)$context->get('chauffage.gv', 1.0);
        $shImmeuble = $this->getShImmeuble($accessor, $node);
        $hsp        = $this->getHsp($accessor, $node);
        $g          = ($hsp * $shImmeuble) > 0.0 ? $gv / ($hsp * $shImmeuble) : 1.0;

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

                // §9.7 p.65-66 affecte 0,8 × 0,75 à la PAC, 0,2 × 0,75 à la
                // chaudière qui la relève et 0,25 à l'insert ou au poêle
                // d'appoint. La nature du générateur désigne la part ; l'ordre
                // d'écriture ne sert que de repli.
                $typeId = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $gen);
                $factor = self::FACTORS[$this->rang(GenerateurChAlias::normalizeNode($typeId, $gen)) ?? $genPos]
                    ?? (1.0 / max(1, $genPos));

                $rg = RendementGenerateur::lire($accessor, $gen) ?? 1.0;
                $i0 = $this->weightedEmetteurFloat($accessor, $node, 'i0') ?? 1.0;
                $re = $this->weightedEmetteurFloat($accessor, $node, 'rendement_emission') ?? 1.0;
                $rd = $this->weightedEmetteurFloat($accessor, $node, 'rendement_distribution') ?? 1.0;
                $rr = $this->weightedEmetteurFloat($accessor, $node, 'rendement_regulation') ?? 1.0;

                $facteurEmission = $this->facteurEmissionParLien($accessor, $node, $gen)
                    ?? ($i0 / max(1e-9, $re * $rd * $rr));
                $facteur = $facteurEmission / max(1e-9, $rg * (1.0 + 0.1 * ($g - 1.0)));

                $genConso    = $factor * $besoin    * $facteur;
                $genConsoDep = $factor * $besoinDep * $facteur;

                $totalConso    += $genConso;
                $totalConsoDep += $genConsoDep;

                $genDi = $accessor->ensureDonneeIntermediaire($gen);
                $accessor->setChildValue($genDi, 'conso_ch',           $genConso);
                $accessor->setChildValue($genDi, 'conso_ch_depensier', $genConsoDep);
                $this->publierPartDuBesoin($context, $gen, $factor);
            }
        }

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch',           $besoin);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $besoinDep);
        $accessor->setChildValue($di, 'conso_ch',            $totalConso);
        $accessor->setChildValue($di, 'conso_ch_depensier',  $totalConsoDep);
    }

    /**
     * §9.7 p.65 et §9.1.3 p.60 : chaque générateur consomme via les
     * émissions auxquelles il est relié. Pour une branche à plusieurs
     * émissions, sommer Sh_i/Sh_branche × I0_i/(Re_i × Rd_i × Rr_i).
     * Le poêle d'appoint ne partage ni l'intermittence ni le réseau
     * hydraulique des émissions desservies par la PAC et sa relève.
     *
     * @spec-formula F-9.7-emissions
     */
    private function facteurEmissionParLien(NodeAccessor $accessor, DOMElement $installation, DOMElement $generateur): ?float
    {
        $lien = $accessor->getIntOrNull('./donnee_entree/enum_lien_generateur_emetteur_id', $generateur);
        if ($lien === null) {
            return null;
        }
        $emetteurs = $this->groupByLien($accessor, $installation, 'emetteur_chauffage_collection', 'emetteur_chauffage')[$lien] ?? [];
        $surfaceTotale = $somme = 0.0;
        foreach ($emetteurs as $emetteur) {
            $surface = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $emetteur) ?? 1.0;
            $i0 = $accessor->getFloatOrNull('./donnee_intermediaire/i0', $emetteur) ?? 1.0;
            $rendement = 1.0;
            foreach (['rendement_emission', 'rendement_distribution', 'rendement_regulation'] as $champ) {
                $rendement *= $accessor->getFloatOrNull('./donnee_intermediaire/' . $champ, $emetteur) ?? 1.0;
            }
            $surfaceTotale += $surface;
            $somme += $surface * $i0 / max(1e-9, $rendement);
        }

        return $surfaceTotale > 0.0 ? $somme / $surfaceTotale : null;
    }

    /**
     * Rang de §9.7 : 1 la PAC, 2 la chaudière qui la relève, 3 l'insert ou le
     * poêle bois d'appoint. Les plages suivent le XSD : 1-19 pompes à chaleur,
     * 20-54 inserts, poêles et cuisinières, 55-97 et 106 chaudières.
     */
    private function rang(?int $normalise): ?int
    {
        if ($normalise === null) {
            return null;
        }
        if ($normalise >= 1 && $normalise <= 19) {
            return 1;
        }
        if (($normalise >= 55 && $normalise <= 97) || $normalise === 106) {
            return 2;
        }

        return $normalise >= 20 && $normalise <= 54 ? 3 : null;
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
