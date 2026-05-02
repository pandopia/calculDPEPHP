<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Bloc <sortie><apport_et_besoin>.
 *
 * Agrège dans le bloc XML de sortie les valeurs calculées par les calculators
 * en amont (apports gratuits, besoins chauffage/ECS/froid, pertes récupérées).
 *
 * @spec-section 2-11
 * @spec-pages   6-74
 * @spec-source  resources/specsplitted/00-meta/02-expression-besoin-chauffage.md
 * @xml-input    context: apport.*, chauffage.*, ecs.*, froid.*, ch.*
 *               DOM: logement.donnee_intermediaire.surface_sud_equivalente
 * @xml-output   sortie.apport_et_besoin.{surface_sud_equivalente, apport_solaire_ch,
 *               apport_interne_ch, apport_solaire_fr, apport_interne_fr,
 *               fraction_apport_gratuit_ch, fraction_apport_gratuit_depensier_ch,
 *               pertes_distribution_ecs_recup, pertes_distribution_ecs_recup_depensier,
 *               pertes_stockage_ecs_recup, pertes_generateur_ch_recup,
 *               pertes_generateur_ch_recup_depensier, nadeq, v40_ecs_journalier,
 *               v40_ecs_journalier_depensier, besoin_ch, besoin_ch_depensier,
 *               besoin_ecs, besoin_ecs_depensier, besoin_fr, besoin_fr_depensier}
 * @depends-on   \CalculDpePHP\Chauffage\BesoinChauffageCalculator,
 *               \CalculDpePHP\Ecs\BesoinEcsCalculator,
 *               \CalculDpePHP\Froid\BesoinAnnuelCalculator,
 *               \CalculDpePHP\Apport\FCalculator
 * @tables       (aucune)
 */
final class ApportEtBesoinCalculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            '\CalculDpePHP\Chauffage\BesoinChauffageCalculator',
            '\CalculDpePHP\Ecs\BesoinEcsCalculator',
            '\CalculDpePHP\Froid\BesoinAnnuelCalculator',
            '\CalculDpePHP\Apport\FCalculator',
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);

        $values = [
            'surface_sud_equivalente'                    => (float)($context->get('apport.sse_annuel') ?? 0.0),
            'apport_solaire_fr'                          => (float)($context->get('apport.apport_solaire_fr')             ?? 0.0),
            'apport_interne_fr'                          => (float)($context->get('apport.apport_interne_fr')             ?? 0.0),
            'apport_solaire_ch'                          => (float)($context->get('apport.apport_solaire_ch')             ?? 0.0),
            'apport_interne_ch'                          => (float)($context->get('apport.apport_interne_ch')             ?? 0.0),
            'fraction_apport_gratuit_ch'                 => (float)($context->get('apport.fraction_ch')                   ?? 0.0),
            'fraction_apport_gratuit_depensier_ch'       => (float)($context->get('apport.fraction_ch_depensier')         ?? 0.0),
            'pertes_distribution_ecs_recup'              => (float)($context->get('ecs.pertes_distribution_recup')        ?? 0.0),
            'pertes_distribution_ecs_recup_depensier'    => (float)($context->get('ecs.pertes_distribution_recup_dep')    ?? 0.0),
            'pertes_stockage_ecs_recup'                  => (float)($context->get('ecs.pertes_stockage_recup')            ?? 0.0),
            'pertes_generateur_ch_recup'                 => (float)($context->get('ch.pertes_generateur_recup')           ?? 0.0),
            'pertes_generateur_ch_recup_depensier'       => (float)($context->get('ch.pertes_generateur_recup_dep')       ?? 0.0),
            'nadeq'                                      => (float)($context->get('ecs.nadeq')                            ?? 0.0),
            'v40_ecs_journalier'                         => (float)($context->get('ecs.v40_journalier')                   ?? 0.0),
            'v40_ecs_journalier_depensier'               => (float)($context->get('ecs.v40_journalier_dep')               ?? 0.0),
            'besoin_ch'                                  => (float)($context->get('chauffage.besoin_ch')                  ?? 0.0),
            'besoin_ch_depensier'                        => (float)($context->get('chauffage.besoin_ch_depensier')        ?? 0.0),
            'besoin_ecs'                                 => (float)($context->get('ecs.besoin_ecs')                       ?? 0.0),
            'besoin_ecs_depensier'                       => (float)($context->get('ecs.besoin_ecs_depensier')             ?? 0.0),
            'besoin_fr'                                  => (float)($context->get('froid.besoin_fr')                      ?? 0.0),
            'besoin_fr_depensier'                        => (float)($context->get('froid.besoin_fr_depensier')            ?? 0.0),
        ];

        $sortie = $accessor->ensureSortie($node);
        $apb    = $this->ensureChild($context, $sortie, 'apport_et_besoin');

        foreach ($values as $tag => $val) {
            $accessor->setChildValue($apb, $tag, $val);
        }
    }

    private function ensureChild(CalculationContext $context, DOMElement $parent, string $tag): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $tag) return $c;
        }
        $el = $context->document->createElement($tag);
        $parent->appendChild($el);
        return $el;
    }
}
