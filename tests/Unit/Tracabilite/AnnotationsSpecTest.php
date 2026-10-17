<?php

declare(strict_types=1);

namespace Tests\Unit\Tracabilite;

use PHPUnit\Framework\TestCase;

/**
 * La règle d'or n° 1 du projet — « si tu ne peux pas pointer une formule ou une
 * valeur de table vers un numéro de page de la spec, tu n'as pas le droit de la
 * coder » — ne vaut que si les annotations désignent quelque chose de réel.
 *
 * Ces trois contrôles sont bon marché et attrapent ce qui pourrit en silence :
 * une page hors de l'annexe, un `.md` renommé, une section de la méthode que
 * plus aucun calculateur ne revendique.
 */
final class AnnotationsSpecTest extends TestCase
{
    private const RACINE = __DIR__ . '/../../..';

    /** `resources/spec.pdf` — Annexe 1, méthode 3CL-DPE 2021. */
    private const PAGES_SPEC = 147;

    /** @return list<array{string, string}> [fichier, valeur de l'annotation] */
    private function annotations(string $tag): array
    {
        $trouvees = [];
        $racine = realpath(self::RACINE);
        foreach (['src', 'resources/tables'] as $dossier) {
            $iterateur = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($racine . '/' . $dossier),
            );
            foreach ($iterateur as $fichier) {
                if (!$fichier->isFile() || $fichier->getExtension() !== 'php') {
                    continue;
                }
                $contenu = file_get_contents($fichier->getPathname());
                if (!preg_match_all('/@' . $tag . '\s+(.+)$/m', $contenu, $m)) {
                    continue;
                }
                foreach ($m[1] as $valeur) {
                    $trouvees[] = [
                        str_replace($racine . '/', '', $fichier->getPathname()),
                        trim($valeur),
                    ];
                }
            }
        }

        return $trouvees;
    }

    /** Une page citée hors de l'annexe est une coquille, jamais une source. */
    public function testToutePageCiteeExisteDansLaSpec(): void
    {
        $annotations = $this->annotations('spec-pages');
        self::assertGreaterThan(100, count($annotations), 'les annotations ont disparu');

        foreach ($annotations as [$fichier, $valeur]) {
            preg_match_all('/\d+/', $valeur, $m);
            self::assertNotEmpty($m[0], "$fichier : @spec-pages « $valeur » ne cite aucune page");
            foreach ($m[0] as $page) {
                self::assertGreaterThanOrEqual(1, (int) $page, "$fichier : page $page");
                self::assertLessThanOrEqual(
                    self::PAGES_SPEC,
                    (int) $page,
                    "$fichier : la page $page dépasse les " . self::PAGES_SPEC . ' pages de la spec',
                );
            }
        }
    }

    /**
     * `@spec-source` désigne soit un fichier du dépôt, soit une URL de texte
     * réglementaire. Un chemin qui ne mène nulle part fait croire à une
     * traçabilité qui n'existe pas — c'était le cas de la table C1, qui
     * pointait un `00-calcul.md` jamais créé.
     */
    public function testToutCheminDeSourceExiste(): void
    {
        foreach ($this->annotations('spec-source') as [$fichier, $valeur]) {
            foreach (preg_split('/[\s,]+/', $valeur) ?: [] as $source) {
                if ($source === '' || str_starts_with($source, 'http')) {
                    continue;
                }
                if (!str_starts_with($source, 'resources/')) {
                    continue;
                }
                self::assertFileExists(
                    self::RACINE . '/' . $source,
                    "$fichier : @spec-source « $source » ne désigne aucun fichier",
                );
            }
        }
    }

    /**
     * Les sections que le moteur revendique doivent exister dans le découpage
     * de la spec, et réciproquement : une section de `specsplitted` que plus
     * aucun calculateur ne cite signale soit un calcul manquant, soit une
     * annotation périmée.
     */
    public function testChaqueSectionDecoupeeEstRevendiqueeParLeMoteur(): void
    {
        $citees = [];
        foreach ($this->annotations('spec-section') as [, $valeur]) {
            foreach (preg_match_all('/\d+(?:\.\d+)*/', $valeur, $m) ? $m[0] : [] as $num) {
                $citees[$num] = true;
            }
        }
        self::assertNotEmpty($citees);

        $orphelines = [];
        foreach (glob(self::RACINE . '/resources/specsplitted/*/**/*.md') ?: [] as $md) {
            if (!preg_match('/section_id:\s*"([\d.]+)"/', file_get_contents($md), $m)) {
                continue;
            }
            $section = $m[1];
            // PHP convertit les clés numériques en int : 18 redevient une chaîne.
            foreach (array_keys($citees) as $citee) {
                $citee = (string) $citee;
                if ($citee === $section
                    || str_starts_with($citee, $section . '.')
                    || str_starts_with($section, $citee . '.')) {
                    continue 2;
                }
            }
            $orphelines[] = $section;
        }

        self::assertSame([], $orphelines, 'sections sans @spec-section : ' . implode(', ', $orphelines));
    }
}
