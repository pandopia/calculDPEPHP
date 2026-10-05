import { fieldMeta } from '../metier/field-meta';
import { schemaPath } from '../schema/schema-registry';
import { Dossier } from '../state/dossier';
import { childElements } from '../xml/safe-xml';
import { getUid, uidIndex } from '../xml/uid';
import { isNil, kindOf, ownerObject, relativePath, textOf } from './doc-ops';
import { displayValue } from './value-codec';

/**
 * Récapitulatif des modifications depuis l'import, en langage métier.
 * Les objets sont appariés par identifiant interne : un objet déplacé ou
 * renommé reste le même objet.
 */
export interface Change {
  type: 'ajout' | 'suppression' | 'modification';
  uid: string | null;
  objet: string;
  champ: string | null;
  field: string | null;
  avant: string | null;
  apres: string | null;
  xmlPath: string;
}

export function computeChanges(d: Dossier): Change[] {
  const base = uidIndex(d.baseline);
  const work = uidIndex(d.working);
  const out: Change[] = [];
  const label = (el: Element) => {
    const owner = ownerObject(el) ?? el;
    const k = kindOf(owner);
    const name = k?.nameField ? textOf(owner, k.nameField) : null;
    return (k?.label ?? fieldMeta(owner.localName, null).label) + (name ? ` « ${name} »` : '');
  };
  const show = (leaf: Element | undefined) => {
    if (!leaf) return 'non renseigné';
    if (isNil(leaf)) return 'déclaré nul';
    const raw = leaf.textContent ?? '';
    if (raw === '') return 'balise vide';
    const def = d.schema?.def(schemaPath(leaf)) ?? null;
    const shown = displayValue(raw, def);
    return shown === raw ? raw : `${shown} (${raw})`;
  };

  for (const [uid, el] of work) {
    const b = base.get(uid);
    if (!b) {
      const parent = el.parentElement;
      if (parent && !base.has(getUid(parent) ?? '')) continue; // inclus dans un ajout plus haut
      out.push({ type: 'ajout', uid: getUid(ownerObject(el) ?? el), objet: label(el), champ: kindOf(el) ? null : fieldMeta(el.localName, null).label, field: null, avant: null, apres: 'ajouté', xmlPath: schemaPath(el) });
      continue;
    }
    const owner = ownerObject(el) ?? el;
    const leavesW = leaves(el);
    const leavesB = leaves(b);
    for (const name of new Set([...leavesW.keys(), ...leavesB.keys()])) {
      const lw = leavesW.get(name);
      const lb = leavesB.get(name);
      if (sig(lw) === sig(lb)) continue;
      const rel = relativePath(owner, lw ?? el) + (lw ? '' : (owner === el ? '' : '/') + name);
      out.push({
        type: 'modification', uid: getUid(owner), objet: label(el),
        champ: fieldMeta(name, d.schema?.def(schemaPath(el) + '/' + name) ?? null).label,
        field: lw ? relativePath(owner, lw) : rel.replace(/^\//, ''),
        avant: show(lb), apres: show(lw), xmlPath: schemaPath(el) + '/' + name,
      });
    }
    const attrsW = attrs(el);
    const attrsB = attrs(b);
    for (const name of new Set([...Object.keys(attrsW), ...Object.keys(attrsB)])) {
      if (attrsW[name] === attrsB[name]) continue;
      out.push({ type: 'modification', uid: getUid(owner), objet: label(el), champ: `Attribut ${name}`, field: null, avant: attrsB[name] ?? 'absent', apres: attrsW[name] ?? 'absent', xmlPath: schemaPath(el) + '/@' + name });
    }
  }
  for (const [uid, b] of base) {
    if (work.has(uid)) continue;
    const parent = b.parentElement;
    if (parent && !work.has(getUid(parent) ?? '')) continue;
    out.push({ type: 'suppression', uid: parent ? getUid(ownerObject(work.get(getUid(parent)!) ?? null) ?? parent) : null, objet: label(b), champ: null, field: null, avant: 'présent', apres: 'supprimé', xmlPath: schemaPath(b) });
  }
  return out;
}

function leaves(el: Element): Map<string, Element> {
  const m = new Map<string, Element>();
  for (const c of childElements(el)) if (!getUid(c) && !m.has(c.localName)) m.set(c.localName, c);
  return m;
}

function sig(el: Element | undefined): string {
  return el ? `${isNil(el) ? 'nil' : ''}|${el.textContent}` : '∅';
}

function attrs(el: Element): Record<string, string> {
  const out: Record<string, string> = {};
  for (const a of Array.from(el.attributes)) if (!a.name.startsWith('ed:') && a.name !== 'xmlns:ed') out[a.name] = a.value;
  return out;
}
