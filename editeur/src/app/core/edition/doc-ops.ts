import { ElementDef } from '../schema/element-def';
import { schemaPath, SchemaHandle } from '../schema/schema-registry';
import { KIND_BY_KEY, KINDS, KindDef, isResultPath, TABS } from '../metier/catalog';
import { firstChild, resolvePath } from '../xml/safe-xml';
import { annotate, UidCounter, XMLNS_NS, XSI_NS } from '../xml/uid';

/** Opérations élémentaires sur le document, guidées par le schéma. */

export const SINGLETON_PATHS = new Set(TABS.flatMap((t) => t.sections.filter((s) => s.singleton).map((s) => s.singleton!)));

export function kindOf(el: Element): KindDef | undefined {
  const p = schemaPath(el);
  return KINDS.find((k) => k.path === p);
}

/**
 * Objet navigable propriétaire d'un élément : objet du catalogue ou bloc
 * unique d'une section, sinon l'élément complexe annoté le plus proche.
 */
export function ownerObject(el: Element | null): Element | null {
  let root: Element | null = null;
  for (let n = el; n; n = n.parentElement) {
    if (!n.parentElement) {
      root = n;
      break;
    }
    const p = schemaPath(n);
    if (KINDS.some((k) => k.path === p) || SINGLETON_PATHS.has(p)) return n;
  }
  // à défaut : la racine (section « Document »)
  return root;
}

/** Chemin relatif de `el` depuis `ancestor` (`donnee_entree/surface_paroi_opaque`). */
export function relativePath(ancestor: Element, el: Element): string {
  const parts: string[] = [];
  for (let n: Element | null = el; n && n !== ancestor; n = n.parentElement) parts.unshift(n.localName);
  return parts.join('/');
}

export function textOf(el: Element | null, rel: string): string | null {
  const t = resolvePath(el, rel);
  if (!t || t.getAttributeNS(XSI_NS, 'nil') === 'true') return null;
  const v = (t.textContent ?? '').trim();
  return v === '' ? null : v;
}

/** Insère `child` dans `parent` à la place prévue par l'ordre du schéma. */
export function insertOrdered(parent: Element, child: Element, schema: SchemaHandle | null): void {
  const parentDef = schema?.defFor(parent) ?? null;
  const def = parentDef?.childMap.get(child.localName);
  if (!def) {
    parent.appendChild(child);
    return;
  }
  for (let s = parent.firstElementChild; s; s = s.nextElementSibling) {
    const sd = parentDef!.childMap.get(s.localName);
    if (sd && sd.order > def.order) {
      parent.insertBefore(child, s);
      return;
    }
  }
  parent.appendChild(child);
}

export function createElement(parent: Element, name: string): Element {
  return parent.ownerDocument.createElementNS(parent.namespaceURI, name);
}

/** Retourne le premier enfant `name`, en le créant à sa place s'il manque. */
export function ensureChild(parent: Element, name: string, schema: SchemaHandle | null, counter: UidCounter): Element {
  const existing = firstChild(parent, name);
  if (existing) return existing;
  const el = createElement(parent, name);
  insertOrdered(parent, el, schema);
  if (schema?.defFor(el)?.kind === 'complex') annotate(el, counter, () => true);
  return el;
}

export function ensurePath(base: Element, rel: string, schema: SchemaHandle | null, counter: UidCounter): Element {
  let cur = base;
  for (const part of rel.split('/').filter(Boolean)) cur = ensureChild(cur, part, schema, counter);
  return cur;
}

/**
 * Crée la structure obligatoire d'un élément complexe neuf : enfants
 * complexes de minOccurs ≥ 1, hors zones de résultats, hors xs:choice et hors
 * éléments répétables. Aucune valeur n'est inventée : les feuilles restent
 * absentes et seront signalées « à renseigner ».
 */
export function createSkeleton(el: Element, schema: SchemaHandle | null, counter: UidCounter): void {
  const def = schema?.defFor(el);
  if (!def || def.kind !== 'complex') return;
  for (const child of def.children) {
    if (child.kind !== 'complex' || child.minOccurs < 1 || child.choiceGroup !== null) continue;
    if (child.maxOccurs === null || child.maxOccurs > 1) continue;
    if (isResultPath(child.path)) continue;
    const c = ensureChild(el, child.name, schema, counter);
    createSkeleton(c, schema, counter);
  }
  annotate(el, counter, () => true);
}

export function setLeafText(leaf: Element, value: string): void {
  leaf.removeAttributeNS(XSI_NS, 'nil');
  leaf.textContent = value;
}

export function setNil(leaf: Element): void {
  const root = leaf.ownerDocument.documentElement;
  const declared = Array.from(root.attributes).some((a) => a.namespaceURI === XMLNS_NS && a.value === XSI_NS);
  if (!declared) root.setAttributeNS(XMLNS_NS, 'xmlns:xsi', XSI_NS);
  leaf.textContent = '';
  const prefix = leaf.lookupPrefix(XSI_NS) ?? 'xsi';
  leaf.setAttributeNS(XSI_NS, `${prefix}:nil`, 'true');
}

export function isNil(el: Element): boolean {
  return el.getAttributeNS(XSI_NS, 'nil') === 'true';
}

/** Élément conteneur de la collection d'un type d'objet (créé si besoin). */
export function collectionFor(kind: KindDef, doc: Document, parentObj: Element | null, schema: SchemaHandle | null, counter: UidCounter): Element {
  const segments = kind.path.split('/');
  if (kind.parentKind) {
    const parentKind = KIND_BY_KEY.get(kind.parentKind)!;
    if (!parentObj || schemaPath(parentObj) !== parentKind.path) {
      throw new Error(`${kind.label} : ${parentKind.label.toLowerCase()} parente requise.`);
    }
    const rel = kind.path.slice(parentKind.path.length + 1).split('/');
    return ensurePath(parentObj, rel.slice(0, -1).join('/'), schema, counter);
  }
  const root = doc.documentElement;
  if (root.localName !== segments[0]) throw new Error('Racine du document inattendue.');
  return ensurePath(root, segments.slice(1, -1).join('/'), schema, counter);
}

/** Objets du catalogue présents dans le document, par type. */
export function objectsOfKind(doc: Document, kind: KindDef): Element[] {
  const segments = kind.path.split('/');
  let level: Element[] = [doc.documentElement].filter((e) => e.localName === segments[0]);
  for (const seg of segments.slice(1)) {
    level = level.flatMap((e) => Array.from(e.children).filter((c) => c.localName === seg));
  }
  return level;
}

export function referenceValue(obj: Element, kind: KindDef | undefined): string | null {
  return kind?.idField ? textOf(obj, kind.idField) : null;
}

/** Toutes les valeurs `reference` du document (unicité des nouveaux identifiants). */
export function allReferences(doc: Document): Set<string> {
  const set = new Set<string>();
  for (const kind of KINDS) {
    if (!kind.idField) continue;
    for (const o of objectsOfKind(doc, kind)) {
      const v = textOf(o, kind.idField);
      if (v) set.add(v);
    }
  }
  return set;
}

export function uniqueValue(base: string, taken: Set<string>): string {
  let candidate = base;
  for (let i = 2; taken.has(candidate); i++) candidate = `${base}_${i}`;
  taken.add(candidate);
  return candidate;
}

export function defAt(schema: SchemaHandle | null, obj: Element, rel: string): ElementDef | null {
  return schema?.def(schemaPath(obj) + '/' + rel) ?? null;
}
