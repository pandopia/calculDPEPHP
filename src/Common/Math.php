<?php

declare(strict_types=1);

namespace CalculDpePHP\Common;

final class Math
{
    /**
     * Somme pondérée : Σ(coef_i × val_i).
     *
     * @param iterable<array{0: float, 1: float}> $pairs
     */
    public static function weightedSum(iterable $pairs): float
    {
        $sum = 0.0;
        foreach ($pairs as [$coef, $value]) {
            $sum += $coef * $value;
        }
        return $sum;
    }

    /**
     * Moyenne pondérée : Σ(coef_i × val_i) / Σ coef_i.
     *
     * @param iterable<array{0: float, 1: float}> $pairs
     */
    public static function weightedAverage(iterable $pairs): float
    {
        $num = 0.0;
        $den = 0.0;
        foreach ($pairs as [$coef, $value]) {
            $num += $coef * $value;
            $den += $coef;
        }
        return $den > 0.0 ? $num / $den : 0.0;
    }
}
