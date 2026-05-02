<?php

declare(strict_types=1);

namespace CalculDpePHP\Enveloppe;

use CalculDpePHP\Engine\CalculationContext;
use CalculDpePHP\Engine\CalculatorInterface;
use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;
use DOMXPath;

/**
 * Agrégat enveloppe : calcule les déperditions par type de paroi et alimente
 * le bloc `<sortie><deperdition>`.
 *
 * Formules :
 *   DPmur          = Σ surface_paroi_opaque × b × umur
 *   DPplancher_bas = Σ surface_paroi_opaque × b × upb_final
 *   DPplancher_haut= Σ surface_paroi_opaque × b × uph
 *   DPbaie_vitree  = Σ surface_totale_baie  × b × u_menuiserie
 *   DPporte        = Σ surface_porte        × b × uporte
 *   DPpont_thermique = Σ l × k
 *
 * Le total `deperdition_enveloppe` et `deperdition_renouvellement_air` (Hvent + Hperm)
 * ne sont **pas** écrits ici car ils dépendent de la ventilation (phase C). Ils seront
 * complétés par `Sortie\DeperditionCalculator` (TASK-F01).
 *
 * @spec-section 3
 * @spec-pages 7
 * @spec-source resources/specsplitted/03-enveloppe-deperditions/00-overview-GV.md
 * @xml-input  enveloppe.*.donnee_intermediaire.{b, umur, upb_final, uph, u_menuiserie, uporte, k}
 *             enveloppe.*.donnee_entree.{surface_paroi_opaque, surface_totale_baie, surface_porte, l}
 * @xml-output logement.sortie.deperdition.{deperdition_mur, deperdition_plancher_bas, deperdition_plancher_haut, deperdition_baie_vitree, deperdition_porte, deperdition_pont_thermique}
 * @depends-on \CalculDpePHP\Enveloppe\Mur\UmurCalculator,
 *             \CalculDpePHP\Enveloppe\PlancherBas\UpbFinalCalculator,
 *             \CalculDpePHP\Enveloppe\PlancherHaut\UphCalculator,
 *             \CalculDpePHP\Enveloppe\BaieVitree\UMenuiserieCalculator,
 *             \CalculDpePHP\Enveloppe\Porte\UporteCalculator,
 *             \CalculDpePHP\Enveloppe\PontThermique\KCalculator
 */
final class EnveloppeAggregator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [
            \CalculDpePHP\Enveloppe\Mur\UmurCalculator::class,
            \CalculDpePHP\Enveloppe\PlancherBas\UpbFinalCalculator::class,
            \CalculDpePHP\Enveloppe\PlancherHaut\UphCalculator::class,
            \CalculDpePHP\Enveloppe\BaieVitree\UMenuiserieCalculator::class,
            \CalculDpePHP\Enveloppe\Porte\UporteCalculator::class,
            \CalculDpePHP\Enveloppe\PontThermique\KCalculator::class,
        ];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'logement';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $xpath    = new DOMXPath($context->document);

        $dpMur = $this->sumProductSurfaceBU($xpath, $node, 'mur', 'umur', 'surface_paroi_opaque');
        $dpPB  = $this->sumProductSurfaceBU($xpath, $node, 'plancher_bas', 'upb_final', 'surface_paroi_opaque');
        $dpPH  = $this->sumProductSurfaceBU($xpath, $node, 'plancher_haut', 'uph', 'surface_paroi_opaque');
        $dpBV  = $this->sumProductSurfaceBU($xpath, $node, 'baie_vitree', 'u_menuiserie', 'surface_totale_baie');
        $dpPorte = $this->sumProductSurfaceBU($xpath, $node, 'porte', 'uporte', 'surface_porte');
        $dpPT  = $this->sumProductLK($xpath, $node);

        $sortie = $accessor->ensureSortie($node);
        $deperdition = $this->ensureChildElement($context, $sortie, 'deperdition');

        $accessor->setChildValue($deperdition, 'deperdition_mur',            $dpMur);
        $accessor->setChildValue($deperdition, 'deperdition_plancher_bas',   $dpPB);
        $accessor->setChildValue($deperdition, 'deperdition_plancher_haut',  $dpPH);
        $accessor->setChildValue($deperdition, 'deperdition_baie_vitree',    $dpBV);
        $accessor->setChildValue($deperdition, 'deperdition_porte',          $dpPorte);
        $accessor->setChildValue($deperdition, 'deperdition_pont_thermique', $dpPT);

        // Stocke les sommes partielles dans le contexte pour les phases ultérieures (Sortie/DeperditionCalculator).
        $context->set('enveloppe.dp_parois', $dpMur + $dpPB + $dpPH + $dpBV + $dpPorte);
        $context->set('enveloppe.dp_pont_thermique', $dpPT);
    }

    /**
     * Σ surface × b × U pour tous les éléments d'un type donné dans le logement.
     */
    private function sumProductSurfaceBU(DOMXPath $xpath, DOMElement $logement, string $tag, string $uTag, string $surfaceTag): float
    {
        $sum = 0.0;
        $nodes = $xpath->query(".//$tag", $logement);
        if ($nodes === false) return 0.0;
        foreach ($nodes as $n) {
            if (!$n instanceof DOMElement) continue;

            $b = $this->readFloatChild($n, 'donnee_intermediaire', 'b');
            $u = $this->readFloatChild($n, 'donnee_intermediaire', $uTag);
            $surface = $this->readFloatChild($n, 'donnee_entree', $surfaceTag);
            if ($b === null || $u === null || $surface === null) continue;

            $sum += $surface * $b * $u;
        }
        return $sum;
    }

    private function sumProductLK(DOMXPath $xpath, DOMElement $logement): float
    {
        $sum = 0.0;
        $nodes = $xpath->query('.//pont_thermique', $logement);
        if ($nodes === false) return 0.0;
        foreach ($nodes as $n) {
            if (!$n instanceof DOMElement) continue;
            $l   = $this->readFloatChild($n, 'donnee_entree', 'l');
            $k   = $this->readFloatChild($n, 'donnee_intermediaire', 'k');
            $pct = $this->readFloatChild($n, 'donnee_entree', 'pourcentage_valeur_pont_thermique') ?? 1.0;
            if ($l === null || $k === null) continue;
            $sum += $l * $k * $pct;
        }
        return $sum;
    }

    private function readFloatChild(DOMElement $node, string $container, string $tag): ?float
    {
        foreach ($node->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $container) {
                foreach ($c->childNodes as $g) {
                    if ($g instanceof DOMElement && $g->nodeName === $tag) {
                        $v = trim($g->textContent ?? '');
                        if ($v === '') return null;
                        $v = str_replace(',', '.', $v);
                        return is_numeric($v) ? (float)$v : null;
                    }
                }
            }
        }
        return null;
    }

    private function ensureChildElement(CalculationContext $context, DOMElement $parent, string $tag): DOMElement
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === $tag) return $c;
        }
        $el = $context->document->createElement($tag);
        $parent->appendChild($el);
        return $el;
    }
}
