<?php

declare(strict_types=1);

namespace CalculDpe\Sortie;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
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
 * @depends-on    \CalculDpe\Sortie\EpConsoCalculator, \CalculDpe\Sortie\EmissionGesCalculator, \CalculDpe\Sortie\CoutCalculator
 * @tables        (aucune)
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

    // Tarifs Annexe 7 (mars 2021, en €/kWh sauf fonctions tiered)
    private const TARIF_FIXE = [
        3  => 0.09142, // fioul
        4  => 0.03201, // bois bûches
        5  => 0.05991, // bois granulés
        6  => 0.03201, // bois plaquettes forestières
        7  => 0.03201, // bois plaquettes industrie
        8  => 0.0787,  // réseau chaleur urbain
        9  => 0.14305, // propane
        10 => 0.20027, // butane
        11 => 0.02372, // charbon
        13 => 0.14305, // gpl
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

        // ── 2. Collecter les consos par energie depuis les générateurs CH ──────
        /** @var array<int, float[]> */
        $chByEnergie  = []; // energieId → [conso, consoDep]
        $this->collectGenConso($accessor, $node, 'installation_chauffage', 'generateur_chauffage', 'conso_ch', $chByEnergie);

        // ── 3. Collecter les consos par energie depuis les générateurs ECS ─────
        /** @var array<int, float[]> */
        $ecsByEnergie = []; // energieId → [conso, consoDep]
        $this->collectGenConso($accessor, $node, 'installation_ecs', 'generateur_ecs', 'conso_ecs', $ecsByEnergie);

        // ── 4. Union des types d'énergie + toujours électricité (id=1) ─────────
        $energieIds = array_unique(array_merge(
            [1],
            array_keys($chByEnergie),
            array_keys($ecsByEnergie),
        ));
        rsort($energieIds); // non-électrique d'abord, électricité en dernier (idem verif)

        // ── 5. Construire le bloc par énergie ──────────────────────────────────
        $collection = $context->document->createElement('sortie_par_energie_collection');
        $sortie->appendChild($collection);

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

            $gesChE  = $consoChE  * $gesCoefCh;
            $gesEcsE = $consoEcsE * $gesCoefEcs;
            $ges5E   = $gesChE + $gesEcsE;

            if ($eId === 1 || $eId === 12) {
                $ges5E += $consoEclE * self::GES['elec_eclairage']
                        + $consoAuxE * self::GES['elec_auxiliaire']
                        + $consoFrE  * self::GES['elec_fr'];
            }

            // Coût
            $coutChE  = $this->coutEnergie($eId, $consoChE);
            $coutEcsE = $this->coutEnergie($eId, $consoEcsE);
            $cout5E   = $coutChE + $coutEcsE;
            if ($eId === 1 || $eId === 12) {
                $cout5E += $this->coutElectricite($consoEclE)
                         + $this->coutElectricite($consoAuxE)
                         + $this->coutElectricite($consoFrE);
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
            $accessor->setChildValue($item, 'cout_ch',                   $coutChE);
            $accessor->setChildValue($item, 'cout_ecs',                  $coutEcsE);
            $accessor->setChildValue($item, 'cout_5_usages',             $cout5E);
        }
    }

    /**
     * Collecte les consos par énergie depuis les générateurs d'une collection.
     *
     * @param array<int, float[]> $byEnergie
     */
    /**
     * Pour les DPE bâtiment collectif (methode=1, rdim>1), chaque installation
     * représente un logement-type avec rdim copies → multiplier les consos par rdim.
     * Pour les DPE zone (methode=4, cle_repartition), les consos sont déjà au niveau
     * du logement (non multipliées ici).
     */
    private function collectGenConso(
        NodeAccessor $accessor,
        DOMElement $logement,
        string $installTag,
        string $genTag,
        string $consoField,
        array &$byEnergie
    ): void {
        $consoDepField = $consoField . '_depensier';
        $collTag = $installTag . '_collection';

        foreach ($logement->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->nodeName !== $collTag) {
                continue;
            }
            foreach ($child->childNodes as $install) {
                if (!$install instanceof DOMElement || $install->nodeName !== $installTag) {
                    continue;
                }

                // §17 : DPE bâtiment methode=1 → appliquer rdim pour passer
                //       du logement-représentatif au bâtiment entier.
                $methode = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $install) ?? 1;
                $rdim    = $methode === 1
                    ? ($accessor->getFloatOrNull('./donnee_entree/rdim', $install) ?? 1.0)
                    : 1.0;

                foreach ($install->childNodes as $genColl) {
                    if (!$genColl instanceof DOMElement) {
                        continue;
                    }
                    foreach ($genColl->childNodes as $gen) {
                        if (!$gen instanceof DOMElement || $gen->nodeName !== $genTag) {
                            continue;
                        }
                        $eId      = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen) ?? 1;
                        $conso    = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoField,    $gen) ?? 0.0) * $rdim;
                        $consoDep = ($accessor->getFloatOrNull('./donnee_intermediaire/' . $consoDepField, $gen) ?? 0.0) * $rdim;
                        if (!isset($byEnergie[$eId])) {
                            $byEnergie[$eId] = [0.0, 0.0];
                        }
                        $byEnergie[$eId][0] += $conso;
                        $byEnergie[$eId][1] += $consoDep;
                    }
                }
            }
        }
    }

    private function coutEnergie(int $energieId, float $conso): float
    {
        if ($conso === 0.0) {
            return 0.0;
        }
        if ($energieId === 1 || $energieId === 12) {
            return $this->coutElectricite($conso);
        }
        if ($energieId === 2) {
            return $this->coutGazNaturel($conso);
        }
        $tarif = self::TARIF_FIXE[$energieId] ?? null;
        return $tarif !== null ? $tarif * $conso : $conso;
    }

    private function coutGazNaturel(float $cef): float
    {
        if ($cef < 5009.0) {
            return 0.11121 * $cef;
        }
        if ($cef < 50055.0) {
            return 230.0 + 0.06533 * $cef;
        }
        return 415.0 + 0.06164 * $cef;
    }

    private function coutElectricite(float $cef): float
    {
        if ($cef === 0.0) {
            return 0.0;
        }
        if ($cef < 1000.0) {
            return 0.29007 * $cef;
        }
        if ($cef < 2500.0) {
            return 149.0 + 0.14066 * $cef;
        }
        if ($cef < 5000.0) {
            return 122.0 + 0.15176 * $cef;
        }
        if ($cef < 15000.0) {
            return 94.0 + 0.15735 * $cef;
        }
        return 56.0 + 0.15989 * $cef;
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
