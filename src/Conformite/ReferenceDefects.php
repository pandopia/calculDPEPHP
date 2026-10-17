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

    /**
     * Agrégats qui, comme les coûts, sont à énergie donnée des multiples
     * fixes de la consommation finale : l'énergie primaire et les émissions
     * de gaz à effet de serre.
     *
     * @var list<array{0: string, 1: string}> [préfixe de bloc, racine de la grandeur]
     */
    private const AGREGATS_DEPENSIER = [
        ['dpe/logement/sortie/ep_conso/', 'ep_conso'],
        ['dpe/logement/sortie/emission_ges/', 'emission_ges'],
    ];

    /** `enum_methode_saisie_carac_sys_id` = 1 : « valeurs forfaitaires ». */
    private const SAISIE_FORFAITAIRE = 1;

    /**
     * Les seules valeurs de COP que §14.2 p.95 tabule, deux zones et trois
     * millésimes confondus.
     *
     * @var list<float>
     */
    private const COP_TABULES_14_2 = [2.0, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.8, 2.9];

    /**
     * `enum_type_doublage_id` dont §3.2.1.2 p.16 fixe la résistance à
     * 0,21 m².K/W : 4 « lame d'air de plus de 15 mm », 5 « matériau de
     * doublage connu (plâtre, brique, bois) ».
     *
     * @var list<int>
     */
    private const DOUBLAGES_A_021 = [4, 5];

    /**
     * `enum_methode_saisie_u0_id` qui passent par la table ou le forfait :
     * 1 « type de paroi inconnu (valeur par défaut) » et 2 « déterminé selon
     * le matériau et épaisseur à partir de la table de valeur forfaitaire ».
     * Les méthodes 3 et 4 saisissent U0 directement.
     *
     * @var list<int>
     */
    private const METHODES_U0_TABULE = [1, 2];

    /** 1 / (1/3,90 + 0,21), la plus grande valeur tabulée étant 3,90. */
    private const UMUR0_MAX_AVEC_DOUBLAGE = 2.144;

    /**
     * `enum_type_isolation_id` : 1 « inconnu », 2 « non isolé ». Les autres
     * valeurs déclarent une isolation.
     *
     * @var list<int>
     */
    private const ISOLATION_ABSENTE_OU_INCONNUE = [1, 2];

    private const COUT_PREFIX = 'dpe/logement/sortie/cout/';
    private const EF_PREFIX = 'dpe/logement/sortie/ef_conso/';
    private const APPORT_PREFIX = 'dpe/logement/sortie/apport_et_besoin/';
    private const CONFORT_ETE_PATH = 'dpe/logement/sortie/confort_ete';

    /**
     * Grandeurs que les formats historiques publient en Wh : apports, pertes
     * récupérées et besoin d'ECS porté par l'installation.
     */
    private const BALISES_EN_WATTHEURES = [
        'apport_interne_ch',
        'apport_solaire_ch',
        'fraction_apport_gratuit_ch',
        'fraction_apport_gratuit_depensier_ch',
        'pertes_distribution_ecs_recup',
        'pertes_distribution_ecs_recup_depensier',
        'pertes_generateur_ch_recup',
        'pertes_generateur_ch_recup_depensier',
        'pertes_stockage_ecs_recup',
        'besoin_ecs',
        'besoin_ecs_depensier',
    ];

    /**
     * Postes qui composent le total des auxiliaires, et nom du total, dans
     * chacun des quatre blocs de sortie qui les publient.
     *
     * Le suffixe des postes est commun ; seuls le préfixe du bloc et le nom du
     * total changent (`cout_total_auxiliaire` au singulier, les trois autres
     * `…_totale_auxiliaire`).
     *
     * @var list<array{0: string, 1: string, 2: string}> [préfixe de bloc, préfixe de poste, nom du total]
     */
    private const BLOCS_AUXILIAIRES = [
        ['dpe/logement/sortie/ef_conso/',     'conso_',        'conso_totale_auxiliaire'],
        ['dpe/logement/sortie/cout/',         'cout_',         'cout_total_auxiliaire'],
        ['dpe/logement/sortie/emission_ges/', 'emission_ges_', 'emission_ges_totale_auxiliaire'],
        ['dpe/logement/sortie/ep_conso/',     'ep_conso_',     'ep_conso_totale_auxiliaire'],
    ];

    /** Suffixes des postes qui composent le total des auxiliaires. */
    private const AUXILIAIRES_POSTES = [
        'auxiliaire_generation_ch',
        'auxiliaire_distribution_ch',
        'auxiliaire_generation_ecs',
        'auxiliaire_distribution_ecs',
        'auxiliaire_distribution_fr',
        'auxiliaire_ventilation',
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
            $suspects += self::pertesBallonIndividuelDiviseesParNombreLogements($referenceDoc);
            $suspects += self::qp0SerialiseEnKilowatts($referenceDoc);
            $suspects += self::stockageIntegreSerialiseCommeSepare($referenceDoc);
            $suspects += self::repartitionEcsAppartementNonReproductible($expected, $referenceDoc);
            $suspects += self::ventilationSansPuissanceMaisConsommatrice($expected, $referenceDoc);
            $suspects += self::bouclageEcsSansAuxiliaire($expected, $referenceDoc);
            $suspects += self::pontThermiqueKPorteDejaLaLongueur($referenceDoc);
            $suspects += self::apportsSerialisesEnWattheures($referenceDoc);
            $suspects += self::isolantDePlancherBasDeclareMaisIgnore($referenceDoc);
            $suspects += self::agregatIncompatibleAvecSaVentilation($referenceDoc);
            $suspects += self::mesureDEtancheiteDeclareeMaisIgnoree($referenceDoc);
            $suspects += self::productionSolaireIncompatibleAvecSaConso($referenceDoc);
            $suspects += self::doublageDeMurDeclareMaisIgnore($referenceDoc);
            $suspects += self::copForfaitaireHorsTable($referenceDoc);
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
            $suspects += self::valeurDepensiereRecopiee(
                $expected,
                self::COUT_PREFIX,
                $cout,
                $coutDep,
                $conso,
                $consoDep,
            );
        }

        foreach (self::AGREGATS_DEPENSIER as [$prefixe, $grandeur]) {
            foreach (['ch', 'ecs', 'fr'] as $usage) {
                $suspects += self::valeurDepensiereRecopiee(
                    $expected,
                    $prefixe,
                    $grandeur . '_' . $usage,
                    $grandeur . '_' . $usage . '_depensier',
                    'conso_' . $usage,
                    'conso_' . $usage . '_depensier',
                );
            }
        }

        return $suspects;
    }

    /**
     * §15.2.3 pp.98-102 : un circulateur de réseau ECS bouclé appelle au moins
     * 20 W, même hors puisage. §17.2.2.5.1 p.118 : sa consommation est aussi
     * répartie aux appartements générés par une clé de besoins positive.
     * Preuve sur la référence seule ; aucun résultat de notre moteur n'intervient.
     * @spec-source resources/specsplitted/15-auxiliaires/02-aux-distribution.md
     * @param array<string, string> $expected
     * @return array<string, string>
     */
    /**
     * Une grandeur du scénario dépensier recopiée du conventionnel alors que
     * les deux consommations diffèrent.
     *
     * Le coût, l'énergie primaire et les émissions sont, à énergie donnée, des
     * multiples fixes de la consommation finale : publier la même valeur pour
     * deux consommations différentes est une contradiction du fichier seul,
     * sans qu'aucun résultat de notre moteur n'intervienne.
     *
     * @param array<string, string> $expected
     * @return array<string, string>
     */
    private static function valeurDepensiereRecopiee(
        array $expected,
        string $prefixe,
        string $grandeur,
        string $grandeurDep,
        string $conso,
        string $consoDep,
    ): array {
        $valeur = self::num($expected, $prefixe . $grandeur);
        $valeurDep = self::num($expected, $prefixe . $grandeurDep);
        $vConso = self::num($expected, self::EF_PREFIX . $conso);
        $vConsoDep = self::num($expected, self::EF_PREFIX . $consoDep);

        if ($valeur === null || $valeurDep === null || $vConso === null || $vConsoDep === null) {
            return [];
        }
        if ($valeur == 0.0 || $vConso == 0.0) {
            return [];
        }
        if (abs($valeur - $valeurDep) / abs($valeur) > 1e-9) {
            return [];
        }
        if (abs($vConso - $vConsoDep) / abs($vConso) <= 1e-6) {
            return [];
        }

        return [
            $grandeurDep => sprintf(
                'la référence recopie %s dans %s alors que les consommations diffèrent (%.0f vs %.0f kWh)',
                $grandeur,
                $grandeurDep,
                $vConso,
                $vConsoDep,
            ),
        ];
    }

    private static function bouclageEcsSansAuxiliaire(array $expected, DOMDocument $doc): array
    {
        $xp = new DOMXPath($doc);
        $method = (int) $xp->evaluate('string(//enum_methode_application_dpe_log_id)');
        if (!in_array($method, [10, 11, 12, 13, 33, 34, 38, 39, 40], true)) { return []; }
        $installations = $xp->query('//installation_ecs[donnee_entree/enum_type_installation_id=2 and donnee_entree/enum_bouclage_reseau_ecs_id=2 and donnee_entree/cle_repartition_ecs>0 and donnee_entree/surface_habitable>0 and donnee_intermediaire/besoin_ecs>0 and not(donnee_entree/enum_type_installation_solaire_id)]');
        if ($installations->length === 0) { return []; }
        $result = [];
        foreach (self::BLOCS_AUXILIAIRES as [$prefix, $postPrefix]) {
            $path = $prefix . $postPrefix . 'auxiliaire_distribution_ecs';
            if (self::num($expected, $path) === 0.0) {
                $result[$path] = 'la référence publie zéro auxiliaire ECS malgré un réseau collectif bouclé, des besoins non nuls et une clé positive (§15.2.3 et §17.2.2.5.1)';
            }
        }
        return $result;
    }

    /**
     * QP0 publié en kW alors que le XSD impose des watts.
     *
     * Les pertes forfaitaires des chaudières valent typiquement 0,5 à 4 % de
     * Pn. Un couple tel que Pn=55 000 et QP0=0,55 ne devient physiquement
     * cohérent qu'après multiplication de QP0 par 1000 : l'éditeur a donc
     * sérialisé des kW dans un champ documenté en W.
     *
     * @return array<string, string>
     */
    /**
     * Balises d'apports et de pertes récupérées sérialisées en Wh alors que les
     * besoins du même bloc sont en kWh.
     *
     * Preuve sur le fichier seul : la somme des apports dépasse le besoin de
     * chauffage d'un facteur supérieur à 100 — impossible, le besoin étant ce
     * qui reste une fois les apports déduits — **et** la référence publie
     * `fraction_apport_gratuit_ch` exactement à 1, la valeur de saturation
     * qu'on obtient en divisant des wattheures par des kilowattheures. Les deux
     * ensemble ne s'expliquent que par un mélange d'unités à la sérialisation.
     *
     * Les formats 0.1.0, 8.x et 9.x publient légitimement ces grandeurs en Wh
     * (cf. `IntermediateEnergyUnit`) : leur fraction reste alors une valeur
     * ordinaire, et ce test ne les retient pas.
     *
     * @return array<string, string>
     */
    private static function apportsSerialisesEnWattheures(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $bloc = $xpath->query('//sortie/apport_et_besoin')?->item(0);
        if (!$bloc instanceof DOMElement) {
            return [];
        }

        $besoin = self::nombreEnfant($xpath, './besoin_ch', $bloc);
        $apports = (self::nombreEnfant($xpath, './apport_interne_ch', $bloc) ?? 0.0)
                 + (self::nombreEnfant($xpath, './apport_solaire_ch', $bloc) ?? 0.0);
        $fraction = self::nombreEnfant($xpath, './fraction_apport_gratuit_ch', $bloc);

        if ($besoin === null || $besoin <= 0.0 || $fraction === null) {
            return [];
        }
        if ($apports / $besoin <= 100.0 || abs($fraction - 1.0) > 1e-9) {
            return [];
        }

        $motif = sprintf(
            'la référence sérialise les apports et les pertes récupérées en Wh (%.0f) '
            . 'quand les besoins du même bloc sont en kWh (%.0f), et publie en conséquence '
            . 'une fraction d’apports gratuits saturée à 1',
            $apports,
            $besoin,
        );

        $suspects = [];
        foreach (self::BALISES_EN_WATTHEURES as $balise) {
            $suspects[$balise] = $motif;
        }

        return $suspects;
    }

    private static function qp0SerialiseEnKilowatts(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $generateurs = $xpath->query('//generateur_chauffage|//generateur_ecs');
        if ($generateurs === false) {
            return [];
        }

        foreach ($generateurs as $generateur) {
            if (!$generateur instanceof DOMElement) {
                continue;
            }
            $pn = (float)$xpath->evaluate('string(./donnee_intermediaire/pn)', $generateur);
            $qp0 = (float)$xpath->evaluate('string(./donnee_intermediaire/qp0)', $generateur);
            if ($pn < 1000.0 || $qp0 <= 0.0) {
                continue;
            }
            $tauxPublie = $qp0 / $pn;
            $tauxCorrige = 1000.0 * $qp0 / $pn;
            if ($tauxPublie < 0.0001 && $tauxCorrige >= 0.001 && $tauxCorrige <= 0.10) {
                return [
                    'qp0' => sprintf(
                        'la référence sérialise QP0 en kW (%.3f) malgré l’unité W imposée par le XSD ; '
                        . 'la valeur cohérente avec Pn=%.0f W est %.0f W',
                        $qp0,
                        $pn,
                        $qp0 * 1000.0,
                    ),
                ];
            }
        }

        return [];
    }

    /**
     * Ventilation de puissance moyenne nulle mais qui consomme.
     *
     * §5 p.41 lie les deux grandeurs : `Caux_vent = 8760 × Pventmoy / 1000`.
     * Une référence qui publie `pvent_moy = 0` tout en déclarant une
     * consommation d'auxiliaires de ventilation non nulle dans
     * `sortie/ef_conso` se contredit donc elle-même — le constat se fait sur le
     * fichier de référence seul, sans invoquer notre calcul.
     *
     * Le corpus montre le motif sur les exports du format natif ADEME, qui
     * écrivent la paire sentinelle `pvent_moy = 0` / `conso_auxiliaire_
     * ventilation = 1` dans `donnee_intermediaire` en laissant la vraie
     * consommation dans `sortie` : six ventilations sur les dix de ce format,
     * les quatre autres publiant des valeurs cohérentes. Ce n'est donc pas une
     * convention de format mais un défaut, à ne pas reproduire.
     *
     * @param array<string, string> $expected
     * @return array<string, string>
     */
    private static function ventilationSansPuissanceMaisConsommatrice(
        array $expected,
        DOMDocument $doc,
    ): array {
        $consoSortie = self::num($expected, self::EF_PREFIX . 'conso_auxiliaire_ventilation');
        if ($consoSortie === null || $consoSortie <= 0.0) {
            return [];
        }

        $xpath = new DOMXPath($doc);
        $ventilations = $xpath->query('//logement/ventilation_collection/ventilation');
        if ($ventilations === false || $ventilations->length === 0) {
            return [];
        }

        $motif = 'la référence publie pvent_moy = 0 alors que le même fichier déclare une '
            . 'consommation d\'auxiliaires de ventilation non nulle dans sortie/ef_conso, '
            . 'ce que §5 p.41 (Caux_vent = 8760 × Pventmoy / 1000) interdit';

        $suspects = [];
        foreach ($ventilations as $index => $ventilation) {
            if (!$ventilation instanceof DOMElement) {
                continue;
            }
            $pvent = self::nodeNum($xpath, './donnee_intermediaire/pvent_moy', $ventilation);
            if ($pvent === null || $pvent != 0.0) {
                continue;
            }
            $suffixe = $ventilations->length === 1 ? '' : '@' . ($index + 1);
            $suspects['pvent_moy' . $suffixe] = $motif;
            $suspects['conso_auxiliaire_ventilation' . $suffixe] = $motif;
        }

        return $suspects;
    }

    /**
     * §3.2.2 p.20 : l'arbre de décision du plancher bas ne rend `Upb = Upb0`
     * que sur la branche « Isolation ? Non ». Dès que la paroi est déclarée
     * isolée, Upb vaut `1 / (1/Upb0 + R)` si la résistance est saisie, et
     * `min(Upb0 ; Upb_tab)` sinon — donc strictement moins que Upb0, puisque
     * aucune valeur tabulée d'un plancher isolé n'excède celle du même
     * plancher nu.
     *
     * Un fichier qui déclare un type d'isolation et publie malgré tout
     * `upb = upb0` se contredit : il décrit un plancher isolé et en publie le
     * coefficient non isolé. Le corpus le confirme — sur 286 planchers bas
     * déclarés isolés, 281 publient bien `upb < upb0`.
     *
     * Preuve sur la référence seule, sans aucun résultat de notre moteur.
     *
     * @spec-section 3.2.2
     * @spec-pages   20
     * @spec-source  resources/specsplitted/03-enveloppe-deperditions/02-parois-opaques/02-upb/00-calcul.md
     * @return array<string, string>
     */
    private static function isolantDePlancherBasDeclareMaisIgnore(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);

        foreach ($xpath->query('//plancher_bas') as $plancher) {
            if (!$plancher instanceof DOMElement) {
                continue;
            }

            $isolation = self::nombreEnfant($xpath, './donnee_entree/enum_type_isolation_id', $plancher);
            if ($isolation === null || in_array((int)$isolation, self::ISOLATION_ABSENTE_OU_INCONNUE, true)) {
                continue;
            }

            $upb = self::nombreEnfant($xpath, './donnee_intermediaire/upb', $plancher);
            $upb0 = self::nombreEnfant($xpath, './donnee_intermediaire/upb0', $plancher);
            if ($upb === null || $upb0 === null || $upb0 <= 0.0 || abs($upb - $upb0) > 1e-9) {
                continue;
            }

            $resistance = self::nombreEnfant($xpath, './donnee_entree/resistance_isolation', $plancher);
            if ($resistance !== null && $resistance > 0.0) {
                return [
                    'upb' => sprintf(
                        'la référence déclare un plancher bas isolé (résistance %.2f m².K/W saisie et '
                        . 'justifiée) mais publie upb = upb0 = %.3f, le coefficient non isolé : §3.2.2 '
                        . 'p.20 impose upb = 1 / (1/upb0 + R) = %.3f',
                        $resistance,
                        $upb0,
                        1.0 / (1.0 / $upb0 + $resistance),
                    ),
                ];
            }

            return [
                'upb' => sprintf(
                    'la référence déclare un plancher bas isolé (enum_type_isolation_id = %d) mais '
                    . 'publie upb = upb0 = %.3f, le coefficient non isolé : §3.2.2 p.20 ne rend '
                    . 'upb = upb0 que sur la branche « Isolation ? Non »',
                    (int)$isolation,
                    $upb0,
                ),
            ];
        }

        return [];
    }

    /**
     * Grandeurs que `<sortie>` publie à la fois en agrégat et ventilées par
     * énergie dans `<sortie_par_energie_collection>`.
     *
     * @var list<array{0: string, 1: string}> [bloc d'agrégat, nom de la balise]
     */
    private const AGREGATS_VENTILES = [
        ['ef_conso', 'conso_ch'],
        ['ef_conso', 'conso_ecs'],
        ['ep_conso', 'ep_conso_ch'],
        ['cout', 'cout_ch'],
        ['emission_ges', 'emission_ges_ch'],
        ['emission_ges', 'emission_ges_ecs'],
    ];

    /**
     * Un agrégat de `<sortie>` que sa propre ventilation par énergie ne
     * reproduit pas.
     *
     * Le XSD définit `sortie_par_energie_collection` comme la décomposition de
     * `<sortie>` par type d'énergie : leur somme est l'agrégat, par
     * construction. Quand les deux divergent, le fichier se contredit — sans
     * qu'aucun résultat de notre moteur n'intervienne.
     *
     * Deux formes au corpus : une ventilation qui vaut exactement le double de
     * son agrégat, sur toutes les balises à la fois (2133E0741159K, dont les
     * lignes sont dupliquées), et une ventilation entièrement nulle sous un
     * agrégat positif (2659E2596108U, 2659E2236157N).
     *
     * @return array<string, string>
     */
    private static function agregatIncompatibleAvecSaVentilation(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $lignes = $xpath->query('//sortie_par_energie');
        if ($lignes === false || $lignes->length === 0) {
            return [];
        }

        $suspects = [];
        foreach (self::AGREGATS_VENTILES as [$bloc, $balise]) {
            $agregatNoeud = $xpath->query('//sortie/' . $bloc . '/' . $balise)->item(0);
            if (!$agregatNoeud instanceof DOMElement) {
                continue;
            }
            $agregat = (float)str_replace(',', '.', trim($agregatNoeud->textContent));
            if ($agregat <= 0.0) {
                continue;
            }

            $somme = 0.0;
            $publiees = 0;
            foreach ($lignes as $ligne) {
                if (!$ligne instanceof DOMElement) {
                    continue;
                }
                $valeur = self::nombreEnfant($xpath, './' . $balise, $ligne);
                if ($valeur !== null) {
                    $somme += $valeur;
                    $publiees++;
                }
            }
            if ($publiees === 0 || abs($somme - $agregat) <= 0.005 * $agregat) {
                continue;
            }

            $suspects[$balise] = sprintf(
                'la référence publie %s = %.1f mais sa propre ventilation par énergie en somme %.1f '
                . 'sur %d ligne(s) : le XSD définit sortie_par_energie comme la décomposition de '
                . '<sortie>, leur somme est l\'agrégat par construction',
                $balise,
                $agregat,
                $somme,
                $publiees,
            );
        }

        return $suspects;
    }

    /**
     * Méthodes de saisie de Q4Pa_conv qui déclarent une valeur mesurée :
     * 2 « mesure d'étanchéité à l'air de moins de deux ans » et 3 « mesure
     * d'étanchéité à l'air de la maison mère ».
     *
     * @var list<int>
     */
    private const SAISIES_Q4PA_MESUREES = [2, 3];

    /**
     * `production_ch_solaire` que la consommation du même bloc contredit.
     *
     * §9.2 p.62 et §9.6 p.65 écrivent les deux grandeurs sur le même produit :
     *
     *   Cch  = Bch × (1 − Fch) × Σ_i (part_i × INT_i × Ich_i)
     *   Prod = Bch ×      Fch  × Σ_i (part_i × INT_i × Ich_i)
     *
     * La somme, les rendements et le besoin s'éliminent entre les deux : il
     * reste `Prod / Cch = Fch / (1 − Fch)`, quelle que soit l'installation.
     * Les trois valeurs étant publiées côte à côte dans la même
     * `donnee_intermediaire`, la contradiction se démontre sur le seul fichier
     * de référence, sans aucun calcul de notre moteur.
     *
     * Huit installations du corpus sur les dix qui publient les trois balises
     * la violent, dans les deux sens — de 0,52 à 3,32 fois le rapport imposé.
     *
     * @return array<string, string>
     */
    private static function productionSolaireIncompatibleAvecSaConso(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);

        foreach ($xpath->query('//installation_chauffage') as $installation) {
            if (!$installation instanceof DOMElement) {
                continue;
            }

            $production = self::nombreEnfant($xpath, './donnee_intermediaire/production_ch_solaire', $installation);
            $conso = self::nombreEnfant($xpath, './donnee_intermediaire/conso_ch', $installation);
            $fch = self::nombreEnfant($xpath, './donnee_intermediaire/fch', $installation);
            if ($production === null || $conso === null || $fch === null
                || $conso <= 0.0 || $fch <= 0.0 || $fch >= 1.0) {
                continue;
            }

            $impose = $fch / (1.0 - $fch);
            $publie = $production / $conso;
            if (abs($publie - $impose) <= 0.01 * $impose) {
                continue;
            }

            return [
                'production_ch_solaire' => sprintf(
                    'la référence publie production_ch_solaire = %.3f et conso_ch = %.3f pour '
                    . 'fch = %.2f : §9.2 p.62 et §9.6 p.65 posent les deux sur le même produit '
                    . 'Bch × Σ(part × INT × Ich), d\'où Prod / Cch = Fch / (1 − Fch) = %.4f, '
                    . 'quand le fichier publie %.4f',
                    $production,
                    $conso,
                    $fch,
                    $impose,
                    $publie,
                ),
            ];
        }

        return [];
    }

    /**
     * Résistance de doublage déclarée, puis absente du `umur0` publié.
     *
     * §3.2.1.2 p.16 : « pour l'ensemble des parois, la présence d'un doublage
     * apporte une résistance thermique supplémentaire », avec
     * `Rdoublage` = 0,21 m².K/W pour une lame d'air de plus de 15 mm ou un
     * matériau connu — `enum_type_doublage_id` 4 et 5.
     *
     * La borne qui en découle est arithmétique : la plus grande valeur tabulée
     * de §3.2.1.2 est 3,90 W/(m².K) (briques pleines simples ≤ 9 cm, p.15), et
     * `1 / (1/3,90 + 0,21) = 2,144`. Un `umur0` publié au-dessus de cette
     * borne sur un mur qui déclare un tel doublage ne peut donc venir ni de la
     * table ni du forfait 2,5 des parois inconnues : la résistance déclarée
     * n'a pas été appliquée.
     *
     * La preuve n'exige que le fichier de référence : son
     * `enum_type_doublage_id`, sa méthode de saisie et son `umur0`. Elle ne
     * vaut que pour `Rdoublage` = 0,21 — avec 0,10 le plafond de 2,5 reste
     * atteignable (`1 / (1/3,90 + 0,10) = 2,806`), et il n'y a rien à prouver.
     *
     * @return array<string, string>
     */
    private static function doublageDeMurDeclareMaisIgnore(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);

        foreach ($xpath->query('//mur') as $mur) {
            if (!$mur instanceof DOMElement) {
                continue;
            }

            $doublage = self::nombreEnfant($xpath, './donnee_entree/enum_type_doublage_id', $mur);
            if ($doublage === null || !in_array((int) $doublage, self::DOUBLAGES_A_021, true)) {
                continue;
            }

            // Un U0 saisi et justifié échappe à la table : rien à démontrer.
            $methode = self::nombreEnfant($xpath, './donnee_entree/enum_methode_saisie_u0_id', $mur);
            if ($methode === null || !in_array((int) $methode, self::METHODES_U0_TABULE, true)) {
                continue;
            }

            $umur0 = self::nombreEnfant($xpath, './donnee_intermediaire/umur0', $mur);
            if ($umur0 === null || $umur0 <= self::UMUR0_MAX_AVEC_DOUBLAGE + 1e-9) {
                continue;
            }

            return [
                'umur0' => sprintf(
                    'la référence déclare un doublage (enum_type_doublage_id = %d, '
                    . 'Rdoublage = 0,21 m².K/W) mais publie umur0 = %.3f : §3.2.1.2 p.16 '
                    . 'plafonne alors Umur0 à 1 / (1/3,90 + 0,21) = %.3f, la plus grande '
                    . 'valeur tabulée étant 3,90 W/(m².K)',
                    (int) $doublage,
                    $umur0,
                    self::UMUR0_MAX_AVEC_DOUBLAGE,
                ),
            ];
        }

        return [];
    }

    /**
     * COP forfaitaire d'un chauffe-eau thermodynamique absent de §14.2.
     *
     * §14.2 p.95 : « Le tableau suivant donne les caractéristiques par défaut
     * des chauffe-eau thermodynamiques **si les caractéristiques exactes des
     * équipements ne peuvent pas être saisies** ». Ce tableau est fini : trois
     * familles — CET sur air extérieur ou ambiant, CET sur air extrait, PAC
     * double service — par trois millésimes et deux groupes de zones, soit
     * les seules valeurs 2 ; 2,1 ; 2,2 ; 2,3 ; 2,4 ; 2,5 ; 2,6 ; 2,8 ; 2,9.
     *
     * Quand le générateur déclare `enum_methode_saisie_carac_sys_id` = 1,
     * « valeurs forfaitaires », son COP ne peut donc être qu'une de ces neuf
     * valeurs. Un COP publié hors de cet ensemble ne vient d'aucune ligne de
     * la méthode, et la déclaration exclut la saisie justifiée qui
     * l'autoriserait. La preuve tient sur le seul fichier de référence.
     *
     * Les COP présents dans l'ensemble mais lus sur la mauvaise ligne — zone
     * H3 au lieu de H1/H2, ou famille voisine — ne sont pas couverts : les
     * départager demanderait de refaire le calcul, pas de lire le fichier.
     *
     * @return array<string, string>
     */
    private static function copForfaitaireHorsTable(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);

        foreach ($xpath->query('//generateur_ecs') as $generateur) {
            if (!$generateur instanceof DOMElement) {
                continue;
            }

            $saisie = self::nombreEnfant($xpath, './donnee_entree/enum_methode_saisie_carac_sys_id', $generateur);
            if ($saisie === null || (int) $saisie !== self::SAISIE_FORFAITAIRE) {
                continue;
            }

            $cop = self::nombreEnfant($xpath, './donnee_intermediaire/cop', $generateur);
            if ($cop === null || $cop <= 0.0) {
                continue;
            }

            foreach (self::COP_TABULES_14_2 as $tabule) {
                if (abs($cop - $tabule) < 1e-9) {
                    continue 2;
                }
            }

            return [
                'cop' => sprintf(
                    'la référence déclare une saisie forfaitaire '
                    . '(enum_methode_saisie_carac_sys_id = 1) mais publie cop = %s : §14.2 p.95 '
                    . 'ne tabule que %s pour les chauffe-eau thermodynamiques',
                    rtrim(rtrim(sprintf('%.4f', $cop), '0'), '.'),
                    implode(' ; ', array_map(
                        static fn (float $v): string => rtrim(rtrim(sprintf('%.1f', $v), '0'), '.'),
                        self::COP_TABULES_14_2,
                    )),
                ),
            ];
        }

        return [];
    }

    /**
     * Une perméabilité mesurée, saisie et justifiée, que la référence publie
     * au forfait.
     *
     * §4 p.44 : la valeur forfaitaire de Q4Pa_conv ne s'emploie qu'à défaut de
     * mesure. Un fichier qui déclare une mesure de moins de deux ans, en donne
     * la valeur dans `q4pa_conv_saisi`, puis publie autre chose dans
     * `q4pa_conv`, se contredit.
     *
     * @return array<string, string>
     */
    private static function mesureDEtancheiteDeclareeMaisIgnoree(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);

        foreach ($xpath->query('//ventilation') as $ventilation) {
            if (!$ventilation instanceof DOMElement) {
                continue;
            }

            $methode = self::nombreEnfant($xpath, './donnee_entree/enum_methode_saisie_q4pa_conv_id', $ventilation);
            $saisi = self::nombreEnfant($xpath, './donnee_entree/q4pa_conv_saisi', $ventilation);
            $publie = self::nombreEnfant($xpath, './donnee_intermediaire/q4pa_conv', $ventilation);

            if ($methode === null || !in_array((int)$methode, self::SAISIES_Q4PA_MESUREES, true)
                || $saisi === null || $saisi <= 0.0 || $publie === null
                || abs($publie - $saisi) <= 1e-9) {
                continue;
            }

            return [
                'q4pa_conv' => sprintf(
                    'la référence déclare une perméabilité mesurée de %.3f m³/(h.m²) '
                    . '(enum_methode_saisie_q4pa_conv_id = %d) mais publie %.3f, '
                    . 'la valeur forfaitaire : §4 p.44 ne l\'emploie qu\'à défaut de mesure',
                    $saisi,
                    (int)$methode,
                    $publie,
                ),
            ];
        }

        return [];
    }

    private static function nodeNum(DOMXPath $xpath, string $query, DOMElement $context): ?float
    {
        $node = $xpath->query($query, $context);
        if ($node === false || $node->length === 0) {
            return null;
        }

        $texte = trim((string) $node->item(0)?->textContent);

        return $texte === '' ? null : (float) str_replace(',', '.', $texte);
    }

    /**
     * Stockage intégré publié avec les champs réservés au stockage séparé.
     *
     * Le XSD définit enum_type_stockage_ecs_id=3 comme « stockage intégré à
     * la production » et réserve alors `rendement_generation_stockage` au
     * produit Rg×Rs. Publier à la place `rendement_generation` et
     * `rendement_stockage` contredit les documentations de ces trois champs.
     *
     * @return array<string, string>
     */
    private static function stockageIntegreSerialiseCommeSepare(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $installations = $xpath->query('//installation_ecs');
        if ($installations === false || $installations->length === 0) {
            return [];
        }

        $motif = 'la référence déclare un stockage intégré (type 3) mais sérialise les rendements '
            . 'dans les champs réservés par le XSD au stockage séparé, au lieu de rendement_generation_stockage';
        $suspects = [];
        foreach ($installations as $index => $installation) {
            if (!$installation instanceof DOMElement) {
                continue;
            }
            $noeuds = $xpath->query(
                './/generateur_ecs[donnee_entree/enum_type_stockage_ecs_id="3" '
                . 'and donnee_intermediaire/rendement_generation '
                . 'and donnee_intermediaire/rendement_stockage '
                . 'and not(donnee_intermediaire/rendement_generation_stockage)]',
                $installation,
            );
            if ($noeuds === false || $noeuds->length === 0) {
                continue;
            }
            $suffixe = $installations->length === 1 ? '' : '@' . ($index + 1);
            foreach (['rendement_generation', 'rendement_stockage', 'rendement_generation_stockage'] as $tag) {
                $suspects[$tag . $suffixe] = $motif;
            }
        }

        return $suspects;
    }

    /**
     * Sortie ECS d'un appartement généré incompatible avec les données publiées.
     *
     * §17.2.2.3.1 impose Cecs_ap = Cecs_immeuble × Becs_ap/Becs. Le XSD
     * documente `cle_repartition_ecs` comme la clé permettant précisément de
     * passer de la consommation bâtiment à celle du logement, et impose dans
     * ce cas que `surface_habitable` décrive l'installation à l'immeuble.
     *
     * Certains exports publient pourtant une surface d'installation ramenée à
     * quelques m² et une sortie ECS incompatible avec leur consommation
     * intermédiaire, `rdim` et leur clé. La vraie consommation bâtiment ou la
     * distribution complète des logements n'étant pas dans le XML, la sortie
     * ne peut pas être reconstruite sans données cachées de l'éditeur.
     *
     * @param array<string, string> $expected
     * @return array<string, string>
     */
    private static function repartitionEcsAppartementNonReproductible(array $expected, DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $mode = (int)$xpath->evaluate('string(//logement/caracteristique_generale/enum_methode_application_dpe_log_id)');
        if (!in_array($mode, [10, 11, 12, 13, 33, 34, 38, 39, 40], true)) {
            return [];
        }

        $surfaceImmeuble = (float)$xpath->evaluate('string(//logement/caracteristique_generale/surface_habitable_immeuble)');
        if ($surfaceImmeuble <= 0.0) {
            return [];
        }

        $installations = $xpath->query('//logement/installation_ecs_collection/installation_ecs');
        if ($installations === false || $installations->length === 0) {
            return [];
        }

        $reconstruit = ['conso_ecs' => 0.0, 'conso_ecs_depensier' => 0.0];
        $surfaceIncompatible = false;
        foreach ($installations as $installation) {
            if (!$installation instanceof DOMElement) {
                continue;
            }
            $methode = (int)$xpath->evaluate('string(./donnee_entree/enum_methode_calcul_conso_id)', $installation);
            $type = (int)$xpath->evaluate('string(./donnee_entree/enum_type_installation_id)', $installation);
            if ($methode !== 1 || $type !== 1) {
                return [];
            }
            $surface = (float)$xpath->evaluate('string(./donnee_entree/surface_habitable)', $installation);
            $rdim = (float)$xpath->evaluate('string(./donnee_entree/rdim)', $installation);
            $cle = (float)$xpath->evaluate('string(./donnee_entree/cle_repartition_ecs)', $installation);
            if (min($surface, $rdim, $cle) <= 0.0) {
                return [];
            }
            $surfaceIncompatible = $surfaceIncompatible
                || abs($surface - $surfaceImmeuble) / $surfaceImmeuble > 0.01;
            foreach (array_keys($reconstruit) as $tag) {
                $conso = (float)$xpath->evaluate('string(./donnee_intermediaire/' . $tag . ')', $installation);
                if ($conso <= 0.0) {
                    return [];
                }
                $reconstruit[$tag] += $conso * $rdim * $cle;
            }
        }
        if (!$surfaceIncompatible) {
            return [];
        }

        $motif = 'la sortie ECS de l’appartement généré n’est pas reproductible depuis le XML : '
            . 'surface_habitable ne décrit pas l’installation à l’immeuble comme l’impose le XSD, '
            . 'et consommation intermédiaire × rdim × cle_repartition_ecs ne retrouve pas la sortie';
        $suspects = [];
        foreach ($reconstruit as $tag => $valeurReconstituee) {
            $path = self::EF_PREFIX . $tag;
            $publie = self::num($expected, $path);
            if ($publie === null || $publie <= 0.0
                || abs($publie - $valeurReconstituee) / $publie <= 0.01) {
                continue;
            }
            $suspects[$path] = sprintf(
                '%s (%.1f kWh publiés contre %.1f kWh reconstitués)',
                $motif,
                $publie,
                $valeurReconstituee,
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
     * `k` de pont thermique publié comme un produit k × l.
     *
     * Le XSD définit `k` comme la valeur du pont thermique en W/(m·K) : la
     * déperdition totale vaut donc Σ k × l. Quand c'est la somme des `k`
     * eux-mêmes qui reproduit `deperdition_pont_thermique`, alors que Σ k × l
     * ne la reproduit pas, les valeurs publiées portent déjà la longueur et
     * contredisent l'unité déclarée par le schéma. La contradiction s'établit
     * sur le fichier de référence seul.
     *
     * @return array<string, string>
     */
    private static function pontThermiqueKPorteDejaLaLongueur(DOMDocument $doc): array
    {
        $xpath = new DOMXPath($doc);
        $total = $xpath->query('//deperdition/deperdition_pont_thermique')->item(0);
        if ($total === null) {
            return [];
        }
        $attendu = (float)str_replace(',', '.', trim($total->textContent));
        if ($attendu <= 0.0) {
            return [];
        }

        $sommeK = 0.0;
        $sommeKL = 0.0;
        $ponts = 0;
        foreach ($xpath->query('//pont_thermique') as $pont) {
            if (!$pont instanceof DOMElement) {
                continue;
            }
            $k = self::nombreEnfant($xpath, './donnee_intermediaire/k', $pont);
            $l = self::nombreEnfant($xpath, './donnee_entree/l', $pont);
            if ($k === null || $l === null) {
                continue;
            }
            $pct = self::nombreEnfant($xpath, './donnee_entree/pourcentage_valeur_pont_thermique', $pont) ?? 1.0;
            $sommeK += $k * $pct;
            $sommeKL += $k * $l * $pct;
            $ponts++;
        }
        if ($ponts === 0) {
            return [];
        }

        $tolerance = max(0.01, $attendu * 0.002);
        if (abs($sommeK - $attendu) > $tolerance || abs($sommeKL - $attendu) <= $tolerance) {
            return [];
        }

        return [
            'k' => sprintf(
                'la référence publie des k déjà multipliés par la longueur : leur somme (%.3f) '
                . 'reproduit deperdition_pont_thermique, alors que Σ k × l vaut %.3f — '
                . 'le XSD définit k en W/(m·K)',
                $sommeK,
                $sommeKL,
            ),
        ];
    }

    private static function nombreEnfant(DOMXPath $xpath, string $chemin, DOMElement $contexte): ?float
    {
        $noeud = $xpath->query($chemin, $contexte)->item(0);
        if ($noeud === null) {
            return null;
        }
        $valeur = str_replace(',', '.', trim($noeud->textContent));

        return is_numeric($valeur) ? (float)$valeur : null;
    }

    /**
     * Total d'auxiliaires qui ne correspond pas à la somme de ses postes.
     *
     * Le contrôle porte sur les quatre blocs qui publient ces totaux —
     * consommation finale, coût, émissions de GES et énergie primaire. Un total
     * qui contredit ses propres addendes est un défaut de la référence quel que
     * soit le bloc : la contradiction s'établit sur le fichier seul, sans
     * référence à notre calcul.
     *
     * @param array<string, string> $expected
     * @return array<string, string>
     */
    private static function totalAuxiliaireIncoherent(array $expected): array
    {
        $suspects = [];

        foreach (self::BLOCS_AUXILIAIRES as [$prefixe, $prefixePoste, $nomTotal]) {
            $total = self::num($expected, $prefixe . $nomTotal);
            if ($total === null) {
                continue;
            }

            $somme = 0.0;
            $trouves = 0;
            foreach (self::AUXILIAIRES_POSTES as $poste) {
                $valeur = self::num($expected, $prefixe . $prefixePoste . $poste);
                if ($valeur !== null) {
                    $somme += $valeur;
                    $trouves++;
                }
            }
            // Les coûts et les émissions sont publiés arrondis : la tolérance
            // absolue couvre l'arrondi de chaque poste sommé.
            $tolerance = max(count(self::AUXILIAIRES_POSTES) * 0.05, abs($somme) * 0.001);
            if ($trouves === 0 || abs($total - $somme) <= $tolerance) {
                continue;
            }

            $suspects[$nomTotal] = sprintf(
                'la référence publie un total auxiliaire de %.1f, incompatible avec la somme '
                . 'de ses postes (%.1f)',
                $total,
                $somme,
            );
        }

        return $suspects;
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
    /**
     * §11.2 et §11.6.2-3 p.72-75 : pour un ballon électrique individuel,
     * Cecs = (Becs / Rd + Qgw) / k, avec k=1,08 en catégorie C, 1 sinon.
     * §17.1.3.2 p.109 divise le besoin par N pour l'appartement moyen,
     * puis multiplie sa consommation par N ; les pertes de SON ballon ne
     * doivent pas être divisées une seconde fois par le nombre de logements.
     *
     * Détection conservative : un seul système couvrant tout l'immeuble,
     * aucune virtualisation ni solaire, et la même division Qgw/N retrouvée
     * dans les deux scénarios publiés. La preuve ne dépend ni de notre sortie
     * ni du numéro du DPE. Les consommations ECS et le bilan qui en dépend
     * sont suspects ; les écarts bruts restent tous comptés par le comparateur.
     *
     * @return array<string, string>
     */
    private static function pertesBallonIndividuelDiviseesParNombreLogements(DOMDocument $doc): array
    {
        $xp = new DOMXPath($doc);
        $mode = (int)$xp->evaluate('string(//logement/caracteristique_generale/enum_methode_application_dpe_log_id)');
        $nombre = (int)$xp->evaluate('string(//logement/caracteristique_generale/nombre_appartement)');
        $surface = (float)$xp->evaluate('string(//logement/caracteristique_generale/surface_habitable_immeuble)');
        $installations = $xp->query('//logement/installation_ecs_collection/installation_ecs');
        if (!in_array($mode, [6, 10], true) || $nombre <= 1 || $surface <= 0
            || $installations === false || $installations->length !== 1) {
            return [];
        }
        $installation = $installations->item(0);
        $generateurs = $xp->query('./generateur_ecs_collection/generateur_ecs', $installation);
        if ($generateurs === false || $generateurs->length !== 1
            || (int)$xp->evaluate('string(donnee_entree/enum_type_installation_id)', $installation) !== 1
            || (int)$xp->evaluate('string(donnee_entree/enum_methode_calcul_conso_id)', $installation) !== 4
            || (float)$xp->evaluate('string(donnee_entree/ratio_virtualisation)', $installation) !== 1.0
            || (float)$xp->evaluate('string(donnee_entree/rdim)', $installation) !== 1.0
            || (float)$xp->evaluate('string(donnee_entree/nombre_logement)', $installation) !== 1.0
            || abs((float)$xp->evaluate('string(donnee_entree/surface_habitable)', $installation) - $surface) > 0.01
            || $xp->query('.//enum_type_installation_solaire_id | .//tv_fecs_id', $installation)->length > 0) {
            return [];
        }
        $generateur = $generateurs->item(0);
        $type = (int)$xp->evaluate('string(donnee_entree/enum_type_generateur_ecs_id)', $generateur);
        $volume = (float)$xp->evaluate('string(donnee_entree/volume_stockage)', $generateur);
        $rd = (float)$xp->evaluate('string(donnee_intermediaire/rendement_distribution)', $installation);
        if (!in_array($type, [68, 69, 70, 71], true) || $volume <= 0 || $rd <= 0 || $rd > 1
            || (int)$xp->evaluate('string(donnee_entree/enum_type_energie_id)', $generateur) !== 1
            || (float)$xp->evaluate('string(donnee_intermediaire/rendement_generation)', $generateur) !== 1.0
            || (float)$xp->evaluate('string(donnee_intermediaire/ratio_besoin_ecs)', $generateur) !== 1.0) {
            return [];
        }

        // Tableau complet §11.6.2 : tranche de volume × type de ballon,
        // sans lire l'identifiant de table éventuellement erroné du XML.
        $tranche = match (true) { $volume <= 100 => 0, $volume <= 200 => 1, $volume <= 300 => 2, default => 3 };
        $table = require __DIR__ . '/../../resources/tables/ecs/tv_pertes_stockage.php';
        $coefficient = $table[4 * $tranche + $type - 67];
        $qgwKwh = 8592 * 45 / 24 * $volume * $coefficient['cr'] / 1000;
        $k = $coefficient['cat_c'] ? 1.08 : 1.0;
        $xmlPerKwh = \CalculDpePHP\Common\IntermediateEnergyUnit::xmlPerKwh($doc);
        foreach (['', '_depensier'] as $suffixe) {
            $besoin = (float)$xp->evaluate('string(donnee_intermediaire/besoin_ecs' . $suffixe . ')', $installation) / $xmlPerKwh;
            $conso = (float)$xp->evaluate('string(donnee_intermediaire/conso_ecs' . $suffixe . ')', $installation);
            $pertesPubliees = $k * $conso - $besoin / $rd;
            if ($besoin <= 0 || $conso <= 0 || $pertesPubliees <= 0
                || abs($pertesPubliees * $nombre - $qgwKwh) > 0.001 * $qgwKwh) {
                return [];
            }
        }
        $motif = sprintf(
            'les deux consommations ECS de la référence divisent les pertes du ballon individuel '
            . '(%.3f kWh/an selon §11.6.2) par %d logements : %.3f au lieu de %.3f kWh/an '
            . 'par appartement moyen (§17.1.3.2) ; le bilan énergétique et ses classes héritent de cette sous-estimation',
            $qgwKwh, $nombre, $qgwKwh / $nombre, $qgwKwh,
        );
        $suspects = [];
        foreach (['rendement_stockage', 'conso_ecs', 'conso_ecs_depensier',
            'ep_conso_5_usages', 'ep_conso_5_usages_m2', 'classe_bilan_dpe',
            'emission_ges_5_usages', 'emission_ges_5_usages_m2', 'classe_emission_ges'] as $tag) {
            $suspects[$tag] = $motif;
        }
        return $suspects;
    }


}
