import { KIND_BY_KEY, KindDef, REFS, RefDef, isResultPath } from '../metier/catalog';
import { fieldMeta } from '../metier/field-meta';
import { RelationGraph } from '../metier/relations';
import { schemaPath } from '../schema/schema-registry';
import { Dossier } from '../state/dossier';
import { firstChild, resolvePath } from '../xml/safe-xml';
import { allElements, annotate, getUid, setUid, uidIndex } from '../xml/uid';
import {
  allReferences, collectionFor, createElement, createSkeleton, defAt, ensurePath, insertOrdered, kindOf,
  objectsOfKind, ownerObject, relativePath, setLeafText, setNil, textOf, uniqueValue,
} from './doc-ops';
import { facetViolations, parseInput } from './value-codec';

/**
 * Opérations d'édition. Chacune :
 *  - passe par `Dossier.transact` (annulable, horodatée) ;
 *  - ne touche que les nœuds concernés (le reste du document est intact) ;
 *  - ne laisse jamais de référence cassée qu'elle aurait créée ;
 *  - signale les champs susceptibles d'influencer le calcul (résultats à
 *    recalculer).
 */

/**
 * Champs purement descriptifs ou d'identification, qui n'entrent pas dans la
 * méthode 3CL : leur modification ne rend pas les résultats obsolètes. Toute
 * autre modification les marque « à recalculer » (pas de graphe de
 * dépendances de calcul fiable : on marque l'ensemble).
 */
const NON_CALCUL = new Set([
  'description', 'motif_remplacement', 'nom_proprietaire', 'siren_proprietaire', 'nom_proprietaire_installation_commune',
  'nom_diagnostiqueur', 'prenom_diagnostiqueur', 'mail_diagnostiqueur', 'telephone_diagnostiqueur', 'adresse_diagnostiqueur',
  'entreprise_diagnostiqueur', 'numero_certification_diagnostiqueur', 'organisme_certificateur', 'commentaire_travaux',
  'description_travaux', 'avertissement_travaux', 'categorie_geste_entretien', 'reference_interne_projet', 'ref_produit_ventilation',
  'ref_produit_generateur_ch', 'ref_produit_generateur_ecs', 'ref_produit_fr', 'detail_origine_donnee',
]);
const NON_CALCUL_ZONES = [/^dpe\/administratif\/geolocalisation\//, /^dpe\/administratif\/information_formulaire_consentement\//];

function affects(el: Element): boolean {
  const p = schemaPath(el);
  if (NON_CALCUL.has(el.localName)) return false;
  return !NON_CALCUL_ZONES.some((r) => r.test(p));
}

export class EditError extends Error {}

function objectLabel(obj: Element): string {
  const kind = kindOf(obj);
  const name = kind?.nameField ? textOf(obj, kind.nameField) : null;
  return (kind?.label ?? fieldMeta(obj.localName, null).label) + (name ? ` « ${name} »` : '');
}

function motif(obj: Element, leaf: Element): string {
  return `${objectLabel(obj)} : ${fieldMeta(leaf.localName, null).label}`;
}

function findObj(d: Dossier, uid: string): Element {
  const el = uidIndex(d.working).get(uid);
  if (!el) throw new EditError('Objet introuvable (il a peut-être été supprimé).');
  return el;
}

// ---------------------------------------------------------------- champs

export type FieldState = 'absent' | 'vide' | 'nil';

/** Saisit une valeur. `input` est la saisie utilisateur (virgule acceptée). */
export function setFieldValue(d: Dossier, uid: string, rel: string, input: string): { warnings: string[] } {
  const obj = findObj(d, uid);
  const def = defAt(d.schema, obj, rel);
  if (def && def.kind !== 'simple') throw new EditError('Ce champ n\'est pas une valeur simple.');
  const parsed = parseInput(input, def);
  if (!parsed.ok) throw new EditError(parsed.error);
  return d.transact(`Modifier ${fieldMeta(rel.split('/').pop()!, def).label}`, () => {
    const leaf = ensurePath(obj, rel, d.schema, d.counter);
    const before = leaf.textContent;
    setLeafText(leaf, parsed.value);
    const changed = before !== parsed.value;
    return { result: { warnings: facetViolations(parsed.value, def) }, affectsResults: changed && affects(leaf) ? [motif(obj, leaf)] : [] };
  });
}

/** Place un champ dans un état sans valeur : absent (balise retirée), vide, ou nul explicite (xsi:nil). */
export function setFieldState(d: Dossier, uid: string, rel: string, state: FieldState): void {
  const obj = findObj(d, uid);
  const def = defAt(d.schema, obj, rel);
  if (state === 'nil' && def && !def.nillable) throw new EditError('Le schéma n\'autorise pas la valeur nulle explicite pour ce champ.');
  d.transact(`${state === 'absent' ? 'Effacer' : state === 'nil' ? 'Déclarer nul' : 'Vider'} ${fieldMeta(rel.split('/').pop()!, def).label}`, () => {
    if (state === 'absent') {
      const leaf = resolvePath(obj, rel);
      if (!leaf) return { result: undefined, affectsResults: [] };
      const m = affects(leaf) ? [motif(obj, leaf)] : [];
      leaf.remove();
      return { result: undefined, affectsResults: m };
    }
    const leaf = ensurePath(obj, rel, d.schema, d.counter);
    if (state === 'nil') setNil(leaf);
    else setLeafText(leaf, '');
    return { result: undefined, affectsResults: affects(leaf) ? [motif(obj, leaf)] : [] };
  });
}

// ---------------------------------------------------------- références

/** Garantit que l'objet a une `reference` ; en attribue une si besoin. */
function ensureReference(d: Dossier, obj: Element, taken: Set<string>): string {
  const kind = kindOf(obj);
  if (!kind?.idField) throw new EditError(`${kind?.label ?? 'Cet objet'} ne porte pas d'identifiant de référence.`);
  const existing = textOf(obj, kind.idField);
  if (existing) return existing;
  const value = uniqueValue(kind.key, taken);
  setLeafText(ensurePath(obj, kind.idField, d.schema, d.counter), value);
  return value;
}

/** Associe (ou dissocie si `targetUid` est null) un objet à une cible via un champ de référence. */
export function setReference(d: Dossier, uid: string, refKey: string, targetUid: string | null): void {
  const obj = findObj(d, uid);
  const kind = kindOf(obj);
  const ref = REFS.find((r) => r.key === refKey && kind && r.fromKinds.includes(kind.key));
  if (!ref) throw new EditError('Relation non prévue pour cet objet.');
  const target = targetUid ? findObj(d, targetUid) : null;
  if (target && !ref.toKinds.includes(kindOf(target)?.key ?? '')) throw new EditError('Objet cible incompatible avec cette relation.');
  d.transact(`${target ? 'Associer' : 'Dissocier'} : ${ref.label}`, () => {
    applyReference(d, obj, ref, target, allReferences(d.working));
    return { result: undefined, affectsResults: [`${objectLabel(obj)} : ${ref.label}`] };
  });
}

function applyReference(d: Dossier, obj: Element, ref: RefDef, target: Element | null, taken: Set<string>): void {
  if (!target) {
    resolvePath(obj, ref.field)?.remove();
    return;
  }
  if (ref.mode === 'id') {
    const value = ensureReference(d, target, taken);
    setLeafText(ensurePath(obj, ref.field, d.schema, d.counter), value);
    return;
  }
  const keys = new Set<string>();
  for (const k of ref.fromKinds) for (const o of objectsOfKind(d.working, KIND_BY_KEY.get(k)!)) {
    const v = textOf(o, ref.field);
    if (v) keys.add(v);
  }
  const value = textOf(target, ref.field) ?? uniqueValue('generateur_mixte', keys);
  setLeafText(ensurePath(obj, ref.field, d.schema, d.counter), value);
  setLeafText(ensurePath(target, ref.field, d.schema, d.counter), value);
}

// --------------------------------------------------------------- ajout

export interface AddOptions {
  parentUid?: string | null;
  name?: string;
  /** rattachement automatique, ex. { refKey: 'paroi', targetUid: <mur> } */
  link?: { refKey: string; targetUid: string };
}

export function addObject(d: Dossier, kindKey: string, options: AddOptions = {}): string {
  const kind = KIND_BY_KEY.get(kindKey);
  if (!kind) throw new EditError('Type d\'objet inconnu.');
  if (d.format.niveauSupport !== 'complet' || d.working.documentElement.localName !== 'dpe') {
    throw new EditError('Ajout d\'objets métier réservé aux DPE logement existant d\'une version connue.');
  }
  const parent = options.parentUid ? findObj(d, options.parentUid) : null;
  const link = options.link
    ? { ref: REFS.find((r) => r.key === options.link!.refKey && r.fromKinds.includes(kind.key)), target: findObj(d, options.link.targetUid) }
    : null;
  if (link && !link.ref) throw new EditError('Rattachement non prévu pour ce type d\'objet.');
  return d.transact(`Ajouter ${kind.article} ${kind.label.toLowerCase()}`, () => {
    const container = collectionFor(kind, d.working, parent, d.schema, d.counter);
    const el = createElement(container, kind.path.split('/').pop()!);
    container.appendChild(el);
    annotate(el, d.counter, () => true);
    createSkeleton(el, d.schema, d.counter);
    const taken = allReferences(d.working);
    if (kind.idField) setLeafText(ensurePath(el, kind.idField, d.schema, d.counter), uniqueValue(kind.key, taken));
    if (options.name && kind.nameField) setLeafText(ensurePath(el, kind.nameField, d.schema, d.counter), options.name);
    if (link?.ref) applyReference(d, el, link.ref, link.target, taken);
    return { result: getUid(el)!, affectsResults: kind.results ? [] : [`Ajout : ${objectLabel(el)}`] };
  });
}

/** Ajoute un élément répétable générique (ex. masque lointain d'une baie). */
export function addGenericItem(d: Dossier, parentUid: string, rel: string): string {
  const obj = findObj(d, parentUid);
  const parts = rel.split('/');
  const name = parts.pop()!;
  const def = defAt(d.schema, obj, rel);
  if (!def) throw new EditError('Élément non prévu par le schéma.');
  return d.transact(`Ajouter ${fieldMeta(name, def).label.toLowerCase()}`, () => {
    const container = parts.length ? ensurePath(obj, parts.join('/'), d.schema, d.counter) : obj;
    const el = createElement(container, name);
    let last: Element | null = null;
    for (let s = container.firstElementChild; s; s = s.nextElementSibling) if (s.localName === name) last = s;
    if (last) last.after(el);
    else insertOrdered(container, el, d.schema);
    annotate(el, d.counter, () => def.kind === 'complex');
    createSkeleton(el, d.schema, d.counter);
    return { result: getUid(el) ?? parentUid, affectsResults: [`${objectLabel(obj)} : ajout ${fieldMeta(name, def).label}`] };
  });
}

/** Retire un élément générique (sans référence possible vers lui). */
export function removeGenericItem(d: Dossier, uid: string): void {
  const el = findObj(d, uid);
  if (kindOf(el)) throw new EditError('Utilisez la suppression d\'objet, qui contrôle les dépendances.');
  const owner = ownerObject(el.parentElement);
  d.transact(`Retirer ${fieldMeta(el.localName, null).label.toLowerCase()}`, () => {
    el.remove();
    return { result: undefined, affectsResults: owner ? [`${objectLabel(owner)} : retrait ${fieldMeta(el.localName, null).label}`] : ['Retrait d\'un élément'] };
  });
}

// ---------------------------------------------------------- suppression

export type Resolution = { action: 'detacher' } | { action: 'supprimer' } | { action: 'reaffecter'; targetUid: string };

export interface DeletionDependency {
  /** clé stable : uid du référent + champ */
  key: string;
  fromUid: string;
  fromLabel: string;
  ref: RefDef;
  targetUid: string;
  targetLabel: string;
  resolution: Resolution;
  /** cibles de réaffectation compatibles (hors objets supprimés) */
  alternatives: { uid: string; label: string }[];
}

export interface DeletionPlan {
  rootUid: string;
  /** objets supprimés : la cible, ses objets contenus et les cascades choisies */
  deleted: { uid: string; label: string; reason: string }[];
  dependencies: DeletionDependency[];
  warnings: string[];
}

export function planDeletion(d: Dossier, uid: string, choices: Record<string, Resolution> = {}): DeletionPlan {
  const root = findObj(d, uid);
  if (!kindOf(root)) throw new EditError('Seuls les objets du dossier se suppriment ici.');
  const graph = new RelationGraph(d.working);
  const deleted = new Map<string, { uid: string; label: string; reason: string }>();
  const addSubtree = (el: Element, reason: string) => {
    const u = getUid(el)!;
    if (deleted.has(u)) return;
    deleted.set(u, { uid: u, label: objectLabel(el), reason });
    for (const o of graph.objects) {
      if (o.el !== el && el.contains(o.el) && !deleted.has(o.uid)) {
        deleted.set(o.uid, { uid: o.uid, label: objectLabel(o.el), reason: `contenu dans ${objectLabel(el)}` });
      }
    }
  };
  addSubtree(root, 'objet sélectionné');

  const deps = new Map<string, DeletionDependency>();
  for (let changed = true; changed;) {
    changed = false;
    for (const target of [...deleted.keys()]) {
      for (const link of graph.incoming.get(target) ?? []) {
        if (deleted.has(link.fromUid)) continue;
        const key = `${link.fromUid}:${link.ref.key}`;
        if (deps.has(key)) continue;
        const resolution = choices[key] ?? { action: 'detacher' as const };
        const dep: DeletionDependency = {
          key, fromUid: link.fromUid, fromLabel: objectLabel(link.from), ref: link.ref, targetUid: target,
          targetLabel: deleted.get(target)!.label, resolution, alternatives: [],
        };
        deps.set(key, dep);
        if (resolution.action === 'supprimer') {
          addSubtree(link.from, `référence ${deleted.get(target)!.label} (${link.ref.label.toLowerCase()})`);
          changed = true;
        }
      }
    }
  }
  for (const dep of deps.values()) {
    const from = graph.byUid.get(dep.fromUid)!.el;
    dep.alternatives = graph.candidates(dep.ref, from).filter((c) => !deleted.has(c.uid)).map((c) => ({ uid: c.uid, label: objectLabel(c.el) }));
    if (dep.resolution.action === 'reaffecter' && !dep.alternatives.some((a) => a.uid === (dep.resolution as { targetUid: string }).targetUid)) {
      dep.resolution = { action: 'detacher' };
    }
  }
  // les dépendances internes à l'ensemble supprimé disparaissent avec lui
  const dependencies = [...deps.values()].filter((dep) => !deleted.has(dep.fromUid));

  const warnings: string[] = [];
  const kind = kindOf(root)!;
  const def = d.schema?.def(kind.path);
  const siblings = Array.from(root.parentElement?.children ?? []).filter((c) => c.localName === root.localName && !deleted.has(getUid(c) ?? ''));
  if (def && def.minOccurs > 0 && siblings.length < def.minOccurs) {
    warnings.push(`Le schéma exige au moins ${def.minOccurs} ${kind.label.toLowerCase()} à cet endroit : le dossier sera signalé incomplet.`);
  }
  for (const dep of dependencies.filter((x) => x.ref.mode === 'shared' && x.resolution.action === 'detacher')) {
    warnings.push(`${dep.fromLabel} : la référence de générateur mixte sera retirée.`);
  }
  return { rootUid: uid, deleted: [...deleted.values()], dependencies, warnings };
}

export function executeDeletion(d: Dossier, uid: string, choices: Record<string, Resolution>): void {
  const plan = planDeletion(d, uid, choices);
  d.transact(`Supprimer ${plan.deleted[0].label}`, () => {
    const index = uidIndex(d.working);
    const taken = allReferences(d.working);
    for (const dep of plan.dependencies) {
      const from = index.get(dep.fromUid);
      if (!from) continue;
      if (dep.resolution.action === 'reaffecter') applyReference(d, from, dep.ref, index.get(dep.resolution.targetUid) ?? null, taken);
      else if (dep.resolution.action === 'detacher') {
        applyReference(d, from, dep.ref, null, taken);
      }
    }
    for (const item of plan.deleted) index.get(item.uid)?.remove();
    return { result: undefined, affectsResults: plan.deleted.map((x) => `Suppression : ${x.label}`) };
  });
}

// ----------------------------------------------------------- duplication

export interface DuplicationOptions {
  /** pour une paroi : dupliquer aussi les baies et portes qui y sont rattachées */
  withOpenings?: boolean;
  /** conserver la référence de générateur mixte sur la copie (lien partagé) */
  keepMixte?: boolean;
}

export interface DuplicationPlan {
  copies: { uid: string; label: string }[];
  remapped: string[];
  shared: string[];
  notCopied: string[];
  removed: string[];
  openings: { uid: string; label: string }[];
}

export function planDuplication(d: Dossier, uid: string, options: DuplicationOptions = {}): DuplicationPlan {
  const root = findObj(d, uid);
  const kind = kindOf(root);
  if (!kind) throw new EditError('Seuls les objets du dossier se dupliquent.');
  const graph = new RelationGraph(d.working);
  const rootEntry = graph.byUid.get(uid)!;
  const openings = (graph.incoming.get(uid) ?? [])
    .filter((l) => l.ref.key === 'paroi')
    .map((l) => ({ uid: l.fromUid, label: objectLabel(l.from) }));
  const sources = [root, ...(options.withOpenings ? openings.map((o) => graph.byUid.get(o.uid)!.el) : [])];
  const copied = graph.objects.filter((o) => sources.some((s) => s.contains(o.el)));
  const copiedRefs = new Set(copied.map((o) => o.reference).filter(Boolean) as string[]);
  const plan: DuplicationPlan = {
    copies: copied.map((o) => ({ uid: o.uid, label: objectLabel(o.el) })),
    remapped: [], shared: [], notCopied: [], removed: [], openings,
  };
  for (const o of copied) {
    for (const link of graph.outgoing.get(o.uid) ?? []) {
      if (link.ref.mode === 'shared') {
        (options.keepMixte ? plan.shared : plan.removed).push(
          `${objectLabel(o.el)} : ${link.ref.label}` + (options.keepMixte ? ' — la copie partagera la même référence mixte' : ' — retirée sur la copie'),
        );
      } else if (copiedRefs.has(link.value)) {
        plan.remapped.push(`${objectLabel(o.el)} : ${link.ref.label} → pointe vers la copie`);
      } else if (link.status === 'ok') {
        plan.shared.push(`${objectLabel(o.el)} : ${link.ref.label} → ${link.targets.map(objectLabel).join(', ')} (lien conservé, cible partagée)`);
      } else {
        plan.shared.push(`${objectLabel(o.el)} : ${link.ref.label} « ${link.value} » (valeur recopiée telle quelle)`);
      }
    }
  }
  for (const link of graph.incoming.get(rootEntry.uid) ?? []) {
    if (copied.some((c) => c.uid === link.fromUid)) continue;
    if (link.ref.mode === 'shared') continue;
    plan.notCopied.push(`${objectLabel(link.from)} (${link.ref.label.toLowerCase()}) reste lié à l'original`);
  }
  return plan;
}

export function executeDuplication(d: Dossier, uid: string, options: DuplicationOptions = {}): string {
  const plan = planDuplication(d, uid, options);
  const root = findObj(d, uid);
  return d.transact(`Dupliquer ${objectLabel(root)}`, () => {
    const index = uidIndex(d.working);
    const taken = allReferences(d.working);
    const refMap = new Map<string, string>();
    const clones: Element[] = [];
    const sources = [root, ...(options.withOpenings ? plan.openings.map((o) => index.get(o.uid)!) : [])];
    for (const source of sources) {
      const clone = source.cloneNode(true) as Element;
      for (const el of allElements(clone)) {
        if (getUid(el)) setUid(el, d.counter.next());
      }
      source.after(clone);
      clones.push(clone);
    }
    // nouvelles références uniques pour chaque objet copié
    for (const clone of clones) {
      for (const el of allElements(clone)) {
        const k = kindOf(el);
        if (!k?.idField) continue;
        const leaf = resolvePath(el, k.idField);
        const old = textOf(el, k.idField);
        if (!leaf || !old) continue;
        const fresh = uniqueValue(`${old}_copie`, taken);
        refMap.set(old, fresh);
        setLeafText(leaf, fresh);
        if (k.nameField) {
          const name = textOf(el, k.nameField);
          if (name && el === clone) setLeafText(resolvePath(el, k.nameField)!, `${name} (copie)`);
        }
      }
    }
    // remappage des liens internes, traitement du générateur mixte
    for (const clone of clones) {
      for (const el of allElements(clone)) {
        const k = kindOf(el);
        if (!k) continue;
        for (const r of REFS.filter((x) => x.fromKinds.includes(k.key))) {
          const leaf = resolvePath(el, r.field);
          const v = textOf(el, r.field);
          if (!leaf || !v) continue;
          if (r.mode === 'shared') {
            if (!options.keepMixte) leaf.remove();
          } else if (refMap.has(v)) setLeafText(leaf, refMap.get(v)!);
        }
      }
    }
    return { result: getUid(clones[0])!, affectsResults: [`Duplication : ${objectLabel(root)}`] };
  });
}

/** Valeur d'un champ dans l'état actuel, pour l'interface. */
export function readField(d: Dossier, uid: string, rel: string): string | null {
  return textOf(findObj(d, uid), rel);
}

export function kindDefOf(d: Dossier, uid: string): KindDef | undefined {
  return kindOf(findObj(d, uid));
}

export { isResultPath, relativePath, firstChild };

/** Modifie (ou retire si `value` est null) un attribut déclaré par le schéma. */
export function setAttribute(d: Dossier, uid: string, name: string, value: string | null): void {
  const el = findObj(d, uid);
  const def = d.schema?.defFor(el);
  if (def && !def.attributes.some((a) => a.name === name)) throw new EditError(`Attribut « ${name} » non prévu par le schéma.`);
  d.transact(`${value === null ? 'Retirer' : 'Modifier'} l'attribut ${name}`, () => {
    if (value === null) el.removeAttribute(name);
    else el.setAttribute(name, value);
    return { result: undefined, affectsResults: [] };
  });
}
