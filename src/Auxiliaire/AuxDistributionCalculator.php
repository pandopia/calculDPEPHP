<?php

declare(strict_types=1);

namespace CalculDpePHP\Auxiliaire;

use CalculDpePHP\Common\ClimaticSolicitations;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Conso auxiliaires de distribution chauffage et ECS (§15.2 p.98-102).
 *
 * Chauffage — puissance du circulateur :
 *   Lem = 5 × Fcot × [Niv + sqrt(Sh/Niv)]
 *   ΔPemnom = 0.15 × Lem + ΔPem
 *   Pnc = 10^-3 × GV_total × (20 − Tbase)   (kW)
 *   qvemnom = Pnc × rat / (1.163 × δθdim)    (m³/h)
 *   Pcircem = max(30; 6.44 × (ΔPemnom × qvemnom / max(1;Sh/400))^0.676 × max(1;Sh/400))  (W)
 *   Caux_dist_ch = Pcircem × Nref / 1000  (kWh)
 *
 * GV_total = dp_parois + dp_pont_thermique + hvent + hperm  (W/K)
 * Nref = Σ Nref19 mensuel (h/an) from tv_sollicitations
 *
 * ECS collective avec bouclage — calcul mensuel :
 *   Lb = 4 × sqrt(Sh/Niv) + 6 × (Niv − 0.5)
 *   ΔPb = 0.2 × Lb + 10
 *   Qd,w,j = 0.112 × Becs_j [+ 0.028 × Becs_j if not isolated]  (Wh)
 *   qd,w,j = Qd,w,j / (5.815 × Nhpuisage_j)   (m³/h)
 *   Phyd,j = qd,w,j × ΔPb / 3.6   (W)
 *   Effcirb,j = (Phyd,j/15.3)^0.324
 *   Pcirb,j = max(20; Phyd,j / Effcirb,j)   (W)
 *   Qcirb,j = Nhpuisage_j × Pcirb,j + (Nhmois_j − Nhpuisage_j) × 20   (Wh)
 *   Caux_dist_ecs = Σ Qcirb,j / 1000   (kWh)
 *
 * Mode ZONE : résultat multiplié par cle_repartition_ch / cle_repartition_ecs.
 *
 * @spec-section 15.2
 * @spec-pages   98-102
 * @spec-source  resources/specsplitted/15-auxiliaires/02-aux-distribution.md
 * @xml-input    installation_chauffage.donnee_entree.{surface_chauffee, nombre_niveau_installation_ch,
 *               cle_repartition_ch}
 *               installation_chauffage.emetteur_chauffage.donnee_entree.{enum_type_emission_distribution_id,
 *               enum_temp_distribution_ch_id}
 *               installation_ecs.donnee_entree.{surface_habitable, nombre_niveau_installation_ecs,
 *               enum_type_installation_id, reseau_distribution_isole, cle_repartition_ecs}
 * @xml-output   sortie.ef_conso.{conso_auxiliaire_distribution_ch, conso_auxiliaire_distribution_ecs}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator,
 *               \CalculDpePHP\Ecs\BesoinEcsCalculator
 * @tables       reference/tv_sollicitations
 */
final class AuxDistributionCalculator implements CalculatorInterface
{
    /** Heures de puisage ECS/jour (7h-9h, 18h-19h, 20h-22h) — §15.2.3 */
    private const NHPUISAGE_PER_DAY = 5;

    /** Puissance minimale pompe chauffage (W) — §15.2.1 */
    private const PCIRCEM_MIN = 30.0;

    /** Puissance minimale circulateur bouclage ECS hors puisage (W) — §15.2.3 */
    private const PCIRB_MIN = 20.0;

    /** Tbase (°C) par zone_id (1-8) et altitude_id (1-3) — §18.1 p.121 */
    private const TBASE = [
        1 => [-9.5, -11.5, -13.5], // H1a, H1b, H1c
        2 => [-9.5, -11.5, -13.5],
        3 => [-9.5, -11.5, -13.5],
        4 => [-6.5, -8.5,  -10.5], // H2a, H2b, H2c, H2d
        5 => [-6.5, -8.5,  -10.5],
        6 => [-6.5, -8.5,  -10.5],
        7 => [-6.5, -8.5,  -10.5],
        8 => [-3.5, -5.5,  -7.5],  // H3
    ];

    /**
     * enum_type_emission_distribution_id → ligne du tableau ΔPem/Fcot §15.2.1.
     *
     * La spec n'y donne que trois lignes — « Radiateurs » (30 en monotube,
     * 10 sinon), « Plancher/plafond chauffant » (15) et « Autres cas » (35) —
     * et §15.2.2 ne prévoit aucune consommation nulle pour le chauffage,
     * contrairement à §15.2.3 qui l'énonce explicitement pour l'ECS
     * individuelle. `none` est donc réservé aux émetteurs sans réseau de
     * distribution du tout (effet joule direct, poêles, radiateurs à gaz) :
     * il n'y a alors pas de circuit à faire circuler.
     *
     * L'émetteur 5 « soufflage d'air chaud avec distribution par **réseau
     * aéraulique** » possède, lui, un réseau de distribution : il relève de
     * « Autres cas ».
     */
    private const EMETTEUR_TYPE = [
        // Sans réseau de distribution (émission individuelle) → pas de circulateur
        // IDs 1-4, 6-10 (direct, convecteur, panneau/plancher rayonnant électrique), 19-23, 40-41, 50
         1 => 'none',  2 => 'none',  3 => 'none',  4 => 'none',  5 => 'autre',
         6 => 'none',  7 => 'none',  8 => 'none',  9 => 'none', 10 => 'none',
        19 => 'none', 20 => 'none', 21 => 'none', 22 => 'none', 23 => 'none',
        40 => 'none', 41 => 'none', 50 => 'none',
        // Plancher/plafond chauffant eau (IDs 11-18) → ΔPem=15, Fcot=0.156
        11 => 'plancher', 12 => 'plancher', 13 => 'plancher', 14 => 'plancher',
        15 => 'plancher', 16 => 'plancher', 17 => 'plancher', 18 => 'plancher',
        // Radiateurs monotube (IDs 24-31) → ΔPem=30
        24 => 'monotube', 25 => 'monotube', 26 => 'monotube', 27 => 'monotube',
        28 => 'monotube', 29 => 'monotube', 30 => 'monotube', 31 => 'monotube',
        // Radiateurs bitube (IDs 32-39) → ΔPem=10
        32 => 'radiateur', 33 => 'radiateur', 34 => 'radiateur', 35 => 'radiateur',
        36 => 'radiateur', 37 => 'radiateur', 38 => 'radiateur', 39 => 'radiateur',
        // Fluide frigorigène (PAC à détente directe, IDs 42-45) → pas de circulateur eau
        42 => 'none', 43 => 'none', 44 => 'none', 45 => 'none',
        // Ventiloconvecteur sur eau chaude (IDs 46-49) → Autres cas → ΔPem=35
        46 => 'autre', 47 => 'autre', 48 => 'autre', 49 => 'autre',
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Chauffage\BesoinChauffageCalculator',
            '\CalculDpePHP\Ecs\BesoinEcsCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $isZone = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node) !== null;

        // ── Nref annuel ───────────────────────────────────────────────────────
        $nref = $this->computeNref($context);

        // ── GV total ──────────────────────────────────────────────────────────
        $gvTotal = (float)($context->get('enveloppe.dp_parois')         ?? 0.0)
                 + (float)($context->get('enveloppe.dp_pont_thermique')  ?? 0.0)
                 + (float)($context->get('ventilation.hvent')            ?? 0.0)
                 + (float)($context->get('ventilation.hperm')            ?? 0.0);

        // ── Tbase ──────────────────────────────────────────────────────────────
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : 1;
        $altId  = $context->classeAltitude  !== null ? (int)$context->classeAltitude  : 1;
        $tbase  = self::TBASE[$zoneId][($altId - 1)] ?? -9.5;

        // ── Pnc (kW) ──────────────────────────────────────────────────────────
        $pnc = 1e-3 * $gvTotal * (20.0 - $tbase);

        // ── CH distribution ───────────────────────────────────────────────────
        [$cauxDistCh, $cleRepCh] = $this->computeChDistribution($accessor, $node, $pnc, $nref);

        // ── ECS distribution ──────────────────────────────────────────────────
        [$cauxDistEcs, $cleRepEcs] = $this->computeEcsDistribution($accessor, $node, $context);

        // ── Zone scaling ──────────────────────────────────────────────────────
        if ($isZone) {
            $cauxDistCh  *= $cleRepCh;
            $cauxDistEcs *= $cleRepEcs;
        }

        // ── Write to sortie/ef_conso ──────────────────────────────────────────
        $sortie  = $accessor->ensureSortie($node);
        $efConso = $this->ensureChild($context->document, $sortie, 'ef_conso');

        $accessor->setChildValue($efConso, 'conso_auxiliaire_distribution_ch',  $cauxDistCh);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_distribution_ecs', $cauxDistEcs);
    }

    /**
     * @return array{float, float} [caux_dist_ch_kWh, cle_repartition_ch]
     */
    private function computeChDistribution(
        NodeAccessor $accessor,
        DOMElement   $logement,
        float        $pnc,
        float        $nref,
    ): array {
        $collection = $this->getChild($logement, 'installation_chauffage_collection');
        if ($collection === null) {
            return [0.0, 1.0];
        }

        $totalCaux = 0.0;
        $cle       = 1.0;

        // Surface habitable de référence pour Lem/shFactor (= bâtiment complet pour immeuble, sinon logement)
        $modeApp = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logement);
        $isAppartementMixte = $modeApp !== null && in_array($modeApp, [31, 32, 35], true);
        $shRef = $isAppartementMixte
            ? ($accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $logement) ?? 0.0)
            : ($accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logement)
                ?? $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $logement)
                ?? 0.0);

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_chauffage') {
                continue;
            }

            $surfChauffee = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $install) ?? 0.0;
            $niv          = $accessor->getFloatOrNull('./donnee_entree/nombre_niveau_installation_ch', $install) ?? 1.0;
            $shCalc       = $shRef > 0.0 ? $shRef : $surfChauffee;

            if ($shCalc <= 0.0 || $niv <= 0.0) {
                continue;
            }

            // Installation collective multi-bâtiment (enum_type_installation_id=3, ex.
            // réseau de chauffage urbain modélisé en multi-bâtiment §17.3) :
            // pas de circulateur local — distribution centralisée par le réseau.
            $typeInstall = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $install);
            if ($typeInstall === 3) {
                continue;
            }

            // Emitter characteristics (worst-case across all emitters)
            [$deltaP, $fcot, $dtDim] = $this->emetteurParams($accessor, $install);

            // No hydraulic emitter → no circulateur for this installation
            if ($fcot === 0.0 && $deltaP === 0.0) {
                continue;
            }

            // Échelle de calcul : Pcircem doit être dimensionné à la taille du circuit
            // réellement piloté par UN circulateur (§15.2.1).
            //   • Chauffage collectif (type=2) : un seul circulateur pour l'immeuble entier
            //     → on calcule à l'échelle bâtiment (rdim=1).
            //   • Chauffage individuel dans un DPE immeuble (type=1 avec rdim>1) :
            //     chaque appartement a son propre circulateur → on calcule à l'échelle
            //     d'un appartement « moyen » (Sh/rdim, GV/rdim) puis on multiplie par rdim
            //     pour obtenir le total bâtiment. Le plancher de 30 W joue par circulateur.
            $rdimInstall = $this->heatingInstallationMultiplicity($accessor, $logement, $install);
            $rdimInstall = $rdimInstall > 0.0 ? $rdimInstall : 1.0;
            $isIndividuelMultiplie = ($typeInstall === 1 && $rdimInstall > 1.0);

            $shCalcEff = $isIndividuelMultiplie ? ($shCalc / $rdimInstall) : $shCalc;
            $pncEff    = $isIndividuelMultiplie ? ($pnc / $rdimInstall)    : $pnc;

            // Spec §15.2.1 / open3cl 15_conso_aux.js : Lem et shFactor à l'échelle bâtiment
            $lem       = 5.0 * $fcot * ($niv + sqrt($shCalcEff / $niv));
            $deltaPnom = 0.15 * $lem + $deltaP;

            // Ratio de surface couverte par l'installation : surface_chauffee / Sh_bâtiment
            // (= 1.0 pour maison/appartement individuel ; < 1 pour immeuble multi-installations)
            $ratioSurf = ($surfChauffee > 0.0 && $shCalc > 0.0) ? min(1.0, $surfChauffee / $shCalc) : 1.0;

            $qvem = $dtDim > 0.0 ? ($pncEff * $ratioSurf / (1.163 * $dtDim)) : 0.0;

            $shFactor  = max(1.0, $shCalcEff / 400.0);
            $inner     = ($shFactor > 0.0) ? ($deltaPnom * $qvem / $shFactor) : 0.0;
            $pcircem   = max(self::PCIRCEM_MIN, 6.44 * (($inner > 0.0) ? ($inner ** 0.676) : 0.0) * $shFactor);

            $totalCaux += $pcircem * $nref * ($isIndividuelMultiplie ? $rdimInstall : 1.0) / 1000.0;

            $cleInst = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ch', $install);
            if ($cleInst !== null && $cleInst > 0.0) {
                $cle = $cleInst;
            }
        }

        return [$totalCaux, $cle];
    }

    /**
     * @return array{float, float} [caux_dist_ecs_kWh, cle_repartition_ecs]
     */
    private function computeEcsDistribution(
        NodeAccessor      $accessor,
        DOMElement        $logement,
        CalculationContext $context,
    ): array {
        $collection = $this->getChild($logement, 'installation_ecs_collection');
        if ($collection === null) {
            return [0.0, 1.0];
        }

        $totalCaux = 0.0;
        $cle       = 1.0;

        // DPE appartement / zone (modes 2-4, 10-13, 31-40) : la conso d'aux de
        // distribution ECS collective est portée par l'immeuble — LICIEL ne la
        // facture pas au niveau apt. Le mode 5 est une virtualisation directe de
        // l'installation collective : son réseau local reste donc à calculer.
        $modeAppId  = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $logement);
        $isZoneDpe  = $modeAppId !== null && in_array(
            $modeAppId,
            [2, 3, 4, 10, 11, 12, 13, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40],
            true,
        );
        if ($isZoneDpe) {
            return [0.0, 1.0];
        }

        // Monthly besoin ECS (kWh) — set by BesoinEcsCalculator
        $becsMonthly = $context->get('ecs.besoin_ecs_mensuel') ?? [];

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_ecs') {
                continue;
            }

            $typeInstall = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $install) ?? 1;

            // Individual installation: no distribution auxiliaries
            if ($typeInstall !== 2) {
                continue;
            }

            // §15.2.3 ne prévoit un circulateur que pour un réseau bouclé.
            // XSD : 1=non bouclé, 2=bouclé, 3=traceur chauffant.
            $bouclage = $accessor->getIntOrNull('./donnee_entree/enum_bouclage_reseau_ecs_id', $install);
            if ($bouclage !== 2) {
                continue;
            }

            $sh       = $accessor->getFloatOrNull('./donnee_entree/surface_habitable', $install) ?? 0.0;
            $niv      = $accessor->getFloatOrNull('./donnee_entree/nombre_niveau_installation_ecs', $install) ?? 1.0;
            $isolated = (int)($accessor->getFloatOrNull('./donnee_entree/reseau_distribution_isole', $install) ?? 0);

            if ($sh <= 0.0) {
                continue;
            }

            // §17.2 : en mode zone collectif, le circulateur est dimensionné à
            // l'échelle de l'immeuble, puis sa consommation est ramenée au
            // logement par ratio_virtualisation. Dimensionner directement sur
            // 44 m² ferait jouer à tort le plancher de 20 W au logement entier.
            $ratioVirt = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation', $install) ?? 1.0;
            $isVirtualized = $ratioVirt > 0.0 && $ratioVirt < 1.0;
            $shCalc = $isVirtualized ? $sh / $ratioVirt : $sh;
            $becsCalc = $becsMonthly;
            if ($isVirtualized) {
                $becsCalc = array_map(
                    static fn(mixed $value): float => (float)$value / $ratioVirt,
                    $becsMonthly,
                );
            }

            $lb    = 4.0 * sqrt($shCalc / $niv) + 6.0 * ($niv - 0.5);
            $deltaPb = 0.2 * $lb + 10.0;

            $qcirb = $this->computeBouclageAnnuel($becsCalc, $shCalc, $deltaPb, $isolated);
            $totalCaux += $qcirb * ($isVirtualized ? $ratioVirt : 1.0) / 1000.0;

            $cleInst = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ecs', $install);
            if ($cleInst !== null && $cleInst > 0.0) {
                $cle = $cleInst;
            }
        }

        return [$totalCaux, $cle];
    }

    /**
     * §17.2 — multiplicateur effectif d'une installation CH individuelle échantillonnée.
     *
     * @spec-formula F-17.2-rdim-effective
     */
    private function heatingInstallationMultiplicity(
        NodeAccessor $accessor,
        DOMElement $logement,
        DOMElement $install,
    ): float {
        $rdim = $accessor->getFloatOrNull('./donnee_entree/rdim', $install) ?? 1.0;
        $methode = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $install) ?? 1;
        $type = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id', $install) ?? 1;
        if ($methode === 1 || $type !== 1) {
            return max(1e-9, $rdim);
        }

        $nbApt = $accessor->getFloatOrNull('./caracteristique_generale/nombre_appartement', $logement) ?? 1.0;
        $ratioVirt = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation', $install) ?? 1.0;
        $sumSample = 0.0;
        foreach ($logement->getElementsByTagName('installation_chauffage') as $candidate) {
            $sumSample += $accessor->getFloatOrNull('./donnee_entree/nombre_logement_echantillon', $candidate) ?? 0.0;
        }

        return max(1e-9, $nbApt * $ratioVirt / max(1.0, $sumSample));
    }

    /**
     * Compute annual bouclage consumption (Wh) for one ECS collective installation.
     *
     * §15.2.3 p.100-102 :
     *   Qd,w,j [kWh] = 0.112 × Becs_j + 0.028 × Becs_j (hors vol. chauffé si non isolé)
     *   qd,w,j [m³/h] = Qd,w,j / (5.815 [kWh/m³] × Nhpuisage_per_day)
     *   Nhpuisage_per_day = 5 (7h-9h, 18h-19h, 20h-22h)
     *   Phyd,j = qd,w,j × ΔPb / 3.6
     *   Effcirb,j = (Phyd,j / 15.3)^0.324
     *   Pcirb,j = max(20 ; Phyd,j / Effcirb,j)
     *   Qcirb,j = Nhpuisage_monthly,j × Pcirb,j + (Nhmois,j − Nhpuisage_monthly,j) × 20
     *
     * Note: qd,w uses the daily 5h as denominator (representative flow rate during
     * a puisage period); total monthly consumption uses monthly Nhpuisage = njj × 5.
     *
     * @spec-formula §15.2.3 p.100-102
     */
    private function computeBouclageAnnuel(
        array $becsMonthly,
        float $sh,
        float $deltaPb,
        int   $isolated,
    ): float {
        // njj (occupied days) and month lengths — same as BesoinEcsCalculator
        static $njj = [
            1  => 31, 2  => 28, 3  => 31, 4  => 30, 5  => 31, 6  => 30,
            7  => 31, 8  => 31, 9  => 30, 10 => 31, 11 => 30, 12 => 24,
        ];

        $qcirb = 0.0;

        for ($j = 1; $j <= 12; $j++) {
            $becsJ_kWh = (float)($becsMonthly[$j] ?? 0.0);

            if ($becsJ_kWh <= 0.0) {
                $njjVal = $njj[$j];
                $qcirb += $njjVal * 24 * self::PCIRB_MIN;
                continue;
            }

            $njjVal    = $njj[$j];
            $nhpuisage = $njjVal * self::NHPUISAGE_PER_DAY; // total monthly hours
            $nhmois    = $njjVal * 24;

            // Distribution losses (kWh) — collective
            $qdWcol = 0.112 * $becsJ_kWh;
            $qdWhvc = $isolated === 1 ? 0.0 : 0.028 * $becsJ_kWh;
            $qdW    = $qdWcol + $qdWhvc; // kWh

            // Flow rate: divide by daily puisage hours (5h) to get representative hourly flow
            // 5.815 kWh/m³ = ρ × Cp × ΔT (ΔT=5°C) — §15.2.3 p.100
            $qdwJ    = $qdW / (5.815 * self::NHPUISAGE_PER_DAY); // m³/h
            $phyd    = $qdwJ * $deltaPb / 3.6;                   // W
            $effcirb = $phyd > 0.0 ? (($phyd / 15.3) ** 0.324) : 1.0;
            $pcirb   = max(self::PCIRB_MIN, $phyd > 0.0 ? ($phyd / $effcirb) : self::PCIRB_MIN);

            $qcirb += $nhpuisage * $pcirb + ($nhmois - $nhpuisage) * self::PCIRB_MIN;
        }

        return $qcirb;
    }

    /**
     * Determine [ΔPem_kPa, Fcot, δθdim_°C] from the worst-case emitter in the installation.
     * Returns [0, 0, 0] if no hydraulic emitter found (no circulateur needed).
     *
     * @spec-formula §15.2.1 p.98-99
     * @return array{float, float, float}
     */
    /**
     * Détecte si l'installation est connectée à un réseau de chaleur urbain
     * (enum_type_energie_id=8 sur au moins un générateur). Dans ce cas la
     * distribution se fait par le réseau et il n'y a pas de circulateur côté
     * utilisateur — pas de conso d'aux distribution chauffage.
     */
    private function isReseauChaleurUrbain(NodeAccessor $accessor, DOMElement $install): bool
    {
        foreach ($install->getElementsByTagName('generateur_chauffage') as $gen) {
            if (!$gen instanceof DOMElement) {
                continue;
            }
            $energieId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen);
            if ($energieId === 8) {
                return true;
            }
        }
        return false;
    }

    private function emetteurParams(NodeAccessor $accessor, DOMElement $install): array
    {
        $emCollection = $this->getChild($install, 'emetteur_chauffage_collection');

        $worstDeltaP    = 10.0; // default radiateur bitube
        $worstFcot      = 0.802;
        $dtDim          = 7.5;
        $hasHydraulic   = false;

        if ($emCollection === null) {
            return [0.0, 0.0, 0.0]; // pas d'émetteur → pas de circulateur
        }

        foreach ($emCollection->childNodes as $em) {
            if (!$em instanceof DOMElement || $em->nodeName !== 'emetteur_chauffage') {
                continue;
            }

            $typeId = $accessor->getIntOrNull('./donnee_entree/enum_type_emission_distribution_id', $em);
            $tempId = $accessor->getIntOrNull('./donnee_entree/enum_temp_distribution_ch_id', $em);

            $emType = self::EMETTEUR_TYPE[$typeId] ?? 'autre';

            // Systems without hydraulic circuit → no circulateur
            if ($emType === 'none') {
                continue;
            }

            $hasHydraulic = true;

            // δθdim from temperature distribution
            $emDt = match ($tempId) {
                4 => 15.0,  // haute
                default => 7.5,
            };

            // ΔPem and Fcot from emitter type
            [$emDeltaP, $emFcot] = match ($emType) {
                'plancher'  => [15.0, 0.156],
                'monotube'  => [30.0, 0.802],
                'radiateur' => [10.0, 0.802],
                default     => [35.0, 0.802], // Ventiloconvecteurs
            };

            // Take worst-case Fcot (Autre = 0.802 always wins over plancher 0.156)
            if ($emFcot > $worstFcot) {
                $worstFcot = $emFcot;
            }
            // Take worst-case ΔPem (highest)
            if ($emDeltaP > $worstDeltaP) {
                $worstDeltaP = $emDeltaP;
            }

            $dtDim = $emDt;
        }

        if (!$hasHydraulic) {
            return [0.0, 0.0, 0.0]; // pas de réseau hydraulique → Pcircem=0
        }

        return [$worstDeltaP, $worstFcot, $dtDim];
    }

    /**
     * Compute annual Nref (heating hours) from tv_sollicitations table.
     *
     * @spec-formula §18.2 p.121-136
     */
    private function computeNref(CalculationContext $context): float
    {
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : null;
        $altId  = $context->classeAltitude  !== null ? (int)$context->classeAltitude  : null;

        if ($zoneId === null || $altId === null) {
            return 0.0;
        }

        $zoneData = ClimaticSolicitations::heating($context, $zoneId, $altId);
        if ($zoneData === []) {
            return 0.0;
        }

        $nref = 0.0;
        for ($j = 1; $j <= 12; $j++) {
            $nref += (float)($zoneData[$j]['Nref19'] ?? 0.0);
        }

        return $nref;
    }

    private function ensureChild(\DOMDocument $doc, DOMElement $parent, string $tag): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $tag) {
                return $c;
            }
        }
        $el = $doc->createElement($tag);
        $parent->appendChild($el);
        return $el;
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
