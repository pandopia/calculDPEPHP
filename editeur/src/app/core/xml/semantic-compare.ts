import { UID_NS, UID_PREFIX, XMLNS_NS } from './uid';

/**
 * Compare deux documents XML au sens du contenu.
 *
 * Égaux si : mêmes éléments (nom qualifié, espace de noms) dans le même ordre,
 * mêmes attributs et déclarations d'espaces de noms (hors identifiants
 * internes), mêmes textes au caractère près, mêmes commentaires, PI et CDATA.
 * Seuls les nœuds texte blancs d'un contenu « éléments seuls » sont ignorés :
 * c'est la différence purement typographique admise.
 */
export function semanticDiff(a: Document, b: Document, max = 20): string[] {
  const out: string[] = [];
  compare(a.documentElement, b.documentElement, '/' + a.documentElement.tagName, out, max);
  return out;
}

function compare(a: Node, b: Node, path: string, out: string[], max: number): void {
  if (out.length >= max) return;
  if (a.nodeType !== b.nodeType || a.nodeName !== b.nodeName) {
    out.push(`${path} : nœud ${a.nodeName} ≠ ${b.nodeName}`);
    return;
  }
  if (a.nodeType !== 1) {
    if (a.nodeValue !== b.nodeValue) out.push(`${path} : « ${a.nodeValue} » ≠ « ${b.nodeValue} »`);
    return;
  }
  const ea = a as Element;
  const eb = b as Element;
  if (ea.namespaceURI !== eb.namespaceURI) out.push(`${path} : espace de noms ${ea.namespaceURI} ≠ ${eb.namespaceURI}`);
  const aa = attrs(ea);
  const ab = attrs(eb);
  if (aa !== ab) out.push(`${path} : attributs ${aa} ≠ ${ab}`);
  const ca = significant(ea);
  const cb = significant(eb);
  if (ca.length !== cb.length) {
    out.push(`${path} : ${ca.length} enfants ≠ ${cb.length}`);
    return;
  }
  const seen = new Map<string, number>();
  ca.forEach((child, i) => {
    const n = (seen.get(child.nodeName) ?? 0) + 1;
    seen.set(child.nodeName, n);
    compare(child, cb[i], `${path}/${child.nodeName}[${n}]`, out, max);
  });
}

function attrs(el: Element): string {
  const list: string[] = [];
  for (const a of Array.from(el.attributes)) {
    if (a.namespaceURI === UID_NS) continue;
    if (a.namespaceURI === XMLNS_NS && a.localName === UID_PREFIX) continue;
    list.push(`{${a.namespaceURI ?? ''}}${a.name}=${a.value}`);
  }
  return list.sort().join(' ');
}

function significant(el: Element): Node[] {
  const nodes = Array.from(el.childNodes);
  const hasElement = nodes.some((n) => n.nodeType === 1);
  return nodes.filter((n) => !(n.nodeType === 3 && hasElement && (n.nodeValue ?? '').trim() === ''));
}
