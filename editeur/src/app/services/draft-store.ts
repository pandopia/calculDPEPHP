import { Injectable } from '@angular/core';
import { DraftData } from '../core/state/dossier';

/**
 * Brouillons conservés dans le navigateur (IndexedDB), jamais envoyés à un
 * serveur. Ils permettent la reprise après fermeture de l'onglet sur le même
 * poste et le même navigateur ; pour changer de poste, le brouillon se
 * télécharge en fichier.
 */
const DB = 'calculdpe-editeur';
const STORE = 'brouillons';

export interface DraftSummary {
  id: string;
  nom: string;
  origine: string;
  nomFichier: string | null;
  modifieLe: string;
  resultatsObsoletes: boolean;
}

@Injectable({ providedIn: 'root' })
export class DraftStore {
  private db: Promise<IDBDatabase> | null = null;

  private open(): Promise<IDBDatabase> {
    this.db ??= new Promise((resolve, reject) => {
      if (typeof indexedDB === 'undefined') {
        reject(new Error('Stockage local indisponible dans ce navigateur.'));
        return;
      }
      const req = indexedDB.open(DB, 1);
      req.onupgradeneeded = () => req.result.createObjectStore(STORE, { keyPath: 'meta.id' });
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error ?? new Error('Ouverture du stockage local impossible.'));
    });
    return this.db;
  }

  private async tx<T>(mode: IDBTransactionMode, fn: (s: IDBObjectStore) => IDBRequest<T>): Promise<T> {
    const db = await this.open();
    return new Promise((resolve, reject) => {
      const req = fn(db.transaction(STORE, mode).objectStore(STORE));
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error ?? new Error('Opération de stockage local impossible.'));
    });
  }

  async list(): Promise<DraftSummary[]> {
    const all = await this.tx<DraftData[]>('readonly', (s) => s.getAll() as IDBRequest<DraftData[]>);
    return all
      .map((d) => ({
        id: d.meta.id, nom: d.meta.nom, origine: d.meta.origine, nomFichier: d.meta.nomFichier,
        modifieLe: d.meta.modifieLe, resultatsObsoletes: d.meta.resultatsObsoletes,
      }))
      .sort((a, b) => b.modifieLe.localeCompare(a.modifieLe));
  }

  get(id: string): Promise<DraftData | undefined> {
    return this.tx<DraftData | undefined>('readonly', (s) => s.get(id) as IDBRequest<DraftData | undefined>);
  }

  async put(draft: DraftData): Promise<void> {
    await this.tx('readwrite', (s) => s.put(draft));
  }

  async delete(id: string): Promise<void> {
    await this.tx('readwrite', (s) => s.delete(id));
  }
}
