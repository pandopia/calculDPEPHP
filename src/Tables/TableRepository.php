<?php

declare(strict_types=1);

namespace CalculDpe\Tables;

use RuntimeException;

/**
 * Façade unique pour accéder aux tables `tv_*_id` digitalisées dans
 * `resources/tables/**\/*.php`.
 *
 * Chaque table est un fichier PHP de la forme :
 *
 *     <?php return [
 *         1 => [...],
 *         2 => [...],
 *     ];
 *
 * Les tables sont chargées paresseusement et mémoïsées en mémoire.
 *
 * Usage :
 *     $umur = $repo->load('enveloppe/tv_umur')[42];
 */
final class TableRepository
{
    /** @var array<string, array<int|string, mixed>> */
    private array $cache = [];

    public function __construct(
        private readonly string $tablesRoot,
    ) {}

    /**
     * Charge une table identifiée par son chemin relatif (sans extension).
     * Exemple : `enveloppe/tv_umur` → `resources/tables/enveloppe/tv_umur.php`.
     *
     * @return array<int|string, mixed>
     */
    public function load(string $relPath): array
    {
        if (isset($this->cache[$relPath])) {
            return $this->cache[$relPath];
        }
        $file = rtrim($this->tablesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relPath . '.php';
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('Table introuvable : %s', $file));
        }
        $data = require $file;
        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Table %s : doit retourner un array.', $file));
        }
        return $this->cache[$relPath] = $data;
    }

    public function has(string $relPath): bool
    {
        $file = rtrim($this->tablesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relPath . '.php';
        return is_file($file);
    }
}
