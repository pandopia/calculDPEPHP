import { SchemaHandle } from '../schema/schema-registry';
import { facetViolations } from '../edition/value-codec';
import { UID_NS, XMLNS_NS } from './uid';

/**
 * Éléments vides « de remplissage ».
 *
 * Les logiciels de diagnostic écrivent souvent des balises vides
 * (`<production_elec_enr/>`, `<numero_fiscal_local/>`,
 * `<masque_lointain_non_homogene_collection/>`…). Elles sont ignorées par
 * l'éditeur (ni anomalie, ni « à compléter ») et retirées sans le signaler à
 * l'export du XML de travail.
 *
 * Un élément est retiré s'il n'a ni texte, ni attribut, ni `xsi:nil`, ni
 * enfant conservé, qu'il est connu du schéma, et :
 *  - qu'il est facultatif (minOccurs = 0), ou
 *  - que sa valeur vide est de toute façon invalide pour son type.
 * Une balise obligatoire dont la valeur vide est valide (chaîne libre,
 * collection pouvant être vide) est conservée. Les balises hors schéma sont toujours conservées.
 */
export function prunableElements(doc: Document, schema: SchemaHandle | null): Set<Element> {
  const out = new Set<Element>();
  if (!schema) return out;
  const visit = (el: Element): boolean => {
    let keptChild = false;
    for (let c = el.firstElementChild; c; c = c.nextElementSibling) {
      if (!visit(c)) keptChild = true;
    }
    if (keptChild || !isBlank(el)) return false;
    const def = schema.defFor(el);
    if (!def || !el.parentElement) return false;
    const removable = def.minOccurs === 0 ||
      (def.kind === 'simple' && (facetViolations('', def).length > 0 || (!!def.base && def.base !== 'string')));
    if (removable) out.add(el);
    return removable;
  };
  visit(doc.documentElement);
  return out;
}

function isBlank(el: Element): boolean {
  // tout attribut réel (dont xsi:nil, choix explicite) rend l'élément significatif
  for (const a of Array.from(el.attributes)) {
    if (a.namespaceURI !== UID_NS && a.namespaceURI !== XMLNS_NS) return false;
  }
  for (let n = el.firstChild; n; n = n.nextSibling) {
    if (n.nodeType === 1) continue; // enfants traités par l'appelant
    if (n.nodeType === 3 && (n.nodeValue ?? '').trim() === '') continue;
    return false;
  }
  return true;
}
