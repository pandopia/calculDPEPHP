import { UID_NS, UID_PREFIX, XMLNS_NS } from './uid';

/**
 * Sérialiseur XML déterministe.
 *
 * - retire les identifiants internes `ed:uid` et la déclaration `xmlns:ed` ;
 * - conserve tout le reste : balises inconnues, attributs, autres espaces de
 *   noms, commentaires, instructions de traitement, CDATA, textes au
 *   caractère près (aucun nombre n'est reformaté) ;
 * - réindente uniquement le contenu « éléments seuls » (deux espaces) : le
 *   contenu textuel ou mixte est écrit tel quel ;
 * - mémorise la ligne de début de chaque élément, pour rattacher une erreur
 *   de validation XSD (numéro de ligne libxml) au nœud du document de travail.
 */
export interface SerializeOptions {
  /** Éléments à omettre (ex. en-tête observatoire pour la validation de dépôt). */
  omit?: (el: Element) => boolean;
  keepUids?: boolean;
}

export interface Serialized {
  text: string;
  /** ligne (1-based) → élément du document source */
  lineToElement: Map<number, Element>;
}

const TEXT = 3;
const CDATA = 4;
const PI = 7;
const COMMENT = 8;
const ELEMENT = 1;

export function serialize(doc: Document, options: SerializeOptions = {}): Serialized {
  const out: string[] = ['<?xml version="1.0" encoding="UTF-8"?>\n'];
  let line = 2;
  const lineToElement = new Map<number, Element>();

  const write = (s: string) => {
    out.push(s);
    for (let i = s.indexOf('\n'); i !== -1; i = s.indexOf('\n', i + 1)) line++;
  };

  const attrs = (el: Element): string => {
    let s = '';
    for (const a of Array.from(el.attributes)) {
      if (!options.keepUids) {
        if (a.namespaceURI === UID_NS) continue;
        if (a.namespaceURI === XMLNS_NS && a.localName === UID_PREFIX) continue;
      }
      s += ` ${a.name}="${escapeAttr(a.value)}"`;
    }
    return s;
  };

  const inline = (node: Node) => {
    switch (node.nodeType) {
      case ELEMENT: {
        const el = node as Element;
        if (options.omit?.(el)) return;
        lineToElement.set(line, el);
        if (!el.firstChild) {
          write(`<${el.tagName}${attrs(el)}/>`);
          return;
        }
        write(`<${el.tagName}${attrs(el)}>`);
        el.childNodes.forEach(inline);
        write(`</${el.tagName}>`);
        return;
      }
      case TEXT:
        write(escapeText(node.nodeValue ?? ''));
        return;
      case CDATA:
        write(`<![CDATA[${node.nodeValue ?? ''}]]>`);
        return;
      case COMMENT:
        write(`<!--${node.nodeValue ?? ''}-->`);
        return;
      case PI:
        write(`<?${(node as ProcessingInstruction).target} ${node.nodeValue ?? ''}?>`);
        return;
    }
  };

  const block = (el: Element, indent: string) => {
    if (options.omit?.(el)) return;
    const children = Array.from(el.childNodes);
    const elementOnly =
      children.some((c) => c.nodeType === ELEMENT) &&
      children.every((c) => c.nodeType !== TEXT || (c.nodeValue ?? '').trim() === '') &&
      !children.some((c) => c.nodeType === CDATA);
    if (!elementOnly) {
      write(indent);
      inline(el);
      write('\n');
      return;
    }
    lineToElement.set(line, el);
    write(`${indent}<${el.tagName}${attrs(el)}>\n`);
    for (const c of children) {
      if (c.nodeType === ELEMENT) block(c as Element, indent + '  ');
      else if (c.nodeType === COMMENT || c.nodeType === PI) {
        write(indent + '  ');
        inline(c);
        write('\n');
      }
    }
    write(`${indent}</${el.tagName}>\n`);
  };

  for (const node of Array.from(doc.childNodes)) {
    if (node.nodeType === ELEMENT) block(node as Element, '');
    else if (node.nodeType === COMMENT || node.nodeType === PI) {
      inline(node);
      write('\n');
    }
  }
  return { text: out.join(''), lineToElement };
}

export function exportXml(doc: Document): string {
  return serialize(doc).text;
}

function escapeText(s: string): string {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\r/g, '&#13;');
}

function escapeAttr(s: string): string {
  return s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/\n/g, '&#10;')
    .replace(/\r/g, '&#13;')
    .replace(/\t/g, '&#9;');
}
