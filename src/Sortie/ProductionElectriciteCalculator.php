<?php

declare(strict_types=1);

namespace CalculDpe\Sortie;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\ProductionElec\ProductionPvCalculator;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc <sortie><production_electricite>.
 *
 * Agrège les résultats de `ProductionPvCalculator` (stockés dans chaque
 * `production_elec_enr/donnee_intermediaire`) et ventile l'électricité
 * autoconsommée par usage.
 *
 * Quand il n'y a pas de panneaux PV (cas le plus fréquent), toutes les
 * valeurs valent 0.
 *
 * @spec-section 16.2
 * @spec-pages   103-105
 * @spec-source  resources/specsplitted/16-eclairage-prod-elec/02-prod-electricite.md
 * @xml-input    production_elec_enr.donnee_intermediaire.{production_pv, conso_elec_ac},
 *               sortie.ef_conso.*
 * @xml-output   sortie.production_electricite.{production_pv, conso_elec_ac,
 *                   conso_elec_ac_ch, conso_elec_ac_ecs, conso_elec_ac_fr,
 *                   conso_elec_ac_eclairage, conso_elec_ac_auxiliaire, conso_elec_ac_autre_usage}
 * @depends-on   \CalculDpe\ProductionElec\ProductionPvCalculator
 * @tables       (aucune)
 */
final class ProductionElectriciteCalculator implements CalculatorInterface
{
    // §16.2 — taux d'autoproduction par poste (taplpi)
    private const TAPLPI = [
        'chauffage'   => 0.02,
        'ecs'         => 0.05,
        'froid'       => 0.25,
        'eclairage'   => 0.05,
        'ventilation' => 0.50,
        'distribution'=> 0.05,
        'autres'      => 0.45,
    ];

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [ProductionPvCalculator::class];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        // ── 1. Agréger production_pv et conso_elec_ac depuis tous les ENR ──────
        $productionPv = 0.0;
        $consoElecAc  = 0.0;
        foreach ($node->getElementsByTagName('production_elec_enr') as $enr) {
            $productionPv += $accessor->getFloatOrNull('./donnee_intermediaire/production_pv', $enr) ?? 0.0;
            $consoElecAc  += $accessor->getFloatOrNull('./donnee_intermediaire/conso_elec_ac', $enr) ?? 0.0;
        }

        // ── 2. Ventilation par usage (si PV présent) ────────────────────────────
        $consoAcCh    = 0.0;
        $consoAcEcs   = 0.0;
        $consoAcFr    = 0.0;
        $consoAcEcl   = 0.0;
        $consoAcAux   = 0.0;
        $consoAcAutre = 0.0;

        if ($consoElecAc > 0.0) {
            $sortie = $accessor->ensureSortie($node);
            $ef     = null;
            foreach ($sortie->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'ef_conso') {
                    $ef = $child;
                    break;
                }
            }
            if ($ef !== null) {
                $consoChEf        = $accessor->getFloatOrNull('./conso_ch',                      $ef) ?? 0.0;
                $consoEcsEf       = $accessor->getFloatOrNull('./conso_ecs',                     $ef) ?? 0.0;
                $consoFrEf        = $accessor->getFloatOrNull('./conso_fr',                      $ef) ?? 0.0;
                $consoEclEf       = $accessor->getFloatOrNull('./conso_eclairage',               $ef) ?? 0.0;
                $consoVentEf      = $accessor->getFloatOrNull('./conso_auxiliaire_ventilation',  $ef) ?? 0.0;
                $consoAuxDistChEf = $accessor->getFloatOrNull('./conso_auxiliaire_distribution_ch', $ef) ?? 0.0;
                $consoAuxDistEcsEf= $accessor->getFloatOrNull('./conso_auxiliaire_distribution_ecs', $ef) ?? 0.0;

                $total = $consoChEf + $consoEcsEf + $consoFrEf + $consoEclEf
                       + $consoVentEf + $consoAuxDistChEf + $consoAuxDistEcsEf;
                if ($total > 0.0) {
                    $consoAcCh  = $consoElecAc * (self::TAPLPI['chauffage']  * $consoChEf  / $total);
                    $consoAcEcs = $consoElecAc * (self::TAPLPI['ecs']        * $consoEcsEf / $total);
                    $consoAcFr  = $consoElecAc * (self::TAPLPI['froid']      * $consoFrEf  / $total);
                    $consoAcEcl = $consoElecAc * (self::TAPLPI['eclairage']  * $consoEclEf / $total);
                    $consoAcAux = $consoElecAc * (
                        self::TAPLPI['ventilation']   * $consoVentEf                              / $total
                        + self::TAPLPI['distribution'] * ($consoAuxDistChEf + $consoAuxDistEcsEf) / $total
                    );
                }
            }
        }

        // ── 3. Écrire le bloc <production_electricite> ──────────────────────────
        $sortie = $accessor->ensureSortie($node);
        $prod   = $context->document->createElement('production_electricite');
        $sortie->appendChild($prod);

        $accessor->setChildValue($prod, 'production_pv',           $productionPv);
        $accessor->setChildValue($prod, 'conso_elec_ac',           $consoElecAc);
        $accessor->setChildValue($prod, 'conso_elec_ac_ch',        $consoAcCh);
        $accessor->setChildValue($prod, 'conso_elec_ac_ecs',       $consoAcEcs);
        $accessor->setChildValue($prod, 'conso_elec_ac_fr',        $consoAcFr);
        $accessor->setChildValue($prod, 'conso_elec_ac_eclairage', $consoAcEcl);
        $accessor->setChildValue($prod, 'conso_elec_ac_auxiliaire',$consoAcAux);
        $accessor->setChildValue($prod, 'conso_elec_ac_autre_usage',$consoAcAutre);
    }
}
