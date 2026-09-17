<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement\Combustion;

use DOMElement;
use DOMXPath;

/**
 * Puissance de dimensionnement Pdim et puissance nominale Pn des générateurs
 * à combustion (§13.2.2.4 p.91-92).
 *
 * Partagé par le générateur de chauffage et le générateur d'ECS : la spec
 * définit une seule puissance de dimensionnement, Pdim = max(Pch ; Pecs), et
 * une seule table Pdim → Pn pour les deux usages.
 *
 * @spec-section 13.2.2.4
 * @spec-pages   91-92
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/02-valeurs-defaut-gaz-fioul.md
 */
final class PuissanceDimensionnement
{
    /**
     * Puissance nécessaire à la production d'ECS, en W, selon le volume de
     * stockage Vs (litres) — §13.2.2.4 p.92 :
     *
     *   Vs = 0          (instantanée)      Pecs = 21
     *   0 < Vs ≤ 20     (semi-instantanée) Pecs = 21 − 0,8 × Vs
     *   20 < Vs ≤ 150   (semi-accumul.)    Pecs = 5 − 1,751 × (Vs − 20) / 65
     *   150 < Vs        (accumulation)     Pecs = (7,14 × Vs + 428) / 1000
     *
     * Les quatre branches sont continues en Vs = 20 (5 kW) et en Vs = 150
     * (1,5 kW), ce qui confirme la lecture du tableau.
     *
     * @spec-formula F-13.2.2.4-b
     */
    public static function pecsW(float $vsLitres): float
    {
        if ($vsLitres <= 0.0) {
            return 21_000.0;
        }
        if ($vsLitres <= 20.0) {
            return (21.0 - 0.8 * $vsLitres) * 1000.0;
        }
        if ($vsLitres <= 150.0) {
            return (5.0 - 1.751 * ($vsLitres - 20.0) / 65.0) * 1000.0;
        }

        return 7.14 * $vsLitres + 428.0; // déjà en W
    }

    /**
     * Pn (kW) lue dans la table §13.2.2.4 p.92 à partir de Pdim (kW).
     *
     * Colonne 1 : chaudières murales installées avant 2005 ou chaudières sur sol.
     * Colonne 2 : chaudières murales installées à partir de 2006.
     *
     * @spec-formula F-13.2.2.4-c
     */
    public static function pnFromPdimKw(float $pdimKw, bool $post2006): float
    {
        if ($pdimKw <= 5.0)  { return $post2006 ? 5.0  : 18.0; }
        if ($pdimKw <= 10.0) { return $post2006 ? 10.0 : 18.0; }
        if ($pdimKw <= 13.0) { return $post2006 ? 13.0 : 18.0; }
        if ($pdimKw <= 18.0) { return 18.0; }
        if ($pdimKw <= 24.0) { return 24.0; }
        if ($pdimKw <= 28.0) { return 28.0; }
        if ($pdimKw <= 32.0) { return 32.0; }
        if ($pdimKw <= 40.0) { return 40.0; }

        // Pdim > 40 : (partie entière(Pdim / 5) + 1) × 5
        return ((int)floor($pdimKw / 5.0) + 1) * 5.0;
    }

    /**
     * Détecte une chaudière murale installée à partir de 2006 via
     * data_complementaires, qui seule ouvre la colonne 2 de la table.
     */
    public static function isChaudierePost2006(DOMElement $genNode): bool
    {
        $doc = $genNode->ownerDocument;
        if ($doc === null) {
            return false;
        }
        $nodes = (new DOMXPath($doc))->query('./donnee_entree/data_complementaires', $genNode);
        if ($nodes === false || $nodes->length === 0) {
            return false;
        }
        $dc = $nodes->item(0);
        if (!$dc instanceof DOMElement) {
            return false;
        }
        $murale = $dc->getAttribute('data-chaudiere-murale');
        $annee  = $dc->getAttribute('data-annee-installation');

        return $murale === '1' && $annee !== '' && (int)$annee >= 2006;
    }
}
