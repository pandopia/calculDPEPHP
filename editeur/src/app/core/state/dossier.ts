import { detectFormat, FormatInfo } from '../format/format-detector';
import { SchemaHandle, SchemaRegistry } from '../schema/schema-registry';
import { parseXml } from '../xml/safe-xml';
import { serialize } from '../xml/serializer';
import { prunableElements } from '../xml/prune';
import { annotate, maxUid, uidIndex, UidCounter } from '../xml/uid';
import { ConfigImmeuble, configVide, ResultatsLogements } from '../immeuble/logements';

/**
 * Dossier de travail.
 *
 * - `original` : texte exact du fichier importé, conservé intact et
 *   téléchargeable ;
 * - `baseline` : document importé annoté d'identifiants internes, figé :
 *   référence du « ce qui a changé depuis l'import » ;
 * - `working` : document de travail, seul modifié, par opérations ciblées.
 *
 * Aucune donnée ne quitte le navigateur : un brouillon est une sérialisation
 * locale (IndexedDB) de ces trois éléments.
 */
export interface DossierMeta {
  id: string;
  nom: string;
  origine: 'import' | 'nouveau';
  nomFichier: string | null;
  creeLe: string;
  modifieLe: string;
  resultatsObsoletes: boolean;
  motifsObsolescence: string[];
  /** dernier calcul intégré (absent : résultats du fichier source) */
  calcul?: { moteur: string; date: string };
  /** DPE immeuble : logements et liaisons aux parois (§17.2.2), hors XML ADEME */
  immeuble?: ConfigImmeuble;
}

export interface DraftData {
  format: 'calculdpe-editeur-brouillon';
  version: 1;
  meta: DossierMeta;
  original: string | null;
  baseline: string;
  working: string;
  counter: number;
  /** derniers DPE logements calculés (hors historique d'annulation) */
  resultatsLogements?: ResultatsLogements | null;
}

interface Snapshot {
  label: string;
  working: string;
  meta: DossierMeta;
  counter: number;
}

const HISTORY_MAX = 100;

export class Dossier {
  /** incrémenté à chaque modification : déclenche les recalculs de vue */
  revision = 0;
  private undoStack: Snapshot[] = [];
  private workingIndex: { revision: number; index: Map<string, Element> } | null = null;
  private baselineIndex: Map<string, Element> | null = null;
  private prunableCache: { revision: number; doc: Document; set: Set<Element> } | null = null;
  private redoStack: Snapshot[] = [];
  /** derniers DPE logements calculés : un résultat, pas une saisie, donc hors annulation */
  resultatsLogements: ResultatsLogements | null = null;

  private constructor(
    public meta: DossierMeta,
    public format: FormatInfo,
    public schema: SchemaHandle | null,
    public readonly original: string | null,
    public readonly baseline: XMLDocument,
    public working: XMLDocument,
    public readonly counter: UidCounter,
  ) {}

  static async fromImport(text: string, fileName: string, schemas: SchemaRegistry): Promise<Dossier> {
    const baseline = parseXml(text);
    const { info, schema } = await detectFormat(baseline, schemas);
    const counter = new UidCounter();
    // identifiant aussi sur les blocs complexes vides (ex. <baie_vitree_double_fenetre/>)
    annotate(baseline.documentElement, counter, (el) => schema?.defFor(el)?.kind === 'complex');
    const working = parseXml(serialize(baseline, { keepUids: true }).text);
    const now = new Date().toISOString();
    const meta: DossierMeta = {
      id: newId(),
      nom: info.numeroDpe ?? fileName.replace(/\.xml$/i, ''),
      origine: 'import',
      nomFichier: fileName,
      creeLe: now,
      modifieLe: now,
      resultatsObsoletes: false,
      motifsObsolescence: [],
    };
    return new Dossier(meta, info, schema, text, baseline, working, counter);
  }

  /** Dossier vierge : document produit à partir du schéma (voir skeleton.ts). */
  static async fromSkeleton(doc: XMLDocument, nom: string, schemas: SchemaRegistry): Promise<Dossier> {
    const counter = new UidCounter(maxUid(doc));
    annotate(doc.documentElement, counter);
    const baseline = parseXml(serialize(doc, { keepUids: true }).text);
    const working = parseXml(serialize(doc, { keepUids: true }).text);
    const { info, schema } = await detectFormat(working, schemas);
    const now = new Date().toISOString();
    const meta: DossierMeta = {
      id: newId(), nom, origine: 'nouveau', nomFichier: null, creeLe: now, modifieLe: now,
      resultatsObsoletes: false, motifsObsolescence: [],
    };
    return new Dossier(meta, info, schema, null, baseline, working, counter);
  }

  static async fromDraft(draft: DraftData, schemas: SchemaRegistry): Promise<Dossier> {
    if (draft.format !== 'calculdpe-editeur-brouillon') throw new Error('Fichier de brouillon non reconnu.');
    const baseline = parseXml(draft.baseline);
    const working = parseXml(draft.working);
    const counter = new UidCounter(Math.max(draft.counter, maxUid(working), maxUid(baseline)));
    const { info, schema } = await detectFormat(working, schemas);
    const d = new Dossier(draft.meta, info, schema, draft.original, baseline, working, counter);
    d.resultatsLogements = draft.resultatsLogements ?? null;
    return d;
  }

  toDraft(): DraftData {
    return {
      format: 'calculdpe-editeur-brouillon',
      version: 1,
      meta: this.meta,
      original: this.original,
      baseline: serialize(this.baseline, { keepUids: true }).text,
      working: serialize(this.working, { keepUids: true }).text,
      counter: this.counter.value,
      ...(this.resultatsLogements ? { resultatsLogements: this.resultatsLogements } : {}),
    };
  }

  /** Logements du DPE immeuble (configuration vide si absente). */
  immeuble(): ConfigImmeuble {
    return this.meta.immeuble ?? configVide();
  }

  /** Modifie les logements ou leurs liaisons, avec point d'annulation (n'affecte pas le calcul de l'immeuble). */
  modifierImmeuble<T>(label: string, fn: (cfg: ConfigImmeuble) => T): T {
    return this.transact(label, () => {
      const cfg = structuredClone(this.immeuble());
      const result = fn(cfg);
      this.meta.immeuble = cfg;
      return { result, affectsResults: [] };
    });
  }

  setResultatsLogements(r: ResultatsLogements | null): void {
    this.resultatsLogements = r;
    this.touch();
  }

  /**
   * Exécute une modification avec point d'annulation. `fn` retourne la liste
   * des champs modifiés qui peuvent influencer le calcul (motifs
   * d'obsolescence des résultats).
   */
  transact<T>(label: string, fn: () => { result: T; affectsResults: string[] }): T {
    const snapshot: Snapshot = {
      label,
      working: serialize(this.working, { keepUids: true }).text,
      meta: structuredClone(this.meta),
      counter: this.counter.value,
    };
    let out: { result: T; affectsResults: string[] };
    try {
      out = fn();
    } catch (e) {
      this.restore(snapshot);
      throw e;
    }
    this.undoStack.push(snapshot);
    if (this.undoStack.length > HISTORY_MAX) this.undoStack.shift();
    this.redoStack = [];
    if (out.affectsResults.length) {
      this.meta.resultatsObsoletes = true;
      for (const m of out.affectsResults) {
        if (!this.meta.motifsObsolescence.includes(m)) this.meta.motifsObsolescence.push(m);
      }
      this.meta.motifsObsolescence = this.meta.motifsObsolescence.slice(-50);
    }
    this.touch();
    return out.result;
  }

  /** Index uid → élément du document de travail, mis en cache par révision. */
  index(): Map<string, Element> {
    if (!this.workingIndex || this.workingIndex.revision !== this.revision) {
      this.workingIndex = { revision: this.revision, index: uidIndex(this.working) };
    }
    return this.workingIndex.index;
  }

  /** Éléments vides de remplissage (ignorés, retirés à l'export), par révision. */
  prunable(): Set<Element> {
    if (!this.prunableCache || this.prunableCache.revision !== this.revision || this.prunableCache.doc !== this.working) {
      this.prunableCache = { revision: this.revision, doc: this.working, set: prunableElements(this.working, this.schema) };
    }
    return this.prunableCache.set;
  }

  /** XML de travail exporté : identifiants internes et balises vides de remplissage retirés. */
  exportXml(): string {
    const set = this.prunable();
    return serialize(this.working, { omit: (el) => set.has(el) }).text;
  }

  baseIndex(): Map<string, Element> {
    return (this.baselineIndex ??= uidIndex(this.baseline));
  }

  canUndo(): boolean {
    return this.undoStack.length > 0;
  }

  canRedo(): boolean {
    return this.redoStack.length > 0;
  }

  undoLabel(): string | null {
    return this.undoStack.at(-1)?.label ?? null;
  }

  redoLabel(): string | null {
    return this.redoStack.at(-1)?.label ?? null;
  }

  undo(): void {
    const snap = this.undoStack.pop();
    if (!snap) return;
    this.redoStack.push(this.current(snap.label));
    this.restore(snap);
    this.touch();
  }

  redo(): void {
    const snap = this.redoStack.pop();
    if (!snap) return;
    this.undoStack.push(this.current(snap.label));
    this.restore(snap);
    this.touch();
  }

  rename(nom: string): void {
    this.meta.nom = nom;
    this.touch();
  }

  private current(label: string): Snapshot {
    return { label, working: serialize(this.working, { keepUids: true }).text, meta: structuredClone(this.meta), counter: this.counter.value };
  }

  private restore(snap: Snapshot): void {
    this.working = parseXml(snap.working);
    this.meta = snap.meta;
    this.counter.value = snap.counter;
  }

  private touch(): void {
    this.meta.modifieLe = new Date().toISOString();
    this.revision++;
  }
}

function newId(): string {
  return typeof crypto !== 'undefined' && 'randomUUID' in crypto
    ? crypto.randomUUID()
    : 'd' + Date.now().toString(36) + Math.random().toString(36).slice(2);
}
