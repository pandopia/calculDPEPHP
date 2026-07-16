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
 * @status       partial — IDs 1-69 implémentés (gaz, fioul, bois bûche/plaquette/granulés,
 *               charbon via alias GenerateurChAlias, air chaud id 69).
 *               IDs 70-93 (air chaud 2006+, radiateurs gaz, accumulateurs/chauffe-eau
 *               gaz — usage principalement ECS) restent à digitaliser dans TASK-A07.
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

    // ID 25 : Chaudière fioul à condensation après 2015 — Pn≤70 — rpn=91+3*log10(Pn), rpint=98+3*log10(Pn), qp0=0.50%
    25 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (91 + 3 * log10($pnKw)) / 100.0,
            'rpint' => (98 + 3 * log10($pnKw)) / 100.0,
            'qp0'   => 0.005 * $pnW,
            'pveil' => 0.0,
        ];
    },
    // ID 26 : Chaudière fioul à condensation après 2015 — 70<Pn≤400 — rpn=94+log10(Pn), rpint=100+log10(Pn), qp0=0.60%
    26 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (94 + log10($pnKw)) / 100.0,
            'rpint' => (100 + log10($pnKw)) / 100.0,
            'qp0'   => 0.006 * $pnW,
            'pveil' => 0.0,
        ];
    },
    // ID 27 : Chaudière fioul à condensation après 2015 — Pn>400 — rpn=96.6, rpint=102.6, qp0=0.30%
    27 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.966,
            'rpint' => 1.026,
            'qp0'   => 0.003 * $pnW,
            'pveil' => 0.0,
        ];
    },
    // ID 28 : Chaudière bois bûche ou plaquette <1978 — Pn≤70 — rpn=47+6*log10(Pn), rpint=48+6*log10(Pn), qp0=0.08*Pn*(Pn)^-0.27
    28 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (47 + 6 * log10($pnKw)) / 100.0,
            'rpint' => (48 + 6 * log10($pnKw)) / 100.0,
            'qp0'   => 0.08 * ($pnKw ** 0.73) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 29 : Chaudière bois bûche ou plaquette <1978 — 70<Pn≤400 — rpn=58, rpint=59, qp0=1.8
    29 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 1.8 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 30 : Chaudière bois bûche ou plaquette <1978 — Pn>400 — rpn=58, rpint=59, qp0=1.1
    30 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 31 : Chaudière bois bûche ou plaquette 1978-1994 — Pn≤70 — rpn=47+6*log10(Pn), rpint=48+6*log10(Pn), qp0=0.07*Pn*(Pn)^-0.3
    31 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (47 + 6 * log10($pnKw)) / 100.0,
            'rpint' => (48 + 6 * log10($pnKw)) / 100.0,
            'qp0'   => 0.07 * ($pnKw ** 0.70) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 32 : Chaudière bois bûche ou plaquette 1978-1994 — 70<Pn≤400 — rpn=58, rpint=59, qp0=1.4
    32 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 1.4 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 33 : Chaudière bois bûche ou plaquette 1978-1994 — Pn>400 — rpn=58, rpint=59, qp0=0.8
    33 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 0.8 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 34 : Chaudière bois bûche ou plaquette 1995-2003 — Pn≤70 — rpn=47+6*log10(Pn), rpint=48+6*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    34 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (47 + 6 * log10($pnKw)) / 100.0,
            'rpint' => (48 + 6 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 35 : Chaudière bois bûche ou plaquette 1995-2003 — 70<Pn≤400 — rpn=58, rpint=59, qp0=1.1
    35 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 36 : Chaudière bois bûche ou plaquette 1995-2003 — Pn>400 — rpn=58, rpint=59, qp0=0.5
    36 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 0.5 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 37 : Chaudière bois bûche ou plaquette 2004-2012 — Pn≤70 — rpn=57+6*log10(Pn), rpint=58+6*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    37 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (57 + 6 * log10($pnKw)) / 100.0,
            'rpint' => (58 + 6 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 38 : Chaudière bois bûche ou plaquette 2004-2012 — 70<Pn≤400 — rpn=68, rpint=69, qp0=1.1
    38 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.68,
            'rpint' => 0.69,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 39 : Chaudière bois bûche ou plaquette 2004-2012 — Pn>400 — rpn=68, rpint=69, qp0=0.5
    39 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.68,
            'rpint' => 0.69,
            'qp0'   => 0.5 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 40 : Chaudière bois bûche ou plaquette 2013-2017 — Pn≤70 — rpn=67+6*log10(Pn), rpint=68+6*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    40 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (67 + 6 * log10($pnKw)) / 100.0,
            'rpint' => (68 + 6 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 41 : Chaudière bois bûche ou plaquette 2013-2017 — 70<Pn≤400 — rpn=78, rpint=79, qp0=1.1
    41 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.78,
            'rpint' => 0.79,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 42 : Chaudière bois bûche ou plaquette 2013-2017 — Pn>400 — rpn=78, rpint=79, qp0=0.5
    42 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.78,
            'rpint' => 0.79,
            'qp0'   => 0.5 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 43 : Chaudière bois bûche ou plaquette 2018-2019 — Pn≤70 — rpn=80+2*log10(Pn), rpint=77+3*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    43 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (80 + 2 * log10($pnKw)) / 100.0,
            'rpint' => (77 + 3 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 44 : Chaudière bois bûche ou plaquette 2018-2019 — 70<Pn≤400 — rpn=84, rpint=83, qp0=1.1
    44 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.84,
            'rpint' => 0.83,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 45 : Chaudière bois bûche ou plaquette 2018-2019 — Pn>400 — rpn=84, rpint=83, qp0=0.5
    45 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.84,
            'rpint' => 0.83,
            'qp0'   => 0.5 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 46 : Chaudière bois bûche ou plaquette >2019 — Pn≤20 — rpn=89+2*log10(Pn), rpint=84+2*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    46 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (89 + 2 * log10($pnKw)) / 100.0,
            'rpint' => (84 + 2 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 47 : Chaudière bois bûche ou plaquette >2019 — 20<Pn≤70 — rpn=90+2*log10(Pn), rpint=85+2*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    47 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (90 + 2 * log10($pnKw)) / 100.0,
            'rpint' => (85 + 2 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 48 : Chaudière bois bûche ou plaquette >2019 — 70<Pn≤400 — rpn=94, rpint=89, qp0=1.1
    48 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.94,
            'rpint' => 0.89,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 49 : Chaudière bois bûche ou plaquette >2019 — Pn>400 — rpn=94, rpint=89, qp0=0.5
    49 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.94,
            'rpint' => 0.89,
            'qp0'   => 0.5 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 50 : Chaudière bois granulés <1978 — Pn≤70 — rpn=47+6*log10(Pn), rpint=48+6*log10(Pn), qp0=0.08*Pn*(Pn)^-0.27
    50 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (47 + 6 * log10($pnKw)) / 100.0,
            'rpint' => (48 + 6 * log10($pnKw)) / 100.0,
            'qp0'   => 0.08 * ($pnKw ** 0.73) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 51 : Chaudière bois granulés <1978 — 70<Pn≤400 — rpn=58, rpint=59, qp0=1.8
    51 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 1.8 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 52 : Chaudière bois granulés <1978 — Pn>400 — rpn=58, rpint=59, qp0=1.1
    52 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 53 : Chaudière bois granulés 1978-1994 — Pn≤70 — rpn=47+6*log10(Pn), rpint=48+6*log10(Pn), qp0=0.08*Pn*(Pn)^-0.3
    53 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (47 + 6 * log10($pnKw)) / 100.0,
            'rpint' => (48 + 6 * log10($pnKw)) / 100.0,
            'qp0'   => 0.08 * ($pnKw ** 0.70) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 54 : Chaudière bois granulés 1978-1994 — 70<Pn≤400 — rpn=58, rpint=59, qp0=1.4
    54 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 1.4 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 55 : Chaudière bois granulés 1978-1994 — Pn>400 — rpn=58, rpint=59, qp0=0.8
    55 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.58,
            'rpint' => 0.59,
            'qp0'   => 0.8 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 56 : Chaudière bois granulés 1995-2003 — Pn≤70 — rpn=57+6*log10(Pn), rpint=58+6*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    56 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (57 + 6 * log10($pnKw)) / 100.0,
            'rpint' => (58 + 6 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 57 : Chaudière bois granulés 1995-2003 — 70<Pn≤400 — rpn=68, rpint=69, qp0=1.1
    57 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.68,
            'rpint' => 0.69,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 58 : Chaudière bois granulés 1995-2003 — Pn>400 — rpn=68, rpint=69, qp0=0.5
    58 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.68,
            'rpint' => 0.69,
            'qp0'   => 0.5 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 59 : Chaudière bois granulés 2004-2012 — Pn≤70 — rpn=67+6*log10(Pn), rpint=68+6*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    59 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (67 + 6 * log10($pnKw)) / 100.0,
            'rpint' => (68 + 6 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 60 : Chaudière bois granulés 2004-2012 — 70<Pn≤400 — rpn=78, rpint=79, qp0=1.1
    60 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.78,
            'rpint' => 0.79,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 61 : Chaudière bois granulés 2004-2012 — Pn>400 — rpn=78, rpint=79, qp0=0.5
    61 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.78,
            'rpint' => 0.79,
            'qp0'   => 0.5 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 62 : Chaudière bois granulés 2013-2019 — Pn≤70 — rpn=80+2*log10(Pn), rpint=77+3*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    62 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (80 + 2 * log10($pnKw)) / 100.0,
            'rpint' => (77 + 3 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 63 : Chaudière bois granulés 2013-2019 — 70<Pn≤400 — rpn=84, rpint=83, qp0=1.1
    63 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.84,
            'rpint' => 0.83,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 64 : Chaudière bois granulés 2013-2019 — Pn>400 — rpn=84, rpint=83, qp0=0.5
    64 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.84,
            'rpint' => 0.83,
            'qp0'   => 0.5 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 65 : Chaudière bois granulés >2019 — Pn≤20 — rpn=91+2*log10(Pn), rpint=88+2*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    65 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (91 + 2 * log10($pnKw)) / 100.0,
            'rpint' => (88 + 2 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 66 : Chaudière bois granulés >2019 — 20<Pn≤70 — rpn=92+2*log10(Pn), rpint=89+2*log10(Pn), qp0=0.085*Pn*(Pn)^-0.4
    66 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => (92 + 2 * log10($pnKw)) / 100.0,
            'rpint' => (89 + 2 * log10($pnKw)) / 100.0,
            'qp0'   => 0.085 * ($pnKw ** 0.60) * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 67 : Chaudière bois granulés >2019 — 70<Pn≤400 — rpn=96, rpint=93, qp0=1.1
    67 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.96,
            'rpint' => 0.93,
            'qp0'   => 1.1 * 1000.0,
            'pveil' => 0.0,
        ];
    },
    // ID 68 : Chaudière bois granulés >2019 — Pn>400 — rpn=96, rpint=93, qp0=0.5
    68 => static function (float $pnKw, float $e, float $f): array {
        $pnW = $pnKw * 1000.0;
        return [
            'pn'    => $pnW,
            'rpn'   => 0.96,
            'rpint' => 0.93,
            'qp0'   => 0.5 * 1000.0,
            'pveil' => 0.0,
        ];
    },

    // Entrées 70-93 (générateurs air chaud, radiateurs gaz, accumulateurs et
    // chauffe-eau gaz — usage ECS/air chaud) : restent à digitaliser (TASK-A07).
    // Ne pas lever d'exception ici — l'appelant doit gérer null gracieusement.
];
