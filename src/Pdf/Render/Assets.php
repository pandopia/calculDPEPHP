<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Render;

use RuntimeException;

/**
 * Éléments graphiques officiels (archive « documents design DPE » publiée avec
 * les modèles) et polices du rapport.
 */
final class Assets
{
    public static function root(): string
    {
        return dirname(__DIR__, 3) . '/resources/pdf';
    }

    public static function fontDirectory(): string
    {
        return self::root() . '/fonts/tcpdf';
    }

    /** @param string $name nom de fichier sans extension dans resources/pdf/design/png */
    public static function picto(string $name): string
    {
        $path = self::root() . "/design/png/$name.png";
        if (!is_file($path)) {
            throw new RuntimeException("Pictogramme introuvable : $name");
        }

        return $path;
    }
}
