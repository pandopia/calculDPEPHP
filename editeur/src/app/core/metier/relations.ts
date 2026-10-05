import { KINDS, KindDef, REFS, RefDef } from './catalog';
import { objectsOfKind, textOf } from '../edition/doc-ops';
import { getUid } from '../xml/uid';

/**
 * Graphe des relations entre objets, dans les deux sens.
 *
 * - mode `id` : la valeur du champ est la `reference` de l'objet cible ;
 * - mode `shared` : générateur mixte. Le XSD prévoit la même valeur des deux
 *   côtés ; certains logiciels écrivent la `reference` de l'autre partie.
 *   Les deux conventions sont reconnues en lecture.
 * Le rôle générateur ↔ émetteur (`enum_lien_generateur_emetteur_id`, même
 * code au sein d'une installation) est un lien par rôle, pas par identifiant :
 * il est restitué à part (`roleLinks`).
 */
export type LinkStatus = 'ok' | 'non_resolu' | 'type_incompatible' | 'ambigu';

export interface RefLink {
  ref: RefDef;
  from: Element;
  fromUid: string;
  value: string;
  targets: Element[];
  status: LinkStatus;
}

export interface ObjectEntry {
  el: Element;
  uid: string;
  kind: KindDef;
  reference: string | null;
}

export class RelationGraph {
  readonly objects: ObjectEntry[] = [];
  readonly byUid = new Map<string, ObjectEntry>();
  readonly byReference = new Map<string, ObjectEntry[]>();
  readonly outgoing = new Map<string, RefLink[]>();
  readonly incoming = new Map<string, RefLink[]>();
  readonly duplicateReferences = new Map<string, ObjectEntry[]>();

  constructor(doc: Document) {
    for (const kind of KINDS) {
      for (const el of objectsOfKind(doc, kind)) {
        const uid = getUid(el);
        if (!uid) continue;
        const entry: ObjectEntry = { el, uid, kind, reference: kind.idField ? textOf(el, kind.idField) : null };
        this.objects.push(entry);
        this.byUid.set(uid, entry);
        if (entry.reference) {
          const list = this.byReference.get(entry.reference) ?? [];
          list.push(entry);
          this.byReference.set(entry.reference, list);
        }
      }
    }
    for (const [ref, list] of this.byReference) if (list.length > 1) this.duplicateReferences.set(ref, list);

    const sharedIndex = new Map<string, ObjectEntry[]>();
    for (const r of REFS.filter((x) => x.mode === 'shared')) {
      for (const o of this.objects.filter((x) => r.fromKinds.includes(x.kind.key))) {
        const v = textOf(o.el, r.field);
        if (!v) continue;
        const key = r.key + '\u0000' + v;
        sharedIndex.set(key, [...(sharedIndex.get(key) ?? []), o]);
      }
    }

    for (const o of this.objects) {
      for (const r of REFS.filter((x) => x.fromKinds.includes(o.kind.key))) {
        const value = textOf(o.el, r.field);
        if (!value) continue;
        let targets: ObjectEntry[];
        let status: LinkStatus;
        if (r.mode === 'shared') {
          // Convention XSD : même valeur des deux côtés. Convention observée
          // chez certains logiciels : la valeur est la `reference` de l'autre
          // partie (renvoi croisé). Les deux sont reconnues.
          const same = (sharedIndex.get(r.key + '\u0000' + value) ?? []).filter((t) => t !== o && t.kind.key !== o.kind.key);
          const cross = (this.byReference.get(value) ?? []).filter((t) => t !== o && t.kind.key !== o.kind.key && r.toKinds.includes(t.kind.key));
          targets = [...new Set([...same, ...cross])];
          status = targets.length ? 'ok' : 'non_resolu';
        } else {
          const candidates = this.byReference.get(value) ?? [];
          targets = candidates.filter((t) => r.toKinds.includes(t.kind.key));
          status = targets.length === 1 ? 'ok' : targets.length > 1 ? 'ambigu' : candidates.length ? 'type_incompatible' : 'non_resolu';
        }
        const link: RefLink = { ref: r, from: o.el, fromUid: o.uid, value, targets: targets.map((t) => t.el), status };
        this.outgoing.set(o.uid, [...(this.outgoing.get(o.uid) ?? []), link]);
        for (const t of targets) this.incoming.set(t.uid, [...(this.incoming.get(t.uid) ?? []), link]);
      }
    }
  }

  /** Objets compatibles pour un champ de référence, hors l'objet lui-même. */
  candidates(ref: RefDef, from: Element): ObjectEntry[] {
    return this.objects.filter((o) => ref.toKinds.includes(o.kind.key) && o.el !== from);
  }

  /** Liens de rôle générateur ↔ émetteur dans une installation de chauffage. */
  roleLinks(obj: ObjectEntry): ObjectEntry[] {
    if (obj.kind.key !== 'generateur_chauffage' && obj.kind.key !== 'emetteur_chauffage') return [];
    const role = textOf(obj.el, 'donnee_entree/enum_lien_generateur_emetteur_id');
    if (!role) return [];
    const installation = obj.el.parentElement?.parentElement ?? null;
    const other = obj.kind.key === 'generateur_chauffage' ? 'emetteur_chauffage' : 'generateur_chauffage';
    return this.objects.filter(
      (o) => o.kind.key === other && o.el.parentElement?.parentElement === installation &&
        textOf(o.el, 'donnee_entree/enum_lien_generateur_emetteur_id') === role,
    );
  }
}
