<?php

declare(strict_types=1);

namespace CalculDpePHP\Froid;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Consommation annuelle de refroidissement Cfr et Cfr_depensier (§10.3 p.69-70).
 *
 * Formule :
 *   Cfr = 0,9 × Bfr / EER
 *   EER = 0,95 × SEER   (sauf avant 2008 : EER direct)
 *
 * Si seule une partie du logement est refroidie :
 *   Cfr_logement = Cfr × (surface_refroidie / surface_habitable)
 *
 * Si besoin_fr = 0 (pas de climatisation ou pas de besoin), conso = 0.
 *
 * @spec-section 10.3
 * @spec-pages   69-70
 * @spec-source  resources/specsplitted/10-conso-froid/03-consommations.md
 * @xml-input    logement.climatisation_collection.climatisation.donnee_entree.{tv_seer_id, surface_refroidie}
 * @xml-output   logement.sortie.ef_conso.{conso_fr, conso_fr_depensier}
 * @xml-output   logement.sortie.apport_et_besoin.eer
 * @depends-on   \CalculDpePHP\Froid\BesoinAnnuelCalculator
 * @tables       froid/tv_seer
 */
final class ConsoFroidCalculator implements CalculatorInterface
{
    private const INTERMITTENCE_FROID = 0.9;

    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return ['\CalculDpePHP\Froid\BesoinAnnuelCalculator'];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $bfr    = (float)$context->get('froid.besoin_fr',          0.0);
        $bfrDep = (float)$context->get('froid.besoin_fr_depensier', 0.0);

        if ($bfr <= 0.0 && $bfrDep <= 0.0) {
            $this->writeOutputs($accessor, $node, 0.0, 0.0, 0.0);
            return;
        }

        // ── EER depuis tv_seer_id ou SEER direct ──────────────────────────────
        $eer = $this->resolveEer($node, $context, $accessor);

        // ── Surface refroidie vs surface habitable (logement ou immeuble) ─────
        $shLogement     = $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_logement', $node)
            ?? $accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node)
            ?? 0.0;
        $surfaceRatio   = $this->resolveRefroidieRatio($node, $accessor, $shLogement);

        // ── Cfr = 0,9 × Bfr / EER × ratio ────────────────────────────────────
        $cfr    = ($eer > 0.0) ? self::INTERMITTENCE_FROID * $bfr    / $eer * $surfaceRatio : 0.0;
        $cfrDep = ($eer > 0.0) ? self::INTERMITTENCE_FROID * $bfrDep / $eer * $surfaceRatio : 0.0;

        $this->writeOutputs($accessor, $node, $cfr, $cfrDep, $eer);

        $context->set('froid.conso_fr',          $cfr);
        $context->set('froid.conso_fr_depensier', $cfrDep);
        $context->set('froid.eer',               $eer);
    }

    private function resolveEer(DOMElement $node, CalculationContext $context, NodeAccessor $accessor): float
    {
        // Premier: cherche un EER saisi directement dans donnee_entree
        foreach ($node->getElementsByTagName('climatisation') as $clim) {
            if (!$clim instanceof DOMElement) {
                continue;
            }
            $eerSaisi = $accessor->getFloatOrNull('./donnee_entree/eer', $clim);
            if ($eerSaisi !== null && $eerSaisi > 0.0) {
                return $eerSaisi;
            }
        }

        // Sinon: tv_seer_id explicit
        foreach ($node->getElementsByTagName('climatisation') as $clim) {
            if (!$clim instanceof DOMElement) {
                continue;
            }
            $seerId = $accessor->getIntOrNull('./donnee_entree/tv_seer_id', $clim);
            if ($seerId !== null) {
                $tvSeer = $context->tables->load('froid/tv_seer');
                if (isset($tvSeer[$seerId])) {
                    return (float)$tvSeer[$seerId]['eer'];
                }
            }
        }

        // Sinon : déduit du couple (zone climatique × période installation) §10.3.
        // Open3cl tv.js seer : H1/H2 = zones 1-7, H3 = zone 8.
        $zoneId = $context->zoneClimatique !== null ? (int)$context->zoneClimatique : 1;
        $isH3   = $zoneId === 8;
        foreach ($node->getElementsByTagName('climatisation') as $clim) {
            if (!$clim instanceof DOMElement) {
                continue;
            }
            $periodId = $accessor->getIntOrNull('./donnee_entree/enum_periode_installation_fr_id', $clim);
            if ($periodId === null) {
                continue;
            }
            // tv_seer_id : 1-3 (H1/H2 × periodes 1/2/3), 4-6 (H3 × periodes 1/2/3)
            $seerId = $isH3 ? (3 + $periodId) : $periodId;
            $tvSeer = $context->tables->load('froid/tv_seer');
            if (isset($tvSeer[$seerId])) {
                return (float)$tvSeer[$seerId]['eer'];
            }
        }

        // Valeur par défaut (zone H1/H2, installation récente)
        return 0.95 * 6.7;
    }

    private function resolveRefroidieRatio(DOMElement $node, NodeAccessor $accessor, float $shLogement): float
    {
        // Somme des surfaces climatisées sur toutes les installations
        $surfTotal = 0.0;
        foreach ($node->getElementsByTagName('climatisation') as $clim) {
            if (!$clim instanceof DOMElement) {
                continue;
            }
            $surf = $accessor->getFloatOrNull('./donnee_entree/surface_clim', $clim)
                ?? $accessor->getFloatOrNull('./donnee_entree/surface_refroidie', $clim)
                ?? $accessor->getFloatOrNull('./donnee_entree/surface_habitable', $clim)
                ?? 0.0;
            $surfTotal += $surf;
        }
        if ($surfTotal > 0.0 && $shLogement > 0.0) {
            return min(1.0, $surfTotal / $shLogement);
        }
        return 1.0;
    }

    private function writeOutputs(NodeAccessor $accessor, DOMElement $node, float $cfr, float $cfrDep, float $eer): void
    {
        $sortie = $accessor->ensureSortie($node);

        $efConso = $this->ensureChild($node->ownerDocument, $sortie, 'ef_conso');
        $accessor->setChildValue($efConso, 'conso_fr',          $cfr);
        $accessor->setChildValue($efConso, 'conso_fr_depensier', $cfrDep);

        $apportEtBesoin = $this->ensureChild($node->ownerDocument, $sortie, 'apport_et_besoin');
        if ($eer > 0.0) {
            $accessor->setChildValue($apportEtBesoin, 'eer', $eer);
        }

        // Per-climatisation : répartition de conso_fr au prorata de surface_clim
        // (Σ_clim(per_clim) = ef_conso/conso_fr = cfr déjà calculé à l'échelle de la part climatisée)
        $climNodes = [];
        $totalSurfClim = 0.0;
        foreach ($node->getElementsByTagName('climatisation') as $clim) {
            if (!$clim instanceof DOMElement) {
                continue;
            }
            $surf = $accessor->getFloatOrNull('./donnee_entree/surface_clim', $clim)
                ?? $accessor->getFloatOrNull('./donnee_entree/surface_refroidie', $clim)
                ?? 0.0;
            $climNodes[] = [$clim, $surf];
            $totalSurfClim += $surf;
        }
        foreach ($climNodes as [$clim, $surf]) {
            $part = ($totalSurfClim > 0.0) ? ($surf / $totalSurfClim) : (1.0 / max(1, count($climNodes)));
            $di = $accessor->ensureDonneeIntermediaire($clim);
            if ($eer > 0.0) {
                $accessor->setChildValue($di, 'eer', $eer);
            }
            $accessor->setChildValue($di, 'conso_fr',           $cfr    * $part);
            $accessor->setChildValue($di, 'conso_fr_depensier', $cfrDep * $part);
        }
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
}
