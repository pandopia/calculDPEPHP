<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage\Rendement;

use CalculDpePHP\Xml\NodeAccessor;
use DOMElement;

/**
 * Lecture du rendement de génération d'un générateur de chauffage, quelle que
 * soit la balise qui le porte.
 *
 * Le XSD ADEME les définit l'une par rapport à l'autre :
 *
 *   `scop`                 « scop du générateur thermodynamique (en
 *                            remplacement du Rg pour les générateurs
 *                            thermodynamique) »
 *   `rendement_generation` « Rg : rendement de génération du système de
 *                            chauffage pour tous les générateurs sauf
 *                            thermodynamique »
 *
 * Les deux occupent donc la même place dans Ich, et un générateur n'en publie
 * qu'une : une pompe à chaleur son SCOP, tout autre générateur son Rg.
 *
 * @spec-section 12.4
 * @spec-pages   76-78
 * @xml-input    generateur_chauffage.donnee_intermediaire.{rendement_generation, scop}
 */
final class RendementGenerateur
{
    public static function lire(NodeAccessor $accessor, DOMElement $generateur): ?float
    {
        return $accessor->getFloatOrNull('./donnee_intermediaire/rendement_generation', $generateur)
            ?? $accessor->getFloatOrNull('./donnee_intermediaire/scop', $generateur);
    }
}
