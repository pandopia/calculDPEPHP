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
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator
 * @tables       (aucune)
 */
final class InsertElecSdb implements CalculatorInterface
{
    use StrategieComputeTrait;

    private const CFG_ID  = 4;
    private const FACTORS = [1 => 0.90, 2 => 0.10];

    /**
     * Parts de Bch par branche, indexées par `enum_lien_generateur_emetteur_id`
     * (XSD) : 1 « génération principale », 3 « génération appoint électrique
     * salle de bain ». §9.4 p.63 leur affecte respectivement 0,9 et 0,1.
     */
    private const PARTS_PAR_LIEN = [1 => 0.90, 3 => 0.10];

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

        $accessor      = new NodeAccessor($context->document);
        $rdim          = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $rdimEffective = max(1e-9, $rdim);

        // §9.4 p.63 : « tout le bâtiment est chauffé par un poêle bois ; seule
        // la salle de bains est chauffée par un système électrique ». Les deux
        // parts se rapportent donc au besoin du logement entier, ce qui n'a de
        // sens que si cette installation est la seule. Quand le fichier en
        // décrit plusieurs, le besoin est d'abord réparti entre elles et la
        // sérialisation en deux installations successives reprend la main.
        //
        // Dans le cas courant, une seule installation porte les deux branches,
        // que `enum_lien_generateur_emetteur_id` distingue : 1 pour l'émetteur
        // de base (poêle ou insert), 3 pour l'émetteur de salle de bains.
        if ($this->estSeuleInstallation($node) && $this->computeAndWriteParLien(
            self::PARTS_PAR_LIEN,
            $bch / $rdimEffective,
            $bchDep / $rdimEffective,
            $node,
            $context,
        )) {
            return;
        }

        // Sérialisation alternative : les deux branches sont décrites comme
        // deux installations successives, et la part se lit sur leur rang.
        $pos    = $this->positionInCollection($node);
        $factor = self::FACTORS[$pos] ?? (1.0 / max(1, $pos));

        $this->computeAndWrite($factor * $bch / $rdimEffective, $factor * $bchDep / $rdimEffective, $node, $context);
    }

    /** Vrai si le logement ne décrit qu'une seule installation de chauffage. */
    private function estSeuleInstallation(DOMElement $node): bool
    {
        $collection = $node->parentNode;
        if (!$collection instanceof DOMElement) {
            return false;
        }
        $n = 0;
        foreach ($collection->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'installation_chauffage') {
                $n++;
            }
        }

        return $n === 1;
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
