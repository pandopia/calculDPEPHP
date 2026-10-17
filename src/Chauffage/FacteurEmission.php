<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage;

use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Part de consommation portée par chaque émission d'une même installation
 * (§9.1.3 p.60).
 *
 * §9.1.3 pose `Cch = Σ_i (Sh_i / Sh × INT_i × Ich_i) × Bch` et précise le cas
 * visé : « les installations centralisées avec plusieurs émetteurs de types
 * différents ». Chaque émission porte **sa** surface, **son** intermittence et
 * **son** Ich. La somme de ces produits n'est pas le produit de leurs moyennes
 * — un plancher chauffant et des radiateurs n'ont ni le même i0 ni les mêmes
 * rendements — et c'est bien la somme que la méthode écrit.
 *
 * @spec-section 9.1.3
 * @spec-pages   60
 * @spec-source  resources/specsplitted/09-conso-chauffage/01-installation-seule/03-multi-emissions.md
 * @xml-input    emetteur_chauffage.donnee_entree.surface_chauffee
 *               emetteur_chauffage.donnee_intermediaire.{i0, rendement_emission,
 *               rendement_distribution, rendement_regulation}
 * @tables       (aucune)
 */
final class FacteurEmission
{
    /**
     * `Σ_i (Sh_i / Sh) × i0_i / (Re_i × Rd_i × Rr_i)`.
     *
     * Le diviseur d'intermittence `1 / (1 + 0,1 (G − 1))` est commun à tous
     * les émetteurs de l'installation : il reste à la charge de l'appelant,
     * comme le rendement de génération.
     *
     * Null dès qu'un émetteur n'est pas entièrement décrit, ou qu'il n'y en a
     * qu'un : la moyenne pondérée lui est alors exactement équivalente, et
     * mieux vaut ne pas changer de chemin de calcul pour rien.
     *
     * @spec-formula F-9.1.3-a
     */
    public static function parEmetteurOuNull(NodeAccessor $accessor, DOMElement $installation): ?float
    {
        $collection = null;
        foreach ($installation->childNodes as $enfant) {
            if ($enfant instanceof DOMElement && $enfant->nodeName === 'emetteur_chauffage_collection') {
                $collection = $enfant;
                break;
            }
        }
        if ($collection === null) {
            return null;
        }

        $surfaceTotale = 0.0;
        $somme = 0.0;
        $emetteurs = 0;

        foreach ($collection->childNodes as $emetteur) {
            if (!($emetteur instanceof DOMElement) || $emetteur->nodeName !== 'emetteur_chauffage') {
                continue;
            }

            $surface = $accessor->getFloatOrNull('./donnee_entree/surface_chauffee', $emetteur);
            if ($surface === null || $surface <= 0.0) {
                return null;
            }

            $ich = 1.0;
            foreach (['rendement_emission', 'rendement_distribution', 'rendement_regulation'] as $champ) {
                $valeur = $accessor->getFloatOrNull("./donnee_intermediaire/{$champ}", $emetteur);
                if ($valeur === null || $valeur <= 0.0) {
                    return null;
                }
                $ich *= $valeur;
            }

            $i0 = $accessor->getFloatOrNull('./donnee_intermediaire/i0', $emetteur);
            if ($i0 === null) {
                return null;
            }

            $surfaceTotale += $surface;
            $somme += $surface * $i0 / $ich;
            $emetteurs++;
        }

        if ($emetteurs < 2 || $surfaceTotale <= 0.0) {
            return null;
        }

        return $somme / $surfaceTotale;
    }
}
