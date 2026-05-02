<?php

declare(strict_types=1);

namespace CalculDpePHP\Engine;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Pipeline d'exécution des Calculators.
 *
 * - Réalise un tri topologique sur les dépendances déclarées.
 * - Détecte les cycles.
 * - Exécute chaque Calculator sur tous les nœuds DOM auxquels il s'applique.
 *
 * Le pipeline est volontairement simple : il itère sur le DOM linéairement.
 * L'ordre topologique des Calculators garantit que les dépendances
 * intermédiaires sont déjà calculées au moment où un Calculator les lit.
 */
final class CalculatorPipeline
{
    /** @var list<CalculatorInterface> */
    private array $calculators = [];

    /** @var list<CalculatorInterface>|null */
    private ?array $sorted = null;

    public function add(CalculatorInterface $calculator): void
    {
        $this->calculators[] = $calculator;
        $this->sorted = null;
    }

    public function run(DOMDocument $document, CalculationContext $context): void
    {
        $sorted = $this->topologicalSort();

        $xpath = new DOMXPath($document);
        $allElements = $xpath->query('//*');
        if ($allElements === false) {
            throw new RuntimeException('XPath query failed.');
        }

        foreach ($sorted as $calculator) {
            foreach ($allElements as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                if ($calculator->appliesTo($node)) {
                    $calculator->calculate($node, $context);
                }
            }
        }
    }

    /**
     * Tri topologique de Kahn. Détecte les cycles.
     *
     * @return list<CalculatorInterface>
     */
    private function topologicalSort(): array
    {
        if ($this->sorted !== null) {
            return $this->sorted;
        }

        /** @var array<string, CalculatorInterface> $byId */
        $byId = [];
        foreach ($this->calculators as $c) {
            $byId[ltrim($c->id(), '\\')] = $c;
        }

        /** @var array<string, list<string>> $deps */
        $deps = [];
        /** @var array<string, int> $inDegree */
        $inDegree = [];
        foreach ($byId as $id => $c) {
            $deps[$id] = array_map(static fn(string $d) => ltrim($d, '\\'), $c->dependencies());
            $inDegree[$id] = 0;
        }
        foreach ($byId as $id => $c) {
            foreach ($deps[$id] as $dep) {
                if (!isset($byId[$dep])) {
                    // Dépendance non enregistrée : on tolère (utile pendant le développement)
                    continue;
                }
                $inDegree[$id]++;
            }
        }

        $queue = [];
        foreach ($inDegree as $id => $deg) {
            if ($deg === 0) {
                $queue[] = $id;
            }
        }

        $result = [];
        while (!empty($queue)) {
            $id = array_shift($queue);
            $result[] = $byId[$id];
            foreach ($byId as $otherId => $other) {
                if (in_array($id, $deps[$otherId], true)) {
                    if (--$inDegree[$otherId] === 0) {
                        $queue[] = $otherId;
                    }
                }
            }
        }

        if (count($result) !== count($byId)) {
            throw new RuntimeException(
                'Cycle détecté dans les dépendances de Calculators ou dépendance manquante.'
            );
        }

        return $this->sorted = $result;
    }
}
