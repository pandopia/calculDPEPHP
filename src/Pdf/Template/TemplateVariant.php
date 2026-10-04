<?php

declare(strict_types=1);

namespace CalculDpePHP\Pdf\Template;

/**
 * Les quatre modèles officiels de rapport DPE pour les logements existants.
 *
 * Source : modèles publiés par le ministère, une édition par période
 * d'établissement (https://rt-re-batiment.developpement-durable.gouv.fr/modeles-des-dpe-a788.html).
 * Le choix se fait sur `enum_methode_application_dpe_log_id` (XSD ADEME).
 */
enum TemplateVariant: string
{
    case MAISON = '01_dpe_existant_maison';
    case APPARTEMENT = '02_dpe_existant_appartement';
    case APPARTEMENT_IMMEUBLE = '03_dpe_appartement_a_partir_d_immeuble';
    case IMMEUBLE = '04_dpe_existant_ic';

    public static function fromMethodeApplication(int $methode): self
    {
        return match (true) {
            in_array($methode, [1, 14, 18], true) => self::MAISON,
            in_array($methode, [10, 11, 12, 13, 33, 34, 38, 39, 40], true) => self::APPARTEMENT_IMMEUBLE,
            in_array($methode, [6, 7, 8, 9, 17, 21, 26, 27, 28, 29, 30], true) => self::IMMEUBLE,
            default => self::APPARTEMENT,
        };
    }

    /** « logement » ou « bâtiment » dans les libellés du modèle. */
    public function isBatiment(): bool
    {
        return $this === self::IMMEUBLE;
    }

    public function isAppartement(): bool
    {
        return $this === self::APPARTEMENT || $this === self::APPARTEMENT_IMMEUBLE;
    }
}
