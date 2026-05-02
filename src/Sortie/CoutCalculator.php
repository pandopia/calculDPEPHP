<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc <sortie><cout> : coût annuel par usage (€).
 *
 * Tarifs de référence selon Annexe 7 de l'arrêté DPE 3CL-2021 (au 31 mars 2021).
 * Note : les fichiers de vérification ADEME utilisent des tarifs actualisés
 * post-2022 qui ne sont pas publiés dans la spec ouverte. La présente
 * implémentation suit l'Annexe 7 officielle.
 *
 * @spec-section  Annexe 7 (p.147)
 * @spec-source   resources/specsplitted/00-meta/01-methode-conventionnelle.md
 * @xml-input     sortie.ef_conso.*,
 *                installation_chauffage.generateur_chauffage.donnee_entree.enum_type_energie_id,
 *                installation_ecs.generateur_ecs.donnee_entree.enum_type_energie_id
 * @xml-output    sortie.cout.{cout_ch, cout_ch_depensier, cout_ecs, cout_ecs_depensier,
 *                    cout_eclairage, cout_auxiliaire_*, cout_total_auxiliaire,
 *                    cout_fr, cout_fr_depensier, cout_5_usages}
 * @depends-on    \CalculDpePHP\Sortie\EfConsoCalculator
 * @tables        (aucune)
 */
final class CoutCalculator implements CalculatorInterface
{
    // Annexe 7 — tarifs au 31 mars 2021 (€/kWh sauf ceux avec fonction)
    private const TARIF_FIOUL          = 0.09142;
    private const TARIF_RESEAU_CHALEUR = 0.0787;
    private const TARIF_PROPANE        = 0.14305;
    private const TARIF_BUTANE         = 0.20027;
    private const TARIF_CHARBON        = 0.02372;
    private const TARIF_BOIS_GRANULES  = 0.05991;
    private const TARIF_BOIS_BUCHES    = 0.03201;
    private const TARIF_BOIS_PLAQUET   = 0.03201;

    // enum_type_energie_id → tarif fixe (null = fonction tiered)
    private const TARIF_PAR_ENERGIE = [
        1  => null,  // électricité → voir coutElectricite()
        2  => null,  // gaz naturel → voir coutGazNaturel()
        3  => self::TARIF_FIOUL,
        4  => self::TARIF_BOIS_BUCHES,
        5  => self::TARIF_BOIS_GRANULES,
        6  => self::TARIF_BOIS_PLAQUET,
        7  => self::TARIF_BOIS_PLAQUET,
        8  => self::TARIF_RESEAU_CHALEUR,
        9  => self::TARIF_PROPANE,
        10 => self::TARIF_BUTANE,
        11 => self::TARIF_CHARBON,
        12 => null,  // élec renouvelable → coutElectricite()
        13 => self::TARIF_PROPANE, // gpl ≈ propane
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [EfConsoCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $sortie   = $accessor->ensureSortie($node);

        // Lire les consommations depuis <sortie><ef_conso>
        $ef = $this->getEfConso($accessor, $sortie);

        // Déterminer le type d'énergie des générateurs CH et ECS
        $energieChId  = $this->primaryEnergieId($accessor, $node, 'installation_chauffage', 'generateur_chauffage');
        $energieEcsId = $this->primaryEnergieId($accessor, $node, 'installation_ecs', 'generateur_ecs');
        $energieFrId  = $this->primaryEnergieId($accessor, $node, 'installation_climatisation', 'generateur_climatisation')
            ?? 1; // électricité par défaut pour le froid

        // Coûts CH
        $coutCh    = $this->coutEnergie($energieChId, $ef['conso_ch']);
        $coutChDep = $this->coutEnergie($energieChId, $ef['conso_ch_depensier']);

        // Coûts ECS
        $coutEcs    = $this->coutEnergie($energieEcsId, $ef['conso_ecs']);
        $coutEcsDep = $this->coutEnergie($energieEcsId, $ef['conso_ecs_depensier']);

        // Coûts FR
        $coutFr    = $this->coutEnergie($energieFrId, $ef['conso_fr']);
        $coutFrDep = $this->coutEnergie($energieFrId, $ef['conso_fr_depensier']);

        // Auxiliaires et éclairage → toujours électricité
        $coutEcl         = $this->coutElectricite($ef['conso_eclairage']);
        $coutAuxGenCh    = $this->coutElectricite($ef['conso_auxiliaire_generation_ch']);
        $coutAuxGenChDep = $this->coutElectricite($ef['conso_auxiliaire_generation_ch_depensier']);
        $coutAuxDistCh   = $this->coutElectricite($ef['conso_auxiliaire_distribution_ch']);
        $coutAuxGenEcs   = $this->coutElectricite($ef['conso_auxiliaire_generation_ecs']);
        $coutAuxGenEcsDep = $this->coutElectricite($ef['conso_auxiliaire_generation_ecs_depensier']);
        $coutAuxDistEcs  = $this->coutElectricite($ef['conso_auxiliaire_distribution_ecs']);
        $coutAuxVent     = $this->coutElectricite($ef['conso_auxiliaire_ventilation']);

        $coutTotalAux = $coutAuxGenCh + $coutAuxDistCh + $coutAuxGenEcs + $coutAuxDistEcs + $coutAuxVent;
        $cout5Usages  = $coutCh + $coutEcs + $coutFr + $coutTotalAux + $coutEcl;

        $cout = $context->document->createElement('cout');
        $sortie->appendChild($cout);

        $accessor->setChildValue($cout, 'cout_ch',                                   $coutCh);
        $accessor->setChildValue($cout, 'cout_ch_depensier',                         $coutChDep);
        $accessor->setChildValue($cout, 'cout_ecs',                                  $coutEcs);
        $accessor->setChildValue($cout, 'cout_ecs_depensier',                        $coutEcsDep);
        $accessor->setChildValue($cout, 'cout_eclairage',                            $coutEcl);
        $accessor->setChildValue($cout, 'cout_auxiliaire_generation_ch',             $coutAuxGenCh);
        $accessor->setChildValue($cout, 'cout_auxiliaire_generation_ch_depensier',   $coutAuxGenChDep);
        $accessor->setChildValue($cout, 'cout_auxiliaire_distribution_ch',           $coutAuxDistCh);
        $accessor->setChildValue($cout, 'cout_auxiliaire_generation_ecs',            $coutAuxGenEcs);
        $accessor->setChildValue($cout, 'cout_auxiliaire_generation_ecs_depensier',  $coutAuxGenEcsDep);
        $accessor->setChildValue($cout, 'cout_auxiliaire_distribution_ecs',          $coutAuxDistEcs);
        $accessor->setChildValue($cout, 'cout_auxiliaire_ventilation',               $coutAuxVent);
        $accessor->setChildValue($cout, 'cout_total_auxiliaire',                     $coutTotalAux);
        $accessor->setChildValue($cout, 'cout_fr',                                   $coutFr);
        $accessor->setChildValue($cout, 'cout_fr_depensier',                         $coutFrDep);
        $accessor->setChildValue($cout, 'cout_5_usages',                             $cout5Usages);
    }

    /**
     * Applique le tarif correspondant à l'énergie.
     * Pour électricité (id 1/12) et gaz naturel (id 2), utilise les fonctions tiered.
     * Pour les autres, tarif linéaire fixe.
     */
    private function coutEnergie(?int $energieId, float $conso): float
    {
        if ($conso === 0.0) {
            return 0.0;
        }
        $id = $energieId ?? 1;
        if ($id === 1 || $id === 12) {
            return $this->coutElectricite($conso);
        }
        if ($id === 2) {
            return $this->coutGazNaturel($conso);
        }
        $tarif = self::TARIF_PAR_ENERGIE[$id] ?? null;
        if ($tarif !== null) {
            return $tarif * $conso;
        }
        return $conso; // énergie inconnue : 1 €/kWh (fallback visible)
    }

    /**
     * §Annexe 7 — Tarif gaz naturel (€/an) selon tranches de consommation annuelle (kWh EF).
     *
     * @spec-formula Annexe7-gaz : 0.11121×C, 230+0.06533×C, 415+0.06164×C
     */
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

    /**
     * §Annexe 7 — Tarif électricité (€/an) selon tranches de consommation annuelle (kWh EF).
     *
     * @spec-formula Annexe7-elec : 0.29007×C, 149+0.14066×C, 122+0.15176×C, 94+0.15735×C, 56+0.15989×C
     */
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

    /**
     * @return array<string, float>
     */
    private function getEfConso(NodeAccessor $accessor, DOMElement $sortie): array
    {
        $keys = [
            'conso_ch', 'conso_ch_depensier', 'conso_ecs', 'conso_ecs_depensier',
            'conso_eclairage', 'conso_fr', 'conso_fr_depensier',
            'conso_auxiliaire_generation_ch', 'conso_auxiliaire_generation_ch_depensier',
            'conso_auxiliaire_distribution_ch',
            'conso_auxiliaire_generation_ecs', 'conso_auxiliaire_generation_ecs_depensier',
            'conso_auxiliaire_distribution_ecs', 'conso_auxiliaire_ventilation',
        ];
        $ef = [];
        foreach ($keys as $key) {
            $ef[$key] = $accessor->getFloatOrNull('./ef_conso/' . $key, $sortie) ?? 0.0;
        }
        return $ef;
    }

    /**
     * Retourne l'enum_type_energie_id du premier générateur trouvé dans la collection.
     */
    private function primaryEnergieId(
        NodeAccessor $accessor,
        DOMElement $logement,
        string $installCollection,
        string $generateurTag
    ): ?int {
        $collection = null;
        foreach ($logement->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $installCollection . '_collection') {
                $collection = $child;
                break;
            }
        }
        if ($collection === null) {
            return null;
        }
        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== $installCollection) {
                continue;
            }
            foreach ($install->childNodes as $genColl) {
                if (!$genColl instanceof DOMElement) {
                    continue;
                }
                foreach ($genColl->childNodes as $gen) {
                    if (!$gen instanceof DOMElement || $gen->nodeName !== $generateurTag) {
                        continue;
                    }
                    $id = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen);
                    if ($id !== null) {
                        return $id;
                    }
                }
            }
        }
        return null;
    }
}
