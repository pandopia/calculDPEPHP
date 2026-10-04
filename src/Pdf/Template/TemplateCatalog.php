<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Template;

use DateTimeImmutable;

/**
 * Choisit le modèle officiel applicable à un DPE.
 *
 * Le ministère publie une édition des modèles par période d'établissement
 * (1er juillet 2021, 1er janvier 2023, 1er juillet 2024, 1er septembre 2025).
 * Seule l'édition en vigueur est mise en page : un DPE ne se réédite pas avec
 * un modèle différent de celui de sa date d'établissement, et ceux établis
 * avant le 1er septembre 2025 ont déjà leur rapport.
 */
final class TemplateCatalog
{
    public const EDITION_2025 = '01.09_2025';

    private const EDITION_2025_START = '2025-09-01';

    public function __construct(private readonly string $directory)
    {
    }

    public static function default(): self
    {
        return new self(dirname(__DIR__, 3) . '/resources/pdf/templates');
    }

    public function resolve(TemplateVariant $variant, DateTimeImmutable $dateEtablissement): Template
    {
        if ($dateEtablissement < new DateTimeImmutable(self::EDITION_2025_START)) {
            throw new UnsupportedTemplateException(sprintf(
                'DPE établi le %s : seul le modèle de rapport en vigueur depuis le %s est disponible.',
                $dateEtablissement->format('d/m/Y'),
                (new DateTimeImmutable(self::EDITION_2025_START))->format('d/m/Y'),
            ));
        }

        return new Template($variant, self::EDITION_2025, $this->directory);
    }
}
