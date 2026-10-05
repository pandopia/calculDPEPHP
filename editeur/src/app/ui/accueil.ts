import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DossierService } from '../services/dossier.service';
import { DraftStore, DraftSummary } from '../services/draft-store';
import { NavService } from '../services/nav.service';
import { VERSIONS_CREATION } from '../core/schema/schema-registry';

interface TypeGroup {
  label: string;
  options: { code: string; label: string }[];
}

@Component({
  selector: 'app-accueil',
  imports: [FormsModule],
  template: `
    <main class="accueil">
      <header class="accueil-header">
        <h1>Éditeur de DPE</h1>
        <p class="lead">Lire, comprendre, compléter et modifier un diagnostic de performance énergétique à partir de son XML ADEME, puis réexporter le XML.</p>
        <p class="muted small">Tout se passe dans votre navigateur : aucun fichier n'est envoyé à un serveur. Les brouillons sont conservés localement sur ce poste.</p>
      </header>

      <div class="accueil-grid">
        <section class="card entry">
          <h2>Importer un XML DPE</h2>
          <div class="dropzone" [class.over]="over()" (dragover)="$event.preventDefault(); over.set(true)" (dragleave)="over.set(false)"
               (drop)="onDrop($event)" (click)="fileInput.click()" role="button" tabindex="0" (keydown.enter)="fileInput.click()">
            <strong>Déposez un fichier ici</strong>
            <span class="muted">ou cliquez pour choisir un fichier .xml (ou un brouillon .json)</span>
          </div>
          <input #fileInput type="file" accept=".xml,.json,application/xml,text/xml,application/json" hidden (change)="onFile($event)" />
          @if (busy()) { <p class="muted">Lecture du fichier…</p> }
          @if (error()) { <p class="error-box">{{ error() }}</p> }
          <p class="muted small">Formats reconnus : DPE logement existant (3CL-2021), modèle 1 à 2.6 (XSD officiels de l'observatoire ADEME). Les DPE neufs et tertiaires s'ouvrent en vue générique. Taille maximale : 20 Mo.</p>
        </section>

        <section class="card entry">
          <h2>Créer un nouveau dossier</h2>
          <label class="field-label">Nom du dossier
            <input type="text" [(ngModel)]="nom" placeholder="ex. Maison Dupont" />
          </label>
          <label class="field-label">Type de bien et de DPE
            <select [(ngModel)]="methode">
              <option value="">— Choisir —</option>
              @for (g of types(); track g.label) {
                <optgroup [label]="g.label">
                  @for (o of g.options; track o.code) { <option [value]="o.code">{{ o.label }}</option> }
                </optgroup>
              }
            </select>
          </label>
          <label class="field-label">Version du modèle de données
            <select [(ngModel)]="version">
              @for (v of versions; track v) { <option [value]="v">{{ v }} — {{ versionLabel(v) }}</option> }
            </select>
          </label>
          <p class="muted small">Seule la version en vigueur est proposée. Le dossier démarre vide : seuls le type de bien, le modèle (DPE 3CL logement) et la version sont écrits ; tout le reste est à renseigner.</p>
          <button class="primary" [disabled]="!methode" (click)="create()">Créer le dossier</button>
        </section>
      </div>

      <section class="card">
        <h2>Reprendre un dossier</h2>
        @if (drafts().length === 0) {
          <p class="muted">Aucun brouillon enregistré dans ce navigateur.</p>
        } @else {
          <table class="table">
            <thead><tr><th>Dossier</th><th>Origine</th><th>Dernière modification</th><th>Résultats</th><th></th></tr></thead>
            <tbody>
              @for (d of drafts(); track d.id) {
                <tr>
                  <td><a href="" (click)="$event.preventDefault(); open(d.id)">{{ d.nom }}</a></td>
                  <td>{{ d.origine === 'import' ? 'Import ' + (d.nomFichier ?? '') : 'Nouveau dossier' }}</td>
                  <td>{{ date(d.modifieLe) }}</td>
                  <td>@if (d.resultatsObsoletes) { <span class="badge warn">À recalculer</span> } @else { — }</td>
                  <td class="right"><button class="link danger" (click)="remove(d)">Supprimer</button></td>
                </tr>
              }
            </tbody>
          </table>
        }
      </section>
    </main>
  `,
})
export class AccueilComponent implements OnInit {
  private readonly svc = inject(DossierService);
  private readonly store = inject(DraftStore);
  private readonly nav = inject(NavService);

  protected readonly over = signal(false);
  protected readonly busy = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly drafts = signal<DraftSummary[]>([]);
  protected readonly types = signal<TypeGroup[]>([]);
  protected readonly versions = VERSIONS_CREATION;
  private versionLabels: Record<string, string> = {};
  protected nom = '';
  protected methode = '';
  protected version = VERSIONS_CREATION[0];

  async ngOnInit(): Promise<void> {
    this.refresh();
    const last = this.svc.lastId();
    if (last && /^#\/[a-z]/.test(location.hash)) {
      const state = this.nav.state();
      try {
        await this.svc.openDraft(last);
        this.nav.go(state.tab, state.section, state.uid, state.field);
        return;
      } catch {
        /* brouillon disparu : on reste sur l'accueil */
      }
    }
    const schema = await this.svc.schemas.forVersion(this.version);
    this.versionLabels = schema?.def('dpe/administratif/enum_version_id')?.enumLabels ?? {};
    const labels = schema?.def('dpe/logement/caracteristique_generale/enum_methode_application_dpe_log_id')?.enumLabels ?? {};
    const groups: TypeGroup[] = [
      { label: 'Maison individuelle', options: [] },
      { label: 'Appartement', options: [] },
      { label: 'Immeuble collectif', options: [] },
      { label: 'Appartement généré à partir du DPE immeuble', options: [] },
      { label: 'Issu d\'une étude thermique réglementaire (RT2012 / RE2020)', options: [] },
    ];
    for (const [code, label] of Object.entries(labels)) {
      const g = /étude/.test(label) ? 4 : /généré/.test(label) ? 3 : /immeuble collectif/.test(label) ? 2 : /appartement/.test(label) ? 1 : 0;
      groups[g].options.push({ code, label: label.charAt(0).toUpperCase() + label.slice(1) });
    }
    this.types.set(groups.filter((g) => g.options.length));
  }

  protected versionLabel(v: string): string {
    return this.versionLabels[v] ?? '';
  }

  private async refresh(): Promise<void> {
    try {
      this.drafts.set(await this.store.list());
    } catch {
      this.drafts.set([]);
    }
  }

  protected onDrop(e: DragEvent): void {
    e.preventDefault();
    this.over.set(false);
    const f = e.dataTransfer?.files?.[0];
    if (f) void this.load(f);
  }

  protected onFile(e: Event): void {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0];
    input.value = '';
    if (f) void this.load(f);
  }

  private async load(f: File): Promise<void> {
    this.busy.set(true);
    this.error.set(null);
    try {
      await this.svc.importFile(f);
      this.nav.go('synthese');
    } catch (e) {
      this.error.set(e instanceof Error ? e.message : String(e));
    } finally {
      this.busy.set(false);
    }
  }

  protected async create(): Promise<void> {
    try {
      await this.svc.create({ version: this.version, methodeApplication: this.methode }, this.nom.trim() || 'Nouveau dossier');
      this.nav.go('synthese');
    } catch (e) {
      this.error.set(e instanceof Error ? e.message : String(e));
    }
  }

  protected async open(id: string): Promise<void> {
    try {
      await this.svc.openDraft(id);
      this.nav.go('synthese');
    } catch (e) {
      this.error.set(e instanceof Error ? e.message : String(e));
    }
  }

  protected async remove(d: DraftSummary): Promise<void> {
    if (!confirm(`Supprimer définitivement le brouillon « ${d.nom} » de ce navigateur ?`)) return;
    await this.store.delete(d.id);
    this.refresh();
  }

  protected date(iso: string): string {
    return new Date(iso).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' });
  }
}
