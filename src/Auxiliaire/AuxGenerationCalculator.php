<?php

declare(strict_types=1);

namespace CalculDpePHP\Auxiliaire;

use CalculDpePHP\Common\IntermediateEnergyUnit;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Collectif\EcsInstallationMultiplicity;
use CalculDpePHP\Collectif\GeneratedApartment;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Conso auxiliaires de génération chauffage et ECS (§15.1 p.97-98).
 *
 * Paux_g = G + H × Pn  (W)
 * Q_aux_g_ch  = Paux_g × Bch_g  / Pn_ch        (kWh)
 * Q_aux_g_ecs = Paux_g × Becs_g / Pn_ecs        (kWh)
 *
 * G/H par type de générateur (spec §15.1 p.97) :
 *   Chaudière gaz/fioul : G=20, H=1.6 (Pn cap 400 kW) — et chaudière charbon
 *   Chaudière bois atmosphérique : G=0, H=0        (presence_ventouse = 0)
 *   Chaudière bois à ventilateur : G=73.3, H=10.5  (presence_ventouse = 1, cap 70 kW)
 *   Générateur d'air chaud gaz : G=0, H=4 (Pn cap 300 kW)
 *   Radiateur gaz : G=40, H=0
 *   Chauffe-eau gaz / Accumulateur gaz : G=0, H=0
 *   PAC, réseau chaleur, électrique : Q=0
 *
 * Scénario dépensier :
 *   CH  : Q_dep = Paux × (besoin_ch + besoin_ch_dep) / Pn
 *   ECS : Q_dep = Paux × besoin_ecs_dep / Pn
 *
 * Mode ZONE : chaque installation porte sa propre clé ; celle du chauffage
 * est réservée aux appartements générés depuis le DPE immeuble (§17.2).
 *
 * @spec-section 15.1, 17.2.2.2
 * @spec-pages   97-98, 115-117
 * @spec-source  resources/specsplitted/15-auxiliaires/01-aux-generation.md
 * @spec-source  resources/specsplitted/17-collectif/02-appartement.md
 * @xml-input    installation_chauffage.generateur_chauffage.donnee_entree.{enum_type_energie_id,
 *               enum_type_generateur_ch_id, presence_ventouse, tv_generateur_combustion_id}
 *               installation_chauffage.generateur_chauffage.donnee_intermediaire.pn
 *               installation_chauffage.donnee_intermediaire.{besoin_ch, besoin_ch_depensier}
 *               installation_ecs.generateur_ecs.donnee_entree.{enum_type_energie_id,
 *               tv_generateur_combustion_id}
 *               installation_ecs.generateur_ecs.donnee_intermediaire.pn
 *               installation_ecs.donnee_intermediaire.{besoin_ecs, besoin_ecs_depensier}
 * @xml-output   sortie.ef_conso.{conso_auxiliaire_generation_ch, conso_auxiliaire_generation_ch_depensier,
 *               conso_auxiliaire_generation_ecs, conso_auxiliaire_generation_ecs_depensier}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator,
 *               \CalculDpePHP\Ecs\BesoinEcsCalculator,
 *               \CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator,
 *               all heating strategy classes (write besoin_ch to installation.donnee_intermediaire)
 * @tables       (aucune)
 */
final class AuxGenerationCalculator implements CalculatorInterface
{
    /** G/H table: [G_W, H_W_per_kW, Pn_cap_kW] — open3cl §15.1 */
    private const GH_CHAUDIERE        = [20.0,   1.6,  400.0];
    private const GH_RADIATEUR_GAZ    = [40.0,   0.0,  PHP_FLOAT_MAX];
    private const GH_BOIS_ATMO        = [0.0,    0.0,   70.0];
    private const GH_BOIS_VENTILATEUR = [73.3,  10.5,   70.0];
    private const GH_AIR_CHAUD        = [0.0,    4.0,  300.0];
    private const GH_DEFAULT          = [0.0,    0.0,  PHP_FLOAT_MAX];

    /** Chaudières gaz CH (85-97, 127-139, 148-149, 160-161) */
    private const CH_CHAUDIERE_GAZ_RANGES    = [[85,97],[127,139],[148,149],[160,161]];
    /** Chaudières fioul CH (75-84, 150-151) */
    private const CH_CHAUDIERE_FIOUL_RANGES  = [[75,84],[150,151]];
    /** Radiateurs gaz CH (53-54) */
    private const CH_RADIATEUR_GAZ_RANGES    = [[53,54]];
    /** Chaudières bois CH (55-74, 152-156) — atmosphérique ou ventilateur selon la ventouse */
    private const CH_BOIS_RANGES             = [[55,74],[152,156]];
    /**
     * Chaudières charbon CH (120-126). §15.1 p.97 ne tabule pas le charbon et
     * n'énonce que deux cas nuls (PAC, réseau de chaleur) : la ligne retenue est
     * « chaudière au gaz ou au fioul ». §13.2.2.3 renvoie bien le charbon vers le
     * bois bûche, mais pour le rendement de combustion, pas pour les auxiliaires.
     */
    private const CH_CHARBON_RANGES          = [[120,126]];
    /** Générateurs air chaud CH (50-52) */
    private const CH_AIR_CHAUD_RANGES        = [[50,52]];

    /** Chaudières gaz ECS (45-57, 92-104, 120-121, 132-133) */
    private const ECS_CHAUDIERE_GAZ_RANGES   = [[45,57],[92,104],[120,121],[132,133]];
    /** Chaudières fioul ECS (35-44, 122-123) */
    private const ECS_CHAUDIERE_FIOUL_RANGES = [[35,44],[122,123]];
    /** Chaudières bois ECS et parties hybrides (XSD 124-131), même ligne §15.1 p.97. */
    private const ECS_BOIS_RANGES            = [[13,34],[124,131]];

    /**
     * `enum_methode_application_dpe_log_id` des DPE immeuble collectif : seuls
     * ceux-là portent un besoin de chauffage à l'échelle de l'immeuble.
     */
    private const METHODES_DPE_IMMEUBLE = [6, 7, 8, 9, 26, 27, 28, 29, 30];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Chauffage\BesoinChauffageCalculator',
            '\CalculDpePHP\Ecs\BesoinEcsCalculator',
            '\CalculDpePHP\Chauffage\Rendement\Combustion\ChaudiereDefautCalculator',
            // Strategies write besoin_ch to installation.donnee_intermediaire — must run first
            '\CalculDpePHP\Chauffage\Strategy\InstallationClassique',
            '\CalculDpePHP\Chauffage\Strategy\AppointInsertElecSdb',
            '\CalculDpePHP\Chauffage\Strategy\ChaudiereReleve',
            '\CalculDpePHP\Chauffage\Strategy\ChauffageSolaire',
            '\CalculDpePHP\Chauffage\Strategy\InsertPoeleAppoint',
            '\CalculDpePHP\Chauffage\Strategy\ConvecteurBijonction',
            '\CalculDpePHP\Chauffage\Strategy\MultiGenerateurs',
            '\CalculDpePHP\Chauffage\Strategy\InsertElecSdb',
            '\CalculDpePHP\Chauffage\Strategy\SolaireInsertPoele',
            '\CalculDpePHP\Chauffage\Strategy\CollectifBaseAppoint',
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
        [$qauxCh, $qauxChDep, $cleRepartCh] = $this->computeChAux($accessor, $node, $isZone, $context);

        // ── ECS auxiliaires ───────────────────────────────────────────────────
        [$qauxEcs, $qauxEcsDep, $cleRepartEcs] = $this->computeEcsAux($accessor, $node, $isZone, $context);

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
    private function computeChAux(NodeAccessor $accessor, DOMElement $logement, bool $isZone, CalculationContext $context): array
    {
        $collection = $this->getChild($logement, 'installation_chauffage_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        $totalQ    = 0.0;
        $totalQDep = 0.0;
        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_chauffage') {
                continue;
            }

            $besoin    = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ch',           $install) ?? 0.0;
            $besoinDep = $accessor->getFloatOrNull('./donnee_intermediaire/besoin_ch_depensier', $install) ?? 0.0;
            $ratioVirt = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',       $install) ?? 1.0;
            // §17.2 et XSD cle_repartition_ch : répartir chaque installation
            // avant de sommer, uniquement si le besoin vient de l'immeuble.
            // Une dernière clé ne peut pas répartir les autres installations.
            $cleDeclaree = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ch', $install);
            // La clé unitaire conserve ici le périmètre historique du besoin
            // publié ; la reconstruction des clés manquantes en chauffage
            // mixte est une question distincte (TASK-K86).
            $partLogement = $isZone && $cleDeclaree !== null && $cleDeclaree > 0.0 && $cleDeclaree < 1.0
                ? GeneratedApartment::heatingShare($logement, $accessor, $cleDeclaree)
                : 1.0;

            // §15.1.1 : Bch_g est « le besoin annuel d'énergie assuré par le
            // générateur », réduit à « la part du besoin qu'il couvre ». Le
            // besoin publié sur l'installation est déjà cette part dès lors
            // que la stratégie l'a proratisée : le reproratiser par
            // surface_chauffee / surface desservie compterait deux fois.
            // La part surfacique ne s'applique donc qu'à une installation qui
            // porte encore le besoin entier du DPE tout en ne chauffant
            // qu'une fraction de sa surface.
            $surfCh = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $install);
            $sh     = $this->surfaceDesservie($accessor, $logement);
            $besoinDpe = (float)$context->get('chauffage.besoin_ch', 0.0);
            $portePlusQueSaPart = $besoinDpe > 0.0 && abs($besoin - $besoinDpe) <= 1e-6 * $besoinDpe;
            $ratioSurface = ($portePlusQueSaPart && $surfCh !== null && $sh !== null && $sh > 0.0)
                ? min(1.0, $surfCh / $sh)
                : 1.0;

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

                // §15.1.1 : le besoin assuré par ce générateur, et non celui
                // de toute la cascade, dimensionne ses auxiliaires.
                $parts = (array) $context->get(\CalculDpePHP\Chauffage\PartBesoinGenerateur::CLE_CONTEXTE, []);
                $partBesoin = $parts[$gen->getNodePath()] ?? 1.0;
                $besoinGen = $besoin * $partBesoin * $partLogement;
                $besoinGenDep = $besoinDep * $partBesoin * $partLogement;
                [$g, $h, $pnCapKw] = $this->getGHch($accessor, $gen);

                if ($ratioVirt > 0.0 && $ratioVirt < 1.0) {
                    // §15.1 collectif virtualisé : Pe et Paux sont calculés à
                    // l'échelle du générateur bâtiment. Le besoin porté par
                    // l'installation est déjà celui du logement représenté :
                    // le ratio de virtualisation ne doit pas être réappliqué.
                    $pe     = min($pn / $ratioVirt, $pnCapKw * 1000.0);
                    $peKw   = $pe / 1000.0;
                    $paux   = $g + $h * $peKw;
                    if ($paux <= 0.0) {
                        continue;
                    }
                    $totalQ    += $paux * $besoinGen / $pe;
                    $totalQDep += $paux * ($besoinGen + $besoinGenDep) / $pe;
                } else {
                    $pnKw  = min($pn / 1000.0, $pnCapKw);
                    $paux  = $g + $h * $pnKw;
                    if ($paux <= 0.0) {
                        continue;
                    }
                    $totalQ    += $paux * $besoinGen            * $ratioSurface / $pn;
                    $totalQDep += $paux * ($besoinGen + $besoinGenDep) * $ratioSurface / $pn;
                }
            }
        }

        return [$totalQ, $totalQDep, 1.0];
    }

    /**
     * Surface desservie par une installation de chauffage, à l'échelle du
     * périmètre du DPE : l'immeuble pour un DPE immeuble collectif, le logement
     * décrit sinon.
     */
    private function surfaceDesservie(NodeAccessor $accessor, DOMElement $logement): ?float
    {
        $methode = $accessor->getIntOrNull(
            './caracteristique_generale/enum_methode_application_dpe_log_id',
            $logement
        );
        $surfaceLogement = $accessor->getFloatOrNull(
            './caracteristique_generale/surface_habitable_logement',
            $logement
        );
        if ($methode === null || !in_array($methode, self::METHODES_DPE_IMMEUBLE, true)) {
            return $surfaceLogement;
        }
        return $accessor->getFloatOrNull(
            './caracteristique_generale/surface_habitable_immeuble',
            $logement
        ) ?? $surfaceLogement;
    }

    /**
     * @return array{float, float, float} [Q_aux_ecs, Q_aux_ecs_dep, cle_repartition_ecs]
     */
    private function computeEcsAux(
        NodeAccessor $accessor,
        DOMElement $logement,
        bool $isZone,
        CalculationContext $context,
    ): array
    {
        $collection = $this->getChild($logement, 'installation_ecs_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        $totalQ    = 0.0;
        $totalQDep = 0.0;

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_ecs') {
                continue;
            }

            $xmlPerKwh = IntermediateEnergyUnit::xmlPerKwh($context->document);
            $besoin    = ($accessor->getFloatOrNull('./donnee_intermediaire/besoin_ecs',           $install) ?? 0.0) / $xmlPerKwh;
            $besoinDep = ($accessor->getFloatOrNull('./donnee_intermediaire/besoin_ecs_depensier', $install) ?? 0.0) / $xmlPerKwh;
            $ratioVirt = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',        $install) ?? 1.0;
            // rdim : nombre d'unités (appartements) représentées par cette installation.
            // Comme pour conso_ecs, la conso d'aux est agrégée à l'échelle bâtiment au niveau sortie.
            // §17.2.2.3 p.117 : la clé porte sur l'installation qui produit
            // ce besoin, pas sur la somme de toutes les installations ECS.
            $cleDeclaree = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ecs', $install);
            $partLogement = $isZone && $cleDeclaree !== null && $cleDeclaree > 0.0 ? $cleDeclaree : 1.0;
            $rdim = $this->ecsInstallationMultiplicity($accessor, $install) * $partLogement;

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
                    $paux   = $g + $h * $peKw;
                    if ($paux <= 0.0) {
                        continue;
                    }
                    $totalQ    += $rdim * $paux * $besoin    / $pe;
                    $totalQDep += $rdim * $paux * $besoinDep / $pe;
                } else {
                    $pnKw  = min($pn / 1000.0, $pnCapKw);
                    $paux  = $g + $h * $pnKw;
                    if ($paux <= 0.0) {
                        continue;
                    }
                    $totalQ    += $rdim * $paux * $besoin    / $pn;
                    $totalQDep += $rdim * $paux * $besoinDep / $pn;
                }
            }
        }

        return [$totalQ, $totalQDep, 1.0];
    }

    /**
     * §17.2 — multiplicateur effectif d'une installation ECS individuelle échantillonnée.
     *
     * @spec-formula F-17.2-rdim-effective
     */
    /**
     * Nblgt_syst de l'installation ECS, lu par la même règle que le besoin et
     * la consommation qu'il multiplie (§17.1.3.2 p.109).
     *
     * Le reste du moteur passe par {@see EcsInstallationMultiplicity} : une
     * seconde lecture divergerait du besoin publié sur l'installation, et
     * compterait deux fois les logements d'un relevé exhaustif.
     */
    private function ecsInstallationMultiplicity(NodeAccessor $accessor, DOMElement $install): float
    {
        return max(
            1e-9,
            EcsInstallationMultiplicity::sampledOrNull($install, $accessor)
                ?? ($accessor->getFloatOrNull('./donnee_entree/rdim', $install) ?? 1.0),
        );
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
        $saisi = $accessor->getIntOrNull('./donnee_entree/enum_type_generateur_ch_id', $genNode);
        if ($saisi === null) {
            return self::GH_DEFAULT;
        }
        // Les familles qui ont leur propre ligne dans §15.1 sont lues sur l'enum
        // saisi. Le charbon en fait partie : son alias vers le bois bûche ne vaut
        // que pour le rendement de combustion (§13.2.2.3).
        $gh = $this->resolveGHfromRanges($saisi, 'ch', $accessor, $genNode);
        if ($gh !== self::GH_DEFAULT) {
            return $gh;
        }
        // Sinon, l'enum renvoie à une autre famille : §17.2.1.1 pour le système
        // collectif par défaut (119), §13.2.2 pour les « autres systèmes à
        // combustion » (113-116). On résout alors sur l'enum équivalent.
        $equivalent = \CalculDpePHP\Chauffage\GenerateurChAlias::normalizeNode($saisi, $genNode);
        if ($equivalent === null || $equivalent === $saisi) {
            return self::GH_DEFAULT;
        }
        return $this->resolveGHfromRanges($equivalent, 'ch', $accessor, $genNode);
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
        return $this->resolveGHfromRanges($genId, 'ecs', $accessor, $genNode);
    }

    /** @return array{float, float, float} */
    private function resolveGHfromRanges(
        int $genId,
        string $type,
        NodeAccessor $accessor,
        DOMElement $genNode,
    ): array {
        $chaudGazRanges  = ($type === 'ch') ? self::CH_CHAUDIERE_GAZ_RANGES   : self::ECS_CHAUDIERE_GAZ_RANGES;
        $chaudFioulRanges = ($type === 'ch') ? self::CH_CHAUDIERE_FIOUL_RANGES : self::ECS_CHAUDIERE_FIOUL_RANGES;
        $boisRanges      = ($type === 'ch') ? self::CH_BOIS_RANGES            : self::ECS_BOIS_RANGES;

        if ($this->inRanges($genId, $chaudGazRanges) || $this->inRanges($genId, $chaudFioulRanges)) {
            return self::GH_CHAUDIERE;
        }
        if ($type === 'ch' && $this->inRanges($genId, self::CH_CHARBON_RANGES)) {
            return self::GH_CHAUDIERE;
        }
        if ($type === 'ch' && $this->inRanges($genId, self::CH_RADIATEUR_GAZ_RANGES)) {
            return self::GH_RADIATEUR_GAZ;
        }
        if ($this->inRanges($genId, $boisRanges)) {
            return $this->ligneBois($accessor, $genNode);
        }
        if ($type === 'ch' && $this->inRanges($genId, self::CH_AIR_CHAUD_RANGES)) {
            return self::GH_AIR_CHAUD;
        }
        return self::GH_DEFAULT;
    }

    /**
     * §15.1 p.97 distingue « chaudière bois atmosphérique » (G = 0, H = 0) de
     * « chaudière bois assistée par ventilateur » (G = 73,3, H = 10,5) sans dire
     * comment les départager. `presence_ventouse` le fait : une ventouse est un
     * terminal à tirage forcé, qui suppose un ventilateur de combustion ; une
     * chaudière raccordée au tirage naturel d'un conduit est atmosphérique.
     *
     * @return array{float, float, float}
     */
    private function ligneBois(NodeAccessor $accessor, DOMElement $genNode): array
    {
        $ventouse = $accessor->getIntOrNull('./donnee_entree/presence_ventouse', $genNode);

        return $ventouse === 1 ? self::GH_BOIS_VENTILATEUR : self::GH_BOIS_ATMO;
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
