/**
 * Identifiants internes de travail.
 *
 * Chaque élément complexe du document de travail porte un attribut `ed:uid`
 * dans un espace de noms propre à l'éditeur. Il donne une identité stable aux
 * objets (diff depuis l'import, annuler/rétablir, navigation) sans toucher aux
 * balises métier. Il n'existe que dans le document de travail et les
 * brouillons locaux : l'export le retire intégralement (voir serializer.ts).
 */
export const UID_NS = 'urn:calculdpe:editeur';
export const UID_PREFIX = 'ed';
export const XMLNS_NS = 'http://www.w3.org/2000/xmlns/';
export const XSI_NS = 'http://www.w3.org/2001/XMLSchema-instance';

export function getUid(el: Element): string | null {
  return el.getAttributeNS(UID_NS, 'uid') || null;
}

export function setUid(el: Element, uid: string): void {
  el.setAttributeNS(UID_NS, `${UID_PREFIX}:uid`, uid);
}

export class UidCounter {
  constructor(public value = 0) {}
  next(): string {
    this.value++;
    return 'n' + this.value.toString(36);
  }
}

/** Plus grand compteur déjà utilisé dans un document (reprise de brouillon). */
export function maxUid(doc: Document): number {
  let max = 0;
  for (const el of allElements(doc.documentElement)) {
    const uid = getUid(el);
    if (uid) max = Math.max(max, parseInt(uid.slice(1), 36));
  }
  return max;
}

/** Tous les éléments d'un sous-arbre, en ordre de document (parcours itératif). */
export function allElements(root: Element | null): Element[] {
  const out: Element[] = [];
  if (!root) return out;
  const stack: Element[] = [root];
  while (stack.length) {
    const el = stack.pop()!;
    out.push(el);
    for (let c = el.lastElementChild; c; c = c.previousElementSibling) stack.push(c);
  }
  return out;
}

/**
 * Pose un uid sur tout élément ayant des éléments enfants, ou déclaré complexe
 * par le schéma, qui n'en a pas encore.
 */
export function annotate(root: Element, counter: UidCounter, isComplex?: (el: Element) => boolean): void {
  const doc = root.ownerDocument;
  if (!doc.documentElement.hasAttributeNS(XMLNS_NS, UID_PREFIX)) {
    doc.documentElement.setAttributeNS(XMLNS_NS, `xmlns:${UID_PREFIX}`, UID_NS);
  }
  const stack: Element[] = [root];
  while (stack.length) {
    const el = stack.pop()!;
    if ((el.firstElementChild || isComplex?.(el)) && !getUid(el)) setUid(el, counter.next());
    for (let c = el.lastElementChild; c; c = c.previousElementSibling) stack.push(c);
  }
}

export function uidIndex(doc: Document): Map<string, Element> {
  const index = new Map<string, Element>();
  for (const el of allElements(doc.documentElement)) {
    const uid = getUid(el);
    if (uid) index.set(uid, el);
  }
  return index;
}

/** Uid de l'élément ou de son plus proche ancêtre annoté. */
export function nearestUid(el: Element | null): string | null {
  for (let n = el; n; n = n.parentElement) {
    const uid = getUid(n);
    if (uid) return uid;
  }
  return null;
}
