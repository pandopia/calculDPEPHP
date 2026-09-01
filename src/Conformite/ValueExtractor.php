<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Extrait, depuis un XML DPE, l'ensemble des valeurs feuilles calculées
 * (`<donnee_intermediaire>` et `<sortie>`) sous forme d'un tableau
 * `chemin canonique => valeur texte`.
 *
 * Contrairement au harness historique (`tests/EndToEndTest.php`), le chemin
 * porte un **index positionnel** dès qu'un parent possède plusieurs enfants de
 * même nom. Sans cet index, les N murs d'un logement s'écrasent mutuellement
 * dans le tableau de comparaison et seule la dernière occurrence est réellement
 * vérifiée.
 *
 * @xml-input  dpe/logement/**\/donnee_intermediaire, dpe/logement/sortie
 */
final class ValueExtractor
{
    /** Conteneurs dont on extrait les feuilles. */
    private const CONTAINERS = ['donnee_intermediaire', 'sortie'];

    /**
     * Collections dont l'ordre est produit par le moteur et n'a donc aucune
     * raison de coïncider avec celui de la référence : on les indexe par leur
     * clé métier au lieu de leur position, sinon la comparaison oppose par
     * exemple l'électricité de la référence au gaz du moteur.
     *
     * nom d'élément ⇒ balise fille servant de clé.
     */
    private const SEMANTIC_KEYS = [
        'sortie_par_energie' => 'enum_type_energie_id',
    ];

    /**
     * @return array<string, string> chemin indexé ⇒ valeur texte brute
     */
    public function extract(DOMDocument $document): array
    {
        $out = [];
        $xpath = new DOMXPath($document);

        foreach (self::CONTAINERS as $container) {
            $nodes = $xpath->query('//' . $container);
            if ($nodes === false) {
                continue;
            }
            foreach ($nodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }
                // Un <sortie> imbriqué dans un <sortie> n'existe pas ; en
                // revanche <donnee_intermediaire> peut apparaître à plusieurs
                // niveaux : chacun est traité indépendamment via son chemin.
                $this->collect($node, $this->canonicalPath($node), $out);
            }
        }

        ksort($out);

        return $out;
    }

    /**
     * Métadonnées de traçabilité du cas (périmètre, régime réglementaire,
     * moteur de calcul de l'éditeur), lues dans l'en-tête ADEME.
     *
     * @return array<string, string|int|null>
     */
    public function metadata(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);

        $methode = $this->firstInt($xpath, '//caracteristique_generale/enum_methode_application_dpe_log_id');
        $date = $this->firstString($xpath, '//administratif/date_etablissement_dpe');

        return [
            'numero_dpe' => $this->firstString($xpath, '/dpe/numero_dpe'),
            'enum_methode_application_dpe_log_id' => $methode,
            'perimetre' => self::perimetre($methode),
            'date_etablissement_dpe' => $date,
            'regime_coef_ep_elec' => self::regimeCoefElec($date),
            'enum_version_id' => $this->firstString($xpath, '//administratif/enum_version_id'),
            'usr_logiciel_id' => $this->firstString($xpath, '//administratif/diagnostiqueur/usr_logiciel_id'),
            'version_moteur_calcul' => $this->firstString($xpath, '//administratif/diagnostiqueur/version_moteur_calcul'),
            'surface_habitable_logement' => $this->firstString($xpath, '//caracteristique_generale/surface_habitable_logement'),
        ];
    }

    /**
     * Périmètre d'évaluation au sens du règlement CSTB §1.1 (maison
     * individuelle / appartement / immeuble collectif / appartement à partir
     * des données de l'immeuble), déduit de
     * `enum_methode_application_dpe_log_id` (XSD ADEME).
     */
    public static function perimetre(?int $methode): string
    {
        return match (true) {
            $methode === null => 'inconnu',
            $methode === 1 => 'maison_individuelle',
            in_array($methode, [2, 3, 4, 5, 31, 32], true) => 'appartement_individuel',
            in_array($methode, [6, 7, 8, 9, 26, 27, 28, 29, 30], true) => 'immeuble_collectif',
            in_array($methode, [10, 11, 12, 13, 33, 34], true) => 'appartement_issu_immeuble',
            $methode >= 14 && $methode <= 25 => 'logement_neuf',
            default => 'inconnu',
        };
    }

    /**
     * Régime du coefficient de conversion en énergie primaire de l'électricité.
     * Le passage de 2,3 à 1,9 s'applique aux DPE établis à compter du
     * 1er janvier 2026 (arrêté modifiant l'arrêté du 31 mars 2021).
     */
    public static function regimeCoefElec(?string $dateEtablissement): string
    {
        if ($dateEtablissement === null || $dateEtablissement === '') {
            return 'inconnu';
        }

        return $dateEtablissement >= '2026-01-01' ? 'post_2026' : 'pre_2026';
    }

    /**
     * @param array<string, string> $out
     */
    private function collect(DOMElement $node, string $path, array &$out): void
    {
        $children = [];
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $children[] = $child;
            }
        }

        if ($children === []) {
            $out[$path] = $this->valueOf($node);
            return;
        }

        $counts = [];
        foreach ($children as $child) {
            $counts[$child->nodeName] = ($counts[$child->nodeName] ?? 0) + 1;
        }

        $seen = [];
        foreach ($children as $child) {
            $name = $child->nodeName;
            $seen[$name] = ($seen[$name] ?? 0) + 1;

            $suffix = '';
            if ($counts[$name] > 1) {
                $semantic = $this->semanticKey($child);
                $suffix = '[' . ($semantic ?? $seen[$name]) . ']';
            }

            $this->collect($child, $path . '/' . $name . $suffix, $out);
        }
    }

    /**
     * Clé métier d'un élément de collection non ordonnée, si elle est définie
     * et lisible ; `null` sinon (on retombe alors sur l'index positionnel).
     */
    private function semanticKey(DOMElement $child): ?string
    {
        $keyTag = self::SEMANTIC_KEYS[$child->nodeName] ?? null;
        if ($keyTag === null) {
            return null;
        }

        foreach ($child->childNodes as $node) {
            if ($node instanceof DOMElement && $node->nodeName === $keyTag) {
                $value = trim($node->textContent);

                return $value === '' ? null : $keyTag . '=' . $value;
            }
        }

        return null;
    }

    /**
     * `xsi:nil="true"` signale une donnée non saisie : on la distingue d'une
     * balise absente et d'une valeur vide en la normalisant explicitement.
     */
    private function valueOf(DOMElement $node): string
    {
        if ($node->getAttribute('nil') === 'true' || $node->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'nil') === 'true') {
            return '#nil';
        }

        return trim($node->textContent);
    }

    /** Chemin d'un nœud, avec index positionnel sur les fratries homonymes. */
    private function canonicalPath(DOMElement $node): string
    {
        $parts = [];
        $cursor = $node;

        while ($cursor instanceof DOMElement) {
            $name = $cursor->nodeName;
            $siblings = 0;
            $position = 0;
            $parent = $cursor->parentNode;

            if ($parent !== null) {
                foreach ($parent->childNodes as $sibling) {
                    if ($sibling instanceof DOMElement && $sibling->nodeName === $name) {
                        $siblings++;
                        if ($sibling === $cursor) {
                            $position = $siblings;
                        }
                    }
                }
            }

            $parts[] = $siblings > 1 ? $name . '[' . $position . ']' : $name;
            $cursor = $parent instanceof DOMElement ? $parent : null;
        }

        return implode('/', array_reverse($parts));
    }

    private function firstString(DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $value = trim($nodes->item(0)?->textContent ?? '');

        return $value === '' ? null : $value;
    }

    private function firstInt(DOMXPath $xpath, string $query): ?int
    {
        $value = $this->firstString($xpath, $query);

        return ($value === null || !is_numeric($value)) ? null : (int) $value;
    }
}
