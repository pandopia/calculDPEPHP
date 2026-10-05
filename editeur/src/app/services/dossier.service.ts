import { computed, Injectable, signal } from '@angular/core';
import { Dossier } from '../core/state/dossier';
import { addDefaultObjects, buildSkeleton, NewDossierOptions } from '../core/state/skeleton';
import { SchemaRegistry } from '../core/schema/schema-registry';
import { buildAbsentFiche, buildFiche, buildModel, Model } from '../core/metier/model';
import { computeChanges } from '../core/edition/change-set';
import * as ed from '../core/edition/editor';
import { decodeXmlBytes } from '../core/xml/safe-xml';
import { BrowserXsdEngine, runXsdValidation, XsdReport } from '../core/validation/xsd-validation';
import { AUCUN_MOTEUR, MoteurCalcul } from '../core/calcul/moteur-calcul';
import { DraftStore } from './draft-store';

const LAST_KEY = 'calculdpe-editeur:dernier-dossier';

export type SaveStatus = 'aucun' | 'en_attente' | 'enregistrement' | 'enregistre' | 'erreur';

export interface Toast {
  id: number;
  kind: 'info' | 'erreur' | 'succes';
  text: string;
}

/** État applicatif du dossier ouvert ; toutes les modifications passent ici. */
@Injectable({ providedIn: 'root' })
export class DossierService {
  readonly schemas = new SchemaRegistry(async (file) => {
    const res = await fetch(`schemas/${file}`);
    if (!res.ok) throw new Error(`Schéma ${file} introuvable.`);
    return res.text();
  });
  readonly moteur: MoteurCalcul = AUCUN_MOTEUR;
  private readonly xsdEngine = new BrowserXsdEngine();

  readonly dossier = signal<Dossier | null>(null);
  readonly revision = signal(0);
  readonly saveStatus = signal<SaveStatus>('aucun');
  readonly savedAt = signal<string | null>(null);
  readonly advanced = signal(false);
  readonly toasts = signal<Toast[]>([]);
  readonly xsdReport = signal<XsdReport | null>(null);
  readonly xsdRunning = signal(false);

  readonly model = computed<Model | null>(() => {
    this.revision();
    const d = this.dossier();
    return d ? buildModel(d) : null;
  });
  readonly changes = computed(() => {
    this.revision();
    const d = this.dossier();
    return d ? computeChanges(d) : [];
  });

  private saveTimer: ReturnType<typeof setTimeout> | null = null;
  private toastSeq = 0;

  constructor(private readonly drafts: DraftStore) {}

  // ------------------------------------------------------------ ouverture

  async importFile(file: File): Promise<void> {
    const bytes = new Uint8Array(await file.arrayBuffer());
    const text = decodeXmlBytes(bytes);
    if (/\.json$/i.test(file.name) || text.trimStart().startsWith('{')) {
      await this.openDraftData(JSON.parse(text));
      return;
    }
    const d = await Dossier.fromImport(text, file.name, this.schemas);
    if (d.format.niveauSupport === 'aucun') {
      throw new Error(`Fichier non pris en charge — ${d.format.libelleFamille}. ${d.format.messages.join(' ')}`);
    }
    this.setDossier(d);
    this.scheduleSave(0);
  }

  async create(options: NewDossierOptions, nom: string): Promise<void> {
    const schema = await this.schemas.forVersion(options.version);
    if (!schema) throw new Error('Version de modèle non disponible.');
    // objets présents dans tout logement, intégrés à l'état initial (pas des « modifications »)
    const draft = await Dossier.fromSkeleton(buildSkeleton(schema, options), nom, this.schemas);
    addDefaultObjects(draft);
    const d = await Dossier.fromSkeleton(draft.working, nom, this.schemas);
    this.setDossier(d);
    this.scheduleSave(0);
  }

  async openDraft(id: string): Promise<void> {
    const draft = await this.drafts.get(id);
    if (!draft) throw new Error('Brouillon introuvable.');
    await this.openDraftData(draft);
  }

  private async openDraftData(draft: Parameters<typeof Dossier.fromDraft>[0]): Promise<void> {
    const d = await Dossier.fromDraft(draft, this.schemas);
    this.setDossier(d);
    this.saveStatus.set('enregistre');
    this.savedAt.set(d.meta.modifieLe);
  }

  /** Identifiant du dernier dossier ouvert (reprise automatique après rechargement). */
  lastId(): string | null {
    try {
      return localStorage.getItem(LAST_KEY);
    } catch {
      return null;
    }
  }

  close(): void {
    try {
      localStorage.removeItem(LAST_KEY);
    } catch {
      /* ignoré */
    }
    this.flushSave();
    this.dossier.set(null);
    this.xsdReport.set(null);
    this.saveStatus.set('aucun');
  }

  private setDossier(d: Dossier): void {
    try {
      localStorage.setItem(LAST_KEY, d.meta.id);
    } catch {
      /* stockage indisponible : simple commodité */
    }
    this.dossier.set(d);
    this.xsdReport.set(null);
    this.revision.update((r) => r + 1);
  }

  // ------------------------------------------------------------ édition

  /** Exécute une opération ; une erreur métier devient un message, jamais un état cassé. */
  run<T>(fn: (d: Dossier) => T, success?: string): T | undefined {
    const d = this.dossier();
    if (!d) return undefined;
    try {
      const r = fn(d);
      this.changed();
      if (success) this.toast('succes', success);
      return r;
    } catch (e) {
      this.toast('erreur', e instanceof Error ? e.message : String(e));
      return undefined;
    }
  }

  setValue(uid: string, rel: string, input: string): boolean {
    const r = this.run((d) => ed.setFieldValue(d, uid, rel, input));
    if (r?.warnings.length) this.toast('info', `Valeur enregistrée, à vérifier : ${r.warnings.join(' ')}`);
    return r !== undefined;
  }

  undo(): void {
    this.run((d) => d.undo());
  }

  redo(): void {
    this.run((d) => d.redo());
  }

  rename(nom: string): void {
    this.run((d) => d.rename(nom));
  }

  absentFiche(path: string, label: string) {
    const d = this.dossier();
    const m = this.model();
    return d && m ? buildAbsentFiche(d, m, path, label) : null;
  }

  fiche(uid: string) {
    const d = this.dossier();
    const m = this.model();
    return d && m ? buildFiche(d, m, uid) : null;
  }

  private changed(): void {
    this.revision.update((r) => r + 1);
    this.scheduleSave();
  }

  // ------------------------------------------------------------ sauvegarde

  private scheduleSave(delay = 600): void {
    this.saveStatus.set('en_attente');
    if (this.saveTimer) clearTimeout(this.saveTimer);
    this.saveTimer = setTimeout(() => void this.save(), delay);
  }

  flushSave(): void {
    if (this.saveTimer) {
      clearTimeout(this.saveTimer);
      this.saveTimer = null;
      void this.save();
    }
  }

  async save(): Promise<void> {
    const d = this.dossier();
    if (!d) return;
    this.saveTimer = null;
    this.saveStatus.set('enregistrement');
    try {
      await this.drafts.put(d.toDraft());
      this.saveStatus.set('enregistre');
      this.savedAt.set(new Date().toISOString());
    } catch (e) {
      this.saveStatus.set('erreur');
      this.toast('erreur', 'Enregistrement local du brouillon impossible : ' + (e instanceof Error ? e.message : String(e)));
    }
  }

  // ------------------------------------------------------------ contrôles et export

  async validateXsd(): Promise<void> {
    const d = this.dossier();
    if (!d) return;
    this.xsdRunning.set(true);
    try {
      this.xsdReport.set(await runXsdValidation(d, this.xsdEngine));
    } catch (e) {
      this.toast('erreur', 'Validation XSD impossible : ' + (e instanceof Error ? e.message : String(e)));
    } finally {
      this.xsdRunning.set(false);
    }
  }

  exportXml(): string {
    return this.dossier()!.exportXml();
  }

  download(content: string, fileName: string, type: string): void {
    const blob = new Blob([content], { type });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  baseName(): string {
    const d = this.dossier();
    return (d?.meta.nom || 'dossier').replace(/[^\w.-]+/g, '_');
  }

  toast(kind: Toast['kind'], text: string): void {
    const id = ++this.toastSeq;
    this.toasts.update((t) => [...t, { id, kind, text }]);
    setTimeout(() => this.toasts.update((t) => t.filter((x) => x.id !== id)), kind === 'erreur' ? 9000 : 5000);
  }

  dismissToast(id: number): void {
    this.toasts.update((t) => t.filter((x) => x.id !== id));
  }
}
