<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Chauffage\BesoinChauffageCalculator;
use CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator;
use CalculDpePHP\Chauffage\Rendement\DistributionCalculator;
use CalculDpePHP\Chauffage\Rendement\EmissionCalculator;
use CalculDpePHP\Chauffage\Rendement\GenerationNonCombustionCalculator;
use CalculDpePHP\Chauffage\Rendement\RegulationCalculator;
use CalculDpePHP\Collectif\ChauffageInstallationMultiplicity;
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
 * @spec-section 8, 9.9, 17.1.2, 17.1.4.2
 * @spec-pages   55-57, 66, 107, 110
 * @spec-source  resources/specsplitted/09-conso-chauffage/09-convecteur-bijonction.md
 * @spec-source  resources/specsplitted/08-intermittence/00-calcul.md
 * @spec-source  resources/specsplitted/17-collectif/01-immeuble-collectif.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim,
 *               enum_methode_calcul_conso_id, enum_type_installation_id, surface_chauffee}
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator
 * @tables       chauffage/tv_intermittence
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
        $partSurfacique = $this->partSurfacique(new NodeAccessor($context->document), $node);
        // §9.1 p.57 répartit le besoin entre les installations du logement :
        // une installation qui ne chauffe qu'une partie de la surface
        // desservie n'en porte que la part correspondante.
        $bch    = $partSurfacique * (float)$context->get('chauffage.besoin_ch',           0.0);
        $bchDep = $partSurfacique * (float)$context->get('chauffage.besoin_ch_depensier', 0.0);

        $accessor      = new NodeAccessor($context->document);
        $rdim          = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $rdimEffective = ChauffageInstallationMultiplicity::sampledOrNull($node, $accessor) ?? $rdim;
        $rdimEffective = max(1e-9, $rdimEffective);
        $besoin        = $bch / $rdimEffective;
        $besoinDep     = $bchDep / $rdimEffective;

        // §9.9 p.67 : « La base et l'appoint sont assurés par un **même**
        // convecteur disposant d'un circuit collectif alimentant la base et un
        // circuit individuel pour l'appoint » — Cch1 = 0,6 Bch, Cch2 = 0,4 Bch.
        // La coupure est entre deux circuits d'un seul appareil, pas entre
        // deux installations : leurs parts somment à 1.
        //
        $this->computeAndWriteCircuits($besoin, $besoinDep, $bch, $bchDep, $node, $context);
    }

    /**
     * §9.9 p.66 : Cch1 = 0,6 × Bch × INT1 × Ich1 et
     * Cch2 = 0,4 × Bch × INT2 × Ich2.
     *
     * Le circuit de base est un chauffage collectif sans comptage individuel :
     * la table §8 p.57 donne I0=1,03 pour un radiateur/convecteur régulé,
     * sans équipement d'intermittence. L'appoint individuel conserve le I0
     * calculé sur l'émetteur. Confondre les deux INT supprimait le terme
     * collectif de la formule.
     *
     * @spec-formula F-9.9-a
     */
    private function computeAndWriteCircuits(
        float $besoin,
        float $besoinDep,
        float $besoinPublie,
        float $besoinPublieDep,
        DOMElement $node,
        CalculationContext $context,
    ): void {
        $accessor = new NodeAccessor($context->document);
        $gv = (float)$context->get('chauffage.gv', 1.0);
        $g = $gv / max(1e-9, $this->getHsp($accessor, $node) * $this->getShImmeuble($accessor, $node));
        $diviseurInt = max(1e-9, 1.0 + 0.1 * ($g - 1.0));

        $table = $context->tables->load('chauffage/tv_intermittence');
        $i0Base = (float)$table['collectif_collectif']['central']['avec']['radiateur']['absent'][1];
        $i0Appoint = $this->weightedEmetteurFloat($accessor, $node, 'i0') ?? 1.0;
        $intBase = $i0Base / $diviseurInt;
        $intAppoint = $i0Appoint / $diviseurInt;

        $re = $this->weightedEmetteurFloat($accessor, $node, 'rendement_emission') ?? 1.0;
        $rd = $this->weightedEmetteurFloat($accessor, $node, 'rendement_distribution') ?? 1.0;
        $rr = $this->weightedEmetteurFloat($accessor, $node, 'rendement_regulation') ?? 1.0;
        $rg = $this->weightedGenerateurFloat($accessor, $node, 'rendement_generation') ?? 1.0;
        $denom = max(1e-9, $rg * $re * $rd * $rr);

        $consoBase = self::FACTORS[1] * $besoin * $intBase / $denom;
        $consoAppoint = self::FACTORS[2] * $besoin * $intAppoint / $denom;
        $consoBaseDep = self::FACTORS[1] * $besoinDep * $intBase / $denom;
        $consoAppointDep = self::FACTORS[2] * $besoinDep * $intAppoint / $denom;

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch', $besoinPublie);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $besoinPublieDep);
        $accessor->setChildValue($di, 'conso_ch', $consoBase + $consoAppoint);
        $accessor->setChildValue($di, 'conso_ch_depensier', $consoBaseDep + $consoAppointDep);

        // Le générateur publié par le format ADEME décrit le circuit de
        // base ; l'appoint est l'autre circuit du même convecteur (§9.9).
        $collection = $this->getChildByTag($node, 'generateur_chauffage_collection');
        $generateurs = [];
        if ($collection !== null) {
            foreach ($collection->childNodes as $generateur) {
                if ($generateur instanceof DOMElement && $generateur->nodeName === 'generateur_chauffage') {
                    $generateurs[] = $generateur;
                }
            }
        }
        $parGenerateur = max(1, count($generateurs));
        foreach ($generateurs as $generateur) {
            $genDi = $accessor->ensureDonneeIntermediaire($generateur);
            $accessor->setChildValue($genDi, 'conso_ch', $consoBase / $parGenerateur);
            $accessor->setChildValue($genDi, 'conso_ch_depensier', $consoBaseDep / $parGenerateur);
            $this->publierPartDuBesoin($context, $generateur, self::FACTORS[1] / $parGenerateur);
        }
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
