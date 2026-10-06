import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DossierService } from '../services/dossier.service';
import { DraftStore, DraftSummary } from '../services/draft-store';
import { NavService } from '../services/nav.service';
import { VERSIONS_CREATION } from '../core/schema/schema-registry';
import { distinct, findCode, MethodeEntry, parseMethodes } from '../core/metier/methode-application';
import { AdresseBan } from '../core/localisation/geocodage';
import { AdresseBanComponent } from './adresse-ban';


@Component({
  selector: 'app-accueil',
  imports: [FormsModule, AdresseBanComponent],
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
          <div class="field-label">Adresse du bien <span class="muted">(facultatif)</span>
            <app-adresse-ban (choisie)="adresse.set($event)" />
            @if (adresse(); as a) { <span class="ok-text small">✓ {{ a.label }} — géolocalisée, carte et fond de plan disponibles</span> }
          </div>
          <label class="field-label">Type de bien
            <select [ngModel]="bien()" (ngModelChange)="setBien($event)">
              <option value="">— Choisir —</option>
              @for (b of biens(); track b) { <option [value]="b">{{ b }}</option> }
            </select>
          </label>
          @if (chauffages().length) {
            <label class="field-label">Chauffage
              <select [ngModel]="chauffage()" (ngModelChange)="setChauffage($event)">
                @if (chauffages().length > 1) { <option value="">— Choisir —</option> }
                @for (c of chauffages(); track c) { <option [value]="c">{{ c }}</option> }
              </select>
            </label>
          }
          @if (ecsList().length) {
            <label class="field-label">Eau chaude sanitaire
              <select [ngModel]="ecs()" (ngModelChange)="ecs.set($event)">
                @if (ecsList().length > 1) { <option value="">— Choisir —</option> }
                @for (c of ecsList(); track c) { <option [value]="c">{{ c }}</option> }
              </select>
            </label>
          }
          <label class="field-label">Version du modèle de données
            <select [(ngModel)]="version">
              @for (v of versions; track v) { <option [value]="v">{{ v }} — {{ versionLabel(v) }}</option> }
            </select>
          </label>
          <p class="muted small">Seule la version en vigueur est proposée. Le dossier démarre vide : seuls le type de bien, le modèle (DPE 3CL logement) et la version sont écrits ; tout le reste est à renseigner.</p>
          <button class="primary" [disabled]="!methode()" (click)="create()">Créer le dossier</button>
        </section>
      </div>

      <section class="card">
        <h2>Reprendre un dossier</h2>
        @if (drafts().length === 0) {
          <p class="muted">Aucun brouillon enregistré dans ce navigateur.</p>
        } @else {
          <table class="table">
            <thead><tr><th>Dossier</th><th>Adresse</th><th>Type</th><th>Étiquettes</th><th>Dernière modification</th><th>Résultats</th><th></th></tr></thead>
            <tbody>
              @for (d of drafts(); track d.id) {
                <tr>
                  @let r = d.resume;
                  <td><a href="" (click)="$event.preventDefault(); open(d.id)">{{ d.nom }}</a>
                    <div class="muted small">{{ d.origine === 'import' ? 'Import ' + (d.nomFichier ?? '') : 'Créé dans l’éditeur' }}</div></td>
                  <td>@if (r.adresse) { {{ r.adresse }} } @else { <span class="muted">—</span> }</td>
                  <td>@if (r.type) { {{ r.type }} } @else { <span class="muted">—</span> }
                    @if (r.surface || r.logements) { <div class="muted small">@if (r.logements) { {{ r.logements }} logement{{ r.logements > 1 ? 's' : '' }} · } @if (r.surface) { {{ fmt(r.surface) }} m² }</div> }</td>
                  <td class="nowrap">
                    @if (r.classeEnergie || r.classeClimat) {
                      <span class="classe" [attr.data-c]="r.classeEnergie" title="Étiquette énergie">{{ r.classeEnergie ?? '?' }}</span>
                      <span class="classe climat" [attr.data-c]="r.classeClimat" title="Étiquette climat">{{ r.classeClimat ?? '?' }}</span>
                    } @else { <span class="muted">—</span> }
                  </td>
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

  protected fmt(n: number): string {
    return n.toLocaleString('fr-FR', { maximumFractionDigits: 1 });
  }
  private readonly store = inject(DraftStore);
  private readonly nav = inject(NavService);

  protected readonly over = signal(false);
  protected readonly busy = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly drafts = signal<DraftSummary[]>([]);
  /** type de DPE décomposé en bien / chauffage / ECS (libellés officiels) */
  private readonly methodes = signal<MethodeEntry[]>([]);
  protected readonly bien = signal('');
  protected readonly chauffage = signal('');
  protected readonly ecs = signal('');
  protected readonly biens = computed(() => distinct(this.methodes().map((e) => e.bien)));
  protected readonly chauffages = computed(() => distinct(this.methodes().filter((e) => e.bien === this.bien()).map((e) => e.chauffage)));
  protected readonly ecsList = computed(() =>
    distinct(this.methodes().filter((e) => e.bien === this.bien() && e.chauffage === (this.chauffage() || null)).map((e) => e.ecs)),
  );
  protected readonly methode = computed(() =>
    this.bien() ? findCode(this.methodes(), this.bien(), this.chauffage() || null, this.ecs() || null) : null,
  );
  protected readonly versions = VERSIONS_CREATION;
  private versionLabels: Record<string, string> = {};
  protected nom = '';
  protected readonly adresse = signal<AdresseBan | null>(null);
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
    this.methodes.set(parseMethodes(labels));
  }

  protected setBien(b: string): void {
    this.bien.set(b);
    const ch = this.chauffages();
    this.setChauffage(ch.length === 1 ? ch[0] : '');
  }

  protected setChauffage(c: string): void {
    this.chauffage.set(c);
    const e = this.ecsList();
    this.ecs.set(e.length === 1 ? e[0] : '');
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
      const a = this.adresse() ?? undefined;
      await this.svc.create({ version: this.version, methodeApplication: this.methode()! }, this.nom.trim() || a?.label || 'Nouveau dossier', a);
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
