<?php

declare(strict_types=1);

namespace CalculDpePHP\Ventilation;

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
            $caux  += $accessor->getFloatOrNull('./conso_auxiliaire_ventilation', $di) ?? 0.0;
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
            $caux = $caux * $shLogement / $surfaceVentile;
        }

        $sortie      = $accessor->ensureSortie($node);
        $deperdition = $this->ensureChild($context, $sortie, 'deperdition');
        $efConso     = $this->ensureChild($context, $sortie, 'ef_conso');

        // §17.1 : pour les DPE immeuble (methode_id=26), ADEME convention écrit 0 dans
        // sortie/deperdition/hvent (la vraie valeur est dans ventilation/donnee_intermediaire).
        // deperdition_renouvellement_air est calculé correctement par DeperditionCalculator
        // via le contexte ventilation.hvent.
        $methodeId = $accessor->getIntOrNull('./caracteristique_generale/enum_methode_application_dpe_log_id', $node);

        $accessor->setChildValue($deperdition, 'hvent', $methodeId === 26 ? 0.0 : $hvent);
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
