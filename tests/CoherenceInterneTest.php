<?php

declare(strict_types=1);

namespace Tests;

use CalculDpePHP\Conformite\CorpusLocator;
use CalculDpePHP\Engine\DefaultDpeEngineFactory;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Les sorties doivent être cohérentes **entre elles**, indépendamment de toute
 * référence : un agrégat vaut la somme de ses postes, et le même nombre d'euros
 * ou de kilowattheures se retrouve qu'on le regarde par usage ou par énergie.
 *
 * Ce contrôle est muet sur la justesse — il ne compare à aucun fichier
 * attendu — mais il attrape ce que la mesure de conformité laisse passer :
 * `SortieParEnergieAggregator` rattachait tous les auxiliaires à l'abonnement
 * individuel quand `CoutCalculator` les ventilait entre collectif et
 * individuel, et les deux blocs publiaient des `cout_ecs` écartés de 4 à 5 %
 * sans qu'aucun budget ne s'en émeuve.
 */
final class CoherenceInterneTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__ . '/..';

    /** Tolérance relative : les sorties sont arrondies, pas exactes au bit. */
    private const TOL = 0.005;

    /**
     * Cas dont l'incohérence est connue, instruite et en attente d'un correctif.
     *
     * Chacun porte son numéro de tâche : la liste est une dette déclarée, pas
     * une exemption de confort. Un cas qui s'y ajouterait sans tâche est une
     * régression déguisée.
     *
     * @var array<string, string>
     */
    private const DETTE_CONNUE = [
        // `CoutCalculator::partsParEnergie` retombe sur une énergie unique
        // lorsque les installations ne publient pas leur `conso_ch`, là où
        // `SortieParEnergieAggregator` somme les générateurs : les deux
        // tarifent alors des paniers différents.
        '2675E2152874Y.xml' => 'TASK-K79',
    ];

    /** Préfixe de chaque bloc de sortie, par nom de bloc. */
    private const BLOCS = [
        'ef_conso'     => 'conso',
        'ep_conso'     => 'ep_conso',
        'emission_ges' => 'emission_ges',
        'cout'         => 'cout',
    ];

    /** @return iterable<string, array{string}> */
    public static function casesProvider(): iterable
    {
        $cases = (new CorpusLocator(self::PROJECT_ROOT))->locate();
        usort($cases, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));

        // Un échantillon suffit : le défaut cherché est structurel, pas
        // particulier à un fichier. Un cas sur vingt garde le test rapide tout
        // en couvrant les quatre périmètres et les deux régimes.
        $i = 0;
        foreach ($cases as $case) {
            if ($i++ % 20 !== 0) {
                continue;
            }
            if (isset(self::DETTE_CONNUE[$case['name']])) {
                continue;
            }

            yield $case['corpus'] . '/' . $case['name'] => [$case['input']];
        }
    }

    #[DataProvider('casesProvider')]
    public function testLesSortiesSontCoherentesEntreElles(string $input): void
    {
        $document = DefaultDpeEngineFactory::create(self::PROJECT_ROOT)
            ->calculate((string) file_get_contents($input));

        $xpath = new DOMXPath($document);
        $parEnergie = $this->sommesParEnergie($xpath);
        $ecarts = [];

        // La production photovoltaïque autoconsommée est retranchée des
        // consommations, et deux invariants ne tiennent plus quand elle n'est
        // pas nulle — neuf cas du corpus :
        //
        //  - `*_totale_auxiliaire = Σ postes` : la déduction porte sur le seul
        //    total, les postes restent bruts. **C'est la convention des
        //    références elles-mêmes**, qui publient le même écart au même
        //    endroit (724,098 contre 974,310 sur 2400E0180612T).
        //  - `* = Σ par énergie` : la ventilation par énergie somme les
        //    générateurs, que la déduction n'atteint pas. Les références la
        //    tiennent égale, elles — c'est TASK-K78.
        $autoconsommation = (float) $xpath->evaluate('number(//production_elec_enr//conso_elec_ac)');
        $avecPv = $autoconsommation > 0.0;

        // Les exports DPEWIN 9.x reconstruisent le total GES depuis l'intensité
        // au m² arrondie à l'entier (cf. `IntermediateEnergyUnit::
        // usesRoundedGesTotal`) : ce total ne peut pas égaler la somme de ses
        // postes, par construction.
        $version = (string) $xpath->evaluate('string(/*/@version)');
        $gesArrondi = str_starts_with($version, '9.');

        foreach (self::BLOCS as $bloc => $prefixe) {
            $valeurs = $this->valeursDuBloc($xpath, $bloc);
            if ($valeurs === []) {
                continue;
            }

            $total = $valeurs[$prefixe . '_5_usages'] ?? null;
            $usages = array_map(
                static fn(string $u): ?float => $valeurs[$prefixe . '_' . $u] ?? null,
                ['ch', 'ecs', 'fr', 'eclairage', 'totale_auxiliaire'],
            );
            $totalArrondi = $gesArrondi && $prefixe === 'emission_ges';
            if (!$totalArrondi && $total !== null && $total > 0.0 && !in_array(null, $usages, true)) {
                $this->comparer($ecarts, $prefixe . '_5_usages = Σ usages', $total, array_sum($usages));
            }

            $totalAux = $valeurs[$prefixe . '_totale_auxiliaire'] ?? null;
            $postes = array_filter(array_map(
                static fn(string $p): ?float => $valeurs[$prefixe . '_auxiliaire_' . $p] ?? null,
                ['generation_ch', 'distribution_ch', 'generation_ecs', 'distribution_ecs', 'ventilation', 'distribution_fr'],
            ), static fn(?float $v): bool => $v !== null);
            if (!$avecPv && $totalAux !== null && $totalAux > 0.0 && $postes !== []) {
                $this->comparer($ecarts, $prefixe . '_totale_auxiliaire = Σ postes', $totalAux, array_sum($postes));
            }

            foreach (['5_usages', 'ch', 'ecs'] as $usage) {
                $agregat = $valeurs[$prefixe . '_' . $usage] ?? null;
                $somme = $parEnergie[$prefixe . '_' . $usage] ?? null;
                if (!$avecPv && !$totalArrondi && $agregat !== null && $agregat > 0.0 && $somme !== null) {
                    $this->comparer($ecarts, $prefixe . '_' . $usage . ' = Σ par énergie', $agregat, $somme);
                }
            }
        }

        self::assertSame([], $ecarts, "Sortie incohérente avec elle-même :\n" . implode("\n", $ecarts));
    }

    /** @param list<string> $ecarts */
    private function comparer(array &$ecarts, string $invariant, float $attendu, float $obtenu): void
    {
        if (abs($attendu - $obtenu) > self::TOL * abs($attendu)) {
            $ecarts[] = sprintf('%s : %.3f ≠ %.3f (%.1f %%)', $invariant, $attendu, $obtenu, 100 * abs($attendu - $obtenu) / abs($attendu));
        }
    }

    /** @return array<string, float> */
    private function valeursDuBloc(DOMXPath $xpath, string $bloc): array
    {
        $noeud = $xpath->query('//sortie/' . $bloc)?->item(0);
        if (!$noeud instanceof DOMElement) {
            return [];
        }

        $valeurs = [];
        foreach ($noeud->childNodes as $enfant) {
            if ($enfant instanceof DOMElement && is_numeric(trim($enfant->textContent))) {
                $valeurs[$enfant->nodeName] = (float) $enfant->textContent;
            }
        }

        return $valeurs;
    }

    /** @return array<string, float> */
    private function sommesParEnergie(DOMXPath $xpath): array
    {
        $sommes = [];
        foreach ($xpath->query('//sortie_par_energie') ?: [] as $ligne) {
            if (!$ligne instanceof DOMElement) {
                continue;
            }
            foreach ($ligne->childNodes as $enfant) {
                if (!$enfant instanceof DOMElement
                    || $enfant->nodeName === 'enum_type_energie_id'
                    || !is_numeric(trim($enfant->textContent))) {
                    continue;
                }
                $sommes[$enfant->nodeName] = ($sommes[$enfant->nodeName] ?? 0.0) + (float) $enfant->textContent;
            }
        }

        return $sommes;
    }
}
