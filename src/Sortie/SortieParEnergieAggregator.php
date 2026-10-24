<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

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
 * @spec-section  Annexe 4, Annexe 7
 * @spec-source   resources/specsplitted/00-meta/01-methode-conventionnelle.md
 * @spec-source   editeur/public/schemas/DPEv2.7.xsd
 * @spec-source   https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000043353421 (article 1, 9° ; renvoi par l'annexe 4 habitation)
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
        'autre_fossile'    => 0.324,
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
        14 => 'autre_fossile',
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
        14 => 'autre_fossile',
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            EpConsoCalculator::class,
            EmissionGesCalculator::class,
            CoutCalculator::class,
            \CalculDpePHP\ProductionElec\ProductionPvCalculator::class,
        ];
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

        // Les auxiliaires ne forment pas un seul poste tarifaire : ceux d'une
        // installation collective relèvent de l'abonnement de l'immeuble,
        // ceux de la ventilation de celui du logement. `CoutCalculator` les
        // sépare déjà ainsi, et les deux blocs décrivent les mêmes euros —
        // les additionner ici dans un panier unique élargissait l'assiette de
        // la tranche individuelle et faisait baisser son prix du kWh.
        $auxParPoste = [];
        foreach ([
            'conso_auxiliaire_generation_ch',    'conso_auxiliaire_distribution_ch',
            'conso_auxiliaire_generation_ecs',   'conso_auxiliaire_distribution_ecs',
            'conso_auxiliaire_ventilation',
        ] as $poste) {
            $auxParPoste[$poste] = $efNode
                ? ($accessor->getFloatOrNull('./' . $poste, $efNode) ?? 0.0)
                : 0.0;
        }

        // ── 2. Collecter les consos par energie depuis les générateurs CH ──────
        /** @var array<int, float[]> */
        $collecteCh   = ConsoParEnergieCollector::collecter($accessor, $node, 'installation_chauffage', 'generateur_chauffage', 'conso_ch', $context);
        $chByEnergie  = $collecteCh['byEnergie']; // energieId → [conso, consoDep]
        /** @var array<int, float> */
        $chReseauGes  = $collecteCh['reseauGes'];

        // ── 3. Collecter les consos par energie depuis les générateurs ECS ─────
        /** @var array<int, float[]> */
        $collecteEcs  = ConsoParEnergieCollector::collecter($accessor, $node, 'installation_ecs', 'generateur_ecs', 'conso_ecs', $context);
        $ecsByEnergie = $collecteEcs['byEnergie']; // energieId → [conso, consoDep]
        /** @var array<int, float> */
        $ecsReseauGes = $collecteEcs['reseauGes'];

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

            // Les usages électriques non ventilés par générateur sont déjà
            // nets de l'autoconsommation (§16.2 p.104-106). Ils appartiennent
            // à l'électricité achetée (1), jamais une seconde fois à l'énergie
            // renouvelable sur site (12, libellé XSD). Sinon Σ lignes > EF.
            if ($eId === 1) {
                $consoEclE = $consoEcl;
                $consoAuxE = $consoAux;
                $consoFrE    = $consoFr;
                $consoFrEDep = $consoFrDep;
            }

            // §16.2 p.105 : l'électricité autoconsommée n'est pas achetée.
            // `ProductionPvCalculator` la retranche usage par usage de
            // `ef_conso` ; cette ventilation décrit les mêmes kilowattheures et
            // doit donc la retrancher aussi, sans quoi `conso_5_usages` cesse
            // d'être la somme de sa ventilation.
            //
            // **Seuls le chauffage et l'ECS** : le froid, l'éclairage et les
            // auxiliaires sont lus plus haut dans `ef_conso`, donc déjà nets,
            // quand le chauffage et l'ECS viennent des générateurs, que la
            // déduction n'atteint pas. Le scénario dépensier n'est pas déduit,
            // là non plus — c'est la convention de `ef_conso`.
            if ($eId === 1) {
                $consoChE  = max(0.0, $consoChE  - $this->autoconsommation($context, 'ch'));
                $consoEcsE = max(0.0, $consoEcsE - $this->autoconsommation($context, 'ecs'));
            }

            $conso5E    = $consoChE + $consoEcsE + $consoFrE + $consoAuxE + $consoEclE;

            // GES
            $gesCoefCh  = self::GES[self::GES_KEY_CH[$eId]  ?? 'gaz_naturel'] ?? 0.0;
            $gesCoefEcs = self::GES[self::GES_KEY_ECS[$eId] ?? 'gaz_naturel'] ?? 0.0;

            $gesChE  = $eId === 8 ? ($chReseauGes[$eId] ?? 0.0) : $consoChE * $gesCoefCh;
            $gesEcsE = $eId === 8 ? ($ecsReseauGes[$eId] ?? 0.0) : $consoEcsE * $gesCoefEcs;
            $ges5E   = $gesChE + $gesEcsE;

            if ($eId === 1) {
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
                $postesCout[$eId . '|fr']  = [$eId, $consoFrE, false];
                // Même ventilation des auxiliaires que `CoutCalculator`. Les
                // cinq postes sont remis à l'échelle de `conso_totale_auxiliaire`
                // pour que leur somme la reproduise exactement ; s'ils sont tous
                // nuls — un bloc `ef_conso` qui ne publie que le total —, tout
                // retombe sur l'abonnement individuel, comme auparavant.
                $sommePostes = array_sum($auxParPoste);
                if ($sommePostes > 0.0) {
                    $facteur = $consoAuxE / $sommePostes;
                    foreach ([
                        'conso_auxiliaire_generation_ch'    => $prix->chauffageCollectif,
                        'conso_auxiliaire_distribution_ch'  => $prix->chauffageCollectif,
                        'conso_auxiliaire_generation_ecs'   => $prix->ecsCollectif,
                        'conso_auxiliaire_distribution_ecs' => $prix->ecsCollectif,
                        'conso_auxiliaire_ventilation'      => $prix->ventilationCollective,
                    ] as $poste => $collectif) {
                        $postesCout[$eId . '|' . $poste] = [$eId, $auxParPoste[$poste] * $facteur, $collectif];
                    }
                } else {
                    $postesCout[$eId . '|conso_auxiliaire_ventilation'] = [$eId, $consoAuxE, false];
                }
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
                + ($couts[$eId . '|fr'] ?? 0.0);
            foreach ([
                'conso_auxiliaire_generation_ch',  'conso_auxiliaire_distribution_ch',
                'conso_auxiliaire_generation_ecs', 'conso_auxiliaire_distribution_ecs',
                'conso_auxiliaire_ventilation',
            ] as $poste) {
                $cout5E += $couts[$eId . '|' . $poste] ?? 0.0;
            }

            $accessor->setChildValue($item, 'cout_ch',        $coutChE);
            $accessor->setChildValue($item, 'cout_ecs',       $coutEcsE);
            $accessor->setChildValue($item, 'cout_5_usages',  $cout5E);
        }
    }

    /**
     * Part d'autoconsommation photovoltaïque affectée à un usage.
     *
     * `ProductionPvCalculator` la publie dans le contexte parce qu'elle ne
     * peut pas transiter par le XML : le schéma ADEME ne déclare pas de balise
     * pour la déduction par usage, seulement `conso_elec_ac` au total.
     */
    private function autoconsommation(CalculationContext $context, string $usage): float
    {
        return (float) ($context->get('pv.ac_' . $usage) ?? 0.0);
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
