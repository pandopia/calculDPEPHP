<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

/**
 * Contrôle structurel du XML produit, à partir du schéma ADEME.
 *
 * Le règlement d'évaluation (§3) vérifie « la conformité de la structure des
 * fichiers XML destinés à l'ADEME ». Une validation `schemaValidate` complète
 * n'est pas exploitable ici : le XSD versionné dans le dépôt est antérieur aux
 * XML réellement publiés par l'observatoire — les fichiers de référence
 * eux-mêmes échouent, faute de `numero_dpe` par exemple.
 *
 * Le contrôle porte donc sur les **chemins**. La hiérarchie des déclarations
 * `xs:element` prime sur les `<xs:appinfo source="…">` parfois périmées
 * (seconde fenêtre déplacée dans les XSD ADEME récents), ce qui permet de repérer
 * non seulement une balise inventée, mais aussi une balise réelle écrite au
 * mauvais endroit — cas que le contrôle par nom laissait passer (voir
 * TASK-K14 : `enum_classe_inertie_id` écrit en `donnee_intermediaire` du
 * logement alors que le schéma ne le connaît que sous `<enveloppe><inertie>`).
 *
 * Pour ne pas transformer l'obsolescence du schéma en faux positifs, les
 * chemins réellement présents dans le fichier de référence du cas sont ajoutés
 * au vocabulaire : ce que l'observatoire publie est légitime, quoi qu'en dise
 * ce XSD.
 *
 * @spec-source resources/ademe_DPE.xsd
 */
final class XsdVocabulary
{
    /** @var array<string, array<string, true>> mémoïsation par chemin de schéma */
    private static array $cache = [];

    /**
     * Chemins déclarés par le schéma.
     *
     * @return array<string, true>
     */
    public static function load(string $xsdPath): array
    {
        if (isset(self::$cache[$xsdPath])) {
            return self::$cache[$xsdPath];
        }

        $paths = [];
        if (is_file($xsdPath)) {
            $schema = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            try {
                $loaded = $schema->load($xsdPath, LIBXML_NONET);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            if ($loaded) {
                $xpath = new \DOMXPath($schema);
                $xpath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
                foreach ($xpath->query('//xs:appinfo[@source]') as $annotation) {
                    $path = self::declaredPath($annotation) ?? $annotation->getAttribute('source');
                    $paths[$path] = true;
                }
            }
        }

        return self::$cache[$xsdPath] = $paths;
    }

    /**
     * Chemin réel d'une déclaration locale sous la racine DPE.
     *
     * Les annotations des éléments globaux réutilisés (administratif,
     * adresses…) restent le repli : leur parent ne donne pas le chemin de
     * chaque référence. Les sorties calculées sont déclarées localement.
     */
    private static function declaredPath(\DOMElement $annotation): ?string
    {
        $declaration = $annotation->parentNode?->parentNode;
        if (!$declaration instanceof \DOMElement
            || $declaration->namespaceURI !== 'http://www.w3.org/2001/XMLSchema'
            || $declaration->localName !== 'element'
            || !$declaration->hasAttribute('name')) {
            return null;
        }
        $names = [];
        for ($cursor = $declaration; $cursor instanceof \DOMElement; $cursor = $cursor->parentNode) {
            if ($cursor->namespaceURI === 'http://www.w3.org/2001/XMLSchema'
                && $cursor->localName === 'element' && $cursor->hasAttribute('name')) {
                array_unshift($names, $cursor->getAttribute('name'));
            }
        }
        return ($names[0] ?? null) === 'dpe' ? implode('/', $names) : null;
    }

    /**
     * Chemins produits par le moteur que ni le schéma ni la référence ne
     * connaissent.
     *
     * @param array<string, string> $emitted chemins indexés ⇒ valeurs produites
     * @param array<string, string> $reference chemins indexés ⇒ valeurs de la référence
     * @return list<string> chemins canoniques, dédoublonnés et triés
     */
    public static function unknownElements(array $emitted, string $xsdPath, array $reference = []): array
    {
        $declared = self::load($xsdPath);
        if ($declared === []) {
            return [];
        }

        $known = [];
        foreach ($reference as $path => $_) {
            $known[self::canonical($path)] = true;
        }

        $unknown = [];
        foreach (array_keys($emitted) as $path) {
            $canonical = self::canonical($path);
            if (isset($known[$canonical]) || isset($unknown[$canonical])) {
                continue;
            }
            if (!self::isDeclared($canonical, $declared)) {
                $unknown[$canonical] = true;
            }
        }

        $out = array_keys($unknown);
        sort($out);

        return $out;
    }

    /**
     * Le schéma note certains chemins sans le préfixe racine `dpe/` : un
     * chemin est reconnu s'il est déclaré tel quel, ou s'il se termine par un
     * chemin déclaré.
     *
     * @param array<string, true> $declared
     */
    private static function isDeclared(string $canonical, array $declared): bool
    {
        if (isset($declared[$canonical])) {
            return true;
        }

        foreach (array_keys($declared) as $path) {
            if (str_ends_with($canonical, '/' . $path)) {
                return true;
            }
        }

        return false;
    }

    /** Chemin sans les index positionnels ou sémantiques. */
    private static function canonical(string $path): string
    {
        return (string) preg_replace('/\[[^\]]*\]/', '', $path);
    }
}
