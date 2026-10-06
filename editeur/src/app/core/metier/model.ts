import { ElementDef, isEnum } from '../schema/element-def';
import { schemaPath, SchemaHandle } from '../schema/schema-registry';
import { Dossier } from '../state/dossier';
import { childElements, indexedPath, resolvePath } from '../xml/safe-xml';
import { getUid, uidIndex } from '../xml/uid';
import { isNil, kindOf, objectsOfKind, ownerObject, relativePath, SINGLETON_PATHS, textOf } from '../edition/doc-ops';
import { displayRounded, displayValue, facetViolations } from '../edition/value-codec';
import { GRAVITE_ORDER, Issue } from '../validation/issues';
import { validateDocument } from '../validation/validator';
import { isObservatoireHeader } from '../format/format-detector';
import { applicability } from './display-rules';
import { fieldMeta } from './field-meta';
import { RelationGraph } from './relations';
import { isResultPath, KIND_BY_KEY, KINDS, KindDef, refsForField, SectionDef, sectionDescendants, TabDef, TabKey, TABS } from './catalog';

/** Vues métier calculées à partir du document de travail. */

export type SectionStatus = 'complet' | 'a_completer' | 'erreurs' | 'vide' | 'non_applicable' | 'absent';

export interface ObjectView {
  uid: string;
  kind: KindDef | null;
  label: string;
  name: string;
  title: string;
  tab: TabKey;
  section: string;
  parentUid: string | null;
  childUids: string[];
  summary: { label: string; value: string }[];
  reference: string | null;
  xmlPath: string;
  errors: number;
  warnings: number;
  missing: number;
  modified: boolean;
  added: boolean;
  results: boolean;
  searchText: string;
}

export interface SectionView {
  def: SectionDef;
  tab: TabKey;
  count: number;
  uids: string[];
  singletonUid: string | null;
  status: SectionStatus;
  errors: number;
  warnings: number;
  missing: number;
  note: string | null;
  minItems: number;
}

export interface TabView {
  def: TabDef;
  sections: SectionView[];
  count: number;
  errors: number;
  warnings: number;
  missing: number;
  status: SectionStatus;
}

export interface Model {
  revision: number;
  objects: Map<string, ObjectView>;
  tabs: TabView[];
  issues: Issue[];
  graph: RelationGraph;
  modifiedUids: Set<string>;
  /** sections de premier niveau non couvertes par le catalogue */
  otherSections: { uid: string; label: string; path: string }[];
}

const IMMEUBLE = ['6', '7', '8', '9', '26', '27', '28', '29', '30'];

export function buildModel(d: Dossier): Model {
  const doc = d.working;
  const graph = new RelationGraph(doc);
  const modifiedUids = changedUids(d);
  const baselineUids = d.baseIndex();
  const issues = validateDocument(d, graph).sort((a, b) => GRAVITE_ORDER[a.gravite] - GRAVITE_ORDER[b.gravite]);
  const issuesByUid = new Map<string, Issue[]>();
  for (const i of issues) if (i.uid) issuesByUid.set(i.uid, [...(issuesByUid.get(i.uid) ?? []), i]);

  const objects = new Map<string, ObjectView>();
  const counters = (uid: string) => {
    const list = issuesByUid.get(uid) ?? [];
    return {
      errors: list.filter((i) => i.gravite === 'erreur').length,
      warnings: list.filter((i) => i.gravite === 'avertissement' && i.niveau !== 'completude').length,
      missing: list.filter((i) => i.niveau === 'completude').length,
    };
  };
  // objet propriétaire de chaque élément modifié (sans parcours quadratique)
  const index = d.index();
  const modifiedOwners = new Set<Element>();
  for (const u of modifiedUids) {
    const el = index.get(u);
    for (let n: Element | null = el ?? null; n; n = n.parentElement) {
      const p = schemaPath(n);
      if (KINDS.some((k) => k.path === p) || SINGLETON_PATHS.has(p)) {
        modifiedOwners.add(n);
        break;
      }
    }
  }
  const isModified = (el: Element): boolean => modifiedOwners.has(el);

  for (const entry of graph.objects) {
    const { el, uid, kind } = entry;
    const name = kind.nameField ? textOf(el, kind.nameField) : null;
    const position = Array.from(el.parentElement?.children ?? []).filter((c) => c.localName === el.localName).indexOf(el) + 1;
    const parentObj = kind.parentKind ? ownerObject(el.parentElement) : null;
    const summary = kind.summary
      .map((rel) => {
        const leaf = resolvePath(el, rel);
        const v = textOf(el, rel);
        if (!leaf || v === null) return null;
        const def = d.schema?.def(schemaPath(leaf)) ?? null;
        const meta = fieldMeta(leaf.localName, def);
        const shown = isResultPath(schemaPath(leaf)) && !def?.enumLabels ? displayRounded(v, 0) : displayValue(v, def);
        return { label: meta.label, value: shown + (meta.unit && !def?.enumLabels ? ' ' + meta.unit : '') };
      })
      .filter((x): x is { label: string; value: string } => x !== null);
    const title = name ?? `${kind.label} n° ${position}`;
    objects.set(uid, {
      uid, kind, label: kind.label, name: name ?? '', title,
      tab: kind.tab, section: kind.section,
      parentUid: parentObj ? getUid(parentObj) : null,
      childUids: [],
      summary,
      reference: entry.reference,
      xmlPath: indexedPath(el),
      ...counters(uid),
      modified: isModified(el),
      added: !baselineUids.has(uid),
      results: !!kind.results,
      searchText: [title, kind.label, entry.reference ?? '', ...summary.map((s) => s.value)].join(' ').toLowerCase(),
    });
  }
  for (const o of objects.values()) if (o.parentUid) objects.get(o.parentUid)?.childUids.push(o.uid);

  // singletons de section
  const singletonUid = new Map<string, string | null>();
  for (const path of SINGLETON_PATHS) {
    const segments = path.split('/');
    let el: Element | null = doc.documentElement.localName === segments[0] ? doc.documentElement : null;
    for (const s of segments.slice(1)) el = el ? (childElements(el).find((c) => c.localName === s) ?? null) : null;
    const uid = el ? getUid(el) : null;
    singletonUid.set(path, uid);
    if (el && uid) {
      const tab = TABS.find((t) => t.sections.some((s) => s.singleton === path))!;
      const sec = tab.sections.find((s) => s.singleton === path)!;
      objects.set(uid, {
        uid, kind: null, label: sec.label, name: sec.label, title: sec.label, tab: tab.key, section: sec.key,
        parentUid: null, childUids: [], summary: [], reference: null, xmlPath: indexedPath(el), ...counters(uid),
        modified: isModified(el), added: !baselineUids.has(uid), results: !!sec.results, searchText: sec.label.toLowerCase(),
      });
    }
  }

  const methode = textOf(doc.documentElement, 'logement/caracteristique_generale/enum_methode_application_dpe_log_id');
  const tabs: TabView[] = TABS.map((tab) => {
    const sections = tab.sections.map((sec): SectionView => {
      let uids: string[] = [];
      let sUid: string | null = null;
      let minItems = 0;
      let note: string | null = null;
      if (sec.kind) {
        const kind = KIND_BY_KEY.get(sec.kind)!;
        uids = [...objects.values()].filter((o) => o.kind?.key === kind.key).map((o) => o.uid);
        minItems = d.schema?.def(kind.path)?.minOccurs ?? 0;
      } else if (sec.singleton) {
        sUid = singletonUid.get(sec.singleton) ?? null;
      }
      const related = sec.kind
        ? [...objects.values()].filter((o) => o.section === sec.key && o.tab === tab.key)
        : sUid ? [objects.get(sUid)!] : [];
      const errors = related.reduce((n, o) => n + o.errors, 0);
      const warnings = related.reduce((n, o) => n + o.warnings, 0);
      let missing = related.reduce((n, o) => n + o.missing, 0);
      let status: SectionStatus;
      const singletonDef = sec.singleton ? d.schema?.def(sec.singleton) : null;
      if (sec.key === 'logements_visites' && methode && !IMMEUBLE.includes(methode) && uids.length === 0) {
        status = 'non_applicable';
        note = 'Ne concerne que les DPE à l\'immeuble.';
      } else if (sec.kind && uids.length === 0) {
        status = minItems > 0 ? 'a_completer' : 'vide';
        if (minItems > 0) missing++;
        note = minItems > 0 ? `Au moins ${minItems} élément(s) exigé(s) par le schéma.` : 'Aucun élément déclaré (facultatif).';
      } else if (sec.singleton && sUid && d.prunable().has(index.get(sUid)!)) {
        status = 'vide';
        note = 'Bloc facultatif non renseigné.';
      } else if (sec.singleton && !sUid) {
        if (sec.results) {
          status = 'absent';
          note = 'Résultat absent du fichier : il doit être produit par un moteur de calcul.';
        } else if (singletonDef && singletonDef.minOccurs > 0) {
          status = 'vide';
          note = 'Bloc non renseigné : il sera contrôlé dès qu\'une valeur y sera saisie.';
        } else {
          status = 'non_applicable';
          note = singletonDef ? 'Bloc facultatif, non renseigné.' : 'Non prévu par la version du schéma de ce fichier.';
        }
      } else {
        status = errors ? 'erreurs' : missing ? 'a_completer' : 'complet';
      }
      return { def: sec, tab: tab.key, count: sec.kind ? uids.length : sUid ? 1 : 0, uids, singletonUid: sUid, status, errors, warnings, missing, note, minItems };
    });
    const errors = sections.reduce((n, s) => n + s.errors, 0);
    const missing = sections.reduce((n, s) => n + s.missing, 0);
    return {
      def: tab, sections,
      count: sections.reduce((n, s) => n + (s.def.kind ? s.count : 0), 0),
      errors, warnings: sections.reduce((n, s) => n + s.warnings, 0), missing,
      status: sections.length === 0 ? 'complet' : errors ? 'erreurs' : missing ? 'a_completer' : sections.every((s) => s.status === 'non_applicable' || s.status === 'absent') ? 'non_applicable' : 'complet',
    };
  });

  const covered = new Set<string>([...KINDS.map((k) => k.path.split('/').slice(0, 2).join('/')), ...[...SINGLETON_PATHS].map((p) => p.split('/').slice(0, 2).join('/'))]);
  const otherSections = childElements(doc.documentElement)
    .filter((c) => !covered.has(`${doc.documentElement.localName}/${c.localName}`) && !isObservatoireHeader(c))
    .map((c) => ({ uid: getUid(c) ?? '', label: fieldMeta(c.localName, null).label, path: indexedPath(c) }))
    .filter((c) => c.uid);
  // branche logement_neuf/tertiaire : vue générique
  return { revision: d.revision, objects, tabs, issues, graph, modifiedUids, otherSections };
}

/** Uid dont le contenu propre (feuilles, attributs) diffère de l'import, ou nouveaux. */
export function changedUids(d: Dossier): Set<string> {
  const base = d.baseIndex();
  const work = d.index();
  const out = new Set<string>();
  for (const [uid, el] of work) {
    const b = base.get(uid);
    if (!b || ownSignature(b) !== ownSignature(el)) out.add(uid);
  }
  for (const [uid, b] of base) {
    if (!work.has(uid)) {
      const parent = b.parentElement ? getUid(b.parentElement) : null;
      if (parent) out.add(parent);
    }
  }
  return out;
}

export function ownSignature(el: Element): string {
  const parts: string[] = [];
  for (const a of Array.from(el.attributes)) if (!a.name.startsWith('ed:') && a.name !== 'xmlns:ed') parts.push(`@${a.name}=${a.value}`);
  for (const c of childElements(el)) {
    if (getUid(c)) parts.push(`<${c.localName}#${getUid(c)}>`);
    else parts.push(`${c.localName}${isNil(c) ? '∅' : ''}=${c.textContent}${Array.from(c.attributes).map((a) => a.name + a.value).join('')}`);
  }
  return parts.join('|');
}

// ================================================================ fiche

export type FieldInput = 'enum' | 'number' | 'integer' | 'text' | 'date' | 'datetime' | 'reference' | 'oui_non';
export type ValueState = 'absent' | 'vide' | 'nil' | 'valeur';

export interface FieldView {
  rel: string;
  name: string;
  label: string;
  unit: string;
  help: string | null;
  input: FieldInput;
  required: boolean;
  nillable: boolean;
  state: ValueState;
  raw: string | null;
  display: string;
  options: { code: string; label: string }[];
  unknownCode: boolean;
  isTableId: boolean;
  ref: null | {
    key: string;
    label: string;
    status: string;
    targets: { uid: string; label: string }[];
    candidates: { uid: string; label: string }[];
    doc: string;
  };
  issues: Issue[];
  applicable: boolean;
  applicabilitySource: string | null;
  modified: boolean;
  before: string | null;
  xmlPath: string;
  schemaPath: string;
  readOnly: boolean;
  inSchema: boolean;
  choiceGroup: number | null;
}

export interface GroupView {
  key: string;
  label: string;
  rel: string;
  result: boolean;
  fields: FieldView[];
  notApplicable: FieldView[];
  groups: GroupView[];
  /** éléments répétables génériques (ex. masques lointains) */
  repeatables: { name: string; label: string; rel: string; items: { uid: string; label: string; group: GroupView }[]; canAdd: boolean }[];
  /** balises hors schéma conservées (rel : chemin depuis l'objet propriétaire) */
  unknown: { name: string; xml: string; rel?: string }[];
  present: boolean;
  /** identifiant du bloc (s'il est présent) */
  uid: string | null;
  /** bloc facultatif : peut être retiré / ajouté explicitement */
  optional: boolean;
}

export interface RelationView {
  label: string;
  items: { uid: string; label: string; kindLabel: string; status?: string }[];
  refKey?: string;
  doc?: string;
}

export interface FicheView {
  uid: string;
  title: string;
  kind: KindDef | null;
  path: string;
  xmlPath: string;
  results: boolean;
  groups: GroupView[];
  parent: { uid: string; label: string } | null;
  children: { kind: KindDef; items: { uid: string; title: string; summary: string }[]; min: number }[];
  outgoing: RelationView[];
  incoming: RelationView[];
  roles: RelationView[];
  issues: Issue[];
  attributes: { name: string; value: string | null; required: boolean; doc: string | null }[];
  links: { label: string; uid: string; tab?: TabKey; section?: string }[];
}

export function buildFiche(d: Dossier, model: Model, uid: string): FicheView | null {
  const el = d.index().get(uid);
  if (!el) return null;
  const kind = kindOf(el) ?? null;
  const view = model.objects.get(uid);
  const baseEl = d.baseIndex().get(uid) ?? null;
  const issues = model.issues.filter((i) => i.uid === uid);
  const results = isResultPath(schemaPath(el)) || !!view?.results;
  const ctx: FicheCtx = { d, model, owner: el, baseOwner: baseEl, kind: kind?.key, issues, results };

  const groups: GroupView[] = [];
  const rootFields = buildGroup(ctx, el, '', kind ? 'Informations' : (view?.title ?? fieldMeta(el.localName, null).label), true);
  // les enfants complexes deviennent des groupes ; les liens vers d'autres sections restent des liens
  const links: FicheView["links"] = [];
  const nested: GroupView[] = [];
  for (const g of rootFields.groups) {
    const childEl = resolvePath(el, g.rel);
    const childPath = childEl ? schemaPath(childEl) : schemaPath(el) + '/' + g.rel;
    if (SINGLETON_PATHS.has(childPath)) {
      const u = childEl ? getUid(childEl) : null;
      if (u) links.push({ label: g.label, uid: u });
      continue;
    }
    nested.push(g);
  }
  rootFields.groups = [];
  const hasContent = (g: GroupView): boolean => g.fields.length + g.notApplicable.length + g.repeatables.length + g.unknown.length > 0 || g.groups.some(hasContent);
  for (let i = nested.length - 1; i >= 0; i--) if (!hasContent(nested[i]) && !nested[i].result) nested.splice(i, 1);
  // sous-blocs ayant leur propre section (ex. adresses sous la localisation) : liens
  const myPath = schemaPath(el);
  for (const p of SINGLETON_PATHS) {
    if (!p.startsWith(myPath + '/') || links.some((l) => model.objects.get(l.uid)?.xmlPath.endsWith(p.split('/').pop()!))) continue;
    const between = p.slice(myPath.length + 1).split('/');
    const intermediate = between.slice(0, -1).some((_, i) => SINGLETON_PATHS.has(myPath + '/' + between.slice(0, i + 1).join('/')));
    if (intermediate) continue;
    const sec = TABS.flatMap((t) => t.sections).find((s) => s.singleton === p);
    const target = [...model.objects.values()].find((o) => o.section === sec?.key);
    const tab = TABS.find((t) => t.sections.includes(sec!))?.key;
    if (sec && target && !links.some((l) => l.uid === target.uid)) links.push({ label: sec.label, uid: target.uid });
    else if (sec && !target) links.push({ label: `${sec.label} (à renseigner)`, uid: '', tab, section: sec.key });
  }
  if (rootFields.fields.length || rootFields.notApplicable.length || rootFields.unknown.length || rootFields.repeatables.length) groups.push(rootFields);
  groups.push(...nested);

  const graph = model.graph;
  const parentObj = kind?.parentKind ? ownerObject(el.parentElement) : null;
  const elPath = schemaPath(el);
  const children = KINDS.filter((k) =>
    kind ? k.parentKind === kind.key
      : !k.parentKind && el.parentElement !== null && k.path.startsWith(elPath + '/') && k.path.split('/').length === elPath.split('/').length + 2,
  ).map((k) => {
    const items = [...model.objects.values()].filter((o) => o.kind?.key === k.key && (kind ? o.parentUid === uid : true));
    return { kind: k, items: items.map((o) => ({ uid: o.uid, title: o.title, summary: o.summary.map((s) => s.value).join(' · ') })), min: d.schema?.def(k.path)?.minOccurs ?? 0 };
  });

  const label = (e: Element) => model.objects.get(getUid(e) ?? '')?.title ?? e.localName;
  const outgoing: RelationView[] = (graph.outgoing.get(uid) ?? []).map((l) => ({
    label: l.ref.label, refKey: l.ref.key, doc: l.ref.doc,
    items: l.targets.length
      ? l.targets.map((t) => ({ uid: getUid(t)!, label: label(t), kindLabel: kindOf(t)?.label ?? '', status: l.status }))
      : [{ uid: '', label: `« ${l.value} » (non résolu)`, kindLabel: '', status: l.status }],
  }));
  const incomingMap = new Map<string, RelationView>();
  const outgoingTargets = new Set((graph.outgoing.get(uid) ?? []).flatMap((l) => l.targets));
  for (const l of graph.incoming.get(uid) ?? []) {
    // lien mixte déjà affiché dans le sens sortant : pas de doublon
    if (l.ref.mode === 'shared' && outgoingTargets.has(l.from)) continue;
    const key = l.ref.key === 'pt2' ? 'pt1' : l.ref.key;
    const rv = incomingMap.get(key) ?? { label: l.ref.inverseLabel, items: [], refKey: l.ref.key, doc: l.ref.doc };
    if (!rv.items.some((i) => i.uid === l.fromUid)) rv.items.push({ uid: l.fromUid, label: label(l.from), kindLabel: kindOf(l.from)?.label ?? '' });
    incomingMap.set(key, rv);
  }
  const roles: RelationView[] = [];
  const entry = graph.byUid.get(uid);
  if (entry) {
    const linked = graph.roleLinks(entry);
    if (entry.kind.key === 'generateur_chauffage' || entry.kind.key === 'emetteur_chauffage') {
      roles.push({
        label: entry.kind.key === 'generateur_chauffage' ? 'Émetteurs de même rôle' : 'Générateurs de même rôle',
        items: linked.map((o) => ({ uid: o.uid, label: label(o.el), kindLabel: o.kind.label })),
        doc: 'Lien par rôle (enum_lien_generateur_emetteur_id identique dans l\'installation) : génération principale, d\'appoint ou appoint électrique de salle de bain.',
      });
    }
  }

  const def = d.schema?.defFor(el) ?? null;
  const attributes = (def?.attributes ?? []).map((a) => ({ name: a.name, value: el.getAttribute(a.name), required: a.required, doc: a.doc }));

  return {
    uid, title: view?.title ?? fieldMeta(el.localName, null).label, kind, path: schemaPath(el), xmlPath: indexedPath(el), results,
    groups, parent: parentObj ? { uid: getUid(parentObj)!, label: label(parentObj) } : null,
    children, outgoing, incoming: [...incomingMap.values()], roles, issues, attributes, links,
  };
}

interface FicheCtx {
  d: Dossier;
  model: Model;
  owner: Element;
  baseOwner: Element | null;
  kind: string | undefined;
  issues: Issue[];
  results: boolean;
}

const SKIP_IN_FICHE = (def: ElementDef) => !!KINDS.find((k) => k.path.startsWith(def.path + '/') && k.path.split('/').length === def.path.split('/').length + 1);

function buildGroup(ctx: FicheCtx, container: Element | null, rel: string, label: string, present: boolean): GroupView {
  const { d } = ctx;
  const containerPath = container ? schemaPath(container) : schemaPath(ctx.owner) + (rel ? '/' + rel : '');
  const def = d.schema?.def(containerPath) ?? null;
  const result = ctx.results || isResultPath(containerPath);
  const group: GroupView = {
    key: rel || '.', label, rel, result, fields: [], notApplicable: [], groups: [], repeatables: [], unknown: [], present,
    uid: container && rel ? getUid(container) : null,
    optional: !!rel && !!def && def.minOccurs === 0 && !SINGLETON_PATHS.has(def.path),
  };
  const children = container ? childElements(container) : [];
  const seen = new Set<string>();

  const schemaChildren = def?.children ?? [];
  for (const cdef of schemaChildren) {
    seen.add(cdef.name);
    const childRel = rel ? `${rel}/${cdef.name}` : cdef.name;
    const instances = children.filter((c) => c.localName === cdef.name);
    if (cdef.kind === 'complex' || cdef.kind === 'any') {
      if (SKIP_IN_FICHE(cdef)) continue;
      if (SINGLETON_PATHS.has(cdef.path) && cdef.path !== containerPath) continue; // a sa propre section
      if (!def?.parent && cdef.path.split('/').length === 2) continue; // racine : chaque bloc a sa propre section
      if (cdef.maxOccurs === null || cdef.maxOccurs > 1) {
        group.repeatables.push({
          name: cdef.name, label: fieldMeta(cdef.name, cdef).label, rel: childRel,
          // chaque élément répété est son propre propriétaire : ses champs lui sont relatifs
          items: instances.map((inst, i) => {
            const itemUid = getUid(inst) ?? '';
            const sub: FicheCtx = { ...ctx, owner: inst, baseOwner: ctx.d.baseIndex().get(itemUid) ?? null, kind: undefined, issues: [] };
            return {
              uid: itemUid, label: `${fieldMeta(cdef.name, cdef).label} n° ${i + 1}`,
              group: buildGroup(sub, inst, '', `${fieldMeta(cdef.name, cdef).label} n° ${i + 1}`, true),
            };
          }),
          canAdd: !result && cdef.kind === 'complex',
        });
        continue;
      }
      const inst = instances[0] ?? null;
      if (!inst && isResultPath(cdef.path) && !result) {
        group.groups.push({ ...emptyGroup(childRel, groupLabel(cdef.name, cdef), true), present: false });
        continue;
      }
      if (cdef.kind === 'any') {
        group.unknown.push(...(inst ? [{ name: cdef.name, xml: (inst.innerHTML ?? '').slice(0, 2000) }] : []));
        continue;
      }
      group.groups.push(buildGroup(ctx, inst, childRel, groupLabel(cdef.name, cdef), !!inst));
      continue;
    }
    const leaf = instances[0] ?? null;
    const fv = fieldView(ctx, leaf, childRel, cdef);
    if (!fv.applicable && fv.state !== 'valeur') group.notApplicable.push(fv);
    else group.fields.push(fv);
  }
  // balises présentes hors schéma : conservées, affichées à part
  for (const c of children) {
    if (seen.has(c.localName) || !d.schema) {
      if (!d.schema && !seen.has(c.localName)) {
        seen.add(c.localName);
        const childRel = rel ? `${rel}/${c.localName}` : c.localName;
        if (c.firstElementChild) group.groups.push(buildGroup(ctx, c, childRel, groupLabel(c.localName, null), true));
        else group.fields.push(fieldView(ctx, c, childRel, null));
      }
      continue;
    }
    if (isObservatoireHeader(c)) {
      group.fields.push({ ...fieldView(ctx, c, c.localName, null), readOnly: true });
      continue;
    }
    group.unknown.push({ name: c.localName, rel: rel ? `${rel}/${c.localName}` : c.localName, xml: new XMLSerializer().serializeToString(c).replace(/ xmlns:ed="[^"]*"| ed:uid="[^"]*"/g, '').slice(0, 4000) });
  }
  return group;
}

function emptyGroup(rel: string, label: string, result: boolean): GroupView {
  return { key: rel, label, rel, result, fields: [], notApplicable: [], groups: [], repeatables: [], unknown: [], present: false, uid: null, optional: false };
}

function groupLabel(name: string, def: ElementDef | null): string {
  if (name === 'donnee_entree') return 'Données saisies';
  if (name === 'donnee_intermediaire') return 'Valeurs intermédiaires calculées';
  return fieldMeta(name, def).label;
}

function fieldView(ctx: FicheCtx, leaf: Element | null, rel: string, def: ElementDef | null): FieldView {
  const { d } = ctx;
  const name = rel.split('/').pop()!;
  const meta = fieldMeta(name, def);
  // une balise vide (remplissage du logiciel d'origine) est traitée comme absente
  const state: ValueState = !leaf ? 'absent' : isNil(leaf) ? 'nil' : (leaf.textContent ?? '').trim() === '' ? 'absent' : 'valeur';
  const raw = leaf && state === 'valeur' ? (leaf.textContent ?? '') : null;
  const ref = ctx.kind ? refsForField(ctx.kind, rel) : undefined;
  const baseLeaf = ctx.baseOwner ? resolvePath(ctx.baseOwner, rel) : null;
  const baseState = !baseLeaf ? 'absent' : isNil(baseLeaf) ? 'nil' : (baseLeaf.textContent ?? '').trim() === '' ? 'absent' : 'valeur';
  const modified = ctx.baseOwner ? baseState !== state || (state === 'valeur' && baseLeaf?.textContent !== leaf?.textContent) : state !== 'absent';
  const appl = applicability(ctx.kind, rel, (r) => textOf(ctx.owner, r));
  const input: FieldInput = ref
    ? 'reference'
    : def?.typeName === 's_oui_non' || (def?.enumLabels && Object.keys(def.enumLabels).join() === '0,1')
      ? 'oui_non'
      : def && isEnum(def) ? 'enum'
        : def?.base === 'int' ? 'integer'
          : def?.base && ['double', 'decimal', 'float'].includes(def.base) ? 'number'
            : def?.base === 'date' ? 'date' : def?.base === 'dateTime' ? 'datetime' : 'text';
  const options = def?.enumLabels ? Object.entries(def.enumLabels).map(([code, label]) => ({ code, label: label.charAt(0).toUpperCase() + label.slice(1) })) : [];
  const unknownCode = !!(raw !== null && def?.enumLabels && !(raw in def.enumLabels));
  const fieldIssues = ctx.issues.filter((i) => i.field === rel);
  const fv: FieldView = {
    rel, name, label: meta.label, unit: meta.unit, help: meta.help, input,
    required: (def?.minOccurs ?? 0) > 0 && def?.choiceGroup == null || appl.required,
    nillable: !!def?.nillable, state, raw,
    display: raw === null ? '' : displayValue(raw, def),
    options, unknownCode, isTableId: meta.isTableId, ref: null, issues: fieldIssues,
    applicable: appl.applicable, applicabilitySource: appl.rules[0]?.source ?? null,
    modified, before: modified && baseLeaf ? (baseState === 'valeur' ? displayValue(baseLeaf.textContent ?? '', def) : stateLabel(baseState)) : modified ? 'absent' : null,
    xmlPath: leaf ? indexedPath(leaf) : indexedPath(ctx.owner) + '/' + rel,
    schemaPath: def?.path ?? schemaPath(ctx.owner) + '/' + rel,
    readOnly: ctx.results || isResultPath(def?.path ?? schemaPath(ctx.owner) + '/' + rel) || (leaf ? isObservatoireHeader(leaf) : false),
    inSchema: !!def,
    choiceGroup: def?.choiceGroup ?? null,
  };
  if (ref) {
    const graph = ctx.model.graph;
    const link = (graph.outgoing.get(getUid(ctx.owner) ?? '') ?? []).find((l) => l.ref.key === ref.key);
    const label = (e: Element) => ctx.model.objects.get(getUid(e) ?? '')?.title ?? e.localName;
    fv.ref = {
      key: ref.key, label: ref.label, status: link?.status ?? (raw ? 'non_resolu' : 'vide'),
      targets: (link?.targets ?? []).map((t) => ({ uid: getUid(t)!, label: label(t) })),
      candidates: graph.candidates(ref, ctx.owner).map((c) => ({ uid: c.uid, label: `${label(c.el)} — ${c.kind.label}` })),
      doc: ref.doc,
    };
  }
  return fv;
}

export function stateLabel(state: ValueState): string {
  return state === 'absent' ? 'non renseigné' : state === 'nil' ? 'déclaré nul (xsi:nil)' : state === 'vide' ? 'balise vide' : '';
}

export { relativePath, objectsOfKind, facetViolations };


/**
 * Fiche d'un bloc absent du fichier : ses champs s'affichent vides et
 * saisissables. Ils sont rattachés au plus proche ancêtre présent ; la
 * première valeur saisie crée le bloc à sa place dans le XML.
 */
export function buildAbsentFiche(d: Dossier, model: Model, path: string, label: string): FicheView | null {
  const segments = path.split('/');
  let el: Element | null = d.working.documentElement.localName === segments[0] ? d.working.documentElement : null;
  let depth = 1;
  for (; el && depth < segments.length; depth++) {
    const next: Element | null = childElements(el).find((c) => c.localName === segments[depth]) ?? null;
    if (!next) break;
    el = next;
  }
  if (!el || depth >= segments.length) return null;
  const uid = getUid(el);
  if (!uid) return null;
  const rel = segments.slice(depth).join('/');
  const ctx: FicheCtx = { d, model, owner: el, baseOwner: d.baseIndex().get(uid) ?? null, kind: undefined, issues: [], results: false };
  const group = buildGroup(ctx, null, rel, label, false);
  return {
    uid, title: label, kind: null, path, xmlPath: indexedPath(el) + '/' + rel, results: false,
    groups: [{ ...group, present: true }], parent: null, children: [], outgoing: [], incoming: [], roles: [], issues: [], attributes: [], links: [],
  };
}

export interface Signal {
  errors: number;
  /** avertissements et éléments à compléter */
  warnings: number;
}

/** Points signalés sur un objet et ses sous-objets (ex. installation et ses générateurs). */
export function objectSignal(model: Model, uid: string): Signal {
  const out: Signal = { errors: 0, warnings: 0 };
  const walk = (u: string) => {
    const o = model.objects.get(u);
    if (!o) return;
    out.errors += o.errors;
    out.warnings += o.warnings + o.missing;
    o.childUids.forEach(walk);
  };
  walk(uid);
  return out;
}

/** Points signalés sur une section et ses sous-blocs ouverts en colonnes. */
export function sectionSignal(model: Model, tab: TabKey, key: string): Signal {
  const keys = new Set(sectionDescendants(tab, key));
  const secs = model.tabs.find((t) => t.def.key === tab)?.sections.filter((s) => keys.has(s.def.key)) ?? [];
  return { errors: secs.reduce((n, s) => n + s.errors, 0), warnings: secs.reduce((n, s) => n + s.missing + s.warnings, 0) };
}
