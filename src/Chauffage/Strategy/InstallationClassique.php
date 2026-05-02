<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Strategy;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Installation de chauffage classique (§9.1.2 p.59).
 *
 * Cch = Bch_moy × INT / (Rg × Re × Rd × Rr)
 *
 * avec :
 *   Bch_moy = besoin de l'appartement moyen = Bch_immeuble × (S_chauffée / Sh_immeuble) / rdim_effectif
 *   INT = I0 / (1 + 0,1 × (G - 1))
 *   G   = GV_total / (Hsp × Sh_immeuble)
 *
 * rdim_effectif :
 *   - BAT (methode_calcul=1) : valeur de <rdim> dans la donnée d'entrée
 *   - ZONE individuel (methode_calcul=4, type_installation=1) :
 *       nombre_appartement × ratio_virtualisation / Σ(nombre_logement_echantillon)
 *   - ZONE collectif (methode_calcul=4, type_installation=2) :
 *       valeur de <rdim> (=1 pour un unique système collectif)
 *
 * @spec-section  9.1.2
 * @spec-pages    59
 * @spec-source   resources/specsplitted/09-conso-chauffage/01-installation-seule/02-classique.md
 * @xml-input     installation_chauffage.donnee_entree.{surface_chauffee, rdim, enum_methode_calcul_conso_id,
 *                    enum_type_installation_id, nombre_logement_echantillon, ratio_virtualisation}
 *                logement.donnee_entree.{hsp, surface_habitable_immeuble, nombre_appartement}
 *                emetteur_chauffage.donnee_intermediaire.{i0, rendement_emission, rendement_distribution, rendement_regulation}
 *                generateur_chauffage.donnee_intermediaire.rendement_generation
 * @xml-output    installation_chauffage.donnee_intermediaire.{besoin_ch, besoin_ch_depensier, conso_ch, conso_ch_depensier}
 *                generateur_chauffage.donnee_intermediaire.{conso_ch, conso_ch_depensier}
 * @depends-on    \CalculDpePHP\Chauffage\BesoinChauffageCalculator
 *                \CalculDpePHP\Chauffage\Rendement\EmissionCalculator
 *                \CalculDpePHP\Chauffage\Rendement\DistributionCalculator
 *                \CalculDpePHP\Chauffage\Rendement\RegulationCalculator
 *                \CalculDpePHP\Chauffage\Rendement\GenerationNonCombustionCalculator
 *                \CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator
 * @tables        (aucune)
 */
final class InstallationClassique implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Chauffage\BesoinChauffageCalculator',
            '\CalculDpePHP\Chauffage\Rendement\EmissionCalculator',
            '\CalculDpePHP\Chauffage\Rendement\DistributionCalculator',
            '\CalculDpePHP\Chauffage\Rendement\RegulationCalculator',
            '\CalculDpePHP\Chauffage\Rendement\GenerationNonCombustionCalculator',
            '\CalculDpePHP\Chauffage\Rendement\Combustion\RendementAnnuelMoyenCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        if ($node->nodeName !== 'installation_chauffage') {
            return false;
        }
        $cfgId = null;
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                foreach ($child->childNodes as $c) {
                    if ($c instanceof DOMElement && $c->nodeName === 'enum_cfg_installation_ch_id') {
                        $cfgId = (int)trim($c->textContent);
                        break 2;
                    }
                }
            }
        }
        return $cfgId === 1;
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Besoin bâtiment (contexte) ─────────────────────────────────────
        $bchImmeuble    = (float)$context->get('chauffage.besoin_ch', 0.0);
        $bchDepImmeuble = (float)$context->get('chauffage.besoin_ch_depensier', 0.0);
        $gv             = (float)$context->get('chauffage.gv', 1.0);

        // ── 2. Données d'entrée de l'installation ─────────────────────────────
        $de = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $node) ?? 0.0;
        $rdim           = $accessor->getFloatOrNull('./donnee_entree/rdim', $node) ?? 1.0;
        $ratioVirt      = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation', $node) ?? 1.0;
        $methode        = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $node) ?? 1;
        $typeInstall    = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $node) ?? 1;

        // ── 3. Données bâtiment (nœud parent logement) ───────────────────────
        $logementNode = $node->parentNode?->parentNode;
        $hsp        = $accessor->getFloatOrNull('./caracteristique_generale/hsp', $logementNode) ?? 2.5;
        $nbreAppt   = $accessor->getFloatOrNull('./caracteristique_generale/nombre_appartement', $logementNode) ?? 1.0;

        // For appartement/maison DPEs, use sh_logement as reference surface (besoin is at logement scale).
        // For immeuble DPEs, use sh_immeuble (besoin is at building scale).
        $methodeLog = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logementNode);
        $isMaisonOrAppart = in_array($methodeLog, [1, 2, 3, 4, 5, 14, 18, 31, 32, 35, 36, 37], true);
        if ($isMaisonOrAppart) {
            $shImmeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $logementNode) ?? $de;
        } else {
            $shImmeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logementNode) ?? $de;
        }

        // ── 4. Σ(nombre_logement_echantillon) pour rdim ZONE individuel ───────
        $sumEchantillon = 0.0;
        $collection = $node->parentNode;
        if ($collection instanceof DOMElement) {
            foreach ($collection->childNodes as $sibling) {
                if ($sibling instanceof DOMElement && $sibling->nodeName === 'installation_chauffage') {
                    $ne = $accessor->getFloatOrNull('./donnee_entree/nombre_logement_echantillon', $sibling) ?? 1.0;
                    $sumEchantillon += $ne;
                }
            }
        }
        if ($sumEchantillon === 0.0) {
            $sumEchantillon = 1.0;
        }

        // ── 5. rdim effectif ──────────────────────────────────────────────────
        if ($methode === 1) {
            // BAT : rdim fourni directement
            $rdimEffective = $rdim;
        } elseif ($typeInstall === 1) {
            // ZONE individuel : extrapolation échantillon → immeuble
            $rdimEffective = $nbreAppt * $ratioVirt / $sumEchantillon;
        } else {
            // ZONE collectif : un unique système collectif, rdim=1
            $rdimEffective = $rdim;
        }
        if ($rdimEffective <= 0.0) {
            $rdimEffective = 1.0;
        }

        // ── 6. Besoin de l'installation (fraction surfacique) ─────────────────
        $surfaceRatio = $shImmeuble > 0.0 ? $de / $shImmeuble : 1.0;
        $besoinInstall    = $bchImmeuble    * $surfaceRatio;
        $besoinInstallDep = $bchDepImmeuble * $surfaceRatio;

        // ── 7. Besoin de l'appartement moyen ──────────────────────────────────
        $besoinMoy    = $besoinInstall    / $rdimEffective;
        $besoinMoyDep = $besoinInstallDep / $rdimEffective;

        // ── 8. Facteur G et intermittence INT ─────────────────────────────────
        $g   = ($hsp * $shImmeuble) > 0.0 ? $gv / ($hsp * $shImmeuble) : 1.0;
        $i0  = $this->weightedEmetteurFloat($accessor, $node, 'i0') ?? 1.0;
        $int = $i0 / (1.0 + 0.1 * ($g - 1.0));

        // ── 9. Rendements (moyenne pondérée par surface si plusieurs émetteurs) ─
        $re = $this->weightedEmetteurFloat($accessor, $node, 'rendement_emission') ?? 1.0;
        $rd = $this->weightedEmetteurFloat($accessor, $node, 'rendement_distribution') ?? 1.0;
        $rr = $this->weightedEmetteurFloat($accessor, $node, 'rendement_regulation') ?? 1.0;
        $rg = $this->weightedGenerateurFloat($accessor, $node, 'rendement_generation') ?? 1.0;

        // ── 10. Consommation de l'appartement moyen ───────────────────────────
        $denom      = max(1e-9, $rg * $re * $rd * $rr);
        $consoCh    = $besoinMoy    * $int / $denom;
        $consoChDep = $besoinMoyDep * $int / $denom;

        // ── 11. Écriture dans installation.donnee_intermediaire ───────────────
        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch',          $besoinInstall);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $besoinInstallDep);
        $accessor->setChildValue($di, 'conso_ch',           $consoCh);
        $accessor->setChildValue($di, 'conso_ch_depensier', $consoChDep);

        // ── 12. Écriture dans chaque generateur.donnee_intermediaire ──────────
        $genCollection = $this->getChild($node, 'generateur_chauffage_collection');
        if ($genCollection !== null) {
            foreach ($genCollection->childNodes as $gen) {
                if (!($gen instanceof DOMElement) || $gen->nodeName !== 'generateur_chauffage') {
                    continue;
                }
                $genDi = $accessor->ensureDonneeIntermediaire($gen);
                $accessor->setChildValue($genDi, 'conso_ch',           $consoCh);
                $accessor->setChildValue($genDi, 'conso_ch_depensier', $consoChDep);
            }
        }
    }

    /**
     * Retourne la moyenne pondérée par surface_chauffee d'un champ DI d'émetteur.
     */
    private function weightedEmetteurFloat(NodeAccessor $accessor, DOMElement $installNode, string $field): ?float
    {
        $emCollection = $this->getChild($installNode, 'emetteur_chauffage_collection');
        if ($emCollection === null) {
            return null;
        }

        $totalSurface = 0.0;
        $weightedSum  = 0.0;

        foreach ($emCollection->childNodes as $em) {
            if (!($em instanceof DOMElement) || $em->nodeName !== 'emetteur_chauffage') {
                continue;
            }
            $surface = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $em) ?? 1.0;
            $value   = $accessor->getFloatOrNull("./donnee_intermediaire/{$field}", $em);
            if ($value === null) {
                continue;
            }
            $totalSurface += $surface;
            $weightedSum  += $surface * $value;
        }

        return $totalSurface > 0.0 ? $weightedSum / $totalSurface : null;
    }

    /**
     * Retourne la moyenne pondérée par conso_generation d'un champ DI de générateur.
     * Pour InstallationClassique (1 générateur), c'est la valeur directe.
     */
    private function weightedGenerateurFloat(NodeAccessor $accessor, DOMElement $installNode, string $field): ?float
    {
        $genCollection = $this->getChild($installNode, 'generateur_chauffage_collection');
        if ($genCollection === null) {
            return null;
        }

        $count = 0;
        $sum   = 0.0;

        foreach ($genCollection->childNodes as $gen) {
            if (!($gen instanceof DOMElement) || $gen->nodeName !== 'generateur_chauffage') {
                continue;
            }
            $value = $accessor->getFloatOrNull("./donnee_intermediaire/{$field}", $gen);
            if ($value === null) {
                continue;
            }
            $sum += $value;
            $count++;
        }

        return $count > 0 ? $sum / $count : null;
    }

    private function getChild(DOMElement $parent, string $tag): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $tag) {
                return $child;
            }
        }
        return null;
    }
}
