<?php

declare(strict_types=1);

namespace CalculDpe\Xml;

use DOMDocument;
use InvalidArgumentException;
use RuntimeException;

final class XmlReader
{
    public function load(string $path): DOMDocument
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException(sprintf('Fichier introuvable : %s', $path));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        if (!$document->load($path)) {
            throw new RuntimeException(sprintf('Impossible de charger le XML : %s', $path));
        }

        return $document;
    }
}
