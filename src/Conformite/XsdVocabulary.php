<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

/**
 * Vocabulaire des noms d'éléments déclarés par le schéma ADEME.
 *
 * Le règlement d'évaluation (§3) vérifie « la conformité de la structure des
 * fichiers XML destinés à l'ADEME ». Une validation `schemaValidate` complète
 * n'est pas exploitable ici : le XSD versionné dans le dépôt ne correspond plus
 * à la structure des XML réellement publiés par l'observatoire (les fichiers de
 * référence eux-mêmes échouent). On se limite donc à un contrôle de
 * vocabulaire, qui reste suffisant pour détecter une balise inventée par le
 * moteur — cas qui rendrait le fichier inexploitable par l'ADEME.
 *
 * @spec-source resources/ademe_DPE.xsd
 */
final class XsdVocabulary
{
    /** @var array<string, array<string, true>> mémoïsation par chemin de schéma */
    private static array $cache = [];

    /** @return array<string, true> */
    public static function load(string $xsdPath): array
    {
        if (isset(self::$cache[$xsdPath])) {
            return self::$cache[$xsdPath];
        }

        $names = [];
        if (is_file($xsdPath)) {
            $content = (string) file_get_contents($xsdPath);
            if (preg_match_all('/<xs:element\s[^>]*name="([^"]+)"/', $content, $m) > 0) {
                foreach ($m[1] as $name) {
                    $names[$name] = true;
                }
            }
        }

        return self::$cache[$xsdPath] = $names;
    }

    /**
     * Balises produites par le moteur qui n'existent ni dans le schéma, ni dans
     * le XML de référence du cas.
     *
     * Le XSD versionné ici est antérieur aux XML réellement publiés (il ignore
     * par exemple `numero_dpe`). Les noms lus dans la référence sont donc
     * ajoutés au vocabulaire : une balise que l'observatoire publie est
     * légitime, quoi qu'en dise ce schéma. Ne restent signalées que les balises
     * qu'aucune des deux sources ne connaît — donc inventées par le moteur.
     *
     * @param array<string, string> $emitted chemins ⇒ valeurs produites
     * @param array<string, string> $reference chemins ⇒ valeurs de la référence
     * @return list<string> noms de balises, dédoublonnés et triés
     */
    public static function unknownElements(array $emitted, string $xsdPath, array $reference = []): array
    {
        $known = self::load($xsdPath);
        if ($known === []) {
            return [];
        }

        foreach (self::segments(array_keys($reference)) as $name) {
            $known[$name] = true;
        }

        $unknown = [];
        foreach (self::segments(array_keys($emitted)) as $name) {
            if (!isset($known[$name])) {
                $unknown[$name] = true;
            }
        }

        $out = array_keys($unknown);
        sort($out);

        return $out;
    }

    /**
     * Noms d'éléments traversés par une liste de chemins, index positionnels
     * ou sémantiques retirés.
     *
     * @param list<string> $paths
     * @return list<string>
     */
    private static function segments(array $paths): array
    {
        $names = [];
        foreach ($paths as $path) {
            foreach (explode('/', $path) as $segment) {
                $name = (string) preg_replace('/\[[^\]]*\]$/', '', $segment);
                if ($name !== '') {
                    $names[$name] = true;
                }
            }
        }

        return array_keys($names);
    }
}
