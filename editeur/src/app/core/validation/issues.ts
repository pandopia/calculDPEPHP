/**
 * Anomalie rattachée à un objet et, si possible, à un champ.
 *
 * Niveaux de contrôle, du plus technique au plus indicatif :
 *  - xml          : XML bien formé (contrôlé à l'import) ;
 *  - format       : version, famille, balises hors schéma, codes inconnus ;
 *  - xsd          : conformité au XSD de la version (libxml2) ;
 *  - champ        : facettes XSD d'une valeur, contrôlées en direct ;
 *  - completude   : éléments obligatoires selon le XSD, ou attendus selon une
 *                   règle d'affichage documentée ;
 *  - references   : intégrité des identifiants et des liens ;
 *  - metier       : règles métier documentées (source citée) ;
 *  - plausibilite : avertissements de vraisemblance, non réglementaires ;
 *  - resultats    : résultats du fichier source absents ou à recalculer.
 */
export type Niveau = 'xml' | 'format' | 'xsd' | 'champ' | 'completude' | 'references' | 'metier' | 'plausibilite' | 'resultats';
export type Gravite = 'erreur' | 'avertissement' | 'info';

export interface Issue {
  id: string;
  niveau: Niveau;
  gravite: Gravite;
  message: string;
  /** objet navigable concerné */
  uid: string | null;
  /** chemin relatif du champ depuis l'objet */
  field?: string;
  xmlPath?: string;
  detail?: string;
  source?: string;
  /** section de rattachement quand l'anomalie concerne une collection */
  nav?: { tab: string; section: string };
}

export const NIVEAU_LABELS: Record<Niveau, string> = {
  xml: 'XML bien formé',
  format: 'Format et version',
  xsd: 'Conformité XSD',
  champ: 'Valeur de champ',
  completude: 'Complétude',
  references: 'Identifiants et références',
  metier: 'Règle métier documentée',
  plausibilite: 'Plausibilité (indicatif)',
  resultats: 'Résultats',
};

export const GRAVITE_ORDER: Record<Gravite, number> = { erreur: 0, avertissement: 1, info: 2 };

let seq = 0;
export function issue(partial: Omit<Issue, 'id'>): Issue {
  return { id: `i${++seq}`, ...partial };
}
