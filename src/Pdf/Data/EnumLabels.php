<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Data;

/**
 * Libellés des énumérations du schéma ADEME, lus dans les `<xs:appinfo>` du
 * XSD (objet JSON id ⇒ libellé).
 */
final class EnumLabels
{
    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    private static ?string $xsd = null;

    public static function label(string $enum, int|string|null $id): ?string
    {
        if ($id === null) {
            return null;
        }

        return self::all($enum)[(string) $id] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function all(string $enum): array
    {
        if (isset(self::$cache[$enum])) {
            return self::$cache[$enum];
        }

        self::$xsd ??= (string) file_get_contents(dirname(__DIR__, 3) . '/resources/ademe_DPE.xsd');
        $pattern = '#<xs:appinfo source="[^"]*/' . preg_quote($enum, '#') . '">(.*?)</xs:appinfo>#s';
        $labels = [];
        if (preg_match($pattern, self::$xsd, $m) === 1) {
            $decoded = json_decode(trim($m[1]), true);
            if (is_array($decoded)) {
                $labels = array_map('strval', $decoded);
            }
        }

        return self::$cache[$enum] = $labels;
    }
}
