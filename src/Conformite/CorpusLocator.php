<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

use XMLReader;

/**
 * Découvre les cas de test disponibles.
 *
 * Deux dispositions sont reconnues :
 *
 * 1. `resources/XML/official/<jeu>/{input,expected}/` — jeux officiels ou
 *    publics intégrés tels quels, avec leur `metadata/manifest.json`.
 * 2. `resources/XML/{input,verif}/` — corpus historique du dépôt, exposé sous
 *    le nom `ademe-observatoire-local`.
 */
final class CorpusLocator
{
    public const LEGACY_CORPUS = 'ademe-observatoire-local';

    /**
     * Références écartées du corpus car **démontrées fausses**.
     *
     * À ne pas confondre avec {@see ReferenceDefects}, qui isole des *balises*
     * dont l'écart est imputable à la référence tout en les laissant comptées :
     * la mesure brute ne se maquille pas. Ici, le fichier entier est retiré,
     * parce que sa sortie est contradictoire avec elle-même ou avec le schéma
     * ADEME au point de ne plus constituer une référence exploitable.
     *
     * Le critère est la **preuve**, établie sur le fichier de référence seul,
     * sans invoquer notre calcul : un simple désaccord avec notre moteur, ou
     * avec le reste du corpus, n'en est pas une. Chaque entrée porte sa preuve.
     *
     * Un cas retiré ici le reste même après un nouveau `bin/fetch-dpe`.
     *
     * @var array<string, string> nom de fichier ⇒ preuve
     */
    public const REFERENCES_INVALIDES = [
        // Le fichier publie besoin_ecs deux fois, à deux échelles incompatibles :
        // installation_ecs/donnee_intermediaire/besoin_ecs = 1 150 715,8 et
        // sortie/apport_et_besoin/besoin_ecs = 1 150,72. Le XSD documente les
        // deux en kWh (« besoin d'ECS annuel pour l'ensemble de l'installation
        // (kWh) » et « besoin d'ecs annuel total du bâtiment (kWh) ») et le
        // logement ne porte qu'une installation d'ECS : les deux valeurs
        // devraient être égales. Elles diffèrent d'un facteur exactement 1 000.
        // Même décalage sur apport_interne_ch (2 168 306,9),
        // pertes_distribution_ecs_recup{,_depensier} et pertes_stockage_ecs_recup,
        // toutes documentées en kWh : ces sorties sont sérialisées en Wh.
        '2676E2269868T.xml' => "besoin_ecs publié deux fois, en kWh et en Wh (×1 000), "
            . 'sur un logement à une seule installation ECS — sortie contradictoire '
            . 'avec elle-même et avec les unités documentées par le XSD',
    ];

    public function __construct(private readonly string $projectRoot)
    {
    }

    /**
     * @return list<array{corpus: string, name: string, input: string, expected: string}>
     */
    public function locate(?string $corpusFilter = null, ?string $nameFilter = null): array
    {
        $cases = [];

        foreach ($this->officialCorpora() as $corpus => $dir) {
            foreach ($this->pair($corpus, $dir . '/input', $dir . '/expected') as $case) {
                $cases[] = $case;
            }
        }

        foreach ($this->pair(
            self::LEGACY_CORPUS,
            $this->projectRoot . '/resources/XML/input',
            $this->projectRoot . '/resources/XML/verif',
        ) as $case) {
            $cases[] = $case;
        }

        return array_values(array_filter($cases, static function (array $case) use ($corpusFilter, $nameFilter): bool {
            if ($corpusFilter !== null && $case['corpus'] !== $corpusFilter) {
                return false;
            }

            return $nameFilter === null || str_contains($case['name'], $nameFilter);
        }));
    }

    /** @return array<string, string> nom du jeu ⇒ répertoire */
    public function officialCorpora(): array
    {
        $root = $this->projectRoot . '/resources/XML/official';
        if (!is_dir($root)) {
            return [];
        }

        $out = [];
        foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (is_dir($dir . '/input')) {
                $out[basename($dir)] = $dir;
            }
        }
        ksort($out);

        return $out;
    }

    /**
     * @return list<array{corpus: string, name: string, input: string, expected: string}>
     */
    private function pair(string $corpus, string $inputDir, string $expectedDir): array
    {
        if (!is_dir($inputDir) || !is_dir($expectedDir)) {
            return [];
        }

        $cases = [];
        foreach (glob($inputDir . '/*.xml') ?: [] as $input) {
            $name = basename($input);

            // Sorties de travail produites par `bin/calcul-dpe`, pas des cas.
            if (str_contains($name, '.calculated.')) {
                continue;
            }

            $expected = $expectedDir . '/' . $name;
            if (!is_file($expected)) {
                continue;
            }

            // Référence démontrée fausse : hors mesure (cf. REFERENCES_INVALIDES).
            if (isset(self::REFERENCES_INVALIDES[$name])) {
                continue;
            }

            // Les DPE « logement neuf » (RT2012 / RE2020) relèvent d'une autre
            // méthode que le 3CL existant : hors périmètre du moteur.
            if (self::isLogementNeuf($expected)) {
                continue;
            }

            $cases[] = ['corpus' => $corpus, 'name' => $name, 'input' => $input, 'expected' => $expected];
        }

        usort($cases, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $cases;
    }

    public static function isLogementNeuf(string $path): bool
    {
        $reader = new XMLReader();
        if (!@$reader->open($path)) {
            return false;
        }

        $isNeuf = false;
        while (@$reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->name === 'logement_neuf') {
                $isNeuf = true;
                break;
            }
            if ($reader->name === 'logement') {
                break;
            }
        }
        $reader->close();

        return $isNeuf;
    }
}
