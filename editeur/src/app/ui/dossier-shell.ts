import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { ObjectView, SectionView, TabView } from '../core/metier/model';
import { SyntheseComponent } from './synthese';
import { TabViewComponent } from './tab-view';
import { ControlesComponent } from './controles';
import { frDate } from './labels';

/**
 * Cadre du dossier : en-tête compact, navigation latérale unique (groupes et
 * sections, avec compteurs et signalement seulement quand il y a quelque chose
 * à faire), zone de travail.
 */
@Component({
  selector: 'app-dossier-shell',
  imports: [FormsModule, SyntheseComponent, TabViewComponent, ControlesComponent],
  template: `
    @let d = svc.dossier()!;
    <header class="topbar">
      <button class="link" (click)="close()" title="Retour à l'accueil">←</button>
      <div class="title">
        @if (editingName()) {
          <input class="name-input" [ngModel]="d.meta.nom" (keydown.enter)="rename($any($event.target).value)" (blur)="rename($any($event.target).value)" />
        } @else {
          <h1 (dblclick)="editingName.set(true)" title="Double-cliquer pour renommer">{{ d.meta.nom }}</h1>
        }
        <span class="muted small">{{ d.format.libelleMethodeApplication ? cap(d.format.libelleMethodeApplication) : d.format.libelleFamille }} · modèle {{ d.format.enumVersionId ?? '?' }}</span>
        @if (d.meta.resultatsObsoletes) { <span class="badge warn" title="Des données d'entrée ont changé depuis l'import">résultats à recalculer</span> }
      </div>
      <div class="search">
        <input type="search" placeholder="Rechercher…" [ngModel]="query()" (ngModelChange)="query.set($event)" (keydown.escape)="query.set('')" />
        @if (results().length) {
          <ul class="search-results">
            @for (o of results(); track o.uid) {
              <li><a href="" (click)="$event.preventDefault(); openResult(o)"><strong>{{ o.title }}</strong> <span class="muted">{{ o.label }}</span></a></li>
            }
          </ul>
        }
      </div>
      <div class="actions">
        <button class="icon" (click)="svc.undo()" [disabled]="!d.canUndo()" [title]="d.undoLabel() ? 'Annuler : ' + d.undoLabel() + ' (Ctrl+Z)' : 'Rien à annuler'">↶</button>
        <button class="icon" (click)="svc.redo()" [disabled]="!d.canRedo()" [title]="d.redoLabel() ? 'Rétablir : ' + d.redoLabel() : 'Rien à rétablir'">↷</button>
        <span class="save" [class]="'save save-' + svc.saveStatus()" title="Brouillon conservé dans ce navigateur">
          @switch (svc.saveStatus()) {
            @case ('en_attente') { • enregistrement… }
            @case ('enregistrement') { • enregistrement… }
            @case ('enregistre') { ✓ enregistré {{ frDate(svc.savedAt()) }} }
            @case ('erreur') { ✕ non enregistré }
          }
        </span>
        <div class="dropdown">
          <button class="icon" (click)="menu.set(!menu())" title="Options">⚙</button>
          @if (menu()) {
            <div class="menu right" (mouseleave)="menu.set(false)">
              <label class="menu-check"><input type="checkbox" [ngModel]="svc.advanced()" (ngModelChange)="svc.advanced.set($event)" /> Mode avancé (chemins XML, codes)</label>
              @if (d.original) { <button (click)="menu.set(false); svc.download(d.original, d.meta.nomFichier ?? 'original.xml', 'application/xml')">Télécharger le fichier d'origine</button> }
              <button (click)="menu.set(false); downloadDraft()">Télécharger le brouillon</button>
              <button (click)="menu.set(false); editingName.set(true)">Renommer le dossier</button>
            </div>
          }
        </div>
        <button class="primary" (click)="nav.go('controles')">Vérifier et exporter</button>
      </div>
    </header>

    <div class="app-body">
      <nav class="sidebar">
        <button class="nav-item top" [class.active]="nav.state().tab === 'synthese'" (click)="nav.go('synthese')">Synthèse</button>
        @for (t of groups(); track t.def.key) {
          <div class="nav-group">
            <div class="nav-group-title">{{ t.def.label }}
              @if (t.errors) { <span class="dot err">{{ t.errors }}</span> } @else if (t.missing) { <span class="dot miss">{{ t.missing }}</span> }
            </div>
            @for (s of t.sections; track s.def.key) {
              @if (!isDim(s) || isActive(t, s) || expanded().has(t.def.key)) {
              <button class="nav-item" [class.active]="isActive(t, s)" [class.dim]="isDim(s)" (click)="open(t, s)" [title]="s.note ?? ''">
                <span class="nav-label">{{ s.def.label }}</span>
                @if (s.def.kind && s.count) { <span class="count">{{ s.count }}</span> }
                @if (s.errors) { <span class="dot err" [title]="s.errors + ' erreur(s)'">{{ s.errors }}</span> }
                @else if (s.missing) { <span class="dot miss" [title]="s.missing + ' à compléter'">{{ s.missing }}</span> }
              </button>
              }
            }
            @if (dimCount(t) > 0) {
              <button class="nav-more" (click)="toggle(t.def.key)">{{ expanded().has(t.def.key) ? '− masquer les sections vides' : '+ ' + dimCount(t) + ' section(s) vide(s)' }}</button>
            }
          </div>
        }
        <button class="nav-item top" [class.active]="nav.state().tab === 'controles'" (click)="nav.go('controles')">Contrôles et export</button>
      </nav>

      <main class="content">
        @switch (nav.state().tab) {
          @case ('synthese') { <app-synthese /> }
          @case ('controles') { <app-controles /> }
          @default { <app-tab-view [tab]="$any(nav.state().tab)" /> }
        }
      </main>
    </div>
  `,
})
export class DossierShellComponent {
  protected readonly svc = inject(DossierService);
  protected readonly nav = inject(NavService);
  protected readonly frDate = frDate;
  protected readonly editingName = signal(false);
  protected readonly menu = signal(false);
  protected readonly query = signal('');

  protected readonly groups = computed(() => (this.svc.model()?.tabs ?? []).filter((t) => t.sections.length));
  protected readonly results = computed<ObjectView[]>(() => {
    const q = this.query().trim().toLowerCase();
    const m = this.svc.model();
    if (!q || !m) return [];
    return [...m.objects.values()].filter((o) => o.searchText.includes(q)).slice(0, 15);
  });

  protected cap(s: string): string {
    return s.charAt(0).toUpperCase() + s.slice(1);
  }

  protected readonly expanded = signal(new Set<string>());

  protected toggle(key: string): void {
    const next = new Set(this.expanded());
    if (next.has(key)) next.delete(key);
    else next.add(key);
    this.expanded.set(next);
  }

  protected dimCount(t: TabView): number {
    return t.sections.filter((s) => this.isDim(s) && !this.isActive(t, s)).length;
  }

  protected isActive(t: TabView, s: SectionView): boolean {
    const st = this.nav.state();
    return st.tab === t.def.key && (st.section === s.def.key || (!st.section && t.sections[0] === s));
  }

  /** Section sans contenu ni action requise : atténuée, toujours accessible. */
  protected isDim(s: SectionView): boolean {
    return s.status === 'vide' || s.status === 'non_applicable' || s.status === 'absent';
  }

  protected open(t: TabView, s: SectionView): void {
    this.nav.go(t.def.key, s.def.key, s.def.singleton ? s.singletonUid : null);
  }

  protected rename(nom: string): void {
    this.editingName.set(false);
    if (nom.trim() && nom.trim() !== this.svc.dossier()!.meta.nom) this.svc.rename(nom.trim());
  }

  protected openResult(o: ObjectView): void {
    this.query.set('');
    this.nav.go(o.tab, o.section, o.uid);
  }

  protected downloadDraft(): void {
    this.svc.download(JSON.stringify(this.svc.dossier()!.toDraft()), `${this.svc.baseName()}.brouillon.json`, 'application/json');
  }

  protected close(): void {
    this.svc.close();
    this.nav.reset();
  }
}
