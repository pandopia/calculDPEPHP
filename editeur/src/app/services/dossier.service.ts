import { computed, Injectable, signal } from '@angular/core';
import { Dossier } from '../core/state/dossier';
import { addDefaultObjects, buildSkeleton, NewDossierOptions } from '../core/state/skeleton';
import { SchemaRegistry } from '../core/schema/schema-registry';
import { buildAbsentFiche, buildFiche, buildModel, Model } from '../core/metier/model';
import { computeChanges } from '../core/edition/change-set';
import * as ed from '../core/edition/editor';
import { decodeXmlBytes } from '../core/xml/safe-xml';
import { BrowserXsdEngine, runXsdValidation, XsdReport } from '../core/validation/xsd-validation';
import { MoteurCalcul, MoteurPandopia, RapportPdfPandopia, ServiceLogementsPandopia } from '../core/calcul/moteur-calcul';
import { ConfigImmeuble, contexteImmeuble, controler, empreinte, lireReponse, parois, requete, resultats } from '../core/immeuble/logements';
import { integrerResultats, xmlPourRapport } from '../core/calcul/integrer-resultats';
import { DraftStore } from './draft-store';

const LAST_KEY = 'calculdpe-editeur:dernier-dossier';
const CONSENT_KEY = 'calculdpe-editeur:envoi-moteur-accepte';

function readConsent(): boolean {
  try {
    return localStorage.getItem(CONSENT_KEY) === '1';
  } catch {
    return false;
  }
}

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
  readonly moteur: MoteurCalcul = new MoteurPandopia();
  readonly calculEnCours = signal(false);
  /** l'utilisateur a accepté l'envoi du XML au moteur (mémorisé sur ce poste) */
  readonly consentementCalcul = signal(readConsent());
  /** action en attente de l'accord d'envoi */
  readonly demandeConsentement = signal<'calcul' | 'pdf' | 'logements' | null>(null);
  readonly serviceLogements = new ServiceLogementsPandopia();
  readonly logementsEnCours = signal(false);
  /** référence du logement dont le PDF est en cours */
  readonly pdfLogementEnCours = signal<string | null>(null);
  /** XML calculés des logements, gardés en mémoire seulement (volumineux) */
  readonly xmlLogements = signal(new Map<string, string>());
  private pdfLogementEnAttente: string | null = null;
  readonly rapport = new RapportPdfPandopia();
  readonly pdfEnCours = signal(false);
  /** confirmation avant PDF : résultats absents ou pas à jour, numéro ADEME d'un dossier modifié */
  readonly pdfConfirmation = signal<{ sansResultats: boolean; obsoletes: boolean } | null>(null);
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
    this.xmlLogements.set(new Map());
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

  /** Lance le calcul ; demande d'abord l'accord d'envoi si besoin. */
  async calculer(): Promise<boolean> {
    const d = this.dossier();
    if (!d || this.calculEnCours()) return false;
    if (!this.consentementCalcul()) {
      this.demandeConsentement.set('calcul');
      return false;
    }
    this.calculEnCours.set(true);
    try {
      const envoye = d.exportXml();
      const reponse = await this.moteur.calculer(envoye);
      const r = integrerResultats(d, envoye, reponse, this.moteur.nom);
      this.changed();
      const etiq = r.avant.energie !== r.apres.energie || r.avant.climat !== r.apres.climat
        ? ` Étiquettes : énergie ${r.avant.energie ?? '—'} → ${r.apres.energie ?? '—'}, climat ${r.avant.climat ?? '—'} → ${r.apres.climat ?? '—'}.`
        : ` Étiquettes inchangées (${r.apres.energie ?? '—'} / ${r.apres.climat ?? '—'}).`;
      this.toast('succes', `Calcul terminé.${etiq}`);
      return true;
    } catch (e) {
      this.toast('erreur', e instanceof Error ? e.message : String(e));
      return false;
    } finally {
      this.calculEnCours.set(false);
    }
  }

  /** Rapport PDF du diagnostic ; confirme d'abord si les résultats ne sont pas à jour. */
  rapportPdf(): void {
    const d = this.dossier();
    if (!d || this.pdfEnCours()) return;
    if (!this.consentementCalcul()) {
      this.demandeConsentement.set('pdf');
      return;
    }
    const sansResultats = !d.working.getElementsByTagName('sortie').length;
    if (sansResultats || d.meta.resultatsObsoletes) {
      this.pdfConfirmation.set({ sansResultats, obsoletes: d.meta.resultatsObsoletes });
      return;
    }
    void this.genererPdf(false);
  }

  async genererPdf(calculerAvant: boolean): Promise<void> {
    this.pdfConfirmation.set(null);
    const d = this.dossier();
    if (!d) return;
    if (calculerAvant && !(await this.calculer())) return;
    this.pdfEnCours.set(true);
    try {
      // dossier modifié depuis l'import : rapport sans numéro ADEME (« non attribué », DOCUMENT NON OFFICIEL)
      const { xml, numeroRetire } = xmlPourRapport(d, this.changes().length > 0);
      const blob = await this.rapport.generer(xml);
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${this.baseName()}-diagnostic.pdf`;
      a.click();
      setTimeout(() => URL.revokeObjectURL(url), 5000);
      this.toast('succes', numeroRetire
        ? `Rapport PDF téléchargé, sans le numéro ADEME ${numeroRetire} : le dossier a été modifié depuis l'import (mention « document non officiel »).`
        : 'Rapport PDF téléchargé.');
    } catch (e) {
      this.toast('erreur', e instanceof Error ? e.message : String(e));
    } finally {
      this.pdfEnCours.set(false);
    }
  }

  // ------------------------------------------------------------ DPE logements (§17.2.2)

  modifierImmeuble<T>(label: string, fn: (cfg: ConfigImmeuble) => T, success?: string): T | undefined {
    return this.run((d) => d.modifierImmeuble(label, fn), success);
  }

  /** Calcule l'immeuble et génère le DPE de chacun de ses logements. */
  async calculerLogements(): Promise<boolean> {
    const d = this.dossier();
    if (!d || this.logementsEnCours()) return false;
    const cfg = d.immeuble();
    const ctx = contexteImmeuble(d.working);
    const bloquants = controler(cfg, ctx, parois(d.working, d.schema)).filter((c) => c.gravite === 'erreur');
    if (bloquants.length) {
      this.toast('erreur', `Calcul des logements impossible : ${bloquants[0].message}${bloquants.length > 1 ? ` (et ${bloquants.length - 1} autre(s) point(s))` : ''}`);
      return false;
    }
    if (!this.consentementCalcul()) {
      this.pdfLogementEnAttente = null;
      this.demandeConsentement.set('logements');
      return false;
    }
    this.logementsEnCours.set(true);
    try {
      const envoye = d.exportXml();
      const rep = lireReponse(await this.serviceLogements.calculer(requete(cfg, ctx, envoye)));
      const notes: string[] = [];
      if (rep.batiment.xml) {
        try {
          integrerResultats(d, envoye, rep.batiment.xml, this.moteur.nom);
        } catch (e) {
          notes.push('Résultats de l\'immeuble non intégrés : ' + (e instanceof Error ? e.message : String(e)));
        }
      }
      const r = resultats(rep, empreinte(envoye, cfg), this.moteur.nom);
      r.hypotheses.push(...notes);
      d.setResultatsLogements(r);
      this.xmlLogements.set(new Map(rep.logements.filter((l) => l.xml).map((l) => [l.reference, l.xml!])));
      this.changed();
      const ko = r.logements.filter((l) => l.erreur).length;
      this.toast(ko ? 'info' : 'succes', `DPE logements calculés : ${r.logements.length - ko} réussi(s)${ko ? `, ${ko} en erreur` : ''}.`);
      return true;
    } catch (e) {
      this.toast('erreur', e instanceof Error ? e.message : String(e));
      return false;
    } finally {
      this.logementsEnCours.set(false);
    }
  }

  /** Rapport PDF du DPE d'un logement, produit par le service à partir de l'immeuble. */
  async pdfLogement(reference: string): Promise<void> {
    const d = this.dossier();
    if (!d || this.pdfLogementEnCours()) return;
    if (!this.consentementCalcul()) {
      this.pdfLogementEnAttente = reference;
      this.demandeConsentement.set('logements');
      return;
    }
    this.pdfLogementEnCours.set(reference);
    try {
      const blob = await this.serviceLogements.pdf(requete(d.immeuble(), contexteImmeuble(d.working), d.exportXml(), reference));
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${this.baseName()}-${reference.replace(/[^\w.-]+/g, '_')}.pdf`;
      a.click();
      setTimeout(() => URL.revokeObjectURL(url), 5000);
      this.toast('succes', `Rapport PDF du logement ${reference} téléchargé.`);
    } catch (e) {
      this.toast('erreur', e instanceof Error ? e.message : String(e));
    } finally {
      this.pdfLogementEnCours.set(null);
    }
  }

  xmlLogement(reference: string): void {
    const xml = this.xmlLogements().get(reference);
    if (xml) this.download(xml, `${this.baseName()}-${reference.replace(/[^\w.-]+/g, '_')}.xml`, 'application/xml');
  }

  accepterEnvoi(memoriser: boolean): void {
    const action = this.demandeConsentement();
    this.demandeConsentement.set(null);
    this.consentementCalcul.set(true);
    if (memoriser) {
      try {
        localStorage.setItem(CONSENT_KEY, '1');
      } catch {
        /* commodité seulement */
      }
    }
    if (action === 'pdf') this.rapportPdf();
    else if (action === 'logements') {
      const ref = this.pdfLogementEnAttente;
      this.pdfLogementEnAttente = null;
      void (ref ? this.pdfLogement(ref) : this.calculerLogements());
    } else void this.calculer();
  }

  /** « résultats du fichier source » ou « calculés par … le … » */
  origineResultats(): string {
    const c = this.dossier()?.meta.calcul;
    if (!c) return 'résultats du fichier source';
    return `calculés par ${c.moteur} le ${new Date(c.date).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })}`;
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
