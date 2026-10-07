<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Chauffage\BesoinChauffageCalculator;
use CalculDpePHP\Chauffage\GenerateurChAlias;
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
 * Une installation unique peut porter les deux générateurs couplés sur la même
 * émission. Le besoin est alors partagé par nature du générateur, pas par rang
 * de l'installation. Les PAC hybrides suivent §9.1.4.3 : H1=80/20, H2=83/17, H3=88/12.
 *
 * @spec-section 9.1.4
 * @spec-pages   61-62
 * @spec-source  resources/specsplitted/09-conso-chauffage/01-installation-seule/04-multi-generateurs.md
 * @xml-input    installation_chauffage.donnee_entree.{enum_cfg_installation_ch_id, rdim}
 *               generateur_chauffage.donnee_entree.{enum_type_generateur_ch_id, enum_lien_generateur_emetteur_id}
 *               generateur_chauffage.donnee_intermediaire.rendement_generation
 * @xml-output   installation_chauffage.donnee_intermediaire.{besoin_ch, conso_ch}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator
 * @tables       (aucune)
 */
final class MultiGenerateurs implements CalculatorInterface
{
    use StrategieComputeTrait;

    // cfg_id → [position → factor]
    /** `enum_type_generateur_ch_id` = 106 : chaudière électrique. */
    private const CHAUDIERE_ELECTRIQUE = 106;

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

        if ($cfgId === 8 && $this->computePacEtReleve($bch, $bchDep, $rdimEffective, $node, $context, $accessor)) {
            return;
        }

        $this->computeAndWrite($factor * $bch / $rdimEffective, $factor * $bchDep / $rdimEffective, $node, $context);
    }

    /**
     * §9.1.4.2-3 p.61-62 : Cch_i = part_i × Bch × INT / (Rg_i × Re × Rd × Rr).
     * Les deux générateurs partagent les émetteurs de base ; la nature PAC/chaudière
     * détermine les parts. Les autres sérialisations conservent le calcul existant.
     */
    private function computePacEtReleve(
        float $besoin, float $besoinDep, float $rdim,
        DOMElement $node, CalculationContext $context, NodeAccessor $accessor,
    ): bool {
        if (!$node->parentNode instanceof DOMElement
            || $node->parentNode->getElementsByTagName('installation_chauffage')->length !== 1) {
            return false;
        }
        $generateurs = $node->getElementsByTagName('generateur_chauffage');
        $emetteurs = $node->getElementsByTagName('emetteur_chauffage');
        if ($generateurs->length !== 2 || $emetteurs->length === 0) {
            return false;
        }
        foreach ($emetteurs as $emetteur) {
            if ($accessor->getIntOrNull('./donnee_entree/enum_lien_generateur_emetteur_id', $emetteur) !== 1) {
                return false;
            }
        }
        $couple = [];
        $hybride = false;
        foreach ($generateurs as $gen) {
            if ($accessor->getIntOrNull('./donnee_entree/enum_lien_generateur_emetteur_id', $gen) !== 1) {
                return false;
            }
            $type = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $gen);
            $normalise = GenerateurChAlias::normalizeNode($type, $gen);
            // §9.1.4.2 parle d'une « chaudière en relève de PAC » sans
            // restreindre son énergie : la chaudière électrique (enum 106) en
            // est une au même titre que les chaudières à combustion.
            $role = $normalise >= 1 && $normalise <= 19 ? 'pac'
                : (($normalise >= 55 && $normalise <= 68)
                    || ($normalise >= 75 && $normalise <= 97)
                    || $normalise === self::CHAUDIERE_ELECTRIQUE ? 'chaudiere' : null);
            $rg = $accessor->getFloatOrNull('./donnee_intermediaire/rendement_generation', $gen);
            if ($role === null || isset($couple[$role]) || $rg === null || $rg <= 0.0) {
                return false;
            }
            $couple[$role] = [$gen, $rg];
            $hybride = $hybride || GenerateurChAlias::isHybride($type);
        }
        if (!isset($couple['pac'], $couple['chaudiere'])) {
            return false;
        }
        $zoneId = $context->zoneClimatique
            ?? $accessor->getStringOrNull('//meteo/enum_zone_climatique_id')
            ?? $accessor->getStringOrNull('//caracteristique_generale/enum_zone_climatique_id');
        $zone = $context->zoneGroupe ?? CalculationContext::zoneGroupeFromId($zoneId) ?? 'H1';
        $parts = $hybride ? GenerateurChAlias::prorataHybride($zone) : ['pac' => 0.8, 'chaudiere' => 0.2];
        $surface = $this->getShImmeuble($accessor, $node);
        $hauteur = $this->getHsp($accessor, $node);
        $g = $surface * $hauteur > 0.0 ? (float)$context->get('chauffage.gv', 1.0) / ($surface * $hauteur) : 1.0;
        $intermittence = ($this->weightedEmetteurFloat($accessor, $node, 'i0') ?? 1.0) / (1 + 0.1 * ($g - 1));
        $rendement = ($this->weightedEmetteurFloat($accessor, $node, 'rendement_emission') ?? 1.0)
            * ($this->weightedEmetteurFloat($accessor, $node, 'rendement_distribution') ?? 1.0)
            * ($this->weightedEmetteurFloat($accessor, $node, 'rendement_regulation') ?? 1.0);
        $rendementsDep = (array)$context->get('chauffage.rendement_generation_depensier', []);
        $partsBesoin = (array)$context->get('chauffage.part_besoin_generateur', []);
        $conso = $consoDep = 0.0;
        foreach ($couple as $role => [$gen, $rg]) {
            $reference = $accessor->getStringOrNull('./donnee_entree/reference', $gen);
            $rgDep = $reference !== null ? ($rendementsDep[$reference] ?? $rg) : $rg;
            $c = $parts[$role] * $besoin * $intermittence / ($rdim * max(1e-9, $rg * $rendement));
            $cDep = $parts[$role] * $besoinDep * $intermittence / ($rdim * max(1e-9, $rgDep * $rendement));
            $genDi = $accessor->ensureDonneeIntermediaire($gen);
            $accessor->setChildValue($genDi, 'conso_ch', $c);
            $accessor->setChildValue($genDi, 'conso_ch_depensier', $cDep);
            $conso += $c;
            $consoDep += $cDep;
            $partsBesoin[$gen->getNodePath()] = $parts[$role];
        }
        // §15.1.1 p.97 : les auxiliaires reçoivent aussi la part de besoin assurée.
        $context->set('chauffage.part_besoin_generateur', $partsBesoin);
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch', $besoin);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $besoinDep);
        $accessor->setChildValue($di, 'conso_ch', $conso);
        $accessor->setChildValue($di, 'conso_ch_depensier', $consoDep);
        return true;
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
