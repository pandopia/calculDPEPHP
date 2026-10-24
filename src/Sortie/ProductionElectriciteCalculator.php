<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\ProductionElec\ProductionPvCalculator;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc <sortie><production_electricite>.
 *
 * Projette le bilan de `ProductionPvCalculator` et sa répartition par usage.
 * Le bilan doit être repris avant la déduction sur les consommations finales :
 * refaire la pondération sur des consommations déjà nettes change les parts,
 * attribue du PV à un chauffage fossile et oublie les usages mobiliers.
 *
 * Les anciens exports sérialisent ici des kWh EF, malgré la documentation
 * kWh EP du XSD : cette question d'unité reste à qualifier séparément (K97).
 * Le canal `pv.autoconsommation` porte les six parts avant toute déduction.
 *
 * Quand il n'y a pas de panneaux PV (cas le plus fréquent), toutes les
 * valeurs valent 0.
 *
 * @spec-section 16.2
 * @spec-pages   103-106
 * @spec-source  resources/specsplitted/16-eclairage-prod-elec/02-prod-electricite.md
 * @xml-input    production_elec_enr.donnee_intermediaire.{production_pv, conso_elec_ac}
 * @xml-output   sortie.production_electricite.{production_pv, conso_elec_ac,
 *                   conso_elec_ac_ch, conso_elec_ac_ecs, conso_elec_ac_fr,
 *                   conso_elec_ac_eclairage, conso_elec_ac_auxiliaire, conso_elec_ac_autre_usage}
 * @depends-on   \CalculDpePHP\ProductionElec\ProductionPvCalculator
 * @tables       (aucune)
 */
final class ProductionElectriciteCalculator implements CalculatorInterface
{
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

        // §16.2 p.106 : Celec_ac_i = Celec_ac × Taplp_i × Celec_i / Σ(Taplp×Celec).
        // Le calcul amont connaît les seules consommations électriques et les
        // usages hors DPE. Ce canal interne évite une seconde formule divergente.
        $parts = (array)$context->get('pv.autoconsommation', []);
        $consoAcCh    = (float)($parts['ch'] ?? 0.0);
        $consoAcEcs   = (float)($parts['ecs'] ?? 0.0);
        $consoAcFr    = (float)($parts['fr'] ?? 0.0);
        $consoAcEcl   = (float)($parts['eclairage'] ?? 0.0);
        $consoAcAux   = (float)($parts['auxiliaire'] ?? 0.0);
        $consoAcAutre = (float)($parts['autre_usage'] ?? 0.0);

        // ── 3. Écrire le bloc <production_electricite> ──────────────────────────
        $sortie = $accessor->ensureSortie($node);
        $prod = null;
        foreach ($sortie->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'production_electricite') {
                $prod = $child;
                break;
            }
        }
        if ($prod === null) {
            $prod = $context->document->createElement('production_electricite');
            $sortie->appendChild($prod);
        }

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
