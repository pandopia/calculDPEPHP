<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement\Combustion;

use CalculDpePHP\Chauffage\BesoinChauffageCalculator;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Rendement conventionnel annuel moyen de génération chauffage — §13.2.3-13.2.4 p.92.
 *
 *   Rg_ch_PCS = Pmfou / (Pmcons + 0,45 × QP0_PCS + Pveil_PCS)
 *   Rg_ch_PCI = k_PCS/PCI × Rg_ch_PCS
 *
 * Le profil de charge (§13.2.1.1) est le profil long à 10 niveaux.
 * Cdimref = 1000 × Pn / (GV_building × (Tcons - Tbase)) permet d'adapter
 * le profil aux charges partielles.
 *
 * GV_building = GV_appartement × (Sh_immeuble / Sh_appartement) pour installations collectives.
 *
 * @spec-section 13.2.3-13.2.4
 * @spec-pages   92
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/04-rendement-annuel-moyen.md
 * @xml-input    generateur_chauffage.donnee_intermediaire.{pn, rpn, rpint, qp0, pveil, temp_fonc_100, temp_fonc_30}
 * @xml-input    generateur_chauffage.donnee_entree.{enum_type_energie_id, presence_regulation_combustion}
 * @xml-output   generateur_chauffage.donnee_intermediaire.rendement_generation
 * @depends-on   \CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereProfilChargeCalculator
 * @depends-on   \CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator
 * @tables       (aucune)
 */
final class RendementAnnuelMoyenCalculator implements CalculatorInterface
{
    /** Profil de charge long (occupation longue — §13.2.1.1) */
    private const LOAD_POINTS  = [0.05, 0.15, 0.25, 0.35, 0.45, 0.55, 0.65, 0.75, 0.85, 0.95];
    private const LOAD_WEIGHTS = [0.10, 0.25, 0.20, 0.15, 0.10, 0.10, 0.05, 0.025, 0.025, 0.0];

    /** k_PCS/PCI par enum_type_energie_id */
    private const K_PCS_PCI = [
        1  => 1.00, // électricité
        2  => 1.11, // gaz naturel
        3  => 1.07, // fioul domestique
        4  => 1.08, // bois bûches
        5  => 1.08, // bois granulés
        6  => 1.08, // bois plaquettes forestières
        7  => 1.08, // bois plaquettes d'industrie
        8  => 1.00, // réseau de chaleur urbain
        9  => 1.09, // propane
        10 => 1.09, // butane
        11 => 1.04, // charbon
        12 => 1.00, // électricité renouvelable
    ];

    /** Tbase (°C) par [zone_climatique_id] [altitude_classe_id] */
    private const TBASE = [
        // H1 (zones 1=H1a, 2=H1b, 3=H1c)
        1 => [1 => -9.5, 2 => -11.5, 3 => -13.5],
        2 => [1 => -9.5, 2 => -11.5, 3 => -13.5],
        3 => [1 => -9.5, 2 => -11.5, 3 => -13.5],
        // H2 (zones 4=H2a, 5=H2b, 6=H2c, 7=H2d)
        4 => [1 => -6.5, 2 => -8.5, 3 => -10.5],
        5 => [1 => -6.5, 2 => -8.5, 3 => -10.5],
        6 => [1 => -6.5, 2 => -8.5, 3 => -10.5],
        7 => [1 => -6.5, 2 => -8.5, 3 => -10.5],
        // H3 (zone 8)
        8 => [1 => -3.5, 2 => -5.5, 3 => -7.5],
    ];

    /** Chaudières couvertes par ce calculateur (combustion gaz/fioul/bois) */
    private const COMBUSTION_MIN = 20;
    private const COMBUSTION_MAX = 97;

    /** IDs chaudières bois (profil 50% de charge — §13.2.2.3) */
    private const BOIS_IDS = [55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74];

    /** IDs générateurs air chaud à combustion (profil 50% de charge — §13.2.2.1) */
    private const AIR_CHAUD_IDS = [50, 51, 52];

    /** IDs radiateurs gaz (formule spécifique §13.2.2.2) */
    private const RADIATEUR_GAZ_IDS = [53, 54];

    /** IDs chaudières à condensation */
    private const CONDENSATION_IDS = [83, 84, 94, 95, 96, 97];

    /** IDs chaudières basse température */
    private const BT_IDS = [81, 82, 91, 92, 93];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [ChaudiereProfilChargeCalculator::class, ChaudiereDefautCalculator::class, BesoinChauffageCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'generateur_chauffage';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $genId    = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $node);

        if ($genId === null || $genId < self::COMBUSTION_MIN || $genId > self::COMBUSTION_MAX) {
            return;
        }

        // Caractéristiques de la chaudière (PCI)
        $pn     = $accessor->getFloatOrNull('./donnee_intermediaire/pn',    $node);
        $rpn    = $accessor->getFloatOrNull('./donnee_intermediaire/rpn',   $node);
        $rpint  = $accessor->getFloatOrNull('./donnee_intermediaire/rpint', $node);
        $qp0    = $accessor->getFloatOrNull('./donnee_intermediaire/qp0',   $node);
        $pveil  = $accessor->getFloatOrNull('./donnee_intermediaire/pveil', $node) ?? 0.0;

        if ($pn === null || $rpn === null || $rpint === null || $qp0 === null) {
            return; // données manquantes — table partielle
        }

        $tfonc100 = $accessor->getFloatOrNull('./donnee_intermediaire/temp_fonc_100', $node) ?? 70.0;
        $tfonc30  = $accessor->getFloatOrNull('./donnee_intermediaire/temp_fonc_30',  $node) ?? 52.5;

        // Énergie → k_PCS/PCI
        $energieId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $node) ?? 2;
        $k         = self::K_PCS_PCI[$energieId] ?? 1.11;

        // Conversion PCI → PCS
        $rpn_pcs   = $rpn  / $k;   // rendement en PCS (fraction)
        $rpint_pcs = $rpint / $k;
        $qp0_pcs   = $qp0  * $k;   // W
        $pveil_pcs = $pveil * $k;   // W
        $pn_kw     = $pn / 1000.0; // kW

        // Températures de fonctionnement (°C) — déjà en PCS (pas de conversion)
        // (Tfonc sont des températures physiques, indépendantes de PCI/PCS)

        // Cdimref = 1000 × Pngen_kW / (GV_building × (Tcons - Tbase))
        $gvBuilding = $this->resolveGvBuilding($node, $accessor, $context);
        $tbase      = $this->resolveTbase($context, $accessor);
        $tcons      = 19.0;
        $cdimref    = ($gvBuilding > 0.0) ? (1000.0 * $pn_kw) / ($gvBuilding * ($tcons - $tbase)) : 1.0;

        // Calcul QPx selon le type de chaudière
        $boilerCat = $this->boilerCategory($genId);
        $regulation = $accessor->getIntOrNull('./donnee_entree/presence_regulation_combustion', $node) === 1;

        // Puissances moyennes
        $pmFou  = 0.0;
        $pmCons = 0.0;

        foreach (self::LOAD_POINTS as $i => $x) {
            $weight = self::LOAD_WEIGHTS[$i];
            if ($weight <= 0.0) {
                continue;
            }

            // Tchx_dim : Tch95 a une règle spéciale (toujours = Tch95)
            $tchDim = ($x >= 0.95) ? $x : min($x / max($cdimref, 1e-6), 1.0);

            $px_kw = $pn_kw * $tchDim; // kW
            if ($px_kw <= 0.0) {
                continue;
            }

            $qpx_kw = $this->computeQpx($boilerCat, $rpn_pcs, $rpint_pcs, $tfonc100, $tfonc30, $qp0_pcs, $pn_kw, $tchDim, $regulation);

            $pfou  = $px_kw * $weight;
            $pcons = $pfou * ($px_kw + $qpx_kw) / $px_kw;

            $pmFou  += $pfou;
            $pmCons += $pcons;
        }

        if ($pmFou <= 0.0 || $pmCons <= 0.0) {
            return;
        }

        // QP0 et Pveil en kW
        $qp0_kw   = $qp0_pcs  / 1000.0;
        $pveil_kw = $pveil_pcs / 1000.0;

        $rgPcs = $pmFou / ($pmCons + 0.45 * $qp0_kw + $pveil_kw);
        $rgPci = $k * $rgPcs;

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_generation', $rgPci);
    }

    /**
     * Calcule QPx (kW, en PCS) pour un taux de charge Tchx_dim.
     *
     * @param float $rpn_pcs    Rpn en PCS (fraction, ex. 0.863)
     * @param float $rpint_pcs  Rpint en PCS (fraction)
     * @param float $qp0_pcs    QP0 en W (PCS)
     * @param float $pn_kw      Puissance nominale en kW
     * @param float $tchDim     Taux de charge (0-1)
     */
    private function computeQpx(
        string $boilerCat,
        float $rpn_pcs,
        float $rpint_pcs,
        float $tfonc100,
        float $tfonc30,
        float $qp0_pcs,
        float $pn_kw,
        float $tchDim,
        bool $regulation,
    ): float {
        // Conversion QP0 kW pour le calcul
        $qp0_kw = $qp0_pcs / 1000.0;

        return match ($boilerCat) {
            'condensation' => $this->qpxCondensation($rpn_pcs, $rpint_pcs, $tfonc100, $tfonc30, $qp0_kw, $pn_kw, $tchDim, $regulation),
            'bt'           => $this->qpxBt($rpn_pcs, $rpint_pcs, $tfonc100, $tfonc30, $qp0_kw, $pn_kw, $tchDim, $regulation),
            default        => $this->qpxStandard($rpn_pcs, $rpint_pcs, $qp0_kw, $pn_kw, $tchDim, $regulation),
        };
    }

    /** QPx chaudière condensation — point w=15% (§13.2.1.5) */
    private function qpxCondensation(
        float $rp_n, float $rp_int, float $tf100, float $tf30,
        float $qp0, float $pn, float $x, bool $reg,
    ): float {
        [$qp15, $qp30, $qp100] = $this->condPoints($rp_n, $rp_int, $tf100, $tf30, $qp0, $pn, $reg);
        return $this->interpolateCond($x, $qp0, $qp15, $qp30, $qp100);
    }

    /** Calcule QP15, QP30, QP100 pour condensation/BT */
    private function condPoints(
        float $rp_n, float $rp_int, float $tf100, float $tf30,
        float $qp0, float $pn, bool $reg,
    ): array {
        // Condensation §13.2.1.5 : QP30 avec coefficient 0.2 × (33 − Tfonc)
        //   Tfonc = Tfonc_30 s'il y a une régulation de combustion, Tfonc_100 sinon.
        $tfRef    = $reg ? $tf30 : $tf100;
        $numQp30  = 100.0 - ($rp_int * 100.0 + 0.2 * (33.0 - $tfRef));
        $denQp30  = $rp_int * 100.0 + 0.2 * (33.0 - $tfRef);
        $qp30     = ($denQp30 > 0.0) ? 0.3 * $pn * $numQp30 / $denQp30 : 0.0;
        $qp15     = $qp30 / 2.0;

        $numQp100 = 100.0 - ($rp_n * 100.0 + 0.1 * (70.0 - $tf100));
        $denQp100 = $rp_n * 100.0 + 0.1 * (70.0 - $tf100);
        $qp100    = ($denQp100 > 0.0) ? $pn * $numQp100 / $denQp100 : 0.0;

        return [$qp15, $qp30, $qp100];
    }

    /** Interpolation piecewise condensation (0-15%, 15-30%, 30-100%) */
    private function interpolateCond(float $x, float $qp0, float $qp15, float $qp30, float $qp100): float
    {
        if ($x <= 0.15) {
            return ($qp15 - 0.15 * $qp0) * $x / 0.15 + 0.15 * $qp0;
        }
        if ($x <= 0.30) {
            return ($qp30 - $qp15) * ($x - 0.15) / 0.15 + $qp15;
        }
        return ($qp100 - $qp30) * ($x - 0.30) / 0.70 + $qp30;
    }

    /** QPx chaudière basse température — point w=15% (§13.2.1.5) */
    private function qpxBt(
        float $rp_n, float $rp_int, float $tf100, float $tf30,
        float $qp0, float $pn, float $x, bool $reg,
    ): float {
        // BT : QP30 avec coefficient 0.1 × (40 - Tfonc_30 ou Tfonc_100 selon régulation)
        $tfRef    = $reg ? $tf30 : $tf100;
        $numQp30  = 100.0 - ($rp_int * 100.0 + 0.1 * (40.0 - $tfRef));
        $denQp30  = $rp_int * 100.0 + 0.1 * (40.0 - $tfRef);
        $qp30     = ($denQp30 > 0.0) ? 0.3 * $pn * $numQp30 / $denQp30 : 0.0;
        $qp15     = $qp30 / 2.0;

        $numQp100 = 100.0 - ($rp_n * 100.0 + 0.1 * (70.0 - $tf100));
        $denQp100 = $rp_n * 100.0 + 0.1 * (70.0 - $tf100);
        $qp100    = ($denQp100 > 0.0) ? $pn * $numQp100 / $denQp100 : 0.0;

        return $this->interpolateCond($x, $qp0, $qp15, $qp30, $qp100);
    }

    /** QPx chaudière standard — point w=30% (§13.2.1.6) */
    private function qpxStandard(
        float $rp_n, float $rp_int, float $qp0, float $pn, float $x, bool $reg,
    ): float {
        // QP30 avec Tfonc référence: 50°C (standard)
        $numQp30  = 100.0 - ($rp_int * 100.0 + 0.1 * (50.0 - 52.5)); // Tfonc_30 standard
        $denQp30  = $rp_int * 100.0 + 0.1 * (50.0 - 52.5);
        $qp30     = ($denQp30 > 0.0) ? 0.3 * $pn * $numQp30 / $denQp30 : 0.0;

        $numQp100 = 100.0 - ($rp_n * 100.0 + 0.1 * (70.0 - 70.0));
        $denQp100 = $rp_n * 100.0 + 0.1 * (70.0 - 70.0);
        $qp100    = ($denQp100 > 0.0) ? $pn * $numQp100 / $denQp100 : 0.0;

        if ($x <= 0.30) {
            return ($qp30 - 0.15 * $qp0) * $x / 0.30 + 0.15 * $qp0;
        }
        return ($qp100 - $qp30) * ($x - 0.30) / 0.70 + $qp30;
    }

    private function boilerCategory(int $genId): string
    {
        if (in_array($genId, self::CONDENSATION_IDS, true)) {
            return 'condensation';
        }
        if (in_array($genId, self::BT_IDS, true)) {
            return 'bt';
        }
        return 'standard';
    }

    /**
     * Détermine GV bâtiment (W/K).
     * chauffage.gv représente déjà le GV du bâtiment entier (calculé sur l'enveloppe complète
     * du logement XML, que ce soit un DPE bâtiment ou un DPE zone/appartement).
     */
    private function resolveGvBuilding(DOMElement $node, NodeAccessor $accessor, CalculationContext $context): float
    {
        $gv = (float)($context->get('chauffage.gv') ?? 0.0);
        if ($gv <= 0.0) {
            return 0.0;
        }

        // Immeuble avec chauffage individuel (§17.1.4.2) : Cdimref doit être calculé
        // à l'échelle servie par l'installation.
        //   • Si l'installation déclare sa surface : GV utile = GV × sh_install/sh_immeuble.
        //   • Sinon : GV utile = GV_immeuble / Nblgt (« appartement moyen »).
        $modeApp = $accessor->getIntOrNull('//caracteristique_generale/enum_methode_application_dpe_log_id');
        if ($modeApp !== null && in_array($modeApp, [6, 8, 10, 12], true)) {
            $shImmeuble = $accessor->getFloatOrNull('//caracteristique_generale/surface_habitable_immeuble');
            $shInstall  = $this->getSurfaceInstallation($node, $accessor);
            if ($shImmeuble !== null && $shImmeuble > 0.0
                && $shInstall !== null && $shInstall > 0.0
                && $shInstall < $shImmeuble) {
                return $gv * ($shInstall / $shImmeuble);
            }
            $nblgt = $accessor->getIntOrNull('//caracteristique_generale/nombre_appartement');
            if ($nblgt !== null && $nblgt > 1) {
                return $gv / $nblgt;
            }
        }
        return $gv;
    }

    /**
     * Lit la surface couverte par l'installation parente (chauffage ou ECS).
     */
    private function getSurfaceInstallation(DOMElement $genNode, NodeAccessor $accessor): ?float
    {
        $parent = $genNode->parentNode?->parentNode;
        if (!$parent instanceof DOMElement) {
            return null;
        }
        if ($parent->nodeName === 'installation_chauffage') {
            return $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $parent);
        }
        if ($parent->nodeName === 'installation_ecs') {
            return $accessor->getFloatOrNull('./donnee_entree/surface_habitable', $parent);
        }
        return null;
    }

    private function resolveTbase(CalculationContext $context, NodeAccessor $accessor): float
    {
        $cached = $context->get('logement.tbase');
        if ($cached !== null) {
            return (float)$cached;
        }

        $zoneId    = (int)($context->get('logement.zone_climatique_id')
            ?? $accessor->getIntOrNull('//meteo/enum_zone_climatique_id')
            ?? $accessor->getIntOrNull('//caracteristique_generale/enum_zone_climatique_id')
            ?? 1);
        $altClasse = (int)($accessor->getIntOrNull('//meteo/enum_classe_altitude_id')
            ?? $accessor->getIntOrNull('//caracteristique_generale/enum_classe_altitude_id')
            ?? 1);

        $tbase = (float)(self::TBASE[$zoneId][$altClasse] ?? -9.5);
        $context->set('logement.tbase', $tbase);
        return $tbase;
    }
}
