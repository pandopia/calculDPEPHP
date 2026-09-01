<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

/**
 * Repère les écarts où c'est la **référence** qui est en tort.
 *
 * Le corpus n'est pas la vérité réglementaire : ce sont les sorties d'autres
 * logiciels, qui ont leurs propres défauts. Les copier ferait baisser le
 * compteur d'écarts tout en éloignant le moteur de la méthode. Ces cas sont
 * donc signalés à part, sans être retirés du taux de conformité : le taux
 * reste la distance brute au corpus, et le rapport indique en regard le
 * plafond réellement atteignable.
 *
 * Une suspicion n'est retenue que si elle est démontrable depuis le fichier de
 * référence lui-même, sans référence à notre propre calcul.
 */
final class ReferenceDefects
{
    /**
     * Postes dont la référence recopie parfois le coût conventionnel dans le
     * coût du scénario dépensier, alors que les deux consommations diffèrent.
     *
     * Le schéma ADEME est explicite : `cout_ch_depensier` est le « coût de
     * chauffage **pour le scénario dépensier** ». Un coût identique pour deux
     * consommations différentes contredit sa propre documentation.
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string}>
     *      [coût conventionnel, coût dépensier, conso conventionnelle, conso dépensière]
     */
    private const COUTS_DEPENSIER = [
        ['cout_ch', 'cout_ch_depensier', 'conso_ch', 'conso_ch_depensier'],
        ['cout_ecs', 'cout_ecs_depensier', 'conso_ecs', 'conso_ecs_depensier'],
        ['cout_fr', 'cout_fr_depensier', 'conso_fr', 'conso_fr_depensier'],
        [
            'cout_auxiliaire_generation_ch', 'cout_auxiliaire_generation_ch_depensier',
            'conso_auxiliaire_generation_ch', 'conso_auxiliaire_generation_ch_depensier',
        ],
        [
            'cout_auxiliaire_generation_ecs', 'cout_auxiliaire_generation_ecs_depensier',
            'conso_auxiliaire_generation_ecs', 'conso_auxiliaire_generation_ecs_depensier',
        ],
    ];

    private const COUT_PREFIX = 'dpe/logement/sortie/cout/';
    private const EF_PREFIX = 'dpe/logement/sortie/ef_conso/';
    private const CONFORT_ETE_PATH = 'dpe/logement/sortie/confort_ete';

    /**
     * Contenu du bloc `<sortie><confort_ete>` déclaré par le schéma ADEME.
     *
     * Le schéma rend le bloc facultatif (`minOccurs="0"`), mais
     * `protection_solaire_exterieure` y est obligatoire dès que le bloc est
     * présent. Une balise `<confort_ete></confort_ete>` vide viole donc le
     * schéma : le bloc est là, son contenu obligatoire non.
     *
     * @var list<string>
     */
    private const CONFORT_ETE_ENFANTS = [
        'enum_indicateur_confort_ete_id',
        'isolation_toiture',
        'protection_solaire_exterieure',
        'aspect_traversant',
        'brasseur_air',
        'inertie_lourde',
    ];

    /**
     * Balises dont l'écart est imputable à la référence, pour ce cas.
     *
     * @param array<string, string> $expected valeurs de la référence
     * @return array<string, string> nom de balise ⇒ motif
     */
    public static function detect(array $expected): array
    {
        $suspects = [];

        // Bloc confort d'été présent mais vide : le contenu que nous produisons
        // est conforme au schéma, c'est la référence qui ne l'est pas.
        if (($expected[self::CONFORT_ETE_PATH] ?? null) === '') {
            foreach (self::CONFORT_ETE_ENFANTS as $enfant) {
                $suspects[$enfant] = 'la référence écrit un bloc <confort_ete> vide, '
                    . 'alors que son schéma y rend protection_solaire_exterieure obligatoire';
            }
        }

        foreach (self::COUTS_DEPENSIER as [$cout, $coutDep, $conso, $consoDep]) {
            $vCout = self::num($expected, self::COUT_PREFIX . $cout);
            $vCoutDep = self::num($expected, self::COUT_PREFIX . $coutDep);
            $vConso = self::num($expected, self::EF_PREFIX . $conso);
            $vConsoDep = self::num($expected, self::EF_PREFIX . $consoDep);

            if ($vCout === null || $vCoutDep === null || $vConso === null || $vConsoDep === null) {
                continue;
            }
            if ($vCout == 0.0 || $vConso == 0.0) {
                continue;
            }
            if (abs($vCout - $vCoutDep) / abs($vCout) > 1e-9) {
                continue;
            }
            if (abs($vConso - $vConsoDep) / abs($vConso) <= 1e-6) {
                continue;
            }

            $suspects[$coutDep] = sprintf(
                'la référence recopie %s dans %s alors que les consommations diffèrent (%.0f vs %.0f kWh)',
                $cout,
                $coutDep,
                $vConso,
                $vConsoDep,
            );
        }

        return $suspects;
    }

    /** @param array<string, string> $values */
    private static function num(array $values, string $path): ?float
    {
        $v = $values[$path] ?? null;

        return ($v === null || !is_numeric($v)) ? null : (float) $v;
    }
}
