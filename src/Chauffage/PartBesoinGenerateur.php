<?php

declare(strict_types=1);

namespace CalculDpePHP\Chauffage;

use CalculDpePHP\Engine\CalculationContext;
use DOMElement;

/**
 * Part du besoin de chauffage couverte par un générateur, partagée entre les
 * stratégies de §9 qui la calculent et `AuxGenerationCalculator` qui la
 * consomme.
 *
 * §15.1.1 p.97 : « Bch_g : besoin annuel d'énergie assuré par le générateur »,
 * et « dans les cas où le générateur n'assure pas 100 % du besoin, seule la
 * part du besoin qu'il couvre est prise en compte ».
 *
 * Les stratégies connaissent déjà cette part — 0,75 et 0,25 entre un système
 * principal et son appoint (§9.3, §9.6), le prorata de Pn d'une cascade
 * (§13.2.1.3.2), la coupure base/appoint de §9.10 — mais seule la cascade la
 * publiait. Sans elle, chaque générateur se voit attribuer le besoin entier et
 * une installation à deux générateurs compte ses auxiliaires deux fois.
 *
 * @spec-section 15.1.1
 * @spec-pages   97
 * @spec-source  resources/specsplitted/15-auxiliaires/01-aux-generation.md
 */
final class PartBesoinGenerateur
{
    public const CLE_CONTEXTE = 'chauffage.part_besoin_generateur';

    public static function publier(CalculationContext $context, DOMElement $generateur, float $part): void
    {
        $parts = (array)$context->get(self::CLE_CONTEXTE, []);
        $parts[$generateur->getNodePath()] = $part;
        $context->set(self::CLE_CONTEXTE, $parts);
    }
}
