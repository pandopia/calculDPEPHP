<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement\Combustion;

use CalculDpePHP\Chauffage\BesoinChauffageCalculator;
use CalculDpePHP\Chauffage\CascadeGenerateurs;
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
 * Cdimref = Pn / (GV_ratio × (Tcons - Tbase)) permet d'adapter le profil aux
 * charges partielles.
 *
 * Point que la formule seule ne dit pas, et que §13.2.1.2 (« présence d'un ou
 * plusieurs générateurs à combustion indépendants ») impose : **Pn est la
 * puissance cumulée** de tous les générateurs à combustion du logement, pas
 * celle du seul générateur courant. Le taux de charge d'une chaudière dépend
 * de la puissance installée face au besoin, pas de sa puissance propre.
 * Cf. open3cl `9_chauffage.js::tauxChargeForGenerator`.
 *
 * GV_building : voir `resolveGvBuilding()`.
 *
 * @spec-section 13.2.2.2, 13.2.3-13.2.4, 17.1.2
 * @spec-pages   88, 92, 108-109
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/02-valeurs-defaut-gaz-fioul.md
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/04-rendement-annuel-moyen.md
 * @spec-source  resources/specsplitted/17-collectif/01-immeuble-collectif.md
 * @xml-input    generateur_chauffage.donnee_intermediaire.{pn, rpn, rpint, qp0, pveilleuse, temp_fonc_100, temp_fonc_30}
 * @xml-input    generateur_chauffage.donnee_entree.{enum_type_energie_id, presence_regulation_combustion}
 * @xml-input    installation_chauffage.donnee_entree.surface_chauffee
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
        // Normalisation GPL/charbon/hybride-chaudière → générateur équivalent (§13.2)
        $genId    = \CalculDpePHP\Chauffage\GenerateurChAlias::normalizeNode(
            $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $node),
            $node,
        );

        if ($genId === null || $genId < self::COMBUSTION_MIN || $genId > self::COMBUSTION_MAX) {
            return;
        }

        // Caractéristiques de la chaudière (PCI)
        $pn     = $accessor->getFloatOrNull('./donnee_intermediaire/pn',    $node);
        $rpn    = $accessor->getFloatOrNull('./donnee_intermediaire/rpn',   $node);
        $rpint  = $accessor->getFloatOrNull('./donnee_intermediaire/rpint', $node);
        $qp0    = $accessor->getFloatOrNull('./donnee_intermediaire/qp0',   $node);
        $pveil  = $accessor->getFloatOrNull('./donnee_intermediaire/pveilleuse', $node) ?? 0.0;

        $estRadiateurGaz = in_array($genId, self::RADIATEUR_GAZ_IDS, true);
        if ($pn === null || $rpn === null || (!$estRadiateurGaz && ($rpint === null || $qp0 === null))) {
            return; // données manquantes — table partielle
        }
        // §13.2.2.2 : la loi QPx du radiateur ne dépend pas de Rpint et
        // s'annule à l'arrêt. Ces données ne sont pas tabulées pour lui.
        $rpint ??= 0.0;
        $qp0 ??= 0.0;

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

        // Cdimref = 1000 × Pngen_kW / (GV_building × (Tcons - Tbase)).
        // Le profil conventionnel utilise 19 °C et le profil dépensier 21 °C.
        [$gvBuilding, $gvRameneAuLogementMoyen] = $this->resolveGvBuilding($node, $accessor, $context);
        $tbase      = $this->resolveTbase($context, $accessor);
        // §13.2.1.2 : le taux de charge se juge sur la puissance installée, et
        // Cdimref rapporte cette puissance au GV. Les deux doivent donc couvrir
        // le même périmètre. Quand resolveGvBuilding ramène le GV à
        // l'appartement « moyen » (§17.1.4.2), la puissance à lui comparer est
        // celle de la seule installation du générateur : sommer les chaudières
        // de tous les appartements visités face au GV d'un seul les confondrait.
        // Sinon le GV reste celui du bâtiment entier et la puissance installée
        // est le cumul de tous ses générateurs.
        // Hors appartement généré, open3cl — citant le moteur de référence CSTB
        // (`Calcul_batiment.cs`, CTchx) — pose `Cdimref += Pn × Rdim / (GV ×
        // (Tcons − Tbase))` : le GV de l'immeuble entier, et `rdim` qui compte
        // une installation individuelle autant de fois qu'elle représente de
        // logements. Le Cdimref est unique pour tous les générateurs.
        $pnCumule   = $gvRameneAuLogementMoyen
            ? $this->puissanceDuGenerateurMoyen($node, $accessor, $context)
            : $this->puissanceCombustionPonderee($node, $accessor, $context);

        // Calcul QPx selon le type de chaudière
        $boilerCat = $this->boilerCategory($genId);
        $regulation = $accessor->getIntOrNull('./donnee_entree/presence_regulation_combustion', $node) === 1;

        // §13.2.1.3.1 : en cascade avec priorité, chaque générateur a son
        // propre profil de charge, bâti sur sa contribution au taux de charge
        // du système.
        $cascade = $this->cascadeAvecPriorite($node, $accessor);

        $rgPci = $this->computeAnnualYield(
            19.0, $tbase, $gvBuilding, $pn_kw, $pnCumule / 1000.0, $boilerCat, $rpn_pcs,
            $rpint_pcs, $tfonc100, $tfonc30, $qp0_pcs, $pveil_pcs, $k, $regulation, $cascade,
        );
        $rgPciDepensier = $this->computeAnnualYield(
            21.0, $tbase, $gvBuilding, $pn_kw, $pnCumule / 1000.0, $boilerCat, $rpn_pcs,
            $rpint_pcs, $tfonc100, $tfonc30, $qp0_pcs, $pveil_pcs, $k, $regulation, $cascade,
        );

        if ($rgPci === null || $rgPciDepensier === null) {
            return;
        }

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'rendement_generation', $rgPci);

        // Le XSD ne possède pas de balise rendement_generation_depensier : la
        // valeur reste interne au pipeline et est indexée par la référence du
        // générateur.
        $depensierByReference = (array)$context->get('chauffage.rendement_generation_depensier', []);
        $reference = $accessor->getStringOrNull('./donnee_entree/reference', $node);
        $key = $reference ?? '#' . count($depensierByReference);
        $depensierByReference[$key] = $rgPciDepensier;
        $context->set('chauffage.rendement_generation_depensier', $depensierByReference);
    }

    /**
     * §13.2.3-13.2.4 p.92 : rendement annuel moyen pour une température
     * conventionnelle donnée (19 °C ou 21 °C).
     *
     * @spec-formula F-13.2.3-rg-annuel
     */
    private function computeAnnualYield(
        float $tcons,
        float $tbase,
        float $gvBuilding,
        float $pnKw,
        float $pnCumuleKw,
        string $boilerCat,
        float $rpnPcs,
        float $rpintPcs,
        float $tfonc100,
        float $tfonc30,
        float $qp0Pcs,
        float $pveilPcs,
        float $k,
        bool $regulation,
        ?array $cascade = null,
    ): ?float {
        // Cdimref porte sur la puissance installée totale ; le profil de
        // charge qui en découle s'applique ensuite à ce générateur.
        $cdimref = $gvBuilding > 0.0
            ? (1000.0 * $pnCumuleKw) / ($gvBuilding * ($tcons - $tbase))
            : 1.0;
        $pmFou = 0.0;
        $pmCons = 0.0;

        foreach ($this->profilDeCharge($cdimref, $cascade) as [$tchDim, $weight]) {
            $pxKw = $pnKw * $tchDim;
            if ($pxKw <= 0.0) {
                continue;
            }
            $qpxKw = $this->computeQpx(
                $boilerCat, $rpnPcs, $rpintPcs, $tfonc100, $tfonc30,
                $qp0Pcs, $pnKw, $tchDim, $regulation,
            );
            $pfou = $pxKw * $weight;
            $pmFou += $pfou;
            $pmCons += $pfou * ($pxKw + $qpxKw) / $pxKw;
        }

        if ($pmFou <= 0.0 || $pmCons <= 0.0) {
            return null;
        }

        $rgPcs = $pmFou / ($pmCons + 0.45 * ($qp0Pcs / 1000.0) + ($pveilPcs / 1000.0));
        return $k * $rgPcs;
    }

    /**
     * Profil de charge du générateur : couples (taux de charge final,
     * coefficient de pondération final).
     *
     * Hors cascade hiérarchisée, §13.2.1.2 pose `Tchx_final = Tchx_dim` et
     * `Coeff_pondx_final = Coeff_pondx_dim` : le profil conventionnel,
     * simplement resserré par Cdimref.
     *
     * En cascade avec priorité (§13.2.1.3.1), chaque générateur reçoit sa
     * contribution au taux de charge du système :
     *
     *   CTchx_dim(gen₁) = min(Prel₁ ; Tchx_dim)
     *   CTchx_dim(gen₂) = min(Prel₂ ; Tchx_dim − CTchx_dim(gen₁))
     *   Tchx_final(genᵢ) = min(1 ; CTchx_dim(genᵢ) / Prelᵢ)
     *
     * et sa pondération est renormalisée sur sa propre contribution :
     *
     *   Coeff_pondx_final(genᵢ) ∝ CTchx_dim(genᵢ) / Tchx_dim × Coeff_pondx_dim
     *
     * Le subordonné ne reçoit de charge qu'aux points où le prioritaire sature,
     * d'où un profil très différent du sien.
     *
     * @param array{prel: float, prelPrioritaire: float, prioritaire: bool}|null $cascade
     *
     * @return list<array{0: float, 1: float}>
     */
    private function profilDeCharge(float $cdimref, ?array $cascade): array
    {
        $points = [];
        $somme  = 0.0;

        foreach (self::LOAD_POINTS as $i => $x) {
            $weight = self::LOAD_WEIGHTS[$i];
            if ($weight <= 0.0) {
                continue;
            }

            // §13.2.1.2 : Tch95_dim = Tch95, le dernier point n'est pas resserré.
            $tchDim = $x >= 0.95 ? $x : min($x / max($cdimref, 1e-6), 1.0);

            if ($cascade === null) {
                $points[] = [$tchDim, $weight];
                continue;
            }

            $contributionPrioritaire = min($cascade['prelPrioritaire'], $tchDim);
            $contribution = $cascade['prioritaire']
                ? $contributionPrioritaire
                : max(0.0, min($cascade['prel'], $tchDim - $contributionPrioritaire));

            if ($contribution <= 0.0 || $tchDim <= 0.0) {
                continue;
            }

            $pond = $contribution / $tchDim * $weight;
            $points[] = [min(1.0, $contribution / max($cascade['prel'], 1e-9)), $pond];
            $somme += $pond;
        }

        if ($cascade === null || $somme <= 0.0) {
            return $points;
        }

        return array_map(
            static fn(array $point): array => [$point[0], $point[1] / $somme],
            $points,
        );
    }

    /**
     * Décrit la place de ce générateur dans une cascade avec priorité, ou null
     * s'il n'y en a pas.
     *
     * §13.2.1.3 : « ne seront traités que les configurations de cascade à deux
     * générateurs ». Au-delà, et hors cascade hiérarchisée, le profil
     * conventionnel s'applique tel quel.
     *
     * @return array{prel: float, prelPrioritaire: float, prioritaire: bool}|null
     */
    private function cascadeAvecPriorite(DOMElement $node, NodeAccessor $accessor): ?array
    {
        $installation = $this->installationParente($node);
        if ($installation === null) {
            return null;
        }

        $generateurs = [];
        foreach ($installation->getElementsByTagName('generateur_chauffage') as $gen) {
            if (!$gen instanceof DOMElement) {
                continue;
            }
            $genId = \CalculDpePHP\Chauffage\GenerateurChAlias::normalizeNode(
                $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $gen),
                $gen,
            );
            if ($genId !== null && $genId >= self::COMBUSTION_MIN && $genId <= self::COMBUSTION_MAX) {
                $generateurs[] = $gen;
            }
        }

        if (count($generateurs) !== 2 || !CascadeGenerateurs::avecPriorite($generateurs, $accessor)) {
            return null;
        }

        $puissances = [];
        foreach ($generateurs as $gen) {
            $pn = $accessor->getFloatOrNull('./donnee_intermediaire/pn', $gen) ?? 0.0;
            if ($pn <= 0.0) {
                return null;
            }
            $puissances[] = $pn;
        }
        $totale = array_sum($puissances);

        $prioritaires = CascadeGenerateurs::principaux($generateurs, $accessor);
        $indexPrioritaire = array_search($prioritaires[0], $generateurs, true);
        $index = array_search($node, $generateurs, true);
        if ($index === false || $indexPrioritaire === false) {
            return null;
        }

        return [
            'prel'            => $puissances[$index] / $totale,
            'prelPrioritaire' => $puissances[$indexPrioritaire] / $totale,
            'prioritaire'     => $index === $indexPrioritaire,
        ];
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
            'radiateur_gaz' => $this->qpxRadiateurGaz($rpn_pcs, $pn_kw, $tchDim),
            'condensation' => $this->qpxCondensation($rpn_pcs, $rpint_pcs, $tfonc100, $tfonc30, $qp0_kw, $pn_kw, $tchDim, $regulation),
            'bt'           => $this->qpxBt($rpn_pcs, $rpint_pcs, $tfonc100, $tfonc30, $qp0_kw, $pn_kw, $tchDim, $regulation),
            'point_50'     => $this->qpxPointA50PourCent($rpn_pcs, $rpint_pcs, $qp0_kw, $pn_kw, $tchDim),
            default        => $this->qpxStandard($rpn_pcs, $rpint_pcs, $tfonc100, $tfonc30, $qp0_kw, $pn_kw, $tchDim, $regulation),
        };
    }

    /**
     * §13.2.2.2 p.88 : QPx = 1,04 × (100 − RPn) / RPn × Pn × x.
     * RPn est ici une fraction sur PCS, comme pour les autres lois de pertes.
     * Sans pertes fixes, §13.2.4 se réduit à
     * Rg_PCI = k / (1 + 1,04 × (1 − RPn_PCS) / RPn_PCS),
     * indépendamment du profil de charge et de la puissance nominale.
     *
     * @spec-formula F-13.2.2.2-QPx
     */
    private function qpxRadiateurGaz(float $rpnPcs, float $pnKw, float $charge): float
    {
        return 1.04 * (1.0 - $rpnPcs) / max(1e-9, $rpnPcs) * $pnKw * $charge;
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
        float $rp_n, float $rp_int, float $tf100, float $tf30,
        float $qp0, float $pn, float $x, bool $reg,
    ): float {
        // Standard §13.2.1.6 : QP30 avec coefficient 0.1 × (50 − Tfonc)
        //   Tfonc = Tfonc_30 s'il y a une régulation de combustion, Tfonc_100 sinon
        //   (mêmes températures réelles que BT/condensation — open3cl QPx, vérifié
        //   sur 2662E2147774H : rg = 0.776571 avec tf100 = 80 °C).
        $tfRef    = $reg ? $tf30 : $tf100;
        $numQp30  = 100.0 - ($rp_int * 100.0 + 0.1 * (50.0 - $tfRef));
        $denQp30  = $rp_int * 100.0 + 0.1 * (50.0 - $tfRef);
        $qp30     = ($denQp30 > 0.0) ? 0.3 * $pn * $numQp30 / $denQp30 : 0.0;

        $numQp100 = 100.0 - ($rp_n * 100.0 + 0.1 * (70.0 - $tf100));
        $denQp100 = $rp_n * 100.0 + 0.1 * (70.0 - $tf100);
        $qp100    = ($denQp100 > 0.0) ? $pn * $numQp100 / $denQp100 : 0.0;

        if ($x <= 0.30) {
            return ($qp30 - 0.15 * $qp0) * $x / 0.30 + 0.15 * $qp0;
        }
        return ($qp100 - $qp30) * ($x - 0.30) / 0.70 + $qp30;
    }

    /**
     * §13.2.2.1 p.89 pour un générateur d'air chaud et §13.2.2.3 p.90 pour
     * une chaudière bois — les deux paragraphes donnent les mêmes formules —
     * placent le point de fonctionnement w à 50 % de charge, et non 30 %
     * comme une chaudière gaz ou fioul standard :
     *
     *   QP50  = 0,5 × Pn × (100 − RPint) / RPint
     *   QP100 = Pn × (100 − RPn) / RPn
     *   0 ≤ x ≤ 0,5  : QPx = [QP50 − 0,15 QP0] × x / 0,5 + 0,15 QP0
     *   0,5 < x ≤ 1  : QPx = [QP100 − QP50] × x / 0,5 + 2 QP50 − QP100
     *
     * Aucune correction de température n'y figure : ces paragraphes ne
     * retiennent que les rendements, là où §13.2.1.5 et §13.2.1.6 corrigent
     * QP30 par la température de fonctionnement du circuit d'eau.
     *
     * §13.2.2.3 p.90 ajoute que « les chaudières au charbon sont traitées
     * comme des chaudières bois bûche » ; GenerateurChAlias les y ramène
     * avant que la catégorie ne soit résolue.
     *
     * @spec-formula F-13.2.2.1-QPx
     */
    private function qpxPointA50PourCent(float $rp_n, float $rp_int, float $qp0, float $pn, float $x): float
    {
        $qp50  = $rp_int > 0.0 ? 0.5 * $pn * (100.0 - $rp_int * 100.0) / ($rp_int * 100.0) : 0.0;
        $qp100 = $rp_n > 0.0 ? $pn * (100.0 - $rp_n * 100.0) / ($rp_n * 100.0) : 0.0;

        if ($x <= 0.5) {
            return ($qp50 - 0.15 * $qp0) * $x / 0.5 + 0.15 * $qp0;
        }

        return ($qp100 - $qp50) * $x / 0.5 + 2.0 * $qp50 - $qp100;
    }

    private function boilerCategory(int $genId): string
    {
        if (in_array($genId, self::RADIATEUR_GAZ_IDS, true)) {
            return 'radiateur_gaz';
        }
        if (in_array($genId, self::AIR_CHAUD_IDS, true) || in_array($genId, self::BOIS_IDS, true)) {
            return 'point_50';
        }
        if (in_array($genId, self::CONDENSATION_IDS, true)) {
            return 'condensation';
        }
        if (in_array($genId, self::BT_IDS, true)) {
            return 'bt';
        }
        return 'standard';
    }

    /**
     * Détermine le GV (W/K) auquel se compare la puissance installée.
     *
     * `chauffage.gv` est le GV du bâtiment décrit par le XML.
     *
     * Immeuble avec chauffage individuel (§17.1.4.2) : Cdimref est calculé à
     * l'échelle de l'appartement moyen.
     *
     * §17.1.4.2 : quand le chauffage d'un immeuble est individuel, « le calcul
     * des consommations de chauffage est effectué sur la base d'un appartement
     * "moyen", à partir du besoin de chauffage de l'appartement "moyen"
     * (obtenu en multipliant le besoin de chauffage de l'immeuble Bch par le
     * rapport de la surface habitable de l'appartement "moyen" à celle de
     * l'immeuble, ce qui revient à diviser le besoin de chauffage Bch de
     * l'immeuble par le nombre de logements de l'immeuble Nblgt) ». Le
     * générateur individuel se compare donc au GV d'un logement moyen, soit
     * `GV / Nblgt`. Chauffage collectif : le calcul reste à l'immeuble, GV
     * entier.
     *
     * Les descriptions directes agrégées sont identifiées séparément : leurs
     * surfaces couvrent l'immeuble avec une multiplicité totale inférieure
     * à Nblgt. Leur GV suit la surface desservie par le générateur déclaré.
     * Pour les relevés par logement et les échantillons, conserver GV/Nblgt.
     */
    /** @return array{float, bool} [GV retenu (W/K), GV ramené à l'appartement moyen] */
    private function resolveGvBuilding(DOMElement $node, NodeAccessor $accessor, CalculationContext $context): array
    {
        $gv = (float)($context->get('chauffage.gv') ?? 0.0);
        if ($gv <= 0.0) {
            return [0.0, false];
        }

        $modeApp = $accessor->getIntOrNull('//caracteristique_generale/enum_methode_application_dpe_log_id');
        if ($modeApp !== null && in_array($modeApp, [10, 12], true)) {
            $nblgt = $accessor->getIntOrNull('//caracteristique_generale/nombre_appartement');
            if ($nblgt !== null && $nblgt > 1) {
                $share = \CalculDpePHP\Collectif\ChauffageInstallationMultiplicity::directShareOrNull($node, $accessor)
                    ?? (1.0 / max($nblgt, $this->nombreDeSystemesIndividuels($node, $accessor)));
                return [$gv * $share, true];
            }
        }

        return [$gv, false];
    }

    /**
     * Puissance nominale cumulée (W) des générateurs à combustion.
     *
     * Portée : l'installation passée en argument, ou le document entier à
     * défaut (installation introuvable).
     *
     * @spec-section 13.2.1.2
     */
    private function puissanceCombustionCumulee(NodeAccessor $accessor, CalculationContext $context, ?DOMElement $installation = null): float
    {
        $total = 0.0;
        $portee = $installation ?? $context->document;
        foreach ($portee->getElementsByTagName('generateur_chauffage') as $gen) {
            if (!$gen instanceof DOMElement) {
                continue;
            }
            $genId = \CalculDpePHP\Chauffage\GenerateurChAlias::normalizeNode(
                $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $gen),
                $gen,
            );
            if ($genId === null || $genId < self::COMBUSTION_MIN || $genId > self::COMBUSTION_MAX) {
                continue;
            }
            $total += $accessor->getFloatOrNull('./donnee_intermediaire/pn', $gen) ?? 0.0;
        }

        return $total;
    }

    /**
     * Nombre de systèmes de chauffage individuels décrits par le document.
     *
     * Cdimref compare la puissance installée d'**un** générateur au GV qu'il
     * dessert. Quand §17.1.4.2 ramène ce GV à l'appartement « moyen », le
     * diviseur ne peut pas être inférieur au nombre de systèmes décrits : un
     * immeuble peut en porter davantage que son `nombre_appartement` — sept
     * chaudières individuelles pour six appartements sur `2400E0575636Z` —, et
     * chacune dessert alors moins qu'un appartement moyen.
     *
     * Le maximum des deux, et non le seul nombre de systèmes : un GV par
     * système **supérieur** au GV par logement n'a pas de sens dans un
     * immeuble à chauffage individuel, et le mesurer le confirme — la règle
     * non bornée coûte 537 écarts sur 23 cas.
     */
    private function nombreDeSystemesIndividuels(DOMElement $node, NodeAccessor $accessor): float
    {
        $logement = $node;
        while ($logement instanceof DOMElement && $logement->nodeName !== 'logement') {
            $parent = $logement->parentNode;
            $logement = $parent instanceof DOMElement ? $parent : null;
        }
        if ($logement === null) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($logement->getElementsByTagName('installation_chauffage') as $install) {
            if (!$install instanceof DOMElement
                || $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $install) !== 1) {
                continue;
            }
            $total += $accessor->getFloatOrNull('./donnee_entree/rdim', $install) ?? 1.0;
        }

        return $total;
    }

    /**
     * Puissance du générateur « moyen » d'un immeuble à chauffage individuel.
     *
     * §17.1.4.2 raisonne sur un appartement moyen : son GV est celui de
     * l'immeuble divisé par le nombre de logements. Le Cdimref qui s'y compare
     * doit décrire le **même** logement moyen, donc une chaudière moyenne — et
     * non celle du générateur courant, sans quoi Cdimref varie d'un
     * générateur à l'autre alors que §13.2.1.2 le définit pour la zone.
     *
     * Les références le confirment sur `2513E2308028Z` : sept chaudières de
     * 24 et 10 kW y partagent un Cdimref implicite de 2,6834 et 2,6848, soit
     * un rapport de 0,9995. C'est pourquoi leur grosse chaudière sort avec un
     * **meilleur** rendement que les petites — son `Rpn` est meilleur — quand
     * un Cdimref proportionnel à sa puissance lui donnait le pire.
     *
     * §17.1.2 p.108-109 : à population cohérente avec le GV moyen,
     * Pn_comb_moy = Σ(Pn_comb_i × Sh_i) / ΣSh_i. Les logements sans
     * combustion ont Pn_comb_i = 0, sans disparaître de cette population.
     *
     * @spec-formula F-17.1.2-pn-comb-moy-zone
     */
    private function puissanceDuGenerateurMoyen(
        DOMElement $node,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        // §17.1.2 p.108-109 : les sous-ensembles représentent des nombres
        // différents de logements moyens. N_i/N = Sh_i/Sh, donc la puissance
        // de combustion ramenée à cette même population est Σ(Pn_i × Sh_i)/ΣSh_i.
        // Une installation sans combustion contribue 0 au numérateur, mais
        // ses logements restent dans le dénominateur du GV moyen. Compter
        // seulement les chaudières surévaluait la puissance installée moyenne.
        $puissancePonderee = $surfaceTotale = 0.0;
        $surfacesCompletes = true;
        foreach ($context->document->getElementsByTagName('installation_chauffage') as $installation) {
            $surface = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $installation);
            if ($surface === null || $surface <= 0.0) {
                $surfacesCompletes = false;
                break;
            }
            $puissancePonderee += $this->puissanceCombustionCumulee($accessor, $context, $installation) * $surface;
            $surfaceTotale += $surface;
        }
        if ($surfacesCompletes && $surfaceTotale > 0.0) {
            return $puissancePonderee / $surfaceTotale;
        }

        // Exports incomplets : ne pas inventer les poids manquants, garder
        // le repli arithmétique des générateurs connus.
        $total = $this->puissanceCombustionCumulee($accessor, $context, null);
        $nombre = 0;
        foreach ($context->document->getElementsByTagName('generateur_chauffage') as $gen) {
            if (!$gen instanceof DOMElement) {
                continue;
            }
            $genId = \CalculDpePHP\Chauffage\GenerateurChAlias::normalizeNode(
                $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $gen),
                $gen,
            );
            if ($genId !== null && $genId >= self::COMBUSTION_MIN && $genId <= self::COMBUSTION_MAX
                && ($accessor->getFloatOrNull('./donnee_intermediaire/pn', $gen) ?? 0.0) > 0.0) {
                $nombre++;
            }
        }

        return $nombre > 0 ? $total / $nombre : $total;
    }

    /**
     * Σ(Pn × rdim) des générateurs à combustion, sur le périmètre du GV.
     *
     * `rdim` ne joue que sur un DPE **immeuble** : la sortie y décrit tout le
     * bâtiment, et une installation individuelle représentant un logement type
     * compte autant de fois qu'elle est dimensionnée. Partout ailleurs la
     * sortie porte déjà sur un seul logement et `rdim` vaut 1.
     */
    private function puissanceCombustionPonderee(
        DOMElement $node,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): float {
        $modeApp = $accessor->getIntOrNull('//caracteristique_generale/enum_methode_application_dpe_log_id');
        $estImmeuble = $modeApp !== null && in_array($modeApp, [6, 7, 8, 9, 26, 27, 28, 29, 30], true);
        if (!$estImmeuble) {
            return $this->puissanceCombustionCumulee($accessor, $context, null);
        }

        $total = 0.0;
        foreach ($context->document->getElementsByTagName('generateur_chauffage') as $gen) {
            if (!$gen instanceof DOMElement) {
                continue;
            }
            $genId = \CalculDpePHP\Chauffage\GenerateurChAlias::normalizeNode(
                $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $gen),
                $gen,
            );
            if ($genId === null || $genId < self::COMBUSTION_MIN || $genId > self::COMBUSTION_MAX) {
                continue;
            }
            $install = $this->installationParente($gen);
            $rdim = $install === null
                ? 1.0
                : max(1.0, $accessor->getFloatOrNull('./donnee_entree/rdim', $install) ?? 1.0);
            $total += ($accessor->getFloatOrNull('./donnee_intermediaire/pn', $gen) ?? 0.0) * $rdim;
        }

        return $total;
    }

    /** Installation de chauffage à laquelle appartient le générateur. */
    private function installationParente(DOMElement $genNode): ?DOMElement
    {
        $courant = $genNode->parentNode;
        while ($courant instanceof DOMElement) {
            if ($courant->nodeName === 'installation_chauffage') {
                return $courant;
            }
            $courant = $courant->parentNode;
        }

        return null;
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
