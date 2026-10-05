/**
 * Définition d'un élément XSD compilé.
 *
 * `path` est le chemin XSD sans indice (`dpe/logement/enveloppe/mur_collection/
 * mur/donnee_entree/surface_paroi_opaque`), identique au `xs:appinfo source`
 * publié par l'ADEME.
 */
export type ElementKind = 'complex' | 'simple' | 'any';

export interface Facets {
  enumeration?: string[];
  pattern?: string[];
  minInclusive?: string;
  maxInclusive?: string;
  minExclusive?: string;
  maxExclusive?: string;
  length?: string;
  minLength?: string;
  maxLength?: string;
  totalDigits?: string;
  fractionDigits?: string;
}

export interface AttributeDef {
  name: string;
  required: boolean;
  doc: string | null;
}

export interface ElementDef {
  name: string;
  path: string;
  kind: ElementKind;
  minOccurs: number;
  /** null = unbounded */
  maxOccurs: number | null;
  nillable: boolean;
  doc: string | null;
  /** code → libellé (xs:appinfo JSON) */
  enumLabels: Record<string, string> | null;
  typeName: string | null;
  /** type XSD de base : int, double, string, date, dateTime… */
  base: string | null;
  facets: Facets;
  compositor: 'all' | 'sequence';
  /** identifiant du xs:choice auquel appartient l'élément dans son parent */
  choiceGroup: number | null;
  /** rang dans le modèle de contenu du parent */
  order: number;
  allowsAny: boolean;
  children: ElementDef[];
  childMap: Map<string, ElementDef>;
  attributes: AttributeDef[];
  parent: ElementDef | null;
}

export function isRepeatable(def: ElementDef): boolean {
  return def.maxOccurs === null || def.maxOccurs > 1;
}

export function isEnum(def: ElementDef): boolean {
  return !!def.enumLabels && Object.keys(def.enumLabels).length > 0;
}
