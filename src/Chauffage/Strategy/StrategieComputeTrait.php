<?php

declare(strict_types=1);

namespace CalculDpe\Chauffage\Strategy;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;

/**
 * Logique partagée entre toutes les stratégies de chauffage (§9.x).
 *
 * Chaque stratégie appelle `computeAndWrite(besoin, node, context)` avec
 * le besoin effectif (fraction de Bch) alloué à cette installation.
 */
trait StrategieComputeTrait
{
    /**
     * Calcule conso_ch pour un besoin donné et l'écrit dans l'installation
     * et dans chaque générateur.
     *
     * @param float $besoin Besoin alloué à cette installation (kWh).
     */
    private function computeAndWrite(float $besoin, float $besoinDep, DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $gv         = (float)$context->get('chauffage.gv', 1.0);
        $shImmeuble = $this->getShImmeuble($accessor, $node);
        $hsp        = $this->getHsp($accessor, $node);

        $g   = ($hsp * $shImmeuble) > 0.0 ? $gv / ($hsp * $shImmeuble) : 1.0;
        $i0  = $this->weightedEmetteurFloat($accessor, $node, 'i0') ?? 1.0;
        $int = $i0 / (1.0 + 0.1 * ($g - 1.0));

        $re = $this->weightedEmetteurFloat($accessor, $node, 'rendement_emission')    ?? 1.0;
        $rd = $this->weightedEmetteurFloat($accessor, $node, 'rendement_distribution') ?? 1.0;
        $rr = $this->weightedEmetteurFloat($accessor, $node, 'rendement_regulation')  ?? 1.0;
        $rg = $this->weightedGenerateurFloat($accessor, $node, 'rendement_generation') ?? 1.0;

        $denom      = max(1e-9, $rg * $re * $rd * $rr);
        $consoCh    = $besoin    * $int / $denom;
        $consoChDep = $besoinDep * $int / $denom;

        $di = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($di, 'besoin_ch',           $besoin);
        $accessor->setChildValue($di, 'besoin_ch_depensier', $besoinDep);
        $accessor->setChildValue($di, 'conso_ch',            $consoCh);
        $accessor->setChildValue($di, 'conso_ch_depensier',  $consoChDep);

        $genCollection = $this->getChildByTag($node, 'generateur_chauffage_collection');
        if ($genCollection !== null) {
            foreach ($genCollection->childNodes as $gen) {
                if (!($gen instanceof DOMElement) || $gen->nodeName !== 'generateur_chauffage') {
                    continue;
                }
                $genDi = $accessor->ensureDonneeIntermediaire($gen);
                $accessor->setChildValue($genDi, 'conso_ch',           $consoCh);
                $accessor->setChildValue($genDi, 'conso_ch_depensier', $consoChDep);
            }
        }
    }

    /**
     * Position de ce nœud dans sa collection parent (1 = premier, 2 = second…).
     */
    private function positionInCollection(DOMElement $node): int
    {
        $pos  = 1;
        $prev = $node->previousElementSibling;
        while ($prev !== null) {
            if ($prev->nodeName === $node->nodeName) {
                $pos++;
            }
            $prev = $prev->previousElementSibling;
        }
        return $pos;
    }

    private function getShImmeuble(NodeAccessor $accessor, DOMElement $installNode): float
    {
        $logement = $installNode->parentNode?->parentNode;
        $sh = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $logement)
            ?? $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $installNode)
            ?? 1.0;
        return max(1.0, $sh);
    }

    private function getHsp(NodeAccessor $accessor, DOMElement $installNode): float
    {
        $logement = $installNode->parentNode?->parentNode;
        return $accessor->getFloatOrNull('./caracteristique_generale/hsp', $logement) ?? 2.5;
    }

    private function weightedEmetteurFloat(NodeAccessor $accessor, DOMElement $installNode, string $field): ?float
    {
        $col = $this->getChildByTag($installNode, 'emetteur_chauffage_collection');
        if ($col === null) {
            return null;
        }
        $totalSurface = 0.0;
        $weightedSum  = 0.0;
        foreach ($col->childNodes as $em) {
            if (!($em instanceof DOMElement) || $em->nodeName !== 'emetteur_chauffage') {
                continue;
            }
            $surface = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $em) ?? 1.0;
            $value   = $accessor->getFloatOrNull("./donnee_intermediaire/{$field}", $em);
            if ($value === null) {
                continue;
            }
            $totalSurface += $surface;
            $weightedSum  += $surface * $value;
        }
        return $totalSurface > 0.0 ? $weightedSum / $totalSurface : null;
    }

    private function weightedGenerateurFloat(NodeAccessor $accessor, DOMElement $installNode, string $field): ?float
    {
        $col = $this->getChildByTag($installNode, 'generateur_chauffage_collection');
        if ($col === null) {
            return null;
        }
        $count = 0;
        $sum   = 0.0;
        foreach ($col->childNodes as $gen) {
            if (!($gen instanceof DOMElement) || $gen->nodeName !== 'generateur_chauffage') {
                continue;
            }
            $value = $accessor->getFloatOrNull("./donnee_intermediaire/{$field}", $gen);
            if ($value === null) {
                continue;
            }
            $sum += $value;
            $count++;
        }
        return $count > 0 ? $sum / $count : null;
    }

    private function getChildByTag(DOMElement $parent, string $tag): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === $tag) {
                return $child;
            }
        }
        return null;
    }
}
