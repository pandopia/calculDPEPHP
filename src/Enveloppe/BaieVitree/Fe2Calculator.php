<?php

declare(strict_types=1);

namespace CalculDpe\Enveloppe\BaieVitree;

use CalculDpe\Engine\CalculationContext;
use CalculDpe\Engine\CalculatorInterface;
use CalculDpe\Xml\NodeAccessor;
use DOMElement;
use RuntimeException;

/**
 * Facteur d'ensoleillement Fe2 (masques lointains : obstacles d'environnement) — §6.2.2.2.
 *
 * Algorithme (identique à open3cl) :
 *   1. fe2 = 1 (pas de masque)
 *   2. Si masque_lointain_non_homogene_collection contient des entrées :
 *      fe2 = max(0, 1 − Σ(omb_i / 100))  où omb_i est lu dans tv_coef_masque_lointain_non_homogene
 *   3. Si tv_coef_masque_lointain_homogene_id présent : ÉCRASE fe2 avec la valeur de la table
 *      (le masque homogène prend toujours la priorité sur le non-homogène)
 *
 * @spec-section 6.2.2.2
 * @spec-pages 49-50
 * @spec-source resources/specsplitted/06-apports-gratuits/02-surface-sud-equivalente/06-masques-lointains.md
 * @xml-input  baie_vitree.donnee_entree.{tv_coef_masque_lointain_homogene_id, masque_lointain_non_homogene_collection}
 * @xml-output baie_vitree.donnee_intermediaire.fe2
 * @tables enveloppe/tv_coef_masque_lointain_homogene, enveloppe/tv_coef_masque_lointain_non_homogene
 */
final class Fe2Calculator implements CalculatorInterface
{
    public function id(): string
    {
        return self::class;
    }

    public function dependencies(): array
    {
        return [];
    }

    public function appliesTo(DOMElement $node): bool
    {
        return $node->nodeName === 'baie_vitree';
    }

    public function calculate(DOMElement $node, CalculationContext $context): void
    {
        $accessor = new NodeAccessor($context->document);
        $entree   = $node->getElementsByTagName('donnee_entree')->item(0);
        if (!$entree instanceof DOMElement) {
            throw new RuntimeException('baie_vitree sans <donnee_entree>.');
        }

        $fe2 = 1.0;

        // Step 2: masques lointains non homogènes (collection of multiple masques)
        $fe2 = $this->computeFromNonHomogene($entree, $context, $fe2);

        // Step 3: masque lointain homogène — overwrites non_homogene if id is present
        $fe2 = $this->computeFromHomogene($entree, $accessor, $context, $fe2);

        $intermediaire = $accessor->ensureDonneeIntermediaire($node);
        $accessor->setChildValue($intermediaire, 'fe2', $fe2);
    }

    private function computeFromNonHomogene(DOMElement $entree, CalculationContext $context, float $fe2): float
    {
        $collection = null;
        foreach ($entree->childNodes as $c) {
            if ($c instanceof DOMElement && $c->nodeName === 'masque_lointain_non_homogene_collection') {
                $collection = $c;
                break;
            }
        }
        if ($collection === null) {
            return $fe2;
        }

        $table    = $context->tables->load('enveloppe/tv_coef_masque_lointain_non_homogene');
        $totalOmb = 0.0;
        $hasEntry = false;

        foreach ($collection->childNodes as $ml) {
            if (!$ml instanceof DOMElement || $ml->nodeName !== 'masque_lointain_non_homogene') {
                continue;
            }
            foreach ($ml->childNodes as $c) {
                if ($c instanceof DOMElement && $c->nodeName === 'tv_coef_masque_lointain_non_homogene_id') {
                    $id = (int)trim($c->textContent ?? '');
                    if (isset($table[$id])) {
                        $totalOmb += (float)$table[$id];
                        $hasEntry  = true;
                    }
                    break;
                }
            }
        }

        return $hasEntry ? max(0.0, 1.0 - $totalOmb / 100.0) : $fe2;
    }

    private function computeFromHomogene(DOMElement $entree, NodeAccessor $accessor, CalculationContext $context, float $fe2): float
    {
        $idHom = $accessor->getIntOrNull('./tv_coef_masque_lointain_homogene_id', $entree);
        if ($idHom === null) {
            return $fe2;
        }

        $table = $context->tables->load('enveloppe/tv_coef_masque_lointain_homogene');
        return isset($table[$idHom]) ? (float)$table[$idHom] : $fe2;
    }
}
