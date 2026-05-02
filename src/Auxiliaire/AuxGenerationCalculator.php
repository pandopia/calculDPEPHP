<?php

declare(strict_types=1);

namespace CalculDpe\Auxiliaire;

use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Engine\CalculationContext;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Conso auxiliaires de génération chauffage et ECS (§15.1 p.97-98).
 *
 * Paux_g = G + H × Pn  (W)
 * Q_aux_g_ch  = Paux_g × Bch_g  / Pn_ch        (kWh)
 * Q_aux_g_ecs = Paux_g × Becs_g / Pn_ecs        (kWh)
 *
 * G/H par type de générateur (spec §15.1 p.97) :
 *   Chaudière gaz/fioul : G=20, H=1.6 (Pn cap 400 kW)
 *   Chaudière bois atmosphérique : G=0, H=0
 *   Chaudière bois à ventilateur : G=73.3, H=10.5 (Pn cap 70 kW)
 *   Générateur d'air chaud gaz : G=0, H=4 (Pn cap 300 kW)
 *   Radiateur gaz : G=40, H=0
 *   Chauffe-eau gaz / Accumulateur gaz : G=0, H=0
 *   PAC, réseau chaleur, électrique : Q=0
 *
 * Scénario dépensier :
 *   CH  : Q_dep = Paux × (besoin_ch + besoin_ch_dep) / Pn
 *   ECS : Q_dep = Paux × besoin_ecs_dep / Pn
 *
 * Mode ZONE : le résultat est multiplié par cle_repartition_ch/ecs.
 *
 * @spec-section 15.1
 * @spec-pages   97-98
 * @spec-source  resources/specsplitted/15-auxiliaires/01-aux-generation.md
 * @xml-input    installation_chauffage.generateur_chauffage.donnee_entree.{enum_type_energie_id,
 *               tv_generateur_combustion_id}
 *               installation_chauffage.generateur_chauffage.donnee_intermediaire.pn
 *               installation_chauffage.donnee_intermediaire.{besoin_ch, besoin_ch_depensier}
 *               installation_ecs.generateur_ecs.donnee_entree.{enum_type_energie_id,
 *               tv_generateur_combustion_id}
 *               installation_ecs.generateur_ecs.donnee_intermediaire.pn
 *               installation_ecs.donnee_intermediaire.{besoin_ecs, besoin_ecs_depensier}
 * @xml-output   sortie.ef_conso.{conso_auxiliaire_generation_ch, conso_auxiliaire_generation_ch_depensier,
 *               conso_auxiliaire_generation_ecs, conso_auxiliaire_generation_ecs_depensier}
 * @depends-on   \CalculDpe\Chauffage\BesoinChauffageCalculator,
 *               \CalculDpe\Ecs\BesoinEcsCalculator,
 *               \CalculDpe\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator,
 *               all heating strategy classes (write besoin_ch to installation.donnee_intermediaire)
 * @tables       (aucune)
 */
final class AuxGenerationCalculator implements CalculatorInterface
{
    /** G/H table: [G_W, H_W_per_kW, Pn_cap_kW] — open3cl §15.1 */
    private const GH_CHAUDIERE        = [20.0,   1.6,  400.0];
    private const GH_RADIATEUR_GAZ    = [40.0,   0.0,  PHP_FLOAT_MAX];
    private const GH_CHAUDIERE_BOIS   = [73.3,  10.5,   70.0];
    private const GH_AIR_CHAUD        = [0.0,    4.0,  300.0];
    private const GH_DEFAULT          = [0.0,    0.0,  PHP_FLOAT_MAX];

    /** Chaudières gaz CH (85-97, 127-139, 148-149, 160-161) */
    private const CH_CHAUDIERE_GAZ_RANGES    = [[85,97],[127,139],[148,149],[160,161]];
    /** Chaudières fioul CH (75-84, 150-151) */
    private const CH_CHAUDIERE_FIOUL_RANGES  = [[75,84],[150,151]];
    /** Radiateurs gaz CH (53-54) */
    private const CH_RADIATEUR_GAZ_RANGES    = [[53,54]];
    /** Chaudières bois ventilateur CH (55-74, 152-156) */
    private const CH_BOIS_VENT_RANGES        = [[55,74],[152,156]];
    /** Générateurs air chaud CH (50-52) */
    private const CH_AIR_CHAUD_RANGES        = [[50,52]];

    /** Chaudières gaz ECS (45-57, 92-104, 120-121, 132-133) */
    private const ECS_CHAUDIERE_GAZ_RANGES   = [[45,57],[92,104],[120,121],[132,133]];
    /** Chaudières fioul ECS (35-44, 122-123) */
    private const ECS_CHAUDIERE_FIOUL_RANGES = [[35,44],[122,123]];
    /** Chaudières bois ventilateur ECS (13-34) */
    private const ECS_BOIS_VENT_RANGES       = [[13,34]];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpe\Chauffage\BesoinChauffageCalculator',
            '\CalculDpe\Ecs\BesoinEcsCalculator',
            '\CalculDpe\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator',
            // Strategies write besoin_ch to installation.donnee_intermediaire — must run first
            '\CalculDpe\Chauffage\Strategy\InstallationClassique',
            '\CalculDpe\Chauffage\Strategy\AppointInsertElecSdb',
            '\CalculDpe\Chauffage\Strategy\ChaudiereReleve',
            '\CalculDpe\Chauffage\Strategy\ChauffageSolaire',
            '\CalculDpe\Chauffage\Strategy\InsertPoeleAppoint',
            '\CalculDpe\Chauffage\Strategy\ConvecteurBijonction',
            '\CalculDpe\Chauffage\Strategy\MultiGenerateurs',
            '\CalculDpe\Chauffage\Strategy\InsertElecSdb',
            '\CalculDpe\Chauffage\Strategy\SolaireInsertPoele',
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

        // ── CH auxiliaires ────────────────────────────────────────────────────
        [$qauxCh, $qauxChDep, $cleRepartCh] = $this->computeChAux($accessor, $node, $isZone);

        // ── ECS auxiliaires ───────────────────────────────────────────────────
        [$qauxEcs, $qauxEcsDep, $cleRepartEcs] = $this->computeEcsAux($accessor, $node, $isZone);

        // ── Zone scaling ──────────────────────────────────────────────────────
        if ($isZone) {
            $qauxCh    *= $cleRepartCh;
            $qauxChDep *= $cleRepartCh;
            $qauxEcs    *= $cleRepartEcs;
            $qauxEcsDep *= $cleRepartEcs;
        }

        // ── Write to sortie/ef_conso ──────────────────────────────────────────
        $sortie  = $accessor->ensureSortie($node);
        $efConso = $this->ensureChild($context->document, $sortie, 'ef_conso');

        $accessor->setChildValue($efConso, 'conso_auxiliaire_generation_ch',             $qauxCh);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_generation_ch_depensier',   $qauxChDep);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_generation_ecs',            $qauxEcs);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_generation_ecs_depensier',  $qauxEcsDep);
    }

    /**
     * @return array{float, float, float} [Q_aux_ch, Q_aux_ch_dep, cle_repartition_ch]
     */
    private function computeChAux(NodeAccessor $accessor, DOMElement $logement, bool $isZone): array
    {
        $collection = $this->getChild($logement, 'installation_chauffage_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        $totalQ    = 0.0;
        $totalQDep = 0.0;
        $cle       = 1.0;

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_chauffage') {
                continue;
            }

            $besoin    = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ch',           $install) ?? 0.0;
            $besoinDep = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ch_depensier', $install) ?? 0.0;
            $ratioVirt = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',       $install) ?? 1.0;

            // surface_chauffee / surface_habitable ratio for CH — open3cl §15.1
            $surfCh = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $install);
            $sh     = $accessor->getFloatOrNull('../caracteristique_generale/surface_habitable_logement', $logement)
                   ?? $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement',   $logement);
            $ratioSurface = ($surfCh !== null && $sh !== null && $sh > 0.0) ? ($surfCh / $sh) : 1.0;

            $genCollection = $this->getChild($install, 'generateur_chauffage_collection');
            if ($genCollection === null) {
                continue;
            }

            foreach ($genCollection->childNodes as $gen) {
                if (!$gen instanceof DOMElement || $gen->nodeName !== 'generateur_chauffage') {
                    continue;
                }
                $pn = $accessor->getFloatOrNull('./donnee_intermediaire/pn', $gen);
                if ($pn === null || $pn <= 0.0) {
                    continue;
                }

                [$g, $h, $pnCapKw] = $this->getGHch($accessor, $gen);

                if ($ratioVirt > 0.0 && $ratioVirt < 1.0) {
                    // Collective: pe = Pn_building (apartment pn / ratio_virt), capped
                    $pe     = min($pn / $ratioVirt, $pnCapKw * 1000.0);
                    $peKw   = $pe / 1000.0;
                    $paux   = $g + ($h * $peKw) / $ratioVirt;
                    if ($paux <= 0.0) {
                        continue;
                    }
                    $totalQ    += $ratioVirt * $paux * $besoin              * $ratioSurface / $pe;
                    $totalQDep += $ratioVirt * $paux * ($besoin + $besoinDep) * $ratioSurface / $pe;
                } else {
                    $pnKw  = min($pn / 1000.0, $pnCapKw);
                    $paux  = $g + $h * $pnKw;
                    if ($paux <= 0.0) {
                        continue;
                    }
                    $totalQ    += $paux * $besoin               * $ratioSurface / $pn;
                    $totalQDep += $paux * ($besoin + $besoinDep) * $ratioSurface / $pn;
                }
            }

            $cleInst = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ch', $install);
            if ($cleInst !== null && $cleInst > 0.0) {
                $cle = $cleInst;
            }
        }

        return [$totalQ, $totalQDep, $cle];
    }

    /**
     * @return array{float, float, float} [Q_aux_ecs, Q_aux_ecs_dep, cle_repartition_ecs]
     */
    private function computeEcsAux(NodeAccessor $accessor, DOMElement $logement, bool $isZone): array
    {
        $collection = $this->getChild($logement, 'installation_ecs_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        $totalQ    = 0.0;
        $totalQDep = 0.0;
        $cle       = 1.0;

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_ecs') {
                continue;
            }

            $besoin    = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ecs',           $install) ?? 0.0;
            $besoinDep = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ecs_depensier', $install) ?? 0.0;
            $ratioVirt = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',        $install) ?? 1.0;

            foreach ($install->getElementsByTagName('generateur_ecs') as $gen) {
                if (!$gen instanceof DOMElement) {
                    continue;
                }
                $pn = $accessor->getFloatOrNull('./donnee_intermediaire/pn', $gen);
                if ($pn === null || $pn <= 0.0) {
                    continue;
                }

                [$g, $h, $pnCapKw] = $this->getGHecs($accessor, $gen);

                if ($ratioVirt > 0.0 && $ratioVirt < 1.0) {
                    $pe     = min($pn / $ratioVirt, $pnCapKw * 1000.0);
                    $peKw   = $pe / 1000.0;
                    $paux   = $g + ($h * $peKw) / $ratioVirt;
                    if ($paux <= 0.0) {
                        continue;
                    }
                    $totalQ    += $ratioVirt * $paux * $besoin    / $pe;
                    $totalQDep += $ratioVirt * $paux * $besoinDep / $pe;
                } else {
                    $pnKw  = min($pn / 1000.0, $pnCapKw);
                    $paux  = $g + $h * $pnKw;
                    if ($paux <= 0.0) {
                        continue;
                    }
                    $totalQ    += $paux * $besoin    / $pn;
                    $totalQDep += $paux * $besoinDep / $pn;
                }
            }

            $cleInst = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ecs', $install);
            if ($cleInst !== null && $cleInst > 0.0) {
                $cle = $cleInst;
            }
        }

        return [$totalQ, $totalQDep, $cle];
    }

    /**
     * Determine [G, H, Pn_cap_kW] for a CH generator from enum_type_generateur_ch_id ranges.
     * Returns [0, 0, INF] for PAC, electric, réseau (Q=0 by spec).
     *
     * @spec-formula §15.1 p.97 table G/H
     * @return array{float, float, float}
     */
    private function getGHch(NodeAccessor $accessor, DOMElement $genNode): array
    {
        $genId = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $genNode);
        if ($genId === null) {
            return self::GH_DEFAULT;
        }
        return $this->resolveGHfromRanges($genId, 'ch');
    }

    /**
     * Determine [G, H, Pn_cap_kW] for an ECS generator from enum_type_generateur_ecs_id ranges.
     *
     * @spec-formula §15.1 p.97 table G/H
     * @return array{float, float, float}
     */
    private function getGHecs(NodeAccessor $accessor, DOMElement $genNode): array
    {
        $genId = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ecs_id', $genNode);
        if ($genId === null) {
            return self::GH_DEFAULT;
        }
        return $this->resolveGHfromRanges($genId, 'ecs');
    }

    /** @return array{float, float, float} */
    private function resolveGHfromRanges(int $genId, string $type): array
    {
        $chaudGazRanges  = ($type === 'ch') ? self::CH_CHAUDIERE_GAZ_RANGES   : self::ECS_CHAUDIERE_GAZ_RANGES;
        $chaudFioulRanges = ($type === 'ch') ? self::CH_CHAUDIERE_FIOUL_RANGES : self::ECS_CHAUDIERE_FIOUL_RANGES;
        $boisVentRanges  = ($type === 'ch') ? self::CH_BOIS_VENT_RANGES        : self::ECS_BOIS_VENT_RANGES;

        if ($this->inRanges($genId, $chaudGazRanges) || $this->inRanges($genId, $chaudFioulRanges)) {
            return self::GH_CHAUDIERE;
        }
        if ($type === 'ch' && $this->inRanges($genId, self::CH_RADIATEUR_GAZ_RANGES)) {
            return self::GH_RADIATEUR_GAZ;
        }
        if ($this->inRanges($genId, $boisVentRanges)) {
            return self::GH_CHAUDIERE_BOIS;
        }
        if ($type === 'ch' && $this->inRanges($genId, self::CH_AIR_CHAUD_RANGES)) {
            return self::GH_AIR_CHAUD;
        }
        return self::GH_DEFAULT;
    }

    /** @param list<array{int,int}> $ranges */
    private function inRanges(int $id, array $ranges): bool
    {
        foreach ($ranges as [$lo, $hi]) {
            if ($id >= $lo && $id <= $hi) {
                return true;
            }
        }
        return false;
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
