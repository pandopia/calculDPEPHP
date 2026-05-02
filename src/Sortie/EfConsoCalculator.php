<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc <sortie><ef_conso> : énergie finale par usage et 5 usages (§9-16).
 *
 * Agrégation BAT vs ZONE :
 *   BAT  : ef_conso.conso_ch = Σ(install.conso_ch × rdim)
 *   ZONE : ef_conso.conso_ch = building_total_conso_ch × cle_repartition_ch
 *
 * cle_repartition_ch et cle_repartition_ecs sont des données d'entrée
 * pré-calculées par le logiciel diagnostiqueur (§17.2 p.112-119).
 *
 * @spec-section  9-17
 * @spec-pages    57-119
 * @spec-source   resources/specsplitted/17-collectif/02-appartement.md
 * @xml-input     installation_chauffage.donnee_intermediaire.{conso_ch, conso_ch_depensier}
 *                installation_chauffage.donnee_entree.{rdim, cle_repartition_ch, …}
 *                installation_ecs.generateur_ecs.donnee_intermediaire.{conso_ecs, conso_ecs_depensier}
 *                installation_ecs.donnee_entree.{rdim, cle_repartition_ecs, …}
 *                logement.sortie.ef_conso.conso_auxiliaire_ventilation
 * @xml-output    logement.sortie.ef_conso.{conso_ch, conso_ecs, conso_eclairage, conso_fr,
 *                    conso_auxiliaire_*, conso_totale_auxiliaire, conso_5_usages, conso_5_usages_m2}
 * @depends-on    \CalculDpePHP\Chauffage\Strategy\InstallationClassique
 *                \CalculDpePHP\Ecs\ConsoEcsCalculator
 *                \CalculDpePHP\Froid\ConsoFroidCalculator
 *                \CalculDpePHP\Eclairage\ConsoEclairageCalculator
 *                \CalculDpePHP\Ventilation\VentilationAggregator
 *                \CalculDpePHP\Auxiliaire\AuxDistributionCalculator
 * @tables        (aucune)
 */
final class EfConsoCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Chauffage\Strategy\InstallationClassique',
            '\CalculDpePHP\Ecs\ConsoEcsCalculator',
            '\CalculDpePHP\Froid\ConsoFroidCalculator',
            '\CalculDpePHP\Eclairage\ConsoEclairageCalculator',
            '\CalculDpePHP\Ventilation\VentilationAggregator',
            '\CalculDpePHP\Auxiliaire\AuxDistributionCalculator',
            '\CalculDpePHP\Auxiliaire\AuxGenerationCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Paramètres du bâtiment ─────────────────────────────────────────
        $shImmeuble = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node) ?? 0.0;
        $shLogement = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node);
        $isZone     = $shLogement !== null;
        $nbreAppt   = $accessor->getFloatOrNull('./caracteristique_generale/nombre_appartement', $node) ?? 1.0;
        $surface    = $isZone ? (float)$shLogement : $shImmeuble;

        // ── 2. Chauffage ──────────────────────────────────────────────────────
        [$buildingConsoChTotal, $buildingConsoChDepTotal, $cleRepartitionCh] =
            $this->aggregateChauffage($accessor, $node, $nbreAppt);

        $consoChEf    = $isZone ? $buildingConsoChTotal    * $cleRepartitionCh : $buildingConsoChTotal;
        $consoChDepEf = $isZone ? $buildingConsoChDepTotal * $cleRepartitionCh : $buildingConsoChDepTotal;

        // ── 3. ECS ────────────────────────────────────────────────────────────
        [$buildingConsoEcsTotal, $buildingConsoEcsDepTotal, $cleRepartitionEcs] =
            $this->aggregateEcs($accessor, $node, $nbreAppt);

        $consoEcsEf    = $isZone ? $buildingConsoEcsTotal    * $cleRepartitionEcs : $buildingConsoEcsTotal;
        $consoEcsDepEf = $isZone ? $buildingConsoEcsDepTotal * $cleRepartitionEcs : $buildingConsoEcsDepTotal;

        // ── 4. Froid (déjà proratisé par ConsoFroidCalculator) ───────────────
        $consoFr    = (float)$context->get('froid.conso_fr',           0.0);
        $consoFrDep = (float)$context->get('froid.conso_fr_depensier', 0.0);

        // ── 5. Éclairage (déjà proratisé par ConsoEclairageCalculator) ───────
        $consoEcl = (float)$context->get('eclairage.conso_eclairage', 0.0);

        // ── 6. Auxiliaires existants dans le DOM (VentilationAggregator + futures implémentations) ──
        $sortie  = $accessor->ensureSortie($node);
        $efConso = $this->ensureChild($context->document, $sortie, 'ef_conso');

        $cauxGenCh      = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ch',                 $efConso) ?? 0.0;
        $cauxGenChDep   = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ch_depensier',        $efConso) ?? 0.0;
        $cauxDistCh     = $accessor->getFloatOrNull('./conso_auxiliaire_distribution_ch',               $efConso) ?? 0.0;
        $cauxGenEcs     = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ecs',                $efConso) ?? 0.0;
        $cauxGenEcsDep  = $accessor->getFloatOrNull('./conso_auxiliaire_generation_ecs_depensier',       $efConso) ?? 0.0;
        $cauxDistEcs    = $accessor->getFloatOrNull('./conso_auxiliaire_distribution_ecs',              $efConso) ?? 0.0;
        $cauxVent       = $accessor->getFloatOrNull('./conso_auxiliaire_ventilation',                   $efConso) ?? 0.0;

        $cauxTotal = $cauxGenCh + $cauxDistCh + $cauxGenEcs + $cauxDistEcs + $cauxVent;

        // ── 7. Agrégat 5 usages ───────────────────────────────────────────────
        $conso5   = $consoChEf + $consoEcsEf + $consoFr + $consoEcl + $cauxTotal;
        $conso5m2 = $surface > 0.0 ? (int)floor($conso5 / $surface) : 0;

        // ── 8. Écriture dans sortie/ef_conso ─────────────────────────────────
        $accessor->setChildValue($efConso, 'conso_ch',                                   $consoChEf);
        $accessor->setChildValue($efConso, 'conso_ch_depensier',                         $consoChDepEf);
        $accessor->setChildValue($efConso, 'conso_ecs',                                  $consoEcsEf);
        $accessor->setChildValue($efConso, 'conso_ecs_depensier',                        $consoEcsDepEf);
        $accessor->setChildValue($efConso, 'conso_eclairage',                            $consoEcl);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_generation_ch',             $cauxGenCh);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_generation_ch_depensier',   $cauxGenChDep);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_distribution_ch',           $cauxDistCh);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_generation_ecs',            $cauxGenEcs);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_generation_ecs_depensier',  $cauxGenEcsDep);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_distribution_ecs',          $cauxDistEcs);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_ventilation',               $cauxVent);
        $accessor->setChildValue($efConso, 'conso_totale_auxiliaire',                    $cauxTotal);
        $accessor->setChildValue($efConso, 'conso_fr',                                   $consoFr);
        $accessor->setChildValue($efConso, 'conso_fr_depensier',                         $consoFrDep);
        $accessor->setChildValue($efConso, 'conso_5_usages',                             $conso5);
        $accessor->setChildValue($efConso, 'conso_5_usages_m2',                          $conso5m2);
    }

    /**
     * Agrège les consommations de chauffage de l'immeuble.
     *
     * @return array{float, float, float} [building_total, building_total_dep, cle_repartition_ch]
     */
    private function aggregateChauffage(NodeAccessor $accessor, DOMElement $logement, float $nbreAppt): array
    {
        $collection = $this->getChild($logement, 'installation_chauffage_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        // Σ(nombre_logement_echantillon) pour ZONE individuel
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

        $totalConso    = 0.0;
        $totalConsoDep = 0.0;
        $cleRepartition = 1.0;

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_chauffage') {
                continue;
            }

            $rdimEff = $this->computeRdimChauffage($accessor, $install, $nbreAppt, $sumEchantillon);

            $conso    = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ch',           $install) ?? 0.0;
            $consoDep = $accessor->getFloatOrNull('./donnee_intermediaire/conso_ch_depensier', $install) ?? 0.0;

            $totalConso    += $conso    * $rdimEff;
            $totalConsoDep += $consoDep * $rdimEff;

            $cle = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ch', $install);
            if ($cle !== null && $cle > 0.0) {
                $cleRepartition = $cle;
            }
        }

        return [$totalConso, $totalConsoDep, $cleRepartition];
    }

    /**
     * Agrège les consommations ECS de l'immeuble.
     *
     * @return array{float, float, float} [building_total, building_total_dep, cle_repartition_ecs]
     */
    private function aggregateEcs(NodeAccessor $accessor, DOMElement $logement, float $nbreAppt): array
    {
        $collection = $this->getChild($logement, 'installation_ecs_collection');
        if ($collection === null) {
            return [0.0, 0.0, 1.0];
        }

        // Σ(nombre_logement) pour ZONE individuel ECS
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

        $totalConso    = 0.0;
        $totalConsoDep = 0.0;
        $cleRepartition = 1.0;

        foreach ($collection->childNodes as $install) {
            if (!$install instanceof DOMElement || $install->nodeName !== 'installation_ecs') {
                continue;
            }

            $rdimEff = $this->computeRdimEcs($accessor, $install, $nbreAppt, $sumLogement);

            $installConso    = 0.0;
            $installConsoDep = 0.0;
            foreach ($install->getElementsByTagName('generateur_ecs') as $gen) {
                if (!$gen instanceof DOMElement) {
                    continue;
                }
                $installConso    += $accessor->getFloatOrNull('./donnee_intermediaire/conso_ecs',           $gen) ?? 0.0;
                $installConsoDep += $accessor->getFloatOrNull('./donnee_intermediaire/conso_ecs_depensier', $gen) ?? 0.0;
            }

            $totalConso    += $installConso    * $rdimEff;
            $totalConsoDep += $installConsoDep * $rdimEff;

            $cle = $accessor->getFloatOrNull('./donnee_entree/cle_repartition_ecs', $install);
            if ($cle !== null && $cle > 0.0) {
                $cleRepartition = $cle;
            }
        }

        return [$totalConso, $totalConsoDep, $cleRepartition];
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
