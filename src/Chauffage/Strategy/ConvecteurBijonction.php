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

        // §9.9 p.66 : `Cch1 = 0,6 Bch INT1 Ich1` pour le circuit **collectif**
        // qui assure la base, `Cch2 = 0,4 Bch INT2 Ich2` pour le circuit
        // individuel d'appoint. Les deux INT ne sont pas le même : la base
        // collective ne s'interrompt pas, et les références le confirment —
        // sur `2601E0208630M`, 0,6 × 4 869,27 × 1,1696 = 3 417,0 contre
        // 3 415,95 publiés sur le générateur, soit INT1 = 1, et le total de
        // 5 361,60 se reconstruit à 0,015 % près avec INT2 = notre
        // intermittence. Quatre cas du corpus, tous à la même lecture.
        $this->computeAndWrite(
            $besoin,
            $besoinDep,
            $node,
            $context,
            partContinue: self::FACTORS[1],
        );
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
