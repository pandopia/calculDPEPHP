<?php

declare(strict_types=1);

namespace CalculDpePHP\Conformite;

/**
 * Range chaque balise calculée dans une famille fonctionnelle, afin de
 * regrouper les écarts par domaine de la méthode 3CL plutôt que par balise.
 *
 * Le classement est **sensible au chemin** : `rendement_generation`,
 * `rendement_distribution` ou `cop` existent à la fois sous
 * `installation_chauffage_collection` et `installation_ecs_collection`, et
 * doivent être imputés à la bonne famille.
 */
final class TagFamily
{
    public const ENVELOPPE            = 'Enveloppe';
    public const VENTILATION          = 'Ventilation';
    public const APPORTS              = 'Apports';
    public const BESOIN_CH            = 'Besoin chauffage';
    public const BESOIN_ECS           = 'Besoin ECS';
    public const GENERATION_CH        = 'Génération chauffage';
    public const GENERATION_ECS       = 'Génération ECS';
    public const AUXILIAIRES          = 'Auxiliaires';
    public const FROID                = 'Froid';
    public const PV                   = 'PV';
    public const SORTIES_EF           = 'Sorties énergie finale';
    public const SORTIES_EP           = 'Sorties énergie primaire';
    public const GES                  = 'GES';
    public const COUT                 = 'Coûts';
    public const CONFORT_ETE          = "Confort d'été";
    public const COLLECTIF            = 'Collectif';
    public const AUTRE                = 'Autre';

    /** Ordre d'affichage : amont de la méthode → sorties finales. */
    public const ORDER = [
        self::ENVELOPPE,
        self::VENTILATION,
        self::APPORTS,
        self::BESOIN_CH,
        self::BESOIN_ECS,
        self::GENERATION_CH,
        self::GENERATION_ECS,
        self::AUXILIAIRES,
        self::FROID,
        self::PV,
        self::SORTIES_EF,
        self::SORTIES_EP,
        self::GES,
        self::COUT,
        self::CONFORT_ETE,
        self::COLLECTIF,
        self::AUTRE,
    ];

    private const ENVELOPPE_TAGS = [
        'b', 'umur', 'umur0', 'upb', 'upb0', 'upb_final', 'uph', 'uph0',
        'ug', 'uw', 'ujn', 'u_menuiserie', 'sw', 'uporte', 'k',
        'deperdition_mur', 'deperdition_plancher_bas', 'deperdition_plancher_haut',
        'deperdition_baie_vitree', 'deperdition_porte', 'deperdition_pont_thermique',
        'deperdition_enveloppe',
        'ubat', 'qualite_isol_enveloppe', 'qualite_isol_mur', 'qualite_isol_menuiserie',
        'qualite_isol_plancher_bas', 'qualite_isol_plancher_haut_toit_terrasse',
        'qualite_isol_plancher_haut_comble_perdu', 'qualite_isol_plancher_haut_comble_amenage',
        'qualite_isol_plancher_haut_comble_amenage_ou_perdu',
    ];

    private const VENTILATION_TAGS = [
        'q4pa_conv', 'hvent', 'hperm', 'pvent_moy',
        'deperdition_renouvellement_air',
    ];

    private const APPORTS_TAGS = [
        'surface_sud_equivalente', 'fe1', 'fe2',
        'enum_classe_inertie_id',
        'apport_solaire_ch', 'apport_interne_ch',
        'fraction_apport_gratuit_ch', 'fraction_apport_gratuit_depensier_ch',
        'i0', 'inertie_lourde',
        'pertes_generateur_ch_recup', 'pertes_generateur_ch_recup_depensier',
        'pertes_distribution_ecs_recup', 'pertes_distribution_ecs_recup_depensier',
        'pertes_stockage_ecs_recup',
    ];

    private const BESOIN_CH_TAGS = ['besoin_ch', 'besoin_ch_depensier'];

    private const BESOIN_ECS_TAGS = [
        'besoin_ecs', 'besoin_ecs_depensier',
        'nadeq', 'v40_ecs_journalier', 'v40_ecs_journalier_depensier',
    ];

    private const GENERATION_CH_TAGS = [
        'rendement_generation', 'rendement_distribution', 'rendement_emission',
        'rendement_regulation', 'pn', 'qp0', 'rpn', 'rpint',
        'temp_fonc_30', 'temp_fonc_100', 'cop', 'scop', 'pveilleuse',
        'conso_ch', 'conso_ch_depensier',
    ];

    private const GENERATION_ECS_TAGS = [
        'rendement_generation', 'rendement_distribution', 'rendement_stockage',
        'rendement_generation_stockage', 'ratio_besoin_ecs', 'cop', 'pveilleuse',
        'fecs', 'production_ecs_solaire',
        'conso_ecs', 'conso_ecs_depensier',
    ];

    private const FROID_TAGS = [
        'eer', 'seer', 'besoin_fr', 'besoin_fr_depensier',
        'apport_solaire_fr', 'apport_interne_fr',
        'conso_fr', 'conso_fr_depensier',
        'ep_conso_fr', 'ep_conso_fr_depensier',
        'emission_ges_fr', 'emission_ges_fr_depensier',
        'cout_fr', 'cout_fr_depensier',
    ];

    private const PV_TAGS = [
        'production_pv', 'taux_autoproduction', 'conso_elec_ac',
        'conso_elec_ac_ch', 'conso_elec_ac_ecs', 'conso_elec_ac_fr',
        'conso_elec_ac_eclairage', 'conso_elec_ac_auxiliaire', 'conso_elec_ac_autre_usage',
    ];

    private const CONFORT_ETE_TAGS = [
        'confort_ete', 'isolation_toiture', 'protection_solaire_exterieure',
        'aspect_traversant', 'brasseur_air', 'enum_indicateur_confort_ete_id',
    ];

    /**
     * @param string $path chemin canonique indexé issu de ValueExtractor
     */
    public static function of(string $path): string
    {
        $tag = self::leaf($path);

        // ── Contexte de l'installation : lève l'ambiguïté chauffage / ECS ──
        $isEcsBranch = str_contains($path, 'installation_ecs');
        $isChBranch = str_contains($path, 'installation_chauffage');
        $isFroidBranch = str_contains($path, 'climatisation');

        // Les auxiliaires portent leur usage dans le nom : on les isole avant
        // tout, sinon `conso_auxiliaire_generation_ch` tomberait dans EF.
        if (str_contains($tag, 'auxiliaire')) {
            return str_contains($tag, '_fr') && self::endsWithUsage($tag, 'fr')
                ? self::FROID
                : self::AUXILIAIRES;
        }

        if (in_array($tag, self::FROID_TAGS, true) || $isFroidBranch) {
            return self::FROID;
        }

        if (in_array($tag, self::PV_TAGS, true)) {
            return self::PV;
        }

        if (in_array($tag, self::CONFORT_ETE_TAGS, true)) {
            return self::CONFORT_ETE;
        }

        if ($isEcsBranch && in_array($tag, self::GENERATION_ECS_TAGS, true)) {
            return self::GENERATION_ECS;
        }

        if ($isChBranch && in_array($tag, self::GENERATION_CH_TAGS, true)) {
            return self::GENERATION_CH;
        }

        if (in_array($tag, self::BESOIN_CH_TAGS, true)) {
            return self::BESOIN_CH;
        }

        if (in_array($tag, self::BESOIN_ECS_TAGS, true)) {
            return self::BESOIN_ECS;
        }

        if (in_array($tag, self::ENVELOPPE_TAGS, true)) {
            return self::ENVELOPPE;
        }

        if (in_array($tag, self::VENTILATION_TAGS, true)) {
            return self::VENTILATION;
        }

        if (in_array($tag, self::APPORTS_TAGS, true)) {
            return self::APPORTS;
        }

        // Consommations ECS/CH remontées au niveau <sortie> : rattachées au
        // poste de génération correspondant.
        if (str_starts_with($tag, 'conso_ecs') || str_starts_with($tag, 'conso_ch')) {
            return str_starts_with($tag, 'conso_ecs') ? self::GENERATION_ECS : self::GENERATION_CH;
        }

        if (str_starts_with($tag, 'ep_conso') || $tag === 'classe_bilan_dpe') {
            return self::SORTIES_EP;
        }

        if (str_starts_with($tag, 'emission_ges') || $tag === 'classe_emission_ges') {
            return self::GES;
        }

        if (str_starts_with($tag, 'cout')) {
            return self::COUT;
        }

        if (str_starts_with($tag, 'conso_') || $tag === 'enum_type_energie_id') {
            return self::SORTIES_EF;
        }

        if (str_contains($path, 'dpe_immeuble') || str_contains($path, 'logement_visite')) {
            return self::COLLECTIF;
        }

        return self::AUTRE;
    }

    /** Nom de balise feuille, index positionnel retiré. */
    public static function leaf(string $path): string
    {
        $leaf = substr((string) strrchr('/' . $path, '/'), 1);

        return (string) preg_replace('/\[\d+\]$/', '', $leaf);
    }

    /**
     * `conso_auxiliaire_distribution_fr` relève du froid ;
     * `conso_auxiliaire_generation_ch` non. On ne teste donc que le suffixe
     * d'usage, jamais une sous-chaîne quelconque.
     */
    private static function endsWithUsage(string $tag, string $usage): bool
    {
        return str_ends_with($tag, '_' . $usage) || str_ends_with($tag, '_' . $usage . '_depensier');
    }
}
