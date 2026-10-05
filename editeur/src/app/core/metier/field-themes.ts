/**
 * Regroupement thématique des champs d'une fiche, pour la lisibilité.
 *
 * Le XSD range tout dans `donnee_entree` à plat ; on présente les champs par
 * thème, à partir de leur nom de balise. Purement visuel : l'ordre et la
 * structure du XML ne changent pas.
 *
 * Les champs « techniques » (références d'objet, identifiants de lignes de
 * tables forfaitaires, références produit, clés de répartition…) sont
 * repliés par défaut : utiles au logiciel, rarement à la compréhension.
 */
export interface Theme {
  key: string;
  label: string;
  test: RegExp;
}

export const THEMES: Theme[] = [
  { key: 'identification', label: 'Identification', test: /^(description|description_travaux|categorie_geste_entretien|enum_categorie_.*|enum_num_pack_travaux_id|enum_lot_travaux_id|enum_type_justificatif_id|enum_picto_geste_entretien_id|valeur|enum_origine_donnee_id)$/ },
  { key: 'position', label: 'Situation', test: /adjacence|orientation|inclinaison|reference_paroi|reference_lnc|cfg_isolation_lnc|surface_a(iu|ue)$|masque|position_volume|plusieurs_facade|position_etage|enum_zone|altitude|enum_classe_altitude|reference_[12]$/ },
  { key: 'dimensions', label: 'Dimensions', test: /^surface|^nb_|^nombre_|^l$|largeur|hsp|volume|perimetre|annee_construction|periode_construction|typologie/ },
  { key: 'composition', label: 'Composition', test: /materiau|epaisseur_structure|doublage|paroi_|enduit|type_plancher|type_vitrage|gaz_lame|epaisseur_lame|menuiserie|type_baie|type_porte|type_pose|fermeture|protection_solaire|joint|retour_isolation|double_fenetre|vitrage_vir|lourd|materiaux_anciens|inertie|type_liaison/ },
  { key: 'isolation', label: 'Isolation et performance', test: /isolation|^u[a-z]*0?(_saisi|_final)?$|^u[a-z]*0?_saisi$|resistance|methode_saisie_u|^ug|^uw|^ujn|^sw|^k(_saisi)?$|pourcentage_valeur|perf_vitrage|q4pa|ue$|calcul_ue/ },
  { key: 'systeme', label: 'Système', test: /generateur|energie|emission|distribution|regulation|intermittence|type_chauffage|temp_|installation|ventilation|stockage|bouclage|cfg_|chauffe|reseau|ventouse|combustion|radiateur|cascade|usage|lien_generateur|methode_calcul|methode_saisie_carac|fact_couv|fch|fecs|solaire|enr|pv|production|clim|presence_production/ },
];

export const TECHNICAL = /^reference$|^tv_.*_id$|^ref_produit|^cle_repartition|^ratio_virtualisation|^rdim$|^coef_ifc$|^nombre_logement_echantillon$|^reference_generateur_mixte$|^identifiant_reseau_chaleur$|^date_arrete_reseau_chaleur$|^reference_interne_projet$|^horodatage|^ban_|^enum_statut_geocodage|^usr_logiciel_id$|^version_logiciel$|^version_moteur_calcul$/;

export function themeOf(name: string): Theme | null {
  return THEMES.find((t) => t.test.test(name)) ?? null;
}

export function isTechnical(name: string): boolean {
  return TECHNICAL.test(name);
}
