<?php

declare(strict_types=1);

namespace CalculDpe\Xml;

use DOMDocument;
use RuntimeException;

final class XmlWriter
{
    public function save(DOMDocument $document, string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Impossible de créer le dossier %s', $dir));
        }

        if ($document->save($path) === false) {
            throw new RuntimeException(sprintf('Impossible d\'écrire %s', $path));
        }
    }
}
