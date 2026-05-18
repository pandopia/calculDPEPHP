<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc <sortie><emission_ges> : émissions de GES (kgCO2eq/an) et classe GES A→G.
 *
 * Facteurs d'émission (kgCO2eq/kWh EF) par type d'énergie et usage :
 *   Électricité CH   : 0.079  Électricité ECS   : 0.065
 *   Électricité ECL  : 0.069  Électricité AUX   : 0.064
 *   Gaz naturel      : 0.227  Fioul domestique  : 0.324
 *   Bois (toutes formes) : 0.030  Propane : 0.272  Réseau chaleur : 0.110
 *
 * Met à jour classe_bilan_dpe = WORST(classe_energie, classe_ges).
 *
 * @spec-section  1
 * @spec-pages    6
 * @spec-source   resources/specsplitted/00-meta/01-methode-conventionnelle.md
 * @xml-input     sortie.ef_conso.*
 *                installation_chauffage.generateur_chauffage_collection.generateur_chauffage.donnee_entree.enum_type_energie_id
 *                installation_ecs.generateur_ecs_collection.generateur_ecs.donnee_entree.enum_type_energie_id
 * @xml-output    sortie.emission_ges.{emission_ges_ch, emission_ges_ecs, emission_ges_eclairage,
 *                    emission_ges_fr, emission_ges_auxiliaire_*, emission_ges_totale_auxiliaire,
 *                    emission_ges_5_usages, emission_ges_5_usages_m2, classe_emission_ges}
 *                sortie.ep_conso.classe_bilan_dpe (mis à jour avec WORST(classe_energie, classe_ges))
 * @depends-on    \CalculDpePHP\Sortie\EfConsoCalculator
 *                \CalculDpePHP\Sortie\EpConsoCalculator
 * @tables        (aucune)
 */
final class EmissionGesCalculator implements CalculatorInterface
{
    // Facteurs GES électricité par usage (kgCO2eq/kWh EF)
    private const GES_ELEC_CH  = 0.079;
    private const GES_ELEC_ECS = 0.065;
    private const GES_ELEC_ECL = 0.069;
    private const GES_ELEC_AUX = 0.064;

    // Facteurs GES par type d'énergie (kgCO2eq/kWh EF)
    private const GES_BY_ENERGY = [
        1 => null,   // électricité → dépend de l'usage, voir méthodes dédiées
        2 => 0.227,  // gaz naturel
        3 => 0.324,  // fioul domestique
        4 => 0.030,  // bois – bûches
        5 => 0.030,  // bois – granulés
        6 => 0.030,  // bois – plaquettes forestières
        7 => 0.030,  // bois – plaquettes d'industrie
        8 => 0.110,  // réseau de chauffage urbain
        9 => 0.272,  // propane
    ];

    /** Seuils de classe GES (kgCO2eq/m².an) : A≤6, B≤11, C≤30, D≤50, E≤70, F≤100, G>100 */
    private const GES_THRESHOLDS = [
        'A' => 6, 'B' => 11, 'C' => 30, 'D' => 50, 'E' => 70, 'F' => 100,
    ];

    /** Ordre des classes de A à G pour calculer la moins bonne. */
    private const CLASSE_ORDER = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Sortie\EfConsoCalculator',
            '\CalculDpePHP\Sortie\EpConsoCalculator',
            '\CalculDpePHP\ProductionElec\ProductionPvCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Paramètres ────────────────────────────────────────────────────
        $shLogement = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node);
        $shImmeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node) ?? 0.0;
        $nbreAppt   = $accessor->getFloatOrNull('./caracteristique_generale/nombre_appartement', $node) ?? 1.0;
        $isZone     = $shLogement !== null;
        $surface    = $isZone ? (float)$shLogement : $shImmeuble;

        // ── 2. Chauffage GES ─────────────────────────────────────────────────
        [$gesChTotal, $gesChDepTotal, $cleRepartitionCh] =
            $this->aggregateChGes($accessor, $node, $nbreAppt, $context);

        $gesConsoChEf    = $isZone ? $gesChTotal * $cleRepartitionCh : $gesChTotal;
        // GES dépensier chauffage = GES conventionnel (même convention que l'EP :
        // le scénario dépensier ne change pas les émissions de référence pour l'étiquette).
        $gesConsoChDepEf = $gesConsoChEf;

        // ── 3. ECS GES ───────────────────────────────────────────────────────
        [$gesEcsTotal, , $cleRepartitionEcs] =
            $this->aggregateEcsGes($accessor, $node, $nbreAppt, $context);

        $gesConsoEcsEf    = $isZone ? $gesEcsTotal * $cleRepartitionEcs : $gesEcsTotal;
        // GES dépensier ECS = GES conventionnel (même convention).
        $gesConsoEcsDepEf = $gesConsoEcsEf;

        // ── 4. ef_conso depuis le DOM ────────────────────────────────────────
        $sortie    = $accessor->ensureSortie($node);
        $efConso   = $this->ensureChild($context->document, $sortie, 'ef_conso');
        $emGes     = $this->ensureChild($context->document, $sortie, 'emission_ges');

        $efConsoFr    = $accessor->getFloatOrNull('./conso_fr',           $efConso) ?? 0.0;
        $efConsoFrDep = $accessor->getFloatOrNull('./conso_fr_depensier', $efConso) ?? 0.0;
        $efConsoEcl   = $accessor->getFloatOrNull('./conso_eclairage',    $efConso) ?? 0.0;

        // Froid : électricité (COP intégré dans ConsoFroidCalculator)
        $gesConsoFr    = $efConsoFr    * self::GES_ELEC_CH;
        $gesConsoFrDep = $efConsoFrDep * self::GES_ELEC_CH;

        // Éclairage
        $gesConsoEcl = $efConsoEcl * self::GES_ELEC_ECL;

        // ── 5. Auxiliaires ──────────────────────────────────────────────────
        $cauxGenCh     = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ch',               $efConso) ?? 0.0;
        $cauxGenChDep  = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ch_depensier',      $efConso) ?? 0.0;
        $cauxDistCh    = $accessor->getFloatOrNull('./conso_auxiliaire_distribution_ch',             $efConso) ?? 0.0;
        $cauxGenEcs    = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ecs',              $efConso) ?? 0.0;
        $cauxGenEcsDep = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ecs_depensier',    $efConso) ?? 0.0;
        $cauxDistEcs   = $accessor->getFloatOrNull('./conso_auxiliaire_distribution_ecs',            $efConso) ?? 0.0;
        $cauxVent      = $accessor->getFloatOrNull('./conso_auxiliaire_ventilation',                 $efConso) ?? 0.0;

        $gesCauxGenCh     = $cauxGenCh * self::GES_ELEC_AUX;
        $gesCauxGenChDep  = $gesCauxGenCh;  // dépensier = conventionnel
        $gesCauxDistCh    = $cauxDistCh    * self::GES_ELEC_AUX;
        $gesCauxGenEcs    = $cauxGenEcs    * self::GES_ELEC_AUX;
        $gesCauxGenEcsDep = $gesCauxGenEcs;  // dépensier = conventionnel
        $gesCauxDistEcs   = $cauxDistEcs   * self::GES_ELEC_AUX;
        $gesCauxVent      = $cauxVent      * self::GES_ELEC_AUX;
        $gesCauxTotal     = $gesCauxGenCh + $gesCauxDistCh + $gesCauxGenEcs + $gesCauxDistEcs + $gesCauxVent;

        // ── 6. 5 usages GES ─────────────────────────────────────────────────
        $ges5   = $gesConsoChEf + $gesConsoEcsEf + $gesConsoFr + $gesConsoEcl + $gesCauxTotal;
        $ges5m2 = $surface > 0.0 ? (int)floor($ges5 / $surface) : 0;

        $classeGes = $this->classeGes($ges5m2);

        // ── 7. Mise à jour classe_bilan_dpe = WORST(classe_energie, classe_ges) ──
        $epConso = $this->ensureChild($context->document, $sortie, 'ep_conso');
        $classeEnergie = $accessor->getStringOrNull('./classe_bilan_dpe', $epConso) ?? 'G';
        $classeBilan   = $this->worstClasse($classeEnergie, $classeGes);
        $accessor->setChildValue($epConso, 'classe_bilan_dpe', $classeBilan);

        // ── 8. Écriture dans sortie/emission_ges ────────────────────────────
        $accessor->setChildValue($emGes, 'emission_ges_ch',                                  $gesConsoChEf);
        $accessor->setChildValue($emGes, 'emission_ges_ch_depensier',                        $gesConsoChDepEf);
        $accessor->setChildValue($emGes, 'emission_ges_ecs',                                 $gesConsoEcsEf);
        $accessor->setChildValue($emGes, 'emission_ges_ecs_depensier',                       $gesConsoEcsDepEf);
        $accessor->setChildValue($emGes, 'emission_ges_eclairage',                           $gesConsoEcl);
        $accessor->setChildValue($emGes, 'emission_ges_auxiliaire_generation_ch',            $gesCauxGenCh);
        $accessor->setChildValue($emGes, 'emission_ges_auxiliaire_generation_ch_depensier',  $gesCauxGenChDep);
        $accessor->setChildValue($emGes, 'emission_ges_auxiliaire_distribution_ch',          $gesCauxDistCh);
        $accessor->setChildValue($emGes, 'emission_ges_auxiliaire_generation_ecs',           $gesCauxGenEcs);
        $accessor->setChildValue($emGes, 'emission_ges_auxiliaire_generation_ecs_depensier', $gesCauxGenEcsDep);
        $accessor->setChildValue($emGes, 'emission_ges_auxiliaire_distribution_ecs',         $gesCauxDistEcs);
        $accessor->setChildValue($emGes, 'emission_ges_auxiliaire_ventilation',              $gesCauxVent);
        $accessor->setChildValue($emGes, 'emission_ges_totale_auxiliaire',                   $gesCauxTotal);
        $accessor->setChildValue($emGes, 'emission_ges_fr',                                  $gesConsoFr);
        $accessor->setChildValue($emGes, 'emission_ges_fr_depensier',                        $gesConsoFrDep);
        $accessor->setChildValue($emGes, 'emission_ges_5_usages',                            $ges5);
        $accessor->setChildValue($emGes, 'emission_ges_5_usages_m2',                         $ges5m2);
        $accessor->setChildValue($emGes, 'classe_emission_ges',                              $classeGes);
    }

    /**
     * @return array{float, float, float} [ges_total, ges_total_dep, cle_repartition_ch]
     */
    private function aggregateChGes(NodeAccessor $accessor, DOMElement $logement, float $nbreAppt, CalculationContext $context): array
    {
        $collection = $this->getChild($logement, 'installation_chauffage_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        $sumEchantillon = 0.0;
        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_chauffage') {
                continue;
            }
            $ne = $accessor->getFloatOrNull('./donnee_entree/nombre_logement_echantillon', $install) ?? 0.0;
            $sumEchantillon += $ne;
        }
        if ($sumEchantillon <= 0.0) {
            $sumEchantillon = 1.0;
        }

        $totalGes    = 0.0;
        $totalGesDep = 0.0;
        $cleRepartition = 1.0;

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_chauffage') {
                continue;
            }

            $rdimEff  = $this->computeRdimChauffage($accessor, $install, $nbreAppt, $sumEchantillon);
            $conso    = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ch',           $install) ?? 0.0;
            $consoDep = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ch_depensier', $install) ?? 0.0;

            $energyId = $this->firstGeneratorEnergyType($accessor, $install, 'generateur_chauffage');
            $gesFact  = $this->gesFactorCh($energyId, $install, $accessor, 'generateur_chauffage', $context);

            $totalGes    += $conso    * $rdimEff * $gesFact;
            $totalGesDep += $consoDep * $rdimEff * $gesFact;

            $cle = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ch', $install);
            if ($cle !== null && $cle > 0.0) {
                $cleRepartition = $cle;
            }
        }

        return [$totalGes, $totalGesDep, $cleRepartition];
    }

    /**
     * @return array{float, float, float} [ges_total, ges_total_dep, cle_repartition_ecs]
     */
    private function aggregateEcsGes(NodeAccessor $accessor, DOMElement $logement, float $nbreAppt, CalculationContext $context): array
    {
        $collection = $this->getChild($logement, 'installation_ecs_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        $sumLogement = 0.0;
        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_ecs') {
                continue;
            }
            $ne = $accessor->getFloatOrNull('./donnee_entree/nombre_logement', $install) ?? 0.0;
            $sumLogement += $ne;
        }
        if ($sumLogement <= 0.0) {
            $sumLogement = 1.0;
        }

        $totalGes    = 0.0;
        $totalGesDep = 0.0;
        $cleRepartition = 1.0;

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_ecs') {
                continue;
            }

            $rdimEff = $this->computeRdimEcs($accessor, $install, $nbreAppt, $sumLogement);

            $installGes    = 0.0;
            $installGesDep = 0.0;
            foreach ($install->getElementsByTagName('generateur_ecs') as $gen) {
                if (!$gen instanceof DOMElement) {
                    continue;
                }
                $conso    = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ecs',           $gen) ?? 0.0;
                $consoDep = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ecs_depensier', $gen) ?? 0.0;

                $energyId = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen) ?? 2;
                $gesFact  = $this->gesFactorEcs($energyId, $gen, $accessor, $context);

                $installGes    += $conso    * $gesFact;
                $installGesDep += $consoDep * $gesFact;
            }

            $totalGes    += $installGes    * $rdimEff;
            $totalGesDep += $installGesDep * $rdimEff;

            $cle = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ecs', $install);
            if ($cle !== null && $cle > 0.0) {
                $cleRepartition = $cle;
            }
        }

        return [$totalGes, $totalGesDep, $cleRepartition];
    }

    private function gesFactorCh(
        int $energyTypeId,
        ?DOMElement $install = null,
        ?NodeAccessor $accessor = null,
        ?string $generatorTag = null,
        ?CalculationContext $context = null,
    ): float {
        if ($energyTypeId === 1) {
            return self::GES_ELEC_CH;
        }
        if ($energyTypeId === 8 && $install !== null && $accessor !== null && $context !== null && $generatorTag !== null) {
            $gen = $this->firstGenerator($install, $generatorTag);
            if ($gen !== null) {
                $factor = $this->resolveReseauChaleurFactor($gen, $accessor, $context);
                if ($factor !== null) {
                    return $factor;
                }
            }
        }
        return self::GES_BY_ENERGY[$energyTypeId] ?? 0.0;
    }

    private function gesFactorEcs(
        int $energyTypeId,
        ?DOMElement $gen = null,
        ?NodeAccessor $accessor = null,
        ?CalculationContext $context = null,
    ): float {
        if ($energyTypeId === 1) {
            return self::GES_ELEC_ECS;
        }
        if ($energyTypeId === 8 && $gen !== null && $accessor !== null && $context !== null) {
            $factor = $this->resolveReseauChaleurFactor($gen, $accessor, $context);
            if ($factor !== null) {
                return $factor;
            }
        }
        return self::GES_BY_ENERGY[$energyTypeId] ?? 0.0;
    }

    /**
     * Lookup contenu_co2_acv pour un générateur sur réseau de chauffage urbain.
     * Année : year(date_arrete_reseau_chaleur) - 1, sinon year(date_etablissement_dpe) - 1.
     * Clamp ≥ 2022. Fallback null (caller utilise 0.385 par défaut).
     */
    private function resolveReseauChaleurFactor(
        DOMElement $gen,
        NodeAccessor $accessor,
        CalculationContext $context,
    ): ?float {
        $reseauId = $accessor->getStringOrNull('./donnee_entree/identifiant_reseau_chaleur', $gen);
        if ($reseauId === null || $reseauId === '') {
            return 0.385; // « autres réseaux de chaleur »
        }

        $dateArrete = $accessor->getStringOrNull('./donnee_entree/date_arrete_reseau_chaleur', $gen);
        $dateRef    = $dateArrete ?: $accessor->getStringOrNull('//date_etablissement_dpe', $gen);
        $year = 2022;
        if ($dateRef !== null) {
            $ts = strtotime($dateRef);
            if ($ts !== false) {
                $year = max(2022, (int)date('Y', $ts) - 1);
            }
        }

        $table = $context->tables->load('reference/tv_reseau_chaleur');
        for ($y = $year; $y >= 2022; $y--) {
            if (isset($table[$y][$reseauId])) {
                return (float)$table[$y][$reseauId];
            }
        }
        return null;
    }

    private function firstGenerator(DOMElement $install, string $generatorTag): ?DOMElement
    {
        foreach ($install->getElementsByTagName($generatorTag) as $gen) {
            if ($gen instanceof DOMElement) {
                return $gen;
            }
        }
        return null;
    }

    private function firstGeneratorEnergyType(
        NodeAccessor $accessor,
        DOMElement $install,
        string $generatorTag,
    ): int {
        foreach ($install->getElementsByTagName($generatorTag) as $gen) {
            if (!$gen instanceof DOMElement) {
                continue;
            }
            $id = $accessor->getIntOrNull('./donnee_entree/enum_type_energie_id', $gen);
            if ($id !== null) {
                return $id;
            }
        }
        return 2; // default: gaz
    }

    private function classeGes(int $ges5m2): string
    {
        foreach (self::GES_THRESHOLDS as $classe => $threshold) {
            if ($ges5m2 <= $threshold) {
                return $classe;
            }
        }
        return 'G';
    }

    private function worstClasse(string $classeA, string $classeB): string
    {
        $order = self::CLASSE_ORDER;
        $posA = array_search($classeA, $order, true);
        $posB = array_search($classeB, $order, true);
        $posA = $posA === false ? count($order) - 1 : $posA;
        $posB = $posB === false ? count($order) - 1 : $posB;
        return $order[max($posA, $posB)];
    }

    private function computeRdimChauffage(
        NodeAccessor $accessor,
        DOMElement $install,
        float $nbreAppt,
        float $sumEchantillon,
    ): float {
        $methode     = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $install) ?? 1;
        $typeInstall = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id',    $install) ?? 1;
        $rdim        = $accessor->getFloatOrNull('./donnee_entree/rdim',                       $install) ?? 1.0;
        $ratioVirt   = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',       $install) ?? 1.0;

        if ($methode === 1) {
            $rdimEff = $rdim;
        } elseif ($typeInstall === 1) {
            $rdimEff = $nbreAppt * $ratioVirt / $sumEchantillon;
        } else {
            $rdimEff = $rdim;
        }

        return max(1e-9, $rdimEff);
    }

    private function computeRdimEcs(
        NodeAccessor $accessor,
        DOMElement $install,
        float $nbreAppt,
        float $sumLogement,
    ): float {
        $methode     = $accessor->getIntOrNull('./donnee_entree/enum_methode_calcul_conso_id', $install) ?? 1;
        $typeInstall = $accessor->getIntOrNull('./donnee_entree/enum_type_installation_id',    $install) ?? 1;
        $rdim        = $accessor->getFloatOrNull('./donnee_entree/rdim',                       $install) ?? 1.0;
        $ratioVirt   = $accessor->getFloatOrNull('./donnee_entree/ratio_virtualisation',       $install) ?? 1.0;

        if ($methode === 1) {
            $rdimEff = $rdim;
        } elseif ($typeInstall === 1) {
            $rdimEff = $nbreAppt * $ratioVirt / $sumLogement;
        } else {
            $rdimEff = $rdim;
        }

        return max(1e-9, $rdimEff);
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
