<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Template;

use DateTimeImmutable;

/**
 * Choisit le modèle officiel applicable à un DPE selon sa date d'établissement.
 *
 * Le ministère publie une édition des modèles par période d'établissement :
 * un DPE se présente avec le modèle en vigueur à la date où il a été établi.
 * Sont mises en page les éditions du 1er janvier 2023, du 1er juillet 2024
 * et du 1er septembre 2025, de même structure ; l'édition 2025 ajoute le QR
 * code de vérification. L'édition 2021-2022 (pages et annexes organisées
 * autrement) n'est pas prise en charge.
 */
final class TemplateCatalog
{
    public const EDITION_2025 = '01.09_2025';
    public const EDITION_2024 = '01.07_2024';
    public const EDITION_2023 = '01.01_2023';

    /** Édition ⇒ premier jour d'application, de la plus récente à la plus ancienne. */
    public const EDITIONS = [
        self::EDITION_2025 => '2025-09-01',
        self::EDITION_2024 => '2024-07-01',
        self::EDITION_2023 => '2023-01-01',
    ];

    /** Suffixe du fichier publié par le ministère (l'édition 2023 n'en a pas). */
    public const SOURCE_SUFFIXES = [
        self::EDITION_2025 => '_01.09_2025',
        self::EDITION_2024 => '_01.07_2024',
        self::EDITION_2023 => '',
    ];

    public function __construct(private readonly string $directory)
    {
    }

    public static function default(): self
    {
        return new self(dirname(__DIR__, 3) . '/resources/pdf/templates');
    }

    public static function editionPour(DateTimeImmutable $dateEtablissement): string
    {
        foreach (self::EDITIONS as $edition => $debut) {
            if ($dateEtablissement >= new DateTimeImmutable($debut)) {
                return $edition;
            }
        }

        throw new UnsupportedTemplateException(sprintf(
            'DPE établi le %s : les modèles de rapport antérieurs au %s ne sont pas pris en charge.',
            $dateEtablissement->format('d/m/Y'),
            (new DateTimeImmutable(self::EDITIONS[self::EDITION_2023]))->format('d/m/Y'),
        ));
    }

    public function resolve(TemplateVariant $variant, DateTimeImmutable $dateEtablissement): Template
    {
        return new Template($variant, self::editionPour($dateEtablissement), $this->directory);
    }
}
