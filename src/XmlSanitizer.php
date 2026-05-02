<?php

declare(strict_types=1);

namespace CalculDpe;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;
use RuntimeException;

final class XmlSanitizer
{
    public function process(string $inputFilePath, string $verifDirectory, string $inputDirectory): array
    {
        if (!is_file($inputFilePath)) {
            throw new InvalidArgumentException(sprintf('Fichier introuvable: %s', $inputFilePath));
        }

        $this->ensureDirectory($verifDirectory);
        $this->ensureDirectory($inputDirectory);

        $fileName = basename($inputFilePath);
        $verifPath = rtrim($verifDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        $sanitizedPath = rtrim($inputDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        if (realpath($inputFilePath) !== realpath($verifPath) && !copy($inputFilePath, $verifPath)) {
            throw new RuntimeException(sprintf('Impossible de copier le fichier vers %s', $verifPath));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        if (!$document->load($inputFilePath)) {
            throw new RuntimeException(sprintf('Impossible de charger le XML: %s', $inputFilePath));
        }

        $removedCount = 0;
        foreach (['donnee_intermediaire', 'sortie'] as $tagName) {
            $removedCount += $this->removeElementsByTagName($document, $tagName);
        }

        if ($document->save($sanitizedPath) === false) {
            throw new RuntimeException(sprintf('Impossible d\'enregistrer le fichier nettoye vers %s', $sanitizedPath));
        }

        return [
            'source' => $inputFilePath,
            'verif' => $verifPath,
            'input' => $sanitizedPath,
            'removed_count' => $removedCount,
        ];
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Impossible de creer le dossier %s', $directory));
        }
    }

    private function removeElementsByTagName(DOMDocument $document, string $tagName): int
    {
        $nodes = [];

        foreach ($document->getElementsByTagName($tagName) as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = $node;
            }
        }

        foreach ($nodes as $node) {
            $parent = $node->parentNode;
            if ($parent instanceof DOMNode) {
                $parent->removeChild($node);
            }
        }

        return count($nodes);
    }
}
