<?php

declare(strict_types=1);

namespace CalculDpePHP\Engine;

use CalculDpePHP\Common\Period;
use CalculDpePHP\Tables\TableRepository;
use DOMDocument;

/**
 * Contexte global d'un calcul DPE.
 *
 * Porte les données dérivées du logement (zone climatique, altitude, surface
 * habitable, période…) ainsi que des références techniques (TableRepository,
 * DOMDocument).
 *
 * Les Calculators lisent et écrivent dans ce contexte des résultats nommés
 * via `set()`/`get()`. Les noms suivent la convention "section_id.balise"
 * (ex: "3.2.1.umur") pour éviter les collisions.
 */
final class CalculationContext
{
    /** @var array<string, mixed> */
    private array $bag = [];

    public function __construct(
        public readonly DOMDocument $document,
        public readonly TableRepository $tables,
        public readonly ?string $zoneClimatique = null,
        public readonly ?string $classeAltitude = null,
        public readonly ?float $surfaceHabitable = null,
        public readonly ?Period $period = null,
        public readonly ?string $zoneGroupe = null,
        public readonly ?string $energieChauffagePrincipale = null,
        public readonly ?int $periodeConstructionId = null,
    ) {}

    /**
     * Mapping enum_zone_climatique_id → groupe H1/H2/H3.
     *
     * @spec-pages 17, 21, 120 (cf. §18.1)
     */
    public static function zoneGroupeFromId(?string $zoneClimatiqueId): ?string
    {
        return match ($zoneClimatiqueId) {
            '1', '2', '3'        => 'H1',  // H1a, H1b, H1c
            '4', '5', '6', '7'   => 'H2',  // H2a, H2b, H2c, H2d
            '8'                  => 'H3',
            default              => null,
        };
    }

    /**
     * Générateurs de chauffage qui fournissent la chaleur par une résistance
     * électrique (XSD `enum_type_generateur_ch_id`) : convecteur, panneau
     * rayonnant, radiateur, autres émetteurs à effet joule, plancher ou plafond
     * rayonnant, radiateur à accumulation, convecteur bi-jonction et chaudière
     * électrique.
     *
     * @var list<int>
     */
    private const GENERATEURS_EFFET_JOULE = [98, 99, 100, 101, 102, 103, 104, 105, 106];

    /**
     * Colonne des tables forfaitaires de U : « effet joule » ou « autres »
     * (§3.2.1 p.13 et suivantes).
     *
     * §3.2 p.12 la définit sans ambiguïté : « On considère qu'un logement est
     * chauffé par effet joule lorsque la chaleur est fournie par une résistance
     * électrique. » Une pompe à chaleur fonctionne sur un cycle thermodynamique :
     * elle est électrique sans être à effet joule, et relève donc de « autres ».
     *
     * @spec-section 3.2
     * @spec-pages   12
     */
    public static function colonneEffetJoule(?int $energieId, ?int $typeGenerateurId): ?string
    {
        if ($energieId === null) {
            return null;
        }
        if ($energieId !== 1) {
            return 'autres';
        }

        return $typeGenerateurId !== null
            && in_array($typeGenerateurId, self::GENERATEURS_EFFET_JOULE, true)
                ? 'joule'
                : 'autres';
    }

    public function set(string $key, mixed $value): void
    {
        $this->bag[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->bag[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->bag);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->bag;
    }
}
