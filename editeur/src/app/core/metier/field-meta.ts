import { ElementDef } from '../schema/element-def';

/**
 * Registre de métadonnées des champs : libellé, unité, aide.
 *
 * Ordre de résolution :
 *  1. surcharge explicite ci-dessous (libellés métier usuels) ;
 *  2. libellé dérivé du nom de balise (préfixes `enum_`, `tv_` et suffixe
 *     `_id` retirés) ;
 * L'aide est toujours la documentation officielle du XSD (xs:documentation).
 * L'unité vient de la surcharge, sinon de la mention entre parenthèses de la
 * documentation XSD (« (cm) », « (m) »…), sinon de la famille du nom.
 */
const LABELS: Record<string, string> = {
  description: 'Description',
  reference: 'Référence de l\'objet',
  reference_paroi: 'Paroi support',
  reference_lnc: 'Local non chauffé / espace tampon associé',
  reference_1: 'Premier élément lié',
  reference_2: 'Second élément lié',
  reference_generateur_mixte: 'Référence de générateur mixte',
  enum_type_adjacence_id: 'Donne sur',
  enum_orientation_id: 'Orientation',
  surface_paroi_totale: 'Surface totale de la paroi',
  surface_paroi_opaque: 'Surface opaque',
  surface_aiu: 'Surface Aiu (local non chauffé ↔ local chauffé)',
  surface_aue: 'Surface Aue (local non chauffé ↔ extérieur ou sol)',
  enum_cfg_isolation_lnc_id: 'Isolation local chauffé / local non chauffé',
  paroi_lourde: 'Paroi lourde (inertie)',
  paroi_ancienne: 'Paroi ancienne',
  enduit_isolant_paroi_ancienne: 'Enduit isolant sur paroi ancienne',
  epaisseur_structure: 'Épaisseur de la structure',
  enum_materiaux_structure_mur_id: 'Matériau de structure',
  enum_methode_saisie_u0_id: 'Méthode de détermination de U0',
  enum_methode_saisie_u_id: 'Méthode de détermination de U',
  enum_type_doublage_id: 'Doublage intérieur',
  enum_type_isolation_id: 'Type d\'isolation',
  enum_periode_isolation_id: 'Période d\'isolation',
  resistance_isolation: 'Résistance thermique de l\'isolant',
  epaisseur_isolation: 'Épaisseur d\'isolant',
  umur0_saisi: 'U0 du mur saisi',
  umur_saisi: 'U du mur saisi',
  upb0_saisi: 'U0 du plancher saisi',
  upb_saisi: 'U du plancher saisi',
  uph0_saisi: 'U0 du plancher haut saisi',
  uph_saisi: 'U du plancher haut saisi',
  enum_type_plancher_bas_id: 'Type de plancher bas',
  enum_type_plancher_haut_id: 'Type de plancher haut',
  calcul_ue: 'Calcul de Ue',
  perimetre_ue: 'Périmètre (calcul de Ue)',
  surface_ue: 'Surface (calcul de Ue)',
  ue: 'Ue',
  surface_totale_baie: 'Surface totale des baies',
  nb_baie: 'Nombre de baies',
  enum_type_vitrage_id: 'Type de vitrage',
  enum_inclinaison_vitrage_id: 'Inclinaison',
  enum_type_gaz_lame_id: 'Gaz de la lame',
  epaisseur_lame: 'Épaisseur de la lame',
  vitrage_vir: 'Vitrage à isolation renforcée (VIR)',
  enum_methode_saisie_perf_vitrage_id: 'Méthode de saisie des performances',
  ug_saisi: 'Ug saisi',
  uw_saisi: 'Uw saisi',
  ujn_saisi: 'Ujn saisi',
  sw_saisi: 'Sw saisi',
  enum_type_materiaux_menuiserie_id: 'Matériau de menuiserie',
  enum_type_baie_id: 'Type de baie',
  double_fenetre: 'Double fenêtre',
  enum_type_fermeture_id: 'Fermeture',
  presence_protection_solaire_hors_fermeture: 'Protection solaire hors fermeture',
  presence_retour_isolation: 'Retour d\'isolation',
  presence_joint: 'Joint',
  largeur_dormant: 'Largeur du dormant',
  enum_type_pose_id: 'Type de pose',
  surface_porte: 'Surface de la porte',
  nb_porte: 'Nombre de portes',
  enum_type_porte_id: 'Type de porte',
  enum_methode_saisie_uporte_id: 'Méthode de saisie de U',
  uporte_saisi: 'U de la porte saisi',
  enum_type_liaison_id: 'Type de liaison',
  l: 'Longueur',
  k_saisi: 'k saisi',
  k: 'k (coefficient linéique)',
  pourcentage_valeur_pont_thermique: 'Part de la valeur retenue',
  enum_methode_saisie_pont_thermique_id: 'Méthode de saisie',
  surface_ventile: 'Surface ventilée',
  plusieurs_facade_exposee: 'Plusieurs façades exposées',
  enum_methode_saisie_q4pa_conv_id: 'Méthode de saisie de Q4Pa',
  q4pa_conv_saisi: 'Q4Pa conventionnel saisi',
  enum_type_ventilation_id: 'Type de ventilation',
  ventilation_post_2012: 'Ventilation installée après 2012',
  surface_chauffee: 'Surface chauffée',
  surface_habitable: 'Surface habitable desservie',
  rdim: 'Nombre d\'installations identiques (rdim)',
  nombre_niveau_installation_ch: 'Niveaux desservis',
  nombre_niveau_installation_ecs: 'Niveaux desservis',
  enum_cfg_installation_ch_id: 'Configuration de l\'installation',
  enum_cfg_installation_ecs_id: 'Configuration de l\'installation',
  enum_type_installation_id: 'Type d\'installation',
  enum_methode_calcul_conso_id: 'Méthode de calcul de la consommation',
  enum_type_generateur_ch_id: 'Type de générateur',
  enum_type_generateur_ecs_id: 'Type de générateur',
  enum_type_generateur_fr_id: 'Type de générateur de froid',
  enum_usage_generateur_id: 'Usage du générateur',
  enum_type_energie_id: 'Énergie',
  position_volume_chauffe: 'Situé dans le volume chauffé',
  position_volume_chauffe_stockage: 'Stockage dans le volume chauffé',
  enum_methode_saisie_carac_sys_id: 'Méthode de saisie des caractéristiques',
  enum_lien_generateur_emetteur_id: 'Rôle (lien générateur ↔ émetteur)',
  enum_type_emission_distribution_id: 'Type d\'émission / distribution',
  reseau_distribution_isole: 'Réseau de distribution isolé',
  enum_equipement_intermittence_id: 'Équipement d\'intermittence',
  enum_type_regulation_id: 'Régulation',
  enum_periode_installation_emetteur_id: 'Période d\'installation des émetteurs',
  enum_type_chauffage_id: 'Type de chauffage',
  enum_temp_distribution_ch_id: 'Température de distribution',
  enum_type_stockage_ecs_id: 'Type de stockage',
  volume_stockage: 'Volume de stockage',
  enum_bouclage_reseau_ecs_id: 'Bouclage du réseau',
  annee_construction: 'Année de construction',
  enum_periode_construction_id: 'Période de construction',
  enum_methode_application_dpe_log_id: 'Type de DPE (méthode d\'application)',
  surface_habitable_logement: 'Surface habitable du logement',
  surface_habitable_immeuble: 'Surface habitable de l\'immeuble',
  surface_tertiaire_immeuble: 'Surface tertiaire de l\'immeuble',
  nombre_niveau_immeuble: 'Niveaux de l\'immeuble',
  nombre_niveau_logement: 'Niveaux du logement',
  nombre_appartement: 'Nombre d\'appartements',
  hsp: 'Hauteur sous plafond',
  enum_zone_climatique_id: 'Zone climatique',
  enum_classe_altitude_id: 'Classe d\'altitude',
  altitude: 'Altitude',
  batiment_materiaux_anciens: 'Bâtiment en matériaux anciens',
  enum_classe_inertie_id: 'Classe d\'inertie',
  inertie_plancher_bas_lourd: 'Plancher bas lourd',
  inertie_plancher_haut_lourd: 'Plancher haut lourd',
  inertie_paroi_verticale_lourd: 'Parois verticales lourdes',
  enum_version_id: 'Version du modèle de données',
  enum_modele_dpe_id: 'Modèle de DPE',
  date_visite_diagnostiqueur: 'Date de visite',
  date_etablissement_dpe: 'Date d\'établissement',
  dpe_a_remplacer: 'DPE remplacé (numéro)',
  motif_remplacement: 'Motif du remplacement',
  dpe_immeuble_associe: 'DPE immeuble associé (numéro)',
  nom_proprietaire: 'Propriétaire',
  numero_dpe: 'Numéro ADEME (export observatoire)',
  statut: 'Statut (export observatoire)',
  surface_clim: 'Surface climatisée',
  surface_totale_capteurs: 'Surface totale des capteurs',
  nombre_module: 'Nombre de modules',
  enum_orientation_pv_id: 'Orientation des panneaux',
  enum_inclinaison_pv_id: 'Inclinaison des panneaux',
  presence_production_pv: 'Production photovoltaïque',
  enum_type_enr_id: 'Type d\'énergie renouvelable',
  ratio_virtualisation: 'Ratio de virtualisation',
  cle_repartition_ch: 'Clé de répartition',
  cle_repartition_ecs: 'Clé de répartition',
  conso_5_usages_apres_travaux: 'Consommation 5 usages après travaux',
  emission_ges_5_usages_apres_travaux: 'Émissions 5 usages après travaux',
  cout_pack_travaux_min: 'Coût minimal',
  cout_pack_travaux_max: 'Coût maximal',
  enum_num_pack_travaux_id: 'Numéro du pack',
  description_travaux: 'Description des travaux',
  enum_lot_travaux_id: 'Lot',
  performance_recommande: 'Performance recommandée',
  avertissement_travaux: 'Avertissement',
  commentaire_travaux: 'Commentaire général sur les travaux',
  b: 'Coefficient de réduction b',
  umur: 'U du mur',
  umur0: 'U0 du mur',
  upb: 'U du plancher bas',
  upb_final: 'U final du plancher bas',
  upb0: 'U0 du plancher bas',
  uph: 'U du plancher haut',
  uph0: 'U0 du plancher haut',
  uw: 'Uw',
  ug: 'Ug',
  ujn: 'Ujn',
  sw: 'Sw',
  uporte: 'U de la porte',
  pn: 'Puissance nominale',
  conso_ch: 'Consommation de chauffage',
  conso_ecs: 'Consommation d\'ECS',
  besoin_ch: 'Besoin de chauffage',
  besoin_ecs: 'Besoin d\'ECS',
  conso_5_usages: 'Consommation 5 usages',
  conso_5_usages_m2: 'Consommation 5 usages par m²',
  emission_ges_5_usages: 'Émissions 5 usages',
  emission_ges_5_usages_m2: 'Émissions 5 usages par m²',
  cout_5_usages: 'Coût 5 usages',
  classe_bilan_dpe: 'Étiquette énergie',
  classe_emission_ges: 'Étiquette climat',
};

const UNITS: Record<string, string> = {
  hsp: 'm',
  l: 'm',
  altitude: 'm',
  largeur_dormant: 'mm',
  epaisseur_lame: 'mm',
  epaisseur_structure: 'cm',
  epaisseur_isolation: 'cm',
  resistance_isolation: 'm².K/W',
  volume_stockage: 'L',
  pn: 'kW',
  k: 'W/(m.K)',
  k_saisi: 'W/(m.K)',
  q4pa_conv_saisi: 'm³/(h.m²)',
  q4pa_conv: 'm³/(h.m²)',
  pourcentage_valeur_pont_thermique: '(0 à 1)',
  annee_construction: '',
};

export interface FieldMeta {
  label: string;
  unit: string;
  help: string | null;
  /** Identifiant de ligne d'une table de valeurs forfaitaires (tv_*). */
  isTableId: boolean;
}

export function humanize(name: string): string {
  let s = name.replace(/^enum_/, '').replace(/^tv_/, '').replace(/_id$/, '').replace(/_/g, ' ');
  s = s.replace(/\bch\b/g, 'chauffage').replace(/\becs\b/g, 'ECS').replace(/\bfr\b/g, 'froid').replace(/\bpv\b/g, 'PV');
  return s.charAt(0).toUpperCase() + s.slice(1);
}

export function fieldMeta(name: string, def: ElementDef | null): FieldMeta {
  const isTableId = /^tv_.*_id$/.test(name);
  let label = LABELS[name] ?? humanize(name);
  if (isTableId) label = `Ligne de table forfaitaire : ${humanize(name).toLowerCase()}`;
  return { label, unit: unitFor(name, def), help: def?.doc ?? null, isTableId };
}

function unitFor(name: string, def: ElementDef | null): string {
  if (name in UNITS) return UNITS[name];
  if (def?.kind === 'simple' && def.base && !['double', 'int', 'decimal', 'float'].includes(def.base)) return '';
  if (def?.enumLabels) return '';
  const doc = def?.doc ?? '';
  const m = /\((cm|mm|m|m2|m²|kW|W|kWh|%|L|l)\)/.exec(doc);
  if (m) return m[1] === 'm2' ? 'm²' : m[1];
  if (/^surface|_surface$|^surface_/.test(name)) return 'm²';
  if (/^(u|ug|uw|ujn|umur|upb|uph|uporte|ue)(0)?(_saisi|_final)?$/.test(name) || /^u[a-z]*0?_saisi$/.test(name)) return 'W/(m².K)';
  if (/_m2$/.test(name) && /^(conso|ep_conso|ef_conso)/.test(name)) return 'kWh/m²/an';
  if (/_m2$/.test(name) && /emission/.test(name)) return 'kg CO₂/m²/an';
  if (/^conso_|^besoin_|_conso_|^production_/.test(name)) return 'kWh/an';
  if (/^emission_ges/.test(name)) return 'kg CO₂/an';
  if (/^cout/.test(name)) return '€';
  if (/^deperdition|^hvent|^hperm/.test(name)) return 'W/K';
  return '';
}
