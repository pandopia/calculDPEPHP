<?php

declare(strict_types=1);

namespace CalculDpePHP\Xml;

use DOMDocument;
use DOMElement;

/**
 * Purge les balises `<donnee_intermediaire>` et `<sortie>` d'un document DPE
 * (idempotence du moteur), en PRÉSERVANT les caractéristiques réellement
 * saisies par le diagnostiqueur.
 *
 * Selon `enum_methode_saisie_carac_sys_id` (XSD, donnee_entree des générateurs),
 * certaines valeurs de `<donnee_intermediaire>` sont des ENTRÉES saisies à partir
 * de la plaque signalétique et non des résultats de calcul (les logiciels comme
 * LICIEL les stockent uniquement en donnee_intermediaire) :
 *   2 → pn ; 3 → pn, rpn, rpint ; 4 → + qp0 ; 5 → + temp_fonc_30/100 ;
 *   6 → scop/cop/eer (thermodynamique) ; 8 → eer/seer (climatisation).
 * Les purger ferait perdre une donnée d'entrée irrécupérable.
 *
 * @spec-section 13.2.2, 14.1
 * @spec-pages   86-95
 * @spec-source  resources/ademe_DPE.xsd (appinfo enum_methode_saisie_carac_sys_id)
 */
final class OutputPurger
{
    /** Parents dont le donnee_intermediaire peut contenir des valeurs saisies */
    private const PARENTS_SAISIE = ['generateur_chauffage', 'generateur_ecs', 'climatisation'];

    /**
     * Champs de donnee_intermediaire saisis par le diagnostiqueur, par méthode de saisie.
     * (pveilleuse : saisie possible dès qu'on n'est plus en valeurs forfaitaires — open3cl)
     */
    private const CHAMPS_SAISIS = [
        2 => ['pn', 'pveilleuse'],
        3 => ['pn', 'pveilleuse', 'rpn', 'rpint'],
        4 => ['pn', 'pveilleuse', 'rpn', 'rpint', 'qp0'],
        5 => ['pn', 'pveilleuse', 'rpn', 'rpint', 'qp0', 'temp_fonc_30', 'temp_fonc_100'],
        6 => ['scop', 'cop', 'eer'],
        8 => ['eer', 'seer'],
    ];

    /**
     * Supprime les balises donnee_intermediaire / sortie du document,
     * sauf les champs saisis (cf. CHAMPS_SAISIS). Retourne le nombre de
     * nœuds supprimés (balises complètes ou champs individuels).
     */
    public static function purge(DOMDocument $document): int
    {
        $removed = 0;
        foreach (['donnee_intermediaire', 'sortie'] as $tag) {
            $nodes = [];
            foreach ($document->getElementsByTagName($tag) as $n) {
                if ($n instanceof DOMElement) {
                    $nodes[] = $n;
                }
            }
            foreach ($nodes as $node) {
                $keep = $tag === 'donnee_intermediaire' ? self::champsAPreserver($node) : [];
                if ($keep === []) {
                    $node->parentNode?->removeChild($node);
                    $removed++;
                    continue;
                }
                // Purge sélective : ne retirer que les champs calculés
                $children = [];
                foreach ($node->childNodes as $child) {
                    if ($child instanceof DOMElement) {
                        $children[] = $child;
                    }
                }
                $kept = 0;
                foreach ($children as $child) {
                    if (in_array($child->nodeName, $keep, true) && trim($child->textContent) !== '') {
                        $kept++;
                        continue;
                    }
                    $node->removeChild($child);
                    $removed++;
                }
                if ($kept === 0) {
                    $node->parentNode?->removeChild($node);
                }
            }
        }

        return $removed;
    }

    /**
     * Liste des champs saisis à préserver pour ce donnee_intermediaire,
     * selon la méthode de saisie déclarée dans le donnee_entree frère.
     *
     * @return list<string>
     */
    private static function champsAPreserver(DOMElement $diNode): array
    {
        $parent = $diNode->parentNode;
        if (!$parent instanceof DOMElement || !in_array($parent->nodeName, self::PARENTS_SAISIE, true)) {
            return [];
        }

        $methode = null;
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'donnee_entree') {
                foreach ($child->childNodes as $deChild) {
                    if ($deChild instanceof DOMElement && $deChild->nodeName === 'enum_methode_saisie_carac_sys_id') {
                        $methode = (int)trim($deChild->textContent);
                        break 2;
                    }
                }
            }
        }

        return $methode !== null ? (self::CHAMPS_SAISIS[$methode] ?? []) : [];
    }
}
