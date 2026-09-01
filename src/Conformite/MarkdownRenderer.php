<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

/**
 * Rend le rapport de conformité en Markdown lisible en revue de code.
 */
final class MarkdownRenderer
{
    private const TOP_TAGS = 40;
    private const TOP_CASES = 25;

    /** @param array<string, mixed> $report */
    public function render(array $report): string
    {
        $s = $report['summary'];
        $out = [];

        $out[] = '# Conformité du moteur — jeux de tests DPE 3CL';
        $out[] = '';
        $out[] = sprintf('_Généré le %s — profil de tolérance : `%s`_', $report['generated_at'], $report['tolerance_profile']);
        $revision = $report['revision'] ?? [];
        if ($revision !== []) {
            $out[] = sprintf(
                '_Révision mesurée : `%s`%s_',
                $revision['head'] ?? 'inconnue',
                ($revision['dirty'] ?? '') !== '' ? ' — arbre de travail modifié : ' . $revision['dirty'] : '',
            );
        }
        $out[] = '';
        $out[] = 'Comparaison balise à balise de la totalité de `<donnee_intermediaire>` et';
        $out[] = '`<sortie>` entre la sortie du moteur et la référence du cas. Aucune balise';
        $out[] = "n'est exclue : une balise attendue mais non produite compte comme non conforme.";
        $out[] = '';

        // ── Synthèse ──────────────────────────────────────────────────────
        $out[] = '## Synthèse';
        $out[] = '';
        $out[] = '```';
        $out[] = sprintf('Cas                      : %d', $s['cases_total']);
        $out[] = sprintf('Exécutés                 : %d', $s['cases_run']);
        $out[] = sprintf('Crash                    : %d', $s['cases_crashed']);
        $out[] = sprintf('Totalement conformes     : %d', $s['cases_fully_conform']);
        $out[] = sprintf('Partiellement conformes  : %d', $s['cases_partially_conform']);
        $out[] = '';
        $out[] = sprintf('Valeurs comparées        : %s', number_format((float) $s['values_compared'], 0, ',', ' '));
        $out[] = sprintf('Exactes                  : %s', number_format((float) $s[ComparisonStatus::EXACT], 0, ',', ' '));
        $out[] = sprintf('Dans tolérance           : %s', number_format((float) $s[ComparisonStatus::WITHIN], 0, ',', ' '));
        $out[] = sprintf('Hors tolérance           : %s', number_format((float) $s[ComparisonStatus::OUT], 0, ',', ' '));
        $out[] = sprintf('Balises manquantes       : %s', number_format((float) $s[ComparisonStatus::MISSING], 0, ',', ' '));
        $out[] = sprintf('Balises supplémentaires  : %s', number_format((float) $s[ComparisonStatus::EXTRA], 0, ',', ' '));
        $out[] = sprintf('Écarts non numériques    : %s', number_format((float) $s[ComparisonStatus::MISMATCH], 0, ',', ' '));
        $out[] = '';
        $out[] = sprintf('Conformité               : %s %%', $s['conformity_percent'] === null ? 'n/a' : number_format((float) $s['conformity_percent'], 2, ',', ' '));
        $out[] = '```';
        $out[] = '';

        // ── Familles ──────────────────────────────────────────────────────
        $out[] = '## Écarts par famille fonctionnelle';
        $out[] = '';
        $out[] = '| Famille | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Suppl. | Conformité |';
        $out[] = '|---|---:|---:|---:|---:|---:|---:|---:|';
        foreach ($report['by_famille'] as $f) {
            $out[] = sprintf(
                '| %s | %d | %d | %d | %d | %d | %d | %s %% |',
                $f['famille'],
                $f['compared'],
                $f[ComparisonStatus::EXACT],
                $f[ComparisonStatus::WITHIN],
                $f[ComparisonStatus::OUT],
                $f[ComparisonStatus::MISSING],
                $f[ComparisonStatus::EXTRA],
                $f['conformity_percent'] === null ? 'n/a' : number_format((float) $f['conformity_percent'], 2, ',', ' '),
            );
        }
        $out[] = '';

        // ── Périmètres CSTB ───────────────────────────────────────────────
        $out[] = '## Écarts par périmètre d\'évaluation';
        $out[] = '';
        $out[] = 'Périmètres du règlement d\'évaluation CSTB §1.1, déduits de';
        $out[] = '`enum_methode_application_dpe_log_id`.';
        $out[] = '';
        $out[] = $this->bucketTable($report['by_perimetre'], 'Périmètre');
        $out[] = '';

        $out[] = '## Écarts par régime du coefficient EP électricité';
        $out[] = '';
        $out[] = $this->bucketTable($report['by_regime'], 'Régime');
        $out[] = '';

        $out[] = '## Écarts par moteur de calcul de la référence';
        $out[] = '';
        $out[] = 'Un écart concentré sur un seul moteur éditeur signale plutôt une';
        $out[] = 'particularité de ce logiciel qu\'un défaut de notre implémentation.';
        $out[] = '';
        $out[] = $this->bucketTable(array_slice($report['by_moteur_calcul'], 0, 15), 'Moteur');
        $out[] = '';

        // ── Balises fautives ──────────────────────────────────────────────
        $out[] = '## Principales sources d\'écart (par balise)';
        $out[] = '';
        $out[] = '| # | Famille | Balise | Occurrences | Cas touchés | Écart max | Statuts |';
        $out[] = '|---:|---|---|---:|---:|---:|---|';
        $rank = 0;
        foreach (array_slice($report['by_tag'], 0, self::TOP_TAGS) as $t) {
            $rank++;
            $statuses = [];
            foreach ($t['statuses'] as $st => $n) {
                $statuses[] = sprintf('%s×%d', $this->shortStatus($st), $n);
            }
            $out[] = sprintf(
                '| %d | %s | `%s` | %d | %d | %s | %s |',
                $rank,
                $t['famille'],
                $t['tag'],
                $t['count'],
                $t['cases'],
                $t['worst_percent'] > 0 ? number_format($t['worst_percent'], 1, ',', ' ') . ' %' : '—',
                implode(' ', $statuses),
            );
        }
        $out[] = '';

        // ── Conformité structurelle ───────────────────────────────────────
        $out[] = '## Conformité structurelle du XML produit';
        $out[] = '';
        if (($report['unknown_elements'] ?? []) === []) {
            $out[] = 'Aucune balise produite hors du vocabulaire de `resources/ademe_DPE.xsd`.';
        } else {
            $out[] = 'Balises écrites par le moteur mais absentes du schéma ADEME — un fichier';
            $out[] = 'les contenant serait rejeté par l\'observatoire :';
            $out[] = '';
            $out[] = '| Balise | Cas concernés |';
            $out[] = '|---|---:|';
            foreach ($report['unknown_elements'] as $name => $n) {
                $out[] = sprintf('| `%s` | %d |', $name, $n);
            }
        }
        $out[] = '';

        // ── Cas les plus dégradés ─────────────────────────────────────────
        $cases = $report['cases'];
        usort($cases, static fn (array $a, array $b): int => $b['failing'] <=> $a['failing']);

        $out[] = '## Cas les plus dégradés';
        $out[] = '';
        $out[] = '| Cas | Périmètre | Régime | Comparées | Non conformes | Conformité |';
        $out[] = '|---|---|---|---:|---:|---:|';
        foreach (array_slice($cases, 0, self::TOP_CASES) as $c) {
            $out[] = sprintf(
                '| `%s` | %s | %s | %d | %d | %s %% |',
                $c['name'],
                $c['perimetre'],
                $c['regime_coef_ep_elec'],
                $c['values_compared'],
                $c['failing'],
                $c['conformity_percent'] === null ? 'n/a' : number_format((float) $c['conformity_percent'], 2, ',', ' '),
            );
        }
        $out[] = '';

        // ── Crashes ───────────────────────────────────────────────────────
        $crashes = array_values(array_filter($report['cases'], static fn (array $c): bool => $c['status'] !== 'ok'));
        if ($crashes !== []) {
            $out[] = '## Cas en échec d\'exécution';
            $out[] = '';
            $out[] = '| Cas | Statut | Erreur |';
            $out[] = '|---|---|---|';
            foreach ($crashes as $c) {
                $out[] = sprintf('| `%s` | %s | %s |', $c['name'], $c['status'], str_replace('|', '\\|', (string) $c['error']));
            }
            $out[] = '';
        }

        $out[] = '## Cas totalement conformes';
        $out[] = '';
        $conform = array_values(array_filter($report['cases'], static fn (array $c): bool => $c['status'] === 'ok' && $c['failing'] === 0));
        if ($conform === []) {
            $out[] = '_Aucun._';
        } else {
            foreach ($conform as $c) {
                $out[] = sprintf('- `%s` (%s, %s) — %d valeurs', $c['name'], $c['perimetre'], $c['regime_coef_ep_elec'], $c['values_compared']);
            }
        }
        $out[] = '';

        return implode("\n", $out);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function bucketTable(array $rows, string $label): string
    {
        $lines = [];
        $lines[] = sprintf('| %s | Cas | Comparées | Exactes | Tolérance | Hors tol. | Manquantes | Conformité |', $label);
        $lines[] = '|---|---:|---:|---:|---:|---:|---:|---:|';
        foreach ($rows as $r) {
            $lines[] = sprintf(
                '| %s | %d | %d | %d | %d | %d | %d | %s %% |',
                $r['key'],
                $r['cases'],
                $r['compared'],
                $r[ComparisonStatus::EXACT],
                $r[ComparisonStatus::WITHIN],
                $r[ComparisonStatus::OUT],
                $r[ComparisonStatus::MISSING],
                $r['conformity_percent'] === null ? 'n/a' : number_format((float) $r['conformity_percent'], 2, ',', ' '),
            );
        }

        return implode("\n", $lines);
    }

    private function shortStatus(string $status): string
    {
        return match ($status) {
            ComparisonStatus::OUT => 'hors-tol',
            ComparisonStatus::MISSING => 'manquante',
            ComparisonStatus::EXTRA => 'suppl',
            ComparisonStatus::MISMATCH => 'texte',
            default => $status,
        };
    }
}
