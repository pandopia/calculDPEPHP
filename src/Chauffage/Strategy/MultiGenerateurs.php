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
use CalculDpePHP\Chauffage\Rendement\RendementGenerateur;
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
        $partSurfacique = $this->partSurfacique(new NodeAccessor($context->document), $node);
        // §9.1 p.57 répartit le besoin entre les installations du logement :
        // une installation qui ne chauffe qu'une partie de la surface
        // desservie n'en porte que la part correspondante.
        $bch    = $partSurfacique * (float)$context->get('chauffage.besoin_ch',           0.0);
        $bchDep = $partSurfacique * (float)$context->get('chauffage.besoin_ch_depensier', 0.0);

        $cfgId   = $this->cfgId($node) ?? 6;
        $pos     = $this->positionInCollection($node);
        $factors = self::FACTORS[$cfgId];
        $factor  = $factors[$pos] ?? (1.0 / max(1, $pos));

        $accessor      = new NodeAccessor($context->document);
        $rdim          = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $rdimEffective = max(1e-9, $rdim);

        if ($this->computeParGenerateur($cfgId, $bch, $bchDep, $rdimEffective, $node, $context, $accessor)) {
            return;
        }

        $this->computeAndWrite($factor * $bch / $rdimEffective, $factor * $bchDep / $rdimEffective, $node, $context);
    }

    /**
     * §9.1.4.2-3 p.61-62 : Cch_i = part_i × Bch × INT / (Rg_i × Re × Rd × Rr).
     * Les deux générateurs partagent les émetteurs de base ; la nature
     * PAC/chaudière détermine les parts. §9.1.4 coupe entre les générateurs
     * d'une installation, pas entre les installations : un logement qui en
     * décrit plusieurs voit chacune porter sa part surfacique (§9.1 p.57) puis
     * la répartir entre ses deux générateurs.
     */
    private function computeParGenerateur(
        int $cfgId, float $besoin, float $besoinDep, float $rdim,
        DOMElement $node, CalculationContext $context, NodeAccessor $accessor,
    ): bool {
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
            $role = $cfgId === 6 ? $this->roleBoisOuReleve($normalise) : $this->rolePacOuChaudiere($normalise);
            $rg = RendementGenerateur::lire($accessor, $gen);
            if ($role === null || isset($couple[$role]) || $rg === null || $rg <= 0.0) {
                return false;
            }
            $couple[$role] = [$gen, $rg];
            $hybride = $hybride || GenerateurChAlias::isHybride($type);
        }
        $attendus = $cfgId === 6 ? ['bois', 'releve'] : ['pac', 'chaudiere'];
        foreach ($attendus as $attendu) {
            if (!isset($couple[$attendu])) {
                return false;
            }
        }
        $zoneId = $context->zoneClimatique
            ?? $accessor->getStringOrNull('//meteo/enum_zone_climatique_id')
            ?? $accessor->getStringOrNull('//caracteristique_generale/enum_zone_climatique_id');
        $zone = $context->zoneGroupe ?? CalculationContext::zoneGroupeFromId($zoneId) ?? 'H1';
        // §9.1.4.1 p.61 : Cch1 = 0,75 × Bch (chaudière bois), Cch2 = 0,25 × Bch.
        $parts = $cfgId === 6
            ? ['bois' => 0.75, 'releve' => 0.25]
            : ($hybride ? GenerateurChAlias::prorataHybride($zone) : ['pac' => 0.8, 'chaudiere' => 0.2]);
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

    /**
     * §9.1.4.2 p.61 parle d'une « chaudière en relève de PAC » sans
     * restreindre son énergie : la chaudière électrique (enum 106) en est une
     * au même titre que les chaudières à combustion.
     */
    private function rolePacOuChaudiere(?int $normalise): ?string
    {
        if ($normalise === null) {
            return null;
        }
        if ($normalise >= 1 && $normalise <= 19) {
            return 'pac';
        }

        return ($normalise >= 55 && $normalise <= 68)
            || ($normalise >= 75 && $normalise <= 97)
            || $normalise === self::CHAUDIERE_ELECTRIQUE ? 'chaudiere' : null;
    }

    /**
     * §9.1.4.1 p.61 : « une chaudière bois assurant principalement le
     * chauffage sauf par temps doux ou en mi-saison où une PAC ou chaudière
     * prend le relais ». La chaudière bois porte 0,75 du besoin, celle qui la
     * relève 0,25 — quelle que soit son énergie.
     *
     * Les chaudières bois sont les types 55 à 74 du XSD, bûche, plaquette et
     * granulés confondus ; les poêles bouilleur 48 et 49 chauffent le même
     * réseau d'eau.
     */
    private function roleBoisOuReleve(?int $normalise): ?string
    {
        if ($normalise === null) {
            return null;
        }
        if (($normalise >= 55 && $normalise <= 74) || $normalise === 48 || $normalise === 49) {
            return 'bois';
        }

        return $this->rolePacOuChaudiere($normalise) !== null ? 'releve' : null;
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
