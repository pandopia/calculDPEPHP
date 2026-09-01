<?php

declare(strict_types=1);

namespace CalculDpePHP\Sortie;

use CalculDpePHP\Engine\CalculationContext;

/**
 * Étiquettes DPE et GES : détermination de la classe A→G.
 *
 * Trois règles que l'implémentation précédente ignorait :
 *
 * 1. **Le seuil est strict.** Un logement est de classe X si sa valeur est
 *    *strictement inférieure* au seuil de X. Comparer avec `<=` décalait d'une
 *    classe tous les logements pile sur un seuil — 49 des 79 écarts de classe
 *    du corpus, avec une valeur au m² pourtant exacte.
 * 2. **Les petites surfaces ont leurs propres seuils.** L'arrêté du 25 mars
 *    2024 les relève, surface par surface de 3 à 40 m².
 * 3. **L'altitude relève E et F.** Au-dessus de 800 m en zone H1b, H1c ou H2d.
 *
 * La valeur classée est la valeur au m² **plancher**, celle qui est publiée :
 * la classe et l'étiquette affichée doivent être cohérentes.
 *
 * @spec-source https://www.legifrance.gouv.fr/jorf/id/JORFTEXT000049446315
 * @tables      reference/tv_seuils_classes
 */
final class SeuilsClasses
{
    private const TABLE = 'reference/tv_seuils_classes';

    /** Surface de référence au-delà de laquelle les seuils nationaux s'appliquent. */
    private const SURFACE_MAX_PETIT_LOGEMENT = 40;

    /** `enum_classe_altitude_id` = 3 : au-dessus de 800 m. */
    private const ALTITUDE_SUP_800 = 3;

    /** `enum_zone_climatique_id` de H1b, H1c et H2d. */
    private const ZONES_RELEVEES_EN_ALTITUDE = [2, 3, 7];

    private const ORDRE = ['A', 'B', 'C', 'D', 'E', 'F'];

    /** Étiquette énergie, depuis la consommation d'énergie primaire au m². */
    public static function energie(
        int $epParM2,
        float $surface,
        ?int $zoneClimatiqueId,
        ?int $classeAltitudeId,
        CalculationContext $context,
    ): string {
        return self::classer($epParM2, 'ep', $surface, $zoneClimatiqueId, $classeAltitudeId, $context);
    }

    /** Étiquette climat, depuis les émissions de GES au m². */
    public static function ges(
        int $gesParM2,
        float $surface,
        ?int $zoneClimatiqueId,
        ?int $classeAltitudeId,
        CalculationContext $context,
    ): string {
        return self::classer($gesParM2, 'ges', $surface, $zoneClimatiqueId, $classeAltitudeId, $context);
    }

    private static function classer(
        int $valeur,
        string $grandeur,
        float $surface,
        ?int $zoneClimatiqueId,
        ?int $classeAltitudeId,
        CalculationContext $context,
    ): string {
        $seuils = self::seuils($grandeur, $surface, $zoneClimatiqueId, $classeAltitudeId, $context);

        foreach (self::ORDRE as $classe) {
            if (isset($seuils[$classe]) && $valeur < $seuils[$classe]) {
                return $classe;
            }
        }

        return 'G';
    }

    /**
     * Seuils applicables : barème national, relevé pour les petites surfaces,
     * puis E et F relevés en altitude.
     *
     * @return array<string, int|float>
     */
    private static function seuils(
        string $grandeur,
        float $surface,
        ?int $zoneClimatiqueId,
        ?int $classeAltitudeId,
        CalculationContext $context,
    ): array {
        /** @var array<string, array<string, mixed>> $table */
        $table = $context->tables->load(self::TABLE);

        $seuils = $table['standard']['defaut'][$grandeur];
        $surfaceArrondie = (int) round($surface);

        if ($surface > 0.0 && $surfaceArrondie <= self::SURFACE_MAX_PETIT_LOGEMENT) {
            $seuils = $table['standard']['par_surface'][$surfaceArrondie][$grandeur] ?? $seuils;
        }

        if (!self::estRelevePourAltitude($zoneClimatiqueId, $classeAltitudeId)) {
            return $seuils;
        }

        $altitude = $table['altitude_sup_800'];
        $releves = ($surface > 0.0 && $surfaceArrondie <= self::SURFACE_MAX_PETIT_LOGEMENT)
            ? ($altitude['par_surface'][$surfaceArrondie][$grandeur] ?? $altitude['defaut'][$grandeur])
            : $altitude['defaut'][$grandeur];

        // Le barème d'altitude ne porte que sur E et F.
        return array_merge($seuils, $releves);
    }

    private static function estRelevePourAltitude(?int $zoneClimatiqueId, ?int $classeAltitudeId): bool
    {
        return $classeAltitudeId === self::ALTITUDE_SUP_800
            && $zoneClimatiqueId !== null
            && in_array($zoneClimatiqueId, self::ZONES_RELEVEES_EN_ALTITUDE, true);
    }
}
