<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

use DOMDocument;
use DOMElement;
use DOMXPath;

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
    private const APPORT_PREFIX = 'dpe/logement/sortie/apport_et_besoin/';
    private const CONFORT_ETE_PATH = 'dpe/logement/sortie/confort_ete';

    /** Postes qui composent le total des auxiliaires en énergie finale. */
    private const AUXILIAIRES_EF = [
        'conso_auxiliaire_generation_ch',
        'conso_auxiliaire_distribution_ch',
        'conso_auxiliaire_generation_ecs',
        'conso_auxiliaire_distribution_ecs',
        'conso_auxiliaire_distribution_fr',
        'conso_auxiliaire_ventilation',
    ];

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
     * Écarts imputables à la référence, pour ce cas.
     *
     * @param array<string, string> $expected valeurs de la référence
     * @param DOMDocument|null $referenceDoc document de référence, pour les
     *        règles qui doivent inspecter les données d'entrée
     * @return array<string, string> nom de balise **ou chemin** ⇒ motif
     */
    public static function detect(array $expected, ?DOMDocument $referenceDoc = null): array
    {
        $suspects = $referenceDoc === null ? [] : self::installationsEcsIndiscernables($referenceDoc);
        if ($referenceDoc !== null) {
            $suspects += self::rendementStockageDuScenarioDepensier($referenceDoc);
        }

        $suspects += self::besoinsDepensiersIncoherents($expected);
        $suspects += self::totalAuxiliaireIncoherent($expected);

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

    /**
     * Besoins dépensiers incompatibles avec leur définition réglementaire.
     *
     * §9.1 fixe une consigne de 21 °C pour le scénario dépensier, contre 19 °C
     * pour le conventionnel : son besoin de chauffage ne peut pas être plus
     * faible. §11.1 emploie respectivement 79 et 56 litres à 40 °C par unité
     * d'occupation ; tous les autres termes étant communs, les besoins ECS
     * annuels doivent respecter Becs_dep = Becs × 79/56.
     *
     * @param array<string, string> $expected
     * @return array<string, string>
     */
    private static function besoinsDepensiersIncoherents(array $expected): array
    {
        $suspects = [];
        $bch = self::num($expected, self::APPORT_PREFIX . 'besoin_ch');
        $bchDep = self::num($expected, self::APPORT_PREFIX . 'besoin_ch_depensier');
        if ($bch !== null && $bchDep !== null && $bch > 0.0 && $bchDep < $bch) {
            $suspects['besoin_ch_depensier'] = sprintf(
                'la référence publie un besoin de chauffage dépensier inférieur au conventionnel '
                . '(%.0f vs %.0f), malgré les consignes réglementaires de 21 °C et 19 °C',
                $bchDep,
                $bch,
            );
        }

        $becs = self::num($expected, self::APPORT_PREFIX . 'besoin_ecs');
        $becsDep = self::num($expected, self::APPORT_PREFIX . 'besoin_ecs_depensier');
        if ($becs !== null && $becsDep !== null && $becs > 0.0) {
            $attendu = $becs * 79.0 / 56.0;
            if (abs($becsDep - $attendu) / $attendu > 0.01) {
                $suspects['besoin_ecs_depensier'] = sprintf(
                    'la référence viole Becs_dep = Becs × 79/56 (§11.1) : %.0f publié au lieu de %.0f',
                    $becsDep,
                    $attendu,
                );
            }
        }

        return $suspects;
    }

    /**
     * Total d'auxiliaires qui ne correspond pas à la somme de ses postes.
     *
     * @param array<string, string> $expected
     * @return array<string, string>
     */
    private static function totalAuxiliaireIncoherent(array $expected): array
    {
        $total = self::num($expected, self::EF_PREFIX . 'conso_totale_auxiliaire');
        if ($total === null) {
            return [];
        }

        $somme = 0.0;
        $trouves = 0;
        foreach (self::AUXILIAIRES_EF as $poste) {
            $valeur = self::num($expected, self::EF_PREFIX . $poste);
            if ($valeur !== null) {
                $somme += $valeur;
                $trouves++;
            }
        }
        if ($trouves === 0 || abs($total - $somme) <= max(0.1, abs($somme) * 0.001)) {
            return [];
        }

        return [
            'conso_totale_auxiliaire' => sprintf(
                'la référence publie un total auxiliaire de %.1f kWh, incompatible avec la somme '
                . 'de ses postes (%.1f kWh)',
                $total,
                $somme,
            ),
        ];
    }

    /**
     * Rendement de stockage sérialisé pour le mauvais scénario.
     *
     * Pour un ballon électrique simple, les consommations publiées permettent
     * de retrouver sans hypothèse Rs_conv = Becs/(Cecs×Rd) et
     * Rs_dep = Becs_dep/(Cecs_dep×Rd). Si la balise unique `rendement_stockage`
     * est égale au second alors qu'elle diffère du premier, la référence a
     * conservé le résultat du dernier passage dépensier ; sa propre
     * consommation conventionnelle contredit donc la valeur sérialisée.
     *
     * @return array<string, string>
     */
    private static function rendementStockageDuScenarioDepensier(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $installations = $xpath->query('//installation_ecs');
        if ($installations === false) {
            return [];
        }

        $suspects = [];
        $nombreInstallations = $installations->length;
        foreach ($installations as $index => $installation) {
            if (!$installation instanceof DOMElement) {
                continue;
            }
            $generateurs = $xpath->query('./generateur_ecs_collection/generateur_ecs', $installation);
            if ($generateurs === false || $generateurs->length !== 1) {
                continue;
            }
            $generateur = $generateurs->item(0);
            if (!$generateur instanceof DOMElement) {
                continue;
            }
            $type = (int)$xpath->evaluate('string(./donnee_entree/enum_type_generateur_ecs_id)', $generateur);
            if (!in_array($type, [68, 69, 70, 71], true)) {
                continue;
            }

            $rd = (float)$xpath->evaluate('string(./donnee_intermediaire/rendement_distribution)', $installation);
            $becs = (float)$xpath->evaluate('string(./donnee_intermediaire/besoin_ecs)', $installation);
            $becsDep = (float)$xpath->evaluate('string(./donnee_intermediaire/besoin_ecs_depensier)', $installation);
            $cecs = (float)$xpath->evaluate('string(./donnee_intermediaire/conso_ecs)', $installation);
            $cecsDep = (float)$xpath->evaluate('string(./donnee_intermediaire/conso_ecs_depensier)', $installation);
            $rsPublie = (float)$xpath->evaluate('string(./donnee_intermediaire/rendement_stockage)', $generateur);
            if (min($rd, $becs, $becsDep, $cecs, $cecsDep, $rsPublie) <= 0.0) {
                continue;
            }

            $rsConv = $becs / ($cecs * $rd);
            $rsDep = $becsDep / ($cecsDep * $rd);
            $procheDep = abs($rsPublie - $rsDep) / $rsDep <= 0.001;
            $loinConv = abs($rsPublie - $rsConv) / $rsConv > 0.01;
            if (!$procheDep || !$loinConv) {
                continue;
            }

            $cle = $nombreInstallations === 1 ? 'rendement_stockage' : 'rendement_stockage@' . ($index + 1);
            $suspects[$cle] = sprintf(
                'la référence sérialise Rs dépensier (%.4f) alors que sa consommation conventionnelle '
                . 'implique Rs=%.4f (§11.2 et §11.6)',
                $rsDep,
                $rsConv,
            );
        }

        return $suspects;
    }

    /**
     * Installations ECS aux données d'entrée identiques mais aux rendements de
     * stockage — et donc aux consommations ECS — différents.
     *
     * Sur un DPE issu d'un échantillonnage §17, la référence publie plusieurs
     * `installation_ecs` strictement identiques — mêmes surface, même volume
     * de stockage, même type de générateur, seule la `reference` horodatée
     * change — et leur attribue pourtant des `rendement_stockage` distincts.
     * Le rendement y suit la surface du logement visité dont l'installation
     * provient, mais **aucun élément du XML ne relie une installation à un
     * logement visité** : `logement_visite` ne porte que description, étage,
     * typologie et surface.
     *
     * L'écart n'est donc pas reproductible depuis les données publiées : des
     * entrées identiques doivent donner des sorties identiques. Toute règle qui
     * y parviendrait devinerait l'appariement. `conso_ecs` et
     * `conso_ecs_depensier` dépendent directement de ce rendement (§11.1) ; les
     * écarts correspondants du même générateur sont donc signalés avec leur
     * cause, sans être retirés du taux de conformité brut.
     *
     * @return array<string, string> chemin ⇒ motif
     */
    private static function installationsEcsIndiscernables(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $installations = $xpath->query('//installation_ecs');
        if ($installations === false || $installations->length < 2) {
            return [];
        }

        /** @var array<string, list<array{path: string, rs: string}>> $groupes */
        $groupes = [];
        $index = 0;
        foreach ($installations as $installation) {
            $index++;
            if (!$installation instanceof DOMElement) {
                continue;
            }
            $rsNodes = $xpath->query('.//generateur_ecs/donnee_intermediaire/rendement_stockage', $installation);
            if ($rsNodes === false || $rsNodes->length === 0) {
                continue;
            }
            $groupes[self::signature($xpath, $installation)][] = [
                'index' => $index,
                'rs' => trim($rsNodes->item(0)?->textContent ?? ''),
            ];
        }

        $suspects = [];
        foreach ($groupes as $membres) {
            if (count($membres) < 2) {
                continue;
            }
            $valeurs = array_unique(array_column($membres, 'rs'));
            if (count($valeurs) < 2) {
                continue;
            }
            $motif = sprintf(
                'la référence publie %d rendements de stockage différents pour %d installations ECS '
                . 'aux données d\'entrée identiques, sans qu\'aucun élément du XML ne les distingue',
                count($valeurs),
                count($membres),
            );
            foreach ($membres as $membre) {
                foreach (['rendement_stockage', 'conso_ecs', 'conso_ecs_depensier'] as $balise) {
                    $suspects[$balise . '@' . $membre['index']] = $motif;
                }
            }
        }

        return $suspects;
    }

    /**
     * Empreinte des données d'entrée d'une installation ECS, hors identifiants
     * (`reference`, `description`) qui n'ont pas d'effet sur le calcul.
     */
    private static function signature(DOMXPath $xpath, DOMElement $installation): string
    {
        $parts = [];
        $nodes = $xpath->query('./donnee_entree/*|.//generateur_ecs/donnee_entree/*', $installation);
        if ($nodes !== false) {
            foreach ($nodes as $node) {
                if (!$node instanceof DOMElement || in_array($node->nodeName, ['reference', 'description'], true)) {
                    continue;
                }
                $parts[] = $node->nodeName . '=' . trim($node->textContent);
            }
        }
        sort($parts);

        return implode('|', $parts);
    }

    /** @param array<string, string> $values */
    private static function num(array $values, string $path): ?float
    {
        $v = $values[$path] ?? null;

        return ($v === null || !is_numeric($v)) ? null : (float) $v;
    }
}
