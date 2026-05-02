<?php

declare(strict_types=1);

/**
 * Caractéristiques des générateurs à combustion — §13.2.2 p.86-92 (chauffage)
 * et §14.1 p.93-95 (ECS).
 *
 * Chaque entrée est un tableau associatif contenant les formules évaluées :
 *   'pn'    → float W   puissance nominale
 *   'rpn'   → float     rendement pleine charge PCI (fraction 0-1)
 *   'rpint' → float     rendement charge intermédiaire PCI (fraction 0-1)
 *   'qp0'   → float W   pertes à l'arrêt
 *   'pveil' → float W   puissance veilleuse (0 si absence)
 *
 * Les fermetures acceptent ($pn_kw, $e, $f) :
 *   $pn_kw  puissance nominale calculée depuis GV (kW)
 *   $e/$f   facteurs ventouse (E_tab/F_tab dans open3cl)
 *             sans ventouse: E=2.5, F=-0.8
 *             avec ventouse: E=1.75, F=-0.55
 *
 * Formules de référence open3cl tv.js (source de vérité) :
 *   Gaz classique 1991-2000 (ID 4) :
 *     rpn=(84+2×log10(Pn))/100, rpint=(80+3×log10(Pn))/100, qp0=1.2%×Pn, pveil=120
 *   Condensation gaz ≥2016, Pn≤70 (ID 13) :
 *     rpn=(91+3×log10(Pn))/100, rpint=(103+2.5×log10(Pn))/100, qp0=0.5%×Pn
 *   Générateur air chaud avant 2006, Pn≤300 (ID 69) :
 *     rpn=77/100, rpint=74/100, qp0=Pn×(1.75−0.55×log10(Pn))×1000
 *
 * @spec-section 13.2.2
 * @spec-pages   86-92
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/02-valeurs-defaut-gaz-fioul.md
 * @generated-on 2026-04-30
 * @status       partial — IDs 1-24 et 69 implémentés (gaz classique/standard/condensation, fioul, air chaud)
 *               IDs 25-68, 70-93 restent à digitaliser dans TASK-A07.
 */

// ─── Helpers de formules ──────────────────────────────────────────────────────

/** Formule standard gaz classique/standard: rpn=(84+2×log10(Pn))/100 */
$rpn84 = static fn(float $p): float => (84.0 + 2.0 * log10($p)) / 100.0;

/** rpint=(80+3×log10(Pn))/100 */
$rpint80 = static fn(float $p): float => (80.0 + 3.0 * log10($p)) / 100.0;

/** Formule basse température: rpn/rpint=(87.5+1.5×log10(Pn))/100 */
$rpn875 = static fn(float $p): float => (87.5 + 1.5 * log10($p)) / 100.0;

/** Formule condensation gaz 1981-2015: rpn=(91+log10(Pn))/100 */
$rpn91 = static fn(float $p): float => (91.0 + log10($p)) / 100.0;

/** rpint=(97+log10(Pn))/100 */
$rpint97 = static fn(float $p): float => (97.0 + log10($p)) / 100.0;

/** Formule condensation gaz ≥2016 Pn≤70: rpn=(91+3×log10(Pn))/100 */
$rpn91_3 = static fn(float $p): float => (91.0 + 3.0 * log10($p)) / 100.0;

/** rpint=(103+2.5×log10(Pn))/100 */
$rpint103 = static fn(float $p): float => (103.0 + 2.5 * log10($p)) / 100.0;

/** qp0 = pct% × Pn_W */
$qp0pct = static fn(float $pct, float $pnKw): float => ($pct / 100.0) * $pnKw * 1000.0;

/** qp0 = Pn_kW × (E + F × log10(Pn_kW)) / 100 × 1000 W */
$qp0EF = static fn(float $pnKw, float $e, float $f): float
    => $pnKw * ($e + $f * log10($pnKw)) / 100.0 * 1000.0;

/** Entrée standard « classique gaz » sans ventouse */
$chaudGaz = static function (
    float $rpnCoeff,
    float $rpintCoeff,
    float $qp0PctVal,
    float $pveil
) use ($rpn84, $rpint80, $qp0pct): \Closure {
    return static function (float $pnKw, float $e, float $f) use (
        $rpnCoeff, $rpintCoeff, $qp0PctVal, $pveil
    ): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => ($rpnCoeff + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => ($rpintCoeff + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => $qp0PctVal / 100.0 * $pnW,
            'pveil' => $pveil,
        ];
    };
};

return [

    // ── Chaudières gaz classiques ─────────────────────────────────────────────
    // ID 1 : avant 1981  — rpn=84+2log, rpint=80+3log, qp0=4%Pn, pveil=240
    1 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.04 * $pnW,
            'pveil' => 240.0,
        ];
    },

    // ID 2 : 1981-1985 — qp0=2%Pn, pveil=150
    2 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.02 * $pnW,
            'pveil' => 150.0,
        ];
    },

    // ID 3 : 1986-1990 — qp0=1.5%Pn, pveil=150
    3 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.015 * $pnW,
            'pveil' => 150.0,
        ];
    },

    // ID 4 : gaz standard 1991-2000 — qp0=1.2%Pn, pveil=120
    4 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.012 * $pnW,
            'pveil' => 120.0,
        ];
    },

    // ID 5 : gaz standard 2001-2015 — qp0=1%Pn, pveil=0
    5 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.01 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 6 : gaz standard après 2015 — qp0=Pn×(E+F×log10(Pn))/100, pveil=0
    6 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => $pnKw * ($e + $f * log10($pnKw)) / 100.0 * 1000.0,
            'pveil' => 0.0,
        ];
    },

    // ── Chaudières gaz basse température ─────────────────────────────────────
    // ID 7 : 1991-2000 — rpn=rpint=87.5+1.5log, qp0=1.2%Pn, pveil=120
    7 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        $rp  = (87.5 + 1.5 * log10($pnKw)) / 100.0;
        return [
            'pn'    => $pnW,
            'rpn'   => $rp,
            'rpint' => $rp,
            'qp0'   => 0.012 * $pnW,
            'pveil' => 120.0,
        ];
    },

    // ID 8 : 2001-2015 — qp0=1%Pn, pveil=0
    8 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        $rp  = (87.5 + 1.5 * log10($pnKw)) / 100.0;
        return [
            'pn'    => $pnW,
            'rpn'   => $rp,
            'rpint' => $rp,
            'qp0'   => 0.01 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 9 : après 2015 — qp0=Pn×(E+F×log10(Pn))/100, pveil=0
    9 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        $rp  = (87.5 + 1.5 * log10($pnKw)) / 100.0;
        return [
            'pn'    => $pnW,
            'rpn'   => $rp,
            'rpint' => $rp,
            'qp0'   => $pnKw * ($e + $f * log10($pnKw)) / 100.0 * 1000.0,
            'pveil' => 0.0,
        ];
    },

    // ── Chaudières gaz à condensation ────────────────────────────────────────
    // ID 10 : condensation 1981-1985 — rpn=91+log, rpint=97+log, qp0=1%Pn, pveil=150
    10 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (91.0 + log10($pnKw)) / 100.0,
            'rpint' => (97.0 + log10($pnKw)) / 100.0,
            'qp0'   => 0.01 * $pnW,
            'pveil' => 150.0,
        ];
    },

    // ID 11 : condensation 1986-2000 — pveil=120
    11 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (91.0 + log10($pnKw)) / 100.0,
            'rpint' => (97.0 + log10($pnKw)) / 100.0,
            'qp0'   => 0.01 * $pnW,
            'pveil' => 120.0,
        ];
    },

    // ID 12 : condensation 2001-2015 — pveil=0
    12 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (91.0 + log10($pnKw)) / 100.0,
            'rpint' => (97.0 + log10($pnKw)) / 100.0,
            'qp0'   => 0.01 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 13 : condensation gaz ≥2016, Pn≤70 — rpn=91+3log, rpint=103+2.5log, qp0=0.5%Pn
    13 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (91.0 + 3.0 * log10($pnKw)) / 100.0,
            'rpint' => (103.0 + 2.5 * log10($pnKw)) / 100.0,
            'qp0'   => 0.005 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 14 : condensation gaz ≥2016, 70<Pn≤400 — rpn=94+log, rpint=105+0.5log, qp0=0.3%Pn
    14 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (94.0 + log10($pnKw)) / 100.0,
            'rpint' => (105.0 + 0.5 * log10($pnKw)) / 100.0,
            'qp0'   => 0.003 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 15 : condensation gaz ≥2016, Pn>400 — valeurs fixes
    15 => static function (float $pnKw, float $e, float $f): array {
        $pnW = max($pnKw, 400.0) * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.966,
            'rpint' => 1.063,
            'qp0'   => 0.003 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ── Chaudières fioul classiques ───────────────────────────────────────────
    // ID 16 : avant 1970 — qp0=4%Pn, pveil=0
    16 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.04 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 17 : fioul classique 1970-1975 — qp0=3%Pn
    17 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.03 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 18 : fioul classique 1976-1980 — qp0=2%Pn
    18 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.02 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 19 : fioul classique 1981-1990 — qp0=1%Pn
    19 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.01 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 20 : fioul standard 1991-2015 — qp0=1%Pn
    20 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => 0.01 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 21 : fioul standard après 2015 — qp0=Pn×(E+F×log10(Pn))/100
    21 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (84.0 + 2.0 * log10($pnKw)) / 100.0,
            'rpint' => (80.0 + 3.0 * log10($pnKw)) / 100.0,
            'qp0'   => $pnKw * ($e + $f * log10($pnKw)) / 100.0 * 1000.0,
            'pveil' => 0.0,
        ];
    },

    // ID 22 : fioul basse température 1991-2015 — rpn=rpint=87.5+1.5log, qp0=1%Pn
    22 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        $rp  = (87.5 + 1.5 * log10($pnKw)) / 100.0;
        return [
            'pn'    => $pnW,
            'rpn'   => $rp,
            'rpint' => $rp,
            'qp0'   => 0.01 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ID 23 : fioul basse température après 2015 — qp0=Pn×(E+F×log10(Pn))/100
    23 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        $rp  = (87.5 + 1.5 * log10($pnKw)) / 100.0;
        return [
            'pn'    => $pnW,
            'rpn'   => $rp,
            'rpint' => $rp,
            'qp0'   => $pnKw * ($e + $f * log10($pnKw)) / 100.0 * 1000.0,
            'pveil' => 0.0,
        ];
    },

    // ID 24 : fioul condensation 1996-2015 — rpn=91+log, rpint=97+log, qp0=1%Pn
    24 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (91.0 + log10($pnKw)) / 100.0,
            'rpint' => (97.0 + log10($pnKw)) / 100.0,
            'qp0'   => 0.01 * $pnW,
            'pveil' => 0.0,
        ];
    },

    // ── Générateur à air chaud à combustion ──────────────────────────────────
    // ID 69 : avant 2006, Pn≤300 — rpn=77%, rpint=74%, qp0=Pn×(1.75−0.55×log10(Pn))×1000
    69 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.77,
            'rpint' => 0.74,
            'qp0'   => $pnKw * (1.75 - 0.55 * log10($pnKw)) * 1000.0,
            'pveil' => 0.0,
        ];
    },

    // Entrées 25-68, 70-93 : à digitaliser dans TASK-A07
    // Ne pas lever d'exception ici — l'appelant doit gérer null gracieusement.
];
