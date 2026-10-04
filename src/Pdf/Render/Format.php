<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render;

/**
 * Formats d'affichage du rapport (typographie française).
 */
final class Format
{
    /**
     * Séparateur de milliers : espace insécable (l'espace fine U+202F manque
     * à IBM Plex Sans Condensed).
     */
    public const THOUSANDS = "\u{00A0}";

    public static function int(float|int $value): string
    {
        return number_format((float) round($value), 0, ',', self::THOUSANDS);
    }

    public static function euros(float|int $value): string
    {
        return self::int($value) . "\u{00A0}€";
    }

    public static function decimal(float $value, int $decimals = 1): string
    {
        $formatted = number_format($value, $decimals, ',', self::THOUSANDS);

        return str_contains($formatted, ',') ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    }

    public static function surface(?float $value): string
    {
        return $value === null ? '' : self::decimal($value, 2) . "\u{00A0}m²";
    }

    public static function date(\DateTimeInterface $date): string
    {
        return $date->format('d/m/Y');
    }

    public static function ucfirst(string $text): string
    {
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }
}
