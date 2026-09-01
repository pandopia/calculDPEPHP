<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

/**
 * Politique de tolérance appliquée à la comparaison moteur ↔ référence.
 *
 * Le règlement public d'évaluation des logiciels DPE 2021 (CSTB/DHUP, version
 * du 13/12/2022) ne publie **aucun seuil chiffré** : les critères de conformité
 * accompagnent les jeux de cas tests remis aux éditeurs, non diffusés. Les
 * profils ci-dessous sont donc explicites et documentés, et volontairement
 * décorrélés des tolérances de confort de `tests/tolerances.php`.
 *
 * - `strict` : 0,1 % relatif partout. C'est le profil de mesure de conformité :
 *   aucun assouplissement par balise, pour que le rapport ne puisse pas se
 *   flatter en élargissant une tolérance.
 * - `reglementaire` : 1 % relatif, ordre de grandeur usuellement admis entre
 *   deux moteurs implémentant la même méthode.
 * - `repo` : reprend `tests/tolerances.php`, pour comparer le rapport au
 *   harness E2E historique.
 *
 * Un seul assouplissement est intégré à tous les profils : les balises que la
 * référence ADEME stocke **arrondies à l'unité** sont comparées à ±0,5. Ce
 * n'est pas une tolérance de calcul mais la précision réelle de la référence.
 */
final class ToleranceProfile
{
    /**
     * Balises arrondies à l'entier dans le XML de référence : comparer au-delà
     * de ±0,5 n'aurait pas de sens, l'information n'y est pas.
     */
    private const REFERENCE_ROUNDED_TO_UNIT = [
        'conso_5_usages_m2',
        'ep_conso_5_usages_m2',
        'emission_ges_5_usages_m2',
    ];

    /** En deçà, deux flottants sont considérés identiques (bruit IEEE-754). */
    private const ABSOLUTE_EPSILON = 1e-9;

    /**
     * @param array<string, float> $overrides tolérance relative par nom de balise
     */
    private function __construct(
        public readonly string $name,
        private readonly float $defaultTolerance,
        private readonly array $overrides,
    ) {
    }

    public static function strict(): self
    {
        return new self('strict', 1e-3, []);
    }

    public static function reglementaire(): self
    {
        return new self('reglementaire', 1e-2, []);
    }

    public static function repo(string $projectRoot): self
    {
        /** @var array{default: float|string, overrides: array<string, float>} $tol */
        $tol = require $projectRoot . '/tests/tolerances.php';

        return new self('repo', (float) $tol['default'], $tol['overrides']);
    }

    public static function named(string $name, string $projectRoot): self
    {
        return match ($name) {
            'strict' => self::strict(),
            'reglementaire' => self::reglementaire(),
            'repo' => self::repo($projectRoot),
            default => throw new \InvalidArgumentException(sprintf(
                'Profil de tolérance inconnu : %s (attendu : strict, reglementaire, repo)',
                $name,
            )),
        };
    }

    public function toleranceFor(string $tag): float
    {
        return $this->overrides[$tag] ?? $this->defaultTolerance;
    }

    /**
     * Compare deux valeurs numériques.
     *
     * @return array{status: string, delta_abs: float, delta_percent: float|null}
     */
    public function compareNumeric(string $tag, float $expected, float $actual): array
    {
        $deltaAbs = abs($expected - $actual);
        $deltaPercent = ($expected != 0.0) ? ($deltaAbs / abs($expected)) * 100.0 : null;

        if ($deltaAbs <= self::ABSOLUTE_EPSILON) {
            return ['status' => ComparisonStatus::EXACT, 'delta_abs' => $deltaAbs, 'delta_percent' => $deltaPercent];
        }

        if (in_array($tag, self::REFERENCE_ROUNDED_TO_UNIT, true) && $deltaAbs <= 0.5) {
            return ['status' => ComparisonStatus::WITHIN, 'delta_abs' => $deltaAbs, 'delta_percent' => $deltaPercent];
        }

        $tolerance = $this->toleranceFor($tag);
        $relative = ($expected != 0.0) ? $deltaAbs / abs($expected) : $deltaAbs;

        return [
            'status' => $relative <= $tolerance ? ComparisonStatus::WITHIN : ComparisonStatus::OUT,
            'delta_abs' => $deltaAbs,
            'delta_percent' => $deltaPercent,
        ];
    }
}
