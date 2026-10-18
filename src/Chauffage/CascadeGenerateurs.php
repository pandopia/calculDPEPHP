<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage;

use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Générateurs principaux d'une cascade (§13.2.1.3).
 *
 * Le XSD porte `priorite_generateur_cascade` sur chaque générateur. Deux
 * configurations s'en déduisent, et la méthode les traite différemment :
 *
 * - **Cascade sans priorité** (§13.2.1.3.2) : les générateurs portent tous la
 *   même priorité — « dans le cas de deux générateurs en cascade sans
 *   priorité, les deux générateurs sont déclarés comme principal ». Ils
 *   contribuent au taux de charge au prorata de leur puissance.
 * - **Cascade avec priorité** (§13.2.1.3.1) : le générateur prioritaire prend
 *   `min(Prel₁ ; Tchx_dim)` à chaque point de charge, le suivant le reliquat.
 *   Sur un profil de charge d'habitation, où les trois quarts de la
 *   pondération portent sur des taux inférieurs à 40 %, le second ne
 *   fonctionne presque jamais.
 *
 * Le moteur de référence CSTB va au bout de cette logique : **le générateur
 * prioritaire assure la totalité du besoin**, et son taux de charge se calcule
 * sur sa seule puissance. C'est la lecture retenue par open3cl, qui la rattache
 * aux autotests d'État APP5-0-4, 41 et 43.
 *
 * @spec-section 13.2.1.3
 * @spec-pages   79-86
 * @spec-source  resources/specsplitted/13-rendement-combustion/02-chaudieres/01-profil-charge.md
 * @xml-input    generateur_chauffage.donnee_entree.priorite_generateur_cascade
 */
final class CascadeGenerateurs
{
    /**
     * Générateurs qui assurent le besoin.
     *
     * Hors cascade avec priorité — priorité absente, nulle, ou identique
     * partout — tous les générateurs sont rendus : l'appelant retrouve le
     * comportement antérieur sans avoir à distinguer les cas.
     *
     * @param list<DOMElement> $generateurs
     *
     * @return list<DOMElement>
     */
    public static function principaux(array $generateurs, NodeAccessor $accessor): array
    {
        $priorites = self::priorites($generateurs, $accessor);
        if ($priorites === null) {
            return $generateurs;
        }

        $minimale = min($priorites);

        return array_values(array_filter(
            $generateurs,
            static fn(DOMElement $gen, int $i): bool => $priorites[$i] === $minimale,
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /**
     * Vrai lorsque les générateurs forment une cascade **avec** priorité,
     * c'est-à-dire qu'au moins l'un d'eux est subordonné à un autre.
     *
     * @param list<DOMElement> $generateurs
     */
    public static function avecPriorite(array $generateurs, NodeAccessor $accessor): bool
    {
        return self::priorites($generateurs, $accessor) !== null;
    }

    /**
     * Priorités déclarées, ou null si elles ne décrivent pas une cascade
     * hiérarchisée : moins de deux générateurs, une priorité manquante ou non
     * positive, ou une même valeur partout.
     *
     * @param list<DOMElement> $generateurs
     *
     * @return list<int>|null
     */
    private static function priorites(array $generateurs, NodeAccessor $accessor): ?array
    {
        if (count($generateurs) < 2) {
            return null;
        }

        $priorites = [];
        foreach ($generateurs as $gen) {
            $priorite = $accessor->getIntOrNull('./donnee_entree/priorite_generateur_cascade', $gen);
            if ($priorite === null || $priorite <= 0) {
                return null;
            }
            $priorites[] = $priorite;
        }

        return count(array_unique($priorites)) < 2 ? null : $priorites;
    }
}
