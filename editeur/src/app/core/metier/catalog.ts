/**
 * Catalogue métier du DPE logement existant (branche `dpe/logement`).
 *
 * Chaque type d'objet est rattaché à un chemin XSD réel ; les liens entre
 * objets ne reprennent que ceux que le XSD documente (xs:documentation des
 * balises `reference*`). Rien n'est déduit d'un exemple de fichier.
 */

export type TabKey =
  | 'synthese'
  | 'general'
  | 'batiment'
  | 'enveloppe'
  | 'ventilation'
  | 'chauffage'
  | 'ecs'
  | 'autres'
  | 'resultats'
  | 'travaux'
  | 'controles';

export interface TabDef {
  key: TabKey;
  label: string;
  sections: SectionDef[];
}

export interface SectionDef {
  key: string;
  label: string;
  /** Type d'objet listé (collection) ou chemin d'un bloc unique (singleton). */
  kind?: string;
  singleton?: string;
  /** Bloc de résultats : lecture seule. */
  results?: boolean;
  help?: string;
}

export interface KindDef {
  key: string;
  label: string;
  plural: string;
  /** article indéfini pour « Ajouter un/une … » */
  article: 'un' | 'une';
  /** chemin XSD de l'élément objet */
  path: string;
  tab: TabKey;
  section: string;
  /** type parent dans l'arborescence XML (objets imbriqués) */
  parentKind?: string;
  /** chemin relatif du nom lisible */
  nameField?: string;
  /** chemin relatif de l'identifiant `reference` */
  idField?: string;
  /** chemins relatifs résumés dans les listes */
  summary: string[];
  /** collection minimale exigée par le XSD (ex. ventilation 1..*) */
  results?: boolean;
}

export const LOG = 'dpe/logement';
const ENV = `${LOG}/enveloppe`;
const CH = `${LOG}/installation_chauffage_collection/installation_chauffage`;
const ECS = `${LOG}/installation_ecs_collection/installation_ecs`;

export const KINDS: KindDef[] = [
  {
    key: 'mur', label: 'Mur', plural: 'Murs', article: 'un', path: `${ENV}/mur_collection/mur`,
    tab: 'enveloppe', section: 'murs', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_adjacence_id', 'donnee_entree/enum_orientation_id', 'donnee_entree/surface_paroi_opaque',
      'donnee_entree/enum_materiaux_structure_mur_id', 'donnee_entree/enum_type_isolation_id', 'donnee_intermediaire/umur'],
  },
  {
    key: 'plancher_bas', label: 'Plancher bas', plural: 'Planchers bas', article: 'un', path: `${ENV}/plancher_bas_collection/plancher_bas`,
    tab: 'enveloppe', section: 'planchers_bas', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_adjacence_id', 'donnee_entree/surface_paroi_opaque', 'donnee_entree/enum_type_plancher_bas_id',
      'donnee_entree/enum_type_isolation_id', 'donnee_intermediaire/upb_final'],
  },
  {
    key: 'plancher_haut', label: 'Plancher haut / toiture', plural: 'Planchers hauts et toitures', article: 'un',
    path: `${ENV}/plancher_haut_collection/plancher_haut`, tab: 'enveloppe', section: 'planchers_hauts',
    nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_adjacence_id', 'donnee_entree/surface_paroi_opaque', 'donnee_entree/enum_type_plancher_haut_id',
      'donnee_entree/enum_type_isolation_id', 'donnee_intermediaire/uph'],
  },
  {
    key: 'baie_vitree', label: 'Baie vitrée', plural: 'Baies vitrées', article: 'une', path: `${ENV}/baie_vitree_collection/baie_vitree`,
    tab: 'enveloppe', section: 'baies', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_baie_id', 'donnee_entree/enum_orientation_id', 'donnee_entree/surface_totale_baie',
      'donnee_entree/nb_baie', 'donnee_entree/enum_type_vitrage_id', 'donnee_intermediaire/uw'],
  },
  {
    key: 'porte', label: 'Porte', plural: 'Portes', article: 'une', path: `${ENV}/porte_collection/porte`,
    tab: 'enveloppe', section: 'portes', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_porte_id', 'donnee_entree/surface_porte', 'donnee_entree/nb_porte', 'donnee_intermediaire/uporte'],
  },
  {
    key: 'ets', label: 'Espace tampon solarisé', plural: 'Espaces tampons solarisés', article: 'un', path: `${ENV}/ets_collection/ets`,
    tab: 'enveloppe', section: 'ets', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_cfg_isolation_lnc_id', 'donnee_intermediaire/bver'],
  },
  {
    key: 'baie_ets', label: 'Baie d\'espace tampon', plural: 'Baies de l\'espace tampon', article: 'une',
    path: `${ENV}/ets_collection/ets/baie_ets_collection/baie_ets`, tab: 'enveloppe', section: 'ets', parentKind: 'ets',
    nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_orientation_id', 'donnee_entree/surface_totale_baie', 'donnee_entree/nb_baie'],
  },
  {
    key: 'pont_thermique', label: 'Pont thermique', plural: 'Ponts thermiques', article: 'un',
    path: `${ENV}/pont_thermique_collection/pont_thermique`, tab: 'enveloppe', section: 'ponts_thermiques',
    nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_liaison_id', 'donnee_entree/l', 'donnee_intermediaire/k'],
  },
  {
    key: 'ventilation', label: 'Ventilation', plural: 'Ventilations', article: 'une', path: `${LOG}/ventilation_collection/ventilation`,
    tab: 'ventilation', section: 'ventilations', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_ventilation_id', 'donnee_entree/surface_ventile', 'donnee_entree/plusieurs_facade_exposee'],
  },
  {
    key: 'installation_chauffage', label: 'Installation de chauffage', plural: 'Installations de chauffage', article: 'une',
    path: CH, tab: 'chauffage', section: 'installations_chauffage', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_installation_id', 'donnee_entree/enum_cfg_installation_ch_id', 'donnee_entree/surface_chauffee',
      'donnee_intermediaire/conso_ch'],
  },
  {
    key: 'generateur_chauffage', label: 'Générateur de chauffage', plural: 'Générateurs de chauffage', article: 'un',
    path: `${CH}/generateur_chauffage_collection/generateur_chauffage`, tab: 'chauffage', section: 'installations_chauffage',
    parentKind: 'installation_chauffage', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_generateur_ch_id', 'donnee_entree/enum_type_energie_id', 'donnee_entree/enum_lien_generateur_emetteur_id',
      'donnee_intermediaire/pn'],
  },
  {
    key: 'emetteur_chauffage', label: 'Émetteur de chauffage', plural: 'Émetteurs de chauffage', article: 'un',
    path: `${CH}/emetteur_chauffage_collection/emetteur_chauffage`, tab: 'chauffage', section: 'installations_chauffage',
    parentKind: 'installation_chauffage', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_emission_distribution_id', 'donnee_entree/surface_chauffee', 'donnee_entree/enum_lien_generateur_emetteur_id'],
  },
  {
    key: 'installation_ecs', label: 'Installation d\'eau chaude sanitaire', plural: 'Installations d\'ECS', article: 'une', path: ECS,
    tab: 'ecs', section: 'installations_ecs', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_installation_id', 'donnee_entree/enum_cfg_installation_ecs_id', 'donnee_entree/surface_habitable',
      'donnee_intermediaire/conso_ecs'],
  },
  {
    key: 'generateur_ecs', label: 'Générateur d\'ECS', plural: 'Générateurs d\'ECS', article: 'un',
    path: `${ECS}/generateur_ecs_collection/generateur_ecs`, tab: 'ecs', section: 'installations_ecs', parentKind: 'installation_ecs',
    nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_generateur_ecs_id', 'donnee_entree/enum_type_energie_id', 'donnee_entree/volume_stockage'],
  },
  {
    key: 'climatisation', label: 'Climatisation', plural: 'Climatisations', article: 'une', path: `${LOG}/climatisation_collection/climatisation`,
    tab: 'autres', section: 'climatisations', nameField: 'donnee_entree/description', idField: 'donnee_entree/reference',
    summary: ['donnee_entree/enum_type_generateur_fr_id', 'donnee_entree/enum_type_energie_id', 'donnee_entree/surface_clim'],
  },
  {
    key: 'panneaux_pv', label: 'Champ de panneaux photovoltaïques', plural: 'Panneaux photovoltaïques', article: 'un',
    path: `${LOG}/production_elec_enr/panneaux_pv_collection/panneaux_pv`, tab: 'autres', section: 'panneaux_pv',
    summary: ['surface_totale_capteurs', 'nombre_module', 'enum_orientation_pv_id', 'enum_inclinaison_pv_id'],
  },
  {
    key: 'descriptif_enr', label: 'Équipement d\'énergie renouvelable', plural: 'Énergies renouvelables (descriptif)', article: 'un',
    path: 'dpe/descriptif_enr_collection/descriptif_enr', tab: 'autres', section: 'descriptif_enr', nameField: 'description',
    summary: ['enum_categorie_enr_descriptif_id'],
  },
  {
    key: 'logement_visite', label: 'Logement visité', plural: 'Logements visités', article: 'un',
    path: 'dpe/dpe_immeuble/logement_visite_collection/logement_visite', tab: 'batiment', section: 'logements_visites', nameField: 'description',
    summary: ['enum_position_etage_logement_id', 'enum_typologie_logement_id', 'surface_habitable_logement'],
  },
  {
    key: 'descriptif_simplifie', label: 'Descriptif simplifié', plural: 'Descriptifs simplifiés', article: 'un',
    path: 'dpe/descriptif_simplifie_collection/descriptif_simplifie', tab: 'general', section: 'descriptifs', nameField: 'description',
    summary: ['enum_categorie_descriptif_simplifie_id'],
  },
  {
    key: 'fiche_technique', label: 'Fiche technique', plural: 'Fiches techniques', article: 'une',
    path: 'dpe/fiche_technique_collection/fiche_technique', tab: 'general', section: 'fiches_techniques',
    summary: ['enum_categorie_fiche_technique_id'],
  },
  {
    key: 'sous_fiche_technique', label: 'Ligne de fiche technique', plural: 'Lignes de fiche technique', article: 'une',
    path: 'dpe/fiche_technique_collection/fiche_technique/sous_fiche_technique_collection/sous_fiche_technique', tab: 'general',
    section: 'fiches_techniques', parentKind: 'fiche_technique', nameField: 'description', summary: ['valeur', 'enum_origine_donnee_id'],
  },
  {
    key: 'justificatif', label: 'Justificatif', plural: 'Justificatifs', article: 'un', path: 'dpe/justificatif_collection/justificatif',
    tab: 'general', section: 'justificatifs', nameField: 'description', summary: ['enum_type_justificatif_id'],
  },
  {
    key: 'pack_travaux', label: 'Pack de travaux', plural: 'Packs de travaux', article: 'un',
    path: 'dpe/descriptif_travaux/pack_travaux_collection/pack_travaux', tab: 'travaux', section: 'packs',
    summary: ['enum_num_pack_travaux_id', 'conso_5_usages_apres_travaux', 'emission_ges_5_usages_apres_travaux', 'cout_pack_travaux_min', 'cout_pack_travaux_max'],
  },
  {
    key: 'travaux', label: 'Travaux', plural: 'Travaux', article: 'un',
    path: 'dpe/descriptif_travaux/pack_travaux_collection/pack_travaux/travaux_collection/travaux', tab: 'travaux', section: 'packs',
    parentKind: 'pack_travaux', nameField: 'description_travaux', summary: ['enum_lot_travaux_id', 'performance_recommande'],
  },
  {
    key: 'descriptif_geste_entretien', label: 'Geste d\'entretien', plural: 'Gestes d\'entretien', article: 'un',
    path: 'dpe/descriptif_geste_entretien_collection/descriptif_geste_entretien', tab: 'travaux', section: 'gestes', nameField: 'description',
    summary: ['enum_picto_geste_entretien_id', 'categorie_geste_entretien'],
  },
  {
    key: 'sortie_par_energie', label: 'Résultat par énergie', plural: 'Résultats par énergie', article: 'un',
    path: `${LOG}/sortie/sortie_par_energie_collection/sortie_par_energie`, tab: 'resultats', section: 'par_energie', results: true,
    summary: ['enum_type_energie_id', 'conso_5_usages', 'emission_ges_5_usages', 'cout_5_usages'],
  },
];

export const KIND_BY_KEY = new Map(KINDS.map((k) => [k.key, k]));
export const KIND_BY_PATH = new Map(KINDS.map((k) => [k.path, k]));

export const TABS: TabDef[] = [
  { key: 'synthese', label: 'Synthèse', sections: [] },
  {
    key: 'general', label: 'Informations générales', sections: [
      { key: 'document', label: 'Document (version, en-tête)', singleton: 'dpe' },
      { key: 'administratif', label: 'Administratif', singleton: 'dpe/administratif' },
      { key: 'diagnostiqueur', label: 'Diagnostiqueur et logiciel', singleton: 'dpe/administratif/diagnostiqueur' },
      { key: 'geolocalisation', label: 'Localisation et identifiants', singleton: 'dpe/administratif/geolocalisation' },
      { key: 'adresse_bien', label: 'Adresse du bien', singleton: 'dpe/administratif/geolocalisation/adresses/adresse_bien' },
      { key: 'adresse_proprietaire', label: 'Adresse du propriétaire', singleton: 'dpe/administratif/geolocalisation/adresses/adresse_proprietaire' },
      { key: 'adresse_proprietaire_installation_commune', label: 'Adresse du propriétaire des installations communes', singleton: 'dpe/administratif/geolocalisation/adresses/adresse_proprietaire_installation_commune' },
      { key: 'consentement', label: 'Formulaire de consentement', singleton: 'dpe/administratif/information_formulaire_consentement' },
      { key: 'descriptifs', label: 'Descriptifs simplifiés', kind: 'descriptif_simplifie' },
      { key: 'fiches_techniques', label: 'Fiches techniques', kind: 'fiche_technique' },
      { key: 'justificatifs', label: 'Justificatifs', kind: 'justificatif' },
    ],
  },
  {
    key: 'batiment', label: 'Bâtiment / logement', sections: [
      { key: 'caracteristique_generale', label: 'Caractéristiques générales', singleton: `${LOG}/caracteristique_generale` },
      { key: 'meteo', label: 'Situation climatique', singleton: `${LOG}/meteo` },
      { key: 'logements_visites', label: 'Logements visités (DPE immeuble)', kind: 'logement_visite' },
    ],
  },
  {
    key: 'enveloppe', label: 'Enveloppe', sections: [
      { key: 'inertie', label: 'Inertie', singleton: `${ENV}/inertie` },
      { key: 'murs', label: 'Murs', kind: 'mur' },
      { key: 'planchers_bas', label: 'Planchers bas', kind: 'plancher_bas' },
      { key: 'planchers_hauts', label: 'Planchers hauts / toitures', kind: 'plancher_haut' },
      { key: 'baies', label: 'Baies vitrées', kind: 'baie_vitree' },
      { key: 'portes', label: 'Portes', kind: 'porte' },
      { key: 'ets', label: 'Espaces tampons solarisés', kind: 'ets' },
      { key: 'ponts_thermiques', label: 'Ponts thermiques', kind: 'pont_thermique' },
    ],
  },
  { key: 'ventilation', label: 'Ventilation', sections: [{ key: 'ventilations', label: 'Systèmes de ventilation', kind: 'ventilation' }] },
  { key: 'chauffage', label: 'Chauffage', sections: [{ key: 'installations_chauffage', label: 'Installations de chauffage', kind: 'installation_chauffage' }] },
  { key: 'ecs', label: 'Eau chaude sanitaire', sections: [{ key: 'installations_ecs', label: 'Installations d\'ECS', kind: 'installation_ecs' }] },
  {
    key: 'autres', label: 'Refroidissement et autres', sections: [
      { key: 'climatisations', label: 'Climatisation', kind: 'climatisation' },
      { key: 'production_elec', label: 'Production d\'électricité', singleton: `${LOG}/production_elec_enr` },
      { key: 'panneaux_pv', label: 'Panneaux photovoltaïques', kind: 'panneaux_pv' },
      { key: 'descriptif_enr', label: 'Énergies renouvelables (descriptif)', kind: 'descriptif_enr' },
    ],
  },
  {
    key: 'resultats', label: 'Résultats', sections: [
      { key: 'deperdition', label: 'Déperditions', singleton: `${LOG}/sortie/deperdition`, results: true },
      { key: 'apport_et_besoin', label: 'Apports et besoins', singleton: `${LOG}/sortie/apport_et_besoin`, results: true },
      { key: 'ef_conso', label: 'Consommations en énergie finale', singleton: `${LOG}/sortie/ef_conso`, results: true },
      { key: 'ep_conso', label: 'Consommations en énergie primaire', singleton: `${LOG}/sortie/ep_conso`, results: true },
      { key: 'emission_ges', label: 'Émissions de gaz à effet de serre', singleton: `${LOG}/sortie/emission_ges`, results: true },
      { key: 'cout', label: 'Coûts', singleton: `${LOG}/sortie/cout`, results: true },
      { key: 'production_electricite', label: 'Production d\'électricité', singleton: `${LOG}/sortie/production_electricite`, results: true },
      { key: 'par_energie', label: 'Par énergie', kind: 'sortie_par_energie', results: true },
      { key: 'confort_ete', label: 'Confort d\'été', singleton: `${LOG}/sortie/confort_ete`, results: true },
      { key: 'qualite_isolation', label: 'Qualité de l\'isolation', singleton: `${LOG}/sortie/qualite_isolation`, results: true },
    ],
  },
  {
    key: 'travaux', label: 'Recommandations et travaux', sections: [
      { key: 'descriptif_travaux', label: 'Commentaire général', singleton: 'dpe/descriptif_travaux' },
      { key: 'packs', label: 'Packs de travaux', kind: 'pack_travaux' },
      { key: 'gestes', label: 'Gestes d\'entretien', kind: 'descriptif_geste_entretien' },
    ],
  },
  { key: 'controles', label: 'Contrôles et export', sections: [] },
];

/** Relation documentée par le XSD. */
export interface RefDef {
  key: string;
  fromKinds: string[];
  /** chemin relatif du champ portant la référence */
  field: string;
  toKinds: string[];
  label: string;
  inverseLabel: string;
  /** 'id' : valeur = `reference` de la cible ; 'shared' : même valeur des deux côtés */
  mode: 'id' | 'shared';
  /**
   * Une valeur non résolue est-elle admise ? Le XSD prévoit par exemple qu'un
   * local non chauffé soit référencé sans être décrit comme objet.
   */
  unresolved: 'avertissement' | 'info';
  doc: string;
}

const PAROIS = ['mur', 'plancher_bas', 'plancher_haut'];
const ELEMENTS_ENVELOPPE = ['mur', 'plancher_bas', 'plancher_haut', 'baie_vitree', 'porte'];

export const REFS: RefDef[] = [
  {
    key: 'paroi', fromKinds: ['baie_vitree', 'porte'], field: 'donnee_entree/reference_paroi', toKinds: PAROIS,
    label: 'Paroi support', inverseLabel: 'Ouvertures rattachées', mode: 'id', unresolved: 'avertissement',
    doc: 'XSD : « reference_paroi est la référence d\'une paroi de type mur, plancher_haut ou plancher_bas ».',
  },
  {
    key: 'lnc', fromKinds: ELEMENTS_ENVELOPPE, field: 'donnee_entree/reference_lnc', toKinds: ['ets'],
    label: 'Local non chauffé / espace tampon', inverseLabel: 'Parois donnant sur cet espace', mode: 'id', unresolved: 'info',
    doc: 'XSD : « référence de l\'objet local non chauffé associé à la paroi ; pour un espace tampon solarisé, celle de l\'espace tampon ». Un local non chauffé n\'est pas un objet du format : une référence non résolue est admise.',
  },
  {
    key: 'pt1', fromKinds: ['pont_thermique'], field: 'donnee_entree/reference_1', toKinds: ELEMENTS_ENVELOPPE,
    label: 'Premier élément de la liaison', inverseLabel: 'Ponts thermiques', mode: 'id', unresolved: 'info',
    doc: 'XSD : « 2 références complémentaires correspondant à chacun des deux objets concernés par le pont thermique ».',
  },
  {
    key: 'pt2', fromKinds: ['pont_thermique'], field: 'donnee_entree/reference_2', toKinds: ELEMENTS_ENVELOPPE,
    label: 'Second élément de la liaison', inverseLabel: 'Ponts thermiques', mode: 'id', unresolved: 'info',
    doc: 'XSD : « 2 références complémentaires correspondant à chacun des deux objets concernés par le pont thermique ».',
  },
  {
    key: 'mixte', fromKinds: ['generateur_chauffage', 'generateur_ecs'], field: 'donnee_entree/reference_generateur_mixte',
    toKinds: ['generateur_chauffage', 'generateur_ecs'], label: 'Générateur mixte (chauffage + ECS)',
    inverseLabel: 'Partie associée du générateur mixte', mode: 'shared', unresolved: 'avertissement',
    doc: 'XSD : « référence identique pour les deux parties du générateur (chauffage et ECS), utilisée pour faire le lien entre les deux ». Certains logiciels y écrivent la référence de l\'autre partie : cette convention est aussi reconnue.',
  },
];

/** Champ → définitions de référence qui le concernent. */
export function refsForField(kindKey: string, field: string): RefDef | undefined {
  return REFS.find((r) => r.field === field && r.fromKinds.includes(kindKey));
}

/** Zones « résultats » : sorties et valeurs intermédiaires calculées. */
export function isResultPath(schemaPath: string): boolean {
  return /\/sortie(\/|$)/.test(schemaPath) || /\/donnee_intermediaire(\/|$)/.test(schemaPath);
}

export function kindForPath(schemaPath: string): KindDef | undefined {
  return KIND_BY_PATH.get(schemaPath);
}
