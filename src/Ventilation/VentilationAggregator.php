<?php

declare(strict_types=1);

namespace CalculDpePHP\Ventilation;

use CalculDpePHP\Collectif\GeneratedApartment;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Agrégat ventilation : recopie hvent, hperm et conso_auxiliaire_ventilation
 * depuis `ventilation/donnee_intermediaire` vers `logement/sortie`.
 *
 * - hvent   → logement/sortie/deperdition/hvent
 * - hperm   → logement/sortie/deperdition/hperm
 * - conso_auxiliaire_ventilation → logement/sortie/ef_conso/conso_auxiliaire_ventilation
 *
 * @spec-section 4-5
 * @spec-pages 38-42
 * @spec-source resources/specsplitted/04-renouvellement-air/00-calcul.md
 * @xml-input  ventilation.donnee_intermediaire.{hvent, hperm, conso_auxiliaire_ventilation}
 * @xml-output logement.sortie.deperdition.{hvent, hperm}
 * @xml-output logement.sortie.ef_conso.conso_auxiliaire_ventilation
 * @depends-on \CalculDpePHP\Ventilation\HventCalculator,
 *             \CalculDpePHP\Ventilation\HpermCalculator,
 *             \CalculDpePHP\Ventilation\ConsoAuxiliaireVentilationCalculator
 * @tables (aucune)
 */
final class VentilationAggregator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            HventCalculator::class,
            HpermCalculator::class,
            ConsoAuxiliaireVentilationCalculator::class,
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $hvent        = 0.0;
        $hperm        = 0.0;
        $caux         = 0.0;
        $surfaceVentile = null;

        foreach ($node->getElementsByTagName('ventilation') as $vent) {
            if (!$vent instanceof DOMElement) {
                continue;
            }
            $di = null;
            $de = null;
            foreach ($vent->childNodes as $child) {
                if (!$child instanceof DOMElement) {
                    continue;
                }
                if ($child->nodeName === 'donnee_intermediaire') {
                    $di = $child;
                } elseif ($child->nodeName === 'donnee_entree') {
                    $de = $child;
                }
            }
            if ($di === null) {
                continue;
            }
            $hvent += $accessor->getFloatOrNull('./hvent', $di) ?? 0.0;
            $hperm += $accessor->getFloatOrNull('./hperm', $di) ?? 0.0;
            // La copie DI de conso_auxiliaire_ventilation est arrondie à l'entier
            // (convention LICIEL) : recalcul pleine précision depuis pvent_moy.
            $pventMoy = $accessor->getFloatOrNull('./pvent_moy', $di);
            $caux  += $pventMoy !== null
                ? 8760.0 * $pventMoy / 1000.0
                : ($accessor->getFloatOrNull('./conso_auxiliaire_ventilation', $di) ?? 0.0);
            if ($de !== null && $surfaceVentile === null) {
                $surfaceVentile = $accessor->getFloatOrNull('./surface_ventile', $de);
            }
        }

        // conso_auxiliaire_ventilation in sortie is prorated by logement share when
        // surface_habitable_logement (zone DPE) < surface_ventile (whole building system).
        $shLogement = $accessor->getFloatOrNull(
            './caracteristique_generale/surface_habitable_logement',
            $node
        );
        if ($shLogement !== null && $surfaceVentile !== null && $surfaceVentile > 0.0) {
            $surfaceCalcul = GeneratedApartment::isGenerated($node, $accessor)
                ? ($accessor->getFloatOrNull('./caracteristique_generale/surface_habitable_immeuble', $node)
                    ?? $surfaceVentile)
                : $surfaceVentile;
            $caux = $caux * $shLogement / $surfaceCalcul;
        }

        $sortie      = $accessor->ensureSortie($node);
        $deperdition = $this->ensureChild($context, $sortie, 'deperdition');
        $efConso     = $this->ensureChild($context, $sortie, 'ef_conso');

        // `hvent` est publié tel quel, quel que soit le mode d'application.
        //
        // Un zéro y était écrit pour la méthode 26, au titre d'une convention
        // d'éditeur. C'en est bien une — cinq des six références de ce mode
        // publient `hvent = 0,000` avec un `deperdition_renouvellement_air`
        // très supérieur à leur `hperm` (2 861,935 contre 818,943), donc elles
        // se contredisent : les déperditions par renouvellement d'air sont la
        // somme des deux termes. Mais la sixième, `2501E0014261S`, est
        // cohérente — 202,2966 + 76,8765 = 279,1731 — et c'est elle que la
        // convention nous faisait rater. Imiter le défaut d'une référence
        // n'est pas le reproduire une fois sur six : c'est le reproduire tout
        // le temps, y compris là où elle a raison.
        $accessor->setChildValue($deperdition, 'hvent', $hvent);
        $accessor->setChildValue($deperdition, 'hperm', $hperm);
        $accessor->setChildValue($efConso, 'conso_auxiliaire_ventilation', $caux);

        // Stocke dans le contexte pour FCalculator (GV inclut Hvent + Hperm)
        $context->set('ventilation.hvent', $hvent);
        $context->set('ventilation.hperm', $hperm);
    }

    private function ensureChild(CalculationContext $context, DOMElement $parent, string $tag): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $tag) {
                return $c;
            }
        }
        $el = $context->document->createElement($tag);
        $parent->appendChild($el);
        return $el;
    }
}
