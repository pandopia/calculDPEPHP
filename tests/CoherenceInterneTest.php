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
        // Une entrée ici est un bloc de sortie qui se contredit lui-même, pas
        // un simple écart à la référence : elle appelle une TASK, pas une
        // tolérance.
        //
        // Deux installations de chauffage aux clés de répartition distinctes
        // (0,7866 et 0,2134) : `EfConsoCalculator` retient la dernière clé lue
        // et l'applique au total, quand `sortie_par_energie` applique la
        // sienne à chaque installation. Voir TASK-K90.
        '2601E0158771V.xml' => 'TASK-K90',
        '2601E0304014C.xml' => 'TASK-K90',
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

        // Le corpus entier, et non un échantillon : 541 cas coûtent onze
        // secondes, et un défaut de cohérence se niche dans une configuration
        // rare — la cascade de TASK-K80 n'apparaît que sur un fichier.
        foreach ($cases as $case) {
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
        // consommations, et un invariant n'y tient pas : la déduction porte
        // sur le seul `*_totale_auxiliaire`, les postes restant bruts.
        // **C'est la convention des références elles-mêmes**, qui publient le
        // même écart au même endroit — 724,098 contre 974,310 sur
        // 2400E0180612T. Seule cette exemption-là subsiste : la ventilation
        // par énergie, elle, déduit désormais l'autoconsommation du chauffage
        // et de l'ECS comme `ef_conso` le fait.
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
                if (!$totalArrondi && $agregat !== null && $agregat > 0.0 && $somme !== null) {
                    $this->comparer($ecarts, $prefixe . '_' . $usage . ' = Σ par énergie', $agregat, $somme);
                }
            }
        }

        // Les auxiliaires d'une installation ne peuvent pas consommer plus que
        // le chauffage qu'ils servent : un circulateur dimensionné sur le
        // réseau de l'immeuble publié sur un appartement de 35 m² donnait
        // 7 792 kWh pour 1 919 de chauffage. La ventilation seule peut
        // dépasser un chauffage très faible, d'où le seuil.
        $lireEf = static function (string $tag) use ($xpath): ?float {
            $noeuds = $xpath->query('//sortie/ef_conso/' . $tag);
            return $noeuds !== false && $noeuds->length > 0
                ? (float) str_replace(',', '.', (string) $noeuds->item(0)?->textContent)
                : null;
        };
        $consoCh = $lireEf('conso_ch');
        $auxCh = ($lireEf('conso_auxiliaire_generation_ch') ?? 0.0)
            + ($lireEf('conso_auxiliaire_distribution_ch') ?? 0.0);
        if ($consoCh !== null && $consoCh > 0.0 && $auxCh > $consoCh) {
            $ecarts[] = sprintf(
                'auxiliaires de chauffage (%.3f) supérieurs à la consommation de chauffage (%.3f)',
                $auxCh,
                $consoCh,
            );
        }

        // Le bloc des déperditions : son enveloppe est la somme de ses postes,
        // et son renouvellement d'air celle de ses deux termes. C'est ce
        // second invariant qui a révélé que nous écrivions `hvent = 0` pour le
        // mode 26, en imitant une convention d'éditeur.
        $this->deperditionsCoherentes($xpath, $ecarts);

        self::assertSame([], $ecarts, "Sortie incohérente avec elle-même :\n" . implode("\n", $ecarts));
    }

    /** @param list<string> $ecarts */
    private function deperditionsCoherentes(DOMXPath $xpath, array &$ecarts): void
    {
        $lire = static function (string $tag) use ($xpath): ?float {
            $noeuds = $xpath->query('//sortie/deperdition/' . $tag);
            return $noeuds !== false && $noeuds->length > 0
                ? (float) str_replace(',', '.', (string) $noeuds->item(0)?->textContent)
                : null;
        };

        $renouvellement = $lire('deperdition_renouvellement_air');
        $hvent = $lire('hvent');
        $hperm = $lire('hperm');
        if ($renouvellement !== null && $renouvellement > 0.0 && $hvent !== null && $hperm !== null) {
            $this->comparer($ecarts, 'renouvellement_air = hvent + hperm', $renouvellement, $hvent + $hperm);
        }

        $enveloppe = $lire('deperdition_enveloppe');
        $somme = 0.0;
        foreach ([
            'deperdition_mur', 'deperdition_plancher_bas', 'deperdition_plancher_haut',
            'deperdition_baie_vitree', 'deperdition_porte', 'deperdition_pont_thermique',
            'deperdition_renouvellement_air',
        ] as $poste) {
            $valeur = $lire($poste);
            if ($valeur === null) {
                return;
            }
            $somme += $valeur;
        }
        if ($enveloppe !== null && $enveloppe > 0.0) {
            $this->comparer($ecarts, 'deperdition_enveloppe = Σ postes', $enveloppe, $somme);
        }
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
