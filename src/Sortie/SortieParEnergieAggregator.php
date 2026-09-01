<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Collectif\EcsInstallationMultiplicity;
use CalculDpePHP\Common\IntermediateEnergyUnit;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Ventilation des consommations/émissions/coûts par enum_type_energie_id.
 *
 * Algorithme (open3cl src/conso.js calc_conso_pond, filtré par energie_id) :
 *   - Pour chaque type d'énergie CH+ECS+FR : conso_ch = Σ gen_ch de ce type,
 *     conso_ecs = Σ gen_ecs de ce type, conso_5_usages = ch + ecs + fr de ce type.
 *   - Pour l'électricité (id=1) : conso_5_usages += conso_eclairage + aux_total.
 *   - L'électricité (id=1) est toujours incluse même s'il n'y a pas de générateur électrique.
 *   - Coefficients GES par usage (Annexe 5) : gaz 0.227, élec_ch 0.079, élec_ecs 0.065,
 *     élec_éclairage 0.069, élec_auxiliaire 0.064, fioul 0.324, etc.
 *   - Coûts : mêmes fonctions que CoutCalculator (Annexe 7 mars 2021).
 *
 * @spec-section  Annexe 5, Annexe 7
 * @spec-source   resources/specsplitted/00-meta/01-methode-conventionnelle.md
 * @xml-input     installation_chauffage.generateur_chauffage.{donnee_entree.enum_type_energie_id,
 *                    donnee_intermediaire.{conso_ch, conso_ch_depensier}}
 *                installation_ecs.generateur_ecs.{donnee_entree.enum_type_energie_id,
 *                    donnee_intermediaire.{conso_ecs, conso_ecs_depensier}}
 *                sortie.ef_conso.{conso_eclairage, conso_totale_auxiliaire, conso_fr}
 * @xml-output    sortie.sortie_par_energie_collection.sortie_par_energie[]
 * @depends-on    \CalculDpePHP\Sortie\EpConsoCalculator, \CalculDpePHP\Sortie\EmissionGesCalculator, \CalculDpePHP\Sortie\CoutCalculator
 * @tables        reference/tv_reseau_chaleur
 */
final class SortieParEnergieAggregator implements CalculatorInterface
{
    // GES coefficients (Annexe 5 DPE 3CL-2021, en kgCO2e/kWh EF)
    private const GES = [
        'bois_buches'      => 0.03,
        'bois_granules'    => 0.03,
        'bois_plaquettes'  => 0.024,
        'gaz_naturel'      => 0.227,
        'fioul'            => 0.324,
        'charbon'          => 0.385,
        'propane'          => 0.272,
        'butane'           => 0.272,
        'gpl'              => 0.272,
        'elec_ch'          => 0.079,
        'elec_ecs'         => 0.065,
        'elec_fr'          => 0.064,
        'elec_eclairage'   => 0.069,
        'elec_auxiliaire'  => 0.064,
        'reseau_chaleur'   => 0.385,
        'elec_renouv'      => 0.0,
    ];

    // GES key for each energy_id (ch/ecs usage)
    private const GES_KEY_CH = [
        1  => 'elec_ch',
        2  => 'gaz_naturel',
        3  => 'fioul',
        4  => 'bois_buches',
        5  => 'bois_granules',
        6  => 'bois_plaquettes',
        7  => 'bois_plaquettes',
        8  => 'reseau_chaleur',
        9  => 'propane',
        10 => 'butane',
        11 => 'charbon',
        12 => 'elec_renouv',
        13 => 'gpl',
    ];

    private const GES_KEY_ECS = [
        1  => 'elec_ecs',
        2  => 'gaz_naturel',
        3  => 'fioul',
        4  => 'bois_buches',
        5  => 'bois_granules',
        6  => 'bois_plaquettes',
        7  => 'bois_plaquettes',
        8  => 'reseau_chaleur',
        9  => 'propane',
        10 => 'butane',
        11 => 'charbon',
        12 => 'elec_renouv',
        13 => 'gpl',
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [EpConsoCalculator::class, EmissionGesCalculator::class, CoutCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $sortie   = $accessor->ensureSortie($node);

        // ── 1. Lire ef_conso (éclairage, aux, fr) ─────────────────────────────
        $efNode = $this->getChild($sortie, 'ef_conso');
        $consoEcl    = $efNode ? ($accessor->getFloatOrNull('./conso_eclairage',         $efNode) ?? 0.0) : 0.0;
        $consoAux    = $efNode ? ($accessor->getFloatOrNull('./conso_totale_auxiliaire',  $efNode) ?? 0.0) : 0.0;
        $consoFr     = $efNode ? ($accessor->getFloatOrNull('./conso_fr',                $efNode) ?? 0.0) : 0.0;
        $consoFrDep  = $efNode ? ($accessor->getFloatOrNull('./conso_fr_depensier',      $efNode) ?? 0.0) : 0.0;

        // Mode ZONE (DPE appartement) : les consos par énergie doivent être ramenées
        // au logement (rdim échantillon × cle_repartition), comme dans EfConsoCalculator.
        $isZone   = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node) !== null;
        $nbreAppt = $accessor->getFloatOrNull('./caracteristique_generale/nombre_appartement', $node) ?? 1.0;

        // ── 2. Collecter les consos par energie depuis les générateurs CH ──────
        /** @var array<int, float[]> */
        $chByEnergie  = []; // energieId → [conso, consoDep]
        /** @var array<int, float> */
        $chReseauGes = [];
        $this->collectGenConso($accessor, $node, 'installation_chauffage', 'generateur_chauffage', 'conso_ch', $chByEnergie, $chReseauGes, $isZone, $nbreAppt, $context);

        // ── 3. Collecter les consos par energie depuis les générateurs ECS ─────
        /** @var array<int, float[]> */
        $ecsByEnergie = []; // energieId → [conso, consoDep]
        /** @var array<int, float> */
        $ecsReseauGes = [];
        $this->collectGenConso($accessor, $node, 'installation_ecs', 'generateur_ecs', 'conso_ecs', $ecsByEnergie, $ecsReseauGes, $isZone, $nbreAppt, $context);

        // ── 4. Union des types d'énergie + toujours électricité (id=1) ─────────
        $energieIds = array_unique(array_merge(
            [1],
            array_keys($chByEnergie),
            array_keys($ecsByEnergie),
        ));
        if (IntermediateEnergyUnit::usesAscendingEnergyOrder($context->document)) {
            sort($energieIds);
        } else {
            rsort($energieIds); // exports historiques : non-électrique d'abord
        }

        // ── 5. Construire le bloc par énergie ──────────────────────────────────
        $collection = $context->document->createElement('sortie_par_energie_collection');
        $sortie->appendChild($collection);

        $prix = PrixEnergie::pour($node, $accessor, $context);
        $postesCout = [];
        $items = [];

        foreach ($energieIds as $eId) {
            $consoChE    = $chByEnergie[$eId][0]  ?? 0.0;
            $consoChEDep = $chByEnergie[$eId][1]  ?? 0.0;
            $consoEcsE   = $ecsByEnergie[$eId][0] ?? 0.0;
            $consoEcsEDep = $ecsByEnergie[$eId][1] ?? 0.0;

            $consoFrE    = 0.0;
            $consoFrEDep = 0.0;
            $consoEclE   = 0.0;
            $consoAuxE   = 0.0;

            // Électricité (id=1 ou id=12) reçoit éclairage + auxiliaires + fr
            if ($eId === 1 || $eId === 12) {
                $consoEclE = $consoEcl;
                $consoAuxE = $consoAux;
                $consoFrE    = $consoFr;
                $consoFrEDep = $consoFrDep;
            }

            $conso5E    = $consoChE + $consoEcsE + $consoFrE + $consoAuxE + $consoEclE;

            // GES
            $gesCoefCh  = self::GES[self::GES_KEY_CH[$eId]  ?? 'gaz_naturel'] ?? 0.0;
            $gesCoefEcs = self::GES[self::GES_KEY_ECS[$eId] ?? 'gaz_naturel'] ?? 0.0;

            $gesChE  = $eId === 8 ? ($chReseauGes[$eId] ?? 0.0) : $consoChE * $gesCoefCh;
            $gesEcsE = $eId === 8 ? ($ecsReseauGes[$eId] ?? 0.0) : $consoEcsE * $gesCoefEcs;
            $ges5E   = $gesChE + $gesEcsE;

            if ($eId === 1 || $eId === 12) {
                $ges5E += $consoEclE * self::GES['elec_eclairage']
                        + $consoAuxE * self::GES['elec_auxiliaire']
                        + $consoFrE  * self::GES['elec_fr'];
            }

            // Coût : les postes sont accumulés, la tarification a lieu une
            // seule fois après la boucle. La tranche de l'électricité et du gaz
            // porte sur le total d'un abonnement, pas sur un poste isolé.
            $postesCout[$eId . '|ch']  = [$eId, $consoChE, $prix->chauffageCollectif];
            $postesCout[$eId . '|ecs'] = [$eId, $consoEcsE, $prix->ecsCollectif];
            if (PrixEnergie::estElectricite($eId)) {
                $postesCout[$eId . '|ecl'] = [$eId, $consoEclE, false];
                $postesCout[$eId . '|aux'] = [$eId, $consoAuxE, false];
                $postesCout[$eId . '|fr']  = [$eId, $consoFrE, false];
            }

            $item = $context->document->createElement('sortie_par_energie');
            $collection->appendChild($item);

            $accessor->setChildValue($item, 'enum_type_energie_id',      $eId);
            $accessor->setChildValue($item, 'conso_ch',                  $consoChE);
            $accessor->setChildValue($item, 'conso_ecs',                 $consoEcsE);
            $accessor->setChildValue($item, 'conso_5_usages',            $conso5E);
            $accessor->setChildValue($item, 'emission_ges_ch',           $gesChE);
            $accessor->setChildValue($item, 'emission_ges_ecs',          $gesEcsE);
            $accessor->setChildValue($item, 'emission_ges_5_usages',     $ges5E);
            $items[$eId] = $item;
        }

        // Tarification unique, tous postes et toutes énergies confondus.
        $couts = $prix->tarifer($postesCout);
        foreach ($items as $eId => $item) {
            $coutChE  = $couts[$eId . '|ch'] ?? 0.0;
            $coutEcsE = $couts[$eId . '|ecs'] ?? 0.0;
            $cout5E   = $coutChE + $coutEcsE
                + ($couts[$eId . '|ecl'] ?? 0.0)
                + ($couts[$eId . '|aux'] ?? 0.0)
                + ($couts[$eId . '|fr'] ?? 0.0);

            $accessor->setChildValue($item, 'cout_ch',        $coutChE);
            $accessor->setChildValue($item, 'cout_ecs',       $coutEcsE);
            $accessor->setChildValue($item, 'cout_5_usages',  $cout5E);
        }
    }

    /**
     * Collecte les consos par énergie depuis les générateurs d'une collection.
     *
     * @param array<int, float[]> $byEnergie
     * @param array<int, float> $reseauGesByEnergie
     */
    /**
     * Mise à l'échelle identique à EfConsoCalculator (§17) :
     *   BAT  : conso × rdimEff (rdim, ou nbApt×ratio_virt/Σéchantillon pour ZONE individuel)
     *   ZONE : le total est ensuite ramené au logement via cle_repartition_ch/ecs.
     */
    private function collectGenConso(
        NodeAccessor $accessor,
        DOMElement $logement,
        string $installTag,
        string $genTag,
        string $consoField,
        array &$byEnergie,
        array &$reseauGesByEnergie,
        bool $isZone,
        float $nbreAppt,
        CalculationContext $context,
    ): void {
        $consoDepField = $consoField . '_depensier';
        $collTag  = $installTag . '_collection';
        $isCh     = $installTag === 'installation_chauffage';
        $sumField = $isCh ? 'nombre_logement_echantillon' : 'nombre_logement';
        $cleField = $isCh ? 'cle_repartition_ch' : 'cle_repartition_ecs';

        foreach ($logement->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->nodeName !== $collTag) {
                continue;
            }

            // Σ(nombre_logement[_echantillon]) pour le rdim effectif ZONE individuel
            $sumEchantillon = 0.0;
            foreach ($child->childNodes as $install) {
                if ($install instanceof DOMElement && $install->nodeName === $installTag) {
                    $sumEchantillon += $accessor->getFloatOrNull('./donnee_entree/' . $sumField, $install) ?? 0.0;
                }
            }
            if ($sumEchantillon <= 0.0) {
                $sumEchantillon = 1.0;
            }

            foreach ($child->childNodes as $install) {
                if (!$install instanceof DOMElement || $install->nodeName !== $installTag) {
                    continue;
                }

                $methode     = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $install) ?? 1;
                $typeInstall = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id',    $install) ?? 1;
                $rdim        = $accessor->getFloatOrNull('./donnee_entree/rdim',                       $install) ?? 1.0;
                $ratioVirt   = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',       $install) ?? 1.0;

                if ($methode === 1) {
                    $rdimEff = $rdim;
                } elseif (!$isCh && $methode === 4 && $typeInstall === 1) {
                    $rdimEff = EcsInstallationMultiplicity::sampledOrNull($install, $accessor)
                        ?? ($nbreAppt * $ratioVirt / $sumEchantillon);
                } elseif ($typeInstall === 1) {
                    $rdimEff = $nbreAppt * $ratioVirt / $sumEchantillon;
                } else {
                    $rdimEff = $rdim;
                }
                $rdimEff = max(1e-9, $rdimEff);

                // ZONE : ramener du bâtiment au logement via la clé de répartition
                $scale = $rdimEff;
                if ($isZone) {
                    $cle = $accessor->getFloatOrNull('./donnee_entree/' . $cleField, $install);
                    if ($cle !== null && $cle > 0.0) {
                        $scale *= $cle;
                    }
                }

                // Le format ADEME natif reprend le total de l'installation
                // lorsqu'elle ne possède qu'un générateur. Cela évite les
                // écarts de précision dus au rendement réécrit sur le générateur.
                $generators = $install->getElementsByTagName($genTag);
                if (IntermediateEnergyUnit::isNativeAdeme($context->document) && $generators->length === 1) {
                    $gen = $generators->item(0);
                    if ($gen instanceof DOMElement) {
                        $eId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen) ?? 1;
                        $conso = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoField, $install) ?? 0.0) * $scale;
                        $consoDep = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoDepField, $install) ?? 0.0) * $scale;
                        $byEnergie[$eId] ??= [0.0, 0.0];
                        $byEnergie[$eId][0] += $conso;
                        $byEnergie[$eId][1] += $consoDep;
                        if ($eId === 8) {
                            $factor = ReseauChaleurFactorResolver::resolve($gen, $accessor, $context);
                            $reseauGesByEnergie[$eId] = ($reseauGesByEnergie[$eId] ?? 0.0) + $conso * $factor;
                        }
                        continue;
                    }
                }

                foreach ($install->childNodes as $genColl) {
                    if (!$genColl instanceof DOMElement) {
                        continue;
                    }
                    foreach ($genColl->childNodes as $gen) {
                        if (!$gen instanceof DOMElement || $gen->nodeName !== $genTag) {
                            continue;
                        }
                        $eId      = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen) ?? 1;
                        $conso    = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoField,    $gen) ?? 0.0) * $scale;
                        $consoDep = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoDepField, $gen) ?? 0.0) * $scale;
                        if (!isset($byEnergie[$eId])) {
                            $byEnergie[$eId] = [0.0, 0.0];
                        }
                        $byEnergie[$eId][0] += $conso;
                        $byEnergie[$eId][1] += $consoDep;
                        if ($eId === 8) {
                            $factor = ReseauChaleurFactorResolver::resolve($gen, $accessor, $context);
                            $reseauGesByEnergie[$eId] = ($reseauGesByEnergie[$eId] ?? 0.0) + $conso * $factor;
                        }
                    }
                }
            }
        }
    }

    private function getChild(DOMElement $parent, string $tagName): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $tagName) {
                return $child;
            }
        }
        return null;
    }
}
