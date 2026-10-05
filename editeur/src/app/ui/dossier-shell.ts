import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DossierService } from '../services/dossier.service';
import { NavService } from '../services/nav.service';
import { TabKey } from '../core/metier/catalog';
import { ObjectView } from '../core/metier/model';
import { SyntheseComponent } from './synthese';
import { TabViewComponent } from './tab-view';
import { ControlesComponent } from './controles';
import { frDate, STATUS_LABELS } from './labels';

@Component({
  selector: 'app-dossier-shell',
  imports: [FormsModule, SyntheseComponent, TabViewComponent, ControlesComponent],
  template: `
    @let d = svc.dossier()!;
    @let m = svc.model()!;
    <header class="topbar">
      <button class="link" (click)="close()" title="Retour à l'accueil">← Accueil</button>
      <div class="title">
        @if (editingName()) {
          <input class="name-input" [ngModel]="d.meta.nom" (keydown.enter)="rename($any($event.target).value)" (blur)="rename($any($event.target).value)" autofocus />
        } @else {
          <h1 (dblclick)="editingName.set(true)" title="Double-cliquer pour renommer">{{ d.meta.nom }}</h1>
        }
        <span class="badge" [class.warn]="d.format.niveauSupport !== 'complet'">{{ d.format.libelleFamille }} · modèle {{ d.format.enumVersionId ?? '?' }}</span>
        @if (d.meta.resultatsObsoletes) { <span class="badge warn" title="Des données d'entrée ont changé depuis l'import">Résultats à recalculer</span> }
      </div>
      <div class="search">
        <input type="search" placeholder="Rechercher un objet…" [ngModel]="query()" (ngModelChange)="query.set($event)" (keydown.escape)="query.set('')" />
        @if (results().length) {
          <ul class="search-results">
            @for (o of results(); track o.uid) {
              <li><a href="" (click)="$event.preventDefault(); openResult(o)"><strong>{{ o.title }}</strong> <span class="muted">{{ o.label }}</span></a></li>
            }
          </ul>
        }
      </div>
      <div class="actions">
        <button (click)="svc.undo()" [disabled]="!d.canUndo()" [title]="d.undoLabel() ? 'Annuler : ' + d.undoLabel() + ' (Ctrl+Z)' : 'Rien à annuler'">↶ Annuler</button>
        <button (click)="svc.redo()" [disabled]="!d.canRedo()" [title]="d.redoLabel() ? 'Rétablir : ' + d.redoLabel() + ' (Ctrl+Maj+Z)' : 'Rien à rétablir'">↷ Rétablir</button>
        <span class="save" [class]="'save save-' + svc.saveStatus()" [title]="'Brouillon conservé dans ce navigateur'">
          @switch (svc.saveStatus()) {
            @case ('en_attente') { Modifications en cours… }
            @case ('enregistrement') { Enregistrement… }
            @case ('enregistre') { Brouillon enregistré {{ frDate(svc.savedAt()) }} }
            @case ('erreur') { Échec de l'enregistrement local }
            @default { — }
          }
        </span>
        <label class="toggle" title="Afficher chemins XML, codes bruts et détails techniques">
          <input type="checkbox" [ngModel]="svc.advanced()" (ngModelChange)="svc.advanced.set($event)" /> Mode avancé
        </label>
        <button class="primary" (click)="nav.go('controles')">Contrôles et export</button>
      </div>
    </header>

    <nav class="tabs" role="tablist">
      @for (t of m.tabs; track t.def.key) {
        <button role="tab" [class.active]="nav.state().tab === t.def.key" (click)="nav.go(t.def.key)" [title]="STATUS_LABELS[t.status]">
          {{ t.def.label }}
          @if (t.count) { <span class="count">{{ t.count }}</span> }
          @if (t.errors) { <span class="dot err" [title]="t.errors + ' erreur(s)'">{{ t.errors }}</span> }
          @else if (t.missing) { <span class="dot miss" [title]="t.missing + ' information(s) manquante(s)'">{{ t.missing }}</span> }
          @if (t.def.key === 'resultats' && d.meta.resultatsObsoletes) { <span class="dot miss" title="À recalculer">!</span> }
        </button>
      }
    </nav>

    <div class="content">
      @switch (nav.state().tab) {
        @case ('synthese') { <app-synthese /> }
        @case ('controles') { <app-controles /> }
        @default { <app-tab-view [tab]="currentTab()" /> }
      }
    </div>
  `,
})
export class DossierShellComponent {
  protected readonly svc = inject(DossierService);
  protected readonly nav = inject(NavService);
  protected readonly STATUS_LABELS = STATUS_LABELS;
  protected readonly frDate = frDate;
  protected readonly editingName = signal(false);
  protected readonly query = signal('');

  protected readonly currentTab = computed(() => this.nav.state().tab as TabKey);
  protected readonly results = computed<ObjectView[]>(() => {
    const q = this.query().trim().toLowerCase();
    const m = this.svc.model();
    if (!q || !m) return [];
    return [...m.objects.values()].filter((o) => o.searchText.includes(q)).slice(0, 15);
  });

  protected rename(nom: string): void {
    this.editingName.set(false);
    if (nom.trim() && nom.trim() !== this.svc.dossier()!.meta.nom) this.svc.rename(nom.trim());
  }

  protected openResult(o: ObjectView): void {
    this.query.set('');
    this.nav.go(o.tab, o.section, o.uid);
  }

  protected close(): void {
    this.svc.close();
    this.nav.reset();
  }
}
