/**
 * Chargement XML défensif, entièrement local.
 *
 * - aucune DTD acceptée : `<!DOCTYPE` / `<!ENTITY` sont refusés (pas
 *   d'entités externes, pas d'expansion d'entités) ;
 * - aucune ressource réseau : DOMParser ne charge rien ;
 * - taille et profondeur bornées.
 */
export const MAX_BYTES = 20 * 1024 * 1024;
export const MAX_DEPTH = 64;

export class XmlLoadError extends Error {}

/** Décode un fichier selon l'encodage déclaré dans son prologue (UTF-8 par défaut). */
export function decodeXmlBytes(bytes: Uint8Array): string {
  if (bytes.byteLength > MAX_BYTES) {
    throw new XmlLoadError(
      `Fichier trop volumineux (${(bytes.byteLength / 1048576).toFixed(1).replace('.', ',')} Mo, maximum ${MAX_BYTES / 1048576} Mo).`,
    );
  }
  const head = new TextDecoder('ascii').decode(bytes.slice(0, 200));
  const declared = /encoding\s*=\s*["']([A-Za-z0-9._-]+)["']/.exec(head)?.[1] ?? 'utf-8';
  try {
    return new TextDecoder(declared.toLowerCase(), { fatal: false }).decode(bytes);
  } catch {
    throw new XmlLoadError(`Encodage « ${declared} » non pris en charge.`);
  }
}

export function parseXml(text: string, parser: DOMParser = new DOMParser()): XMLDocument {
  if (text.length > MAX_BYTES) {
    throw new XmlLoadError(`Fichier trop volumineux (maximum ${MAX_BYTES / 1048576} Mo).`);
  }
  if (text.trim() === '') throw new XmlLoadError('Le fichier est vide.');
  if (/<!DOCTYPE|<!ENTITY/i.test(text)) {
    throw new XmlLoadError(
      "Le fichier contient une déclaration DOCTYPE ou ENTITY : refusée par sécurité (un XML DPE n'en comporte pas).",
    );
  }
  const doc = parser.parseFromString(text, 'application/xml');
  const error = doc.getElementsByTagName('parsererror')[0];
  if (error || !doc.documentElement) {
    const detail = (error?.textContent ?? '').replace(/\s+/g, ' ').trim();
    throw new XmlLoadError('XML mal formé' + (detail ? ' : ' + detail : '.'));
  }
  const depth = maxDepth(doc.documentElement);
  if (depth > MAX_DEPTH) {
    throw new XmlLoadError(`Profondeur XML excessive (plus de ${MAX_DEPTH} niveaux).`);
  }
  return doc;
}

function maxDepth(root: Element): number {
  let max = 0;
  const stack: [Element, number][] = [[root, 1]];
  while (stack.length) {
    const [el, d] = stack.pop()!;
    if (d > max) max = d;
    if (d > MAX_DEPTH) return d;
    for (let c = el.firstElementChild; c; c = c.nextElementSibling) stack.push([c, d + 1]);
  }
  return max;
}

export function childElements(el: Element): Element[] {
  const out: Element[] = [];
  for (let c = el.firstElementChild; c; c = c.nextElementSibling) out.push(c);
  return out;
}

export function firstChild(el: Element | null, name: string): Element | null {
  if (!el) return null;
  for (let c = el.firstElementChild; c; c = c.nextElementSibling) if (c.localName === name) return c;
  return null;
}

/** Résout un chemin relatif `a/b/c` (premier élément de chaque nom). */
export function resolvePath(el: Element | null, rel: string): Element | null {
  let cur = el;
  for (const part of rel.split('/')) {
    if (!cur || part === '') return part === '' ? cur : null;
    cur = firstChild(cur, part);
  }
  return cur;
}

export function hasElementChildren(el: Element): boolean {
  return el.firstElementChild !== null;
}

/** Chemin XML indexé lisible, ex. `/dpe/logement/enveloppe/mur_collection/mur[3]`. */
export function indexedPath(el: Element): string {
  const parts: string[] = [];
  for (let n: Element | null = el; n; n = n.parentElement) {
    let i = 1;
    let same = 0;
    for (let s: Element | null = n.parentElement?.firstElementChild ?? null; s; s = s.nextElementSibling) {
      if (s.localName === n.localName) {
        same++;
        if (s === n) i = same;
      }
    }
    parts.unshift(same > 1 ? `${n.localName}[${i}]` : n.localName);
  }
  return '/' + parts.join('/');
}
