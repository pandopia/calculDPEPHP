<?php

declare(strict_types=1);

namespace CalculDpePHP;

use DOMDocument;
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

        // Préserve les caractéristiques saisies (pn, rpn… selon
        // enum_methode_saisie_carac_sys_id) — voir Xml\OutputPurger.
        $removedCount = \CalculDpePHP\Xml\OutputPurger::purge($document);

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

}
